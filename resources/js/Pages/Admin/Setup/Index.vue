<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    pengaturan: Object,
    kelasDibuka: Array,
    courses: Array,
    lecturers: Array,
    dbRoomCount: Number,
    dbRooms: Array,
});

// Mapping warna badge semester (disesuaikan dengan skema warna yang ditentukan)
const SEMESTER_BADGE_STYLES = {
    1: 'bg-amber-50 border-amber-200 text-amber-900 dark:bg-amber-950/30 dark:border-amber-900 dark:text-amber-400',
    2: 'bg-blue-50 border-blue-200 text-blue-900 dark:bg-blue-950/30 dark:border-blue-900 dark:text-blue-400',
    3: 'bg-purple-50 border-purple-200 text-purple-900 dark:bg-purple-950/30 dark:border-purple-900 dark:text-purple-400',
    4: 'bg-red-50 border-red-200 text-red-900 dark:bg-red-950/30 dark:border-red-900 dark:text-red-400',
    5: 'bg-fuchsia-50 border-fuchsia-200 text-fuchsia-900 dark:bg-fuchsia-950/30 dark:border-fuchsia-900 dark:text-fuchsia-400',
    6: 'bg-emerald-50 border-emerald-200 text-emerald-900 dark:bg-emerald-950/30 dark:border-emerald-900 dark:text-emerald-400',
    7: 'bg-cyan-50 border-cyan-200 text-cyan-900 dark:bg-cyan-950/30 dark:border-cyan-900 dark:text-cyan-400',
    8: 'bg-orange-50 border-orange-200 text-orange-900 dark:bg-orange-950/30 dark:border-orange-900 dark:text-orange-400',
};
const DEFAULT_SEMESTER_BADGE_STYLE = 'bg-slate-50 border-slate-200 text-slate-700 dark:bg-gray-900/40 dark:border-gray-700 dark:text-gray-300';

function semesterBadgeClass(semester) {
    return SEMESTER_BADGE_STYLES[semester] || DEFAULT_SEMESTER_BADGE_STYLE;
}

// Form Parameter Kampus
const formPengaturan = useForm({
    max_kelas_per_semester: props.pengaturan?.max_kelas_per_semester || 3,
});

const submitPengaturan = () => {
    formPengaturan.post(route('admin.setup.updatePengaturan'), {
        preserveScroll: true,
    });
};

// Form Release Status
const releaseForm = useForm({});
const toggleRelease = () => {
    releaseForm.post(route('admin.setup.toggleRelease'), {
        preserveScroll: true,
    });
};

// Form Buka Kelas & Multi Dosen
const jumlahKelas = ref(1);
const formKelas = useForm({
    mata_kuliah_id: '',
    dosen_ids: [''],
});

// Searchable dosen combobox state (satu entry per slot kelas)
const dosenSearch = ref(['']);
const dosenDropdownOpen = ref([false]);

function resizeArray(arr, val, fillValue) {
    const current = [...arr];
    if (val > current.length) {
        for (let i = current.length; i < val; i++) {
            current.push(fillValue);
        }
    } else {
        current.length = val;
    }
    return current;
}

// Watch jumlahKelas and dynamically expand/shrink dosen selector arrays
watch(jumlahKelas, (newVal) => {
    const val = Math.max(1, Math.min(10, Number(newVal) || 1));
    formKelas.dosen_ids = resizeArray(formKelas.dosen_ids, val, '');
    dosenSearch.value = resizeArray(dosenSearch.value, val, '');
    dosenDropdownOpen.value = resizeArray(dosenDropdownOpen.value, val, false);
});

function filteredLecturers(idx) {
    const query = (dosenSearch.value[idx] || '').toLowerCase().trim();
    if (!query) return props.lecturers;
    return props.lecturers.filter((d) => d.nama?.toLowerCase().includes(query));
}

function openDosenDropdown(idx) {
    dosenDropdownOpen.value[idx] = true;
}

function closeDosenDropdown(idx) {
    // delay agar event click pada opsi list sempat tertangkap
    setTimeout(() => {
        dosenDropdownOpen.value[idx] = false;
    }, 150);
}

function selectDosen(idx, dosen) {
    formKelas.dosen_ids[idx] = dosen.id;
    dosenSearch.value[idx] = dosen.nama;
    dosenDropdownOpen.value[idx] = false;
}

function clearDosen(idx) {
    formKelas.dosen_ids[idx] = '';
    dosenSearch.value[idx] = '';
}

const submitKelas = () => {
    formKelas.post(route('admin.setup.storeKelasDibuka'), {
        preserveScroll: true,
        onSuccess: () => {
            formKelas.reset('mata_kuliah_id');
            jumlahKelas.value = 1;
            formKelas.dosen_ids = [''];
            dosenSearch.value = [''];
            dosenDropdownOpen.value = [false];
        },
    });
};

// --- Konfirmasi hapus kelas dibuka via Modal.vue ---
const isDeleteModalOpen = ref(false);
const kelasToDelete = ref(null);
const deleteKelasForm = useForm({});

function askDeleteKelas(kelas) {
    kelasToDelete.value = kelas;
    isDeleteModalOpen.value = true;
}

function closeDeleteModal() {
    isDeleteModalOpen.value = false;
}

function confirmDeleteKelas() {
    if (!kelasToDelete.value) return;
    deleteKelasForm.delete(route('admin.setup.destroyKelasDibuka', kelasToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => closeDeleteModal(),
        onError: () => closeDeleteModal(),
    });
}

// --- Filter Daftar Kelas Dibuka (Mata Kuliah & Dosen, searchable/typeable) ---
const filterMatkul = ref('');
const filterDosen = ref('');
const filterMatkulOpen = ref(false);
const filterDosenOpen = ref(false);

// Opsi saran diambil dari data kelas yang benar-benar ada (bukan seluruh master data),
// supaya saran filter selalu relevan dengan isi tabel.
const uniqueMatkulOptions = computed(() => {
    const seen = new Map();
    (props.kelasDibuka || []).forEach((k) => {
        if (k.mata_kuliah?.id && !seen.has(k.mata_kuliah.id)) {
            seen.set(k.mata_kuliah.id, k.mata_kuliah);
        }
    });
    return Array.from(seen.values());
});

const uniqueDosenOptions = computed(() => {
    const seen = new Map();
    (props.kelasDibuka || []).forEach((k) => {
        if (k.dosen?.id && !seen.has(k.dosen.id)) {
            seen.set(k.dosen.id, k.dosen);
        }
    });
    return Array.from(seen.values());
});

const filteredMatkulSuggestions = computed(() => {
    const query = filterMatkul.value.toLowerCase().trim();
    if (!query) return uniqueMatkulOptions.value;
    return uniqueMatkulOptions.value.filter((m) => m.nama?.toLowerCase().includes(query));
});

const filteredDosenSuggestions = computed(() => {
    const query = filterDosen.value.toLowerCase().trim();
    if (!query) return uniqueDosenOptions.value;
    return uniqueDosenOptions.value.filter((d) => d.nama?.toLowerCase().includes(query));
});

const filteredKelasDibuka = computed(() => {
    const matkulQuery = filterMatkul.value.toLowerCase().trim();
    const dosenQuery = filterDosen.value.toLowerCase().trim();

    return (props.kelasDibuka || []).filter((k) => {
        const matchMatkul = !matkulQuery || k.mata_kuliah?.nama?.toLowerCase().includes(matkulQuery);
        const matchDosen = !dosenQuery || k.dosen?.nama?.toLowerCase().includes(dosenQuery);
        return matchMatkul && matchDosen;
    });
});

function openFilterMatkulDropdown() {
    filterMatkulOpen.value = true;
}

function closeFilterMatkulDropdown() {
    setTimeout(() => {
        filterMatkulOpen.value = false;
    }, 150);
}

function selectFilterMatkul(matkul) {
    filterMatkul.value = matkul.nama;
    filterMatkulOpen.value = false;
}

function clearFilterMatkul() {
    filterMatkul.value = '';
}

function openFilterDosenDropdown() {
    filterDosenOpen.value = true;
}

function closeFilterDosenDropdown() {
    setTimeout(() => {
        filterDosenOpen.value = false;
    }, 150);
}

function selectFilterDosen(dosen) {
    filterDosen.value = dosen.nama;
    filterDosenOpen.value = false;
}

function clearFilterDosen() {
    filterDosen.value = '';
}

function resetFilters() {
    filterMatkul.value = '';
    filterDosen.value = '';
}
</script>

<template>
    <Head title="Setup Kampus & Kelas Dibuka" />

    <AuthenticatedLayout>
        <div class="py-8 bg-slate-50 dark:bg-gray-900 min-h-screen">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Header Section -->
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between bg-white dark:bg-gray-800 p-6 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm">
                    <div>
                        <div class="flex flex-wrap items-center gap-2.5">
                            <h2 class="text-2xl font-extrabold text-slate-900 dark:text-gray-100 tracking-tight">
                                Setup Kampus &amp; Kelas Dibuka
                            </h2>
                            <span
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold border"
                                :class="pengaturan?.is_released
                                    ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-400 dark:border-emerald-900'
                                    : 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-900'"
                            >
                                <span class="h-1.5 w-1.5 rounded-full" :class="pengaturan?.is_released ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                                {{ pengaturan?.is_released ? 'Published' : 'Draft' }}
                            </span>
                        </div>
                        <p class="text-sm text-slate-500 dark:text-gray-400 mt-1">
                            Parameter kampus &amp; alokasi kelas untuk semester aktif
                        </p>
                    </div>

                    <div class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center gap-2.5">
                        <button
                            @click="toggleRelease"
                            :disabled="releaseForm.processing"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition shadow-sm whitespace-nowrap disabled:opacity-50"
                            :class="pengaturan?.is_released
                                ? 'bg-white dark:bg-gray-900 text-slate-700 dark:text-gray-200 border border-slate-200 dark:border-gray-700 hover:bg-slate-50 dark:hover:bg-gray-800'
                                : 'bg-emerald-600 hover:bg-emerald-700 text-white'"
                        >
                            <svg v-if="pengaturan?.is_released" class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                            </svg>
                            <svg v-else class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.5 19.5l7.5-7.5m0 0l7.5 7.5m-7.5-7.5V3" />
                            </svg>
                            {{ pengaturan?.is_released ? 'Tarik Alokasi Kelas' : 'Publish Alokasi Kelas' }}
                        </button>
                    </div>
                </div>

                <!-- Alert Flash Messages -->
                <div
                    v-if="$page.props.flash?.success"
                    class="p-4 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-900/40 text-emerald-800 dark:text-emerald-400 rounded-xl font-medium text-sm"
                >
                    {{ $page.props.flash.success }}
                </div>

                <!-- Card 1: Pengaturan Parameter Kampus -->
                <div class="bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-2xl overflow-hidden shadow-sm">
                    <div class="p-4 sm:p-6 border-b border-slate-200 dark:border-gray-700 bg-slate-50/60 dark:bg-gray-900/20">
                        <h3 class="text-base font-bold text-slate-800 dark:text-gray-100">
                            Parameter Utama Kampus
                        </h3>
                    </div>

                    <div class="p-4 sm:p-6">
                        <form @submit.prevent="submitPengaturan" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">
                                    Max Kelas / Semester / Sesi
                                </label>
                                <input
                                    v-model="formPengaturan.max_kelas_per_semester"
                                    type="number"
                                    min="1"
                                    max="10"
                                    required
                                    class="w-full bg-slate-50 dark:bg-gray-900 border border-slate-200 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-slate-800 dark:text-gray-100 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">
                                    Total Ruangan Aktif
                                </label>
                                <div class="w-full py-2.5 px-4 rounded-xl border border-slate-200 dark:border-gray-700 bg-slate-100 dark:bg-gray-900 text-slate-800 dark:text-gray-100 font-bold text-sm">
                                    {{ dbRoomCount }} Ruangan
                                </div>
                            </div>

                            <div>
                                <button
                                    type="submit"
                                    :disabled="formPengaturan.processing"
                                    class="w-full py-2.5 px-4 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold text-sm shadow-sm transition-all disabled:opacity-50"
                                >
                                    Simpan Parameter
                                </button>
                            </div>
                        </form>

                        <!-- Info list ruangan terdaftar -->
                        <div class="mt-5 pt-4 border-t border-slate-100 dark:border-gray-700">
                            <p class="text-xs font-bold text-slate-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                                Daftar {{ dbRoomCount }} Ruangan Terdaftar
                            </p>
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span
                                    v-for="r in dbRooms"
                                    :key="r.id"
                                    class="px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-slate-100 dark:bg-gray-900 text-slate-600 dark:text-gray-300 border border-slate-200 dark:border-gray-700"
                                >
                                    {{ r.nama_ruangan }}
                                </span>
                                <span v-if="!dbRooms || dbRooms.length === 0" class="text-xs text-slate-400 dark:text-gray-500">
                                    Belum ada ruangan terdaftar.
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Pengaturan Kelas Dibuka -->
                <div class="bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-2xl overflow-hidden shadow-sm">
                    <div class="p-4 sm:p-6 border-b border-slate-200 dark:border-gray-700 bg-slate-50/60 dark:bg-gray-900/20">
                        <h3 class="text-base font-bold text-slate-800 dark:text-gray-100">
                            Buka Kelas &amp; Alokasi Dosen Pengampu
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-gray-400 mt-1">
                            Nama kelas (A, B, C, dst.) diurutkan otomatis oleh sistem setelah Auto-Schedule.
                        </p>
                    </div>

                    <div class="p-4 sm:p-6">
                        <!-- Form Tambah Kelas Dibuka -->
                        <form @submit.prevent="submitKelas" class="space-y-5 bg-slate-50 dark:bg-gray-900/40 p-4 sm:p-5 rounded-2xl border border-slate-200 dark:border-gray-700">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">
                                        Mata Kuliah
                                    </label>
                                    <select
                                        v-model="formKelas.mata_kuliah_id"
                                        required
                                        class="w-full bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-slate-800 dark:text-gray-100 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                    >
                                        <option value="" disabled>Pilih mata kuliah</option>
                                        <option v-for="c in courses" :key="c.id" :value="c.id">
                                            [Sem {{ c.semester }}] {{ c.nama }} ({{ c.sks }} SKS)
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">
                                        Jumlah Kelas Dibuka
                                    </label>
                                    <input
                                        v-model="jumlahKelas"
                                        type="number"
                                        min="1"
                                        max="10"
                                        required
                                        class="w-full bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-slate-800 dark:text-gray-100 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                    />
                                </div>
                            </div>

                            <!-- Dynamic Dosen Selector Rows based on jumlahKelas -->
                            <div class="border-t border-slate-200 dark:border-gray-700 pt-4">
                                <label class="block text-xs font-bold text-slate-500 dark:text-gray-400 uppercase tracking-wider mb-3">
                                    Dosen Pengampu untuk {{ jumlahKelas }} Kelas
                                </label>

                                <!--
                                    Saat hanya 1 kelas dibuka, gunakan layout satu kolom yang
                                    ringkas (tidak melebar penuh & tidak "nyasar" di grid 3 kolom).
                                    Saat lebih dari 1, gunakan grid responsif seperti semula.
                                -->
                                <div
                                    class="grid gap-3"
                                    :class="jumlahKelas === 1 ? 'grid-cols-1 max-w-sm' : 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3'"
                                >
                                    <div
                                        v-for="(dosenId, idx) in formKelas.dosen_ids"
                                        :key="idx"
                                        class="relative bg-white dark:bg-gray-800 p-3 rounded-xl border border-slate-200 dark:border-gray-700"
                                    >
                                        <label class="block text-[11px] font-bold text-blue-600 dark:text-blue-400 mb-1.5">
                                            Slot Kelas #{{ idx + 1 }}
                                        </label>

                                        <div class="relative">
                                            <input
                                                v-model="dosenSearch[idx]"
                                                type="text"
                                                placeholder="Ketik untuk cari dosen..."
                                                autocomplete="off"
                                                @focus="openDosenDropdown(idx)"
                                                @blur="closeDosenDropdown(idx)"
                                                @input="formKelas.dosen_ids[idx] = ''"
                                                class="w-full bg-slate-50 dark:bg-gray-900 border border-slate-200 dark:border-gray-700 rounded-lg pl-3 pr-8 py-2 text-xs text-slate-800 dark:text-gray-100 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                                :class="{ 'border-red-400': !formKelas.dosen_ids[idx] && dosenSearch[idx] }"
                                            />
                                            <button
                                                v-if="dosenSearch[idx]"
                                                type="button"
                                                @mousedown.prevent="clearDosen(idx)"
                                                class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400 hover:text-slate-600 dark:hover:text-gray-200"
                                                aria-label="Hapus pilihan"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>

                                            <!-- input tersembunyi untuk validasi required -->
                                            <input type="hidden" v-model="formKelas.dosen_ids[idx]" required />

                                            <!-- Dropdown hasil pencarian -->
                                            <ul
                                                v-if="dosenDropdownOpen[idx]"
                                                class="absolute z-20 mt-1.5 w-full max-h-48 overflow-y-auto bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-lg shadow-lg py-1"
                                            >
                                                <li
                                                    v-for="d in filteredLecturers(idx)"
                                                    :key="d.id"
                                                    @mousedown.prevent="selectDosen(idx, d)"
                                                    class="px-3 py-2 text-xs text-slate-700 dark:text-gray-200 hover:bg-blue-50 dark:hover:bg-blue-950/30 cursor-pointer"
                                                    :class="{ 'bg-blue-50 dark:bg-blue-950/30 font-semibold': formKelas.dosen_ids[idx] === d.id }"
                                                >
                                                    {{ d.nama }}
                                                </li>
                                                <li v-if="filteredLecturers(idx).length === 0" class="px-3 py-2 text-xs text-slate-400 dark:text-gray-500">
                                                    Dosen tidak ditemukan.
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end pt-1">
                                <button
                                    type="submit"
                                    :disabled="formKelas.processing"
                                    class="w-full sm:w-auto py-2.5 px-6 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold text-sm shadow-sm transition-all disabled:opacity-50"
                                >
                                    Simpan &amp; Buka {{ jumlahKelas }} Kelas
                                </button>
                            </div>
                        </form>

                        <!-- Filter Daftar Kelas Dibuka: Mata Kuliah & Dosen (searchable/typeable) -->
                        <div class="mt-6 bg-slate-50 dark:bg-gray-900/40 border border-slate-200 dark:border-gray-700 rounded-2xl p-4 sm:p-5">
                            <div class="flex items-center justify-between gap-3 mb-3">
                                <h4 class="flex items-center gap-1.5 text-xs font-bold text-slate-500 dark:text-gray-400 uppercase tracking-wider">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4.5h18M6 9h12M9 13.5h6M11.25 18h1.5" />
                                    </svg>
                                    Filter Data
                                </h4>
                                <button
                                    v-if="filterMatkul || filterDosen"
                                    type="button"
                                    @click="resetFilters"
                                    class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline whitespace-nowrap"
                                >
                                    Reset Filter
                                </button>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <!-- Filter Mata Kuliah -->
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-400 dark:text-gray-500 mb-1.5">
                                        Cari Mata Kuliah
                                    </label>
                                    <div class="relative">
                                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 dark:text-gray-500 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z" />
                                        </svg>
                                        <input
                                            v-model="filterMatkul"
                                            type="text"
                                            placeholder="Ketik nama mata kuliah..."
                                            autocomplete="off"
                                            @focus="openFilterMatkulDropdown"
                                            @blur="closeFilterMatkulDropdown"
                                            class="w-full bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-xl pl-9 pr-8 py-2.5 text-sm text-slate-800 dark:text-gray-100 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                        />
                                        <button
                                            v-if="filterMatkul"
                                            type="button"
                                            @mousedown.prevent="clearFilterMatkul"
                                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 dark:hover:text-gray-200"
                                            aria-label="Hapus filter mata kuliah"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>

                                        <ul
                                            v-if="filterMatkulOpen && filteredMatkulSuggestions.length"
                                            class="absolute z-20 mt-1.5 w-full max-h-48 overflow-y-auto bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-lg shadow-lg py-1"
                                        >
                                            <li
                                                v-for="m in filteredMatkulSuggestions"
                                                :key="m.id"
                                                @mousedown.prevent="selectFilterMatkul(m)"
                                                class="px-3 py-2 text-xs text-slate-700 dark:text-gray-200 hover:bg-blue-50 dark:hover:bg-blue-950/30 cursor-pointer"
                                            >
                                                {{ m.nama }}
                                            </li>
                                        </ul>
                                    </div>
                                </div>

                                <!-- Filter Dosen -->
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-400 dark:text-gray-500 mb-1.5">
                                        Cari Dosen Pengampu
                                    </label>
                                    <div class="relative">
                                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 dark:text-gray-500 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z" />
                                        </svg>
                                        <input
                                            v-model="filterDosen"
                                            type="text"
                                            placeholder="Ketik nama dosen..."
                                            autocomplete="off"
                                            @focus="openFilterDosenDropdown"
                                            @blur="closeFilterDosenDropdown"
                                            class="w-full bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-xl pl-9 pr-8 py-2.5 text-sm text-slate-800 dark:text-gray-100 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                        />
                                        <button
                                            v-if="filterDosen"
                                            type="button"
                                            @mousedown.prevent="clearFilterDosen"
                                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 dark:hover:text-gray-200"
                                            aria-label="Hapus filter dosen"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>

                                        <ul
                                            v-if="filterDosenOpen && filteredDosenSuggestions.length"
                                            class="absolute z-20 mt-1.5 w-full max-h-48 overflow-y-auto bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-lg shadow-lg py-1"
                                        >
                                            <li
                                                v-for="d in filteredDosenSuggestions"
                                                :key="d.id"
                                                @mousedown.prevent="selectFilterDosen(d)"
                                                class="px-3 py-2 text-xs text-slate-700 dark:text-gray-200 hover:bg-blue-50 dark:hover:bg-blue-950/30 cursor-pointer"
                                            >
                                                {{ d.nama }}
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <p class="mt-3 text-[11px] font-semibold text-slate-400 dark:text-gray-500">
                                Menampilkan {{ filteredKelasDibuka.length }} dari {{ kelasDibuka?.length || 0 }} data
                            </p>
                        </div>

                        <!-- Table List Kelas Dibuka -->
                        <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-gray-700 mt-4">
                            <table class="w-full border-collapse min-w-[650px] text-left">
                                <thead>
                                    <tr class="bg-slate-50 dark:bg-gray-900/60 border-b border-slate-200 dark:border-gray-700">
                                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-gray-400 tracking-wider">Semester</th>
                                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-gray-400 tracking-wider">Mata Kuliah</th>
                                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-gray-400 tracking-wider">Dosen Pengampu</th>
                                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-gray-400 tracking-wider">Nama Kelas Final</th>
                                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-gray-400 tracking-wider text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="k in filteredKelasDibuka"
                                        :key="k.id"
                                        class="border-b border-slate-100 dark:border-gray-700/50 hover:bg-slate-50/60 dark:hover:bg-gray-900/10 transition-colors"
                                    >
                                        <td class="px-6 py-4 text-sm">
                                            <span
                                                class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold border"
                                                :class="semesterBadgeClass(k.mata_kuliah?.semester)"
                                            >
                                                Sem {{ k.mata_kuliah?.semester }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm font-semibold text-slate-800 dark:text-gray-200">
                                            {{ k.mata_kuliah?.nama }}
                                        </td>
                                        <td class="px-6 py-4 text-sm font-semibold text-slate-800 dark:text-gray-200">
                                            {{ k.dosen?.nama }}
                                        </td>
                                        <td class="px-6 py-4 text-sm font-semibold text-emerald-600 dark:text-emerald-400">
                                            Otomatis (A/B/C) setelah Auto-Schedule
                                        </td>
                                        <td class="px-6 py-4 text-sm text-center">
                                            <button
                                                @click="askDeleteKelas(k)"
                                                class="px-3 py-1.5 rounded-lg text-xs font-bold text-rose-600 dark:text-red-400 hover:bg-rose-50 dark:hover:bg-red-950/20 transition-all border border-transparent hover:border-rose-200 dark:hover:border-red-900/30"
                                            >
                                                Hapus
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Belum ada data kelas sama sekali -->
                                    <tr v-if="!kelasDibuka || kelasDibuka.length === 0">
                                        <td colspan="5" class="px-6 py-12 text-center text-sm text-slate-500 dark:text-gray-400">
                                            Belum ada kelas yang dibuka. Silakan tentukan jumlah kelas &amp; dosen melalui form di atas.
                                        </td>
                                    </tr>

                                    <!-- Ada data, tapi tidak ada yang cocok dengan filter -->
                                    <tr v-else-if="filteredKelasDibuka.length === 0">
                                        <td colspan="5" class="px-6 py-12 text-center text-sm text-slate-500 dark:text-gray-400">
                                            Tidak ada kelas yang cocok dengan filter. Coba kata kunci lain atau
                                            <button type="button" @click="resetFilters" class="font-bold text-blue-600 dark:text-blue-400 hover:underline">reset filter</button>.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Konfirmasi Hapus Kelas Dibuka -->
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
                            Hapus Alokasi Kelas Ini?
                        </h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">
                            Anda akan menghapus alokasi kelas
                            <span class="font-semibold text-slate-700 dark:text-gray-200">
                                {{ kelasToDelete?.mata_kuliah?.nama }}
                            </span>
                            dengan dosen pengampu
                            <span class="font-semibold text-slate-700 dark:text-gray-200">
                                {{ kelasToDelete?.dosen?.nama }}
                            </span>.
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
                        @click="confirmDeleteKelas"
                        :disabled="deleteKelasForm.processing"
                        class="px-5 py-2 rounded-xl text-sm font-bold bg-rose-600 hover:bg-rose-700 text-white transition-all shadow-sm disabled:opacity-50"
                    >
                        Ya, Hapus Alokasi
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>