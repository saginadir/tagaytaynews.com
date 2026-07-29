<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
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
        <header class="border-b border-zinc-200 bg-white sticky top-0 z-50">
            <div class="mx-auto max-w-7xl px-4 sm:px-6">
                <div class="flex items-center justify-between h-16">
                    <Link href="/" class="text-xl font-bold tracking-tight text-zinc-900">
                        {{ appName }}
                    </Link>

                    <nav class="flex items-center gap-1">
                        <Link
                            href="/"
                            class="px-3 py-2 text-sm font-medium text-zinc-600 hover:text-zinc-900 rounded-md hover:bg-zinc-100 transition-colors"
                        >
                            Home
                        </Link>
                        <template v-if="user">
                            <Link
                                href="/dashboard"
                                class="px-3 py-2 text-sm font-medium text-zinc-600 hover:text-zinc-900 rounded-md hover:bg-zinc-100 transition-colors"
                            >
                                Dashboard
                            </Link>
                        </template>
                        <template v-else>
                            <Link
                                href="/login"
                                class="px-3 py-2 text-sm font-medium text-zinc-600 hover:text-zinc-900 rounded-md hover:bg-zinc-100 transition-colors"
                            >
                                Log in
                            </Link>
                            <Link
                                href="/register"
                                class="px-3 py-2 text-sm font-medium text-zinc-600 hover:text-zinc-900 rounded-md hover:bg-zinc-100 transition-colors"
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
        <footer class="border-t border-zinc-200 bg-zinc-50 mt-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 py-8">
                <p class="text-xs text-zinc-400 text-center">&copy; {{ new Date().getFullYear() }} {{ appName }}. All rights reserved.</p>
            </div>
        </footer>
    </div>
</template>
