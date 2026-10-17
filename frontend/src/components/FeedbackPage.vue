<script setup>
const emit = defineEmits(['navigate']);
</script>

<template>
    <section class="document-page">
        <button class="back-button" @click="emit('navigate', '/')">← Back</button>
        <article class="card prose">
            <h1>Feedback</h1>
            <p>Comments, suggestions and bug reports can be sent to <a href="mailto:peter@petervdam.nl">peter@petervdam.nl</a>.</p>
            <p>For feed-specific problems, include the feed URL and what you expected to happen. Administrators can also inspect the deduplicated feed refresh error log from Settings.</p>
            <p>The project can also be improved through <a href="https://github.com/webstylecenter/feednews/" target="_blank" rel="noopener noreferrer">GitHub</a>.</p>
        </article>
    </section>
</template>
