<script setup lang="ts">
import { computed } from 'vue';
import type { RidgeReportData } from '@/types/content';

const props = defineProps<{
    report: RidgeReportData;
}>();

const taalLabels: Record<number, string> = {
    0: 'Quiet',
    1: 'Low-level unrest',
    2: 'Moderate unrest',
    3: 'High unrest',
    4: 'Eruption imminent',
    5: 'Hazardous eruption',
};

const fogCopy = computed(() => {
    if (props.report.fogLevel === 'dense') {
        return { label: 'Dense fog', hint: 'Drive slow on the ridge' };
    }
    if (props.report.fogLevel === 'patches') {
        return { label: 'Fog patches', hint: 'Visibility varies' };
    }
    return { label: 'Clear views', hint: 'Good day for the view deck' };
});

function hhmm(iso: string): string {
    return iso ? iso.slice(11, 16) : '—';
}

const updatedMinutes = computed(() =>
    Math.max(
        0,
        Math.round(
            (Date.now() - new Date(props.report.updatedAt).getTime()) / 60000,
        ),
    ),
);
</script>

<template>
    <div
        class="flex h-full flex-col rounded-xl bg-gradient-to-br from-brand-800 via-brand-900 to-brand-950 p-5 text-brand-50"
    >
        <div class="mb-3 flex items-center justify-between">
            <h3
                class="text-xs font-semibold tracking-widest text-sunrise-400 uppercase"
            >
                Ridge Report
            </h3>
            <span class="flex items-center gap-1.5 text-xs text-brand-300">
                <span class="relative flex h-2 w-2" aria-hidden="true">
                    <span
                        class="absolute inline-flex h-full w-full animate-ping rounded-full bg-green-400 opacity-60"
                    ></span>
                    <span
                        class="relative inline-flex h-2 w-2 rounded-full bg-green-400"
                    ></span>
                </span>
                live
            </span>
        </div>

        <div class="mb-4 flex items-end justify-between gap-2">
            <div>
                <div class="font-display text-5xl font-bold text-white">
                    {{ Math.round(report.temperature) }}&deg;
                </div>
                <div class="mt-1 text-sm text-brand-200">
                    {{ report.weatherLabel }}
                </div>
            </div>
            <div class="text-right text-xs leading-relaxed text-brand-300">
                <div>Wind {{ Math.round(report.windKph) }} km/h</div>
                <div>Humidity {{ report.humidity }}%</div>
                <div>
                    &#9788; {{ hhmm(report.sunrise) }} &ndash;
                    {{ hhmm(report.sunset) }}
                </div>
            </div>
        </div>

        <div class="mt-auto space-y-2">
            <div
                class="flex items-center justify-between rounded-lg bg-white/10 px-3 py-2 text-sm"
            >
                <span class="font-medium">{{ fogCopy.label }}</span>
                <span class="text-xs text-brand-300">{{ fogCopy.hint }}</span>
            </div>
            <div
                class="flex items-center justify-between rounded-lg bg-white/10 px-3 py-2 text-sm"
            >
                <span class="font-medium"
                    >Taal Volcano &middot; Alert {{ report.taalAlert }}</span
                >
                <span class="text-xs text-brand-300">{{
                    taalLabels[report.taalAlert] ?? 'Advisory'
                }}</span>
            </div>
            <p class="pt-1 text-right text-xs text-brand-400">
                updated
                {{ updatedMinutes < 1 ? 'just now' : updatedMinutes + 'm ago' }}
            </p>
        </div>
    </div>
</template>
