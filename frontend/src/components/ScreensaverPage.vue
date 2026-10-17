<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { api } from '../api.js';

const emit = defineEmits(['navigate']);
const time = ref(new Date());
const items = ref([]);
const weather = ref(null);
const index = ref(0);
let clockTimer;
let newsTimer;

const current = computed(() => items.value[index.value] || null);

onMounted(async () => {
    clockTimer = window.setInterval(() => time.value = new Date(), 1000);
    newsTimer = window.setInterval(() => {
        if (items.value.length) index.value = (index.value + 1) % items.value.length;
    }, 9000);

    const [feedResult, weatherResult] = await Promise.allSettled([
        api('/api/feed/items?page=0&limit=20'),
        api('/api/weather'),
    ]);

    if (feedResult.status === 'fulfilled') items.value = feedResult.value.items;
    if (weatherResult.status === 'fulfilled') weather.value = weatherResult.value.forecast;
});

onBeforeUnmount(() => {
    window.clearInterval(clockTimer);
    window.clearInterval(newsTimer);
});
</script>
<template>
    <section class="screensaver" @click="emit('navigate', '/')">
        <button class="screensaver-close" aria-label="Close">×</button>
        <div class="screensaver-top">
            <time>{{ time.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}</time>
            <div v-if="weather" class="screensaver-weather"><strong>{{ Math.round(weather.currentTemp) }}°</strong><span>{{ weather.location }}</span></div>
        </div>
        <div v-if="current" class="screensaver-news">
            <span>{{ current.feedName || 'FeedNews' }}</span>
            <h1>{{ current.title }}</h1>
            <p>{{ current.description }}</p>
        </div>
    </section>
</template>
