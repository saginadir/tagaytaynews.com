<script setup lang="ts">
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AdminNav from '@/components/AdminNav.vue'
import type { MediaItem } from '@/types/content'

const props = defineProps<{
    media: MediaItem[]
    adminPath: string
}>()

const editingId = ref<number | null>(null)
const editAlt = ref('')
const dragOver = ref(false)
const uploading = ref(false)

function startEdit(item: MediaItem) {
    editingId.value = item.id
    editAlt.value = item.alt || ''
}

function saveAlt(item: MediaItem) {
    router.put(`/${props.adminPath}/media/${item.id}`, { alt: editAlt.value }, {
        preserveScroll: true,
        onSuccess: () => { editingId.value = null },
    })
}

function cancelEdit() {
    editingId.value = null
}

function deleteMedia(item: MediaItem) {
    if (!confirm(`Delete "${item.filename}"?`)) return
    router.delete(`/${props.adminPath}/media/${item.id}`, { preserveScroll: true })
}

function copyUrl(item: MediaItem) {
    navigator.clipboard.writeText(item.url)
}

function handleUpload(e: Event) {
    const input = e.target as HTMLInputElement
    if (!input.files?.length) return
    uploadFile(input.files[0])
    input.value = ''
}

function handleDrop(e: DragEvent) {
    dragOver.value = false
    if (!e.dataTransfer?.files?.length) return
    uploadFile(e.dataTransfer.files[0])
}

function uploadFile(file: File) {
    uploading.value = true
    router.post(`/${props.adminPath}/media`, { file }, {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => { uploading.value = false },
    })
}

function formatSize(bytes: number): string {
    if (bytes < 1024) return bytes + ' B'
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}
</script>

<template>
    <div class="min-h-screen bg-zinc-950 text-zinc-100 p-8">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center justify-between mb-8">
                <h1 class="text-2xl font-bold">Media Library</h1>
                <AdminNav :admin-path="adminPath" current="media" />
            </div>

            <!-- Upload area -->
            <div
                class="border-2 border-dashed rounded-lg p-8 mb-8 text-center transition-colors"
                :class="dragOver ? 'border-blue-500 bg-blue-500/10' : 'border-zinc-700 hover:border-zinc-500'"
                @dragover.prevent="dragOver = true"
                @dragleave="dragOver = false"
                @drop.prevent="handleDrop"
            >
                <p class="text-zinc-400 mb-2">
                    {{ uploading ? 'Uploading...' : 'Drag & drop a file here, or' }}
                </p>
                <label v-if="!uploading" class="inline-block px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded cursor-pointer text-sm font-medium">
                    Choose File
                    <input type="file" class="hidden" accept="image/*,video/*" @change="handleUpload" />
                </label>
                <p class="text-xs text-zinc-500 mt-2">Images and videos up to 10 MB</p>
            </div>

            <!-- Media grid -->
            <div v-if="media.length === 0" class="text-center text-zinc-500 py-12">
                No media uploaded yet.
            </div>
            <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                <div
                    v-for="item in media"
                    :key="item.id"
                    class="bg-zinc-900 rounded-lg overflow-hidden border border-zinc-800"
                >
                    <!-- Thumbnail -->
                    <div class="aspect-square bg-zinc-800 flex items-center justify-center overflow-hidden">
                        <template v-if="item.is_video">
                            <div class="text-zinc-500 text-center">
                                <div class="text-3xl mb-1">&#9654;</div>
                                <div class="text-xs">Video</div>
                            </div>
                        </template>
                        <img v-else :src="item.url" :alt="item.alt || item.filename" class="w-full h-full object-cover" />
                    </div>

                    <!-- Details -->
                    <div class="p-3 space-y-2">
                        <p class="text-xs font-medium truncate" :title="item.filename">{{ item.filename }}</p>
                        <p class="text-xs text-zinc-500">{{ formatSize(item.size) }} &middot; {{ item.mime_type }}</p>
                        <p class="text-xs text-zinc-500">{{ formatDate(item.created_at) }}</p>

                        <!-- Alt text -->
                        <div v-if="editingId === item.id" class="flex gap-1">
                            <input
                                v-model="editAlt"
                                class="flex-1 text-xs bg-zinc-800 border border-zinc-700 rounded px-2 py-1 text-white"
                                placeholder="Alt text"
                                @keyup.enter="saveAlt(item)"
                                @keyup.escape="cancelEdit"
                            />
                            <button @click="saveAlt(item)" class="text-xs text-green-400 hover:text-green-300">Save</button>
                            <button @click="cancelEdit" class="text-xs text-zinc-500 hover:text-zinc-300">Cancel</button>
                        </div>
                        <p v-else class="text-xs text-zinc-500 italic truncate cursor-pointer" @click="startEdit(item)">
                            {{ item.alt || 'Click to add alt text' }}
                        </p>

                        <!-- Actions -->
                        <div class="flex gap-2 pt-1">
                            <button @click="copyUrl(item)" class="text-xs text-blue-400 hover:text-blue-300">Copy URL</button>
                            <button @click="deleteMedia(item)" class="text-xs text-red-400 hover:text-red-300">Delete</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
