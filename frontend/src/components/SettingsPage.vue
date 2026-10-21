<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { api } from '../api.js';
import { faviconUrl } from '../presentation.js';
import FeedPreviewDialog from './FeedPreviewDialog.vue';

const props = defineProps({ user: { type: Object, required: true } });
const emit = defineEmits(['navigate', 'logout']);
const state = ref({ subscriptions: [], availableFeeds: [], categories: [], preferences: {} });
const admin = ref(null);
const error = ref('');
const message = ref('');
const failedFavicons = ref(new Set());
const form = ref({ url: '', category: '', color: '#337dff', autoPin: false });
const pendingUserRemoval = ref(null);
const pendingFeedRemoval = ref(null);
const editingSubscriptionUuid = ref('');
const previewFeed = ref(null);
const previewData = ref(null);
const previewLoading = ref(false);
let notificationTimer;

async function load() {
    state.value = await api('/api/settings');
    if (props.user.isAdmin) admin.value = await api('/api/admin');
}

function notify(messageText, isError = false) {
    if (isError) {
        error.value = messageText;
        message.value = '';
    } else {
        message.value = messageText;
        error.value = '';
    }

    window.clearTimeout(notificationTimer);
    notificationTimer = window.setTimeout(() => {
        message.value = '';
        error.value = '';
    }, 2400);
}

function markFaviconFailed(key) {
    const failed = new Set(failedFavicons.value);
    failed.add(key);
    failedFavicons.value = failed;
}

function showFavicon(key, url) {
    return faviconUrl(url) !== '' && !failedFavicons.value.has(key);
}

function formatLastItem(value) {
    if (!value) return 'No items received yet';

    return `Latest item: ${new Date(value).toLocaleString('nl-NL', {
        dateStyle: 'medium',
        timeStyle: 'short',
    })}`;
}

function showFeedError(feed) {
    if (!feed.httpErrorStatus) return;

    notify(feed.httpErrorMessage || `Feed returned HTTP ${feed.httpErrorStatus}.`, true);
}

function toggleFeedEditor(subscription) {
    editingSubscriptionUuid.value = editingSubscriptionUuid.value === subscription.uuid
        ? ''
        : subscription.uuid;
    pendingFeedRemoval.value = null;
}

async function addFeed() {
    error.value = '';
    message.value = '';
    try {
        await api('/api/settings/feeds', {
            method: 'POST',
            body: {
                websiteUrl: form.value.url,
                category: form.value.category || null,
                color: form.value.color,
                autoPin: form.value.autoPin,
            },
        });
        form.value.url = '';
        notify('Feed added.');
        await load();
    } catch (exception) {
        notify(exception.message, true);
    }
}

async function openFeedPreview(feed) {
    previewFeed.value = feed;
    previewData.value = null;
    previewLoading.value = true;

    try {
        previewData.value = await api(`/api/settings/feeds/${feed.uuid}/preview`);
    } catch (exception) {
        previewFeed.value = null;
        notify(exception.message, true);
    } finally {
        previewLoading.value = false;
    }
}

function closeFeedPreview() {
    previewFeed.value = null;
    previewData.value = null;
    previewLoading.value = false;
}

async function follow(feed) {
    try {
        await api(`/api/settings/feeds/${feed.uuid}/follow`, {
            method: 'POST',
            body: { color: feed.color, autoPin: false },
        });
        closeFeedPreview();
        notify(`${feed.name} added.`);
        await load();
    } catch (exception) {
        notify(exception.message, true);
    }
}

async function update(subscription) {
    await api(`/api/settings/feeds/${subscription.uuid}`, {
        method: 'PATCH',
        body: { color: subscription.color, autoPin: subscription.autoPin },
    });
    editingSubscriptionUuid.value = '';
    pendingFeedRemoval.value = null;
    notify(`${subscription.feed.name} updated.`);
}

async function remove(subscription) {
    if (pendingFeedRemoval.value !== subscription.uuid) {
        pendingFeedRemoval.value = subscription.uuid;
        return;
    }

    try {
        await api(`/api/settings/feeds/${subscription.uuid}`, { method: 'DELETE' });
        editingSubscriptionUuid.value = '';
        pendingFeedRemoval.value = null;
        notify(`${subscription.feed.name} removed.`);
        await load();
    } catch (exception) {
        pendingFeedRemoval.value = null;
        notify(exception.message, true);
    }
}

async function savePreference(setting, value) {
    try {
        await api(`/api/settings/preferences/${encodeURIComponent(setting)}`, { method: 'PUT', body: { value } });
        state.value.preferences[setting] = value;
        notify('Preference saved.');
    } catch (exception) {
        notify(exception.message, true);
    }
}

async function removeUser(user) {
    if (pendingUserRemoval.value !== user.uuid) {
        pendingUserRemoval.value = user.uuid;
        return;
    }

    await api(`/api/admin/users/${user.uuid}`, { method: 'DELETE' });
    pendingUserRemoval.value = null;
    admin.value = await api('/api/admin');
}

onMounted(load);
onBeforeUnmount(() => window.clearTimeout(notificationTimer));
</script>

<template>
    <section class="settings-page">
        <header class="page-header">
            <button class="back-button light" @click="emit('navigate', '/')">← FeedNews</button>
            <h1>Feed & settings</h1>
            <button class="secondary" @click="emit('logout')">Sign out</button>
        </header>
        <div class="settings-content">
            <section class="settings-section card">
                <h2>Preferences</h2>
                <div class="preference-grid">
                    <label class="wide">Weather location
                        <input
                            :value="state.preferences.weather_location || ''"
                            type="text"
                            maxlength="120"
                            placeholder="Eindhoven, NL"
                            @change="savePreference('weather_location', $event.target.value)"
                        >
                    </label>
                    <label class="wide">Google Calendar embed URL
                        <input :value="state.preferences.google_calendar || ''" type="url" placeholder="https://calendar.google.com/..." @change="savePreference('google_calendar', $event.target.value)">
                    </label>
                </div>
            </section>

            <section class="settings-section card">
                <h2>Add a feed</h2>
                <p class="muted">Paste an RSS/Atom URL, a website containing feed discovery metadata, or a YouTube channel URL.</p>
                <form class="settings-add-grid" @submit.prevent="addFeed">
                    <label class="settings-feed-url">Website or feed URL<input v-model.trim="form.url" type="url" required placeholder="https://example.com"></label>
                    <label>Category<select v-model="form.category"><option value="">None</option><option v-for="category in state.categories" :key="category.uuid" :value="category.uuid">{{ category.name }}</option></select></label>
                    <label>Color<input v-model="form.color" type="color"></label>
                    <label class="check-row"><input v-model="form.autoPin" type="checkbox"><span>Auto-pin new items</span></label>
                    <button class="primary">Add feed</button>
                </form>
            </section>

            <section class="settings-section card">
                <h2>Your feeds</h2>
                <div class="subscription-list settings-scroll-area">
                    <div
                        v-for="subscription in state.subscriptions"
                        :key="subscription.uuid"
                        class="subscription-row"
                        :class="{ editing: editingSubscriptionUuid === subscription.uuid }"
                    >
                        <span class="feed-source-icon settings-feed-icon">
                            <img
                                v-if="showFavicon(`subscription-${subscription.uuid}`, subscription.feed.url)"
                                :src="faviconUrl(subscription.feed.url)"
                                alt=""
                                loading="lazy"
                                @error="markFaviconFailed(`subscription-${subscription.uuid}`)"
                            >
                            <span v-else class="feed-dot" :style="{ '--feed-color': subscription.color }"></span>
                        </span>
                        <div class="subscription-copy">
                            <div class="subscription-title-row">
                                <strong>{{ subscription.feed.name }}</strong>
                                <button
                                    v-if="subscription.feed.httpErrorStatus"
                                    type="button"
                                    class="feed-health-warning"
                                    :title="subscription.feed.httpErrorMessage || `HTTP ${subscription.feed.httpErrorStatus}`"
                                    @click="showFeedError(subscription.feed)"
                                >⚠ HTTP {{ subscription.feed.httpErrorStatus }}</button>
                            </div>
                            <small class="subscription-meta">{{ formatLastItem(subscription.feed.lastItemAt) }}</small>
                            <small class="subscription-url">{{ subscription.feed.url }}</small>
                        </div>
                        <button class="secondary small subscription-edit-toggle" @click="toggleFeedEditor(subscription)">
                            {{ editingSubscriptionUuid === subscription.uuid ? 'Close' : 'Edit' }}
                        </button>
                        <div class="subscription-controls">
                            <input v-model="subscription.color" type="color" :aria-label="`${subscription.feed.name} color`">
                            <label class="compact-check"><input v-model="subscription.autoPin" type="checkbox"> Auto-pin</label>
                            <button class="secondary small" @click="update(subscription)">Save</button>
                            <button
                                class="danger small feed-remove-button"
                                :class="{ confirming: pendingFeedRemoval === subscription.uuid }"
                                @click="remove(subscription)"
                            >
                                {{ pendingFeedRemoval === subscription.uuid ? 'Are you sure?' : 'Remove' }}
                            </button>
                        </div>
                    </div>
                </div>
                <p v-if="state.subscriptions.length === 0" class="muted">No feeds followed yet.</p>
            </section>

            <section v-if="state.availableFeeds.length" class="settings-section card">
                <h2>Available feeds</h2>
                <div class="available-grid settings-scroll-area">
                    <button v-for="feed in state.availableFeeds" :key="feed.uuid" class="available-feed" @click="openFeedPreview(feed)">
                        <span class="feed-dot" :style="{ '--feed-color': feed.color }"></span>
                        <span><strong>{{ feed.name }}</strong><small>{{ feed.category || 'Uncategorised' }}</small></span>
                        <span>＋</span>
                    </button>
                </div>
            </section>

            <section v-if="props.user.isAdmin && admin" class="settings-section card admin-section">
                <h2>Administration</h2>
                <h3>Users</h3>
                <div class="table-wrap settings-scroll-area"><table><thead><tr><th>Name</th><th>Email</th><th>Last login</th><th></th></tr></thead><tbody>
                    <tr v-for="account in admin.users" :key="account.uuid"><td>{{ account.name }}</td><td class="settings-break-word">{{ account.email }}</td><td>{{ account.lastLogin ? new Date(account.lastLogin).toLocaleString() : 'Never' }}</td><td><button
                                v-if="!account.isAdmin"
                                class="danger small user-remove-button"
                                :class="{ confirming: pendingUserRemoval === account.uuid }"
                                @click="removeUser(account)"
                            >
                                {{ pendingUserRemoval === account.uuid ? 'Are you sure?' : 'Remove' }}
                            </button></td></tr>
                </tbody></table></div>
                <h3>Latest feeds</h3>
                <div class="table-wrap settings-scroll-area"><table><thead><tr><th>Name</th><th>URL</th><th>Updated</th></tr></thead><tbody>
                    <tr v-for="feed in admin.feeds" :key="feed.uuid"><td>{{ feed.name }}</td><td class="settings-break-word">{{ feed.url }}</td><td>{{ new Date(feed.updatedAt).toLocaleString() }}</td></tr>
                </tbody></table></div>
                <h3>Error log</h3>
                <div class="table-wrap settings-scroll-area"><table><thead><tr><th>Type</th><th>Occurrences</th><th>Feed</th><th>Message</th></tr></thead><tbody>
                    <tr v-for="entry in admin.errors" :key="`${entry.type}-${entry.updatedAt}-${entry.message}`"><td>{{ entry.type }}</td><td>{{ entry.occurrences }}</td><td>{{ entry.feed || '—' }}</td><td class="settings-break-word">{{ entry.message }}</td></tr>
                </tbody></table></div>
            </section>
        </div>
        <FeedPreviewDialog
            :feed="previewFeed"
            :preview="previewData"
            :loading="previewLoading"
            @close="closeFeedPreview"
            @subscribe="follow"
        />
        <div v-if="message" class="toast-notification" role="status">{{ message }}</div>
        <div v-if="error" class="toast-notification error" role="alert">{{ error }}</div>
    </section>
</template>

<style scoped>
.subscription-list {
    max-height: min(620px, 65vh);
    padding-right: .25rem;
}

.subscription-copy {
    min-width: 0;
}

.subscription-copy strong,
.subscription-copy small {
    display: block;
}

.subscription-title-row {
    display: flex;
    align-items: center;
    gap: .5rem;
    min-width: 0;
}

.subscription-title-row strong {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.subscription-meta {
    margin-top: .2rem;
    color: var(--muted);
}

.subscription-url {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.feed-health-warning {
    flex: 0 0 auto;
    border: 1px solid color-mix(in srgb, var(--danger) 45%, var(--border));
    border-radius: 999px;
    padding: .18rem .42rem;
    background: color-mix(in srgb, var(--danger) 10%, var(--surface));
    color: var(--danger);
    font-size: .7rem;
    line-height: 1.2;
}

.subscription-controls {
    display: contents;
}

.subscription-edit-toggle {
    display: none;
}

.feed-remove-button.confirming {
    background: var(--danger);
    color: #fff;
}

@media (max-width: 800px) {
    .subscription-list {
        display: grid;
        gap: .55rem;
        max-height: min(560px, 62vh);
    }

    .subscription-row {
        grid-template-columns: 32px minmax(0, 1fr) auto;
        gap: .65rem;
        align-items: center;
        padding: .65rem 0;
    }

    .subscription-url,
    .subscription-controls {
        display: none;
    }

    .subscription-title-row {
        align-items: flex-start;
        flex-direction: column;
        gap: .25rem;
    }

    .subscription-edit-toggle {
        display: inline-block;
    }

    .subscription-row.editing {
        grid-template-columns: 32px minmax(0, 1fr) auto;
    }

    .subscription-row.editing .subscription-url {
        display: block;
        margin-top: .25rem;
        color: var(--muted);
        white-space: normal;
        overflow-wrap: anywhere;
    }

    .subscription-row.editing .subscription-controls {
        grid-column: 1 / -1;
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto auto;
        gap: .55rem;
        align-items: center;
        padding-top: .35rem;
    }

    .subscription-row.editing .subscription-controls > input[type='color'] {
        width: 46px;
        min-height: 38px;
        padding: .2rem;
    }
}
</style>
