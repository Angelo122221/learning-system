<script>
export default { name: 'UserFolderItem' };
</script>

<script setup>
import AppStatusBadge from '@/Components/AppStatusBadge.vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref } from 'vue';

const props = defineProps({
    folder: Object,
    isRoot: {
        type: Boolean,
        default: true,
    },
});

const page = usePage();
const isAuthenticated = computed(() => Boolean(page.props.auth?.user));
const isOpen = ref(false);
const showModal = ref(false);
const showLoginPrompt = ref(false);
const loginPromptTarget = ref('');
const loginPath = '/login';

const palette = [
    'from-sky-700 via-blue-500 to-cyan-400',
    'from-orange-600 via-amber-500 to-yellow-300',
    'from-emerald-700 via-green-500 to-lime-300',
    'from-fuchsia-700 via-violet-500 to-indigo-300',
    'from-rose-700 via-pink-500 to-orange-300',
];

const cardTone = computed(() => {
    const id = Number(props.folder?.id ?? 0);
    return palette[id % palette.length];
});

const folderLocked = computed(() => Boolean(props.folder?.is_effectively_locked ?? props.folder?.is_locked));
const isFileLocked = (file) => Boolean(file?.is_effectively_locked ?? file?.is_locked);
const isTemporarilyUnlocked = (item) => Boolean(item?.is_temporarily_unlocked);

const trackFolderOpen = async () => {
    try {
        await axios.post(`/resources/folders/${props.folder.id}/open`);
    } catch {
        // Tracking should not interrupt navigation.
    }
};

const openLoginPrompt = (target = props.folder?.name ?? 'this resource') => {
    loginPromptTarget.value = target;
    showLoginPrompt.value = true;
};

const handleFolderAction = () => {
    if (folderLocked.value) return;

    if (props.isRoot) {
        showModal.value = true;

        if (isAuthenticated.value) {
            void trackFolderOpen();
        }

        return;
    }

    const willOpen = !isOpen.value;
    isOpen.value = !isOpen.value;

    if (willOpen && isAuthenticated.value) {
        void trackFolderOpen();
    }
};

const closeModal = () => {
    showModal.value = false;
};

const closeLoginPrompt = () => {
    loginPromptTarget.value = '';
    showLoginPrompt.value = false;
};

const navigateToLogin = () => {
    window.location.assign(loginPath);
};
</script>

<template>
    <div v-if="isRoot" class="h-full w-full">
        <button
            type="button"
            class="group relative flex h-full w-full flex-col overflow-hidden rounded-[1.5rem] border-2 border-slate-300 bg-white text-left transition-all"
            :class="folderLocked ? 'cursor-not-allowed opacity-60' : 'hover:-translate-y-0.5 hover:border-slate-400'"
            @click="handleFolderAction"
        >
            <div class="relative overflow-hidden">
                <div class="h-36 bg-gradient-to-br" :class="cardTone" />
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(255,255,255,0.35),_transparent_25%),linear-gradient(180deg,rgba(15,23,42,0.08),rgba(15,23,42,0.42))]" />
                <div v-if="folderLocked || isTemporarilyUnlocked(folder)" class="absolute right-0 top-0 p-3.5">
                    <AppStatusBadge v-if="folderLocked" label="Locked" variant="locked" />
                    <AppStatusBadge v-else-if="isTemporarilyUnlocked(folder)" label="Open Now" variant="success" />
                </div>
                <div class="absolute inset-x-0 bottom-0 p-3.5 text-white">
                    <p class="line-clamp-2 text-lg font-black uppercase tracking-tight">{{ folder.name }}</p>
                    <p class="mt-2 text-[11px] font-black uppercase tracking-[0.18em] text-white/75">
                        {{ folder.children_recursive?.length || 0 }} subfolders / {{ folder.files?.length || 0 }} files
                    </p>
                </div>
            </div>

            <div class="flex items-center justify-between gap-3 px-3.5 py-3.5">
                <div>
                    <p v-if="folderLocked" class="text-xs font-black uppercase tracking-[0.18em] text-red-400">
                        Access restricted
                    </p>
                    <p v-else class="text-xs font-medium text-slate-500">
                        Open this category to view available materials.
                    </p>
                </div>
                <span
                    class="inline-flex shrink-0 items-center rounded-full px-3.5 py-1.5 text-[10px] font-black uppercase tracking-[0.18em]"
                    :class="folderLocked ? 'bg-slate-200 text-slate-500' : 'bg-blue-600 text-white'"
                >
                    {{ folderLocked ? 'Locked' : 'Open Resources' }}
                </span>
            </div>
        </button>

        <Teleport to="body">
            <div
                v-if="showLoginPrompt"
                class="fixed inset-0 z-[110] flex items-center justify-center bg-slate-900/60 p-6 backdrop-blur-sm sm:p-12"
                @click.self="closeLoginPrompt"
            >
                <div class="panel w-full max-w-lg p-8 sm:p-10">
                    <p class="text-[11px] font-black uppercase tracking-[0.18em] text-amber-600">Login required</p>
                    <h2 class="mt-3 text-2xl font-black tracking-tight text-slate-950">Please log in first to access resources.</h2>
                    <p class="mt-3 text-sm font-medium leading-6 text-slate-600">
                        Sign in to open <span class="font-black text-slate-900">{{ loginPromptTarget || folder.name }}</span>, preview files, and download learning materials.
                    </p>

                    <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                        <button type="button" class="action-btn-primary w-full justify-center sm:w-auto" @click="navigateToLogin">
                            Log In
                        </button>
                        <button type="button" class="action-btn-secondary w-full justify-center sm:w-auto" @click="closeLoginPrompt">
                            Maybe later
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
            <div
                v-if="showModal"
                class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/60 p-6 backdrop-blur-sm sm:p-12"
                @click.self="closeModal"
            >
                <div class="panel flex max-h-full w-full max-w-5xl flex-col overflow-hidden">
                    <div class="flex items-center justify-between gap-4 border-b-2 border-slate-100 p-8 md:p-10">
                        <div class="flex items-center gap-5">
                            <div class="flex h-16 w-16 items-center justify-center rounded-[1.5rem] bg-blue-600 text-sm font-black uppercase tracking-[0.18em] text-white">
                                CAT
                            </div>
                            <div>
                                <h2 class="text-3xl font-black uppercase tracking-tight text-slate-900">{{ folder.name }}</h2>
                                <p class="eyebrow mt-2">Directory Contents</p>
                            </div>
                        </div>
                        <button type="button" class="action-btn-secondary" @click="closeModal">
                            Close
                        </button>
                    </div>

                    <div class="custom-scrollbar flex-1 overflow-y-auto bg-slate-50/50 p-8 md:p-10">
                        <div v-if="folder.children_recursive?.length" class="mb-10">
                            <h4 class="eyebrow mb-4 ml-2">Subfolders</h4>
                            <div class="space-y-3">
                                <UserFolderItem v-for="sub in folder.children_recursive" :key="sub.id" :folder="sub" :is-root="false" />
                            </div>
                        </div>

                        <div v-if="folder.files?.length">
                            <h4 class="eyebrow mb-4 ml-2">Files</h4>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div
                                    v-for="file in folder.files"
                                    :key="file.id"
                                    class="panel-muted flex items-center justify-between gap-4 border p-5"
                                    :class="isFileLocked(file) ? 'opacity-60' : 'hover:border-blue-300'"
                                >
                                    <div class="flex min-w-0 items-center gap-4 overflow-hidden">
                                        <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-slate-900 text-[11px] font-black uppercase tracking-[0.18em] text-white">
                                            {{ file.file_type === 'pdf' ? 'PDF' : 'FILE' }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate text-base font-bold text-slate-700" :class="{ 'line-through text-slate-400': isFileLocked(file) }">
                                                {{ file.title }}
                                            </p>
                                            <p class="mt-1 truncate text-xs font-semibold text-slate-500">
                                                {{ file.category || 'Uncategorized' }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex shrink-0 items-center gap-2">
                                        <span v-if="isFileLocked(file)" class="text-xs font-black uppercase tracking-[0.18em] text-slate-400">Locked</span>
                                        <span v-else-if="isTemporarilyUnlocked(file)" class="text-xs font-black uppercase tracking-[0.18em] text-emerald-600">Open Now</span>
                                        <template v-else-if="isAuthenticated">
                                            <a :href="`/resources/preview/${file.id}`" target="_blank" class="action-btn-secondary">Preview</a>
                                            <a :href="`/resources/download/${file.id}`" target="_blank" class="action-btn-primary">Download</a>
                                        </template>
                                        <template v-else>
                                            <button type="button" class="action-btn-secondary" @click="openLoginPrompt(file.title)">Preview</button>
                                            <button type="button" class="action-btn-primary" @click="openLoginPrompt(file.title)">Download</button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="!folder.children_recursive?.length && !folder.files?.length" class="rounded-[2rem] border-4 border-dashed border-slate-200 bg-white p-16 text-center">
                            <p class="text-sm font-black uppercase tracking-[0.18em] text-slate-400">Directory is empty</p>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>

    <div v-else class="w-full">
        <button
            type="button"
            class="panel-muted flex w-full items-center justify-between border p-5 text-left transition-all"
            :class="folderLocked ? 'cursor-not-allowed opacity-60' : 'hover:border-blue-300'"
            @click="handleFolderAction"
        >
            <div class="flex items-center gap-5">
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-600 text-xs font-black uppercase tracking-[0.18em] text-white">
                    {{ isOpen ? 'OPEN' : 'DIR' }}
                </span>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-lg font-black uppercase tracking-tight text-slate-900">{{ folder.name }}</span>
                        <AppStatusBadge v-if="folderLocked" label="Locked" variant="locked" />
                        <AppStatusBadge v-else-if="isTemporarilyUnlocked(folder)" label="Open Now" variant="success" />
                    </div>
                    <span v-if="!folderLocked" class="mt-1 block text-xs font-black uppercase tracking-[0.18em] text-slate-400">
                        {{ folder.children_recursive?.length || 0 }} folders / {{ folder.files?.length || 0 }} files
                    </span>
                </div>
            </div>
        </button>

        <div v-if="isOpen && !folderLocked" class="mt-3 space-y-3 border-l-4 border-slate-200 pl-5 md:ml-6 md:pl-6">
            <UserFolderItem v-for="sub in folder.children_recursive" :key="sub.id" :folder="sub" :is-root="false" />

            <div
                v-for="file in folder.files"
                :key="file.id"
                class="panel-muted flex items-center justify-between gap-4 border p-4"
                :class="isFileLocked(file) ? 'opacity-60' : 'hover:border-blue-200'"
            >
                <div class="flex min-w-0 items-center gap-4">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-900 text-[11px] font-black uppercase tracking-[0.18em] text-white">
                        {{ file.file_type === 'pdf' ? 'PDF' : 'FILE' }}
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold text-slate-700" :class="{ 'line-through text-slate-400': isFileLocked(file) }">
                            {{ file.title }}
                        </p>
                        <p class="mt-1 truncate text-[11px] font-semibold text-slate-500">
                            {{ file.category || 'Uncategorized' }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <span v-if="isFileLocked(file)" class="text-xs font-black uppercase tracking-[0.18em] text-slate-400">Locked</span>
                    <span v-else-if="isTemporarilyUnlocked(file)" class="text-xs font-black uppercase tracking-[0.18em] text-emerald-600">Open Now</span>
                    <template v-else-if="isAuthenticated">
                        <a :href="`/resources/preview/${file.id}`" target="_blank" class="action-btn-secondary">Preview</a>
                        <a :href="`/resources/download/${file.id}`" target="_blank" class="action-btn-primary">Download</a>
                    </template>
                    <template v-else>
                        <button type="button" class="action-btn-secondary" @click="openLoginPrompt(file.title)">Preview</button>
                        <button type="button" class="action-btn-primary" @click="openLoginPrompt(file.title)">Download</button>
                    </template>
                </div>
            </div>

            <div v-if="!folder.children_recursive?.length && !folder.files?.length" class="rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50 p-4 text-center text-xs font-black uppercase tracking-[0.18em] text-slate-400">
                Empty directory
            </div>
        </div>
    </div>
</template>
