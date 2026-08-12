<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    usersList: Array
});

const page = usePage();

const isModalOpen = ref(false);
const editingUser = ref(null);
const searchQuery = ref('');

const form = useForm({
    name: '',
    email: '',
    role: 'dosen',
    password: '',
});

function openAddModal() {
    editingUser.value = null;
    form.reset();
    isModalOpen.value = true;
}

function openEditModal(user) {
    editingUser.value = user;
    form.name = user.name;
    form.email = user.email;
    form.role = user.role;
    form.password = ''; // password optional on edit
    isModalOpen.value = true;
}

function closeModal() {
    isModalOpen.value = false;
    editingUser.value = null;
}

function submit() {
    if (editingUser.value) {
        form.put(route('admin.pengguna.update', editingUser.value.id), {
            onSuccess: () => closeModal()
        });
    } else {
        form.post(route('admin.pengguna.store'), {
            onSuccess: () => closeModal()
        });
    }
}

// --- Konfirmasi hapus akun via Modal.vue (menggantikan window.confirm) ---
const isDeleteModalOpen = ref(false);
const userToDelete = ref(null);
const deleteForm = useForm({});

function askDelete(user) {
    userToDelete.value = user;
    isDeleteModalOpen.value = true;
}
 
function closeDeleteModal() {
    isDeleteModalOpen.value = false;
}
 
function confirmDelete() {
    if (!userToDelete.value) return;
    deleteForm.delete(route('admin.pengguna.destroy', userToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => closeDeleteModal(),
        onError: () => closeDeleteModal(),
    });
}

// --- Filter Pencarian Pengguna ---
const filteredUsersList = computed(() => {
    if (searchQuery.value.trim() === '') {
        return props.usersList;
    }
    const query = searchQuery.value.toLowerCase();
    return props.usersList.filter(
        (u) =>
            u.name?.toLowerCase().includes(query) ||
            u.email?.toLowerCase().includes(query) ||
            (u.role === 'admin' ? 'administrator' : 'dosen pengampu').includes(query) ||
            u.dosen?.kode_dosen?.toLowerCase().includes(query),
    );
});
</script>

<template>
    <Head title="Manajemen Akun Pengguna" />

    <AuthenticatedLayout>
        <div class="py-8 bg-slate-50 dark:bg-gray-900 min-h-screen">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Header Section (disamakan dengan halaman lain) -->
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between bg-white dark:bg-gray-800 p-6 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm">
                    <div>
                        <h2 class="text-2xl font-extrabold text-slate-900 dark:text-gray-100 tracking-tight">
                            Manajemen Akun Pengguna
                        </h2>
                        <p class="text-sm text-slate-500 dark:text-gray-400 mt-1">
                            Kelola akun untuk administrator dan dosen pengampu
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
                            Tambah Pengguna
                        </button>
                    </div>
                </div>

                <!-- User List Card -->
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
                                placeholder="Cari nama, email, role, atau kode dosen..."
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
                            Menampilkan <span class="font-semibold text-slate-500 dark:text-gray-400">{{ filteredUsersList.length }}</span> dari {{ usersList.length }} pengguna
                        </p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse min-w-[750px]">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-gray-900/60 border-b border-slate-200 dark:border-gray-700">
                                    <th class="w-[80px] px-6 py-4 text-xs font-bold text-slate-500 dark:text-gray-400 tracking-wider text-center">No</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-gray-400 tracking-wider">Nama Pengguna</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-gray-400 tracking-wider">Email Akun</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-gray-400 tracking-wider text-center">Role / Hak Akses</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-gray-400 tracking-wider">Kode Dosen (Jika Dosen)</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-gray-400 tracking-wider text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(u, index) in filteredUsersList"
                                    :key="u.id"
                                    class="border-b border-slate-100 dark:border-gray-700/50 hover:bg-slate-50/60 dark:hover:bg-gray-900/10 transition-colors"
                                >
                                    <td class="px-6 py-4 text-sm text-slate-500 dark:text-gray-400 text-center">{{ index + 1 }}</td>
                                    <td class="px-6 py-4 text-sm font-semibold text-slate-800 dark:text-gray-200">{{ u.name }}</td>
                                    <td class="px-6 py-4 text-sm text-slate-600 dark:text-gray-400">{{ u.email }}</td>
                                    <td class="px-6 py-4 text-sm text-center">
                                        <span
                                            v-if="u.role === 'admin'"
                                            class="bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-400 px-3 py-1 rounded-full text-xs font-extrabold border border-indigo-200 dark:border-indigo-900/50"
                                        >
                                            Administrator
                                        </span>
                                        <span
                                            v-else
                                            class="bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 px-3 py-1 rounded-full text-xs font-bold border border-amber-200 dark:border-amber-900/50"
                                        >
                                            Dosen Pengampu
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600 dark:text-gray-400">
                                        {{ u.dosen ? u.dosen.kode_dosen : '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-center">
                                        <div class="flex justify-center gap-2 whitespace-nowrap">
                                            <button
                                                @click="openEditModal(u)"
                                                class="px-3 py-1.5 rounded-lg text-xs font-bold text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-950/20 transition-all border border-transparent hover:border-blue-200 dark:hover:border-blue-900/30"
                                            >
                                                Edit
                                            </button>
                                            <button
                                                v-if="u.id !== page.props.auth.user.id"
                                                @click="askDelete(u)"
                                                class="px-3 py-1.5 rounded-lg text-xs font-bold text-rose-600 dark:text-red-400 hover:bg-rose-50 dark:hover:bg-red-950/20 transition-all border border-transparent hover:border-rose-200 dark:hover:border-red-900/30"
                                            >
                                                Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="filteredUsersList.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center text-sm text-slate-500 dark:text-gray-400">
                                        {{ usersList.length === 0 ? 'Belum ada akun pengguna.' : 'Pengguna yang dicari tidak ditemukan. Coba ubah kata kunci.' }}
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
                    {{ editingUser ? 'Edit Akun Pengguna' : 'Tambah Pengguna Baru' }}
                </h3>
                <button @click="closeModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-gray-200 font-bold text-lg">&times;</button>
            </div>

            <form @submit.prevent="submit" class="p-6 flex flex-col gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Nama Pengguna</label>
                    <input
                        v-model="form.name"
                        type="text"
                        placeholder="Contoh: Fatimah Azzahra"
                        class="w-full bg-slate-50 dark:bg-gray-900 border border-slate-200 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-slate-800 dark:text-gray-100 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                        :class="{ 'border-red-500': form.errors.name }"
                        required
                    />
                    <div v-if="form.errors.name" class="text-xs text-red-500 mt-1">{{ form.errors.name }}</div>
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
                    <label class="block text-xs font-bold text-slate-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Role / Hak Akses</label>
                    <select
                        v-model="form.role"
                        class="w-full bg-slate-50 dark:bg-gray-900 border border-slate-200 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-slate-800 dark:text-gray-100 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                        :class="{ 'border-red-500': form.errors.role }"
                        required
                    >
                        <option value="admin">Administrator</option>
                        <option value="dosen">Dosen Pengampu</option>
                    </select>
                    <div v-if="form.errors.role" class="text-xs text-red-500 mt-1">{{ form.errors.role }}</div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">
                        Password {{ editingUser ? '(Kosongkan jika tidak diubah)' : '' }}
                    </label>
                    <input
                        v-model="form.password"
                        type="password"
                        placeholder="Minimal 8 karakter"
                        class="w-full bg-slate-50 dark:bg-gray-900 border border-slate-200 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-slate-800 dark:text-gray-100 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                        :class="{ 'border-red-500': form.errors.password }"
                        :required="!editingUser"
                    />
                    <div v-if="form.errors.password" class="text-xs text-red-500 mt-1">{{ form.errors.password }}</div>
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

        <!-- Konfirmasi Hapus Akun -->
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
                            Hapus Akun Ini?
                        </h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">
                            Anda akan menghapus akun
                            <span class="font-semibold text-slate-700 dark:text-gray-200">
                                {{ userToDelete?.name }}
                            </span>
                            ({{ userToDelete?.email }}). Jika akun ini dosen, data profil dosennya juga akan terhapus. 
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
                        Ya, Hapus Akun
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>