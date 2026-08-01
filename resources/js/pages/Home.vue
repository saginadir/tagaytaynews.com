<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import ArticleCard from '@/components/ArticleCard.vue';
import SeoHead from '@/components/SeoHead.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { formatDate } from '@/lib/utils';
import type { ArticleItem, CategoryItem, SeoData } from '@/types/content';

interface CategorySection extends CategoryItem {
    articles: ArticleItem[];
}

defineProps<{
    hero: ArticleItem | null;
    latest: ArticleItem[];
    sections: CategorySection[];
    seo: SeoData;
}>();
</script>

<template>
    <PublicLayout>
        <SeoHead :seo="seo" />

        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <!-- Empty state -->
            <section v-if="!hero" class="py-24">
                <div
                    class="mx-auto max-w-md rounded-xl bg-brand-50 px-8 py-14 text-center"
                >
                    <p
                        class="mb-3 text-xs font-semibold tracking-widest text-sunrise-600 uppercase"
                    >
                        Tagaytay News
                    </p>
                    <h1
                        class="mb-3 font-display text-3xl font-bold text-brand-950"
                    >
                        The newsroom is warming up&hellip;
                    </h1>
                    <p class="text-sm leading-relaxed text-neutral-600">
                        We're preparing the first stories from the ridge —
                        weather, traffic, Taal Volcano, and everything Tagaytay.
                        Check back soon.
                    </p>
                </div>
            </section>

            <template v-else>
                <!-- Hero -->
                <section class="py-8 sm:py-10">
                    <Link
                        :href="`/${hero.category?.slug}/${hero.slug}`"
                        class="group grid gap-6 lg:grid-cols-2 lg:items-center"
                    >
                        <div
                            v-if="hero.featured_image"
                            class="overflow-hidden rounded-xl"
                        >
                            <img
                                :src="hero.featured_image.url"
                                :alt="hero.featured_image.alt || hero.title"
                                class="aspect-video w-full object-cover transition group-hover:opacity-95"
                            />
                        </div>
                        <div>
                            <p
                                class="mb-2 text-xs font-semibold tracking-widest text-sunrise-600 uppercase"
                            >
                                {{ hero.category?.name }}
                            </p>
                            <h1
                                class="mb-3 font-display text-3xl leading-tight font-bold text-brand-950 group-hover:text-brand-700 sm:text-4xl"
                            >
                                {{ hero.title }}
                            </h1>
                            <p
                                v-if="hero.excerpt"
                                class="mb-4 leading-relaxed text-neutral-600"
                            >
                                {{ hero.excerpt }}
                            </p>
                            <p class="text-xs text-neutral-500">
                                {{ hero.author }} &middot;
                                {{ formatDate(hero.published_at) }}
                            </p>
                        </div>
                    </Link>
                </section>

                <!-- Latest -->
                <section
                    v-if="latest.length"
                    class="border-t border-neutral-200 py-10"
                >
                    <h2
                        class="mb-6 flex items-center gap-3 font-display text-2xl font-bold text-brand-950"
                    >
                        <span
                            class="h-5 w-1 rounded-full bg-sunrise-500"
                        ></span>
                        The Latest
                    </h2>
                    <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                        <ArticleCard
                            v-for="article in latest"
                            :key="article.id"
                            :article="article"
                        />
                    </div>
                </section>

                <!-- Category sections -->
                <section
                    v-for="section in sections"
                    :key="section.id"
                    class="border-t border-neutral-200 py-10"
                >
                    <div class="mb-6 flex items-baseline justify-between gap-4">
                        <h2
                            class="flex items-center gap-3 font-display text-2xl font-bold text-brand-950"
                        >
                            <span
                                class="h-5 w-1 rounded-full bg-sunrise-500"
                            ></span>
                            {{ section.name }}
                        </h2>
                        <Link
                            :href="`/${section.slug}`"
                            class="shrink-0 text-sm font-medium text-brand-700 hover:text-brand-600"
                        >
                            More {{ section.name }} &rarr;
                        </Link>
                    </div>
                    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        <ArticleCard
                            v-for="article in section.articles"
                            :key="article.id"
                            :article="article"
                            horizontal
                        />
                    </div>
                </section>
            </template>
        </div>
    </PublicLayout>
</template>
