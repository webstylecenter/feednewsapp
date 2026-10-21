<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { api } from '../api.js';
import { openFeed } from '../navigation.js';
import { faviconUrl, weatherIcon } from '../presentation.js';
import FeedPreviewDialog from './FeedPreviewDialog.vue';

const data = ref({ notes: [], todos: [], feeds: [], weather: null });
const settings = ref({ availableFeeds: [], categories: [] });
const statistics = ref(null);
const statisticsLoading = ref(true);
const statisticsError = ref('');
const topFeedPeriod = ref(7);
const feedForm = ref({ url: '', category: '', color: '#337dff', autoPin: false });
const todo = ref('');
const activeNote = ref(null);
const toast = ref('');
const failedFavicons = ref(new Set());
const trendChartScroll = ref(null);
const previewFeed = ref(null);
const previewData = ref(null);
const previewLoading = ref(false);
const noteTimers = new Map();
let toastTimer;

const currentNote = computed(() => activeNote.value ?? data.value.notes[0] ?? null);
const numberFormatter = new Intl.NumberFormat();
const categoryPalette = [
    '#337dff',
    '#7c5cff',
    '#11a683',
    '#f09a35',
    '#e35d6a',
    '#25a7c7',
    '#8b6f47',
    '#7f8c9a',
];

const categoryRows = computed(() => (statistics.value?.categories ?? []).map((category, index) => ({
    ...category,
    color: categoryPalette[index % categoryPalette.length],
})));
const categoryTotal = computed(() => categoryRows.value.reduce((total, category) => total + category.count, 0));
const categoryGradient = computed(() => {
    if (categoryTotal.value === 0) {
        return 'conic-gradient(var(--border) 0 100%)';
    }

    let offset = 0;
    const stops = categoryRows.value.map((category) => {
        const start = offset;
        offset += (category.count / categoryTotal.value) * 100;

        return `${category.color} ${start}% ${offset}%`;
    });

    return `conic-gradient(${stops.join(', ')})`;
});
const openedPercentage = computed(() => {
    const totals = statistics.value?.totals;
    if (!totals || totals.items === 0) {
        return 0;
    }

    return Math.round((totals.opened / totals.items) * 100);
});
const trendChartWidth = 840;
const trendChartHeight = 250;
const trendChartLeft = 58;
const trendChartRight = 18;
const trendChartTop = 30;
const trendChartBottom = 58;

function niceTrendStep(maximum) {
    if (maximum <= 4) {
        return 1;
    }

    const roughStep = maximum / 4;
    const magnitude = 10 ** Math.floor(Math.log10(roughStep));
    const normalized = roughStep / magnitude;
    let niceNormalized = 1;

    if (normalized > 5) {
        niceNormalized = 10;
    } else if (normalized > 2) {
        niceNormalized = 5;
    } else if (normalized > 1) {
        niceNormalized = 2;
    }

    return niceNormalized * magnitude;
}

const trendAxis = computed(() => {
    const rows = statistics.value?.daily ?? [];
    const maximum = Math.max(0, ...rows.map((row) => row.count));
    const step = niceTrendStep(maximum);
    const axisMaximum = Math.max(step, Math.ceil(maximum / step) * step);
    const tickCount = Math.round(axisMaximum / step);

    return {
        maximum: axisMaximum,
        ticks: Array.from({ length: tickCount + 1 }, (_, index) => index * step),
    };
});

function trendY(value) {
    const drawableHeight = trendChartHeight - trendChartTop - trendChartBottom;

    return trendChartTop + (drawableHeight - ((value / trendAxis.value.maximum) * drawableHeight));
}

const trendChartPoints = computed(() => {
    const rows = statistics.value?.daily ?? [];
    if (rows.length === 0) {
        return [];
    }

    const drawableWidth = trendChartWidth - trendChartLeft - trendChartRight;
    const divisor = Math.max(1, rows.length - 1);

    return rows.map((row, index) => ({
        ...row,
        x: trendChartLeft + ((index / divisor) * drawableWidth),
        y: trendY(row.count),
    }));
});
const trendPolyline = computed(() => trendChartPoints.value.map((point) => `${point.x},${point.y}`).join(' '));
const trendAxisTicks = computed(() => trendAxis.value.ticks.map((value) => ({
    value,
    y: trendY(value),
})));
const activeTopFeeds = computed(() => (
    topFeedPeriod.value === 7
        ? (statistics.value?.topFeeds7Days ?? [])
        : (statistics.value?.topFeeds ?? [])
));
const topFeedMaximum = computed(() => Math.max(1, ...activeTopFeeds.value.map((feed) => feed.count)));

function formatNumber(value) {
    return numberFormatter.format(value ?? 0);
}

function formatTrendDate(value) {
    return new Date(`${value}T12:00:00`)
        .toLocaleDateString('nl-NL', { weekday: 'short', day: 'numeric', month: 'short' })
        .replace(/\./g, '')
        .toUpperCase();
}

async function load() {
    const [dashboardData, settingsData] = await Promise.all([
        api('/api/dashboard'),
        api('/api/settings'),
    ]);

    data.value = dashboardData;
    settings.value = settingsData;
    activeNote.value = data.value.notes[0] ?? null;
}

async function scrollTrendToLatest() {
    await nextTick();
    if (trendChartScroll.value) {
        trendChartScroll.value.scrollLeft = trendChartScroll.value.scrollWidth;
    }
}

async function loadStatistics() {
    statisticsLoading.value = true;
    statisticsError.value = '';

    try {
        statistics.value = await api('/api/dashboard/statistics');
        await scrollTrendToLatest();
    } catch (error) {
        statisticsError.value = error instanceof Error ? error.message : 'Could not load statistics';
    } finally {
        statisticsLoading.value = false;
    }
}

async function refreshFeedManagement() {
    const [dashboardData, settingsData] = await Promise.all([
        api('/api/dashboard'),
        api('/api/settings'),
    ]);

    data.value.feeds = dashboardData.feeds;
    settings.value = settingsData;
    loadStatistics().catch(() => {});
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

async function openFeedPreview(feed) {
    previewFeed.value = feed;
    previewData.value = null;
    previewLoading.value = true;

    try {
        previewData.value = await api(`/api/settings/feeds/${feed.uuid}/preview`);
    } catch (error) {
        previewFeed.value = null;
        showToast(error instanceof Error ? error.message : 'Could not load feed preview');
    } finally {
        previewLoading.value = false;
    }
}

function closeFeedPreview() {
    previewFeed.value = null;
    previewData.value = null;
    previewLoading.value = false;
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
        closeFeedPreview();
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

onMounted(() => {
    load().catch(() => showToast('Could not load dashboard'));
    loadStatistics().catch(() => {});
});
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
            <article class="widget card span-two statistics-summary-widget">
                <header>
                    <h2>Your reading statistics</h2>
                    <span v-if="statisticsLoading" class="muted">Loading…</span>
                    <span v-else class="muted">Updated from your FeedNews data</span>
                </header>
                <p v-if="statisticsError" class="muted">{{ statisticsError }}</p>
                <div v-else-if="statistics" class="statistics-summary">
                    <div class="statistic-card"><strong>{{ formatNumber(statistics.totals.items) }}</strong><span>Total items</span></div>
                    <div class="statistic-card"><strong>{{ formatNumber(statistics.totals.unread) }}</strong><span>Unread</span></div>
                    <div class="statistic-card"><strong>{{ openedPercentage }}%</strong><span>Opened</span></div>
                    <div class="statistic-card"><strong>{{ formatNumber(statistics.totals.pinned) }}</strong><span>Pinned</span></div>
                </div>
            </article>

            <article class="widget card statistics-chart-widget">
                <header><h2>Items per category</h2><span v-if="statistics" class="muted">{{ formatNumber(categoryTotal) }} items</span></header>
                <div v-if="statistics && categoryRows.length" class="category-statistics">
                    <div class="category-donut" :style="{ background: categoryGradient }"><span><strong>{{ formatNumber(categoryTotal) }}</strong><small>items</small></span></div>
                    <div class="category-legend">
                        <div v-for="category in categoryRows" :key="category.name" class="category-legend-row">
                            <span class="category-color" :style="{ background: category.color }"></span><span>{{ category.name }}</span><strong>{{ formatNumber(category.count) }}</strong>
                        </div>
                    </div>
                </div>
                <p v-else-if="!statisticsLoading" class="muted">No category statistics available yet.</p>
            </article>

            <article class="widget card statistics-chart-widget">
                <header><h2>New items</h2><span class="muted">Last 14 days</span></header>
                <div v-if="statistics && trendChartPoints.length" class="trend-chart">
                    <div ref="trendChartScroll" class="trend-chart-scroll">
                        <svg :viewBox="`0 0 ${trendChartWidth} ${trendChartHeight}`" role="img" aria-label="New feed items during the last 14 days">
                            <g v-for="tick in trendAxisTicks" :key="tick.value">
                                <line :x1="trendChartLeft" :y1="tick.y" :x2="trendChartWidth - trendChartRight" :y2="tick.y" class="trend-grid-line"></line>
                                <text :x="trendChartLeft - 10" :y="tick.y + 4" class="trend-axis-label trend-axis-label-y">{{ formatNumber(tick.value) }}</text>
                            </g>
                            <line :x1="trendChartLeft" :y1="trendChartTop" :x2="trendChartLeft" :y2="trendChartHeight - trendChartBottom" class="trend-axis-line"></line>
                            <line :x1="trendChartLeft" :y1="trendChartHeight - trendChartBottom" :x2="trendChartWidth - trendChartRight" :y2="trendChartHeight - trendChartBottom" class="trend-axis-line"></line>
                            <polyline :points="trendPolyline" class="trend-line"></polyline>
                            <g v-for="point in trendChartPoints" :key="point.date">
                                <circle :cx="point.x" :cy="point.y" r="4" class="trend-point"><title>{{ formatTrendDate(point.date) }}: {{ point.count }} items</title></circle>
                                <text :x="point.x" :y="Math.max(15, point.y - 10)" class="trend-value-label">{{ formatNumber(point.count) }}</text>
                                <text :x="point.x" :y="trendChartHeight - 28" class="trend-axis-label trend-axis-label-x">{{ formatTrendDate(point.date) }}</text>
                            </g>
                        </svg>
                    </div>
                </div>
                <p v-else-if="!statisticsLoading" class="muted">No item history available yet.</p>
            </article>

            <article class="widget card span-two statistics-feeds-widget">
                <header>
                    <h2>Most active feeds</h2>
                    <div class="statistics-period-switch" aria-label="Most active feeds period">
                        <button type="button" :class="{ active: topFeedPeriod === 7 }" :aria-pressed="topFeedPeriod === 7" @click="topFeedPeriod = 7">7 days</button>
                        <button type="button" :class="{ active: topFeedPeriod === 30 }" :aria-pressed="topFeedPeriod === 30" @click="topFeedPeriod = 30">30 days</button>
                    </div>
                </header>
                <div v-if="activeTopFeeds.length" class="top-feed-statistics">
                    <div v-for="feed in activeTopFeeds" :key="feed.name" class="top-feed-row">
                        <div class="top-feed-label"><strong>{{ feed.name }}</strong><span>{{ formatNumber(feed.count) }}</span></div>
                        <div class="top-feed-track"><span :style="{ width: `${Math.max(2, (feed.count / topFeedMaximum) * 100)}%` }"></span></div>
                    </div>
                </div>
                <p v-else-if="!statisticsLoading" class="muted">No feed activity available yet.</p>
            </article>

            <article class="widget card dashboard-widget">
                <header><h2>Notes</h2><button class="icon-button" title="Add note" @click="addLocalNote">＋</button></header>
                <div v-if="data.notes.length" class="notes-workspace">
                    <select v-model="activeNote" class="notes-select" aria-label="Select note">
                        <option v-for="note in data.notes" :key="note.uuid ?? `new-${note.position}`" :value="note">{{ note.name || 'Untitled note' }}</option>
                    </select>
                    <nav class="notes-list" aria-label="Notes">
                        <button v-for="note in data.notes" :key="note.uuid ?? `new-${note.position}`" :class="{ active: currentNote === note }" @click="activeNote = note">{{ note.name || 'Untitled note' }}</button>
                    </nav>
                    <div v-if="currentNote" class="note-editor">
                        <input v-model="currentNote.name" maxlength="255" aria-label="Note name" @input="queueNoteSave(currentNote)">
                        <textarea v-model="currentNote.content" aria-label="Note content" @input="queueNoteSave(currentNote)"></textarea>
                        <div class="note-editor-actions"><button class="danger small" @click="removeNote(currentNote)">Delete note</button></div>
                    </div>
                </div>
                <p v-else class="muted">No notes yet.</p>
            </article>

            <article class="widget card dashboard-widget">
                <header><h2>Todo</h2></header>
                <form class="inline-form" @submit.prevent="addTodo"><input v-model="todo" placeholder="Add todo" minlength="3"><button class="primary">Add</button></form>
                <div class="todo-list">
                    <label v-for="item in data.todos" :key="item.uuid" class="todo-row"><input :checked="item.checked" type="checkbox" @change="toggleTodo(item)"><span :class="{ done: item.checked }">{{ item.item }}</span></label>
                </div>
            </article>

            <article class="widget card feed-management-widget">
                <header><h2>Add a feed</h2></header>
                <p class="muted">Add an RSS/Atom feed, website or YouTube channel.</p>
                <form class="dashboard-feed-form" @submit.prevent="addFeed">
                    <label class="dashboard-feed-url">Website or feed URL<input v-model.trim="feedForm.url" type="url" required placeholder="https://example.com"></label>
                    <label>Category<select v-model="feedForm.category"><option value="">None</option><option v-for="category in settings.categories" :key="category.uuid" :value="category.uuid">{{ category.name }}</option></select></label>
                    <label>Color<input v-model="feedForm.color" type="color"></label>
                    <label class="check-row dashboard-auto-pin"><input v-model="feedForm.autoPin" type="checkbox"><span>Auto-pin</span></label>
                    <button class="primary">Add feed</button>
                </form>
            </article>

            <article class="widget card feed-management-widget">
                <header><h2>Available feeds</h2><span class="muted">{{ settings.availableFeeds.length }} available</span></header>
                <div v-if="settings.availableFeeds.length" class="dashboard-available-feeds">
                    <button v-for="feed in settings.availableFeeds" :key="feed.uuid" class="available-feed" @click="openFeedPreview(feed)">
                        <span class="feed-dot" :style="{ '--feed-color': feed.color }"></span>
                        <span><strong>{{ feed.name }}</strong><small>{{ feed.category || 'Uncategorised' }}</small></span>
                        <span>＋</span>
                    </button>
                </div>
                <p v-else class="muted">No additional feeds available.</p>
            </article>

            <article class="widget card span-two feeds-widget">
                <header><h2>Your feeds</h2><span class="muted">{{ data.feeds.length }} sources</span></header>
                <div class="feed-overview">
                    <button v-for="feed in data.feeds" :key="feed.source" class="feed-pill" :style="{ '--feed-color': feed.color }" @click="openFeed(feed.source)">
                        <span class="feed-source-icon">
                            <img v-if="showFavicon(feed)" :src="faviconUrl(feed.url)" alt="" loading="lazy" @error="markFaviconFailed(feed.source)">
                            <span v-else class="feed-dot"></span>
                        </span>
                        <strong>{{ feed.name }}</strong><small>{{ feed.itemCount }} items</small>
                    </button>
                    <p v-if="data.feeds.length === 0" class="muted">No feeds configured.</p>
                </div>
            </article>
        </div>

        <FeedPreviewDialog :feed="previewFeed" :preview="previewData" :loading="previewLoading" @close="closeFeedPreview" @subscribe="followFeed" />
        <div v-if="toast" class="toast-notification" role="status">{{ toast }}</div>
    </section>
</template>

<style scoped>
.statistics-period-switch {
    display: inline-flex;
    gap: .2rem;
    padding: .2rem;
    border: 1px solid var(--border);
    border-radius: 999px;
    background: var(--surface-soft);
}

.statistics-period-switch button {
    border: 0;
    border-radius: 999px;
    padding: .35rem .65rem;
    background: transparent;
    color: var(--muted);
    font-size: .8rem;
}

.statistics-period-switch button.active {
    background: var(--surface);
    color: var(--blue);
    box-shadow: 0 2px 8px rgb(20 40 80 / 10%);
}
</style>
