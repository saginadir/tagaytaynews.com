<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import SeoHead from '@/components/SeoHead.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import type { SeoData } from '@/types/content';

defineProps<{
    seo: SeoData;
}>();

const coverage = [
    'Breaking news and public safety',
    'Weather and fog advisories',
    'Taal Volcano updates (PHIVOLCS)',
    'Traffic on the ridge and the main access roads',
    'Tourism, restaurants, resorts, and hotels',
    'Events, local government, business, and real estate',
];
</script>

<template>
    <PublicLayout>
        <SeoHead :seo="seo" />

        <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 sm:py-10">
            <h1 class="mb-6 font-display text-4xl font-bold text-brand-950">
                About Tagaytay News
            </h1>

            <div class="space-y-6 leading-relaxed text-neutral-700">
                <p>
                    <strong class="text-brand-950">Tagaytay News</strong> is the
                    go-to digital news outlet for
                    <strong class="text-brand-950">Tagaytay City</strong> and
                    the Tagaytay Ridge area — the Cavite–Batangas boundary
                    including Silang, Alfonso, Mendez, Amadeo, Laurel, and
                    nearby communities as relevant. We serve Tagaytay residents,
                    Metro Manila weekend visitors, local businesses, and
                    prospective tourists.
                </p>

                <h2 class="font-display text-2xl font-bold text-brand-950">
                    What we cover
                </h2>
                <ul class="list-disc space-y-1.5 pl-6">
                    <li v-for="item in coverage" :key="item">{{ item }}</li>
                </ul>

                <h2 class="font-display text-2xl font-bold text-brand-950">
                    Editorial standards
                </h2>
                <p>Every story follows the same rules:</p>
                <ul class="list-disc space-y-1.5 pl-6">
                    <li>
                        <strong>Tiered sourcing.</strong> Tier 1 official
                        sources (city and provincial government, PAGASA,
                        PHIVOLCS, PNP, DOH, DPWH), Tier 2 established media, and
                        Tier 3 everything else. We never publish from Tier 3
                        alone — it takes at least two independent sources, or
                        one Tier 1/2 source, to confirm.
                    </li>
                    <li>
                        <strong>Attribution.</strong> Sources are always named
                        and linked, and stories are rewritten in our own words —
                        no copy-paste journalism.
                    </li>
                    <li>
                        <strong>Accuracy over speed.</strong> Unconfirmed
                        breaking items are labeled <em>developing</em>, and
                        unverified facts are flagged as such in the article.
                    </li>
                    <li>
                        <strong>Corrections.</strong> When we get something
                        materially wrong, we fix the article and add a visible
                        correction note.
                    </li>
                </ul>

                <h2 class="font-display text-2xl font-bold text-brand-950">
                    Transparency
                </h2>
                <p>
                    Our newsroom is
                    <strong>AI-assisted with human oversight</strong>: software
                    agents help us monitor sources, draft stories, and keep the
                    site fast and accurate, while a human editor owns final
                    publishing decisions, sensitive stories, and corrections.
                </p>

                <h2 class="font-display text-2xl font-bold text-brand-950">
                    Privacy &amp; analytics
                </h2>
                <p>
                    We measure readership to make the site better, with as
                    little data as possible: our own first-party analytics use
                    no cookies and store no IP addresses (only a daily-rotating
                    salted hash). We also use
                    <strong>Microsoft Clarity</strong> to see aggregated
                    heatmaps and anonymized session replays — it masks IPs and
                    form fields by default. No personal data is sold or shared
                    with advertisers.
                </p>

                <h2 class="font-display text-2xl font-bold text-brand-950">
                    Get in touch
                </h2>
                <p>
                    News tips, corrections, and business inquiries are welcome —
                    see our
                    <Link
                        href="/contact"
                        class="font-semibold text-brand-700 hover:underline"
                    >
                        contact page </Link
                    >.
                </p>
            </div>
        </div>
    </PublicLayout>
</template>
