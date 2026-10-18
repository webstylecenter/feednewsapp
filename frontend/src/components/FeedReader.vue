<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { api } from '../api.js';
import { applyTheme } from '../theme.js';
import Dashboard from './Dashboard.vue';
import ArticleReader from './ArticleReader.vue';
import { faviconUrl, weatherIcon } from '../presentation.js';

const props = defineProps({
    user: { type: Object, required: true },
    path: { type: String, default: '/' },
});
const emit = defineEmits(['navigate', 'logout']);

const items = ref([]);
const pinnedItems = ref([]);
const pinnedCount = ref(0);
const showAllPinned = ref(false);
const pinnedLoading = ref(false);
const tags = ref([]);
const failedFavicons = ref(new Set());
const selectedFeedSource = ref('');
const selectedFeedLabel = ref('');
const activeTab = ref('recent');
const selectedTag = ref('');
const search = ref('');
const page = ref(0);
const nextPage = ref(null);
const loading = ref(false);
const currentUrl = ref('');
const currentTitle = ref('');
const currentItem = ref(null);
const weather = ref(null);
const preferences = ref({});
const weatherOpen = ref(false);
const calendarOpen = ref(false);
const readerSource = ref(null);
const readerLoading = ref(false);
const readerError = ref('');
const pictureInPictureUrl = ref('');
const pictureInPictureSource = ref(null);
const pictureInPictureTitle = ref('');
const focusMode = ref(false);
const videoMode = ref(false);
const menuOpen = ref(false);
const addDialog = ref(null);
const tagDialog = ref(null);
const addForm = ref({ url: '', title: '', description: '' });
const selectedItem = ref(null);
const newTag = ref({ name: '', color: randomTagColor() });
const sentinel = ref(null);
const feedList = ref(null);
const weatherRevision = ref(Date.now());
const pullDistance = ref(0);
const pullRefreshing = ref(false);
let observer;
let refreshTimer;
let pointerStartX = 0;
let pointerStartY = 0;
let pointerSwiped = false;
let pointerGestureActive = false;
let longPressTriggered = false;
let longPressTimer;
let pullStartY = 0;
let pullStartX = 0;
let pullTracking = false;
let readerHistoryActive = false;

const hasContent = computed(() => currentUrl.value !== '' || readerSource.value !== null || readerLoading.value || readerError.value !== '');
const visibleItems = computed(() => [...pinnedItems.value, ...items.value]);

function randomTagColor() {
    const bytes = new Uint8Array(3);
    window.crypto.getRandomValues(bytes);

    return `#${Array.from(bytes, (value) => value.toString(16).padStart(2, '0')).join('')}`;
}


function resetPinnedState() {
    pinnedItems.value = [];
    pinnedCount.value = 0;
    showAllPinned.value = false;
}

function markFaviconFailed(item) {
    const url = faviconUrl(item.url);
    if (!url) return;

    const failed = new Set(failedFavicons.value);
    failed.add(url);
    failedFavicons.value = failed;
}

function showFavicon(item) {
    const url = faviconUrl(item.url);

    return url !== '' && !failedFavicons.value.has(url);
}

function feedSourceFromPath(path) {
    const match = /^\/feed\/([^/]+)$/.exec(path);
    if (!match) return '';

    const source = decodeURIComponent(match[1]);
    if (source === 'manual' || /^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i.test(source)) return source;

    return '';
}

async function applyFeedSource(path) {
    const source = feedSourceFromPath(path);
    if (source === selectedFeedSource.value) return;

    selectedFeedSource.value = source;
    selectedFeedLabel.value = source === 'manual' ? 'Saved links' : '';
    activeTab.value = 'recent';
    selectedTag.value = '';
    await loadRecent(true);
}

function clearFeedSource() {
    emit('navigate', '/');
}

function openDashboardFeed(source) {
    emit('navigate', `/feed/${source}`);
}

function youtubeVideoId(url) {
    try {
        const parsed = new URL(url);
        let videoId = '';
        if (parsed.hostname === 'youtu.be') {
            videoId = parsed.pathname.slice(1).split('/')[0] || '';
        } else if (parsed.hostname === 'youtube.com' || parsed.hostname.endsWith('.youtube.com')) {
            if (parsed.pathname === '/watch') videoId = parsed.searchParams.get('v') || '';
            if (parsed.pathname.startsWith('/shorts/')) videoId = parsed.pathname.split('/')[2] || '';
            if (parsed.pathname.startsWith('/embed/')) videoId = parsed.pathname.split('/')[2] || '';
        }

        return /^[A-Za-z0-9_-]{6,20}$/.test(videoId) ? videoId : null;
    } catch {
        return null;
    }
}

function youtubeEmbedUrl(url) {
    const videoId = youtubeVideoId(url);

    return videoId ? `https://www.youtube-nocookie.com/embed/${videoId}?autoplay=1` : null;
}

function isMobileDevice() {
    return window.matchMedia('(max-width: 800px)').matches;
}

function openMobileYoutube(url) {
    const videoId = youtubeVideoId(url);
    if (!videoId) return false;

    if (/Android/i.test(window.navigator.userAgent)) {
        const fallback = encodeURIComponent(`https://www.youtube.com/watch?v=${videoId}`);
        window.location.href = `intent://www.youtube.com/watch?v=${videoId}#Intent;scheme=https;package=com.google.android.youtube;S.browser_fallback_url=${fallback};end`;
        return true;
    }

    window.open(url, '_blank', 'noopener,noreferrer');
    return true;
}

function pushReaderHistory() {
    if (readerHistoryActive) return;

    window.history.pushState(
        { ...window.history.state, feednewsReaderOpen: true },
        '',
        window.location.href,
    );
    readerHistoryActive = true;
}

function resetCurrent() {
    currentUrl.value = '';
    currentItem.value = null;
    currentTitle.value = '';
    readerSource.value = null;
    readerLoading.value = false;
    readerError.value = '';
    videoMode.value = false;
    focusMode.value = false;
}

function onReaderPopState() {
    if (!readerHistoryActive || !hasContent.value) return;

    readerHistoryActive = false;
    resetCurrent();
}

function closeCurrent() {
    if (readerHistoryActive && hasContent.value) {
        window.history.back();
        return;
    }

    resetCurrent();
}

async function loadRecent(reset = false) {
    if (loading.value) return;
    loading.value = true;
    try {
        if (reset) {
            page.value = 0;
            items.value = [];
            resetPinnedState();
        }
        const feedFilter = selectedFeedSource.value
            ? `&feed=${encodeURIComponent(selectedFeedSource.value)}`
            : '';
        const data = await api(`/api/feed/items?page=${page.value}&limit=50${feedFilter}`);
        items.value.push(...data.items);
        if (page.value === 0) {
            pinnedItems.value = data.pinnedItems || [];
            pinnedCount.value = data.pinnedCount || 0;
            if (selectedFeedSource.value) {
                const firstItem = pinnedItems.value[0] || items.value[0];
                selectedFeedLabel.value = selectedFeedSource.value === 'manual'
                    ? 'Saved links'
                    : (firstItem?.feedName || `Feed ${selectedFeedSource.value}`);
            }
        }
        nextPage.value = data.nextPage;
        if (data.nextPage !== null) page.value = data.nextPage;
    } finally {
        loading.value = false;
    }
}

async function switchTab(tab) {
    if (selectedFeedSource.value) {
        selectedFeedSource.value = '';
        selectedFeedLabel.value = '';
        emit('navigate', '/');
    }
    activeTab.value = tab;
    selectedTag.value = '';
    if (tab === 'recent') return loadRecent(true);

    resetPinnedState();
    if (tab === 'history') {
        items.value = (await api('/api/feed/history')).items;
        nextPage.value = null;
    }
}

async function doSearch() {
    if (search.value.trim().length < 2) return;
    if (selectedFeedSource.value) {
        selectedFeedSource.value = '';
        selectedFeedLabel.value = '';
        emit('navigate', '/');
    }
    activeTab.value = 'search';
    selectedTag.value = '';
    resetPinnedState();
    items.value = (await api(`/api/feed/search?q=${encodeURIComponent(search.value.trim())}`)).items;
    nextPage.value = null;
}

async function filterTag() {
    if (selectedFeedSource.value) {
        selectedFeedSource.value = '';
        selectedFeedLabel.value = '';
        emit('navigate', '/');
    }
    if (!selectedTag.value) return loadRecent(true);
    activeTab.value = 'tag';
    resetPinnedState();
    items.value = (await api(`/api/feed/tag/${selectedTag.value}`)).items;
    nextPage.value = null;
}

async function loadTags() {
    tags.value = (await api('/api/tags')).items;
}

async function openItem(item) {
    if (pointerSwiped) {
        pointerSwiped = false;
        return;
    }

    currentTitle.value = item.title;
    currentItem.value = item;
    currentUrl.value = '';
    readerSource.value = null;
    readerError.value = '';
    await api(`/api/feed/items/${item.uuid}/opened`, { method: 'POST' });

    const youtubeUrl = youtubeEmbedUrl(item.url);
    if (youtubeUrl && isMobileDevice()) {
        openMobileYoutube(item.url);
        return;
    }

    pushReaderHistory();
    if (youtubeUrl) {
        videoMode.value = true;
        currentUrl.value = youtubeUrl;
        return;
    }

    videoMode.value = false;
    readerLoading.value = true;
    try {
        readerSource.value = await api(`/api/feed/reader?url=${encodeURIComponent(item.url)}`);
    } catch (error) {
        readerError.value = error instanceof Error ? error.message : 'Het artikel kon niet worden geladen.';
    } finally {
        readerLoading.value = false;
    }
}

async function ensurePinnedPreview() {
    if (showAllPinned.value || pinnedItems.value.length >= Math.min(3, pinnedCount.value)) {
        return;
    }

    const feedFilter = selectedFeedSource.value
        ? `?feed=${encodeURIComponent(selectedFeedSource.value)}`
        : '';
    const pinned = (await api(`/api/feed/pinned${feedFilter}`)).items;
    pinnedItems.value = pinned.slice(0, 3);
}

function sortRecentItems() {
    items.value.sort((left, right) => {
        const dateDifference = new Date(right.createdAt).getTime() - new Date(left.createdAt).getTime();

        return dateDifference !== 0 ? dateDifference : right.uuid.localeCompare(left.uuid);
    });
}

async function togglePin(item) {
    const wasPinned = pinnedItems.value.some((pinnedItem) => pinnedItem.uuid === item.uuid);
    const result = await api(`/api/feed/items/${item.uuid}/pin`, { method: 'PATCH' });
    Object.assign(item, result.item);

    if (activeTab.value !== 'recent') {
        return;
    }

    if (result.currentState) {
        items.value = items.value.filter((recentItem) => recentItem.uuid !== item.uuid);
        pinnedItems.value = [
            item,
            ...pinnedItems.value.filter((pinnedItem) => pinnedItem.uuid !== item.uuid),
        ];

        if (!wasPinned) {
            pinnedCount.value += 1;
        }

        if (!showAllPinned.value) {
            pinnedItems.value = pinnedItems.value.slice(0, 3);
        }

        return;
    }

    pinnedItems.value = pinnedItems.value.filter((pinnedItem) => pinnedItem.uuid !== item.uuid);
    if (wasPinned) {
        pinnedCount.value = Math.max(0, pinnedCount.value - 1);
    }

    if (!items.value.some((recentItem) => recentItem.uuid === item.uuid)) {
        items.value.push(item);
        sortRecentItems();
    }

    await ensurePinnedPreview();
}

async function togglePinnedList() {
    if (showAllPinned.value) {
        showAllPinned.value = false;
        pinnedItems.value = pinnedItems.value.slice(0, 3);
        return;
    }

    if (pinnedItems.value.length < pinnedCount.value) {
        pinnedLoading.value = true;
        try {
            const feedFilter = selectedFeedSource.value
                ? `?feed=${encodeURIComponent(selectedFeedSource.value)}`
                : '';
            pinnedItems.value = (await api(`/api/feed/pinned${feedFilter}`)).items;
        } finally {
            pinnedLoading.value = false;
        }
    }

    showAllPinned.value = true;
}

function openExternal(item) {
    window.open(item.url, '_blank', 'noopener,noreferrer');
}

async function openPictureInPicture(item) {
    pictureInPictureTitle.value = item.title;
    pictureInPictureSource.value = null;
    const youtubeUrl = youtubeEmbedUrl(item.url);
    if (youtubeUrl) {
        pictureInPictureUrl.value = youtubeUrl;
        return;
    }

    pictureInPictureUrl.value = '';
    try {
        pictureInPictureSource.value = await api(`/api/feed/reader?url=${encodeURIComponent(item.url)}`);
    } catch {
        window.open(item.url, '_blank', 'noopener,noreferrer');
        closePictureInPicture();
    }
}

function closePictureInPicture() {
    pictureInPictureUrl.value = '';
    pictureInPictureSource.value = null;
    pictureInPictureTitle.value = '';
}

function toggleFocusMode() {
    if (!hasContent.value) return;
    focusMode.value = !focusMode.value;
}

function clearLongPress() {
    if (longPressTimer) {
        window.clearTimeout(longPressTimer);
        longPressTimer = undefined;
    }
}

function pointerDown(event, item) {
    if (event.target.closest('.feed-pin, .feed-actions')) {
        pointerGestureActive = false;
        return;
    }

    pointerGestureActive = true;
    pointerStartX = event.clientX;
    pointerStartY = event.clientY;
    pointerSwiped = false;
    longPressTriggered = false;
    clearLongPress();

    if (event.pointerType === 'touch' || event.pointerType === 'pen') {
        longPressTimer = window.setTimeout(() => {
            if (!pointerGestureActive) return;

            longPressTriggered = true;
            pointerSwiped = true;
            window.navigator.vibrate?.(20);
            openExternal(item);
        }, 600);
    }
}

function pointerMove(event) {
    if (!pointerGestureActive) return;

    const deltaX = event.clientX - pointerStartX;
    const deltaY = event.clientY - pointerStartY;
    if (Math.abs(deltaX) > 12 || Math.abs(deltaY) > 12) {
        clearLongPress();
    }
}

function pointerUp(event, item) {
    if (!pointerGestureActive) return;

    pointerGestureActive = false;
    clearLongPress();
    if (longPressTriggered) return;

    const deltaX = event.clientX - pointerStartX;
    const deltaY = event.clientY - pointerStartY;
    const horizontalSwipe = Math.abs(deltaX) > 70 && Math.abs(deltaX) > Math.abs(deltaY) * 1.25;
    pointerSwiped = horizontalSwipe;

    if (deltaX < -70 && horizontalSwipe) {
        togglePin(item);
        return;
    }

    if (deltaX > 70 && horizontalSwipe) {
        openTagDialog(item);
    }
}

function pointerCancel() {
    pointerGestureActive = false;
    clearLongPress();
}

function pullStart(event) {
    if (
        activeTab.value !== 'recent'
        || loading.value
        || pullRefreshing.value
        || (feedList.value?.scrollTop ?? 1) > 0
        || event.touches.length !== 1
    ) {
        pullTracking = false;
        return;
    }

    pullStartY = event.touches[0].clientY;
    pullStartX = event.touches[0].clientX;
    pullTracking = true;
    pullDistance.value = 0;
}

function pullMove(event) {
    if (!pullTracking || event.touches.length !== 1) return;

    const deltaY = event.touches[0].clientY - pullStartY;
    const deltaX = event.touches[0].clientX - pullStartX;
    if (deltaY <= 0 || Math.abs(deltaX) > Math.abs(deltaY)) {
        pullDistance.value = 0;
        return;
    }

    pullDistance.value = Math.min(90, deltaY * 0.5);
}

async function refreshItems() {
    if (pullRefreshing.value) return;

    pullRefreshing.value = true;
    try {
        await Promise.all([loadRecent(true), loadTags()]);
    } finally {
        pullRefreshing.value = false;
    }
}

async function pullEnd() {
    if (!pullTracking) return;

    const shouldRefresh = pullDistance.value >= 60;
    pullTracking = false;
    pullDistance.value = 0;
    if (shouldRefresh) {
        await refreshItems();
    }
}

function tagForItem(item) {
    if (!item.tagUuid) return null;
    return tags.value.find((tag) => tag.uuid === item.tagUuid) || null;
}

function openTagDialog(item) {
    selectedItem.value = item;
    newTag.value.color = randomTagColor();
    tagDialog.value?.showModal();
}

async function assignTag(tagUuid) {
    await api('/api/tags/assign', { method: 'POST', body: { itemUuid: selectedItem.value.uuid, tagUuid } });
    selectedItem.value.tagUuid = tagUuid || null;
    await loadTags();
    tagDialog.value?.close();
}

async function createTag() {
    await api('/api/tags', { method: 'POST', body: newTag.value });
    newTag.value.name = '';
    newTag.value.color = randomTagColor();
    await loadTags();
}

async function addItem() {
    await api('/api/feed/items', { method: 'POST', body: addForm.value });
    await loadRecent(true);
    addForm.value = { url: '', title: '', description: '' };
    addDialog.value?.close();
}

async function metadata() {
    if (!addForm.value.url) return;
    const result = await api('/api/feed/metadata', { method: 'POST', body: { url: addForm.value.url } });
    addForm.value.title = result.data.title;
    addForm.value.description = result.data.description;
}


function openCurrentExternal() {
    if (currentItem.value) window.open(currentItem.value.url, '_blank', 'noopener,noreferrer');
}

function openCurrentPopup() {
    if (!currentItem.value) return;
    openPictureInPicture(currentItem.value);
}

async function loadHeaderData() {
    const [forecastResult, settingsResult] = await Promise.allSettled([
        api('/api/weather'),
        api('/api/settings'),
    ]);

    if (forecastResult.status === 'fulfilled') {
        weather.value = forecastResult.value.forecast;
        weatherRevision.value = Date.now();
    }

    if (settingsResult.status === 'fulfilled') {
        preferences.value = settingsResult.value.preferences || {};
        applyTheme(preferences.value.theme || 'auto');
    }
}

async function importFromQuery() {
    const params = new URLSearchParams(window.location.search);
    const importedUrl = params.get('import');
    if (!importedUrl) return;

    window.history.replaceState({}, '', window.location.pathname);
    if (params.get('save') === '1') {
        try {
            await api('/api/feed/items', { method: 'POST', body: { url: importedUrl } });
            await loadRecent(true);
            return;
        } catch {
            // Fall back to the editable dialog when metadata fetching or saving fails.
        }
    }

    addForm.value.url = importedUrl;
    addDialog.value?.showModal();
}

onMounted(async () => {
    window.addEventListener('popstate', onReaderPopState);
    selectedFeedSource.value = feedSourceFromPath(props.path);
    selectedFeedLabel.value = selectedFeedSource.value === 'manual' ? 'Saved links' : '';
    await Promise.all([loadRecent(true), loadTags()]);
    loadHeaderData().catch(() => {});
    refreshTimer = window.setInterval(() => loadHeaderData().catch(() => {}), 300000);
    observer = new IntersectionObserver((entries) => {
        if (entries[0]?.isIntersecting && activeTab.value === 'recent' && nextPage.value !== null) loadRecent();
    }, { rootMargin: '300px' });
    await nextTick();
    if (sentinel.value) observer.observe(sentinel.value);
    await importFromQuery();
});


watch(
    () => props.path,
    (path) => {
        applyFeedSource(path).catch(() => {});
    },
);

onBeforeUnmount(() => {
    observer?.disconnect();
    window.clearInterval(refreshTimer);
    window.removeEventListener('popstate', onReaderPopState);
    clearLongPress();
});
</script>

<template>
    <section class="reader-shell" :class="{ 'focus-mode': focusMode, 'video-mode': videoMode }">
        <header class="app-header">
            <button class="brand-button" @click="closeCurrent"><img src="/images/FeedNews-Logo.png" alt="FeedNews"></button>
            <div class="header-actions">
                <button v-if="weather" class="weather-button" title="Weather forecast" @click="weatherOpen = !weatherOpen">
                    <img :src="weatherIcon(weather.currentWeather, true)" alt="">
                    <span>{{ Math.round(weather.currentTemp) }}°</span>
                </button>
                <button class="icon-button show-mobile" title="Weather" @click="emit('navigate', '/weather')">☀</button>
                <button class="icon-button" title="Todo" @click="emit('navigate', '/checklist')">✓</button>
                <button v-if="preferences.google_calendar" class="icon-button hide-mobile" title="Calendar" @click="calendarOpen = !calendarOpen">▦</button>
                <button v-if="currentItem" class="icon-button hide-mobile" title="Open floating reader" @click="openCurrentPopup">▣</button>
                <button v-if="currentItem" class="icon-button hide-mobile" title="Open new tab" @click="openCurrentExternal">↗</button>
                <button class="icon-button" title="Add saved link" @click="addDialog?.showModal()">＋</button>
                <button class="icon-button hide-mobile" title="Screensaver" @click="emit('navigate', '/screensaver')">◫</button>
                <div class="profile-wrap">
                    <button class="profile-button" @click="menuOpen = !menuOpen">
                        <img v-if="props.user.avatar" :src="props.user.avatar" alt="">
                        <span v-else>{{ props.user.name.slice(0, 1).toUpperCase() }}</span>
                        {{ props.user.name }} ▾
                    </button>
                    <nav v-if="menuOpen" class="profile-menu card">
                        <button @click="emit('navigate', '/welcome')">Dashboard</button>
                        <button @click="emit('navigate', '/settings')">Feed & settings</button>
                        <button @click="emit('navigate', '/feedback')">Feedback</button>
                        <button @click="emit('logout')">Sign out</button>
                    </nav>
                </div>
            </div>
        </header>

        <aside v-if="weatherOpen && weather" class="header-overlay weather-overlay card">
            <header><strong>{{ weather.location }}</strong><button class="icon-button" @click="weatherOpen = false">×</button></header>
            <div class="weather-current"><strong>{{ Math.round(weather.currentTemp) }}°C</strong><span>{{ weather.currentWeather.replace('_', ' ') }}</span></div>
            <div class="weather-days">
                <div v-for="day in weather.days.slice(1, 6)" :key="day.dayOffset">
                    <img :src="weatherIcon(day.weather)" alt="">
                    <strong>{{ Math.round(day.tempMax) }}°</strong><small>{{ Math.round(day.tempMin) }}°</small>
                    <span>{{ new Date(Date.now() + day.dayOffset * 86400000).toLocaleDateString([], { weekday: 'short' }) }}</span>
                </div>
            </div>
            <img class="radar-image" :src="`https://api.buienradar.nl/image/1.0/RadarMapNL?w=500&h=512&time=${weatherRevision}`" alt="Current Buienradar precipitation radar for the Netherlands">
        </aside>

        <aside v-if="calendarOpen && preferences.google_calendar" class="header-overlay calendar-overlay card">
            <header><strong>Calendar</strong><button class="icon-button" @click="calendarOpen = false">×</button></header>
            <iframe :src="preferences.google_calendar" title="Google Calendar"></iframe>
        </aside>

        <aside class="feed-sidebar">
            <div class="sidebar-tools">
                <form class="search-form" @submit.prevent="doSearch">
                    <input v-model="search" type="search" placeholder="Search" aria-label="Search feed items">
                    <button title="Search">⌕</button>
                </form>
                <div v-if="selectedFeedSource" class="feed-source-filter">
                    <strong>{{ selectedFeedLabel || 'Selected feed' }}</strong>
                    <button title="Show all feeds" @click="clearFeedSource">×</button>
                </div>
                <select v-model="selectedTag" aria-label="Filter by tag" @change="filterTag">
                    <option value="">All tags</option>
                    <option v-for="tag in tags" :key="tag.uuid" :value="tag.uuid">{{ tag.name }} ({{ tag.count }})</option>
                </select>
            </div>

            <div
                ref="feedList"
                class="feed-list"
                aria-live="polite"
                @touchstart.passive="pullStart"
                @touchmove.passive="pullMove"
                @touchend="pullEnd"
                @touchcancel="pullEnd"
            >
                <div
                    class="pull-refresh"
                    :class="{ active: pullDistance >= 60 || pullRefreshing }"
                    :style="{ height: `${pullDistance}px` }"
                    aria-live="polite"
                >
                    <span v-if="pullRefreshing">Refreshing…</span>
                    <span v-else-if="pullDistance >= 60">Release to refresh</span>
                    <span v-else-if="pullDistance > 0">Pull to refresh</span>
                </div>
                <template v-for="(item, index) in visibleItems" :key="item.uuid">
                <article
                    class="feed-item"
                    :class="{ pinned: item.pinned, active: currentItem?.uuid === item.uuid, striped: index % 2 === 1 }"
                    :style="{ '--feed-color': item.color }"
                    @pointerdown="pointerDown($event, item)"
                    @pointermove="pointerMove"
                    @pointerup="pointerUp($event, item)"
                    @pointercancel="pointerCancel"
                >
                    <button
                        class="feed-pin"
                        :class="{ active: item.pinned }"
                        :title="item.pinned ? 'Unpin' : 'Pin'"
                        :aria-label="item.pinned ? 'Unpin item' : 'Pin item'"
                        :aria-pressed="item.pinned"
                        @click="togglePin(item)"
                    >
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M14 4v4l2 2v2h-3v7l-1 2-1-2v-7H8v-2l2-2V4z" />
                        </svg>
                    </button>
                    <button class="feed-main" @click="openItem(item)">
                        <span class="feed-icon">
                            <img
                                v-if="showFavicon(item)"
                                :src="faviconUrl(item.url)"
                                alt=""
                                loading="lazy"
                                @error="markFaviconFailed(item)"
                            >
                            <span v-else class="feed-dot"></span>
                        </span>
                        <span class="feed-copy">
                            <strong>{{ item.title }}</strong>
                            <small>{{ item.description }}</small>
                        </span>
                    </button>
                    <div class="feed-actions">
                        <button
                            class="tag-action"
                            :class="{ active: item.tagUuid }"
                            :style="tagForItem(item) ? { '--tag-color': tagForItem(item).color } : undefined"
                            :title="tagForItem(item) ? `Tag: ${tagForItem(item).name}` : 'Tag'"
                            @click="openTagDialog(item)"
                        >
                            <template v-if="tagForItem(item)">
                                <span class="feed-tag-dot"></span>
                                {{ tagForItem(item).name }}
                            </template>
                            <template v-else>#</template>
                        </button>
                        <button class="hide-mobile" title="Open floating reader" @click="openPictureInPicture(item)">▣</button>
                        <button title="Open in new tab" @click="openExternal(item)">↗</button>
                    </div>
                </article>
                <button
                    v-if="index === pinnedItems.length - 1 && pinnedCount > 3"
                    class="pinned-toggle"
                    :disabled="pinnedLoading"
                    @click="togglePinnedList"
                >
                    {{ pinnedLoading ? 'Loading…' : (showAllPinned ? 'Show only 3 pinned items' : `Show all ${pinnedCount} pinned items`) }}
                </button>
                </template>
                <div ref="sentinel" class="sentinel"></div>
                <p v-if="loading" class="muted loading-row">Loading…</p>
                <p v-if="!loading && visibleItems.length === 0" class="empty-state">Nothing to show.</p>
            </div>

            <nav class="tab-bar">
                <button :class="{ active: activeTab === 'recent' }" @click="switchTab('recent')">Recent</button>
                <button :class="{ active: activeTab === 'history' }" @click="switchTab('history')">History</button>
                <button :class="{ active: activeTab === 'search' }" @click="doSearch">Search</button>
            </nav>
        </aside>

        <section class="content-pane" :class="{ 'has-content': hasContent }">
            <div v-if="hasContent" class="content-toolbar" title="Double-click to toggle focus mode" @dblclick="toggleFocusMode">
                <strong>{{ currentTitle }}</strong>
                <div class="content-toolbar-actions">
                    <button title="Toggle focus mode" @click="toggleFocusMode">{{ focusMode ? '⊞' : '⊟' }}</button>
                    <button v-if="currentItem" title="Open original website" @click="openCurrentExternal">↗</button>
                    <button title="Close" @click="closeCurrent">×</button>
                </div>
            </div>
            <iframe
                v-if="currentUrl"
                :src="currentUrl"
                :title="currentTitle"
                referrerpolicy="strict-origin-when-cross-origin"
                sandbox="allow-scripts allow-same-origin allow-presentation"
                allow="autoplay; fullscreen; picture-in-picture"
                allowfullscreen
            ></iframe>
            <div v-else-if="readerLoading" class="reader-loading">
                <div class="spinner"></div>
                <p>Artikel laden…</p>
            </div>
            <div v-else-if="readerError" class="reader-error card">
                <h2>Artikel kon niet worden geladen</h2>
                <p>{{ readerError }}</p>
                <button class="primary" @click="openCurrentExternal">Open originele website ↗</button>
            </div>
            <ArticleReader v-else-if="readerSource" :source="readerSource" />
            <Dashboard v-else @select-feed="openDashboardFeed" />
        </section>


        <aside v-if="pictureInPictureUrl || pictureInPictureSource" class="floating-reader card" aria-label="Floating reader">
            <header>
                <strong>{{ pictureInPictureTitle }}</strong>
                <button class="icon-button" title="Close floating reader" @click="closePictureInPicture">×</button>
            </header>
            <iframe
                v-if="pictureInPictureUrl"
                :src="pictureInPictureUrl"
                :title="pictureInPictureTitle"
                referrerpolicy="strict-origin-when-cross-origin"
                sandbox="allow-scripts allow-same-origin allow-presentation"
                allow="autoplay; fullscreen; picture-in-picture"
                allowfullscreen
            ></iframe>
            <div v-else-if="pictureInPictureSource" class="floating-reader-content">
                <ArticleReader :source="pictureInPictureSource" compact />
            </div>
        </aside>

        <dialog ref="addDialog" class="modal">
            <form class="dialog-form" @submit.prevent="addItem">
                <header><h2>Save a link</h2><button type="button" class="icon-button" @click="addDialog.close()">×</button></header>
                <label>URL<input v-model.trim="addForm.url" type="url" required @blur="metadata"></label>
                <label>Title<input v-model.trim="addForm.title"></label>
                <label>Description<textarea v-model.trim="addForm.description" rows="4"></textarea></label>
                <div class="dialog-actions"><button type="button" class="secondary" @click="metadata">Get metadata</button><button class="primary">Save</button></div>
            </form>
        </dialog>

        <dialog ref="tagDialog" class="modal">
            <div class="dialog-form">
                <header><h2>Tag item</h2><button class="icon-button" @click="tagDialog.close()">×</button></header>
                <button
                    class="tag-option"
                    :class="{ selected: (selectedItem?.tagUuid ?? null) === null }"
                    :aria-pressed="(selectedItem?.tagUuid ?? null) === null"
                    @click="assignTag(null)"
                >No tag</button>
                <button
                    v-for="tag in tags"
                    :key="tag.uuid"
                    class="tag-option"
                    :class="{ selected: selectedItem?.tagUuid === tag.uuid }"
                    :style="{ '--tag-color': tag.color }"
                    :aria-pressed="selectedItem?.tagUuid === tag.uuid"
                    @click="assignTag(tag.uuid)"
                >
                    <span></span>{{ tag.name }}
                </button>
                <form class="inline-form" @submit.prevent="createTag">
                    <input v-model.trim="newTag.name" minlength="3" placeholder="New tag">
                    <input v-model="newTag.color" type="color" aria-label="Tag color">
                    <button class="secondary">Add</button>
                </form>
            </div>
        </dialog>
    </section>
</template>
