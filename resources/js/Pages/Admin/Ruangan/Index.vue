<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    roomsList: Array
});

const isModalOpen = ref(false);
const editingRoom = ref(null);
const searchQuery = ref('');

const form = useForm({
    nama_ruangan: '',
    kapasitas: '',
    tipe: 'kelas',
});

function openAddModal() {
    editingRoom.value = null;
    form.reset();
    isModalOpen.value = true;
}

function openEditModal(room) {
    editingRoom.value = room;
    form.nama_ruangan = room.nama_ruangan;
    form.kapasitas = room.kapasitas;
    form.tipe = room.tipe;
    isModalOpen.value = true;
}

function closeModal() {
    isModalOpen.value = false;
    editingRoom.value = null;
}

function submit() {
    if (editingRoom.value) {
        form.put(route('admin.ruangan.update', editingRoom.value.id), {
            onSuccess: () => closeModal()
        });
    } else {
        form.post(route('admin.ruangan.store'), {
            onSuccess: () => closeModal()
        });
    }
}

// --- Konfirmasi hapus ruangan via Modal.vue (menggantikan window.confirm) ---
const isDeleteModalOpen = ref(false);
const roomToDelete = ref(null);
const deleteForm = useForm({});

function askDelete(room) {
    roomToDelete.value = room;
    isDeleteModalOpen.value = true;
}
 
function closeDeleteModal() {
    isDeleteModalOpen.value = false;
}
 
function confirmDelete() {
    if (!roomToDelete.value) return;
    deleteForm.delete(route('admin.ruangan.destroy', roomToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => closeDeleteModal(),
        onError: () => closeDeleteModal(),
    });
}

// --- Filter Pencarian Ruangan ---
const filteredRoomsList = computed(() => {
    if (searchQuery.value.trim() === '') {
        return props.roomsList;
    }
    const query = searchQuery.value.toLowerCase();
    return props.roomsList.filter(
        (room) =>
            room.nama_ruangan?.toLowerCase().includes(query) ||
            String(room.kapasitas).includes(query) ||
            (room.tipe === 'lab' ? 'laboratorium' : 'kelas teori').includes(query),
    );
});
</script>

<template>
    <Head title="Master Data Ruangan" />

    <AuthenticatedLayout>
        <div class="py-8 bg-slate-50 dark:bg-gray-900 min-h-screen">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Header Section (disamakan dengan halaman lain) -->
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between bg-white dark:bg-gray-800 p-6 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm">
                    <div>
                        <h2 class="text-2xl font-extrabold text-slate-900 dark:text-gray-100 tracking-tight">
                            Master Data Ruangan
                        </h2>
                        <p class="text-sm text-slate-500 dark:text-gray-400 mt-1">
                            Kelola data ruangan kelas teori maupun laboratorium
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
                            Tambah Ruangan
                        </button>
                    </div>
                </div>

                <!-- Room List Card -->
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
                                placeholder="Cari nama ruangan, kapasitas, atau tipe..."
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
                            Menampilkan <span class="font-semibold text-slate-500 dark:text-gray-400">{{ filteredRoomsList.length }}</span> dari {{ roomsList.length }} ruangan
                        </p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse min-w-[650px]">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-gray-900/60 border-b border-slate-200 dark:border-gray-700">
                                    <th class="w-[80px] px-6 py-4 text-xs font-bold text-slate-500 dark:text-gray-400 tracking-wider text-center">No</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-gray-400 tracking-wider text-center">Nama Ruangan</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-gray-400 tracking-wider text-center">Kapasitas</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-gray-400 tracking-wider text-center">Tipe Ruangan</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-gray-400 tracking-wider text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(room, index) in filteredRoomsList"
                                    :key="room.id"
                                    class="border-b border-slate-100 dark:border-gray-700/50 hover:bg-slate-50/60 dark:hover:bg-gray-900/10 transition-colors"
                                >
                                    <td class="px-6 py-4 text-sm text-slate-500 dark:text-gray-400 text-center">{{ index + 1 }}</td>
                                    <td class="px-6 py-4 text-sm text-slate-800 dark:text-gray-200 text-center font-semibold text-center">{{ room.nama_ruangan }}</td>
                                    <td class="px-6 py-4 text-sm text-slate-800 dark:text-gray-200 text-center font-semibold">{{ room.kapasitas }} Kursi</td>
                                    <td class="px-6 py-4 text-sm text-center">
                                        <span
                                            v-if="room.tipe === 'lab'"
                                            class="bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 px-3 py-1 rounded-full text-xs font-extrabold border border-emerald-200 dark:border-emerald-900/50"
                                        >
                                            Laboratorium
                                        </span>
                                        <span
                                            v-else
                                            class="bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-400 px-3 py-1 rounded-full text-xs font-bold border border-blue-200 dark:border-blue-900/50"
                                        >
                                            Kelas Teori
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-center">
                                        <div class="flex justify-center gap-2 whitespace-nowrap">
                                            <button
                                                @click="openEditModal(room)"
                                                class="px-3 py-1.5 rounded-lg text-xs font-bold text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-950/20 transition-all border border-transparent hover:border-blue-200 dark:hover:border-blue-900/30"
                                            >
                                                Edit
                                            </button>
                                            <button
                                                @click="askDelete(room)"
                                                class="px-3 py-1.5 rounded-lg text-xs font-bold text-rose-600 dark:text-red-400 hover:bg-rose-50 dark:hover:bg-red-950/20 transition-all border border-transparent hover:border-rose-200 dark:hover:border-red-900/30"
                                            >
                                                Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="filteredRoomsList.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center text-sm text-slate-500 dark:text-gray-400">
                                        {{ roomsList.length === 0 ? 'Belum ada data ruangan.' : 'Ruangan yang dicari tidak ditemukan. Coba ubah kata kunci.' }}
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
                    {{ editingRoom ? 'Edit Data Ruangan' : 'Tambah Ruangan Baru' }}
                </h3>
                <button @click="closeModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-gray-200 font-bold text-lg">&times;</button>
            </div>

            <form @submit.prevent="submit" class="p-6 flex flex-col gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Nama Ruangan</label>
                    <input
                        v-model="form.nama_ruangan"
                        type="text"
                        placeholder="Contoh: 407, Lab TIA"
                        class="w-full bg-slate-50 dark:bg-gray-900 border border-slate-200 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-slate-800 dark:text-gray-100 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                        :class="{ 'border-red-500': form.errors.nama_ruangan }"
                        required
                    />
                    <div v-if="form.errors.nama_ruangan" class="text-xs text-red-500 mt-1">{{ form.errors.nama_ruangan }}</div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Kapasitas (Kursi)</label>
                    <input
                        v-model="form.kapasitas"
                        type="number"
                        placeholder="Contoh: 40"
                        class="w-full bg-slate-50 dark:bg-gray-900 border border-slate-200 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-slate-800 dark:text-gray-100 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                        :class="{ 'border-red-500': form.errors.kapasitas }"
                        required
                    />
                    <div v-if="form.errors.kapasitas" class="text-xs text-red-500 mt-1">{{ form.errors.kapasitas }}</div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Tipe Ruangan</label>
                    <select
                        v-model="form.tipe"
                        class="w-full bg-slate-50 dark:bg-gray-900 border border-slate-200 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-slate-800 dark:text-gray-100 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                        :class="{ 'border-red-500': form.errors.tipe }"
                        required
                    >
                        <option value="kelas">Kelas Teori</option>
                        <option value="lab">Laboratorium</option>
                    </select>
                    <div v-if="form.errors.tipe" class="text-xs text-red-500 mt-1">{{ form.errors.tipe }}</div>
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

        <!-- Konfirmasi Hapus Ruangan -->
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
                            Hapus Ruangan Ini?
                        </h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">
                            Anda akan menghapus ruangan
                            <span class="font-semibold text-slate-700 dark:text-gray-200">
                                {{ roomToDelete?.nama_ruangan }}
                            </span>
                            . Semua jadwal perkuliahan di ruangan ini juga akan dihapus. 
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
                        Ya, Hapus Ruangan
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>