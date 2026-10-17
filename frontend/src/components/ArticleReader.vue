<script setup>
import DOMPurify from 'dompurify';
import { Readability } from '@mozilla/readability';
import { computed } from 'vue';

const props = defineProps({
    source: {
        type: Object,
        required: true,
    },
    compact: {
        type: Boolean,
        default: false,
    },
});

function absoluteUrl(value, baseUrl) {
    if (!value) return '';

    try {
        const url = new URL(value, baseUrl);
        return ['http:', 'https:'].includes(url.protocol) ? url.href : '';
    } catch {
        return '';
    }
}

function normalizeSrcset(value, baseUrl) {
    if (!value) return '';

    return value
        .split(',')
        .map((candidate) => {
            const parts = candidate.trim().split(/\s+/);
            const url = absoluteUrl(parts.shift() || '', baseUrl);
            return url ? [url, ...parts].join(' ') : '';
        })
        .filter(Boolean)
        .join(', ');
}

function prepareDocument(html, baseUrl) {
    const document = new DOMParser().parseFromString(html, 'text/html');
    const base = document.createElement('base');
    base.href = baseUrl;
    document.head.prepend(base);

    document.querySelectorAll('script, noscript, iframe, object, embed, form').forEach((element) => element.remove());

    document.querySelectorAll('img').forEach((image) => {
        if (!image.getAttribute('src')) {
            const lazySource = image.getAttribute('data-src')
                || image.getAttribute('data-original')
                || image.getAttribute('data-lazy-src');
            if (lazySource) image.setAttribute('src', lazySource);
        }
        if (!image.getAttribute('srcset')) {
            const lazySrcset = image.getAttribute('data-srcset');
            if (lazySrcset) image.setAttribute('srcset', lazySrcset);
        }
    });

    return document;
}

function normalizeContent(content, baseUrl) {
    const document = new DOMParser().parseFromString(`<main>${content}</main>`, 'text/html');
    const root = document.querySelector('main');
    if (!root) return '';

    root.querySelectorAll('a[href]').forEach((link) => {
        const href = absoluteUrl(link.getAttribute('href'), baseUrl);
        if (!href) {
            link.removeAttribute('href');
            return;
        }

        link.setAttribute('href', href);
        link.setAttribute('target', '_blank');
        link.setAttribute('rel', 'noopener noreferrer');
    });

    root.querySelectorAll('img[src], source[src]').forEach((element) => {
        const source = absoluteUrl(element.getAttribute('src'), baseUrl);
        if (source) element.setAttribute('src', source);
        else element.removeAttribute('src');
    });

    root.querySelectorAll('[srcset]').forEach((element) => {
        const srcset = normalizeSrcset(element.getAttribute('srcset'), baseUrl);
        if (srcset) element.setAttribute('srcset', srcset);
        else element.removeAttribute('srcset');
    });

    root.querySelectorAll('img').forEach((image) => {
        image.setAttribute('loading', 'lazy');
        image.setAttribute('decoding', 'async');
    });

    return DOMPurify.sanitize(root.innerHTML, {
      ADD_ATTR: ['target'],
      FORBID_TAGS: [
        'script',
        'style',
        'iframe',
        'object',
        'embed',
        'form',
        'input',
        'button',
        'textarea',
        'select',
      ],
      FORBID_ATTR: [
        'style',
        'srcdoc',
      ],
      ALLOW_DATA_ATTR: false,
    });
}

const article = computed(() => {
    const document = prepareDocument(props.source.html, props.source.url);
    const parsed = new Readability(document, {
        charThreshold: 140,
        keepClasses: false,
    }).parse();

    if (!parsed?.content || parsed.textContent.trim().length < 80) {
        return null;
    }

    return {
        title: parsed.title || '',
        byline: parsed.byline || '',
        excerpt: parsed.excerpt || '',
        content: normalizeContent(parsed.content, props.source.url),
    };
});

const mainImage = computed(() => absoluteUrl(props.source.mainImage, props.source.url));
const showMainImage = computed(() => {
    if (!mainImage.value || !article.value) return Boolean(mainImage.value);
    return !article.value.content.includes(mainImage.value);
});
</script>

<template>
    <article class="reader-article" :class="{ compact }">
        <template v-if="article">
            <header class="reader-article-header">
                <p v-if="source.siteName" class="reader-source">{{ source.siteName }}</p>
                <h1>{{ article.title }}</h1>
                <p v-if="article.byline" class="reader-byline">{{ article.byline }}</p>
                <p v-if="article.excerpt" class="reader-excerpt">{{ article.excerpt }}</p>
            </header>

            <img
                v-if="showMainImage"
                class="reader-main-image"
                :src="mainImage"
                :alt="article.title"
                referrerpolicy="no-referrer-when-downgrade"
            >

            <div class="reader-article-content" v-html="article.content"></div>
        </template>

        <div v-else class="reader-unavailable card">
            <h2>Dit artikel kon niet betrouwbaar worden uitgelezen</h2>
            <p>De website levert onvoldoende leesbare inhoud aan de FeedNews reader.</p>
        </div>

        <footer class="reader-original-link">
            <a class="primary button-link" :href="source.url" target="_blank" rel="noopener noreferrer">
                Open originele website ↗
            </a>
        </footer>
    </article>
</template>
