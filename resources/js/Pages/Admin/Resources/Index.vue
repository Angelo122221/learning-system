<script setup>
import AppEmptyState from '@/Components/AppEmptyState.vue';
import AppSectionCard from '@/Components/AppSectionCard.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import FolderItem from './FolderItem.vue';

const props = defineProps({
    folders: Array,
    allFolders: Array,
    uploadLimits: Object,
    resourceCategories: Array,
});

const folderForm = useForm({
    name: '',
    parent_id: null,
    description: '',
});

const fileForm = useForm({
    title: '',
    category: '',
    folder_id: '',
    file: null,
});

const showFolderModal = ref(false);
const showFileModal = ref(false);
const showUnlockWindowModal = ref(false);
const uploadFileInput = ref(null);
const unlockWindowTarget = ref(null);

const unlockWindowForm = useForm({
    start_at: '',
    duration_value: 1,
    duration_unit: 'days',
});

const FILE_LIMIT_FALLBACK_BYTES = 100 * 1024 * 1024;
const RESOURCE_CATEGORIES_FALLBACK = [
    'Ebook',
    'Test Questionnaires',
    'Modules',
    'Learning Videos',
    'Story Telling Videos',
    'Story Books',
];

const toReadableSize = (bytes) => {
    if (bytes >= 1024 * 1024 * 1024) {
        return `${(bytes / (1024 * 1024 * 1024)).toFixed(1)} GB`;
    }

    if (bytes >= 1024 * 1024) {
        return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    }

    if (bytes >= 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }

    return `${bytes} B`;
};

const maxFileBytes = computed(() => {
    return props.uploadLimits?.max_file_bytes ?? FILE_LIMIT_FALLBACK_BYTES;
});
const resourceCategoryOptions = computed(() => {
    if (Array.isArray(props.resourceCategories) && props.resourceCategories.length > 0) {
        return props.resourceCategories;
    }

    return RESOURCE_CATEGORIES_FALLBACK;
});

const isVideoCategorySelected = computed(() => {
    return String(fileForm.category ?? '').toLowerCase().includes('video');
});

const fileMaxSizeLabel = computed(() => {
    if (isVideoCategorySelected.value) {
        return props.uploadLimits?.video_file_limit_label ?? 'No app limit for video files';
    }

    return props.uploadLimits?.max_file_label ?? toReadableSize(maxFileBytes.value);
});

const setMainFile = (event) => {
    const files = Array.from(event.target?.files || []);

    if (files.length > 1) {
        fileForm.file = null;
        fileForm.setError('file', 'Please select only one file.');
        event.target.value = '';
        return;
    }

    const selectedFile = files[0] || null;
    if (!isVideoCategorySelected.value && selectedFile && selectedFile.size > maxFileBytes.value) {
        fileForm.file = null;
        fileForm.setError('file', `File exceeds the limit (${fileMaxSizeLabel.value} max).`);
        event.target.value = '';
        return;
    }

    fileForm.clearErrors('file');
    fileForm.file = selectedFile;
};

const openFolderModal = () => {
    folderForm.reset();
    folderForm.clearErrors();
    folderForm.parent_id = null;
    showFolderModal.value = true;
};

const openFolderModalWithParent = (folderId) => {
    folderForm.reset();
    folderForm.clearErrors();
    folderForm.parent_id = folderId;
    showFolderModal.value = true;
};

const closeFolderModal = () => {
    showFolderModal.value = false;
    folderForm.reset();
    folderForm.clearErrors();
};

const openFileModal = () => {
    fileForm.reset();
    fileForm.clearErrors();
    if (uploadFileInput.value) uploadFileInput.value.value = '';
    fileForm.folder_id = '';
    showFileModal.value = true;
};

const openFileModalWithFolder = (folderId) => {
    fileForm.reset();
    fileForm.clearErrors();
    if (uploadFileInput.value) uploadFileInput.value.value = '';
    fileForm.folder_id = folderId;
    showFileModal.value = true;
};

const closeFileModal = () => {
    showFileModal.value = false;
    fileForm.reset();
    fileForm.clearErrors();
    if (uploadFileInput.value) uploadFileInput.value.value = '';
};

const submitFolder = () => {
    folderForm.post('/admin/folders', {
        preserveScroll: true,
        onSuccess: () => closeFolderModal(),
    });
};

const submitFile = () => {
    fileForm.post('/admin/files', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => closeFileModal(),
    });
};

const buildResourceActionPath = (type, id, action = 'delete') => {
    if (!id && id !== 0) {
        return null;
    }

    const baseMap = {
        folder: `/admin/folders/${id}`,
        file: `/admin/files/${id}`,
    };

    const basePath = baseMap[type];
    if (!basePath) {
        return null;
    }

    if (action === 'toggle-lock') {
        return `${basePath}/toggle-lock`;
    }

    return basePath;
};

const deleteItem = (type, id) => {
    if (!confirm(`Permanently delete this ${type}?`)) return;

    const path = buildResourceActionPath(type, id);
    if (!path) {
        console.error('Invalid delete action target:', { type, id });
        return;
    }

    router.post(path, { _method: 'delete' }, { preserveScroll: true });
};

const toggleLock = (type, id) => {
    const path = buildResourceActionPath(type, id, 'toggle-lock');
    if (!path) {
        console.error('Invalid lock action target:', { type, id });
        return;
    }

    router.post(path, { _method: 'patch' }, { preserveScroll: true });
};

const searchQuery = ref('');

const normalizeSearchValue = (value) => String(value ?? '').toLowerCase();

const filterFolderTree = (folders, normalizedQuery) => {
    if (!normalizedQuery) {
        return folders ?? [];
    }

    return (folders ?? [])
        .map((folder) => {
            const folderMatches = normalizeSearchValue(folder?.name).includes(normalizedQuery);

            const matchingFiles = (folder?.files ?? []).filter((file) => {
                return [
                    file?.title,
                    file?.file_type,
                    file?.category,
                ].some((field) => normalizeSearchValue(field).includes(normalizedQuery));
            });

            const matchingChildren = filterFolderTree(folder?.children_recursive ?? [], normalizedQuery);

            if (!folderMatches && matchingFiles.length === 0 && matchingChildren.length === 0) {
                return null;
            }

            return {
                ...folder,
                files: folderMatches ? (folder?.files ?? []) : matchingFiles,
                children_recursive: folderMatches ? (folder?.children_recursive ?? []) : matchingChildren,
            };
        })
        .filter((folder) => folder !== null);
};

const hasActiveSearch = computed(() => searchQuery.value.trim().length > 0);

const filteredFolders = computed(() => {
    const normalizedQuery = hasActiveSearch.value
        ? searchQuery.value.trim().toLowerCase()
        : '';

    return filterFolderTree(props.folders, normalizedQuery);
});

const toDateTimeLocalInput = (value) => {
    if (!value) return '';

    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) return '';

    const pad = (part) => String(part).padStart(2, '0');
    const year = parsed.getFullYear();
    const month = pad(parsed.getMonth() + 1);
    const day = pad(parsed.getDate());
    const hour = pad(parsed.getHours());
    const minute = pad(parsed.getMinutes());

    return `${year}-${month}-${day}T${hour}:${minute}`;
};

const toDisplayDateTime = (value) => {
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

const unlockWindowSummary = computed(() => {
    if (!unlockWindowTarget.value?.unlock_starts_at || !unlockWindowTarget.value?.unlock_ends_at) {
        return '';
    }

    return `${toDisplayDateTime(unlockWindowTarget.value.unlock_starts_at)} - ${toDisplayDateTime(unlockWindowTarget.value.unlock_ends_at)}`;
});

const openUnlockWindowModal = (type, item) => {
    unlockWindowTarget.value = {
        type,
        id: item.id,
        name: item.name ?? item.title ?? `Selected ${type}`,
        unlock_starts_at: item.unlock_starts_at ?? null,
        unlock_ends_at: item.unlock_ends_at ?? null,
    };

    unlockWindowForm.reset();
    unlockWindowForm.clearErrors();
    unlockWindowForm.duration_value = 1;
    unlockWindowForm.duration_unit = 'days';
    unlockWindowForm.start_at = toDateTimeLocalInput(item.unlock_starts_at) || toDateTimeLocalInput(new Date().toISOString());
    showUnlockWindowModal.value = true;
};

const closeUnlockWindowModal = () => {
    showUnlockWindowModal.value = false;
    unlockWindowTarget.value = null;
    unlockWindowForm.reset();
    unlockWindowForm.clearErrors();
};

const unlockWindowPath = (target) => {
    if (!target?.id || !target?.type) {
        return null;
    }

    const base = target.type === 'folder' ? '/admin/folders' : '/admin/files';

    return `${base}/${target.id}/unlock-window`;
};

const submitUnlockWindow = () => {
    const path = unlockWindowPath(unlockWindowTarget.value);
    if (!path) {
        return;
    }

    unlockWindowForm.patch(path, {
        preserveScroll: true,
        onSuccess: () => closeUnlockWindowModal(),
    });
};

const clearUnlockWindow = () => {
    const path = unlockWindowPath(unlockWindowTarget.value);
    if (!path) {
        return;
    }

    router.patch(path, {
        clear: true,
    }, {
        preserveScroll: true,
        onSuccess: () => closeUnlockWindowModal(),
    });
};
</script>

<template>
    <Head title="Admin Resources" />

    <AdminLayout>
        <AppSectionCard title="Resource Explorer" subtitle="Review the current folder structure and lock or delete items.">
    <div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
    <div class="flex flex-wrap gap-2">
        <button
            type="button"
            class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-bold text-white shadow-md transition hover:bg-blue-700"
            @click="openFolderModal"
        >
            Add Folder
        </button>

        <button
            type="button"
            class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-bold text-white shadow-md transition hover:bg-emerald-700"
            @click="openFileModal"
        >
            Upload File
        </button>
    </div>

    <div class="w-full lg:w-96">
        <input
            v-model="searchQuery"
            type="text"
            placeholder="Search folder or file name..."
            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
        />
    </div>
</div>

            <div class="custom-scrollbar max-h-[72vh] overflow-y-auto pr-2">
                <AppEmptyState
    v-if="filteredFolders.length === 0"
    title="No matching resources"
    message="Try searching for another folder or file name."
/>

<div v-else class="space-y-3">
    <FolderItem
        v-for="folder in filteredFolders"
        :key="folder.id"
        :folder="folder"
        :auto-expand="hasActiveSearch"
        @delete="deleteItem"
        @lock="toggleLock"
        @add="openFolderModalWithParent"
        @upload="openFileModalWithFolder"
        @schedule="openUnlockWindowModal"
    />
</div>
            </div>
        </AppSectionCard>

        <Modal :show="showFolderModal" max-width="lg" @close="closeFolderModal">
            <div class="p-5 md:p-6">
                <h3 class="text-lg font-black text-slate-900">Create Folder</h3>
                <p class="mt-1 text-sm font-medium text-slate-500">Set a root folder or nested folder for resource organization.</p>

                <form @submit.prevent="submitFolder" class="mt-5 space-y-4">
                    <div>
                        <label class="field-label" for="folder_name">Folder Name</label>
                        <input id="folder_name" v-model="folderForm.name" type="text" class="field-input" placeholder="Quarter 1 Materials" />
                        <InputError :message="folderForm.errors.name" />
                    </div>

                    <div>
                        <label class="field-label" for="parent_folder">Parent Folder</label>
                        <select id="parent_folder" v-model="folderForm.parent_id" class="field-input">
                            <option :value="null">Root Directory</option>
                            <option v-for="f in allFolders" :key="f.id" :value="f.id">{{ f.full_path }}</option>
                        </select>
                        <InputError :message="folderForm.errors.parent_id" />
                    </div>

                    <div>
                        <label class="field-label" for="folder_description">Description</label>
                        <textarea id="folder_description" v-model="folderForm.description" rows="3" class="field-input" placeholder="Short context for teachers." />
                        <InputError :message="folderForm.errors.description" />
                    </div>

                    <div class="flex flex-wrap justify-end gap-2">
                        <button type="button" class="action-btn-secondary" @click="closeFolderModal">Cancel</button>
                        <button type="submit" class="action-btn-primary">Add Folder</button>
                    </div>
                </form>
            </div>
        </Modal>

        <Modal :show="showFileModal" max-width="lg" @close="closeFileModal">
            <div class="p-5 md:p-6">
                <h3 class="text-lg font-black text-slate-900">Upload File</h3>
                <p class="mt-1 text-sm font-medium text-slate-500">Upload one main resource file.</p>

                <form @submit.prevent="submitFile" class="mt-5 space-y-4">
                    <div>
                        <label class="field-label" for="upload_folder">Folder</label>
                        <select id="upload_folder" v-model="fileForm.folder_id" class="field-input">
                            <option value="">Select Folder</option>
                            <option v-for="f in allFolders" :key="f.id" :value="f.id">{{ f.full_path }}</option>
                        </select>
                        <InputError :message="fileForm.errors.folder_id" />
                    </div>

                    <div>
                        <label class="field-label" for="file_title">File Title</label>
                        <input id="file_title" v-model="fileForm.title" type="text" class="field-input" placeholder="Mathematics Curriculum Guide" />
                        <InputError :message="fileForm.errors.title" />
                    </div>

                    <div>
                        <label class="field-label" for="file_category">Resource Category</label>
                        <select id="file_category" v-model="fileForm.category" class="field-input" required>
                            <option value="">Select Category</option>
                            <option v-for="category in resourceCategoryOptions" :key="category" :value="category">
                                {{ category }}
                            </option>
                        </select>
                        <InputError :message="fileForm.errors.category" />
                    </div>

                    <div>
                        <label class="field-label" for="resource_file">Main File</label>
                        <input
                            id="resource_file"
                            ref="uploadFileInput"
                            type="file"
                            accept="video/*,.pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.csv,.txt,.zip,.rar,.7z,.jpg,.jpeg,.png,.gif,.webp"
                            class="field-input"
                            @change="setMainFile($event)"
                        />
                        <p class="field-help mt-2">Max file size: {{ fileMaxSizeLabel }}</p>
                        <InputError :message="fileForm.errors.file" />
                    </div>

                    <div class="flex flex-wrap justify-end gap-2">
                        <button type="button" class="action-btn-secondary" @click="closeFileModal">Cancel</button>
                        <button type="submit" class="action-btn-primary">Upload File</button>
                    </div>
                </form>
            </div>
        </Modal>

        <Modal :show="showUnlockWindowModal" max-width="lg" @close="closeUnlockWindowModal">
            <div class="p-5 md:p-6">
                <h3 class="text-lg font-black text-slate-900">Timed Unlock Window</h3>
                <p class="mt-1 text-sm font-medium text-slate-500">
                    Keep this {{ unlockWindowTarget?.type || 'resource' }} locked by default but temporarily unlocked during the selected window.
                </p>

                <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Selected Item</p>
                    <p class="mt-1 text-sm font-black text-slate-900">{{ unlockWindowTarget?.name }}</p>
                    <p v-if="unlockWindowSummary" class="mt-2 text-xs font-semibold text-blue-600">
                        Existing window: {{ unlockWindowSummary }}
                    </p>
                </div>

                <form @submit.prevent="submitUnlockWindow" class="mt-5 space-y-4">
                    <div>
                        <label class="field-label" for="unlock_start_at">Unlock Start</label>
                        <input
                            id="unlock_start_at"
                            v-model="unlockWindowForm.start_at"
                            type="datetime-local"
                            class="field-input"
                            required
                        />
                        <InputError :message="unlockWindowForm.errors.start_at" />
                    </div>

                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div>
                            <label class="field-label" for="unlock_duration_value">Duration Value</label>
                            <input
                                id="unlock_duration_value"
                                v-model.number="unlockWindowForm.duration_value"
                                type="number"
                                min="1"
                                max="365"
                                class="field-input"
                                required
                            />
                            <InputError :message="unlockWindowForm.errors.duration_value" />
                        </div>

                        <div>
                            <label class="field-label" for="unlock_duration_unit">Duration Unit</label>
                            <select id="unlock_duration_unit" v-model="unlockWindowForm.duration_unit" class="field-input" required>
                                <option value="days">Days</option>
                                <option value="weeks">Weeks</option>
                                <option value="months">Months</option>
                            </select>
                            <InputError :message="unlockWindowForm.errors.duration_unit" />
                        </div>
                    </div>

                    <div class="flex flex-wrap justify-between gap-2">
                        <button
                            type="button"
                            class="action-btn-secondary"
                            :disabled="!unlockWindowTarget?.unlock_starts_at || unlockWindowForm.processing"
                            @click="clearUnlockWindow"
                        >
                            Clear Schedule
                        </button>

                        <div class="flex flex-wrap gap-2">
                            <button type="button" class="action-btn-secondary" @click="closeUnlockWindowModal">
                                Cancel
                            </button>
                            <button type="submit" class="action-btn-primary" :disabled="unlockWindowForm.processing">
                                Save Timed Unlock
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </Modal>
    </AdminLayout>
</template>
