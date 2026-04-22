<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PasswordInput from '@/Components/PasswordInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    district: '',
    school_name: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Crystal Portal" />

        <div class="mb-8">
            <p class="eyebrow">Teacher Registration</p>
            <h2 class="mt-3 text-3xl font-black text-slate-950">Create a DepEd teacher account</h2>
            <p class="mt-3 text-sm font-medium leading-6 text-slate-500">
                Complete your profile so the system can route resources and analytics to the right district and school.
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <InputLabel for="name" value="Full Name" />
                <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required autofocus autocomplete="name" />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="DepEd Email" />
                <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full" required autocomplete="username" />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <InputLabel for="district" value="District" />
                <TextInput id="district" v-model="form.district" type="text" class="mt-1 block w-full" required />
                <InputError class="mt-2" :message="form.errors.district" />
            </div>

            <div>
                <InputLabel for="school_name" value="School Name" />
                <TextInput id="school_name" v-model="form.school_name" type="text" class="mt-1 block w-full" required />
                <InputError class="mt-2" :message="form.errors.school_name" />
            </div>

            <div>
                <InputLabel for="password" value="Password" />
                <PasswordInput id="password" v-model="form.password" class="mt-1 block w-full" required autocomplete="new-password" />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div>
                <InputLabel for="password_confirmation" value="Confirm Password" />
                <PasswordInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    class="mt-1 block w-full"
                    required
                    autocomplete="new-password"
                />
                <InputError class="mt-2" :message="form.errors.password_confirmation" />
            </div>

            <div class="flex items-center justify-end">
                <Link :href="route('login')" class="text-sm font-semibold text-slate-500 underline decoration-slate-300 underline-offset-4 hover:text-slate-900">
                    Already registered?
                </Link>

                <PrimaryButton class="ms-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Register
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
