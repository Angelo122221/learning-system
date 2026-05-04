<script setup>
defineProps({
    headers: {
        type: Array,
        required: true,
    },
    minWidth: {
        type: String,
        default: 'min-w-[720px]',
    },
    emptyText: {
        type: String,
        default: 'No records found.',
    },
    rows: {
        type: Array,
        default: () => [],
    },
    wrapperClass: {
        type: String,
        default: '',
    },
    tableClass: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div class="custom-scrollbar overflow-x-auto" :class="wrapperClass">
        <table class="data-table" :class="[minWidth, tableClass]">
            <thead>
                <tr>
                    <th v-for="header in headers" :key="header.key" :class="header.class">
                        {{ header.label }}
                    </th>
                </tr>
            </thead>
            <tbody>
                <template v-if="rows.length">
                    <slot />
                </template>
                <tr v-else>
                    <td :colspan="headers.length" class="py-8 text-center text-sm font-semibold text-slate-400">
                        {{ emptyText }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
