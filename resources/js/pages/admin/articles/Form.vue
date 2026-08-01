<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { marked } from 'marked';
import { computed, ref, watch } from 'vue';
import AdminNav from '@/components/AdminNav.vue';
import InputError from '@/components/InputError.vue';
import type {
    ArticleItem,
    CategoryItem,
    MediaItem,
    SourceItem,
} from '@/types/content';

const props = defineProps<{
    article: ArticleItem | null;
    categories: CategoryItem[];
    sources: SourceItem[];
    media: MediaItem[];
    adminPath: string;
}>();

function toDateTimeLocal(iso: string | null): string {
    return iso ? iso.slice(0, 16) : '';
}

const form = useForm({
    title: props.article?.title ?? '',
    slug: props.article?.slug ?? '',
    category_id: props.article?.category_id ?? (null as number | null),
    source_id: props.article?.source_id ?? (null as number | null),
    source_url: props.article?.source_url ?? '',
    featured_image_id:
        props.article?.featured_image_id ?? (null as number | null),
    author: props.article?.author ?? 'Tagaytay News Staff',
    excerpt: props.article?.excerpt ?? '',
    body: props.article?.body ?? '',
    status: props.article?.status ?? 'draft',
    published_at: toDateTimeLocal(props.article?.published_at ?? null),
    seo_title: props.article?.seo_title ?? '',
    seo_description: props.article?.seo_description ?? '',
});

const slugTouched = ref(!!props.article);

watch(
    () => form.title,
    (title) => {
        if (!slugTouched.value) {
            form.slug = slugify(title);
        }
    },
);

function slugify(value: string): string {
    return value
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

const preview = computed(() => marked.parse(form.body || '', { async: false }));

const pickerOpen = ref(false);
const selectedImage = computed(
    () => props.media.find((m) => m.id === form.featured_image_id) ?? null,
);

function chooseImage(item: MediaItem) {
    form.featured_image_id = item.id;
    pickerOpen.value = false;
}

function submit() {
    if (props.article) {
        form.put(`/${props.adminPath}/articles/${props.article.id}`);
    } else {
        form.post(`/${props.adminPath}/articles`);
    }
}

const inputClass =
    'w-full rounded-md border border-zinc-700 bg-zinc-800 px-3 py-2 text-sm text-white placeholder-zinc-600 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 [color-scheme:dark]';
const labelClass = 'block text-xs text-zinc-500 mb-1';
</script>

<template>
    <div class="min-h-screen bg-zinc-950 p-8 text-zinc-100">
        <div class="mx-auto max-w-7xl">
            <div class="mb-8 flex items-center justify-between">
                <h1 class="text-2xl font-bold">
                    {{ article ? 'Edit Article' : 'New Article' }}
                </h1>
                <AdminNav :admin-path="adminPath" current="articles" />
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <!-- Main column -->
                    <div class="space-y-6 lg:col-span-2">
                        <div
                            class="space-y-4 rounded-lg border border-zinc-800 bg-zinc-900 p-6"
                        >
                            <div>
                                <label :class="labelClass">Title</label>
                                <input
                                    v-model="form.title"
                                    type="text"
                                    :class="inputClass"
                                    placeholder="Article title"
                                />
                                <InputError :message="form.errors.title" />
                            </div>

                            <div>
                                <label :class="labelClass">Slug</label>
                                <input
                                    v-model="form.slug"
                                    type="text"
                                    :class="inputClass"
                                    placeholder="auto-generated-from-title"
                                    @input="slugTouched = true"
                                />
                                <InputError :message="form.errors.slug" />
                            </div>

                            <div>
                                <label :class="labelClass">Excerpt</label>
                                <textarea
                                    v-model="form.excerpt"
                                    rows="2"
                                    :class="inputClass"
                                    placeholder="Short summary (optional)"
                                ></textarea>
                                <InputError :message="form.errors.excerpt" />
                            </div>

                            <div>
                                <label :class="labelClass"
                                    >Body (Markdown)</label
                                >
                                <div
                                    class="grid grid-cols-1 gap-4 xl:grid-cols-2"
                                >
                                    <textarea
                                        v-model="form.body"
                                        rows="18"
                                        :class="inputClass + ' font-mono'"
                                        placeholder="Write the story in Markdown..."
                                    ></textarea>
                                    <div
                                        class="prose prose-sm max-w-none overflow-y-auto rounded-md border border-zinc-700 bg-zinc-800/50 px-4 py-2 text-sm prose-invert"
                                        v-html="preview"
                                    ></div>
                                </div>
                                <InputError :message="form.errors.body" />
                            </div>
                        </div>

                        <div
                            class="space-y-4 rounded-lg border border-zinc-800 bg-zinc-900 p-6"
                        >
                            <h2 class="text-sm font-semibold text-zinc-300">
                                SEO
                            </h2>
                            <div>
                                <label :class="labelClass">SEO Title</label>
                                <input
                                    v-model="form.seo_title"
                                    type="text"
                                    :class="inputClass"
                                    placeholder="Defaults to title if empty"
                                />
                                <InputError :message="form.errors.seo_title" />
                            </div>
                            <div>
                                <label :class="labelClass"
                                    >SEO Description</label
                                >
                                <textarea
                                    v-model="form.seo_description"
                                    rows="2"
                                    :class="inputClass"
                                ></textarea>
                                <InputError
                                    :message="form.errors.seo_description"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar column -->
                    <div class="space-y-6">
                        <div
                            class="space-y-4 rounded-lg border border-zinc-800 bg-zinc-900 p-6"
                        >
                            <div>
                                <label :class="labelClass">Status</label>
                                <select
                                    v-model="form.status"
                                    :class="inputClass"
                                >
                                    <option value="draft">Draft</option>
                                    <option value="published">Published</option>
                                </select>
                                <InputError :message="form.errors.status" />
                            </div>

                            <div>
                                <label :class="labelClass"
                                    >Published at
                                    <span v-if="form.status === 'published'"
                                        >(required)</span
                                    ></label
                                >
                                <input
                                    v-model="form.published_at"
                                    type="datetime-local"
                                    :class="inputClass"
                                />
                                <InputError
                                    :message="form.errors.published_at"
                                />
                            </div>

                            <div>
                                <label :class="labelClass">Author</label>
                                <input
                                    v-model="form.author"
                                    type="text"
                                    :class="inputClass"
                                />
                                <InputError :message="form.errors.author" />
                            </div>

                            <div>
                                <label :class="labelClass">Category</label>
                                <select
                                    v-model="form.category_id"
                                    :class="inputClass"
                                >
                                    <option :value="null" disabled>
                                        Select category
                                    </option>
                                    <option
                                        v-for="category in categories"
                                        :key="category.id"
                                        :value="category.id"
                                    >
                                        {{ category.name }}
                                    </option>
                                </select>
                                <InputError
                                    :message="form.errors.category_id"
                                />
                            </div>
                        </div>

                        <div
                            class="space-y-4 rounded-lg border border-zinc-800 bg-zinc-900 p-6"
                        >
                            <h2 class="text-sm font-semibold text-zinc-300">
                                Source / Attribution
                            </h2>
                            <div>
                                <label :class="labelClass">Source</label>
                                <select
                                    v-model="form.source_id"
                                    :class="inputClass"
                                >
                                    <option :value="null">None</option>
                                    <option
                                        v-for="source in sources"
                                        :key="source.id"
                                        :value="source.id"
                                    >
                                        {{ source.name }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.source_id" />
                            </div>
                            <div>
                                <label :class="labelClass"
                                    >Source URL (link to original story)</label
                                >
                                <input
                                    v-model="form.source_url"
                                    type="url"
                                    :class="inputClass"
                                    placeholder="https://..."
                                />
                                <InputError :message="form.errors.source_url" />
                            </div>
                        </div>

                        <div
                            class="space-y-4 rounded-lg border border-zinc-800 bg-zinc-900 p-6"
                        >
                            <h2 class="text-sm font-semibold text-zinc-300">
                                Featured Image
                            </h2>
                            <div v-if="selectedImage" class="space-y-2">
                                <img
                                    :src="selectedImage.url"
                                    :alt="
                                        selectedImage.alt ||
                                        selectedImage.filename
                                    "
                                    class="w-full rounded"
                                />
                                <div class="flex gap-3 text-xs">
                                    <button
                                        type="button"
                                        @click="pickerOpen = !pickerOpen"
                                        class="text-blue-400 hover:text-blue-300"
                                    >
                                        Change
                                    </button>
                                    <button
                                        type="button"
                                        @click="form.featured_image_id = null"
                                        class="text-red-400 hover:text-red-300"
                                    >
                                        Remove
                                    </button>
                                </div>
                            </div>
                            <button
                                v-else
                                type="button"
                                @click="pickerOpen = !pickerOpen"
                                class="w-full rounded border border-dashed border-zinc-700 px-3 py-2 text-sm text-zinc-400 hover:border-zinc-500"
                            >
                                Choose from Media Library
                            </button>
                            <InputError
                                :message="form.errors.featured_image_id"
                            />

                            <div
                                v-if="pickerOpen"
                                class="max-h-64 overflow-y-auto rounded border border-zinc-800"
                            >
                                <div
                                    v-if="media.length === 0"
                                    class="p-4 text-center text-xs text-zinc-500"
                                >
                                    No media yet — upload via the Media page.
                                </div>
                                <div v-else class="grid grid-cols-3 gap-1 p-1">
                                    <button
                                        v-for="item in media"
                                        :key="item.id"
                                        type="button"
                                        @click="chooseImage(item)"
                                        class="aspect-square overflow-hidden rounded border-2 bg-zinc-800 hover:border-blue-500"
                                        :class="
                                            form.featured_image_id === item.id
                                                ? 'border-blue-500'
                                                : 'border-transparent'
                                        "
                                        :title="item.filename"
                                    >
                                        <span
                                            v-if="item.is_video"
                                            class="text-xs text-zinc-500"
                                            >Video</span
                                        >
                                        <img
                                            v-else
                                            :src="item.url"
                                            :alt="item.alt || item.filename"
                                            class="h-full w-full object-cover"
                                        />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded bg-blue-600 px-4 py-2 text-sm font-medium hover:bg-blue-700 disabled:opacity-50"
                    >
                        {{
                            form.processing
                                ? 'Saving...'
                                : article
                                  ? 'Update Article'
                                  : 'Create Article'
                        }}
                    </button>
                    <a
                        :href="`/${adminPath}/articles`"
                        class="text-sm text-zinc-400 hover:text-white"
                        >Cancel</a
                    >
                </div>
            </form>
        </div>
    </div>
</template>
