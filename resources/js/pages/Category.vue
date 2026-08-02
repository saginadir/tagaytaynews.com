<script setup lang="ts">
import ArticleCard from '@/components/ArticleCard.vue';
import SeoHead from '@/components/SeoHead.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import type { ArticleItem, CategoryItem, SeoData } from '@/types/content';

defineProps<{
    category: CategoryItem;
    articles: ArticleItem[];
    fallback: ArticleItem[];
    seo: SeoData;
}>();
</script>

<template>
    <PublicLayout :active-slug="category.slug">
        <SeoHead :seo="seo" />

        <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-10">
            <header class="mb-10 border-b border-neutral-200 pb-8">
                <h1 class="mb-2 font-display text-4xl font-bold text-brand-950">
                    {{ category.name }}
                </h1>
                <p
                    v-if="category.description"
                    class="max-w-2xl text-neutral-600"
                >
                    {{ category.description }}
                </p>
            </header>

            <div v-if="!articles.length">
                <div
                    class="mb-10 rounded-xl bg-brand-50 px-8 py-10 text-center"
                >
                    <p
                        class="mb-2 font-display text-xl font-bold text-brand-950"
                    >
                        Nothing here yet
                    </p>
                    <p class="text-sm text-neutral-600">
                        No published stories in {{ category.name }} yet — but
                        the ridge is never quiet. Here's the latest:
                    </p>
                </div>
                <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-3">
                    <ArticleCard
                        v-for="article in fallback"
                        :key="article.id"
                        :article="article"
                    />
                </div>
            </div>
            <div v-else class="grid gap-10 sm:grid-cols-2 lg:grid-cols-3">
                <ArticleCard
                    v-for="article in articles"
                    :key="article.id"
                    :article="article"
                />
            </div>
        </div>
    </PublicLayout>
</template>
