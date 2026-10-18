<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { api } from '../api.js';
import { faviconUrl, weatherIcon } from '../presentation.js';

const emit = defineEmits(['select-feed']);

const data = ref({ notes: [], todos: [], feeds: [], weather: null });
const settings = ref({ availableFeeds: [], categories: [] });
const feedForm = ref({ url: '', category: '', color: '#337dff', autoPin: false });
const todo = ref('');
const activeNote = ref(null);
const toast = ref('');
const failedFavicons = ref(new Set());
const noteTimers = new Map();
let toastTimer;

const currentNote = computed(() => activeNote.value ?? data.value.notes[0] ?? null);

async function load() {
    const [dashboardData, settingsData] = await Promise.all([
        api('/api/dashboard'),
        api('/api/settings'),
    ]);

    data.value = dashboardData;
    settings.value = settingsData;
    activeNote.value = data.value.notes[0] ?? null;
}

async function refreshFeedManagement() {
    const [dashboardData, settingsData] = await Promise.all([
        api('/api/dashboard'),
        api('/api/settings'),
    ]);

    data.value.feeds = dashboardData.feeds;
    settings.value = settingsData;
}

function showToast(message) {
    toast.value = message;
    window.clearTimeout(toastTimer);
    toastTimer = window.setTimeout(() => {
        toast.value = '';
    }, 1800);
}

async function saveNote(note) {
    const path = note.uuid ? `/api/notes/${note.uuid}` : '/api/notes';
    const method = note.uuid ? 'PATCH' : 'POST';
    const result = await api(path, { method, body: note });
    Object.assign(note, result.item);
    showToast('Note saved');
}

function queueNoteSave(note) {
    window.clearTimeout(noteTimers.get(note));
    noteTimers.set(note, window.setTimeout(() => {
        noteTimers.delete(note);
        saveNote(note).catch(() => showToast('Could not save note'));
    }, 600));
}

async function removeNote(note) {
    window.clearTimeout(noteTimers.get(note));
    noteTimers.delete(note);

    if (!note.uuid) {
        data.value.notes = data.value.notes.filter((item) => item !== note);
        activeNote.value = data.value.notes[0] ?? null;
        return;
    }

    await api(`/api/notes/${note.uuid}`, { method: 'DELETE' });
    data.value.notes = data.value.notes.filter((item) => item.uuid !== note.uuid);
    activeNote.value = data.value.notes[0] ?? null;
    showToast('Note deleted');
}

function addLocalNote() {
    const note = {
        uuid: null,
        name: 'New note',
        position: data.value.notes.length,
        content: '',
    };
    data.value.notes.push(note);
    activeNote.value = note;
}


async function addFeed() {
    try {
        await api('/api/settings/feeds', {
            method: 'POST',
            body: {
                websiteUrl: feedForm.value.url,
                category: feedForm.value.category || null,
                color: feedForm.value.color,
                autoPin: feedForm.value.autoPin,
            },
        });
        feedForm.value.url = '';
        showToast('Feed added');
        await refreshFeedManagement();
    } catch (error) {
        showToast(error instanceof Error ? error.message : 'Could not add feed');
    }
}

async function followFeed(feed) {
    try {
        await api(`/api/settings/feeds/${feed.uuid}/follow`, {
            method: 'POST',
            body: {
                color: feed.color,
                autoPin: false,
            },
        });
        showToast(`${feed.name} added`);
        await refreshFeedManagement();
    } catch (error) {
        showToast(error instanceof Error ? error.message : 'Could not follow feed');
    }
}

async function addTodo() {
    if (todo.value.trim().length < 3) return;
    const result = await api('/api/checklist', { method: 'POST', body: { item: todo.value } });
    data.value.todos.unshift(result.item);
    todo.value = '';
}

async function toggleTodo(item) {
    const result = await api(`/api/checklist/${item.uuid}`, { method: 'PATCH' });
    Object.assign(item, result.item);
}

function markFaviconFailed(key) {
    const failed = new Set(failedFavicons.value);
    failed.add(key);
    failedFavicons.value = failed;
}

function showFavicon(feed) {
    return feed.url && faviconUrl(feed.url) !== '' && !failedFavicons.value.has(feed.source);
}

onMounted(load);
onBeforeUnmount(() => {
    for (const timer of noteTimers.values()) window.clearTimeout(timer);
    window.clearTimeout(toastTimer);
});
</script>

<template>
    <section class="dashboard">
        <div class="dashboard-hero">
            <div>
                <p class="eyebrow">FeedNews</p>
                <h1>Dashboard</h1>
            </div>
            <div v-if="data.weather" class="weather-now">
                <img :src="weatherIcon(data.weather.currentWeather, true)" alt="">
                <strong>{{ Math.round(data.weather.currentTemp) }}°</strong>
                <span>{{ data.weather.location }}</span>
            </div>
        </div>

        <div class="widget-grid">
            <article class="widget card dashboard-widget">
                <header>
                    <h2>Notes</h2>
                    <button class="icon-button" title="Add note" @click="addLocalNote">＋</button>
                </header>
                <div v-if="data.notes.length" class="notes-workspace">
                    <select v-model="activeNote" class="notes-select" aria-label="Select note">
                        <option
                            v-for="note in data.notes"
                            :key="note.uuid ?? `new-${note.position}`"
                            :value="note"
                        >
                            {{ note.name || 'Untitled note' }}
                        </option>
                    </select>
                    <nav class="notes-list" aria-label="Notes">
                        <button
                            v-for="note in data.notes"
                            :key="note.uuid ?? `new-${note.position}`"
                            :class="{ active: currentNote === note }"
                            @click="activeNote = note"
                        >
                            {{ note.name || 'Untitled note' }}
                        </button>
                    </nav>
                    <div v-if="currentNote" class="note-editor">
                        <input
                            v-model="currentNote.name"
                            maxlength="255"
                            aria-label="Note name"
                            @input="queueNoteSave(currentNote)"
                        >
                        <textarea
                            v-model="currentNote.content"
                            aria-label="Note content"
                            @input="queueNoteSave(currentNote)"
                        ></textarea>
                        <div class="note-editor-actions">
                            <button class="danger small" @click="removeNote(currentNote)">Delete note</button>
                        </div>
                    </div>
                </div>
                <p v-else class="muted">No notes yet.</p>
            </article>

            <article class="widget card dashboard-widget">
                <header><h2>Todo</h2></header>
                <form class="inline-form" @submit.prevent="addTodo">
                    <input v-model="todo" placeholder="Add todo" minlength="3">
                    <button class="primary">Add</button>
                </form>
                <div class="todo-list">
                    <label v-for="item in data.todos" :key="item.uuid" class="todo-row">
                        <input :checked="item.checked" type="checkbox" @change="toggleTodo(item)">
                        <span :class="{ done: item.checked }">{{ item.item }}</span>
                    </label>
                </div>
            </article>

            <article class="widget card feed-management-widget">
                <header><h2>Add a feed</h2></header>
                <p class="muted">Add an RSS/Atom feed, website or YouTube channel.</p>
                <form class="dashboard-feed-form" @submit.prevent="addFeed">
                    <label class="dashboard-feed-url">Website or feed URL
                        <input v-model.trim="feedForm.url" type="url" required placeholder="https://example.com">
                    </label>
                    <label>Category
                        <select v-model="feedForm.category">
                            <option value="">None</option>
                            <option v-for="category in settings.categories" :key="category.uuid" :value="category.uuid">{{ category.name }}</option>
                        </select>
                    </label>
                    <label>Color
                        <input v-model="feedForm.color" type="color">
                    </label>
                    <label class="check-row dashboard-auto-pin">
                        <input v-model="feedForm.autoPin" type="checkbox">
                        <span>Auto-pin</span>
                    </label>
                    <button class="primary">Add feed</button>
                </form>
            </article>

            <article class="widget card feed-management-widget">
                <header>
                    <h2>Available feeds</h2>
                    <span class="muted">{{ settings.availableFeeds.length }} available</span>
                </header>
                <div v-if="settings.availableFeeds.length" class="dashboard-available-feeds">
                    <button
                        v-for="feed in settings.availableFeeds"
                        :key="feed.uuid"
                        class="available-feed"
                        @click="followFeed(feed)"
                    >
                        <span class="feed-dot" :style="{ '--feed-color': feed.color }"></span>
                        <span>
                            <strong>{{ feed.name }}</strong>
                            <small>{{ feed.category || 'Uncategorised' }}</small>
                        </span>
                        <span>＋</span>
                    </button>
                </div>
                <p v-else class="muted">No additional feeds available.</p>
            </article>

            <article class="widget card span-two feeds-widget">
                <header>
                    <h2>Your feeds</h2>
                    <span class="muted">{{ data.feeds.length }} sources</span>
                </header>
                <div class="feed-overview">
                    <button
                        v-for="feed in data.feeds"
                        :key="feed.source"
                        class="feed-pill"
                        :style="{ '--feed-color': feed.color }"
                        @click="emit('select-feed', feed.source)"
                    >
                        <span class="feed-source-icon">
                            <img
                                v-if="showFavicon(feed)"
                                :src="faviconUrl(feed.url)"
                                alt=""
                                loading="lazy"
                                @error="markFaviconFailed(feed.source)"
                            >
                            <span v-else class="feed-dot"></span>
                        </span>
                        <strong>{{ feed.name }}</strong>
                        <small>{{ feed.itemCount }} items</small>
                    </button>
                    <p v-if="data.feeds.length === 0" class="muted">No feeds configured.</p>
                </div>
            </article>
        </div>

        <div v-if="toast" class="toast-notification" role="status">{{ toast }}</div>
    </section>
</template>
