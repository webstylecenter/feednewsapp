<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { api } from '../api.js';
import { applyTheme } from '../theme.js';
import { faviconUrl } from '../presentation.js';

const props = defineProps({ user: { type: Object, required: true } });
const emit = defineEmits(['navigate', 'logout']);
const state = ref({ subscriptions: [], availableFeeds: [], categories: [], preferences: {} });
const admin = ref(null);
const error = ref('');
const message = ref('');
const failedFavicons = ref(new Set());
const form = ref({ url: '', category: '', color: '#337dff', autoPin: false });
const pendingUserRemoval = ref(null);
const editingFeedUuid = ref('');
let notificationTimer;

async function load() {
    state.value = await api('/api/settings');
    applyTheme(state.value.preferences.theme || 'auto');
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

function toggleFeedEditor(subscription) {
    editingFeedUuid.value = editingFeedUuid.value === subscription.feed.uuid
        ? ''
        : subscription.feed.uuid;
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

async function follow(feed) {
    await api(`/api/settings/feeds/${feed.uuid}/follow`, {
        method: 'POST',
        body: { color: feed.color, autoPin: false },
    });
    await load();
}

async function update(subscription) {
    await api(`/api/settings/feeds/${subscription.feed.uuid}`, {
        method: 'PATCH',
        body: { color: subscription.color, autoPin: subscription.autoPin },
    });
    editingFeedUuid.value = '';
    notify(`${subscription.feed.name} updated.`);
}

async function remove(subscription) {
    if (!window.confirm(`Stop following ${subscription.feed.name}?`)) return;

    try {
        await api(`/api/settings/feeds/${subscription.feed.uuid}`, { method: 'DELETE' });
        editingFeedUuid.value = '';
        notify(`${subscription.feed.name} removed.`);
        await load();
    } catch (exception) {
        notify(exception.message, true);
    }
}

async function savePreference(setting, value) {
    await api(`/api/settings/preferences/${encodeURIComponent(setting)}`, { method: 'PUT', body: { value } });
    state.value.preferences[setting] = value;
    if (setting === 'theme') applyTheme(value);
    notify('Preference saved.');
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
                <h2>Add a feed</h2>
                <p class="muted">Paste an RSS/Atom URL, a website containing feed discovery metadata, or a YouTube channel URL.</p>
                <form class="settings-add-grid" @submit.prevent="addFeed">
                    <label class="wide">Website or feed URL<input v-model.trim="form.url" type="url" required placeholder="https://example.com"></label>
                    <label>Category<select v-model="form.category"><option value="">None</option><option v-for="category in state.categories" :key="category.uuid" :value="category.uuid">{{ category.name }}</option></select></label>
                    <label>Color<input v-model="form.color" type="color"></label>
                    <label class="check-row"><input v-model="form.autoPin" type="checkbox"><span>Auto-pin new items</span></label>
                    <button class="primary">Add feed</button>
                </form>
            </section>

            <section class="settings-section card">
                <h2>Your feeds</h2>
                <div class="subscription-list">
                    <div
                        v-for="subscription in state.subscriptions"
                        :key="subscription.feed.uuid"
                        class="subscription-row"
                        :class="{ editing: editingFeedUuid === subscription.feed.uuid }"
                    >
                        <span class="feed-source-icon settings-feed-icon">
                            <img
                                v-if="showFavicon(`subscription-${subscription.feed.uuid}`, subscription.feed.url)"
                                :src="faviconUrl(subscription.feed.url)"
                                alt=""
                                loading="lazy"
                                @error="markFaviconFailed(`subscription-${subscription.feed.uuid}`)"
                            >
                            <span v-else class="feed-dot" :style="{ '--feed-color': subscription.color }"></span>
                        </span>
                        <div class="subscription-copy">
                            <strong>{{ subscription.feed.name }}</strong>
                            <small class="subscription-url">{{ subscription.feed.url }}</small>
                        </div>
                        <button class="secondary small subscription-edit-toggle" @click="toggleFeedEditor(subscription)">
                            {{ editingFeedUuid === subscription.feed.uuid ? 'Close' : 'Edit' }}
                        </button>
                        <div class="subscription-controls">
                            <input v-model="subscription.color" type="color" :aria-label="`${subscription.feed.name} color`">
                            <label class="compact-check"><input v-model="subscription.autoPin" type="checkbox"> Auto-pin</label>
                            <button class="secondary small" @click="update(subscription)">Save</button>
                            <button class="danger small" @click="remove(subscription)">Remove</button>
                        </div>
                    </div>
                </div>
                <p v-if="state.subscriptions.length === 0" class="muted">No feeds followed yet.</p>
            </section>

            <section v-if="state.availableFeeds.length" class="settings-section card">
                <h2>Available feeds</h2>
                <div class="available-grid">
                    <button v-for="feed in state.availableFeeds" :key="feed.uuid" class="available-feed" @click="follow(feed)">
                        <span class="feed-dot" :style="{ '--feed-color': feed.color }"></span>
                        <span><strong>{{ feed.name }}</strong><small>{{ feed.category || 'Uncategorised' }}</small></span>
                        <span>＋</span>
                    </button>
                </div>
            </section>

            <section class="settings-section card">
                <h2>Preferences</h2>
                <div class="preference-grid">
                    <label>Theme
                        <select :value="state.preferences.theme || 'auto'" @change="savePreference('theme', $event.target.value)">
                            <option value="auto">Automatic</option><option value="light">Light</option><option value="dark">Dark</option>
                        </select>
                    </label>
                    <label class="wide">Google Calendar embed URL
                        <input :value="state.preferences.google_calendar || ''" type="url" placeholder="https://calendar.google.com/..." @change="savePreference('google_calendar', $event.target.value)">
                    </label>
                </div>
            </section>

            <section v-if="props.user.isAdmin && admin" class="settings-section card admin-section">
                <h2>Administration</h2>
                <h3>Users</h3>
                <div class="table-wrap"><table><thead><tr><th>Name</th><th>Email</th><th>Last login</th><th></th></tr></thead><tbody>
                    <tr v-for="account in admin.users" :key="account.uuid"><td>{{ account.name }}</td><td>{{ account.email }}</td><td>{{ account.lastLogin ? new Date(account.lastLogin).toLocaleString() : 'Never' }}</td><td><button
                                v-if="!account.isAdmin"
                                class="danger small user-remove-button"
                                :class="{ confirming: pendingUserRemoval === account.uuid }"
                                @click="removeUser(account)"
                            >
                                {{ pendingUserRemoval === account.uuid ? 'Are you sure?' : 'Remove' }}
                            </button></td></tr>
                </tbody></table></div>
                <h3>Latest feeds</h3>
                <div class="table-wrap"><table><thead><tr><th>Name</th><th>URL</th><th>Updated</th></tr></thead><tbody>
                    <tr v-for="feed in admin.feeds" :key="feed.uuid"><td>{{ feed.name }}</td><td class="break-all">{{ feed.url }}</td><td>{{ new Date(feed.updatedAt).toLocaleString() }}</td></tr>
                </tbody></table></div>
                <h3>Error log</h3>
                <div class="table-wrap"><table><thead><tr><th>Type</th><th>Occurrences</th><th>Feed</th><th>Message</th></tr></thead><tbody>
                    <tr v-for="entry in admin.errors" :key="`${entry.type}-${entry.updatedAt}-${entry.message}`"><td>{{ entry.type }}</td><td>{{ entry.occurrences }}</td><td>{{ entry.feed || '—' }}</td><td class="break-all">{{ entry.message }}</td></tr>
                </tbody></table></div>
            </section>
        </div>
        <div v-if="message" class="toast-notification" role="status">{{ message }}</div>
        <div v-if="error" class="toast-notification error" role="alert">{{ error }}</div>
    </section>
</template>

<style scoped>
.subscription-copy {
    min-width: 0;
}

.subscription-copy strong,
.subscription-copy small {
    display: block;
}

.subscription-url {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.subscription-controls {
    display: contents;
}

.subscription-edit-toggle {
    display: none;
}

@media (max-width: 800px) {
    .subscription-list {
        display: grid;
        gap: .55rem;
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
