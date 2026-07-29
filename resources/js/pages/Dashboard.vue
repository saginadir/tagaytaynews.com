<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { computed } from 'vue';
import type { BreadcrumbItem } from '@/types';

const page = usePage();
const user = computed(() => (page.props.auth as any)?.user);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
];
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <h2 class="text-xl font-semibold">Welcome back, {{ user?.name }}</h2>
            <p class="text-muted-foreground">You're logged in. This is your dashboard.</p>
        </div>
    </AppLayout>
</template>
