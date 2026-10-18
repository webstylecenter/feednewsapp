<script setup>
import Dashboard from './Dashboard.vue';

const emit = defineEmits(['navigate']);
</script>

<template>
    <section class="utility-page dashboard-page">
        <header class="page-header">
            <button class="back-button light" @click="emit('navigate', '/')">← FeedNews</button>
            <h1>Dashboard</h1>
            <span></span>
        </header>
        <Dashboard @select-feed="(source) => emit('navigate', `/feed/${source}`)" />
    </section>
</template>
