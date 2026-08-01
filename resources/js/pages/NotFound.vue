<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/layouts/PublicLayout.vue';
</script>

<template>
    <PublicLayout>
        <Head>
            <title>Page not found — Tagaytay News</title>
        </Head>

        <div class="mx-auto max-w-md px-4 py-24 text-center sm:px-6">
            <p
                class="mb-3 text-xs font-semibold tracking-widest text-sunrise-600 uppercase"
            >
                404
            </p>
            <h1 class="mb-3 font-display text-3xl font-bold text-brand-950">
                Page not found
            </h1>
            <p class="mb-8 text-sm leading-relaxed text-neutral-600">
                The story you were looking for isn't here — it may have been
                moved, corrected, or never published.
            </p>
            <Link
                href="/"
                class="inline-block rounded-md bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-600"
            >
                Back to the front page
            </Link>
        </div>
    </PublicLayout>
</template>
