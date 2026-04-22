<script setup>
import { onMounted, ref, useAttrs } from 'vue';

defineOptions({
    inheritAttrs: false,
});

const model = defineModel({
    type: String,
    required: true,
});

const attrs = useAttrs();
const input = ref(null);
const showPassword = ref(false);

onMounted(() => {
    if (input.value?.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value?.focus() });
</script>

<template>
    <div class="relative">
        <input
            v-bind="attrs"
            ref="input"
            v-model="model"
            :type="showPassword ? 'text' : 'password'"
            class="field-input !pr-14"
        />

        <button
            type="button"
            class="absolute inset-y-0 right-0 flex w-14 items-center justify-center text-slate-400 transition hover:text-blue-600 focus:outline-none focus:text-blue-600"
            :aria-label="showPassword ? 'Hide password' : 'Show password'"
            :aria-pressed="showPassword"
            @click="showPassword = !showPassword"
        >
            <svg
                v-if="showPassword"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                class="h-5 w-5"
                aria-hidden="true"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M2.5 12S5.73 5.8 12 5.8 21.5 12 21.5 12 18.27 18.2 12 18.2 2.5 12 2.5 12z"
                />
                <circle cx="12" cy="12" r="2.7" />
            </svg>

            <svg
                v-else
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                class="h-5 w-5"
                aria-hidden="true"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" />
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M10.58 10.58A2 2 0 0012 14a2 2 0 001.42-.58M9.88 5.09A9.77 9.77 0 0112 4.8c4.58 0 8.27 2.92 9.5 7.2a10.67 10.67 0 01-4.15 5.53M6.61 6.61A10.7 10.7 0 002.5 12c.54 1.87 1.71 3.57 3.3 4.78"
                />
            </svg>
        </button>
    </div>
</template>
