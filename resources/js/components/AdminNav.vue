<script setup lang="ts">
defineProps<{
    adminPath: string;
    current: 'articles' | 'categories' | 'sources' | 'media' | 'polls';
}>();

const links = [
    { key: 'articles', label: 'Articles', path: 'articles' },
    { key: 'categories', label: 'Categories', path: 'categories' },
    { key: 'sources', label: 'Sources', path: 'sources' },
    { key: 'media', label: 'Media', path: 'media' },
    { key: 'polls', label: 'Polls & Alerts', path: 'polls' },
] as const;
</script>

<template>
    <nav class="flex gap-4 text-sm">
        <a
            v-for="link in links"
            :key="link.key"
            :href="`/${adminPath}/${link.path}`"
            :class="
                current === link.key
                    ? 'font-medium text-white'
                    : 'text-zinc-400 hover:text-white'
            "
        >
            {{ link.label }}
        </a>
    </nav>
</template>
