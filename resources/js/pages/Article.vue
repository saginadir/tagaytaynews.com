<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import ArticleCard from '@/components/ArticleCard.vue';
import SeoHead from '@/components/SeoHead.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { formatDate } from '@/lib/utils';
import type { ArticleItem, SeoData } from '@/types/content';

defineProps<{
    article: ArticleItem;
    bodyHtml: string;
    related: ArticleItem[];
    seo: SeoData;
}>();
</script>

<template>
    <PublicLayout :active-slug="article.category?.slug">
        <SeoHead :seo="seo" />

        <article class="mx-auto max-w-3xl px-4 py-8 sm:px-6 sm:py-10">
            <!-- Breadcrumb -->
            <nav
                class="mb-6 flex items-center gap-1.5 text-xs text-neutral-500"
            >
                <Link href="/" class="shrink-0 hover:text-brand-700">Home</Link>
                <template v-if="article.category">
                    <span>/</span>
                    <Link
                        :href="`/${article.category.slug}`"
                        class="shrink-0 hover:text-brand-700"
                    >
                        {{ article.category.name }}
                    </Link>
                    <span>/</span>
                </template>
                <span class="truncate text-neutral-400">{{
                    article.title
                }}</span>
            </nav>

            <p
                v-if="article.category"
                class="mb-3 text-xs font-semibold tracking-widest text-sunrise-600 uppercase"
            >
                {{ article.category.name }}
            </p>
            <h1
                class="mb-4 font-display text-3xl leading-tight font-bold text-brand-950 sm:text-4xl"
            >
                {{ article.title }}
            </h1>

            <!-- Dateline -->
            <div
                class="mb-8 flex flex-wrap items-center gap-x-3 gap-y-2 text-sm text-neutral-500"
            >
                <span class="font-medium text-neutral-700">{{
                    article.author
                }}</span>
                <span aria-hidden="true">&middot;</span>
                <time :datetime="article.published_at ?? ''">
                    {{ formatDate(article.published_at) }}
                </time>
                <template v-if="article.category">
                    <span aria-hidden="true">&middot;</span>
                    <Link
                        :href="`/${article.category.slug}`"
                        class="rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-700 hover:bg-brand-100"
                    >
                        {{ article.category.name }}
                    </Link>
                </template>
            </div>

            <figure v-if="article.featured_image" class="mb-8">
                <img
                    :src="article.featured_image.url"
                    :alt="article.featured_image.alt || article.title"
                    class="w-full rounded-xl object-cover"
                />
            </figure>

            <!-- Body (markdown rendered server-side) -->
            <div
                class="prose max-w-none prose-neutral lg:prose-lg"
                v-html="bodyHtml"
            ></div>

            <!-- Source attribution -->
            <aside
                v-if="article.source || article.source_url"
                class="mt-10 rounded-lg border-l-4 border-sunrise-400 bg-brand-50 p-4 text-sm text-neutral-700"
            >
                <template v-if="article.source && article.source_url">
                    Source:
                    <a
                        :href="article.source_url"
                        target="_blank"
                        rel="nofollow noopener"
                        class="font-semibold text-brand-700 hover:underline"
                        >{{ article.source.name }}</a
                    >
                </template>
                <template v-else-if="article.source">
                    Source: {{ article.source.name }}
                </template>
                <template v-else>
                    Source:
                    <a
                        :href="article.source_url ?? '#'"
                        target="_blank"
                        rel="nofollow noopener"
                        class="font-semibold break-all text-brand-700 hover:underline"
                        >{{ article.source_url }}</a
                    >
                </template>
            </aside>
        </article>

        <!-- Related -->
        <section
            v-if="related.length"
            class="mx-auto max-w-6xl border-t border-neutral-200 px-4 py-10 sm:px-6"
        >
            <h2
                class="mb-6 flex items-center gap-3 font-display text-2xl font-bold text-brand-950"
            >
                <span class="h-5 w-1 rounded-full bg-sunrise-500"></span>
                More {{ article.category?.name }}
            </h2>
            <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                <ArticleCard
                    v-for="item in related"
                    :key="item.id"
                    :article="item"
                />
            </div>
        </section>
    </PublicLayout>
</template>
