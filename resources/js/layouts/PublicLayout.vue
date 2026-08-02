<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import type { NavCategory } from '@/types/content';

defineProps<{
    activeSlug?: string;
}>();

const page = usePage();
const navCategories = computed(() => page.props.navCategories as NavCategory[]);

const today = new Date().toLocaleDateString('en-PH', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
});
const year = new Date().getFullYear();
</script>

<template>
    <div class="flex min-h-screen flex-col bg-white text-neutral-900">
        <!-- Top bar -->
        <div class="bg-brand-900 text-brand-50">
            <div
                class="mx-auto flex max-w-6xl items-center justify-between px-4 py-1.5 text-xs sm:px-6"
            >
                <p class="font-medium tracking-wide">
                    News from the Ridge — Tagaytay City
                </p>
                <p class="hidden text-brand-200 sm:block">{{ today }}</p>
            </div>
        </div>

        <!-- Masthead -->
        <header class="border-b border-neutral-200">
            <div class="mx-auto max-w-6xl px-4 sm:px-6">
                <div class="py-5">
                    <Link href="/" class="inline-flex items-center gap-3">
                        <AppLogoIcon class="h-11 w-11 rounded-lg" />
                        <span
                            class="font-display text-2xl font-bold tracking-tight text-brand-950 sm:text-3xl"
                        >
                            TAGAYTAY <span class="text-brand-700">NEWS</span>
                        </span>
                    </Link>
                </div>
                <nav
                    class="-mb-px flex gap-6 overflow-x-auto text-sm font-medium whitespace-nowrap"
                >
                    <Link
                        href="/"
                        class="border-b-2 py-2.5"
                        :class="
                            !activeSlug
                                ? 'border-sunrise-500 text-brand-950'
                                : 'border-transparent text-neutral-500 hover:border-neutral-300 hover:text-brand-950'
                        "
                    >
                        Home
                    </Link>
                    <Link
                        v-for="category in navCategories"
                        :key="category.slug"
                        :href="`/${category.slug}`"
                        class="border-b-2 py-2.5"
                        :class="
                            activeSlug === category.slug
                                ? 'border-sunrise-500 text-brand-950'
                                : 'border-transparent text-neutral-500 hover:border-neutral-300 hover:text-brand-950'
                        "
                    >
                        {{ category.name }}
                    </Link>
                </nav>
            </div>
        </header>

        <!-- Main content -->
        <main class="flex-1">
            <slot />
        </main>

        <!-- Footer -->
        <footer class="mt-16 bg-brand-950 text-brand-100">
            <div
                class="mx-auto grid max-w-6xl gap-10 px-4 py-12 sm:px-6 md:grid-cols-3"
            >
                <div>
                    <div class="mb-3 flex items-center gap-2">
                        <AppLogoIcon class="h-8 w-8 rounded" />
                        <span class="font-display text-lg font-bold text-white"
                            >TAGAYTAY NEWS</span
                        >
                    </div>
                    <p class="text-sm leading-relaxed text-brand-200">
                        The go-to digital news outlet for Tagaytay City and the
                        ridge — breaking news, weather and fog advisories, Taal
                        Volcano updates, traffic, tourism, and more.
                    </p>
                </div>
                <div>
                    <h3
                        class="mb-3 text-xs font-semibold tracking-wider text-sunrise-400 uppercase"
                    >
                        Sections
                    </h3>
                    <ul class="space-y-2 text-sm">
                        <li
                            v-for="category in navCategories"
                            :key="category.slug"
                        >
                            <Link
                                :href="`/${category.slug}`"
                                class="hover:text-white"
                            >
                                {{ category.name }}
                            </Link>
                        </li>
                    </ul>
                </div>
                <div>
                    <h3
                        class="mb-3 text-xs font-semibold tracking-wider text-sunrise-400 uppercase"
                    >
                        About us
                    </h3>
                    <ul class="space-y-2 text-sm">
                        <li>
                            <Link href="/about" class="hover:text-white"
                                >About Tagaytay News</Link
                            >
                        </li>
                        <li>
                            <Link href="/contact" class="hover:text-white"
                                >Contact the newsroom</Link
                            >
                        </li>
                        <li>
                            <Link href="/work-with-us" class="hover:text-white"
                                >Work with us</Link
                            >
                        </li>
                        <li>
                            <Link href="/quiz" class="hover:text-white"
                                >Quiz: How Tagaytay are you?</Link
                            >
                        </li>
                        <li>
                            <Link href="/map" class="hover:text-white"
                                >Explore the ridge map</Link
                            >
                        </li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-brand-800">
                <p
                    class="mx-auto max-w-6xl px-4 py-4 text-xs text-brand-300 sm:px-6"
                >
                    &copy; {{ year }} Tagaytay News &middot; Tagaytay City,
                    Philippines
                </p>
            </div>
        </footer>
    </div>
</template>
