<script setup>
import AppFormSection from '@/Components/AppFormSection.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PasswordInput from '@/Components/PasswordInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { toApplicationUrl } from '@/lib/basePath';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    mustVerifyEmail: Boolean,
    status: String,
    account: {
        type: Object,
        default: null,
    },
});

const page = usePage();
const user = computed(() => props.account ?? page.props.auth?.user ?? null);
const isEditingProfile = ref(false);
const isEditingPassword = ref(false);

const profileForm = useForm({
    name: user.value?.name || '',
    email: user.value?.email || '',
    district: user.value?.district || '',
    school_name: user.value?.school_name || '',
});

watch(user, (value) => {
    if (!value) {
        return;
    }

    if (!profileForm.name) {
        profileForm.name = value.name || '';
    }

    if (!profileForm.email) {
        profileForm.email = value.email || '';
    }

    if (!profileForm.district) {
        profileForm.district = value.district || '';
    }

    if (!profileForm.school_name) {
        profileForm.school_name = value.school_name || '';
    }
}, { immediate: true });

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const resetProfileForm = () => {
    profileForm.defaults({
        name: user.value?.name || '',
        email: user.value?.email || '',
        district: user.value?.district || '',
        school_name: user.value?.school_name || '',
    });
    profileForm.reset();
    profileForm.clearErrors();
};

const startProfileEdit = () => {
    resetProfileForm();
    isEditingProfile.value = true;
};

const cancelProfileEdit = () => {
    resetProfileForm();
    isEditingProfile.value = false;
};

const updateProfile = () => {
    profileForm
        .transform((data) => ({
            ...data,
            _method: 'patch',
        }))
        .post(toApplicationUrl(route('profile.update')), {
            onSuccess: () => {
                isEditingProfile.value = false;
            },
        });
};

const updatePassword = () => {
    passwordForm
        .transform((data) => ({
            ...data,
            _method: 'put',
        }))
        .post(toApplicationUrl(route('password.update')), {
            onSuccess: () => {
                passwordForm.reset();
                isEditingPassword.value = false;
            },
        });
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Crystal Portal" />

        <div class="mx-auto max-w-5xl space-y-6 pt-2 md:pt-4">
            <AppFormSection title="Current Account Information" subtitle="Review your account details, then use Edit to update profile information.">
                <form @submit.prevent="updateProfile" class="space-y-5">
                    <div class="flex flex-wrap items-center gap-3">
                        <button
                            v-if="!isEditingProfile"
                            type="button"
                            class="action-btn-secondary"
                            @click="startProfileEdit"
                        >
                            Edit Information
                        </button>
                        <template v-else>
                            <button
                                type="button"
                                class="action-btn-secondary"
                                @click="cancelProfileEdit"
                            >
                                Cancel
                            </button>
                            <PrimaryButton :disabled="profileForm.processing">Save Changes</PrimaryButton>
                        </template>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <InputLabel for="name" value="Name" />
                            <TextInput
                                id="name"
                                v-model="profileForm.name"
                                class="mt-1 block w-full"
                                :class="isEditingProfile ? '' : '!border-slate-200 !bg-slate-50 !text-slate-900 focus:!border-slate-200 focus:!ring-0'"
                                :readonly="!isEditingProfile"
                                :disabled="!isEditingProfile"
                                :required="isEditingProfile"
                                autofocus
                            />
                            <InputError class="mt-2" :message="profileForm.errors.name" />
                        </div>

                        <div>
                            <InputLabel for="email" value="Email" />
                            <TextInput
                                id="email"
                                v-model="profileForm.email"
                                type="email"
                                class="mt-1 block w-full"
                                :class="isEditingProfile ? '' : '!border-slate-200 !bg-slate-50 !text-slate-900 focus:!border-slate-200 focus:!ring-0'"
                                :readonly="!isEditingProfile"
                                :disabled="!isEditingProfile"
                                :required="isEditingProfile"
                            />
                            <InputError class="mt-2" :message="profileForm.errors.email" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <InputLabel for="district" value="District" />
                            <TextInput
                                id="district"
                                v-model="profileForm.district"
                                class="mt-1 block w-full"
                                :class="isEditingProfile ? '' : '!border-slate-200 !bg-slate-50 !text-slate-900 focus:!border-slate-200 focus:!ring-0'"
                                :readonly="!isEditingProfile"
                                :disabled="!isEditingProfile"
                            />
                            <InputError class="mt-2" :message="profileForm.errors.district" />
                        </div>

                        <div>
                            <InputLabel for="school_name" value="School" />
                            <TextInput
                                id="school_name"
                                v-model="profileForm.school_name"
                                class="mt-1 block w-full"
                                :class="isEditingProfile ? '' : '!border-slate-200 !bg-slate-50 !text-slate-900 focus:!border-slate-200 focus:!ring-0'"
                                :readonly="!isEditingProfile"
                                :disabled="!isEditingProfile"
                            />
                            <InputError class="mt-2" :message="profileForm.errors.school_name" />
                        </div>
                    </div>
                </form>
            </AppFormSection>

            <AppFormSection title="Password Security" subtitle="Use Change Password to update your account password.">
                <button
                    type="button"
                    class="action-btn-secondary"
                    @click="isEditingPassword = !isEditingPassword"
                >
                    {{ isEditingPassword ? 'Cancel' : 'Change Password' }}
                </button>

                <form v-if="isEditingPassword" @submit.prevent="updatePassword" class="mt-5 space-y-5 border-t border-slate-200 pt-5">
                    <div>
                        <InputLabel for="current_password" value="Current Password" />
                        <PasswordInput id="current_password" v-model="passwordForm.current_password" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="passwordForm.errors.current_password" />
                    </div>
                    <div>
                        <InputLabel for="password" value="New Password" />
                        <PasswordInput id="password" v-model="passwordForm.password" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="passwordForm.errors.password" />
                    </div>
                    <div>
                        <InputLabel for="password_confirmation" value="Confirm Password" />
                        <PasswordInput id="password_confirmation" v-model="passwordForm.password_confirmation" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="passwordForm.errors.password_confirmation" />
                    </div>
                    <PrimaryButton :disabled="passwordForm.processing">Update Password</PrimaryButton>
                </form>
            </AppFormSection>
        </div>
    </AuthenticatedLayout>
</template>
