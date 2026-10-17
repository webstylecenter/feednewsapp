<script setup>
const emit = defineEmits(['navigate']);
</script>

<template>
    <section class="center-page offline-page">
        <article class="card offline-card">
            <img src="/images/FeedNews-Logo.png" alt="FeedNews">
            <h1>You are offline</h1>
            <p class="muted">FeedNews cannot load fresh items until your connection is restored.</p>
            <button class="primary" @click="emit('navigate', '/')">Try again</button>
        </article>
    </section>
</template>
