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
    description: '',
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
    { key: 'name', label: 'Material Name' },
    { key: 'description', label: 'Description' },
    { key: 'created_at', label: 'Created' },
    { key: 'actions', label: 'Actions' },
];

const submissionHeaders = [
    { key: 'material_name', label: 'Material' },
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
                    subtitle="Enter the material name and a short description so teachers can report available quantities."
                >
                    <form class="space-y-4" @submit.prevent="submitMaterial">
                        <div>
                            <label class="field-label" for="material_name">Material Name</label>
                            <input
                                id="material_name"
                                v-model="materialForm.name"
                                type="text"
                                class="field-input"
                                placeholder="Grade 4 Math Module"
                            />
                            <InputError :message="materialForm.errors.name" />
                        </div>

                        <div>
                            <label class="field-label" for="material_description">Description</label>
                            <textarea
                                id="material_description"
                                v-model="materialForm.description"
                                rows="4"
                                class="field-input"
                                placeholder="Short details about this learning material."
                            />
                            <InputError :message="materialForm.errors.description" />
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
                        min-width="min-w-[760px]"
                    >
                        <tr v-for="material in materials" :key="material.id">
                            <td class="font-black text-slate-900">{{ material.name }}</td>
                            <td class="max-w-[28rem] text-sm font-medium leading-6 text-slate-600">
                                {{ material.description || 'No description' }}
                            </td>
                            <td class="text-sm font-semibold text-slate-500">
                                {{ formatDateTime(material.created_at) }}
                            </td>
                            <td>
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
                            <label class="field-label" for="filter_material">Material Name</label>
                            <input
                                id="filter_material"
                                v-model="filterForm.material"
                                type="text"
                                class="field-input"
                                placeholder="Search by material name"
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
                                {{ submission.material_description || 'No description' }}
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
