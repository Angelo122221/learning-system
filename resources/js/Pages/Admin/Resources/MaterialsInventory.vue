<script setup>
import AppDataTable from '@/Components/AppDataTable.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import AppFormSection from '@/Components/AppFormSection.vue';
import AppPageHeader from '@/Components/AppPageHeader.vue';
import AppSectionCard from '@/Components/AppSectionCard.vue';
import AppStatCard from '@/Components/AppStatCard.vue';
import InputError from '@/Components/InputError.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    materials: {
        type: Array,
        default: () => [],
    },
    submissions: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({
            district: '',
            school: '',
            material: '',
        }),
    },
    filterOptions: {
        type: Object,
        default: () => ({
            districts: [],
            schools: [],
        }),
    },
    stats: {
        type: Object,
        default: () => ({
            total_materials: 0,
            total_submissions: 0,
            total_quantity: 0,
        }),
    },
    loadError: {
        type: String,
        default: '',
    },
});

const materialForm = useForm({
    name: '',
    author: '',
    learning_area: '',
    grade_level: '',
    resource_type: '',
    publication_date: '',
    publisher: '',
});

const filterForm = ref({
    district: props.filters?.district ?? '',
    school: props.filters?.school ?? '',
    material: props.filters?.material ?? '',
});

watch(
    () => filterForm.value.district,
    () => {
        if (filterForm.value.district === '') {
            filterForm.value.school = '';
        }
    },
);

watch(
    () => props.filters,
    (nextFilters) => {
        filterForm.value = {
            district: nextFilters?.district ?? '',
            school: nextFilters?.school ?? '',
            material: nextFilters?.material ?? '',
        };
    },
    { deep: true },
);

const materialHeaders = [
    { key: 'title', label: 'Title' },
    { key: 'author', label: 'Author' },
    { key: 'learning_area', label: 'Learning Area' },
    { key: 'grade_level', label: 'Grade Level' },
    { key: 'resource_type', label: 'Type of Resources' },
    { key: 'publication_date', label: 'Date of Publication (copyright)' },
    { key: 'publisher', label: 'Publisher' },
    { key: 'actions', label: 'Actions', class: 'text-right' },
];

const submissionHeaders = [
    { key: 'material_name', label: 'Title' },
    { key: 'teacher_name', label: 'Teacher' },
    { key: 'district', label: 'District' },
    { key: 'school_name', label: 'School' },
    { key: 'quantity', label: 'Quantity' },
    { key: 'updated_at', label: 'Updated' },
];

const hasActiveFilters = computed(() => {
    return (filterForm.value.district || filterForm.value.school || filterForm.value.material);
});

const formatDateTime = (value) => {
    if (!value) return 'N/A';

    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) return 'N/A';

    return parsed.toLocaleString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
};

const formatDateOnly = (value) => {
    if (!value) return 'N/A';

    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) return 'N/A';

    return parsed.toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const submitMaterial = () => {
    materialForm.post('/admin/materials-inventory', {
        preserveScroll: true,
        onSuccess: () => materialForm.reset(),
    });
};

const applyFilters = () => {
    router.get('/admin/materials-inventory', {
        district: filterForm.value.district || undefined,
        school: filterForm.value.school || undefined,
        material: filterForm.value.material || undefined,
    }, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
};

const clearFilters = () => {
    filterForm.value = {
        district: '',
        school: '',
        material: '',
    };

    applyFilters();
};

const deleteMaterial = (materialId) => {
    if (!confirm('Delete this learning material? Existing teacher submissions for it will also be removed.')) {
        return;
    }

    router.post(`/admin/materials-inventory/${materialId}`, {
        _method: 'delete',
    }, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Learning Materials Inventory" />

    <AdminLayout>
        <AppPageHeader
            title="Learning Materials"
            accent="Inventory"
            subtitle="Add official learning materials and review teacher quantity submissions across districts and schools."
        >
            <template #stats>
                <div class="grid w-full gap-3 md:grid-cols-3 xl:w-auto">
                    <AppStatCard label="Materials" :value="stats.total_materials" tone="slate" />
                    <AppStatCard label="Submissions" :value="stats.total_submissions" tone="blue" />
                    <AppStatCard label="Total Quantity" :value="stats.total_quantity" tone="emerald" />
                </div>
            </template>
        </AppPageHeader>

        <div
            v-if="loadError"
            class="mb-6 rounded-2xl border-2 border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800"
        >
            {{ loadError }}
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-12 xl:items-start">
            <div class="xl:col-span-4">
                <AppFormSection
                    title="Add Learning Material"
                    subtitle="Enter bibliographic details so inventory materials follow the required column format."
                >
                    <form class="space-y-4" @submit.prevent="submitMaterial">
                        <div>
                            <label class="field-label" for="material_name">Title</label>
                            <input
                                id="material_name"
                                v-model="materialForm.name"
                                type="text"
                                class="field-input"
                                placeholder="Grade 4 Math Module"
                            />
                            <InputError :message="materialForm.errors.name" />
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="field-label" for="material_author">Author</label>
                                <input
                                    id="material_author"
                                    v-model="materialForm.author"
                                    type="text"
                                    class="field-input"
                                    placeholder="Author name"
                                />
                                <InputError :message="materialForm.errors.author" />
                            </div>

                            <div>
                                <label class="field-label" for="material_learning_area">Learning Area</label>
                                <input
                                    id="material_learning_area"
                                    v-model="materialForm.learning_area"
                                    type="text"
                                    class="field-input"
                                    placeholder="Mathematics"
                                />
                                <InputError :message="materialForm.errors.learning_area" />
                            </div>

                            <div>
                                <label class="field-label" for="material_grade_level">Grade Level</label>
                                <input
                                    id="material_grade_level"
                                    v-model="materialForm.grade_level"
                                    type="text"
                                    class="field-input"
                                    placeholder="Grade 4"
                                />
                                <InputError :message="materialForm.errors.grade_level" />
                            </div>

                            <div>
                                <label class="field-label" for="material_resource_type">Type of Resources</label>
                                <input
                                    id="material_resource_type"
                                    v-model="materialForm.resource_type"
                                    type="text"
                                    class="field-input"
                                    placeholder="Module"
                                />
                                <InputError :message="materialForm.errors.resource_type" />
                            </div>

                            <div>
                                <label class="field-label" for="material_publication_date">Date of Publication (copyright)</label>
                                <input
                                    id="material_publication_date"
                                    v-model="materialForm.publication_date"
                                    type="date"
                                    class="field-input"
                                />
                                <InputError :message="materialForm.errors.publication_date" />
                            </div>

                            <div>
                                <label class="field-label" for="material_publisher">Publisher</label>
                                <input
                                    id="material_publisher"
                                    v-model="materialForm.publisher"
                                    type="text"
                                    class="field-input"
                                    placeholder="Publisher name"
                                />
                                <InputError :message="materialForm.errors.publisher" />
                            </div>
                        </div>

                        <button type="submit" class="action-btn-primary w-full justify-center">
                            Add Material
                        </button>
                    </form>
                </AppFormSection>
            </div>

            <div class="xl:col-span-8">
                <AppSectionCard
                    title="Current Materials"
                    subtitle="Use this list to verify active items available for teacher inventory reporting."
                >
                    <AppEmptyState
                        v-if="materials.length === 0"
                        title="No learning materials yet"
                        message="Add your first material using the form on the left."
                    />

                    <AppDataTable
                        v-else
                        :headers="materialHeaders"
                        :rows="materials"
                        min-width="min-w-[1320px]"
                    >
                        <tr v-for="material in materials" :key="material.id">
                            <td>
                                <p class="font-black text-slate-900">{{ material.name }}</p>
                            </td>
                            <td class="text-sm font-semibold text-slate-600">{{ material.author || 'N/A' }}</td>
                            <td class="text-sm font-semibold text-slate-600">{{ material.learning_area || 'N/A' }}</td>
                            <td class="text-sm font-semibold text-slate-600">{{ material.grade_level || 'N/A' }}</td>
                            <td class="text-sm font-semibold text-slate-600">{{ material.resource_type || 'N/A' }}</td>
                            <td class="text-sm font-semibold text-slate-500">
                                {{ formatDateOnly(material.publication_date) }}
                            </td>
                            <td class="text-sm font-semibold text-slate-600">{{ material.publisher || 'N/A' }}</td>
                            <td class="text-right">
                                <button type="button" class="action-btn-danger" @click="deleteMaterial(material.id)">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    </AppDataTable>
                </AppSectionCard>
            </div>
        </div>

        <div class="mt-6">
            <AppSectionCard
                title="Teacher Inventory Submissions"
                subtitle="Filter teacher-reported quantities by district, school, or material name."
            >
                <div class="panel-muted mb-5 border p-4">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                        <div>
                            <label class="field-label" for="filter_district">District</label>
                            <select id="filter_district" v-model="filterForm.district" class="field-input">
                                <option value="">All Districts</option>
                                <option v-for="district in filterOptions.districts || []" :key="district" :value="district">
                                    {{ district }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="field-label" for="filter_school">School</label>
                            <select id="filter_school" v-model="filterForm.school" class="field-input">
                                <option value="">All Schools</option>
                                <option v-for="school in filterOptions.schools || []" :key="school" :value="school">
                                    {{ school }}
                                </option>
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label class="field-label" for="filter_material">Title</label>
                            <input
                                id="filter_material"
                                v-model="filterForm.material"
                                type="text"
                                class="field-input"
                                placeholder="Search by title"
                            />
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <button type="button" class="action-btn-primary" @click="applyFilters">
                            Apply Filters
                        </button>
                        <button
                            type="button"
                            class="action-btn-secondary"
                            :disabled="!hasActiveFilters"
                            @click="clearFilters"
                        >
                            Clear
                        </button>
                    </div>
                </div>

                <AppDataTable :headers="submissionHeaders" :rows="submissions" min-width="min-w-[980px]" empty-text="No teacher submissions match the current filters.">
                    <tr v-for="submission in submissions" :key="submission.id">
                        <td>
                            <p class="font-black text-slate-900">{{ submission.material_name }}</p>
                            <p class="mt-1 max-w-[20rem] text-xs font-medium leading-5 text-slate-500">
                                Author: {{ submission.material_author || 'N/A' }}
                            </p>
                        </td>
                        <td class="font-semibold text-slate-700">{{ submission.teacher_name }}</td>
                        <td class="font-semibold text-slate-600">{{ submission.district }}</td>
                        <td class="font-semibold text-slate-600">{{ submission.school_name }}</td>
                        <td class="font-black text-blue-600">{{ submission.quantity }}</td>
                        <td class="text-sm font-semibold text-slate-500">{{ formatDateTime(submission.updated_at) }}</td>
                    </tr>
                </AppDataTable>
            </AppSectionCard>
        </div>
    </AdminLayout>
</template>
