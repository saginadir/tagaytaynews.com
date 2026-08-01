<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import AdminNav from '@/components/AdminNav.vue';
import type { ArticleItem } from '@/types/content';

const props = defineProps<{
    articles: ArticleItem[];
    adminPath: string;
}>();

function deleteArticle(article: ArticleItem) {
    if (!confirm(`Delete "${article.title}"?`)) return;
    router.delete(`/${props.adminPath}/articles/${article.id}`, {
        preserveScroll: true,
    });
}

function formatDate(dateStr: string | null): string {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}
</script>

<template>
    <div class="min-h-screen bg-zinc-950 p-8 text-zinc-100">
        <div class="mx-auto max-w-7xl">
            <div class="mb-8 flex items-center justify-between">
                <h1 class="text-2xl font-bold">Articles</h1>
                <AdminNav :admin-path="adminPath" current="articles" />
            </div>

            <div class="mb-6">
                <a
                    :href="`/${adminPath}/articles/create`"
                    class="inline-block rounded bg-blue-600 px-4 py-2 text-sm font-medium hover:bg-blue-700"
                >
                    New Article
                </a>
            </div>

            <div
                v-if="articles.length === 0"
                class="py-12 text-center text-zinc-500"
            >
                No articles yet.
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
                            <th class="px-4 py-3 font-medium">Title</th>
                            <th class="px-4 py-3 font-medium">Category</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium">Published</th>
                            <th class="px-4 py-3 font-medium">Created</th>
                            <th class="px-4 py-3 text-right font-medium">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="article in articles"
                            :key="article.id"
                            class="border-b border-zinc-800 last:border-0 hover:bg-zinc-800/40"
                        >
                            <td class="px-4 py-3">
                                <div class="font-medium">
                                    {{ article.title }}
                                </div>
                                <div class="text-xs text-zinc-500">
                                    {{ article.slug }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-zinc-400">
                                {{ article.category?.name ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-block rounded px-2 py-0.5 text-xs font-medium"
                                    :class="
                                        article.status === 'published'
                                            ? 'bg-green-900/60 text-green-300'
                                            : 'bg-zinc-800 text-zinc-400'
                                    "
                                >
                                    {{ article.status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-zinc-400">
                                {{ formatDate(article.published_at) }}
                            </td>
                            <td class="px-4 py-3 text-zinc-400">
                                {{ formatDate(article.created_at) }}
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <a
                                    :href="`/${adminPath}/articles/${article.id}/edit`"
                                    class="mr-3 text-xs text-blue-400 hover:text-blue-300"
                                    >Edit</a
                                >
                                <button
                                    @click="deleteArticle(article)"
                                    class="text-xs text-red-400 hover:text-red-300"
                                >
                                    Delete
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
