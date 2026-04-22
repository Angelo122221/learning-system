<script setup>
const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        default: '',
    },
    eyebrow: {
        type: String,
        default: 'Portal Access',
    },
    items: {
        type: Array,
        required: true,
    },
    layout: {
        type: String,
        default: 'default',
    },
    showSectionIcon: {
        type: Boolean,
        default: true,
    },
    hideTitle: {
        type: Boolean,
        default: false,
    },
    showHeaderDivider: {
        type: Boolean,
        default: true,
    },
    showTitlesOnHoverOnly: {
        type: Boolean,
        default: false,
    },
    flat: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <section
        :class="props.flat
            ? 'px-0 py-1'
            : 'rounded-[1.85rem] border-2 border-slate-200 bg-white px-4 py-5 shadow-[0_18px_35px_rgba(15,23,42,0.07)] md:px-6 md:py-6'"
    >
        <div
            v-if="props.eyebrow || !props.hideTitle || props.subtitle || props.showSectionIcon"
            class="flex items-start justify-between gap-4"
            :class="[
                props.flat ? 'mb-3' : 'mb-4',
                props.showHeaderDivider ? 'border-b border-slate-100 pb-4' : '',
            ]"
        >
            <div>
                <p v-if="props.eyebrow" class="text-[11px] font-black uppercase tracking-[0.22em] text-blue-600">{{ props.eyebrow }}</p>
                <h2 v-if="!props.hideTitle" class="mt-2 text-xl font-black tracking-tight text-slate-950">{{ title }}</h2>
                <p v-if="subtitle" class="mt-2 max-w-3xl text-sm font-medium leading-6 text-slate-500">
                    {{ subtitle }}
                </p>
            </div>

            <span
                v-if="props.showSectionIcon"
                class="hidden h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 ring-1 ring-blue-100 md:inline-flex"
            >
                <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" aria-hidden="true">
                    <path d="M7.5 12.5 12.5 7.5M8.333 7.5H12.5v4.167" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </span>
        </div>

        <div v-if="$slots.prepend" :class="props.flat ? 'mb-3' : 'mb-4'">
            <slot name="prepend" />
        </div>

        <div
            class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4"
            :class="props.flat && props.layout === 'logo-top' ? 'gap-2 sm:gap-3' : 'gap-3'"
        >
            <a
                v-for="item in props.items"
                :key="item.href"
                :href="item.href"
                target="_blank"
                rel="noopener noreferrer"
                :aria-label="props.layout === 'logo-top' && props.showTitlesOnHoverOnly ? item.title : null"
                class="group transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2"
                :class="props.layout === 'logo-top'
                    ? 'relative flex h-full flex-col items-center justify-start rounded-[1rem] px-4 py-3 text-center'
                    : 'flex h-full items-start gap-3 rounded-[1.35rem] border border-slate-200 bg-[linear-gradient(180deg,#ffffff_0%,#f8fbff_100%)] p-4 shadow-[0_10px_24px_rgba(15,23,42,0.05)] hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-[0_14px_28px_rgba(37,99,235,0.10)]'"
            >
                <div
                    class="shrink-0 items-center justify-center"
                    :class="props.layout === 'logo-top'
                        ? 'flex h-[8.75rem] w-[8.75rem] p-1 md:h-40 md:w-40'
                        : 'flex h-14 w-14 rounded-2xl border border-slate-200 bg-white p-2.5 shadow-[inset_0_1px_0_rgba(255,255,255,0.8)]'"
                >
                    <img
                        :src="item.logo"
                        :alt="item.logoAlt"
                        class="h-full w-full object-contain"
                    />
                </div>

                <span
                    v-if="props.layout === 'logo-top' && props.showTitlesOnHoverOnly"
                    class="pointer-events-none absolute left-1/2 top-full z-20 mt-1.5 w-max max-w-[12rem] -translate-x-1/2 rounded-full bg-slate-950 px-3 py-1.5 text-center text-xs font-bold leading-4 text-white opacity-0 shadow-[0_10px_24px_rgba(15,23,42,0.18)] group-hover:opacity-100 group-focus-visible:opacity-100"
                >
                    {{ item.title }}
                </span>

                <div
                    v-if="!(props.layout === 'logo-top' && props.showTitlesOnHoverOnly)"
                    class="min-w-0"
                    :class="props.layout === 'logo-top' ? 'mt-4 w-full' : 'flex-1'"
                >
                    <div
                        :class="props.layout === 'logo-top'
                            ? 'flex justify-center'
                            : 'flex items-start justify-between gap-2'"
                    >
                        <h3
                            class="font-black text-slate-900 transition group-hover:text-blue-700"
                            :class="props.layout === 'logo-top' ? 'text-sm leading-5 md:text-base' : 'text-sm leading-5'"
                        >
                            {{ item.title }}
                        </h3>

                        <svg
                            v-if="props.layout !== 'logo-top'"
                            viewBox="0 0 20 20"
                            fill="none"
                            xmlns="http://www.w3.org/2000/svg"
                            class="mt-0.5 h-4 w-4 shrink-0 text-slate-300 transition group-hover:text-blue-500"
                            aria-hidden="true"
                        >
                            <path d="M7.5 12.5 12.5 7.5M8.333 7.5H12.5v4.167" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>

                    <p
                        v-if="item.description"
                        class="text-xs font-medium leading-5 text-slate-500"
                        :class="props.layout === 'logo-top' ? 'mt-2 text-center' : 'mt-2'"
                    >
                        {{ item.description }}
                    </p>
                </div>
            </a>
        </div>
    </section>
</template>
