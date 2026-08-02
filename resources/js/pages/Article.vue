<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import ArticleCard from '@/components/ArticleCard.vue';
import SeoHead from '@/components/SeoHead.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { formatDate } from '@/lib/utils';
import type { ArticleItem, SeoData } from '@/types/content';

const props = defineProps<{
    article: ArticleItem;
    bodyHtml: string;
    related: ArticleItem[];
    seo: SeoData;
}>();

// Reading progress bar
const progress = ref(0);

function updateProgress() {
    const doc = document.documentElement;
    const total = doc.scrollHeight - doc.clientHeight;
    progress.value =
        total > 0 ? Math.min(100, (window.scrollY / total) * 100) : 0;
}

onMounted(() => {
    updateProgress();
    window.addEventListener('scroll', updateProgress, { passive: true });
});

onBeforeUnmount(() => window.removeEventListener('scroll', updateProgress));

// Sharing
const canonical = computed(() => props.seo.canonical ?? window.location.href);
const copied = ref(false);

const shareLinks = computed(() => ({
    x: `https://twitter.com/intent/tweet?text=${encodeURIComponent(props.article.title)}&url=${encodeURIComponent(canonical.value)}`,
    facebook: `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(canonical.value)}`,
}));

async function copyLink() {
    try {
        await navigator.clipboard.writeText(canonical.value);
        copied.value = true;
        setTimeout(() => (copied.value = false), 2000);
    } catch {
        window.prompt('Copy this link:', canonical.value);
    }
}
</script>

<template>
    <PublicLayout :active-slug="article.category?.slug">
        <SeoHead :seo="seo" />

        <!-- Reading progress -->
        <div
            class="fixed top-0 left-0 z-50 h-1 bg-sunrise-500 transition-[width] duration-100"
            :style="{ width: progress + '%' }"
            aria-hidden="true"
        ></div>

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
                <figcaption
                    v-if="article.featured_image.credit"
                    class="mt-2 text-xs text-neutral-500"
                >
                    {{ article.featured_image.credit }}
                </figcaption>
            </figure>

            <!-- Share -->
            <div
                class="mb-8 flex flex-wrap items-center gap-2 border-y border-neutral-200 py-3"
            >
                <span
                    class="mr-1 text-xs font-semibold tracking-wide text-neutral-500 uppercase"
                    >Share</span
                >
                <a
                    :href="shareLinks.x"
                    target="_blank"
                    rel="noopener"
                    data-track="share:article-x"
                    class="rounded-full border border-neutral-300 px-3 py-1 text-xs font-medium text-neutral-700 transition hover:border-brand-500 hover:text-brand-700"
                    >&#120143; Post</a
                >
                <a
                    :href="shareLinks.facebook"
                    target="_blank"
                    rel="noopener"
                    data-track="share:article-facebook"
                    class="rounded-full border border-neutral-300 px-3 py-1 text-xs font-medium text-neutral-700 transition hover:border-brand-500 hover:text-brand-700"
                    >Facebook</a
                >
                <button
                    @click="copyLink"
                    data-track="share:article-copy"
                    class="rounded-full border border-neutral-300 px-3 py-1 text-xs font-medium text-neutral-700 transition hover:border-brand-500 hover:text-brand-700"
                >
                    {{ copied ? '✓ Link copied' : 'Copy link' }}
                </button>
            </div>

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
