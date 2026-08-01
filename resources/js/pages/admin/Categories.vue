<script setup lang="ts">
import { router, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AdminNav from '@/components/AdminNav.vue';
import InputError from '@/components/InputError.vue';
import type { CategoryItem } from '@/types/content';

const props = defineProps<{
    categories: CategoryItem[];
    adminPath: string;
}>();

const page = usePage();
const pageError = computed(() => (page.props.errors as any)?.message ?? '');

const addForm = useForm({ name: '', description: '' });
const editingId = ref<number | null>(null);
const editForm = useForm({ name: '', description: '' });

function addCategory() {
    addForm.post(`/${props.adminPath}/categories`, {
        preserveScroll: true,
        onSuccess: () => addForm.reset(),
    });
}

function startEdit(category: CategoryItem) {
    editingId.value = category.id;
    editForm.name = category.name;
    editForm.description = category.description ?? '';
    editForm.clearErrors();
}

function saveEdit(category: CategoryItem) {
    editForm.put(`/${props.adminPath}/categories/${category.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            editingId.value = null;
        },
    });
}

function cancelEdit() {
    editingId.value = null;
}

function deleteCategory(category: CategoryItem) {
    if (!confirm(`Delete category "${category.name}"?`)) return;
    router.delete(`/${props.adminPath}/categories/${category.id}`, {
        preserveScroll: true,
    });
}

const inputClass =
    'w-full rounded-md border border-zinc-700 bg-zinc-800 px-3 py-2 text-sm text-white placeholder-zinc-600 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500';
</script>

<template>
    <div class="min-h-screen bg-zinc-950 p-8 text-zinc-100">
        <div class="mx-auto max-w-5xl">
            <div class="mb-8 flex items-center justify-between">
                <h1 class="text-2xl font-bold">Categories</h1>
                <AdminNav :admin-path="adminPath" current="categories" />
            </div>

            <div
                v-if="pageError"
                class="mb-6 rounded border border-red-800 bg-red-950 px-4 py-3 text-sm text-red-300"
            >
                {{ pageError }}
            </div>

            <!-- Add form -->
            <form
                @submit.prevent="addCategory"
                class="mb-8 rounded-lg border border-zinc-800 bg-zinc-900 p-6"
            >
                <div class="grid grid-cols-1 items-start gap-4 md:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-xs text-zinc-500"
                            >Name</label
                        >
                        <input
                            v-model="addForm.name"
                            type="text"
                            :class="inputClass"
                            placeholder="e.g. Weather"
                        />
                        <InputError :message="addForm.errors.name" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs text-zinc-500"
                            >Description (optional)</label
                        >
                        <input
                            v-model="addForm.description"
                            type="text"
                            :class="inputClass"
                        />
                        <InputError :message="addForm.errors.description" />
                    </div>
                    <div class="md:pt-5">
                        <button
                            type="submit"
                            :disabled="addForm.processing"
                            class="rounded bg-blue-600 px-4 py-2 text-sm font-medium hover:bg-blue-700 disabled:opacity-50"
                        >
                            Add Category
                        </button>
                    </div>
                </div>
            </form>

            <!-- Table -->
            <div
                v-if="categories.length === 0"
                class="py-12 text-center text-zinc-500"
            >
                No categories yet.
            </div>
            <div
                v-else
                class="overflow-hidden rounded-lg border border-zinc-800 bg-zinc-900"
            >
                <table class="w-full text-sm">
                    <thead>
                        <tr
                            class="border-b border-zinc-800 text-left text-xs text-zinc-500"
                        >
                            <th class="px-4 py-3 font-medium">Name</th>
                            <th class="px-4 py-3 font-medium">Slug</th>
                            <th class="px-4 py-3 font-medium">Description</th>
                            <th class="px-4 py-3 font-medium">Articles</th>
                            <th class="px-4 py-3 text-right font-medium">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="category in categories"
                            :key="category.id"
                            class="border-b border-zinc-800 last:border-0 hover:bg-zinc-800/40"
                        >
                            <template v-if="editingId === category.id">
                                <td class="px-4 py-3">
                                    <input
                                        v-model="editForm.name"
                                        type="text"
                                        :class="inputClass"
                                        @keyup.enter="saveEdit(category)"
                                        @keyup.escape="cancelEdit"
                                    />
                                    <InputError
                                        :message="editForm.errors.name"
                                    />
                                </td>
                                <td class="px-4 py-3 text-xs text-zinc-500">
                                    {{ category.slug }}
                                </td>
                                <td class="px-4 py-3">
                                    <input
                                        v-model="editForm.description"
                                        type="text"
                                        :class="inputClass"
                                        @keyup.enter="saveEdit(category)"
                                        @keyup.escape="cancelEdit"
                                    />
                                    <InputError
                                        :message="editForm.errors.description"
                                    />
                                </td>
                                <td class="px-4 py-3 text-zinc-400">
                                    {{ category.articles_count ?? 0 }}
                                </td>
                                <td
                                    class="px-4 py-3 text-right whitespace-nowrap"
                                >
                                    <button
                                        @click="saveEdit(category)"
                                        class="mr-3 text-xs text-green-400 hover:text-green-300"
                                    >
                                        Save
                                    </button>
                                    <button
                                        @click="cancelEdit"
                                        class="text-xs text-zinc-500 hover:text-zinc-300"
                                    >
                                        Cancel
                                    </button>
                                </td>
                            </template>
                            <template v-else>
                                <td class="px-4 py-3 font-medium">
                                    {{ category.name }}
                                </td>
                                <td class="px-4 py-3 text-xs text-zinc-500">
                                    {{ category.slug }}
                                </td>
                                <td class="px-4 py-3 text-zinc-400">
                                    {{ category.description || '—' }}
                                </td>
                                <td class="px-4 py-3 text-zinc-400">
                                    {{ category.articles_count ?? 0 }}
                                </td>
                                <td
                                    class="px-4 py-3 text-right whitespace-nowrap"
                                >
                                    <button
                                        @click="startEdit(category)"
                                        class="mr-3 text-xs text-blue-400 hover:text-blue-300"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        @click="deleteCategory(category)"
                                        class="text-xs text-red-400 hover:text-red-300"
                                    >
                                        Delete
                                    </button>
                                </td>
                            </template>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
