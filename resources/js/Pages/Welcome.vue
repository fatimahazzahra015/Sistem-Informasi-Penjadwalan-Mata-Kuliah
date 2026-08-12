<script setup>
useLayout: null;
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import Navbar from '@/Components/Navbar.vue';

const props = defineProps({
    schedules: Array,
    rooms: Array,
    lecturers: Array,
    courses: Array,
    activeSemester: Object,
    slots: Object,
    filters: Object,
    auth: Object,
    isSchedulePublished: Boolean,
});

const days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
const activeDay = ref(props.filters?.hari || 'Senin');

const filterForm = ref({
    ruangan_id: props.filters?.ruangan_id || '',
    dosen_id: props.filters?.dosen_id || '',
    mata_kuliah_id: props.filters?.mata_kuliah_id || '',
});

// Watch active day and trigger page update
watch(activeDay, () => {
    applyFilters();
});

function applyFilters() {
    router.get(
        route('welcome'),
        {
            hari: activeDay.value,
            ruangan_id: filterForm.value.ruangan_id,
            dosen_id: filterForm.value.dosen_id,
            mata_kuliah_id: filterForm.value.mata_kuliah_id,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
}

function resetFilters() {
    filterForm.value = {
        ruangan_id: '',
        dosen_id: '',
        mata_kuliah_id: '',
    };
    dosenQuery.value = '';
    matkulQuery.value = '';
    applyFilters();
}

// Generate the 2D grid structure reactively
const parsedGrid = computed(() => {
    const grid = {};
    const covered = {};
    const slotKeys = Object.keys(props.slots || {}).map(Number).sort((a, b) => a - b);

    slotKeys.forEach(slotNum => {
        grid[slotNum] = {};
        covered[slotNum] = {};
        (props.rooms || []).forEach(room => {
            grid[slotNum][room.id] = null;
            covered[slotNum][room.id] = false;
        });
    });

    (props.schedules || []).forEach(sched => {
        const start = sched.slot_mulai;
        const end = sched.slot_selesai;
        const roomId = sched.ruangan_id;

        if (grid[start] && grid[start][roomId] !== undefined) {
            grid[start][roomId] = sched;
            for (let s = start + 1; s <= end; s++) {
                if (covered[s] && covered[s][roomId] !== undefined) {
                    covered[s][roomId] = true;
                }
            }
        }
    });

    return { grid, covered, slotKeys };
});

const sortedRooms = computed(() => {
    return [...(props.rooms || [])].sort((a, b) => a.nama_ruangan.localeCompare(b.nama_ruangan));
});

const hasSchedules = computed(() => (props.schedules || []).length > 0);

// --- Searchable combobox: Dosen ---
const sortedLecturers = computed(() => [...(props.lecturers || [])].sort((a, b) => a.nama.localeCompare(b.nama)));
const initialDosen = sortedLecturers.value.find(d => String(d.id) === String(filterForm.value.dosen_id));
const dosenQuery = ref(initialDosen ? initialDosen.nama : '');
const dosenOpen = ref(false);
const filteredLecturers = computed(() => {
    if (!dosenQuery.value) return sortedLecturers.value;
    const q = dosenQuery.value.toLowerCase();
    return sortedLecturers.value.filter(d => d.nama.toLowerCase().includes(q));
});
function selectDosen(d) {
    filterForm.value.dosen_id = d ? d.id : '';
    dosenQuery.value = d ? d.nama : '';
    dosenOpen.value = false;
    applyFilters();
}

// --- Searchable combobox: Mata Kuliah ---
const sortedCourses = computed(() => [...(props.courses || [])].sort((a, b) => a.nama.localeCompare(b.nama)));
const initialCourse = sortedCourses.value.find(c => String(c.id) === String(filterForm.value.mata_kuliah_id));
const matkulQuery = ref(initialCourse ? initialCourse.nama : '');
const matkulOpen = ref(false);
const filteredCourses = computed(() => {
    if (!matkulQuery.value) return sortedCourses.value;
    const q = matkulQuery.value.toLowerCase();
    return sortedCourses.value.filter(c => c.nama.toLowerCase().includes(q));
});
function selectCourse(c) {
    filterForm.value.mata_kuliah_id = c ? c.id : '';
    matkulQuery.value = c ? c.nama : '';
    matkulOpen.value = false;
    applyFilters();
}

// Card color coding by course semester (from mata_kuliah.semester)
const SEMESTER_STYLES = {
    1: 'bg-amber-50 border-amber-200 text-amber-900 hover:border-amber-400',
    2: 'bg-blue-50 border-blue-200 text-blue-900 hover:border-blue-400',
    3: 'bg-purple-50 border-purple-200 text-purple-900 hover:border-purple-400',
    4: 'bg-red-50 border-red-200 text-red-900 hover:border-red-400',
    5: 'bg-fuchsia-50 border-fuchsia-200 text-fuchsia-900 hover:border-fuchsia-400',
    6: 'bg-emerald-50 border-emerald-200 text-emerald-900 hover:border-emerald-400',
    7: 'bg-cyan-50 border-cyan-200 text-cyan-900 hover:border-cyan-400',
    8: 'bg-orange-50 border-orange-200 text-orange-900 hover:border-orange-400',
};
function cardClassesForSemester(semester) {
    return SEMESTER_STYLES[Number(semester)] || 'bg-slate-50 border-slate-200 text-slate-900 hover:border-slate-400';
}
</script>

<template>
    <Head title="Jadwal Perkuliahan TIF" />

    <div class="min-h-screen bg-slate-50 text-slate-800 font-sans">
        <Navbar :auth="auth" />

        <!-- Main Workspace -->
        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <!-- Header Section -->
            <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="text-2xl xl:text-3xl font-extrabold text-slate-900 tracking-tight">
                        Papan Jadwal Perkuliahan
                    </h2>
                    <p class="text-sm text-slate-500 mt-1" v-if="activeSemester">
                        Semester Aktif: <span class="text-blue-900 font-semibold">{{ activeSemester.nama }} {{ activeSemester.tahun_ajaran }}</span>
                    </p>
                    <p class="text-sm mt-1 font-semibold" style="color: #b91c1c;" v-else>
                        Tidak ada semester aktif.
                    </p>
                </div>

                <div v-if="isSchedulePublished && activeSemester" class="flex items-center gap-3">
                    <a
                        :href="route('export.pdf.full')"
                        target="_blank"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold bg-red-700 text-white hover:bg-red-800 transition-all shadow-sm"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Ekspor PDF Keseluruhan
                    </a>
                </div>
            </div>

            <!-- Banner Jadwal Belum Dipublikasikan (Draft Mode) -->
            <div v-if="!isSchedulePublished" class="bg-white border border-slate-200 rounded-2xl p-10 text-center shadow-sm my-6">
                <div class="inline-flex items-center justify-center h-12 w-12 rounded-full bg-amber-50 border border-amber-200 mb-4">
                    <svg class="h-6 w-6" style="color: #b45309;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-900">Jadwal Kuliah Belum Dipublikasikan</h3>
                <p class="text-sm text-slate-500 mt-2 max-w-xl mx-auto leading-relaxed">
                    Jadwal perkuliahan untuk semester aktif sedang dalam tahap penyusunan oleh Admin. Silakan cek kembali halaman ini setelah jadwal final dipublikasikan.
                </p>
            </div>

            <template v-else>
                <!-- Filters card -->
                <div class="bg-white border border-slate-200 rounded-2xl p-6 mb-8 shadow-sm">
                    <h3 class="text-sm font-bold text-slate-600 uppercase tracking-wider mb-4 flex items-center gap-2">
                        <svg class="h-4 w-4 text-blue-800" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        Filter Pencarian
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:items-end">

                        <!-- Course filter (searchable) -->
                        <div class="relative sm:col-span-2 lg:col-span-1">
                            <label class="block text-xs font-semibold text-slate-500 mb-1.5">Mata Kuliah</label>
                            <input
                                type="text"
                                v-model="matkulQuery"
                                @focus="matkulOpen = true"
                                @input="matkulOpen = true; filterForm.mata_kuliah_id = ''"
                                @blur="matkulOpen = false"
                                placeholder="Cari atau pilih mata kuliah..."
                                class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 pr-8 text-sm text-slate-800 focus:outline-none focus:border-blue-700 focus:ring-1 focus:ring-blue-700 transition-all"
                            />
                            <button
                                v-if="matkulQuery"
                                type="button"
                                @mousedown.prevent="selectCourse(null)"
                                class="absolute right-2.5 top-[34px] text-slate-400 hover:text-slate-600 text-sm leading-none"
                                aria-label="Bersihkan filter mata kuliah"
                            >✕</button>
                            <ul
                                v-if="matkulOpen"
                                class="absolute z-20 mt-1 w-full bg-white border border-slate-200 rounded-xl shadow-lg max-h-56 overflow-y-auto"
                            >
                                <li
                                    v-for="c in filteredCourses"
                                    :key="c.id"
                                    @mousedown.prevent="selectCourse(c)"
                                    class="px-3.5 py-2 text-sm cursor-pointer hover:bg-blue-50"
                                    :class="filterForm.mata_kuliah_id == c.id ? 'bg-blue-50 text-blue-900 font-semibold' : 'text-slate-700'"
                                >
                                    {{ c.nama }}
                                </li>
                                <li v-if="filteredCourses.length === 0" class="px-3.5 py-2 text-sm text-slate-400">
                                    Tidak ditemukan
                                </li>
                            </ul>
                        </div>

                        <!-- Room filter -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1.5">Ruangan</label>
                            <select
                                v-model="filterForm.ruangan_id"
                                @change="applyFilters"
                                class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-sm text-slate-800 focus:outline-none focus:border-blue-700 focus:ring-1 focus:ring-blue-700 transition-all"
                            >
                                <option value="">Semua Ruangan</option>
                                <option v-for="r in sortedRooms" :key="r.id" :value="r.id">
                                    {{ r.nama_ruangan }}
                                </option>
                            </select>
                        </div>

                        <!-- Lecturer filter (searchable) -->
                        <div class="relative">
                            <label class="block text-xs font-semibold text-slate-500 mb-1.5">Dosen</label>
                            <input
                                type="text"
                                v-model="dosenQuery"
                                @focus="dosenOpen = true"
                                @input="dosenOpen = true; filterForm.dosen_id = ''"
                                @blur="dosenOpen = false"
                                placeholder="Cari atau pilih dosen..."
                                class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 pr-8 text-sm text-slate-800 focus:outline-none focus:border-blue-700 focus:ring-1 focus:ring-blue-700 transition-all"
                            />
                            <button
                                v-if="dosenQuery"
                                type="button"
                                @mousedown.prevent="selectDosen(null)"
                                class="absolute right-2.5 top-[34px] text-slate-400 hover:text-slate-600 text-sm leading-none"
                                aria-label="Bersihkan filter dosen"
                            >✕</button>
                            <ul
                                v-if="dosenOpen"
                                class="absolute z-20 mt-1 w-full bg-white border border-slate-200 rounded-xl shadow-lg max-h-56 overflow-y-auto"
                            >
                                <li
                                    v-for="d in filteredLecturers"
                                    :key="d.id"
                                    @mousedown.prevent="selectDosen(d)"
                                    class="px-3.5 py-2 text-sm cursor-pointer hover:bg-blue-50"
                                    :class="filterForm.dosen_id == d.id ? 'bg-blue-50 text-blue-900 font-semibold' : 'text-slate-700'"
                                >
                                    {{ d.nama }}
                                </li>
                                <li v-if="filteredLecturers.length === 0" class="px-3.5 py-2 text-sm text-slate-400">
                                    Tidak ditemukan
                                </li>
                            </ul>
                        </div>

                        <div class="sm:col-span-2 lg:col-span-1">
                            <button
                                @click="resetFilters"
                                class="w-full px-4 py-2 rounded-xl text-sm font-semibold bg-slate-100 text-slate-600 hover:bg-slate-200 transition-all border border-slate-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-700 focus-visible:ring-offset-2"
                            >
                                Atur Ulang Filter
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Day Selector Tabs -->
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 mb-6">
                    <div class="flex overflow-x-auto scrollbar-thin">
                        <button
                            v-for="d in days"
                            :key="d"
                            @click="activeDay = d"
                            class="px-6 py-3.5 text-sm font-bold tracking-wide transition-colors duration-200 border-b-2 whitespace-nowrap focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-700 focus-visible:ring-offset-2 focus-visible:ring-offset-white rounded-t-md"
                            :class="activeDay === d ? 'border-blue-800 text-blue-900 bg-blue-50/70' : 'border-transparent text-slate-500 hover:text-slate-800'"
                        >
                            {{ d }}
                        </button>
                    </div>
                </div>

                <!-- Grid Table Schedule Board -->
                <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[1000px] border-collapse table-layout-fixed">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200">
                                    <th class="w-[120px] px-4 py-4 text-xs font-bold text-slate-500 tracking-wider text-center">Waktu</th>
                                    <th
                                        v-for="r in sortedRooms"
                                        :key="r.id"
                                        class="px-4 py-4 text-xs font-bold text-slate-600 tracking-wider text-center border-l border-slate-200"
                                    >
                                        {{ r.nama_ruangan }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="!hasSchedules">
                                    <td :colspan="sortedRooms.length + 1" class="py-12 text-center">
                                        <p class="text-sm font-semibold text-slate-500">Tidak ada jadwal untuk filter yang dipilih.</p>
                                        <p class="text-xs text-slate-400 mt-1">Coba ubah hari atau atur ulang filter di atas.</p>
                                    </td>
                                </tr>
                                <template v-else v-for="slotNum in parsedGrid.slotKeys" :key="slotNum">
                                <tr class="border-b border-slate-100 hover:bg-slate-50/60 transition-colors">
                                    <!-- Time column -->
                                    <td class="px-4 py-4 text-xs font-bold text-blue-900 text-center bg-white">
                                        {{ slots[slotNum] }}
                                    </td>

                                    <!-- Break slot -->
                                    <td
                                        v-if="slotNum === 7"
                                        :colspan="sortedRooms.length"
                                        class="py-3 text-center text-xs font-bold uppercase tracking-widest border-l border-slate-200"
                                        style="background-color: #fee2e2; color: #b91c1c;"
                                    >
                                        Istirahat
                                    </td>

                                    <!-- Regular rooms mapping -->
                                    <template v-else>
                                        <template v-for="room in sortedRooms" :key="room.id">
                                            <!-- Skip if covered by rowspan -->
                                            <td
                                                v-if="parsedGrid.covered[slotNum][room.id]"
                                                class="hidden"
                                            ></td>

                                            <!-- Render schedule card if exists -->
                                            <td
                                                v-else-if="parsedGrid.grid[slotNum][room.id]"
                                                :rowspan="parsedGrid.grid[slotNum][room.id].slot_selesai - parsedGrid.grid[slotNum][room.id].slot_mulai + 1"
                                                class="p-2 border-l border-slate-200 align-top h-full"
                                            >
                                                <div
                                                    class="rounded-xl p-3 h-full border text-left shadow-sm transition-transform hover:-translate-y-0.5 duration-200"
                                                    :class="cardClassesForSemester(parsedGrid.grid[slotNum][room.id].mata_kuliah?.semester)"
                                                >
                                                    <div class="font-extrabold text-xs leading-snug break-words">
                                                        {{ parsedGrid.grid[slotNum][room.id].mata_kuliah?.nama }} {{ parsedGrid.grid[slotNum][room.id].kelas?.nama_kelas }}
                                                    </div>
                                                    <div class="mt-2 flex flex-col gap-1 text-[10px] opacity-80">
                                                        <span>Dosen: <strong class="font-semibold">{{ parsedGrid.grid[slotNum][room.id].dosen?.nama }}</strong></span>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Render empty cell -->
                                            <td
                                                v-else
                                                class="p-2 border-l border-slate-200"
                                            ></td>
                                        </template>
                                    </template>
                                </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>
        </main>
    </div>
</template>

<style scoped>
.scrollbar-thin::-webkit-scrollbar {
    height: 4px;
}
.scrollbar-thin::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 4px;
}
</style>