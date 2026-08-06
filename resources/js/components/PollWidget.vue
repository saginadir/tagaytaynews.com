<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import type { PollData } from '@/types/content';

const props = defineProps<{
    poll: PollData;
}>();

const voting = ref(false);
const showResults = ref(false);

const voted = computed(() => props.poll.myOptionId !== null);
const reveal = computed(() => voted.value || showResults.value);

function vote(optionId: number) {
    if (voting.value || voted.value) return;
    voting.value = true;
    router.post(
        `/polls/${props.poll.id}/vote`,
        { option_id: optionId },
        {
            preserveScroll: true,
            only: ['poll'],
            onFinish: () => {
                voting.value = false;
            },
        },
    );
}

function percent(votes: number): number {
    return props.poll.totalVotes === 0
        ? 0
        : Math.round((votes / props.poll.totalVotes) * 100);
}
</script>

<template>
    <div
        id="have-your-say"
        class="flex h-full flex-col rounded-xl border border-neutral-200 bg-white p-5"
    >
        <h3
            class="mb-1 text-xs font-semibold tracking-widest text-sunrise-600 uppercase"
        >
            Have your say
        </h3>
        <p
            class="mb-4 font-display text-lg leading-snug font-bold text-brand-950"
        >
            {{ poll.question }}
        </p>

        <div v-if="!reveal" class="mt-auto space-y-2">
            <button
                v-for="option in poll.options"
                :key="option.id"
                :disabled="voting"
                @click="vote(option.id)"
                class="w-full rounded-lg border border-brand-200 px-3 py-2 text-left text-sm font-medium text-brand-800 transition hover:border-brand-500 hover:bg-brand-50 disabled:opacity-50"
            >
                {{ option.label }}
            </button>
            <button
                @click="showResults = true"
                data-track="poll:skip-to-results"
                class="pt-1 text-xs text-neutral-500 hover:text-brand-700"
            >
                Skip to results &rarr;
            </button>
        </div>

        <div v-else class="mt-auto space-y-2.5">
            <div v-for="option in poll.options" :key="option.id">
                <div class="mb-1 flex items-baseline justify-between text-sm">
                    <span
                        :class="
                            option.id === poll.myOptionId
                                ? 'font-semibold text-brand-800'
                                : 'text-neutral-700'
                        "
                    >
                        {{ option.label }}
                        <span
                            v-if="option.id === poll.myOptionId"
                            class="text-xs text-sunrise-600"
                            >&middot; your pick</span
                        >
                    </span>
                    <span class="text-xs font-medium text-neutral-500"
                        >{{ percent(option.votes) }}%</span
                    >
                </div>
                <div class="h-2 overflow-hidden rounded-full bg-neutral-100">
                    <div
                        class="h-full rounded-full transition-all duration-700"
                        :class="
                            option.id === poll.myOptionId
                                ? 'bg-sunrise-500'
                                : 'bg-brand-400'
                        "
                        :style="{ width: percent(option.votes) + '%' }"
                    ></div>
                </div>
            </div>
            <p class="pt-1 text-xs text-neutral-500">
                {{ poll.totalVotes }} vote{{ poll.totalVotes === 1 ? '' : 's' }}
                so far
            </p>
            <a
                v-if="voted"
                :href="`https://twitter.com/intent/tweet?text=${encodeURIComponent('I just voted: ' + poll.question + ' Cast yours on Tagaytay News:')}&url=${encodeURIComponent('https://tagaytaynews.com')}`"
                target="_blank"
                rel="noopener"
                data-track="share:poll-x"
                class="inline-block rounded-full border border-neutral-300 px-3 py-1 text-xs font-medium text-neutral-700 transition hover:border-brand-500 hover:text-brand-700"
            >
                &#120143; Share this poll
            </a>
        </div>
    </div>
</template>
