<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    dosenList: Array
});

const isModalOpen = ref(false);
const editingDosen = ref(null);
const searchQuery = ref('');

const form = useForm({
    nama: '',
    kode_dosen: '',
    email: '',
    program_studi: 'Teknik Informatika',
});

function openAddModal() {
    editingDosen.value = null;
    form.reset();
    isModalOpen.value = true;
}

function openEditModal(dosen) {
    editingDosen.value = dosen;
    form.nama = dosen.nama;
    form.kode_dosen = dosen.kode_dosen;
    form.email = dosen.user?.email || '';
    form.program_studi = dosen.program_studi;
    isModalOpen.value = true;
}

function closeModal() {
    isModalOpen.value = false;
    editingDosen.value = null;
}

function submit() {
    if (editingDosen.value) {
        form.put(route('admin.dosen.update', editingDosen.value.id), {
            onSuccess: () => closeModal()
        });
    } else {
        form.post(route('admin.dosen.store'), {
            onSuccess: () => closeModal()
        });
    }
}

// --- Konfirmasi hapus dosen via Modal.vue (menggantikan window.confirm) ---
const isDeleteModalOpen = ref(false);
const dosenToDelete = ref(null);
const deleteForm = useForm({});

function askDelete(dosen) {
    dosenToDelete.value = dosen;
    isDeleteModalOpen.value = true;
}
 
function closeDeleteModal() {
    isDeleteModalOpen.value = false;
}
 
function confirmDelete() {
    if (!dosenToDelete.value) return;
    deleteForm.delete(route('admin.dosen.destroy', dosenToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => closeDeleteModal(),
        onError: () => closeDeleteModal(),
    });
}

// --- Filter Pencarian Dosen ---
const filteredDosenList = computed(() => {
    if (searchQuery.value.trim() === '') {
        return props.dosenList;
    }
    const query = searchQuery.value.toLowerCase();
    return props.dosenList.filter(
        (dosen) =>
            dosen.nama?.toLowerCase().includes(query) ||
            dosen.kode_dosen?.toLowerCase().includes(query) ||
            dosen.user?.email?.toLowerCase().includes(query) ||
            dosen.program_studi?.toLowerCase().includes(query),
    );
});
</script>

<template>
    <Head title="Master Data Dosen" />

    <AuthenticatedLayout>
        <div class="py-8 bg-slate-50 dark:bg-gray-900 min-h-screen">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Header Section (disamakan dengan halaman Semester) -->
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between bg-white dark:bg-gray-800 p-6 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm">
                    <div>
                        <h2 class="text-2xl font-extrabold text-slate-900 dark:text-gray-100 tracking-tight">
                            Master Data Dosen
                        </h2>
                        <p class="text-sm text-slate-500 dark:text-gray-400 mt-1">
                            Kelola data dosen Teknik Informatika
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <button
                            @click="openAddModal"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-blue-600 text-white hover:bg-blue-700 transition-all shadow-sm whitespace-nowrap"
                        >
                            <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Tambah Dosen
                        </button>
                    </div>
                </div>

                <!-- Dosen List Card -->
                <div class="bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-2xl overflow-hidden shadow-sm">
                    <!-- Search Bar -->
                    <div class="p-4 sm:p-6 border-b border-slate-200 dark:border-gray-700 bg-slate-50/60 dark:bg-gray-900/20 space-y-3">
                        <div class="relative w-full">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </span>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Cari nama, kode dosen, atau email..."
                                class="w-full bg-white dark:bg-gray-900 border border-slate-200 dark:border-gray-700 rounded-xl pl-10 pr-9 py-2.5 text-sm text-slate-800 dark:text-gray-100 shadow-sm placeholder:text-slate-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                            />
                            <button
                                v-if="searchQuery"
                                @click="searchQuery = ''"
                                type="button"
                                aria-label="Hapus pencarian"
                                class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-slate-600 dark:hover:text-gray-200 transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <p class="text-xs text-slate-400 dark:text-gray-500">
                            Menampilkan <span class="font-semibold text-slate-500 dark:text-gray-400">{{ filteredDosenList.length }}</span> dari {{ dosenList.length }} dosen
                        </p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse min-w-[750px]">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-gray-900/60 border-b border-slate-200 dark:border-gray-700">
                                    <th class="w-[80px] px-6 py-4 text-xs font-bold text-slate-500 dark:text-gray-400 tracking-wider text-center">No</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-gray-400 tracking-wider">Kode Dosen</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-gray-400 tracking-wider">Nama Dosen</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-gray-400 tracking-wider">Email Akun</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-gray-400 tracking-wider">Program Studi</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-gray-400 tracking-wider text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(dosen, index) in filteredDosenList"
                                    :key="dosen.id"
                                    class="border-b border-slate-100 dark:border-gray-700/50 hover:bg-slate-50/60 dark:hover:bg-gray-900/10 transition-colors"
                                >
                                    <td class="px-6 py-4 text-sm text-slate-500 dark:text-gray-400 text-center">{{ index + 1 }}</td>
                                    <td class="px-6 py-4 text-sm font-bold text-indigo-600 dark:text-indigo-400">{{ dosen.kode_dosen }}</td>
                                    <td class="px-6 py-4 text-sm font-semibold text-slate-800 dark:text-gray-200">{{ dosen.nama }}</td>
                                    <td class="px-6 py-4 text-sm text-slate-600 dark:text-gray-400 text-center">{{ dosen.user?.email }}</td>
                                    <td class="px-6 py-4 text-sm text-slate-600 dark:text-gray-400 text-center">{{ dosen.program_studi }}</td>
                                    <td class="px-6 py-4 text-sm text-center">
                                        <div class="flex justify-center gap-2 whitespace-nowrap">
                                            <button
                                                @click="openEditModal(dosen)"
                                                class="px-3 py-1.5 rounded-lg text-xs font-bold text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-950/20 transition-all border border-transparent hover:border-blue-200 dark:hover:border-blue-900/30"
                                            >
                                                Edit
                                            </button>
                                            <button
                                                @click="askDelete(dosen)"
                                                class="px-3 py-1.5 rounded-lg text-xs font-bold text-rose-600 dark:text-red-400 hover:bg-rose-50 dark:hover:bg-red-950/20 transition-all border border-transparent hover:border-rose-200 dark:hover:border-red-900/30"
                                            >
                                                Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="filteredDosenList.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center text-sm text-slate-500 dark:text-gray-400">
                                        {{ dosenList.length === 0 ? 'Belum ada data dosen.' : 'Dosen yang dicari tidak ditemukan. Coba ubah kata kunci.' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <!-- Add/Edit Modal -->
        <Modal :show="isModalOpen" max-width="md" @close="closeModal">
            <div class="px-6 py-5 border-b border-slate-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="font-bold text-slate-800 dark:text-gray-100 text-lg">
                    {{ editingDosen ? 'Edit Data Dosen' : 'Tambah Dosen Baru' }}
                </h3>
                <button @click="closeModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-gray-200 font-bold text-lg">&times;</button>
            </div>

            <form @submit.prevent="submit" class="p-6 flex flex-col gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Nama Lengkap</label>
                    <input
                        v-model="form.nama"
                        type="text"
                        placeholder="Contoh: Dr. Fatimah Azzahra"
                        class="w-full bg-slate-50 dark:bg-gray-900 border border-slate-200 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-slate-800 dark:text-gray-100 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                        :class="{ 'border-red-500': form.errors.nama }"
                        required
                    />
                    <div v-if="form.errors.nama" class="text-xs text-red-500 mt-1">{{ form.errors.nama }}</div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Kode Dosen</label>
                    <input
                        v-model="form.kode_dosen"
                        type="text"
                        placeholder="Contoh: FA"
                        class="w-full bg-slate-50 dark:bg-gray-900 border border-slate-200 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-slate-800 dark:text-gray-100 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                        :class="{ 'border-red-500': form.errors.kode_dosen }"
                        required
                    />
                    <div v-if="form.errors.kode_dosen" class="text-xs text-red-500 mt-1">{{ form.errors.kode_dosen }}</div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Email Akun</label>
                    <input
                        v-model="form.email"
                        type="email"
                        placeholder="Contoh: fatimah@utm.ac.id"
                        class="w-full bg-slate-50 dark:bg-gray-900 border border-slate-200 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-slate-800 dark:text-gray-100 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                        :class="{ 'border-red-500': form.errors.email }"
                        required
                    />
                    <div v-if="form.errors.email" class="text-xs text-red-500 mt-1">{{ form.errors.email }}</div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Program Studi</label>
                    <input
                        v-model="form.program_studi"
                        type="text"
                        placeholder="Contoh: Teknik Informatika"
                        class="w-full bg-slate-50 dark:bg-gray-900 border border-slate-200 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-slate-800 dark:text-gray-100 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                        :class="{ 'border-red-500': form.errors.program_studi }"
                        required
                    />
                    <div v-if="form.errors.program_studi" class="text-xs text-red-500 mt-1">{{ form.errors.program_studi }}</div>
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

        <!-- Konfirmasi Hapus Dosen -->
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
                            Hapus Dosen Ini?
                        </h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">
                            Anda akan menghapus data dosen
                            <span class="font-semibold text-slate-700 dark:text-gray-200">
                                {{ dosenToDelete?.nama }}
                            </span>
                            ({{ dosenToDelete?.kode_dosen }}). Akun login pengguna terkait juga akan dihapus. Tindakan ini tidak dapat dibatalkan.
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
                        Ya, Hapus Dosen
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>