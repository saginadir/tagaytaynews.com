<script setup lang="ts">
import SeoHead from '@/components/SeoHead.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import type { SeoData } from '@/types/content';

defineProps<{
    seo: SeoData;
}>();
</script>

<template>
    <PublicLayout>
        <SeoHead :seo="seo" />

        <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 sm:py-10">
            <h1 class="mb-6 font-display text-4xl font-bold text-brand-950">
                Contact the newsroom
            </h1>

            <div class="space-y-8">
                <div
                    class="rounded-xl border border-neutral-200 bg-brand-50 p-6 sm:p-8"
                >
                    <p
                        class="mb-2 text-xs font-semibold tracking-widest text-sunrise-600 uppercase"
                    >
                        Email
                    </p>
                    <a
                        href="mailto:hello@tagaytaynews.com"
                        class="font-display text-2xl font-bold text-brand-800 hover:text-brand-600"
                    >
                        hello@tagaytaynews.com
                    </a>
                    <p class="mt-3 text-sm leading-relaxed text-neutral-600">
                        News tips, corrections, photos from the ridge (fog,
                        traffic, Taal), and press releases are all welcome. If a
                        story is time-sensitive, say so in the subject line.
                    </p>
                </div>

                <div class="space-y-3 leading-relaxed text-neutral-700">
                    <h2 class="font-display text-2xl font-bold text-brand-950">
                        For businesses
                    </h2>
                    <p>
                        Restaurants, resorts, hotels, and other Tagaytay-area
                        businesses: we accept review invitations and
                        collaboration proposals. Coverage stays editorially
                        independent, and any sponsored content is always labeled
                        as such.
                    </p>
                </div>

                <div class="space-y-3 leading-relaxed text-neutral-700">
                    <h2 class="font-display text-2xl font-bold text-brand-950">
                        Corrections
                    </h2>
                    <p>
                        Spotted an error in one of our stories? Email us with
                        the article link and what needs fixing — material
                        corrections are made promptly and noted visibly in the
                        article.
                    </p>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
