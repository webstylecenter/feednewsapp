<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { api } from '../api.js';
import { weatherIcon } from '../presentation.js';

const data = ref({ notes: [], todos: [], feeds: [], weather: null });
const todo = ref('');
const status = ref('');
const noteTimers = new Map();

async function load() {
    data.value = await api('/api/dashboard');
}

async function saveNote(note) {
    const result = await api('/api/notes', { method: 'POST', body: note });
    Object.assign(note, result.item);
    status.value = 'Saved';
    window.setTimeout(() => status.value = '', 1200);
}

function queueNoteSave(note) {
    window.clearTimeout(noteTimers.get(note));
    noteTimers.set(note, window.setTimeout(() => {
        noteTimers.delete(note);
        saveNote(note);
    }, 600));
}

async function removeNote(note) {
    await api(`/api/notes/${note.id}`, { method: 'DELETE' });
    data.value.notes = data.value.notes.filter((item) => item.id !== note.id);
}

function addLocalNote() {
    data.value.notes.push({ id: null, name: 'Note', position: data.value.notes.length, content: '' });
}

async function addTodo() {
    if (todo.value.trim().length < 3) return;
    const result = await api('/api/checklist', { method: 'POST', body: { item: todo.value } });
    data.value.todos.unshift(result.item);
    todo.value = '';
}

async function toggleTodo(item) {
    const result = await api(`/api/checklist/${item.id}`, { method: 'PATCH' });
    Object.assign(item, result.item);
}

onMounted(load);
onBeforeUnmount(() => {
    for (const timer of noteTimers.values()) window.clearTimeout(timer);
});
</script>

<template>
    <section class="dashboard">
        <div class="dashboard-hero">
            <div>
                <p class="eyebrow">FeedNews</p>
                <h1>Dashboard</h1>
            </div>
            <div v-if="data.weather" class="weather-now">
                <img :src="weatherIcon(data.weather.currentWeather, true)" alt="">
                <strong>{{ Math.round(data.weather.currentTemp) }}°</strong>
                <span>{{ data.weather.location }}</span>
            </div>
        </div>

        <div class="widget-grid">
            <article class="widget card">
                <header><h2>Notes</h2><button class="icon-button" title="Add note" @click="addLocalNote">＋</button></header>
                <p v-if="status" class="muted">{{ status }}</p>
                <div v-for="note in data.notes" :key="note.id ?? `new-${note.position}`" class="note-row">
                    <input v-model="note.name" maxlength="255" aria-label="Note name" @input="queueNoteSave(note)">
                    <textarea v-model="note.content" rows="3" aria-label="Note content" @input="queueNoteSave(note)"></textarea>
                    <div class="row-actions">
                        <button class="small secondary" @click="saveNote(note)">Save</button>
                        <button v-if="note.id" class="small danger" @click="removeNote(note)">Delete</button>
                    </div>
                </div>
                <p v-if="data.notes.length === 0" class="muted">No notes yet.</p>
            </article>

            <article class="widget card">
                <header><h2>Todo</h2></header>
                <form class="inline-form" @submit.prevent="addTodo">
                    <input v-model="todo" placeholder="Add todo" minlength="3">
                    <button class="primary">Add</button>
                </form>
                <label v-for="item in data.todos" :key="item.id" class="todo-row">
                    <input :checked="item.checked" type="checkbox" @change="toggleTodo(item)">
                    <span :class="{ done: item.checked }">{{ item.item }}</span>
                </label>
            </article>

            <article class="widget card span-two">
                <header><h2>Your feeds</h2><span class="muted">{{ data.feeds.length }} followed</span></header>
                <div class="feed-overview">
                    <div v-for="feed in data.feeds" :key="feed.id" class="feed-pill" :style="{ '--feed-color': feed.color }">
                        <span class="feed-dot"></span><strong>{{ feed.name }}</strong><small>{{ feed.itemCount }} items</small>
                    </div>
                    <p v-if="data.feeds.length === 0" class="muted">No feeds configured.</p>
                </div>
            </article>
        </div>
    </section>
</template>
