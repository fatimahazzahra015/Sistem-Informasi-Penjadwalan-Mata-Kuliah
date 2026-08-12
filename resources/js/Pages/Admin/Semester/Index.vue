<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    semestersList: Array
});

const isModalOpen = ref(false);

const form = useForm({
    nama: 'Ganjil',
    tahun_ajaran: '',
});

function openModal() {
    form.reset();
    isModalOpen.value = true;
}

function closeModal() {
    isModalOpen.value = false;
}

function submit() {
    form.post(route('admin.semester.store'), {
        onSuccess: () => {
            closeModal();
        }
    });
}

// --- Konfirmasi "Set Aktif" via Modal.vue (menggantikan window.confirm) ---
const isActivateModalOpen = ref(false);
const semesterToActivate = ref(null);
const activateForm = useForm({});

function askActivate(sem) {
    semesterToActivate.value = sem;
    isActivateModalOpen.value = true;
}

function closeActivateModal() {
    isActivateModalOpen.value = false;
    semesterToActivate.value = null;
}

function confirmActivate() {
    if (!semesterToActivate.value) return;
    activateForm.post(route('admin.semester.setActive', semesterToActivate.value.id), {
        preserveScroll: true,
        onSuccess: () => closeActivateModal(),
        onError: () => closeActivateModal(),
    });
}

// --- Konfirmasi hapus semester via Modal.vue (menggantikan window.confirm) ---
const isDeleteModalOpen = ref(false);
const semesterToDelete = ref(null);
const deleteForm = useForm({});

function askDelete(sem) {
    semesterToDelete.value = sem;
    isDeleteModalOpen.value = true;
}

function closeDeleteModal() {
    isDeleteModalOpen.value = false;
}

function confirmDelete() {
    if (!semesterToDelete.value) return;
    deleteForm.delete(route('admin.semester.destroy', semesterToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => closeDeleteModal(),
        onError: () => closeDeleteModal(),
    });
}
</script>

<template>
    <Head title="Manajemen Semester" />

    <AuthenticatedLayout>
        <div class="py-8 bg-slate-50 dark:bg-gray-900 min-h-screen">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Header Section (disamakan dengan halaman Jadwal) -->
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between bg-white dark:bg-gray-800 p-6 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm">
                    <div>
                        <h2 class="text-2xl font-extrabold text-slate-900 dark:text-gray-100 tracking-tight">
                            Manajemen Semester
                        </h2>
                        <p class="text-sm text-slate-500 dark:text-gray-400 mt-1">
                            Buat, aktifkan, dan arsipkan semester perkuliahan
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <button
                            @click="openModal"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-blue-600 text-white hover:bg-blue-700 transition-all shadow-sm whitespace-nowrap"
                        >
                            <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Buat Semester Baru
                        </button>
                    </div>
                </div>

                <!-- Semester List Card -->
                <div class="bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-2xl overflow-hidden shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse min-w-[650px]">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-gray-900/60 border-b border-slate-200 dark:border-gray-700">
                                    <th class="w-[80px] px-6 py-4 text-xs font-bold text-slate-500 dark:text-gray-400 tracking-wider text-center">No</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-gray-400 tracking-wider">Nama Semester</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-gray-400 tracking-wider">Tahun Ajaran</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-gray-400 tracking-wider text-center">Status</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-gray-400 tracking-wider text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(sem, index) in semestersList"
                                    :key="sem.id"
                                    class="border-b border-slate-100 dark:border-gray-700/50 hover:bg-slate-50/60 dark:hover:bg-gray-900/10 transition-colors"
                                >
                                    <td class="px-6 py-4 text-sm text-slate-500 dark:text-gray-400 text-center">{{ index + 1 }}</td>
                                    <td class="px-6 py-4 text-sm font-semibold text-slate-800 dark:text-gray-200 text-center">
                                        Semester {{ sem.nama }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600 dark:text-gray-400 text-center">{{ sem.tahun_ajaran }}</td>
                                    <td class="px-6 py-4 text-sm text-center">
                                        <span
                                            v-if="sem.is_active"
                                            class="bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 px-3 py-1 rounded-full text-xs font-extrabold border border-emerald-200 dark:border-emerald-900/50"
                                        >
                                            ● Aktif
                                        </span>
                                        <span
                                            v-else
                                            class="bg-slate-100 dark:bg-gray-900 text-slate-500 dark:text-gray-400 px-3 py-1 rounded-full text-xs font-bold border border-slate-200 dark:border-gray-800"
                                        >
                                            Diarsipkan
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-right">
                                        <div class="flex justify-end gap-2 whitespace-nowrap">
                                            <!-- Set Active Button -->
                                            <button
                                                v-if="!sem.is_active"
                                                @click="askActivate(sem)"
                                                class="px-3 py-1.5 rounded-lg text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/20 transition-all border border-transparent hover:border-emerald-200 dark:hover:border-emerald-900/30"
                                            >
                                                Set Aktif
                                            </button>

                                            <!-- View Archive Button -->
                                            <Link
                                                :href="route('admin.semester.viewArchive', sem.id)"
                                                class="px-3 py-1.5 rounded-lg text-xs font-bold text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-950/20 transition-all border border-transparent hover:border-blue-200 dark:hover:border-blue-900/30"
                                            >
                                                Lihat Arsip
                                            </Link>

                                            <!-- Delete Button -->
                                            <button
                                                v-if="!sem.is_active"
                                                @click="askDelete(sem)"
                                                class="px-3 py-1.5 rounded-lg text-xs font-bold text-rose-600 dark:text-red-400 hover:bg-rose-50 dark:hover:bg-red-950/20 transition-all border border-transparent hover:border-red-200 dark:hover:border-red-900/30"
                                            >
                                                Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="semestersList.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center text-sm text-slate-500 dark:text-gray-400">
                                        Belum ada semester yang dibuat.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Modal Dialog -->
        <Modal :show="isModalOpen" max-width="md" @close="closeModal">
            <div class="px-6 py-5 border-b border-slate-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="font-bold text-slate-800 dark:text-gray-100 text-lg">Buat Semester Baru</h3>
                <button @click="closeModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-gray-200 font-bold text-lg">&times;</button>
            </div>

            <form @submit.prevent="submit" class="p-6 flex flex-col gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Nama Semester</label>
                    <select
                        v-model="form.nama"
                        class="w-full bg-slate-50 dark:bg-gray-900 border border-slate-200 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-slate-800 dark:text-gray-100 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                    >
                        <option value="Ganjil">Ganjil</option>
                        <option value="Genap">Genap</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Tahun Ajaran</label>
                    <input
                        v-model="form.tahun_ajaran"
                        type="text"
                        placeholder="Contoh: 2023/2024"
                        class="w-full bg-slate-50 dark:bg-gray-900 border border-slate-200 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-slate-800 dark:text-gray-100 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                        required
                    />
                </div>

                <div class="mt-4 flex flex-col-reverse sm:flex-row justify-end gap-3">
                    <button
                        type="button"
                        @click="closeModal"
                        class="px-4 py-2 rounded-xl text-sm font-semibold bg-slate-100 dark:bg-gray-900 text-slate-600 dark:text-gray-300 hover:bg-slate-200 dark:hover:bg-gray-800 transition-all border border-slate-200 dark:border-gray-700"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-5 py-2 rounded-xl text-sm font-bold bg-blue-600 hover:bg-blue-700 text-white transition-all shadow-sm disabled:opacity-50"
                    >
                        Simpan
                    </button>
                </div>
            </form>
        </Modal>

        <!-- Konfirmasi Set Aktif -->
        <Modal :show="isActivateModalOpen" max-width="md" @close="closeActivateModal">
            <div class="p-6">
                <div class="flex items-start gap-4">
                    <span class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <h3 class="text-base font-bold text-slate-900 dark:text-gray-100">
                            Aktifkan Semester Ini?
                        </h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">
                            Anda akan menetapkan
                            <span class="font-semibold text-slate-700 dark:text-gray-200">
                                Semester {{ semesterToActivate?.nama }} {{ semesterToActivate?.tahun_ajaran }}
                            </span>
                            sebagai semester aktif.
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex flex-col-reverse sm:flex-row justify-end gap-3">
                    <button
                        type="button"
                        @click="closeActivateModal"
                        class="px-4 py-2 rounded-xl text-sm font-semibold bg-slate-100 dark:bg-gray-900 text-slate-600 dark:text-gray-300 hover:bg-slate-200 dark:hover:bg-gray-800 transition-all border border-slate-200 dark:border-gray-700"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="confirmActivate"
                        :disabled="activateForm.processing"
                        class="px-5 py-2 rounded-xl text-sm font-bold bg-emerald-600 hover:bg-emerald-700 text-white transition-all shadow-sm disabled:opacity-50"
                    >
                        Ya, Aktifkan
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Konfirmasi Hapus Semester -->
        <Modal :show="isDeleteModalOpen" max-width="md" @close="closeDeleteModal">
            <div class="p-6">
                <div class="flex items-start gap-4">
                    <span class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600 dark:bg-rose-950/40 dark:text-rose-400">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <h3 class="text-base font-bold text-slate-900 dark:text-gray-100">
                            Hapus Semester Ini?
                        </h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">
                            Anda akan menghapus
                            <span class="font-semibold text-slate-700 dark:text-gray-200">
                                Semester {{ semesterToDelete?.nama }} {{ semesterToDelete?.tahun_ajaran }}
                            </span>
                            beserta semua jadwal yang terkait. 
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex flex-col-reverse sm:flex-row justify-end gap-3">
                    <button
                        type="button"
                        @click="closeDeleteModal"
                        class="px-4 py-2 rounded-xl text-sm font-semibold bg-slate-100 dark:bg-gray-900 text-slate-600 dark:text-gray-300 hover:bg-slate-200 dark:hover:bg-gray-800 transition-all border border-slate-200 dark:border-gray-700"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="confirmDelete"
                        :disabled="deleteForm.processing"
                        class="px-5 py-2 rounded-xl text-sm font-bold bg-rose-600 hover:bg-rose-700 text-white transition-all shadow-sm disabled:opacity-50"
                    >
                        Ya, Hapus Semester
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>