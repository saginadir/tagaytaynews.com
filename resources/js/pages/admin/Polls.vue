<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import AdminNav from '@/components/AdminNav.vue';
import InputError from '@/components/InputError.vue';

interface AdminPollOption {
    id: number;
    label: string;
    votes: number;
    sort_order: number;
}

interface AdminPoll {
    id: number;
    question: string;
    slug: string;
    is_active: boolean;
    options: AdminPollOption[];
    created_at: string;
}

const props = defineProps<{
    polls: AdminPoll[];
    taalAlertLevel: number;
    adminPath: string;
}>();

const alertLabels: Record<number, string> = {
    0: 'Level 0 — Quiet',
    1: 'Level 1 — Low-level unrest',
    2: 'Level 2 — Moderate unrest',
    3: 'Level 3 — High unrest',
    4: 'Level 4 — Eruption imminent',
    5: 'Level 5 — Hazardous eruption',
};

const alertForm = useForm({ level: props.taalAlertLevel });

function saveAlert() {
    alertForm.post(`/${props.adminPath}/taal-alert`, { preserveScroll: true });
}

const pollForm = useForm({ question: '', optionsText: '' });

function createPoll() {
    const options = pollForm.optionsText
        .split('\n')
        .map((line) => line.trim())
        .filter(Boolean);

    pollForm.transform(() => ({
        question: pollForm.question,
        options,
    })).post(`/${props.adminPath}/polls`, {
        preserveScroll: true,
        onSuccess: () => pollForm.reset(),
    });
}

function togglePoll(poll: AdminPoll) {
    router.put(
        `/${props.adminPath}/polls/${poll.id}`,
        { is_active: !poll.is_active },
        { preserveScroll: true },
    );
}

function deletePoll(poll: AdminPoll) {
    if (!confirm(`Delete poll "${poll.question}" and its votes?`)) return;
    router.delete(`/${props.adminPath}/polls/${poll.id}`, {
        preserveScroll: true,
    });
}

function totalVotes(poll: AdminPoll): number {
    return poll.options.reduce((sum, option) => sum + option.votes, 0);
}

function percent(poll: AdminPoll, votes: number): number {
    const total = totalVotes(poll);
    return total === 0 ? 0 : Math.round((votes / total) * 100);
}

const inputClass =
    'w-full rounded-md border border-zinc-700 bg-zinc-800 px-3 py-2 text-sm text-white placeholder-zinc-600 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500';
const labelClass = 'block text-xs text-zinc-500 mb-1';
</script>

<template>
    <div class="min-h-screen bg-zinc-950 p-8 text-zinc-100">
        <div class="mx-auto max-w-4xl">
            <div class="mb-8 flex items-center justify-between">
                <h1 class="text-2xl font-bold">Polls &amp; Alerts</h1>
                <AdminNav :admin-path="adminPath" current="polls" />
            </div>

            <!-- Taal alert level -->
            <form
                @submit.prevent="saveAlert"
                class="mb-8 rounded-lg border border-zinc-800 bg-zinc-900 p-6"
            >
                <h2 class="mb-1 text-lg font-semibold">Taal alert level</h2>
                <p class="mb-4 text-xs text-zinc-500">
                    Shown in the Ridge Report widget on the homepage. Update
                    when PHIVOLCS changes the level.
                </p>
                <div class="flex items-end gap-4">
                    <div class="grow">
                        <label :class="labelClass">Current level</label>
                        <select v-model="alertForm.level" :class="inputClass">
                            <option
                                v-for="(label, level) in alertLabels"
                                :key="level"
                                :value="Number(level)"
                            >
                                {{ label }}
                            </option>
                        </select>
                        <InputError :message="alertForm.errors.level" />
                    </div>
                    <button
                        type="submit"
                        :disabled="alertForm.processing"
                        class="rounded bg-blue-600 px-4 py-2 text-sm font-medium hover:bg-blue-700 disabled:opacity-50"
                    >
                        Save
                    </button>
                </div>
            </form>

            <!-- New poll -->
            <form
                @submit.prevent="createPoll"
                class="mb-8 rounded-lg border border-zinc-800 bg-zinc-900 p-6"
            >
                <h2 class="mb-4 text-lg font-semibold">New poll</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label :class="labelClass">Question</label>
                        <input
                            v-model="pollForm.question"
                            type="text"
                            :class="inputClass"
                            placeholder="e.g. Best viewpoint on the ridge?"
                        />
                        <InputError :message="pollForm.errors.question" />
                    </div>
                    <div>
                        <label :class="labelClass"
                            >Options (one per line, 2–6)</label
                        >
                        <textarea
                            v-model="pollForm.optionsText"
                            rows="3"
                            :class="inputClass"
                            placeholder="Option A&#10;Option B&#10;Option C"
                        ></textarea>
                        <InputError :message="pollForm.errors.options" />
                    </div>
                </div>
                <p class="mt-2 text-xs text-zinc-500">
                    New polls start inactive — activate one below to put it on
                    the homepage (one live poll at a time).
                </p>
                <button
                    type="submit"
                    :disabled="pollForm.processing"
                    class="mt-3 rounded bg-blue-600 px-4 py-2 text-sm font-medium hover:bg-blue-700 disabled:opacity-50"
                >
                    Create Poll
                </button>
            </form>

            <!-- Polls list -->
            <div class="space-y-4">
                <div
                    v-for="poll in polls"
                    :key="poll.id"
                    class="rounded-lg border border-zinc-800 bg-zinc-900 p-6"
                >
                    <div class="mb-3 flex items-start justify-between gap-4">
                        <div>
                            <h3 class="font-semibold">{{ poll.question }}</h3>
                            <p class="mt-1 text-xs text-zinc-500">
                                {{ totalVotes(poll) }} votes
                                <span
                                    v-if="poll.is_active"
                                    class="ml-2 inline-block rounded bg-green-900/60 px-2 py-0.5 font-medium text-green-300"
                                    >live on homepage</span
                                >
                            </p>
                        </div>
                        <div class="shrink-0 whitespace-nowrap">
                            <button
                                @click="togglePoll(poll)"
                                class="mr-3 text-xs font-medium"
                                :class="
                                    poll.is_active
                                        ? 'text-zinc-400 hover:text-zinc-200'
                                        : 'text-green-400 hover:text-green-300'
                                "
                            >
                                {{ poll.is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                            <button
                                @click="deletePoll(poll)"
                                class="text-xs text-red-400 hover:text-red-300"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                    <div class="space-y-1.5">
                        <div
                            v-for="option in poll.options"
                            :key="option.id"
                            class="flex items-center gap-3 text-sm"
                        >
                            <span class="w-2/5 truncate text-zinc-300">{{
                                option.label
                            }}</span>
                            <div
                                class="h-1.5 grow overflow-hidden rounded-full bg-zinc-800"
                            >
                                <div
                                    class="h-full rounded-full bg-blue-500"
                                    :style="{
                                        width: percent(poll, option.votes) + '%',
                                    }"
                                ></div>
                            </div>
                            <span
                                class="w-14 text-right text-xs text-zinc-500 tabular-nums"
                                >{{ option.votes }} ({{
                                    percent(poll, option.votes)
                                }}%)</span
                            >
                        </div>
                    </div>
                </div>
                <p
                    v-if="polls.length === 0"
                    class="py-8 text-center text-zinc-500"
                >
                    No polls yet.
                </p>
            </div>
        </div>
    </div>
</template>
