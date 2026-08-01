<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminNav from '@/components/AdminNav.vue';
import InputError from '@/components/InputError.vue';
import type { SourceItem } from '@/types/content';

const props = defineProps<{
    sources: SourceItem[];
    adminPath: string;
}>();

const tiers = [
    { value: 1, label: 'Tier 1 — Official' },
    { value: 2, label: 'Tier 2 — Established media' },
    { value: 3, label: 'Tier 3 — Other' },
];

function tierLabel(tier: number): string {
    return tiers.find((t) => t.value === tier)?.label ?? `Tier ${tier}`;
}

const addForm = useForm({
    name: '',
    url: '',
    feed_url: '',
    tier: 2,
    is_active: false,
    notes: '',
});
const editingId = ref<number | null>(null);
const editForm = useForm({
    name: '',
    url: '',
    feed_url: '',
    tier: 2,
    is_active: false,
    notes: '',
});

function addSource() {
    addForm.post(`/${props.adminPath}/sources`, {
        preserveScroll: true,
        onSuccess: () => addForm.reset(),
    });
}

function startEdit(source: SourceItem) {
    editingId.value = source.id;
    editForm.name = source.name;
    editForm.url = source.url;
    editForm.feed_url = source.feed_url ?? '';
    editForm.tier = source.tier;
    editForm.is_active = source.is_active;
    editForm.notes = source.notes ?? '';
    editForm.clearErrors();
}

function saveEdit(source: SourceItem) {
    editForm.put(`/${props.adminPath}/sources/${source.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            editingId.value = null;
        },
    });
}

function cancelEdit() {
    editingId.value = null;
}

function deleteSource(source: SourceItem) {
    if (!confirm(`Delete source "${source.name}"?`)) return;
    router.delete(`/${props.adminPath}/sources/${source.id}`, {
        preserveScroll: true,
    });
}

const inputClass =
    'w-full rounded-md border border-zinc-700 bg-zinc-800 px-3 py-2 text-sm text-white placeholder-zinc-600 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500';
const labelClass = 'block text-xs text-zinc-500 mb-1';
</script>

<template>
    <div class="min-h-screen bg-zinc-950 p-8 text-zinc-100">
        <div class="mx-auto max-w-6xl">
            <div class="mb-8 flex items-center justify-between">
                <h1 class="text-2xl font-bold">Sources</h1>
                <AdminNav :admin-path="adminPath" current="sources" />
            </div>

            <!-- Add form -->
            <form
                @submit.prevent="addSource"
                class="mb-8 rounded-lg border border-zinc-800 bg-zinc-900 p-6"
            >
                <div
                    class="grid grid-cols-1 items-start gap-4 md:grid-cols-2 lg:grid-cols-3"
                >
                    <div>
                        <label :class="labelClass">Name</label>
                        <input
                            v-model="addForm.name"
                            type="text"
                            :class="inputClass"
                            placeholder="e.g. PHIVOLCS"
                        />
                        <InputError :message="addForm.errors.name" />
                    </div>
                    <div>
                        <label :class="labelClass">URL</label>
                        <input
                            v-model="addForm.url"
                            type="url"
                            :class="inputClass"
                            placeholder="https://..."
                        />
                        <InputError :message="addForm.errors.url" />
                    </div>
                    <div>
                        <label :class="labelClass"
                            >RSS feed URL (optional)</label
                        >
                        <input
                            v-model="addForm.feed_url"
                            type="url"
                            :class="inputClass"
                            placeholder="https://.../feed"
                        />
                        <InputError :message="addForm.errors.feed_url" />
                    </div>
                    <div>
                        <label :class="labelClass">Tier</label>
                        <select v-model="addForm.tier" :class="inputClass">
                            <option
                                v-for="tier in tiers"
                                :key="tier.value"
                                :value="tier.value"
                            >
                                {{ tier.label }}
                            </option>
                        </select>
                        <InputError :message="addForm.errors.tier" />
                    </div>
                    <div>
                        <label :class="labelClass">Notes (optional)</label>
                        <input
                            v-model="addForm.notes"
                            type="text"
                            :class="inputClass"
                        />
                        <InputError :message="addForm.errors.notes" />
                    </div>
                    <div class="flex items-end gap-4">
                        <label
                            class="flex items-center gap-2 pb-2 text-xs text-zinc-400"
                        >
                            <input
                                v-model="addForm.is_active"
                                type="checkbox"
                                class="rounded border-zinc-600 bg-zinc-800"
                            />
                            Poll feed
                        </label>
                        <button
                            type="submit"
                            :disabled="addForm.processing"
                            class="rounded bg-blue-600 px-4 py-2 text-sm font-medium hover:bg-blue-700 disabled:opacity-50"
                        >
                            Add Source
                        </button>
                    </div>
                </div>
            </form>

            <!-- Table -->
            <div
                v-if="sources.length === 0"
                class="py-12 text-center text-zinc-500"
            >
                No sources yet.
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
                            <th class="px-4 py-3 font-medium">URL</th>
                            <th class="px-4 py-3 font-medium">Tier</th>
                            <th class="px-4 py-3 font-medium">Feed</th>
                            <th class="px-4 py-3 font-medium">Notes</th>
                            <th class="px-4 py-3 text-right font-medium">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="source in sources"
                            :key="source.id"
                            class="border-b border-zinc-800 last:border-0 hover:bg-zinc-800/40"
                        >
                            <template v-if="editingId === source.id">
                                <td class="px-4 py-3">
                                    <input
                                        v-model="editForm.name"
                                        type="text"
                                        :class="inputClass"
                                        @keyup.enter="saveEdit(source)"
                                        @keyup.escape="cancelEdit"
                                    />
                                    <InputError
                                        :message="editForm.errors.name"
                                    />
                                </td>
                                <td class="px-4 py-3">
                                    <input
                                        v-model="editForm.url"
                                        type="url"
                                        :class="inputClass"
                                        @keyup.enter="saveEdit(source)"
                                        @keyup.escape="cancelEdit"
                                    />
                                    <InputError
                                        :message="editForm.errors.url"
                                    />
                                </td>
                                <td class="px-4 py-3">
                                    <select
                                        v-model="editForm.tier"
                                        :class="inputClass"
                                    >
                                        <option
                                            v-for="tier in tiers"
                                            :key="tier.value"
                                            :value="tier.value"
                                        >
                                            {{ tier.label }}
                                        </option>
                                    </select>
                                    <InputError
                                        :message="editForm.errors.tier"
                                    />
                                </td>
                                <td class="px-4 py-3">
                                    <input
                                        v-model="editForm.feed_url"
                                        type="url"
                                        :class="inputClass"
                                        placeholder="RSS feed URL"
                                        @keyup.enter="saveEdit(source)"
                                        @keyup.escape="cancelEdit"
                                    />
                                    <InputError
                                        :message="editForm.errors.feed_url"
                                    />
                                    <label
                                        class="mt-2 flex items-center gap-2 text-xs text-zinc-400"
                                    >
                                        <input
                                            v-model="editForm.is_active"
                                            type="checkbox"
                                            class="rounded border-zinc-600 bg-zinc-800"
                                        />
                                        Poll feed
                                    </label>
                                </td>
                                <td class="px-4 py-3">
                                    <input
                                        v-model="editForm.notes"
                                        type="text"
                                        :class="inputClass"
                                        @keyup.enter="saveEdit(source)"
                                        @keyup.escape="cancelEdit"
                                    />
                                    <InputError
                                        :message="editForm.errors.notes"
                                    />
                                </td>
                                <td
                                    class="px-4 py-3 text-right whitespace-nowrap"
                                >
                                    <button
                                        @click="saveEdit(source)"
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
                                    {{ source.name }}
                                </td>
                                <td class="px-4 py-3">
                                    <a
                                        :href="source.url"
                                        target="_blank"
                                        rel="noopener"
                                        class="text-xs break-all text-blue-400 hover:text-blue-300"
                                    >
                                        {{ source.url }}
                                    </a>
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-block rounded px-2 py-0.5 text-xs font-medium"
                                        :class="
                                            source.tier === 1
                                                ? 'bg-green-900/60 text-green-300'
                                                : source.tier === 2
                                                  ? 'bg-blue-900/60 text-blue-300'
                                                  : 'bg-zinc-800 text-zinc-400'
                                        "
                                    >
                                        {{ tierLabel(source.tier) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <template v-if="source.feed_url">
                                        <span
                                            class="inline-block rounded px-2 py-0.5 text-xs font-medium"
                                            :class="
                                                source.is_active
                                                    ? 'bg-green-900/60 text-green-300'
                                                    : 'bg-zinc-800 text-zinc-400'
                                            "
                                        >
                                            {{
                                                source.is_active
                                                    ? 'polling'
                                                    : 'paused'
                                            }}
                                        </span>
                                        <div
                                            v-if="source.last_fetched_at"
                                            class="mt-1 text-xs text-zinc-600"
                                        >
                                            {{ source.last_fetched_at }}
                                        </div>
                                    </template>
                                    <span v-else class="text-xs text-zinc-600"
                                        >manual</span
                                    >
                                </td>
                                <td class="px-4 py-3 text-zinc-400">
                                    {{ source.notes || '—' }}
                                </td>
                                <td
                                    class="px-4 py-3 text-right whitespace-nowrap"
                                >
                                    <button
                                        @click="startEdit(source)"
                                        class="mr-3 text-xs text-blue-400 hover:text-blue-300"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        @click="deleteSource(source)"
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
