<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import type { SeoData } from '@/types/content';

defineProps<{
    seo: SeoData;
}>();
</script>

<template>
    <!-- Server-side equivalent lives in resources/views/app.blade.php (initial HTML
         for crawlers); this keeps meta correct on client-side navigations. -->
    <Head>
        <title>{{ seo.title }}</title>
        <meta
            v-if="seo.description"
            name="description"
            :content="seo.description"
        />
        <link v-if="seo.canonical" rel="canonical" :href="seo.canonical" />
        <meta property="og:title" :content="seo.title" />
        <meta
            v-if="seo.description"
            property="og:description"
            :content="seo.description"
        />
        <meta property="og:type" :content="seo.ogType" />
        <meta v-if="seo.canonical" property="og:url" :content="seo.canonical" />
        <meta property="og:image" :content="seo.ogImage" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="seo.title" />
        <meta
            v-if="seo.description"
            name="twitter:description"
            :content="seo.description"
        />
        <meta name="twitter:image" :content="seo.ogImage" />
    </Head>
</template>
