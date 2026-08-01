<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { computed } from 'vue';
import type { BreadcrumbItem } from '@/types';

interface Stats {
    viewsToday: number;
    views7d: number;
    views30d: number;
    uniqueVisitors7d: number;
    topPages: { path: string; views: number }[];
    topReferrers: { referrer: string; views: number }[];
}

const page = usePage();
const user = computed(() => (page.props.auth as any)?.user);
const stats = computed(() => page.props.stats as Stats);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
];

const cards = computed(() => [
    { label: 'Views today', value: stats.value.viewsToday },
    { label: 'Views (7 days)', value: stats.value.views7d },
    { label: 'Views (30 days)', value: stats.value.views30d },
    { label: 'Unique visitors (7 days)', value: stats.value.uniqueVisitors7d },
]);
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <h2 class="text-xl font-semibold">Welcome back, {{ user?.name }}</h2>

            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div
                    v-for="card in cards"
                    :key="card.label"
                    class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                >
                    <div class="text-2xl font-bold">{{ card.value }}</div>
                    <div class="text-sm text-muted-foreground">
                        {{ card.label }}
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div
                    class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                >
                    <h3 class="mb-3 font-semibold">Top pages (7 days)</h3>
                    <p
                        v-if="stats.topPages.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        No traffic yet.
                    </p>
                    <table v-else class="w-full text-sm">
                        <tbody>
                            <tr
                                v-for="row in stats.topPages"
                                :key="row.path"
                                class="border-b border-sidebar-border/50 last:border-0"
                            >
                                <td class="py-1.5 pr-4 break-all">
                                    {{ row.path }}
                                </td>
                                <td class="py-1.5 text-right tabular-nums">
                                    {{ row.views }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                >
                    <h3 class="mb-3 font-semibold">Top referrers (7 days)</h3>
                    <p
                        v-if="stats.topReferrers.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        No external referrers yet.
                    </p>
                    <table v-else class="w-full text-sm">
                        <tbody>
                            <tr
                                v-for="row in stats.topReferrers"
                                :key="row.referrer"
                                class="border-b border-sidebar-border/50 last:border-0"
                            >
                                <td class="py-1.5 pr-4 break-all">
                                    {{ row.referrer }}
                                </td>
                                <td class="py-1.5 text-right tabular-nums">
                                    {{ row.views }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
