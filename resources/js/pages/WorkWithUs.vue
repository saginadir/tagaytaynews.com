<script setup lang="ts">
import SeoHead from '@/components/SeoHead.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import type { SeoData } from '@/types/content';

defineProps<{
    seo: SeoData;
}>();

const offerings = [
    {
        title: 'Visit invitations & reviews',
        body: 'Restaurants, resorts, hotels, cafés, and attractions in and around Tagaytay can invite our team for a visit. We write honest, first-hand reviews — we always disclose invited visits, and invitations never guarantee a positive write-up.',
    },
    {
        title: 'Advertising',
        body: 'Reach Tagaytay residents and the Metro Manila weekend crowd with display placements and sponsored content. Sponsored material is always clearly labeled and held to the same editorial standards as our news coverage.',
    },
    {
        title: 'Media & content partnerships',
        body: 'We collaborate with tourism offices, event organizers, and fellow publishers on coverage, photography, and cross-promotion of events and destinations on the ridge.',
    },
    {
        title: 'Press releases & announcements',
        body: 'Send business openings, events, and community announcements our way. Newsworthy items are covered at the editor’s discretion — always with attribution.',
    },
];
</script>

<template>
    <PublicLayout>
        <SeoHead :seo="seo" />

        <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 sm:py-10">
            <h1 class="mb-6 font-display text-4xl font-bold text-brand-950">
                Work with Tagaytay News
            </h1>

            <div class="space-y-6 leading-relaxed text-neutral-700">
                <p>
                    <strong class="text-brand-950">Tagaytay News</strong> is the
                    hyperlocal news source for Tagaytay City and the ridge —
                    read by residents, weekend visitors from Metro Manila, and
                    tourists planning their trip. If your business serves this
                    audience, we should talk.
                </p>

                <div
                    v-for="offering in offerings"
                    :key="offering.title"
                    class="border-t border-neutral-200 pt-6"
                >
                    <h2 class="mb-2 font-display text-2xl font-bold text-brand-950">
                        {{ offering.title }}
                    </h2>
                    <p>{{ offering.body }}</p>
                </div>

                <h2 class="font-display text-2xl font-bold text-brand-950">
                    Our promise
                </h2>
                <p>
                    Partnerships never buy our opinion. Invited visits,
                    sponsored content, and advertising are disclosed clearly, and
                    our reviews stay honest — that is what makes coverage in
                    Tagaytay News worth having.
                </p>

                <div class="rounded-lg bg-brand-50 p-6">
                    <h2 class="mb-2 font-display text-2xl font-bold text-brand-950">
                        Get in touch
                    </h2>
                    <p class="mb-4">
                        Tell us about your business and what you have in mind —
                        we reply to every serious inquiry.
                    </p>
                    <a
                        href="mailto:hello@tagaytaynews.com?subject=Partnership%20inquiry"
                        class="inline-block rounded-md bg-brand-700 px-5 py-2.5 font-semibold text-white hover:bg-brand-800"
                    >
                        hello@tagaytaynews.com
                    </a>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
