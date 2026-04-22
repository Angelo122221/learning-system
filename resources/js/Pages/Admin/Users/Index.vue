<script setup>
import AppFormSection from '@/Components/AppFormSection.vue';
import AppPageHeader from '@/Components/AppPageHeader.vue';
import AppScrollablePanel from '@/Components/AppScrollablePanel.vue';
import AppStatCard from '@/Components/AppStatCard.vue';
import AppStatusBadge from '@/Components/AppStatusBadge.vue';
import InputError from '@/Components/InputError.vue';
import PasswordInput from '@/Components/PasswordInput.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    users: Array,
    stats: Object,
});

const createForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'teacher',
    district: '',
    school_name: '',
});

const editForm = useForm({
    name: '',
    email: '',
    role: 'teacher',
    district: '',
    school_name: '',
});

const passwordForm = useForm({
    password: '',
    password_confirmation: '',
});

const editingUserId = ref(null);
const passwordUserId = ref(null);

const sortedUsers = computed(() =>
    [...props.users].sort((a, b) => {
        if (a.is_admin !== b.is_admin) return a.is_admin ? -1 : 1;
        return a.name.localeCompare(b.name);
    }),
);

const submitCreate = () => {
    createForm.post('/admin/users', {
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
    });
};

const startEdit = (user) => {
    editingUserId.value = user.id;
    editForm.name = user.name;
    editForm.email = user.email;
    editForm.role = user.role || (user.is_admin ? 'admin' : 'teacher');
    editForm.district = user.district || '';
    editForm.school_name = user.school_name || '';
};

const cancelEdit = () => {
    editingUserId.value = null;
    editForm.reset();
};

const saveEdit = (userId) => {
    editForm
        .transform((data) => ({
            ...data,
            _method: 'patch',
        }))
        .post(`/admin/users/${userId}`, {
            preserveScroll: true,
            onSuccess: () => cancelEdit(),
        });
};

const startPasswordChange = (userId) => {
    passwordUserId.value = userId;
    passwordForm.reset();
};

const cancelPasswordChange = () => {
    passwordUserId.value = null;
    passwordForm.reset();
};

const savePassword = (userId) => {
    passwordForm
        .transform((data) => ({
            ...data,
            _method: 'patch',
        }))
        .post(`/admin/users/${userId}/password`, {
            preserveScroll: true,
            onSuccess: () => cancelPasswordChange(),
        });
};

const deleteUser = (userId) => {
    if (!confirm('Delete this account?')) return;

    router.post(`/admin/users/${userId}`, {
        _method: 'delete',
    }, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Users" />

    <AdminLayout>
        <AppPageHeader
            title="User"
            accent="Management"
            subtitle="Create, edit, secure, and organize DepEd accounts from one workspace."
        >
            <template #stats>
                <div class="grid w-full gap-3 md:grid-cols-3 xl:w-auto">
                    <AppStatCard label="Users" :value="stats.total_users" tone="slate" />
                    <AppStatCard label="Teachers" :value="stats.total_teachers" tone="emerald" />
                    <AppStatCard label="Admins" :value="stats.total_admins" tone="blue" />
                </div>
            </template>
        </AppPageHeader>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-12 xl:items-start">
            <div class="xl:col-span-4">
                <AppFormSection
                    title="Create Account"
                    subtitle="Only DepEd emails are allowed. Teachers must include district and school details."
                >
                    <form @submit.prevent="submitCreate" class="space-y-4">
                        <div>
                            <label class="field-label" for="create_name">Full Name</label>
                            <input id="create_name" v-model="createForm.name" type="text" class="field-input" placeholder="Juan Dela Cruz" />
                            <InputError :message="createForm.errors.name" />
                        </div>

                        <div>
                            <label class="field-label" for="create_email">DepEd Email</label>
                            <input id="create_email" v-model="createForm.email" type="email" class="field-input" placeholder="juan@deped.gov.ph" />
                            <InputError :message="createForm.errors.email" />
                        </div>

                        <div>
                            <label class="field-label" for="create_role">Role</label>
                            <select id="create_role" v-model="createForm.role" class="field-input">
                                <option value="teacher">Teacher</option>
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                            <InputError :message="createForm.errors.role" />
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="field-label" for="create_password">Password</label>
                                <PasswordInput id="create_password" v-model="createForm.password" class="w-full" placeholder="Minimum 8 characters" />
                                <InputError :message="createForm.errors.password" />
                            </div>
                            <div>
                                <label class="field-label" for="create_password_confirmation">Confirm Password</label>
                                <PasswordInput id="create_password_confirmation" v-model="createForm.password_confirmation" class="w-full" placeholder="Repeat password" />
                            </div>
                        </div>

                        <div>
                            <label class="field-label" for="create_district">District</label>
                            <input id="create_district" v-model="createForm.district" :required="createForm.role === 'teacher'" type="text" class="field-input" placeholder="Sample District" />
                            <InputError :message="createForm.errors.district" />
                        </div>

                        <div>
                            <label class="field-label" for="create_school_name">School Name</label>
                            <input id="create_school_name" v-model="createForm.school_name" :required="createForm.role === 'teacher'" type="text" class="field-input" placeholder="Sample School" />
                            <InputError :message="createForm.errors.school_name" />
                        </div>

                        <button type="submit" class="action-btn-primary w-full justify-center bg-emerald-600 hover:bg-emerald-700">
                            Create Account
                        </button>
                    </form>
                </AppFormSection>
            </div>

            <div class="xl:col-span-8">
                <AppScrollablePanel height-class="max-h-[76vh]">
                    <div class="mb-4">
                        <h2 class="text-xs font-black uppercase tracking-[0.24em] text-slate-400">Accounts</h2>
                        <p class="mt-2 text-sm font-medium text-slate-500">Review current users, update roles, or reset passwords without leaving this page.</p>
                    </div>

                    <div class="space-y-4">
                        <article v-for="user in sortedUsers" :key="user.id" class="panel-muted border p-4 md:p-5">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-base font-black uppercase text-slate-900">{{ user.name }}</p>
                                        <AppStatusBadge v-if="user.is_admin" label="Admin" variant="admin" />
                                        <AppStatusBadge v-else-if="user.role === 'teacher'" label="Teacher" variant="teacher" />
                                        <AppStatusBadge v-else label="User" variant="user" />
                                    </div>
                                    <p class="mt-2 break-all text-sm font-semibold text-slate-500">{{ user.email }}</p>
                                    <p class="mt-2 text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">
                                        {{ user.district || 'No district' }} / {{ user.school_name || 'No school' }}
                                    </p>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <button type="button" class="action-btn-secondary bg-slate-900 text-white hover:bg-blue-600" @click="startEdit(user)">Edit</button>
                                    <button type="button" class="action-btn-secondary bg-amber-500 text-white hover:bg-amber-600" @click="startPasswordChange(user.id)">Password</button>
                                    <button type="button" class="action-btn-danger" @click="deleteUser(user.id)">Delete</button>
                                </div>
                            </div>

                            <form v-if="editingUserId === user.id" @submit.prevent="saveEdit(user.id)" class="mt-4 space-y-4 border-t border-slate-200 pt-4">
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="field-label">Name</label>
                                        <input v-model="editForm.name" type="text" class="field-input" />
                                        <InputError :message="editForm.errors.name" />
                                    </div>
                                    <div>
                                        <label class="field-label">DepEd Email</label>
                                        <input v-model="editForm.email" type="email" class="field-input" />
                                        <InputError :message="editForm.errors.email" />
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                    <div>
                                        <label class="field-label">Role</label>
                                        <select v-model="editForm.role" class="field-input">
                                            <option value="teacher">Teacher</option>
                                            <option value="user">User</option>
                                            <option value="admin">Admin</option>
                                        </select>
                                        <InputError :message="editForm.errors.role" />
                                    </div>
                                    <div>
                                        <label class="field-label">District</label>
                                        <input v-model="editForm.district" :required="editForm.role === 'teacher'" type="text" class="field-input" />
                                        <InputError :message="editForm.errors.district" />
                                    </div>
                                    <div>
                                        <label class="field-label">School Name</label>
                                        <input v-model="editForm.school_name" :required="editForm.role === 'teacher'" type="text" class="field-input" />
                                        <InputError :message="editForm.errors.school_name" />
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <button type="submit" class="action-btn-primary">Save Changes</button>
                                    <button type="button" class="action-btn-secondary" @click="cancelEdit">Cancel</button>
                                </div>
                            </form>

                            <form v-if="passwordUserId === user.id" @submit.prevent="savePassword(user.id)" class="mt-4 space-y-4 border-t border-slate-200 pt-4">
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="field-label">New Password</label>
                                        <PasswordInput v-model="passwordForm.password" class="w-full" />
                                        <InputError :message="passwordForm.errors.password" />
                                    </div>
                                    <div>
                                        <label class="field-label">Confirm Password</label>
                                        <PasswordInput v-model="passwordForm.password_confirmation" class="w-full" />
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <button type="submit" class="action-btn-secondary bg-amber-500 text-white hover:bg-amber-600">Update Password</button>
                                    <button type="button" class="action-btn-secondary" @click="cancelPasswordChange">Cancel</button>
                                </div>
                            </form>
                        </article>
                    </div>
                </AppScrollablePanel>
            </div>
        </div>
    </AdminLayout>
</template>
