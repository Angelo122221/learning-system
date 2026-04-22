<script setup>
const props = defineProps({
    items: {
        type: Array,
        required: true,
    },
    showHeading: {
        type: Boolean,
        default: true,
    },
});
</script>

<template>
    <section :aria-labelledby="props.showHeading ? 'deped-systems-heading' : undefined" :aria-label="props.showHeading ? undefined : 'DepEd Systems'">
        <div v-if="props.showHeading" class="mb-3">
            <p id="deped-systems-heading" class="text-[11px] font-black uppercase tracking-[0.22em] text-blue-600">DepEd Systems</p>
        </div>

        <div class="grid justify-items-center grid-cols-1 gap-y-3 sm:grid-cols-2 sm:gap-x-5 lg:grid-cols-4 lg:gap-x-6">
            <a
                v-for="item in items"
                :key="item.href"
                :href="item.href"
                target="_blank"
                rel="noopener noreferrer"
                class="group flex justify-center focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2"
                :aria-label="item.title"
            >
                <img
                    :src="item.banner"
                    :alt="`${item.title} banner`"
                    class="block h-auto w-[13.5rem] max-w-full object-contain sm:w-[14rem] lg:w-[14.5rem]"
                />
            </a>
        </div>
    </section>
</template>
