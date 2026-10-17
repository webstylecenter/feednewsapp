<script setup>
import { onMounted, ref } from 'vue';
import { api } from '../api.js';
import { weatherIcon } from '../presentation.js';

const emit = defineEmits(['navigate']);
const forecast = ref(null);
const error = ref('');
const radarRevision = ref(Date.now());

function weekday(dayOffset) {
    return new Date(Date.now() + dayOffset * 86400000).toLocaleDateString([], {
        weekday: 'long',
    });
}

onMounted(async () => {
    try {
        forecast.value = (await api('/api/weather')).forecast;
        radarRevision.value = Date.now();
    } catch (exception) {
        error.value = exception.message || 'Could not load the weather forecast.';
    }
});
</script>

<template>
    <section class="utility-page">
        <header class="page-header">
            <button class="back-button light" @click="emit('navigate', '/')">← Back</button>
            <h1>Weather</h1>
            <span></span>
        </header>

        <div class="utility-content">
            <p v-if="error" class="alert error card" role="alert">{{ error }}</p>
            <div v-else-if="!forecast" class="card utility-loading">Loading…</div>

            <template v-else>
                <article class="weather-page-current card">
                    <img :src="weatherIcon(forecast.currentWeather)" alt="">
                    <div>
                        <p class="eyebrow">{{ forecast.location }}</p>
                        <strong>{{ Math.round(forecast.currentTemp) }}°C</strong>
                        <span>{{ forecast.currentWeather.replaceAll('_', ' ') }}</span>
                    </div>
                </article>

                <article class="weather-page-days card">
                    <div v-for="day in forecast.days" :key="day.dayOffset" class="weather-page-day">
                        <strong>{{ weekday(day.dayOffset) }}</strong>
                        <img :src="weatherIcon(day.weather)" alt="">
                        <span>{{ Math.round(day.tempMax) }}° / {{ Math.round(day.tempMin) }}°</span>
                        <small>{{ day.weather.replaceAll('_', ' ') }}</small>
                    </div>
                </article>

                <article class="weather-page-radar card">
                    <h2>Rain radar</h2>
                    <img :src="`https://api.buienradar.nl/image/1.0/RadarMapNL?w=700&h=720&time=${radarRevision}`" alt="Current Buienradar precipitation radar for the Netherlands">
                </article>
            </template>
        </div>
    </section>
</template>
