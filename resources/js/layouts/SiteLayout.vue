<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps<{
    title?: string;
    description?: string;
}>();

const page = usePage();
const appName = computed(() => (page.props.name as string) || 'Template');
const user = computed(() => (page.props.auth as any)?.user);
</script>

<template>
    <Head>
        <title>{{ title ? `${title} — ${appName}` : appName }}</title>
        <meta v-if="description" name="description" :content="description" />
    </Head>

    <div class="min-h-screen bg-white text-zinc-900">
        <!-- Header -->
        <header class="sticky top-0 z-50 border-b border-zinc-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 sm:px-6">
                <div class="flex h-16 items-center justify-between">
                    <Link
                        href="/"
                        class="text-xl font-bold tracking-tight text-zinc-900"
                    >
                        {{ appName }}
                    </Link>

                    <nav class="flex items-center gap-1">
                        <Link
                            href="/"
                            class="rounded-md px-3 py-2 text-sm font-medium text-zinc-600 transition-colors hover:bg-zinc-100 hover:text-zinc-900"
                        >
                            Home
                        </Link>
                        <template v-if="user">
                            <Link
                                href="/dashboard"
                                class="rounded-md px-3 py-2 text-sm font-medium text-zinc-600 transition-colors hover:bg-zinc-100 hover:text-zinc-900"
                            >
                                Dashboard
                            </Link>
                        </template>
                        <template v-else>
                            <Link
                                href="/login"
                                class="rounded-md px-3 py-2 text-sm font-medium text-zinc-600 transition-colors hover:bg-zinc-100 hover:text-zinc-900"
                            >
                                Log in
                            </Link>
                            <Link
                                href="/register"
                                class="rounded-md px-3 py-2 text-sm font-medium text-zinc-600 transition-colors hover:bg-zinc-100 hover:text-zinc-900"
                            >
                                Register
                            </Link>
                        </template>
                    </nav>
                </div>
            </div>
        </header>

        <!-- Main content -->
        <main>
            <slot />
        </main>

        <!-- Footer -->
        <footer class="mt-16 border-t border-zinc-200 bg-zinc-50">
            <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
                <p class="text-center text-xs text-zinc-400">
                    &copy; {{ new Date().getFullYear() }} {{ appName }}. All
                    rights reserved.
                </p>
            </div>
        </footer>
    </div>
</template>
