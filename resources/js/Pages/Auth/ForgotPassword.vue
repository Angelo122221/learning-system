<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { toApplicationUrl } from '@/lib/basePath';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    status: String,
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(toApplicationUrl(route('password.email')));
};
</script>

<template>
    <GuestLayout>
        <Head title="Crystal Portal" />

        <div class="mb-8">
            <p class="eyebrow">Password Recovery</p>
            <h2 class="mt-3 text-3xl font-black text-slate-950">Reset your account access</h2>
            <p class="mt-3 text-sm font-medium leading-6 text-slate-500">
                Enter your DepEd email and we will send a secure reset link.
            </p>
        </div>

        <div v-if="status" class="mb-4 rounded-2xl border-2 border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <InputLabel for="email" value="DepEd Email" />
                <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full" required autofocus autocomplete="username" />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="flex items-center justify-end">
                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Email Password Reset Link
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
