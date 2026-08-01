<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{ adminPath: string }>();
const page = usePage();

const email = ref('');
const password = ref('');
const isLoading = ref(false);

const errorMsg = computed(() => (page.props.errors as any)?.message ?? '');

function login() {
    isLoading.value = true;
    router.post(
        `/${props.adminPath}`,
        { email: email.value, password: password.value },
        {
            onFinish: () => {
                isLoading.value = false;
            },
        },
    );
}
</script>

<template>
    <Head
        ><title>{{ ' ' }}</title></Head
    >

    <div class="flex min-h-screen items-center justify-center bg-zinc-950 px-6">
        <div class="w-full max-w-sm">
            <form
                @submit.prevent="login"
                class="space-y-5 rounded-xl border border-zinc-800 bg-zinc-900 p-8"
            >
                <div
                    v-if="errorMsg"
                    class="rounded border border-red-800 bg-red-950 px-4 py-3 text-sm text-red-300"
                >
                    {{ errorMsg }}
                </div>

                <div>
                    <label class="mb-1 block text-xs text-zinc-500"
                        >Email</label
                    >
                    <input
                        v-model="email"
                        type="email"
                        autocomplete="email"
                        required
                        class="w-full rounded-md border border-zinc-700 bg-zinc-800 px-4 py-2.5 text-sm text-white placeholder-zinc-600 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 focus:outline-none"
                        :disabled="isLoading"
                    />
                </div>

                <div>
                    <label class="mb-1 block text-xs text-zinc-500"
                        >Password</label
                    >
                    <input
                        v-model="password"
                        type="password"
                        autocomplete="current-password"
                        required
                        class="w-full rounded-md border border-zinc-700 bg-zinc-800 px-4 py-2.5 text-sm text-white placeholder-zinc-600 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 focus:outline-none"
                        :disabled="isLoading"
                    />
                </div>

                <Button
                    type="submit"
                    :disabled="isLoading"
                    class="w-full bg-cyan-500 font-semibold text-zinc-950 hover:bg-cyan-400"
                >
                    {{ isLoading ? 'Signing in...' : 'Sign in' }}
                </Button>
            </form>
        </div>
    </div>
</template>
