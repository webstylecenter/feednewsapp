<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { api, setCsrfToken } from './api.js';
import AuthPage from './components/AuthPage.vue';
import ChecklistPage from './components/ChecklistPage.vue';
import DashboardPage from './components/DashboardPage.vue';
import FeedReader from './components/FeedReader.vue';
import FeedbackPage from './components/FeedbackPage.vue';
import IntroductionPage from './components/IntroductionPage.vue';
import OfflinePage from './components/OfflinePage.vue';
import PrivacyPage from './components/PrivacyPage.vue';
import ScreensaverPage from './components/ScreensaverPage.vue';
import SettingsPage from './components/SettingsPage.vue';
import WeatherPage from './components/WeatherPage.vue';

const session = ref(null);
const loading = ref(true);
const path = ref(window.location.pathname);

async function loadSession() {
    try {
        session.value = await api('/api/auth/session');
        setCsrfToken(session.value.csrfToken);
    } catch {
        session.value = { user: null, oauth: {}, firstVisit: false, csrfToken: '' };
        setCsrfToken('');
    } finally {
        loading.value = false;
    }
}

function navigate(target) {
    if (target !== window.location.pathname) {
        window.history.pushState({}, '', target);
    }
    path.value = target;
}

function onPopState() {
    path.value = window.location.pathname;
}

async function authenticated() {
    loading.value = true;
    await loadSession();
    if (session.value?.firstVisit) {
        navigate('/introduction');
    } else {
        navigate('/');
    }
}

async function logout() {
    await api('/api/auth/logout', { method: 'POST' });
    session.value = null;
    navigate('/');
}

function finishIntroduction() {
    if (session.value) {
        session.value.firstVisit = false;
    }
    navigate('/');
}

onMounted(() => {
    window.addEventListener('popstate', onPopState);
    loadSession();
});

onBeforeUnmount(() => window.removeEventListener('popstate', onPopState));
</script>

<template>
    <main v-if="loading" class="center-page"><div class="spinner" aria-label="Loading"></div></main>
    <PrivacyPage v-else-if="path === '/privacy-policy'" @navigate="navigate" />
    <OfflinePage v-else-if="path === '/offline'" @navigate="navigate" />
    <AuthPage
        v-else-if="!session?.user"
        :oauth="session?.oauth"
        :initial-mode="path === '/register' ? 'register' : 'login'"
        @authenticated="authenticated"
        @navigate="navigate"
    />
    <IntroductionPage
        v-else-if="path === '/introduction' || (session.firstVisit && (path === '/' || path === '/welcome'))"
        :user="session.user"
        @finish="finishIntroduction"
        @navigate="navigate"
    />
    <DashboardPage v-else-if="path === '/welcome'" @navigate="navigate" />
    <SettingsPage v-else-if="path === '/settings'" :user="session.user" @navigate="navigate" @logout="logout" />
    <ChecklistPage v-else-if="path === '/checklist'" @navigate="navigate" />
    <WeatherPage v-else-if="path === '/weather'" @navigate="navigate" />
    <FeedbackPage v-else-if="path === '/feedback'" @navigate="navigate" />
    <ScreensaverPage v-else-if="path === '/screensaver'" @navigate="navigate" />
    <FeedReader v-else :user="session.user" :path="path" @navigate="navigate" @logout="logout" />
</template>
