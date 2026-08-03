<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Profile" />

    <AuthenticatedLayout>
        <div class="py-8 bg-slate-50 dark:bg-gray-900 min-h-screen">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Header Section (disamakan dengan halaman lain) -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm">
                    <h2 class="text-2xl font-extrabold text-slate-900 dark:text-gray-100 tracking-tight">
                        Profil Akun
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-gray-400 mt-1">
                        Kelola informasi profil, kata sandi, dan pengaturan akun Anda
                    </p>
                </div>

                <!-- Update Profile Information Card -->
                <div class="bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-2xl overflow-hidden shadow-sm p-6">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="max-w-xl w-full"
                    />
                </div>

                <!-- Update Password Card -->
                <div class="bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-2xl overflow-hidden shadow-sm p-6">
                    <UpdatePasswordForm class="max-w-xl w-full" />
                </div>

                <!-- Delete User Card -->
                <div class="bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-2xl overflow-hidden shadow-sm p-6">
                    <DeleteUserForm class="max-w-xl w-full" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>