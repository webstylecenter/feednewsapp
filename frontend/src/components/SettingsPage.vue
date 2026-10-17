<script setup>
import { onMounted, ref } from 'vue';
import { api } from '../api.js';
import { applyTheme } from '../theme.js';

const props = defineProps({ user: { type: Object, required: true } });
const emit = defineEmits(['navigate', 'logout']);
const state = ref({ subscriptions: [], availableFeeds: [], categories: [], preferences: {}, hideXFrameNotice: false });
const admin = ref(null);
const error = ref('');
const message = ref('');
const form = ref({ url: '', category: '', color: '#337dff', icon: '', autoPin: false });

async function load() {
    state.value = await api('/api/settings');
    applyTheme(state.value.preferences.theme || 'auto');
    if (props.user.isAdmin) admin.value = await api('/api/admin');
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
                icon: form.value.icon,
                autoPin: form.value.autoPin,
            },
        });
        form.value.url = '';
        message.value = 'Feed added.';
        await load();
    } catch (exception) {
        error.value = exception.message;
    }
}

async function follow(feed) {
    await api(`/api/settings/feeds/${feed.id}/follow`, {
        method: 'POST',
        body: { color: feed.color, autoPin: false },
    });
    await load();
}

async function update(subscription) {
    await api(`/api/settings/feeds/${subscription.id}`, {
        method: 'PATCH',
        body: { color: subscription.color, icon: subscription.icon, autoPin: subscription.autoPin },
    });
    message.value = `${subscription.feed.name} updated.`;
}

async function remove(subscription) {
    if (!window.confirm(`Stop following ${subscription.feed.name}?`)) return;
    await api(`/api/settings/feeds/${subscription.id}`, { method: 'DELETE' });
    await load();
}

async function savePreference(setting, value) {
    await api(`/api/settings/preferences/${encodeURIComponent(setting)}`, { method: 'PUT', body: { value } });
    state.value.preferences[setting] = value;
    if (setting === 'theme') applyTheme(value);
    message.value = 'Preference saved.';
}

async function disableFrameCheck() {
    await api('/api/settings/hide-x-frame-notice', { method: 'POST' });
    state.value.hideXFrameNotice = true;
    message.value = 'Frame compatibility checks disabled.';
}

async function removeUser(user) {
    if (!window.confirm(`Remove ${user.email}?`)) return;
    await api(`/api/admin/users/${user.id}`, { method: 'DELETE' });
    admin.value = await api('/api/admin');
}

onMounted(load);
</script>

<template>
    <section class="settings-page">
        <header class="page-header">
            <button class="back-button light" @click="emit('navigate', '/')">← FeedNews</button>
            <h1>Feed & settings</h1>
            <button class="secondary" @click="emit('logout')">Sign out</button>
        </header>
        <datalist id="feed-icon-options">
            <option value="◔">RSS</option><option value="▶">Video</option><option value="▰">News</option>
            <option value="◆">Games</option><option value="⌘">Development</option><option value="▦">Calendar</option>
            <option value="▤">Shopping</option><option value="●">General</option>
        </datalist>
        <div class="settings-content">
            <p v-if="error" class="alert error">{{ error }}</p>
            <p v-if="message" class="alert success">{{ message }}</p>

            <section class="settings-section card">
                <h2>Add a feed</h2>
                <p class="muted">Paste an RSS/Atom URL, a website containing feed discovery metadata, or a YouTube channel URL.</p>
                <form class="settings-add-grid" @submit.prevent="addFeed">
                    <label class="wide">Website or feed URL<input v-model.trim="form.url" type="url" required placeholder="https://example.com"></label>
                    <label>Category<select v-model="form.category"><option value="">None</option><option v-for="category in state.categories" :key="category.id" :value="category.id">{{ category.name }}</option></select></label>
                    <label>Color<input v-model="form.color" type="color"></label>
                    <label>Icon<input v-model.trim="form.icon" maxlength="64" placeholder="e.g. ◉" list="feed-icon-options"></label>
                    <label class="check-row"><input v-model="form.autoPin" type="checkbox"><span>Auto-pin new items</span></label>
                    <button class="primary">Add feed</button>
                </form>
            </section>

            <section class="settings-section card">
                <h2>Your feeds</h2>
                <div v-for="subscription in state.subscriptions" :key="subscription.id" class="subscription-row">
                    <div><strong>{{ subscription.feed.name }}</strong><small>{{ subscription.feed.url }}</small></div>
                    <input v-model="subscription.color" type="color" :aria-label="`${subscription.feed.name} color`">
                    <input v-model.trim="subscription.icon" class="icon-input" maxlength="64" placeholder="Icon" aria-label="Feed icon" list="feed-icon-options">
                    <label class="compact-check"><input v-model="subscription.autoPin" type="checkbox"> Auto-pin</label>
                    <button class="secondary small" @click="update(subscription)">Save</button>
                    <button class="danger small" @click="remove(subscription)">Remove</button>
                </div>
                <p v-if="state.subscriptions.length === 0" class="muted">No feeds followed yet.</p>
            </section>

            <section v-if="state.availableFeeds.length" class="settings-section card">
                <h2>Available feeds</h2>
                <div class="available-grid">
                    <button v-for="feed in state.availableFeeds" :key="feed.id" class="available-feed" @click="follow(feed)">
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
                <div class="frame-check-setting">
                    <div>
                        <strong>Embedded page compatibility checks</strong>
                        <p class="muted">Keep this enabled unless you installed the FeedNews browser extension that removes frame-blocking headers only for pages opened inside FeedNews.</p>
                    </div>
                    <button v-if="!state.hideXFrameNotice" class="secondary" @click="disableFrameCheck">Disable checks</button>
                    <span v-else class="muted">Checks disabled</span>
                </div>
            </section>

            <section v-if="props.user.isAdmin && admin" class="settings-section card admin-section">
                <h2>Administration</h2>
                <h3>Users</h3>
                <div class="table-wrap"><table><thead><tr><th>Name</th><th>Email</th><th>Last login</th><th></th></tr></thead><tbody>
                    <tr v-for="account in admin.users" :key="account.id"><td>{{ account.name }}</td><td>{{ account.email }}</td><td>{{ account.lastLogin ? new Date(account.lastLogin).toLocaleString() : 'Never' }}</td><td><button v-if="!account.isAdmin" class="danger small" @click="removeUser(account)">Remove</button></td></tr>
                </tbody></table></div>
                <h3>Latest feeds</h3>
                <div class="table-wrap"><table><thead><tr><th>Name</th><th>URL</th><th>Updated</th></tr></thead><tbody>
                    <tr v-for="feed in admin.feeds" :key="feed.id"><td>{{ feed.name }}</td><td class="break-all">{{ feed.url }}</td><td>{{ new Date(feed.updatedAt).toLocaleString() }}</td></tr>
                </tbody></table></div>
                <h3>Error log</h3>
                <div class="table-wrap"><table><thead><tr><th>Type</th><th>Occurrences</th><th>Feed</th><th>Message</th></tr></thead><tbody>
                    <tr v-for="entry in admin.errors" :key="entry.id"><td>{{ entry.type }}</td><td>{{ entry.occurrences }}</td><td>{{ entry.feed || '—' }}</td><td class="break-all">{{ entry.message }}</td></tr>
                </tbody></table></div>
            </section>
        </div>
    </section>
</template>
