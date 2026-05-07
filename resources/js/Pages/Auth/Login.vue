<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PasswordInput from '@/Components/PasswordInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Crystal Portal" />

    <div class="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(59,130,246,0.12),_transparent_24%),linear-gradient(180deg,#f8fbff_0%,#edf4fb_100%)]">
        <div class="mx-auto flex min-h-screen max-w-7xl flex-col justify-center px-4 py-6 sm:px-6 sm:py-8 lg:px-10">
            <div class="grid items-center gap-8 lg:grid-cols-[0.95fr_1.05fr] lg:gap-12">
                <section class="mx-auto w-full max-w-xl lg:mx-0">
                    <Link href="/" class="inline-flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:gap-6">
                        <img
                            src="/images/crystal-login-logo.png"
                            alt="CRYSTAL Portal official logo"
                            class="h-auto w-24 object-contain sm:w-32 lg:w-36"
                        />

                        <div class="space-y-1.5 sm:space-y-2">
                            <p class="text-[11px] font-black uppercase tracking-[0.28em] text-blue-400 sm:text-xs sm:tracking-[0.34em]">DepEd Resource Portal</p>
                            <h1 class="text-[2.1rem] font-black leading-[0.95] tracking-tight text-slate-950 sm:text-4xl">
                                Learning System
                            </h1>
                            <div class="flex items-center gap-2.5 sm:gap-3">
                                <span class="inline-flex h-2 w-2 rounded-full bg-orange-400 sm:h-2.5 sm:w-2.5" />
                                <p class="text-[11px] font-black uppercase tracking-[0.24em] text-slate-400 sm:text-xs sm:tracking-[0.28em]">Secure Access</p>
                            </div>
                        </div>
                    </Link>

                    <p class="mt-5 max-w-lg text-base font-semibold leading-7 text-slate-700 sm:mt-6 sm:text-lg sm:leading-8">
                        Access learning resources, teaching tools, and digital materials in one place.
                    </p>
                </section>

                <section class="mx-auto w-full max-w-xl">
                    <div class="rounded-[2rem] border border-white/80 bg-white px-5 py-6 shadow-[0_24px_60px_rgba(37,99,235,0.12)] sm:px-8 sm:py-8">
                        <div class="mb-6 sm:mb-7">
                            <div class="flex items-center gap-2.5 sm:gap-3">
                                <span class="inline-flex h-2 w-2 rounded-full bg-orange-400 sm:h-2.5 sm:w-2.5" />
                                <p class="text-[11px] font-black uppercase tracking-[0.24em] text-slate-400 sm:text-xs sm:tracking-[0.3em]">Sign In</p>
                            </div>
                            <h2 class="mt-3 text-[2rem] font-black leading-[0.98] tracking-tight text-slate-950 sm:mt-4 sm:text-3xl">
                                Welcome back
                            </h2>
                            <p class="mt-2.5 text-sm font-medium leading-6 text-slate-500 sm:mt-3 sm:text-base sm:leading-7">
                                Continue to the DepEd learning portal using your official account.
                            </p>
                        </div>

                        <div v-if="status" class="mb-5 rounded-[1.2rem] border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                            {{ status }}
                        </div>

                        <form @submit.prevent="submit" class="space-y-4 sm:space-y-5">
                            <div>
                                <InputLabel for="email" value="DepEd Email" />
                                <TextInput
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    class="mt-2 block w-full !rounded-[1.2rem] !border-slate-200 !bg-slate-50/85 !px-4 !py-3 !text-[15px] !font-semibold placeholder:!font-medium placeholder:!text-slate-400 focus:!border-blue-500 focus:!bg-white sm:!rounded-[1.35rem] sm:!px-5 sm:!py-3.5 sm:!text-base"
                                    required
                                    autofocus
                                    autocomplete="username"
                                    placeholder="name@deped.gov.ph"
                                />
                                <p class="mt-2 text-xs font-medium text-slate-500">Use your official DepEd email address.</p>
                                <InputError class="mt-2" :message="form.errors.email" />
                            </div>

                            <div>
                                <InputLabel for="password" value="Password" />
                                <PasswordInput
                                    id="password"
                                    v-model="form.password"
                                    class="mt-2 block w-full !rounded-[1.2rem] !border-slate-200 !bg-slate-50/85 !px-4 !py-3 !pr-12 !text-[15px] !font-semibold placeholder:!font-medium placeholder:!text-slate-400 focus:!border-blue-500 focus:!bg-white sm:!rounded-[1.35rem] sm:!px-5 sm:!py-3.5 sm:!pr-14 sm:!text-base"
                                    required
                                    autocomplete="current-password"
                                    placeholder="Password"
                                />
                                <InputError class="mt-2" :message="form.errors.password" />
                            </div>

                            <div class="flex flex-col gap-3 pt-1 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                                <label class="inline-flex items-center gap-3 text-sm font-semibold text-slate-600">
                                    <Checkbox name="remember" v-model:checked="form.remember" />
                                    <span>Remember me</span>
                                </label>

                                <Link
                                    v-if="canResetPassword"
                                    :href="route('password.request')"
                                    class="text-sm font-semibold text-slate-500 underline decoration-slate-300 underline-offset-4 hover:text-blue-600"
                                >
                                    Forgot Password?
                                </Link>
                            </div>

                            <div class="border-t border-slate-100 pt-4 sm:pt-5">
                                <PrimaryButton
                                    class="w-full justify-center !rounded-full !px-7 !py-3.5 text-sm shadow-[0_16px_32px_rgba(37,99,235,0.22)] sm:w-auto"
                                    :class="{ 'opacity-25': form.processing }"
                                    :disabled="form.processing"
                                >
                                    Log In
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>
