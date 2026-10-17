<script setup>
import { onMounted, ref } from 'vue';
import { api } from '../api.js';

defineProps({ user: { type: Object, required: true } });
const emit = defineEmits(['finish', 'navigate']);
const step = ref(0);
const settings = ref({ availableFeeds: [], categories: [] });
const followed = ref(new Set());
const frameCheckDisabled = ref(false);

async function load() {
    settings.value = await api('/api/settings');
    frameCheckDisabled.value = Boolean(settings.value.hideXFrameNotice);
}

async function follow(feed) {
    if (followed.value.has(feed.id)) return;
    await api(`/api/settings/feeds/${feed.id}/follow`, { method: 'POST', body: { color: feed.color } });
    followed.value = new Set([...followed.value, feed.id]);
}

async function disableFrameCheck() {
    await api('/api/settings/hide-x-frame-notice', { method: 'POST' });
    frameCheckDisabled.value = true;
}

onMounted(load);
</script>

<template>
    <section class="intro-page">
        <div class="intro-card card">
            <template v-if="step === 0">
                <img src="/images/FeedNews-Logo.png" alt="FeedNews" class="intro-logo">
                <h1>Welcome, {{ user.name }}</h1>
                <p>FeedNews combines your news feeds, saved links, notes and todos in a focused reader.</p>
                <button class="primary" @click="step = 1">Choose feeds</button>
            </template>
            <template v-else-if="step === 1">
                <h1>Pick a starting set</h1>
                <p>You can add, remove and customise feeds later in settings.</p>
                <div class="intro-feeds">
                    <button v-for="feed in settings.availableFeeds" :key="feed.id" :class="{ selected: followed.has(feed.id) }" @click="follow(feed)">
                        <span class="feed-dot" :style="{ '--feed-color': feed.color }"></span>
                        <span><strong>{{ feed.name }}</strong><small>{{ feed.category || 'Feed' }}</small></span>
                        <span>{{ followed.has(feed.id) ? '✓' : '+' }}</span>
                    </button>
                </div>
                <div class="dialog-actions"><button class="secondary" @click="step = 0">Back</button><button class="primary" @click="step = 2">Continue</button></div>
            </template>
            <template v-else>
                <h1>Good to know</h1>
                <ul class="feature-list">
                    <li>Swipe an item right to pin it; swipe left to tag it.</li>
                    <li>Pages that block embedding automatically open in a new tab.</li>
                    <li>The dashboard contains notes, todos, weather and your feed overview.</li>
                    <li>Use the + button to save any page manually.</li>
                    <li><a href="/downloads/chrome-extension.zip">Download the Manifest V3 browser extension</a> to save tabs and support sites that block iframe embedding.</li>
                </ul>
                <p v-if="!frameCheckDisabled" class="muted">After installing the extension, disable FeedNews' server-side frame check so those pages are allowed to load in the split-screen reader.</p>
                <div class="dialog-actions intro-final-actions">
                    <button v-if="!frameCheckDisabled" class="secondary" @click="disableFrameCheck">I installed it — disable frame checks</button>
                    <button class="primary" @click="emit('finish')">Start using FeedNews</button>
                </div>
            </template>
        </div>
    </section>
</template>
