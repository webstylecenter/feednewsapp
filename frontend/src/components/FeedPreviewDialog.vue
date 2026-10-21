<script setup>
defineProps({
    feed: { type: Object, default: null },
    preview: { type: Object, default: null },
    loading: { type: Boolean, default: false },
});

defineEmits(['close', 'subscribe']);
</script>

<template>
    <div v-if="feed" class="feed-preview-backdrop" role="presentation" @click.self="$emit('close')">
        <section class="feed-preview-modal card" role="dialog" aria-modal="true" :aria-label="`Preview ${feed.name}`">
            <header>
                <div>
                    <p class="eyebrow">Feed preview</p>
                    <h2>{{ feed.name }}</h2>
                </div>
                <button type="button" class="icon-button" aria-label="Close preview" @click="$emit('close')">×</button>
            </header>

            <p v-if="loading" class="muted feed-preview-status">Loading latest items…</p>
            <div v-else-if="preview?.items?.length" class="feed-preview-items">
                <a
                    v-for="item in preview.items"
                    :key="item.uuid"
                    :href="item.url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="feed-preview-item"
                >
                    <strong>{{ item.title }}</strong>
                    <small v-if="item.description">{{ item.description }}</small>
                </a>
            </div>
            <p v-else class="muted feed-preview-status">No items received for this feed yet.</p>

            <footer>
                <button type="button" class="secondary" @click="$emit('close')">Cancel</button>
                <button type="button" class="primary" :disabled="loading" @click="$emit('subscribe', feed)">Subscribe</button>
            </footer>
        </section>
    </div>
</template>

<style scoped>
.feed-preview-backdrop {
    position: fixed;
    z-index: 120;
    inset: 0;
    display: grid;
    place-items: center;
    padding: 1rem;
    background: rgb(15 23 42 / 58%);
    backdrop-filter: blur(2px);
}

.feed-preview-modal {
    width: min(620px, 100%);
    max-height: min(760px, calc(100vh - 2rem));
    display: grid;
    grid-template-rows: auto minmax(0, 1fr) auto;
    overflow: hidden;
}

.feed-preview-modal > header,
.feed-preview-modal > footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.2rem;
}

.feed-preview-modal > header {
    border-bottom: 1px solid var(--border);
}

.feed-preview-modal > footer {
    justify-content: flex-end;
    border-top: 1px solid var(--border);
}

.feed-preview-modal h2,
.feed-preview-modal .eyebrow {
    margin: 0;
}

.feed-preview-items {
    min-height: 0;
    display: grid;
    gap: .55rem;
    padding: 1rem 1.2rem;
    overflow-y: auto;
}

.feed-preview-item {
    display: grid;
    gap: .3rem;
    padding: .8rem;
    border: 1px solid var(--border);
    border-radius: 9px;
    background: var(--surface-soft);
    text-decoration: none;
}

.feed-preview-item:hover {
    border-color: var(--blue);
}

.feed-preview-item small {
    display: -webkit-box;
    overflow: hidden;
    color: var(--muted);
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
    line-clamp: 2;
}

.feed-preview-status {
    padding: 1.2rem;
}
</style>
