<script setup>
import AppDataTable from '@/Components/AppDataTable.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import AppPageHeader from '@/Components/AppPageHeader.vue';
import AppSectionCard from '@/Components/AppSectionCard.vue';
import InputError from '@/Components/InputError.vue';
import UserLayout from '@/Layouts/UserLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    materials: {
        type: Array,
        default: () => [],
    },
    loadError: {
        type: String,
        default: '',
    },
});

const page = usePage();
const savingMaterialId = ref(null);

const quantityError = computed(() => page.props.errors?.quantity ?? '');

const buildSelectedQuantities = () => {
    return Object.fromEntries(
        (props.materials ?? []).map((material) => [
            material.id,
            String(material.quantity ?? 0),
        ]),
    );
};

const selectedQuantities = ref(buildSelectedQuantities());

watch(
    () => props.materials,
    () => {
        selectedQuantities.value = buildSelectedQuantities();
    },
    { deep: true },
);

const tableHeaders = [
    { key: 'material', label: 'Learning Material' },
    { key: 'description', label: 'Description' },
    { key: 'quantity', label: 'Available Quantity' },
    { key: 'actions', label: 'Action' },
];

const saveQuantity = (materialId) => {
    const parsedValue = Number.parseInt(selectedQuantities.value[materialId] ?? '0', 10);
    const quantity = Number.isNaN(parsedValue) ? 0 : parsedValue;

    savingMaterialId.value = materialId;

    router.post(`/materials/${materialId}/quantity`, {
        quantity,
    }, {
        preserveScroll: true,
        onFinish: () => {
            savingMaterialId.value = null;
        },
    });
};
</script>

<template>
    <Head title="Materials Inventory" />

    <UserLayout>
        <section class="space-y-6">
            <AppPageHeader
                title="Materials"
                accent="Inventory"
                subtitle="Choose the quantity you currently have for each learning material, then save."
            />

            <div
                v-if="loadError"
                class="rounded-2xl border-2 border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800"
            >
                {{ loadError }}
            </div>

            <AppSectionCard
                title="Report Available Learning Materials"
                subtitle="Update your school inventory so division-level summaries stay accurate."
            >
                <InputError :message="quantityError" />

                <AppEmptyState
                    v-if="materials.length === 0"
                    title="No materials available yet"
                    message="Your admin has not added learning materials to the inventory list."
                />

                <AppDataTable
                    v-else
                    :headers="tableHeaders"
                    :rows="materials"
                    min-width="min-w-[920px]"
                >
                    <tr v-for="material in materials" :key="material.id">
                        <td class="font-black text-slate-900">{{ material.name }}</td>
                        <td class="max-w-[30rem] text-sm font-medium leading-6 text-slate-600">
                            {{ material.description || 'No description' }}
                        </td>
                        <td>
                            <input
                                v-model="selectedQuantities[material.id]"
                                type="number"
                                min="0"
                                max="9999"
                                step="1"
                                class="field-input !mt-0 w-full min-w-[8rem]"
                            />
                        </td>
                        <td>
                            <button
                                type="button"
                                class="action-btn-primary"
                                :disabled="savingMaterialId === material.id"
                                @click="saveQuantity(material.id)"
                            >
                                {{ savingMaterialId === material.id ? 'Saving...' : 'Save Quantity' }}
                            </button>
                        </td>
                    </tr>
                </AppDataTable>
            </AppSectionCard>
        </section>
    </UserLayout>
</template>
