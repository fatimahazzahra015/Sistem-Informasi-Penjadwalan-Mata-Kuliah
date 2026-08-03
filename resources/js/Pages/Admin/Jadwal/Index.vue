<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    schedules: Array,
    semesters: Array,
    courses: Array,
    lecturers: Array,
    rooms: Array,
    classes: Array,
    activeSemester: Object,
    slots: Object,
});

const isModalOpen = ref(false);
const editingSchedule = ref(null);
const searchQuery = ref('');
const filterHari = ref('');
const filterDosen = ref('');
const filterRuangan = ref('');

// State untuk menampilkan/menyembunyikan panel filter lanjutan
const showAdvancedFilter = ref(false);

const form = useForm({
    mata_kuliah_id: '',
    kelas_id: '',
    dosen_id: '',
    ruangan_id: '',
    hari: 'Senin',
    slot_mulai: 1,
    slot_selesai: 3,
});

const days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

// Opsi untuk combobox (searchable select) - id + label
const courseOptions = computed(() =>
    props.courses.map((c) => ({ id: c.id, label: `${c.nama} (${c.sks} SKS)` })),
);
const classOptions = computed(() =>
    props.classes.map((cl) => ({ id: cl.id, label: `Kelas ${cl.nama_kelas}` })),
);
const lecturerOptions = computed(() =>
    props.lecturers.map((d) => ({ id: d.id, label: `${d.nama} [${d.kode_dosen}]` })),
);
const roomOptions = computed(() =>
    props.rooms.map((r) => ({ id: r.id, label: `${r.nama_ruangan} (${r.tipe})` })),
);

// Real-time conflict state
const conflictChecking = ref(false);
const conflictMessage = ref('');
const conflictType = ref('');
const hasConflict = ref(false);

// Judul peringatan disesuaikan dengan jenis konflik yang terdeteksi
const conflictTitleMap = {
    ruangan: '🚨 Bentrok Ruangan!',
    dosen: '🚨 Bentrok Dosen!',
    kelas: '🚨 Bentrok Kelas!',
};
const conflictTitle = computed(() => conflictTitleMap[conflictType.value] || '🚨 Peringatan Bentrok Jadwal!');

function openAddModal() {
    editingSchedule.value = null;
    form.reset();
    hasConflict.value = false;
    conflictMessage.value = '';
    conflictType.value = '';
    isModalOpen.value = true;
}

function openEditModal(sched) {
    editingSchedule.value = sched;
    form.mata_kuliah_id = sched.mata_kuliah_id;
    form.kelas_id = sched.kelas_id;
    form.dosen_id = sched.dosen_id;
    form.ruangan_id = sched.ruangan_id;
    form.hari = sched.hari;
    form.slot_mulai = sched.slot_mulai;
    form.slot_selesai = sched.slot_selesai;
    hasConflict.value = false;
    conflictMessage.value = '';
    conflictType.value = '';
    isModalOpen.value = true;
}

function closeModal() {
    isModalOpen.value = false;
    editingSchedule.value = null;
}

// Watch selected course and auto-calculate slot_selesai based on SKS
watch(() => form.mata_kuliah_id, (newCourseId) => {
    if (newCourseId) {
        const course = props.courses.find((c) => c.id === Number(newCourseId));
        if (course) {
            form.slot_selesai = Math.min(13, Number(form.slot_mulai) + Number(course.sks) - 1);
        }
    }
});

// Watch slot_mulai and update slot_selesai
watch(() => form.slot_mulai, (newStart) => {
    if (form.mata_kuliah_id) {
        const course = props.courses.find((c) => c.id === Number(form.mata_kuliah_id));
        if (course) {
            form.slot_selesai = Math.min(13, Number(newStart) + Number(course.sks) - 1);
        }
    }
});

const triggerValidation = computed(() => {
    return {
        hari: form.hari,
        slot_mulai: form.slot_mulai,
        slot_selesai: form.slot_selesai,
        ruangan_id: form.ruangan_id,
        dosen_id: form.dosen_id,
        kelas_id: form.kelas_id,
        mata_kuliah_id: form.mata_kuliah_id,
    };
});

watch(triggerValidation, async (newVal) => {
    if (
        !newVal.hari ||
        !newVal.slot_mulai ||
        !newVal.slot_selesai ||
        !newVal.ruangan_id ||
        !newVal.dosen_id ||
        !newVal.kelas_id ||
        !newVal.mata_kuliah_id
    ) {
        hasConflict.value = false;
        conflictMessage.value = '';
        conflictType.value = '';
        return;
    }

    conflictChecking.value = true;
    try {
        const response = await axios.post(route('admin.jadwal.validate'), {
            hari: newVal.hari,
            slot_mulai: Number(newVal.slot_mulai),
            slot_selesai: Number(newVal.slot_selesai),
            ruangan_id: Number(newVal.ruangan_id),
            dosen_id: Number(newVal.dosen_id),
            kelas_id: Number(newVal.kelas_id),
            mata_kuliah_id: Number(newVal.mata_kuliah_id),
            ignore_id: editingSchedule.value ? editingSchedule.value.id : null,
        });

        if (response.data.conflict) {
            hasConflict.value = true;
            conflictType.value = response.data.type || '';
            conflictMessage.value = response.data.message;
        } else {
            hasConflict.value = false;
            conflictType.value = '';
            conflictMessage.value = '';
        }
    } catch (error) {
        console.error('Validation error:', error);
    } finally {
        conflictChecking.value = false;
    }
}, { deep: true });

function submit() {
    if (hasConflict.value) {
        alert(`${conflictTitle.value}\n\n${conflictMessage.value}`);
        return;
    }

    if (editingSchedule.value) {
        form.put(route('admin.jadwal.update', editingSchedule.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('admin.jadwal.store'), {
            onSuccess: () => closeModal(),
        });
    }
}

// --- Konfirmasi hapus jadwal via Modal.vue (menggantikan window.confirm) ---
const isDeleteModalOpen = ref(false);
const scheduleToDelete = ref(null);
const deleteForm = useForm({});

function askDelete(sched) {
    scheduleToDelete.value = sched;
    isDeleteModalOpen.value = true;
}

function closeDeleteModal() {
    isDeleteModalOpen.value = false;
}

function confirmDelete() {
    if (!scheduleToDelete.value) return;
    deleteForm.delete(route('admin.jadwal.destroy', scheduleToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => closeDeleteModal(),
        onError: () => closeDeleteModal(),
    });
}

function resetFilters() {
    searchQuery.value = '';
    filterHari.value = '';
    filterDosen.value = '';
    filterRuangan.value = '';
}

const activeFilterCount = computed(() => {
    let count = 0;
    if (filterHari.value) count++;
    if (filterDosen.value) count++;
    if (filterRuangan.value) count++;
    return count;
});

// Filter dan Sort Jadwal berdasarkan Search Query + Filter Dropdown
const sortedSchedules = computed(() => {
    let filtered = [...props.schedules];

    if (searchQuery.value.trim() !== '') {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(
            (sched) =>
                sched.mata_kuliah?.nama?.toLowerCase().includes(query) ||
                sched.dosen?.nama?.toLowerCase().includes(query) ||
                sched.kelas?.nama_kelas?.toLowerCase().includes(query) ||
                sched.ruangan?.nama_ruangan?.toLowerCase().includes(query) ||
                sched.hari?.toLowerCase().includes(query),
        );
    }

    if (filterHari.value) {
        filtered = filtered.filter((sched) => sched.hari === filterHari.value);
    }

    if (filterDosen.value) {
        filtered = filtered.filter((sched) => String(sched.dosen_id) === String(filterDosen.value));
    }

    if (filterRuangan.value) {
        filtered = filtered.filter((sched) => String(sched.ruangan_id) === String(filterRuangan.value));
    }

    return filtered.sort((a, b) => {
        const dayWeights = { Senin: 1, Selasa: 2, Rabu: 3, Kamis: 4, Jumat: 5 };
        const dayA = dayWeights[a.hari] || 9;
        const dayB = dayWeights[b.hari] || 9;
        if (dayA !== dayB) return dayA - dayB;
        return a.slot_mulai - b.slot_mulai;
    });
});
</script>

<template>
    <Head title="Kelola Jadwal Kuliah" />

    <AuthenticatedLayout>
        <div class="py-8 bg-slate-50 dark:bg-gray-900 min-h-screen">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Header Section -->
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between bg-white dark:bg-gray-800 p-6 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm">
                    <div>
                        <h2 class="text-2xl font-extrabold text-slate-900 dark:text-gray-100 tracking-tight">
                            Manajemen Penjadwalan Kuliah
                        </h2>
                        <p class="text-sm text-slate-500 dark:text-gray-400 mt-1">
                            Manajemen jadwal mata kuliah semester aktif
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
                            Tambah Jadwal
                        </button>
                    </div>
                </div>

                <!-- Semester Warning -->
                <div
                    v-if="!activeSemester"
                    class="bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-2xl p-6 shadow-sm flex items-start gap-3"
                >
                    <svg class="h-5 w-5 flex-shrink-0 mt-0.5" style="color: #b91c1c;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <p class="text-sm font-semibold" style="color: #b91c1c;">
                        Tidak ada semester aktif. Silakan aktifkan semester terlebih dahulu pada menu
                        <Link :href="route('admin.semester.index')" class="underline">Manajemen Semester</Link>.
                    </p>
                </div>

                <div v-else class="bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-2xl overflow-hidden shadow-sm">
                    <!-- Info Semester Aktif -->
                    <div class="p-6 border-b border-slate-200 dark:border-gray-700">
                        <h3 class="text-sm font-bold text-slate-600 dark:text-gray-400 uppercase tracking-wider">
                            Semester Aktif: <span class="text-blue-900 dark:text-blue-400">{{ activeSemester.nama }} {{ activeSemester.tahun_ajaran }}</span>
                        </h3>
                    </div>

                    <!-- Search & Toggle Filter Bar -->
                    <div class="p-4 sm:p-6 border-b border-slate-200 dark:border-gray-700 bg-slate-50/60 dark:bg-gray-900/20 space-y-4">
                        <div class="flex flex-col sm:flex-row gap-3 items-center">
                            <!-- Search Input -->
                            <div class="relative w-full">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </span>
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Cari matkul, dosen, kelas, ruangan, atau hari..."
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

                            <!-- Toggle Advanced Filter Button -->
                            <button
                                @click="showAdvancedFilter = !showAdvancedFilter"
                                type="button"
                                :class="[
                                    'inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition-all shadow-sm whitespace-nowrap w-full sm:w-auto',
                                    activeFilterCount > 0
                                        ? 'bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-950/40 dark:text-blue-400 dark:border-blue-900'
                                        : 'bg-white dark:bg-gray-900 text-slate-700 dark:text-gray-200 border border-slate-200 dark:border-gray-700 hover:bg-slate-50 dark:hover:bg-gray-800'
                                ]"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707v4.172a1 1 0 01-.553.894l-4 2A1 1 0 017 18.172v-4.172a1 1 0 00-.293-.707L.293 7.293A1 1 0 010 6.586V4z" />
                                </svg>
                                Filter Lanjutan
                                <span v-if="activeFilterCount > 0" class="inline-flex items-center justify-center bg-blue-600 text-white rounded-full h-5 w-5 text-[10px]">
                                    {{ activeFilterCount }}
                                </span>
                            </button>
                        </div>

                        <!-- Collapsible Dropdown Filters Panel -->
                        <div v-show="showAdvancedFilter" class="pt-2 border-t border-slate-200/60 dark:border-gray-700/60 space-y-3 transition-all">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-bold text-slate-500 dark:text-gray-400 uppercase tracking-wider">
                                    Pilih Kriteria Filter
                                </span>
                                <button
                                    v-if="activeFilterCount > 0"
                                    @click="resetFilters"
                                    type="button"
                                    class="inline-flex items-center gap-1 text-xs font-semibold text-slate-400 dark:text-gray-500 hover:text-rose-600 dark:hover:text-rose-400 transition-colors"
                                >
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    Reset semua filter
                                </button>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <select
                                    v-model="filterHari"
                                    class="w-full bg-white dark:bg-gray-900 border border-slate-200 dark:border-gray-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-700 dark:text-gray-200 shadow-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                >
                                    <option value="">Semua Hari</option>
                                    <option v-for="d in days" :key="d" :value="d">{{ d }}</option>
                                </select>

                                <select
                                    v-model="filterDosen"
                                    class="w-full bg-white dark:bg-gray-900 border border-slate-200 dark:border-gray-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-700 dark:text-gray-200 shadow-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                >
                                    <option value="">Semua Dosen</option>
                                    <option v-for="d in lecturers" :key="d.id" :value="d.id">{{ d.nama }}</option>
                                </select>

                                <select
                                    v-model="filterRuangan"
                                    class="w-full bg-white dark:bg-gray-900 border border-slate-200 dark:border-gray-700 rounded-xl px-3.5 py-2.5 text-sm text-slate-700 dark:text-gray-200 shadow-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                >
                                    <option value="">Semua Ruangan</option>
                                    <option v-for="r in rooms" :key="r.id" :value="r.id">{{ r.nama_ruangan }}</option>
                                </select>
                            </div>
                        </div>

                        <p class="text-xs text-slate-400 dark:text-gray-500">
                            Menampilkan <span class="font-semibold text-slate-500 dark:text-gray-400">{{ sortedSchedules.length }}</span> dari {{ schedules.length }} jadwal
                        </p>
                    </div>

                    <!-- Responsive Table Container -->
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse min-w-[750px]">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-gray-900/60 border-b border-slate-200 dark:border-gray-700">
                                    <th class="w-[50px] px-4 sm:px-6 py-4 text-xs font-bold text-slate-500 dark:text-gray-400 tracking-wider text-center">No</th>
                                    <th class="px-4 sm:px-6 py-4 text-xs font-bold text-slate-500 dark:text-gray-400 tracking-wider">Hari</th>
                                    <th class="px-4 sm:px-6 py-4 text-xs font-bold text-slate-500 dark:text-gray-400 tracking-wider">Waktu</th>
                                    <th class="px-4 sm:px-6 py-4 text-xs font-bold text-slate-500 dark:text-gray-400 tracking-wider">Mata Kuliah</th>
                                    <th class="px-4 sm:px-6 py-4 text-xs font-bold text-slate-500 dark:text-gray-400 tracking-wider text-center">Kelas</th>
                                    <th class="px-4 sm:px-6 py-4 text-xs font-bold text-slate-500 dark:text-gray-400 tracking-wider">Ruangan</th>
                                    <th class="px-4 sm:px-6 py-4 text-xs font-bold text-slate-500 dark:text-gray-400 tracking-wider">Dosen Pengampu</th>
                                    <th class="px-4 sm:px-6 py-4 text-xs font-bold text-slate-500 dark:text-gray-400 tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(sched, index) in sortedSchedules"
                                    :key="sched.id"
                                    class="border-b border-slate-100 dark:border-gray-700/50 hover:bg-slate-50/60 dark:hover:bg-gray-900/10 transition-colors"
                                >
                                    <td class="px-4 sm:px-6 py-4 text-sm text-slate-500 dark:text-gray-400 text-center">{{ index + 1 }}</td>
                                    <td class="px-4 sm:px-6 py-4 text-sm font-semibold text-slate-800 dark:text-gray-200">{{ sched.hari }}</td>
                                    <td class="px-4 sm:px-6 py-4 text-sm text-slate-600 dark:text-gray-400 whitespace-nowrap">
                                        {{ slots[sched.slot_mulai]?.split(' - ')[0] }} - {{ slots[sched.slot_selesai]?.split(' - ')[1] }}
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 text-sm text-slate-700 dark:text-gray-300 font-semibold">{{ sched.mata_kuliah?.nama }}</td>
                                    <td class="px-4 sm:px-6 py-4 text-sm text-slate-800 dark:text-gray-200 text-center">
                                        <span class="bg-slate-100 dark:bg-gray-900 px-2 py-1 rounded text-xs font-bold border border-slate-200 dark:border-gray-800">{{ sched.kelas?.nama_kelas }}</span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 text-sm text-slate-700 dark:text-gray-300">{{ sched.ruangan?.nama_ruangan }}</td>
                                    <td class="px-4 sm:px-6 py-4 text-sm text-slate-700 dark:text-gray-300 font-semibold">{{ sched.dosen?.nama }}</td>
                                    <td class="px-4 sm:px-6 py-4 text-sm text-right flex justify-end gap-2 whitespace-nowrap">
                                        <button
                                            @click="openEditModal(sched)"
                                            class="px-3 py-1.5 rounded-lg text-xs font-bold text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-950/20 transition-all border border-transparent hover:border-blue-200 dark:hover:border-blue-900/30"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            @click="askDelete(sched)"
                                            class="px-3 py-1.5 rounded-lg text-xs font-bold text-rose-600 dark:text-red-400 hover:bg-rose-50 dark:hover:bg-red-950/20 transition-all border border-transparent hover:border-rose-200 dark:hover:border-red-900/30"
                                        >
                                            Hapus
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="sortedSchedules.length === 0">
                                    <td colspan="8" class="px-6 py-12 text-center text-sm text-slate-500 dark:text-gray-400">
                                        {{ schedules.length === 0 ? 'Belum ada jadwal kuliah.' : 'Jadwal yang dicari tidak ditemukan. Coba ubah kata kunci atau filter.' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add/Edit Modal -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-3 sm:p-4 overflow-y-auto">
            <div class="bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700/60 w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden transition-all my-auto">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="font-bold text-slate-800 dark:text-gray-100 text-lg">
                        {{ editingSchedule ? 'Edit Jadwal Perkuliahan' : 'Tambah Jadwal Kuliah Baru' }}
                    </h3>
                    <button @click="closeModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-gray-200 font-bold text-xl">&times;</button>
                </div>

                <form @submit.prevent="submit" class="p-6 flex flex-col gap-4 max-h-[75vh] overflow-y-auto">
                    <!-- Real-time conflict Warning Card: sticky rapat di tepi atas area scroll agar tetap terlihat -->
                    <div
                        v-if="conflictChecking || hasConflict"
                        class="sticky -top-6 -mx-6 -mt-6 z-20 space-y-3 border-b border-slate-100 bg-white/95 px-6 pt-6 pb-4 mb-2 shadow-sm backdrop-blur-sm dark:border-gray-700/60 dark:bg-gray-800/95"
                    >
                        <div v-if="conflictChecking" class="bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-900/50 p-4 rounded-xl text-blue-800 dark:text-blue-400 text-xs font-semibold flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89H18" />
                            </svg>
                            Memvalidasi konflik ruangan, dosen & kelas secara real-time...
                        </div>

                        <div v-if="hasConflict" class="bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900/50 p-4 rounded-xl text-rose-800 dark:text-rose-400 text-xs">
                            <strong class="font-extrabold text-sm block mb-1">{{ conflictTitle }}</strong>
                            {{ conflictMessage }}
                        </div>
                    </div>

                    <!-- Mata Kuliah Selection -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Mata Kuliah</label>
                        <SearchableSelect
                            v-model="form.mata_kuliah_id"
                            :options="courseOptions"
                            placeholder="Ketik atau pilih mata kuliah..."
                            :error="!!form.errors.mata_kuliah_id"
                        />
                        <div v-if="form.errors.mata_kuliah_id" class="text-xs text-red-500 mt-1">{{ form.errors.mata_kuliah_id }}</div>
                    </div>

                    <!-- Kelas Selection -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Kelas</label>
                        <SearchableSelect
                            v-model="form.kelas_id"
                            :options="classOptions"
                            placeholder="Ketik atau pilih kelas..."
                            :error="!!form.errors.kelas_id"
                        />
                        <div v-if="form.errors.kelas_id" class="text-xs text-red-500 mt-1">{{ form.errors.kelas_id }}</div>
                    </div>

                    <!-- Dosen Selection -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Dosen Pengampu</label>
                        <SearchableSelect
                            v-model="form.dosen_id"
                            :options="lecturerOptions"
                            placeholder="Ketik atau pilih dosen..."
                            :error="!!form.errors.dosen_id"
                        />
                        <div v-if="form.errors.dosen_id" class="text-xs text-red-500 mt-1">{{ form.errors.dosen_id }}</div>
                    </div>

                    <!-- Ruangan Selection -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Ruangan</label>
                        <SearchableSelect
                            v-model="form.ruangan_id"
                            :options="roomOptions"
                            placeholder="Ketik atau pilih ruangan..."
                            :error="!!form.errors.ruangan_id"
                        />
                        <div v-if="form.errors.ruangan_id" class="text-xs text-red-500 mt-1">{{ form.errors.ruangan_id }}</div>
                    </div>

                    <!-- Hari Selection -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Hari</label>
                        <select
                            v-model="form.hari"
                            class="w-full bg-slate-50 dark:bg-gray-900 border border-slate-200 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-slate-800 dark:text-gray-100 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                            required
                        >
                            <option v-for="d in days" :key="d" :value="d">{{ d }}</option>
                        </select>
                        <div v-if="form.errors.hari" class="text-xs text-red-500 mt-1">{{ form.errors.hari }}</div>
                    </div>

                    <!-- Slot Waktu Selection -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Slot Mulai</label>
                            <select
                                v-model="form.slot_mulai"
                                class="w-full bg-slate-50 dark:bg-gray-900 border border-slate-200 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-slate-800 dark:text-gray-100 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                required
                            >
                                <option v-for="(time, num) in slots" :key="num" :value="Number(num)">
                                    Slot {{ num }} ({{ time }})
                                </option>
                            </select>
                            <div v-if="form.errors.slot_mulai" class="text-xs text-red-500 mt-1">{{ form.errors.slot_mulai }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Slot Selesai</label>
                            <select
                                v-model="form.slot_selesai"
                                class="w-full bg-slate-50 dark:bg-gray-900 border border-slate-200 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-slate-800 dark:text-gray-100 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                required
                            >
                                <option v-for="(time, num) in slots" :key="num" :value="Number(num)">
                                    Slot {{ num }} ({{ time }})
                                </option>
                            </select>
                            <div v-if="form.errors.slot_selesai" class="text-xs text-red-500 mt-1">{{ form.errors.slot_selesai }}</div>
                        </div>
                    </div>

                    <!-- Actions -->
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
                            :disabled="form.processing || hasConflict || conflictChecking"
                            class="px-5 py-2 rounded-xl text-sm font-bold bg-blue-600 hover:bg-blue-700 text-white transition-all shadow-sm disabled:opacity-50"
                        >
                            Simpan Jadwal
                        </button>
                    </div>
                </form>
            </div>
        </div>

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
                            Hapus Jadwal Perkuliahan?
                        </h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">
                            Anda akan menghapus jadwal
                            <span class="font-semibold text-slate-700 dark:text-gray-200">
                                {{ scheduleToDelete?.mata_kuliah?.nama }}
                            </span>
                            untuk kelas
                            <span class="font-semibold text-slate-700 dark:text-gray-200">
                                {{ scheduleToDelete?.kelas?.nama_kelas }}
                            </span>
                            pada hari {{ scheduleToDelete?.hari }}. Tindakan ini tidak dapat dibatalkan.
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
                        Ya, Hapus Jadwal
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>