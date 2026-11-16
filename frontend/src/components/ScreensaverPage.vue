<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { api } from '../api.js';

const emit = defineEmits(['navigate']);
const time = ref(new Date());
const items = ref([]);
const weather = ref(null);
const index = ref(0);
const wallpaperLayers = ref(['', '']);
const activeWallpaper = ref(0);
const drift = ref({
    clock: { x: 0, y: 0 },
    weather: { x: 0, y: 0 },
    news: { x: 0, y: 0 },
});

const current = computed(() => items.value[index.value] || null);
let mounted = false;
let wallpaperLoading = false;
let clockTimer;
let newsTimer;
let wallpaperTimer;
let driftTimer;
let refreshTimer;

function driftStyle(area) {
    return {
        transform: 'translate(' + drift.value[area].x + 'px, ' + drift.value[area].y + 'px)',
    };
}

function moveContent() {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    const offset = (distance) => Math.round((Math.random() * 2 - 1) * distance);
    drift.value = {
        clock: { x: offset(30), y: offset(18) },
        weather: { x: offset(26), y: offset(20) },
        news: { x: offset(26), y: offset(18) },
    };
}

function loadWallpaper() {
    if (wallpaperLoading) return;
    wallpaperLoading = true;

    const url = 'https://picsum.photos/1920/1080?random=' + Date.now();
    const image = new Image();

    image.onload = () => {
        wallpaperLoading = false;
        if (!mounted) return;

        const nextLayer = 1 - activeWallpaper.value;
        wallpaperLayers.value[nextLayer] = url;
        activeWallpaper.value = nextLayer;
    };
    image.onerror = () => {
        wallpaperLoading = false;
    };
    image.src = url;
}

async function refreshData() {
    const [feedResult, weatherResult] = await Promise.allSettled([
        api('/api/feed/items?page=0&limit=20'),
        api('/api/weather'),
    ]);

    if (!mounted) return;

    if (feedResult.status === 'fulfilled' && Array.isArray(feedResult.value.items)) {
        items.value = feedResult.value.items;
        index.value = items.value.length ? index.value % items.value.length : 0;
    }
    if (weatherResult.status === 'fulfilled') {
        weather.value = weatherResult.value.forecast;
    }
}

function closeScreensaver() {
    emit('navigate', '/');
}

onMounted(() => {
    mounted = true;
    clockTimer = window.setInterval(() => time.value = new Date(), 1000);
    newsTimer = window.setInterval(() => {
        if (items.value.length) index.value = (index.value + 1) % items.value.length;
    }, 9000);
    wallpaperTimer = window.setInterval(loadWallpaper, 120000);
    driftTimer = window.setInterval(moveContent, 55000);
    refreshTimer = window.setInterval(refreshData, 300000);

    loadWallpaper();
    moveContent();
    void refreshData();
});

onBeforeUnmount(() => {
    mounted = false;
    window.clearInterval(clockTimer);
    window.clearInterval(newsTimer);
    window.clearInterval(wallpaperTimer);
    window.clearInterval(driftTimer);
    window.clearInterval(refreshTimer);

    if (document.fullscreenElement) {
        void document.exitFullscreen().catch(() => {});
    }
});
</script>

<template>
    <section class="screensaver" aria-label="Screensaver" @click="closeScreensaver">
        <div
            v-for="(image, layer) in wallpaperLayers"
            :key="layer"
            class="screensaver-wallpaper"
            :class="{ 'is-active': activeWallpaper === layer && image }"
            :style="{ backgroundImage: image ? 'url(' + image + ')' : 'none' }"
            aria-hidden="true"
        ></div>
        <div class="screensaver-shade" aria-hidden="true"></div>

        <div class="screensaver-content">
            <div class="screensaver-top">
                <div class="screensaver-clock screensaver-drift" :style="driftStyle('clock')">
                    <time>{{ time.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}</time>
                </div>
                <div v-if="weather" class="screensaver-weather screensaver-drift" :style="driftStyle('weather')">
                    <strong>{{ Math.round(weather.currentTemp) }}°</strong>
                    <span>{{ weather.location }}</span>
                </div>
            </div>

            <div v-if="current" class="screensaver-news screensaver-drift" :style="driftStyle('news')">
                <span>{{ current.feedName || 'FeedNews' }}</span>
                <h1>{{ current.title }}</h1>
                <p>{{ current.description }}</p>
            </div>
        </div>

        <button
            class="screensaver-close"
            type="button"
            aria-label="Close screensaver"
            title="Close screensaver"
            @click.stop="closeScreensaver"
        >×</button>
    </section>
</template>
