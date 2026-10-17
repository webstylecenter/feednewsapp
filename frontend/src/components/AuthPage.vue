<script setup>
import { ref } from 'vue';
import { api } from '../api.js';

const props = defineProps({
    oauth: { type: Object, default: () => ({}) },
    initialMode: { type: String, default: 'login' },
});
const emit = defineEmits(['authenticated', 'navigate']);

const mode = ref(props.initialMode === 'register' ? 'register' : 'login');
const loading = ref(false);
const oauthFailed = new URLSearchParams(window.location.search).get('oauth') === 'failed';
const error = ref(oauthFailed ? 'OAuth sign-in failed. Please try again.' : '');
const form = ref({ name: '', email: '', password: '', gdpr: false, address: '' });

async function submit() {
    loading.value = true;
    error.value = '';
    try {
        if (mode.value === 'login') {
            await api('/api/auth/login', {
                method: 'POST',
                body: { email: form.value.email, password: form.value.password },
            });
        } else {
            await api('/api/auth/register', { method: 'POST', body: form.value });
        }
        emit('authenticated');
    } catch (exception) {
        const errors = exception.data?.errors;
        error.value = errors ? Object.values(errors).join(' ') : exception.message;
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <section class="auth-layout">
        <div class="auth-brand">
            <img src="/images/FeedNews-Logo.png" alt="FeedNews">
            <h1>Your news, one clean workspace.</h1>
            <p>RSS feeds, saved articles, notes and todos without the noise.</p>
        </div>
        <div class="auth-panel">
            <form class="card auth-card" @submit.prevent="submit">
                <h2>{{ mode === 'login' ? 'Sign in' : 'Create account' }}</h2>
                <p v-if="error" class="alert error">{{ error }}</p>
                <label v-if="mode === 'register'">Name<input v-model.trim="form.name" required autocomplete="name"></label>
                <label>Email<input v-model.trim="form.email" type="email" required autocomplete="email"></label>
                <label>Password<input v-model="form.password" type="password" required :autocomplete="mode === 'login' ? 'current-password' : 'new-password'" minlength="8"></label>
                <input v-if="mode === 'register'" v-model="form.address" class="honeypot" tabindex="-1" autocomplete="off" aria-hidden="true">
                <label v-if="mode === 'register'" class="check-row">
                    <input v-model="form.gdpr" type="checkbox" required>
                    <span>I agree to the <button type="button" class="link-button" @click="emit('navigate', '/privacy-policy')">privacy policy</button>.</span>
                </label>
                <button class="primary" :disabled="loading">{{ loading ? 'Please wait…' : (mode === 'login' ? 'Sign in' : 'Register') }}</button>
                <div v-if="oauth?.google || oauth?.facebook" class="oauth-row">
                    <a v-if="oauth.google" class="secondary button-link" href="/api/auth/oauth/google">Google</a>
                    <a v-if="oauth.facebook" class="secondary button-link" href="/api/auth/oauth/facebook">Facebook</a>
                </div>
                <button type="button" class="link-button auth-switch" @click="mode = mode === 'login' ? 'register' : 'login'; error = ''">
                    {{ mode === 'login' ? 'No account yet? Register' : 'Already have an account? Sign in' }}
                </button>
            </form>
        </div>
    </section>
</template>
