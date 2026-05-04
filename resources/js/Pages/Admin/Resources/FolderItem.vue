<script>
export default { name: 'FolderItem' };
</script>

<script setup>
import AppStatusBadge from '@/Components/AppStatusBadge.vue';
import { ref, watch } from 'vue';

const props = defineProps({
    folder: Object,
    autoExpand: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['delete', 'lock', 'add', 'upload', 'schedule']);

const isOpen = ref(false);

watch(() => props.autoExpand, (value) => {
    isOpen.value = value;
}, { immediate: true });

const hasUnlockWindow = (item) => Boolean(item?.unlock_starts_at && item?.unlock_ends_at);
const isEffectivelyLocked = (item) => Boolean(item?.is_effectively_locked ?? item?.is_locked);

const toDateTimeLabel = (value) => {
    if (!value) return '';

    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) return '';

    return parsed.toLocaleString(undefined, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
};

const unlockWindowLabel = (item) => {
    if (!hasUnlockWindow(item)) {
        return '';
    }

    return `${toDateTimeLabel(item.unlock_starts_at)} - ${toDateTimeLabel(item.unlock_ends_at)}`;
};
</script>

<template>
    <div class="w-full">
        <button
            type="button"
            class="panel-muted flex w-full items-center justify-between border p-5 text-left transition-all hover:border-blue-200 hover:bg-white"
            @click="isOpen = !isOpen"
        >
            <div class="flex items-center gap-4">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-blue-600 text-xs font-black uppercase tracking-[0.18em] text-white">
                    {{ isOpen ? 'OPEN' : 'DIR' }}
                </span>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-base font-black uppercase tracking-tight text-slate-900">{{ folder.name }}</span>
                        <AppStatusBadge v-if="isEffectivelyLocked(folder)" label="Locked" variant="locked" />
                        <AppStatusBadge v-if="folder.is_temporarily_unlocked" label="Temporarily Open" variant="success" />
                    </div>
                    <p class="mt-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">
                        {{ folder.children_recursive?.length || 0 }} subfolders / {{ folder.files?.length || 0 }} files
                    </p>
                    <p v-if="hasUnlockWindow(folder)" class="mt-1 text-[11px] font-semibold text-blue-600">
                        Unlock window: {{ unlockWindowLabel(folder) }}
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap gap-2" @click.stop>
                <button
                    type="button"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 text-xl font-black text-white transition hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2"
                    title="Add subfolder"
                    aria-label="Add subfolder"
                    @click="$emit('add', folder.id)"
                >
                    +
                </button>
                <button
                    type="button"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-600 text-lg font-black text-white transition hover:bg-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2"
                    title="Upload file to this folder"
                    aria-label="Upload file to this folder"
                    @click="$emit('upload', folder.id)"
                >
                    ⤴
                </button>
                <button
                    type="button"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-lg font-black text-slate-700 transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2"
                    :title="hasUnlockWindow(folder) ? 'Edit timed unlock window' : 'Set timed unlock window'"
                    :aria-label="hasUnlockWindow(folder) ? 'Edit timed unlock window' : 'Set timed unlock window'"
                    @click="$emit('schedule', 'folder', folder)"
                >
                    ⏱
                </button>
                <button
                    type="button"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-lg font-black text-slate-700 transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2"
                    :class="folder.is_locked ? 'bg-slate-900 text-white hover:bg-slate-700 border-slate-900' : ''"
                    :title="folder.is_locked ? 'Unlock folder' : 'Lock folder'"
                    :aria-label="folder.is_locked ? 'Unlock folder' : 'Lock folder'"
                    @click="$emit('lock', 'folder', folder.id)"
                >
                    {{ folder.is_locked ? '🔓' : '🔒' }}
                </button>
                <button
                    type="button"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-red-100 text-lg font-black text-red-700 transition hover:bg-red-600 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2"
                    title="Delete folder"
                    aria-label="Delete folder"
                    @click="$emit('delete', 'folder', folder.id)"
                >
                    🗑
                </button>
            </div>
        </button>

        <div v-if="isOpen" class="mt-3 space-y-3 border-l-4 border-slate-200 pl-5 md:ml-6 md:pl-6">
            <FolderItem
                v-for="sub in folder.children_recursive"
                :key="sub.id"
                :folder="sub"
                :auto-expand="props.autoExpand"
                @delete="(type, id) => $emit('delete', type, id)"
                @lock="(type, id) => $emit('lock', type, id)"
                @add="(id) => $emit('add', id)"
                @upload="(id) => $emit('upload', id)"
                @schedule="(type, item) => $emit('schedule', type, item)"
            />

            <div
                v-for="file in folder.files"
                :key="file.id"
                class="panel-muted flex items-center justify-between border p-4"
            >
                <div class="flex min-w-0 items-center gap-4">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-900 text-[11px] font-black uppercase tracking-[0.18em] text-white">
                        {{ file.file_type === 'pdf' ? 'PDF' : 'FILE' }}
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-black text-slate-800" :class="{ 'line-through text-slate-400': isEffectivelyLocked(file) }">
                            {{ file.title }}
                        </p>
                        <p class="mt-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">
                            {{ file.file_type }}
                        </p>
                        <p class="mt-1 text-[11px] font-semibold text-slate-500">
                            Category: {{ file.category || 'Uncategorized' }}
                        </p>
                        <p v-if="hasUnlockWindow(file)" class="mt-1 text-[11px] font-semibold text-blue-600">
                            Unlock window: {{ unlockWindowLabel(file) }}
                        </p>
                    </div>
                    <AppStatusBadge v-if="isEffectivelyLocked(file)" label="Locked" variant="locked" />
                    <AppStatusBadge v-if="file.is_temporarily_unlocked" label="Temporarily Open" variant="success" />
                </div>

                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-lg font-black text-slate-700 transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2"
                        :title="hasUnlockWindow(file) ? 'Edit timed unlock window' : 'Set timed unlock window'"
                        :aria-label="hasUnlockWindow(file) ? 'Edit timed unlock window' : 'Set timed unlock window'"
                        @click="$emit('schedule', 'file', file)"
                    >
                        ⏱
                    </button>
                    <button
                        type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-lg font-black text-slate-700 transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2"
                        :class="file.is_locked ? 'bg-slate-900 text-white hover:bg-slate-700 border-slate-900' : ''"
                        :title="file.is_locked ? 'Unlock file' : 'Lock file'"
                        :aria-label="file.is_locked ? 'Unlock file' : 'Lock file'"
                        @click="$emit('lock', 'file', file.id)"
                    >
                        {{ file.is_locked ? '🔓' : '🔒' }}
                    </button>
                    <button
                        type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-red-100 text-lg font-black text-red-700 transition hover:bg-red-600 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2"
                        title="Delete file"
                        aria-label="Delete file"
                        @click="$emit('delete', 'file', file.id)"
                    >
                        🗑
                    </button>
                </div>
            </div>

            <div
                v-if="!folder.children_recursive?.length && !folder.files?.length"
                class="rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50 p-4 text-xs font-black uppercase tracking-[0.18em] text-slate-400"
            >
                Directory is empty
            </div>
        </div>
    </div>
</template>
