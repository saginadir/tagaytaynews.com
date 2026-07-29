<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => (page.props.auth as any)?.user);
const appName = computed(() => (page.props.name as string) || 'Template');
</script>

<template>
    <Head title="Welcome" />
    <div class="flex min-h-screen flex-col items-center justify-center bg-background p-6">
        <div class="flex max-w-lg flex-col items-center gap-6 text-center">
            <h1 class="text-4xl font-bold tracking-tight">Welcome to {{ appName }}</h1>
            <p class="text-lg text-muted-foreground">A Laravel + Vue + Inertia starter kit</p>

            <div class="flex gap-4">
                <template v-if="user">
                    <Link
                        href="/dashboard"
                        class="inline-flex items-center justify-center rounded-md bg-primary px-6 py-2.5 text-sm font-medium text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors"
                    >
                        Dashboard
                    </Link>
                </template>
                <template v-else>
                    <Link
                        href="/login"
                        class="inline-flex items-center justify-center rounded-md bg-primary px-6 py-2.5 text-sm font-medium text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors"
                    >
                        Log in
                    </Link>
                    <Link
                        href="/register"
                        class="inline-flex items-center justify-center rounded-md border border-input bg-background px-6 py-2.5 text-sm font-medium text-foreground shadow-sm hover:bg-accent hover:text-accent-foreground transition-colors"
                    >
                        Register
                    </Link>
                </template>
            </div>
        </div>
    </div>
</template>
