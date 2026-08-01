<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <Head :title="title" />
    <div
        class="flex min-h-screen flex-col items-center justify-center gap-6 bg-background p-6 md:p-10"
    >
        <div class="flex w-full max-w-sm flex-col gap-6">
            <a href="/" class="flex items-center gap-2 self-center font-medium">
                <div
                    class="flex h-9 w-9 items-center justify-center rounded-md bg-primary text-primary-foreground"
                >
                    <AppLogoIcon class="size-5 fill-current" />
                </div>
            </a>
            <div class="flex flex-col gap-2 text-center">
                <h1 v-if="title" class="text-xl font-bold tracking-tight">
                    {{ title }}
                </h1>
                <p
                    v-if="description"
                    class="text-sm text-balance text-muted-foreground"
                >
                    {{ description }}
                </p>
            </div>
            <slot />
        </div>
    </div>
</template>
