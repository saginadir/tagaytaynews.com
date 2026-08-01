<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { formatDate } from '@/lib/utils';
import type { ArticleItem } from '@/types/content';

defineProps<{
    article: ArticleItem;
    horizontal?: boolean;
}>();
</script>

<template>
    <Link
        :href="`/${article.category?.slug}/${article.slug}`"
        class="group flex"
        :class="horizontal ? 'items-start gap-4' : 'flex-col'"
    >
        <div
            v-if="article.featured_image"
            class="shrink-0 overflow-hidden rounded-lg bg-brand-50"
            :class="horizontal ? 'w-28' : 'aspect-video w-full'"
        >
            <img
                :src="article.featured_image.url"
                :alt="article.featured_image.alt || article.title"
                class="h-full w-full object-cover transition group-hover:opacity-90"
                :class="horizontal ? 'aspect-square' : ''"
                loading="lazy"
            />
        </div>
        <div :class="horizontal ? '' : 'pt-3'">
            <p
                class="mb-1 text-xs font-semibold tracking-wide text-sunrise-600 uppercase"
            >
                {{ article.category?.name }}
            </p>
            <h3
                class="font-display leading-snug font-bold text-brand-950 group-hover:text-brand-700"
                :class="horizontal ? 'text-base' : 'text-lg'"
            >
                {{ article.title }}
            </h3>
            <p class="mt-1 text-xs text-neutral-500">
                {{ formatDate(article.published_at) }}
            </p>
        </div>
    </Link>
</template>
