<script setup>
import { onMounted, ref } from 'vue';
import { api } from '../api.js';

const emit = defineEmits(['navigate']);
const items = ref([]);
const todo = ref('');
const loading = ref(true);
const saving = ref(false);
const error = ref('');

async function load() {
    loading.value = true;
    error.value = '';

    try {
        items.value = (await api('/api/checklist')).items;
    } catch (exception) {
        error.value = exception.message || 'Could not load the checklist.';
    } finally {
        loading.value = false;
    }
}

async function addTodo() {
    if (todo.value.trim().length < 3 || saving.value) return;

    saving.value = true;
    error.value = '';

    try {
        const result = await api('/api/checklist', {
            method: 'POST',
            body: { item: todo.value.trim() },
        });
        items.value.unshift(result.item);
        todo.value = '';
    } catch (exception) {
        error.value = exception.message || 'Could not add the todo.';
    } finally {
        saving.value = false;
    }
}

async function toggleTodo(item) {
    try {
        const result = await api(`/api/checklist/${item.id}`, { method: 'PATCH' });
        Object.assign(item, result.item);
    } catch (exception) {
        error.value = exception.message || 'Could not update the todo.';
    }
}

onMounted(load);
</script>

<template>
    <section class="utility-page">
        <header class="page-header">
            <button class="back-button light" @click="emit('navigate', '/')">← Back</button>
            <h1>Todo</h1>
            <span></span>
        </header>

        <div class="utility-content card">
            <form class="utility-add-form" @submit.prevent="addTodo">
                <label>
                    New todo
                    <input v-model="todo" minlength="3" maxlength="255" placeholder="What do you need to remember?" autofocus>
                </label>
                <button class="primary" :disabled="saving || todo.trim().length < 3">Add</button>
            </form>

            <p v-if="error" class="alert error" role="alert">{{ error }}</p>
            <p v-if="loading" class="muted">Loading…</p>

            <div v-else class="checklist-page-list">
                <label v-for="item in items" :key="item.uuid" class="checklist-page-row">
                    <input :checked="item.checked" type="checkbox" @change="toggleTodo(item)">
                    <span :class="{ done: item.checked }">{{ item.item }}</span>
                </label>
                <p v-if="items.length === 0" class="muted">No todos yet.</p>
            </div>
        </div>
    </section>
</template>
