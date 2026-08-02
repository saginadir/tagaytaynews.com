<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import SeoHead from '@/components/SeoHead.vue';
import { quizQuestions } from '@/data/quizQuestions';
import PublicLayout from '@/layouts/PublicLayout.vue';
import type { SeoData } from '@/types/content';

const props = defineProps<{
    seo: SeoData;
}>();

type Stage = 'intro' | 'playing' | 'finished';

interface ScoreTier {
    min: number;
    name: string;
    description: string;
}

const tiers: ScoreTier[] = [
    {
        min: 10,
        name: 'Honorary Tagaytayeño',
        description:
            'A perfect score! You know the ridge like a true local — from the Palace in the Sky to the last slice of buko pie. Someone get this person a bowl of bulalo.',
    },
    {
        min: 7,
        name: 'Ridge Local',
        description:
            'Fog? Weekend traffic on the ridge? You take it all in stride. Tagaytay is basically your backyard.',
    },
    {
        min: 4,
        name: 'Regular Climber',
        description:
            'You know your way up the ridge, and the bulalo stalls know your face. A few more foggy drives and you’ll pass for a local.',
    },
    {
        min: 0,
        name: 'Weekend Tourist',
        description:
            'You came for the view and the bulalo — nothing wrong with that. One more weekend on the ridge and you’ll be navigating the fog like a pro.',
    },
];

const total = quizQuestions.length;

const stage = ref<Stage>('intro');
const currentIndex = ref(0);
const score = ref(0);
const selectedIndex = ref<number | null>(null);

const currentQuestion = computed(() => quizQuestions[currentIndex.value]);
const answered = computed(() => selectedIndex.value !== null);
const isCorrect = computed(
    () => selectedIndex.value === currentQuestion.value.answerIndex,
);
const isLastQuestion = computed(() => currentIndex.value === total - 1);
const progressPercent = computed(() =>
    Math.round(((currentIndex.value + (answered.value ? 1 : 0)) / total) * 100),
);

const tier = computed(
    () => tiers.find((t) => score.value >= t.min) ?? tiers[tiers.length - 1],
);

const shareUrl = computed(() => {
    const quizUrl = props.seo.canonical ?? 'https://tagaytaynews.com';
    const text = `I scored ${score.value}/${total} on "How Tagaytay Are You?" — I'm a ${tier.value.name}! Take the quiz: ${quizUrl}`;
    return `https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}`;
});

function start(): void {
    stage.value = 'playing';
    currentIndex.value = 0;
    score.value = 0;
    selectedIndex.value = null;
    window.tnTrack?.('feature', 'quiz:start');
}

function choose(index: number): void {
    if (answered.value) {
        return;
    }
    selectedIndex.value = index;
    if (index === currentQuestion.value.answerIndex) {
        score.value += 1;
    }
}

function next(): void {
    if (!answered.value) {
        return;
    }
    if (isLastQuestion.value) {
        stage.value = 'finished';
        window.tnTrack?.('feature', 'quiz:complete', score.value);
    } else {
        currentIndex.value += 1;
        selectedIndex.value = null;
    }
}

function optionLetter(index: number): string {
    return String.fromCharCode(65 + index);
}

function optionClass(index: number): string {
    const base =
        'flex w-full items-center gap-3 rounded-lg border px-4 py-3 text-left transition';
    if (!answered.value) {
        return `${base} border-neutral-200 bg-white hover:border-brand-400 hover:bg-brand-50`;
    }
    if (index === currentQuestion.value.answerIndex) {
        return `${base} border-brand-600 bg-brand-50 font-semibold text-brand-800`;
    }
    if (index === selectedIndex.value) {
        return `${base} border-red-300 bg-red-50 text-red-800`;
    }
    return `${base} border-neutral-200 bg-white opacity-60`;
}
</script>

<template>
    <PublicLayout>
        <SeoHead :seo="seo" />

        <div class="mx-auto max-w-2xl px-4 py-8 sm:px-6 sm:py-10">
            <!-- Intro -->
            <section
                v-if="stage === 'intro'"
                class="rounded-xl border border-neutral-200 bg-brand-50 px-6 py-12 text-center sm:px-10"
            >
                <p
                    class="mb-3 text-xs font-semibold tracking-widest text-sunrise-600 uppercase"
                >
                    Quiz
                </p>
                <h1
                    class="mb-4 font-display text-4xl font-bold text-brand-950 sm:text-5xl"
                >
                    How Tagaytay Are You?
                </h1>
                <p
                    class="mx-auto mb-2 max-w-md leading-relaxed text-neutral-600"
                >
                    Ten questions about Taal, bulalo, fog, and ridge life.
                    Answer them all to find out if you're a Weekend Tourist or
                    an Honorary Tagaytayeño.
                </p>
                <p class="mb-8 text-sm text-neutral-500">
                    10 questions &middot; No timer &middot; Instant results
                </p>
                <button
                    type="button"
                    class="rounded-lg bg-brand-700 px-8 py-3 font-semibold text-white transition hover:bg-brand-600"
                    @click="start"
                >
                    Start the quiz
                </button>
            </section>

            <!-- Question -->
            <section v-else-if="stage === 'playing'">
                <div
                    class="mb-2 flex items-center justify-between text-xs font-medium text-neutral-500"
                >
                    <span>Question {{ currentIndex + 1 }} of {{ total }}</span>
                    <span>{{ score }} correct so far</span>
                </div>
                <div
                    class="mb-6 h-1.5 w-full overflow-hidden rounded-full bg-brand-100"
                >
                    <div
                        class="h-full rounded-full bg-sunrise-500 transition-all duration-300"
                        :style="{ width: `${progressPercent}%` }"
                    ></div>
                </div>

                <div
                    class="rounded-xl border border-neutral-200 bg-white p-6 sm:p-8"
                >
                    <h1
                        class="mb-6 font-display text-2xl leading-snug font-bold text-brand-950"
                    >
                        {{ currentQuestion.question }}
                    </h1>

                    <div class="space-y-3">
                        <button
                            v-for="(option, index) in currentQuestion.options"
                            :key="option"
                            type="button"
                            :class="optionClass(index)"
                            :disabled="answered"
                            @click="choose(index)"
                        >
                            <span
                                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-neutral-300 text-xs font-semibold text-neutral-500"
                                :class="{
                                    'border-brand-600 text-brand-700':
                                        answered &&
                                        index === currentQuestion.answerIndex,
                                }"
                            >
                                {{ optionLetter(index) }}
                            </span>
                            <span>{{ option }}</span>
                            <span
                                v-if="
                                    answered &&
                                    index === currentQuestion.answerIndex
                                "
                                class="ml-auto text-brand-700"
                                aria-hidden="true"
                            >
                                &#10003;
                            </span>
                            <span
                                v-else-if="answered && index === selectedIndex"
                                class="ml-auto text-red-600"
                                aria-hidden="true"
                            >
                                &#10007;
                            </span>
                        </button>
                    </div>

                    <div
                        v-if="answered"
                        class="mt-6 rounded-lg border p-4"
                        :class="
                            isCorrect
                                ? 'border-brand-200 bg-brand-50'
                                : 'border-red-200 bg-red-50'
                        "
                    >
                        <p
                            class="mb-1 font-semibold"
                            :class="
                                isCorrect ? 'text-brand-800' : 'text-red-800'
                            "
                        >
                            {{ isCorrect ? 'Correct!' : 'Not quite!' }}
                        </p>
                        <p class="text-sm leading-relaxed text-neutral-700">
                            {{ currentQuestion.explanation }}
                        </p>
                    </div>

                    <div v-if="answered" class="mt-6 flex justify-end">
                        <button
                            type="button"
                            class="rounded-lg bg-brand-700 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-600"
                            @click="next"
                        >
                            {{
                                isLastQuestion
                                    ? 'See my result'
                                    : 'Next question'
                            }}
                        </button>
                    </div>
                </div>
            </section>

            <!-- Result -->
            <section
                v-else
                class="rounded-xl border border-neutral-200 bg-brand-50 px-6 py-12 text-center sm:px-10"
            >
                <p
                    class="mb-3 text-xs font-semibold tracking-widest text-sunrise-600 uppercase"
                >
                    Your result
                </p>
                <p class="mb-1 font-display text-6xl font-bold text-brand-950">
                    {{ score
                    }}<span class="text-3xl text-neutral-400"
                        >/{{ total }}</span
                    >
                </p>
                <h1
                    class="mb-3 font-display text-3xl font-bold text-sunrise-600"
                >
                    {{ tier.name }}
                </h1>
                <p
                    class="mx-auto mb-8 max-w-md leading-relaxed text-neutral-600"
                >
                    {{ tier.description }}
                </p>

                <div
                    class="flex flex-col items-center justify-center gap-3 sm:flex-row"
                >
                    <button
                        type="button"
                        class="rounded-lg bg-brand-700 px-6 py-3 font-semibold text-white transition hover:bg-brand-600"
                        @click="start"
                    >
                        Play again
                    </button>
                    <a
                        :href="shareUrl"
                        target="_blank"
                        rel="noopener"
                        data-track="share:quiz-x"
                        class="rounded-lg bg-neutral-900 px-6 py-3 font-semibold text-white transition hover:bg-neutral-700"
                    >
                        Share your score on X
                    </a>
                </div>

                <div class="mt-10 border-t border-neutral-200 pt-6 text-left">
                    <p
                        class="mb-3 text-xs font-semibold tracking-widest text-neutral-500 uppercase"
                    >
                        Keep exploring
                    </p>
                    <ul class="space-y-2">
                        <li>
                            <Link
                                href="/taal-volcano/taal-volcano-guide"
                                class="font-medium text-brand-700 hover:text-brand-600"
                            >
                                Taal Volcano guide &rarr;
                            </Link>
                        </li>
                        <li>
                            <Link
                                href="/tourism/tagaytay-weekend-itinerary"
                                class="font-medium text-brand-700 hover:text-brand-600"
                            >
                                Tagaytay weekend itinerary &rarr;
                            </Link>
                        </li>
                    </ul>
                </div>
            </section>
        </div>
    </PublicLayout>
</template>
