<script setup>
import { ref } from 'vue';
import { api } from '../api.js';
import { faviconUrl } from '../presentation.js';

const props = defineProps({
    feeds: { type: Array, required: true },
    categories: { type: Array, required: true },
});
const emit = defineEmits(['changed', 'notify', 'preview']);
const failedFavicons = ref(new Set());
const pendingFeedRemoval = ref('');

function markFaviconFailed(uuid) {
    const failed = new Set(failedFavicons.value);
    failed.add(uuid);
    failedFavicons.value = failed;
}

function showFavicon(feed) {
    return faviconUrl(feed.url) !== '' && !failedFavicons.value.has(feed.uuid);
}

async function updateFeedCategory(feed, categoryUuid) {
    try {
        await api(`/api/admin/feeds/${feed.uuid}`, {
            method: 'PATCH',
            body: { category: categoryUuid || null },
        });
        emit('notify', `${feed.name} updated.`);
        emit('changed');
    } catch (exception) {
        emit('notify', exception.message, true);
        emit('changed');
    }
}

async function removeFeed(feed) {
    if (pendingFeedRemoval.value !== feed.uuid) {
        pendingFeedRemoval.value = feed.uuid;
        return;
    }

    try {
        await api(`/api/admin/feeds/${feed.uuid}`, { method: 'DELETE' });
        pendingFeedRemoval.value = '';
        emit('notify', `${feed.name} removed for all users.`);
        emit('changed');
    } catch (exception) {
        pendingFeedRemoval.value = '';
        emit('notify', exception.message, true);
    }
}
</script>

<template>
    <div class="admin-feed-management">
        <div class="admin-feed-heading">
            <div>
                <h3>Other feeds</h3>
                <p class="muted">Feeds available globally that you do not currently follow.</p>
            </div>
            <span class="muted">{{ feeds.length }} sources</span>
        </div>

        <div v-if="feeds.length" class="admin-feed-list settings-scroll-area">
            <div v-for="feed in feeds" :key="feed.uuid" class="admin-feed-row">
                <span class="feed-source-icon settings-feed-icon">
                    <img
                        v-if="showFavicon(feed)"
                        :src="faviconUrl(feed.url)"
                        alt=""
                        loading="lazy"
                        @error="markFaviconFailed(feed.uuid)"
                    >
                    <span v-else class="feed-dot" :style="{ '--feed-color': feed.color }"></span>
                </span>
                <div class="admin-feed-copy">
                    <strong>{{ feed.name }}</strong>
                    <small>{{ feed.url }}</small>
                </div>
                <label class="admin-feed-category">
                    <span>Category</span>
                    <select
                        :value="feed.categoryUuid || ''"
                        @change="updateFeedCategory(feed, $event.target.value)"
                    >
                        <option value="">Uncategorised</option>
                        <option v-for="category in categories" :key="category.uuid" :value="category.uuid">
                            {{ category.name }}
                        </option>
                    </select>
                </label>
                <div class="admin-feed-actions">
                    <button class="secondary small" @click="emit('preview', feed)">Preview</button>
                    <button
                        class="danger small"
                        :class="{ confirming: pendingFeedRemoval === feed.uuid }"
                        @click="removeFeed(feed)"
                    >
                        {{ pendingFeedRemoval === feed.uuid ? 'Are you sure?' : 'Delete for everyone' }}
                    </button>
                </div>
            </div>
        </div>
        <p v-else class="muted">There are no other feeds.</p>
    </div>
</template>

<style scoped>
.admin-feed-management {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--border);
}

.admin-feed-heading {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: .8rem;
}

.admin-feed-heading h3,
.admin-feed-heading p {
    margin: 0;
}

.admin-feed-heading p {
    margin-top: .2rem;
}

.admin-feed-list {
    display: grid;
    gap: .65rem;
    max-height: min(620px, 65vh);
    padding-right: .25rem;
}

.admin-feed-row {
    display: grid;
    grid-template-columns: 32px minmax(180px, 1fr) minmax(180px, 240px) auto;
    gap: .75rem;
    align-items: center;
    padding: .7rem 0;
    border-bottom: 1px solid var(--border);
}

.admin-feed-copy {
    min-width: 0;
}

.admin-feed-copy strong,
.admin-feed-copy small {
    display: block;
}

.admin-feed-copy small {
    overflow: hidden;
    color: var(--muted);
    text-overflow: ellipsis;
    white-space: nowrap;
}

.admin-feed-category span {
    display: block;
    margin-bottom: .25rem;
    color: var(--muted);
    font-size: .78rem;
}

.admin-feed-category select {
    width: 100%;
}

.admin-feed-actions {
    display: flex;
    gap: .5rem;
    justify-content: flex-end;
}

.admin-feed-actions .confirming {
    background: var(--danger);
    color: #fff;
}

@media (max-width: 900px) {
    .admin-feed-row {
        grid-template-columns: 32px minmax(0, 1fr);
    }

    .admin-feed-category,
    .admin-feed-actions {
        grid-column: 2;
    }

    .admin-feed-actions {
        justify-content: flex-start;
        flex-wrap: wrap;
    }
}

@media (max-width: 600px) {
    .admin-feed-heading {
        align-items: stretch;
        flex-direction: column;
    }
}
</style>
