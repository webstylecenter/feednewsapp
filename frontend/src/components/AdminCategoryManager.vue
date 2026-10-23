<script setup>
import { ref, watch } from 'vue';
import { api } from '../api.js';

const props = defineProps({ categories: { type: Array, required: true } });
const emit = defineEmits(['changed', 'notify']);
const categoryName = ref('');
const categoryEdits = ref({});
const pendingCategoryRemoval = ref('');

watch(
    () => props.categories,
    (categories) => {
        categoryEdits.value = Object.fromEntries(categories.map((category) => [category.uuid, category.name]));
    },
    { immediate: true },
);

async function createCategory() {
    try {
        await api('/api/admin/categories', {
            method: 'POST',
            body: { name: categoryName.value },
        });
        categoryName.value = '';
        emit('notify', 'Category added.');
        emit('changed');
    } catch (exception) {
        emit('notify', exception.message, true);
    }
}

async function updateCategory(category) {
    try {
        await api(`/api/admin/categories/${category.uuid}`, {
            method: 'PATCH',
            body: { name: categoryEdits.value[category.uuid] },
        });
        emit('notify', 'Category updated.');
        emit('changed');
    } catch (exception) {
        emit('notify', exception.message, true);
    }
}

async function removeCategory(category) {
    if (pendingCategoryRemoval.value !== category.uuid) {
        pendingCategoryRemoval.value = category.uuid;
        return;
    }

    try {
        await api(`/api/admin/categories/${category.uuid}`, { method: 'DELETE' });
        pendingCategoryRemoval.value = '';
        emit('notify', 'Category removed. Feeds in this category are now uncategorised.');
        emit('changed');
    } catch (exception) {
        pendingCategoryRemoval.value = '';
        emit('notify', exception.message, true);
    }
}
</script>

<template>
    <div class="admin-category-management">
        <h3>Categories</h3>
        <p class="muted">Categories are shared by all users and can be assigned to any feed.</p>
        <form class="admin-category-add" @submit.prevent="createCategory">
            <label>
                <span>New category</span>
                <input v-model.trim="categoryName" type="text" maxlength="255" required>
            </label>
            <button class="primary small">Add category</button>
        </form>
        <div v-if="categories.length" class="admin-category-list">
            <div v-for="category in categories" :key="category.uuid" class="admin-category-row">
                <input
                    v-model.trim="categoryEdits[category.uuid]"
                    type="text"
                    maxlength="255"
                    :aria-label="`${category.name} category name`"
                >
                <button class="secondary small" @click="updateCategory(category)">Save</button>
                <button
                    class="danger small"
                    :class="{ confirming: pendingCategoryRemoval === category.uuid }"
                    @click="removeCategory(category)"
                >
                    {{ pendingCategoryRemoval === category.uuid ? 'Are you sure?' : 'Delete' }}
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.admin-category-management {
    margin-bottom: 1.75rem;
}

.admin-category-management h3 {
    margin-bottom: .2rem;
}

.admin-category-management > p {
    margin-top: 0;
}

.admin-category-add {
    display: flex;
    align-items: flex-end;
    gap: .65rem;
    margin-bottom: .8rem;
}

.admin-category-add label {
    flex: 1 1 280px;
    max-width: 420px;
}

.admin-category-add span {
    display: block;
    margin-bottom: .25rem;
    color: var(--muted);
    font-size: .78rem;
}

.admin-category-list {
    display: grid;
    gap: .55rem;
    max-width: 700px;
}

.admin-category-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto auto;
    gap: .55rem;
}

.admin-category-row .confirming {
    background: var(--danger);
    color: #fff;
}

@media (max-width: 600px) {
    .admin-category-add {
        align-items: stretch;
        flex-direction: column;
    }

    .admin-category-add label {
        flex: 0 1 auto;
        max-width: none;
    }

    .admin-category-row {
        grid-template-columns: 1fr 1fr;
    }

    .admin-category-row input {
        grid-column: 1 / -1;
    }
}
</style>
