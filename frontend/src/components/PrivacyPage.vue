<script setup>
const emit = defineEmits(['navigate']);
</script>
<template>
    <section class="document-page">
        <button class="back-button" @click="emit('navigate', '/')">← Back</button>
        <article class="card prose">
            <h1>Privacy policy</h1>
            <p>FeedNews stores account details, feed subscriptions, saved links, notes, todo items and the minimum technical information required to operate the service.</p>
            <p>External pages are only requested when you explicitly add a URL or when configured RSS feeds and weather data are refreshed. FeedNews does not sell personal data.</p>
            <p>OAuth sign-in, when configured, receives the account identifier, name, email address and optional profile image supplied by the provider.</p>
            <p>For removal or export of account data, contact the FeedNews administrator.</p>
        </article>
    </section>
</template>
