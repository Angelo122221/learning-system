<script setup>
import PrimaryButton from '@/Components/PrimaryButton.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { toApplicationUrl } from '@/lib/basePath';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: String,
});

const form = useForm({});

const submit = () => {
    form.post(toApplicationUrl(route('verification.send')));
};
</script>

<template>
    <GuestLayout>
        <Head title="Crystal Portal" />

        <div class="mb-8">
            <p class="eyebrow">Email Verification</p>
            <h2 class="mt-3 text-3xl font-black text-slate-950">Verify your email address</h2>
            <p class="mt-3 text-sm font-medium leading-6 text-slate-500">
                Confirm your email to finish setting up your account and unlock the portal.
            </p>
        </div>

        <div v-if="props.status === 'verification-link-sent'" class="mb-4 rounded-2xl border-2 border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
            A new verification link has been sent to your email address.
        </div>

        <form @submit.prevent="submit">
            <div class="mt-4 flex items-center justify-between">
                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Resend Verification Email
                </PrimaryButton>

                <Link :href="toApplicationUrl(route('logout'))" method="post" as="button" class="text-sm font-semibold text-slate-500 underline decoration-slate-300 underline-offset-4 hover:text-slate-900">
                    Log Out
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
