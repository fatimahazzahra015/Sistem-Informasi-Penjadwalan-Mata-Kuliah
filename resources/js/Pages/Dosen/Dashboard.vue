<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    schedules: Array,
    dosen: Object,
    activeSemester: Object,
    slots: Object,
});

const days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
const viewMode = ref('timetable'); // timetable or list

// Calculate time grid: Columns = DAYS, Rows = TIME SLOTS
const timetableGrid = computed(() => {
    const grid = {};
    const covered = {};
    const slotKeys = Object.keys(props.slots).map(Number).sort((a, b) => a - b);

    // Initialize
    slotKeys.forEach(slotNum => {
        grid[slotNum] = {};
        covered[slotNum] = {};
        days.forEach(day => {
            grid[slotNum][day] = null;
            covered[slotNum][day] = false;
        });
    });

    // Populate
    props.schedules.forEach(sched => {
        const start = sched.slot_mulai;
        const end = sched.slot_selesai;
        const day = sched.hari;

        if (grid[start] && grid[start][day] !== undefined) {
            grid[start][day] = sched;
            for (let s = start + 1; s <= end; s++) {
                if (covered[s] && covered[s][day] !== undefined) {
                    covered[s][day] = true;
                }
            }
        }
    });

    return { grid, covered, slotKeys };
});

const sortedList = computed(() => {
    return [...props.schedules].sort((a, b) => {
        const dayWeights = { 'Senin': 1, 'Selasa': 2, 'Rabu': 3, 'Kamis': 4, 'Jumat': 5 };
        const dayA = dayWeights[a.hari] || 9;
        const dayB = dayWeights[b.hari] || 9;
        if (dayA !== dayB) return dayA - dayB;
        return a.slot_mulai - b.slot_mulai;
    });
});

// Card color coding by course semester (matching the public schedule board)
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
const SEMESTER_BADGE_STYLES = {
    1: 'bg-amber-50 border-amber-200 text-amber-900',
    2: 'bg-blue-50 border-blue-200 text-blue-900',
    3: 'bg-purple-50 border-purple-200 text-purple-900',
    4: 'bg-red-50 border-red-200 text-red-900',
    5: 'bg-fuchsia-50 border-fuchsia-200 text-fuchsia-900',
    6: 'bg-emerald-50 border-emerald-200 text-emerald-900',
    7: 'bg-cyan-50 border-cyan-200 text-cyan-900',
    8: 'bg-orange-50 border-orange-200 text-orange-900',
};
function cardClassesForSemester(semester) {
    return SEMESTER_STYLES[Number(semester)] || 'bg-slate-50 border-slate-200 text-slate-900 hover:border-slate-400';
}
function badgeClassesForSemester(semester) {
    return SEMESTER_BADGE_STYLES[Number(semester)] || 'bg-slate-50 border-slate-200 text-slate-700';
}
</script>

<template>
    <Head title="Dashboard Dosen" />

    <AuthenticatedLayout>
        <div class="py-8 bg-slate-50 min-h-screen">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
                
                <!-- Header Section -->
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                    <div>
                        <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">
                            Jadwal Mengajar Dosen
                        </h2>
                        <p class="text-sm text-slate-500 mt-1">
                            Selamat datang, <span class="text-blue-600 font-semibold">{{ dosen.nama }}</span>
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <!-- Toggle View Mode -->
                        <div class="bg-slate-100 p-1 rounded-xl flex border border-slate-200">
                            <button
                                @click="viewMode = 'timetable'"
                                class="px-4 py-2 rounded-lg text-xs font-bold transition-all whitespace-nowrap"
                                :class="viewMode === 'timetable' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                            >
                                Jadwal Grid
                            </button>
                            <button
                                @click="viewMode = 'list'"
                                class="px-4 py-2 rounded-lg text-xs font-bold transition-all whitespace-nowrap"
                                :class="viewMode === 'list' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                            >
                                Daftar Tabel
                            </button>
                        </div>

                        <!-- PDF Export -->
                        <a
                            v-if="schedules.length > 0"
                            :href="route('export.pdf.dosen', { mode: viewMode })"
                            target="_blank"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-rose-600 text-white hover:bg-rose-700 transition-all shadow-sm whitespace-nowrap"
                        >
                            <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Ekspor PDF
                        </a>
                    </div>
                </div>
                <!-- Semester Warning -->
                <div
                    v-if="!activeSemester"
                    class="bg-white border border-slate-200 rounded-2xl p-6 mb-8 shadow-sm flex items-start gap-3"
                >
                    <svg class="h-5 w-5 flex-shrink-0 mt-0.5" style="color: #b91c1c;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <p class="text-sm font-semibold" style="color: #b91c1c;">
                        Tidak ada semester aktif yang dikonfigurasi. Jadwal mengajar tidak dapat ditampilkan.
                    </p>
                </div>

                <div v-else class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
                    <div class="p-6 border-b border-slate-200">
                        <h3 class="text-sm font-bold text-slate-600 uppercase tracking-wider">
                            Semester Aktif: <span class="text-blue-900">{{ activeSemester.nama }} {{ activeSemester.tahun_ajaran }}</span>
                        </h3>
                    </div>

                    <!-- Timetable Grid Mode -->
                    <div v-if="viewMode === 'timetable'" class="overflow-x-auto">
                        <table class="w-full min-w-[700px] border-collapse table-layout-fixed">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200">
                                    <th class="w-[120px] px-4 py-4 text-xs font-bold text-slate-500 tracking-wider text-center">Waktu</th>
                                    <th
                                        v-for="d in days"
                                        :key="d"
                                        class="px-4 py-4 text-xs font-bold text-slate-600 tracking-wider text-center border-l border-slate-200"
                                    >
                                        {{ d }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="slotNum in timetableGrid.slotKeys"
                                    :key="slotNum"
                                    class="border-b border-slate-100 hover:bg-slate-50/60 transition-colors"
                                >
                                    <!-- Time Column -->
                                    <td class="px-4 py-4 text-xs font-bold text-blue-900 text-center bg-white">
                                        {{ slots[slotNum] }}
                                    </td>

                                    <!-- Break Slot -->
                                    <td
                                        v-if="slotNum === 7"
                                        :colspan="days.length"
                                        class="py-3 text-center text-xs font-bold uppercase tracking-widest border-l border-slate-200"
                                        style="background-color: #fee2e2; color: #b91c1c;"
                                    >
                                        ISTIRAHAT
                                    </td>

                                    <template v-else>
                                        <template v-for="day in days" :key="day">
                                            <!-- Covered cell -->
                                            <td
                                                v-if="timetableGrid.covered[slotNum][day]"
                                                class="hidden"
                                            ></td>

                                            <!-- Render card -->
                                            <td
                                                v-else-if="timetableGrid.grid[slotNum][day]"
                                                :rowspan="timetableGrid.grid[slotNum][day].slot_selesai - timetableGrid.grid[slotNum][day].slot_mulai + 1"
                                                class="p-2 border-l border-slate-200 align-top"
                                            >
                                                <div
                                                    class="rounded-xl p-2 sm:p-3 h-full border text-left shadow-sm transition-transform hover:-translate-y-0.5 duration-200"
                                                    :class="cardClassesForSemester(timetableGrid.grid[slotNum][day].mata_kuliah.semester)"
                                                >
                                                    <div class="font-extrabold text-xs leading-snug break-words">
                                                        {{ timetableGrid.grid[slotNum][day].mata_kuliah.nama }} {{ timetableGrid.grid[slotNum][day].kelas.nama_kelas }}
                                                    </div>
                                                    <div class="mt-2 flex flex-col gap-1 text-[10px] opacity-80">
                                                        <span>Ruang: <strong class="font-semibold">{{ timetableGrid.grid[slotNum][day].ruangan.nama_ruangan }}</strong></span>
                                                        <span>SKS: <strong class="font-semibold">{{ timetableGrid.grid[slotNum][day].mata_kuliah.sks }} SKS</strong></span>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Empty cell -->
                                            <td
                                                v-else
                                                class="p-2 border-l border-slate-200"
                                            ></td>
                                        </template>
                                    </template>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- List Mode -->
                    <div v-else class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200">
                                    <th class="w-[50px] px-6 py-4 text-xs font-bold text-slate-500 tracking-wider text-center">No</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 tracking-wider">Hari</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 tracking-wider">Waktu</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 tracking-wider">Mata Kuliah</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 tracking-wider text-center">Kelas</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 tracking-wider">Ruangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(sched, index) in sortedList"
                                    :key="sched.id"
                                    class="border-b border-slate-100 hover:bg-slate-50/60 transition-colors"
                                >
                                    <td class="px-6 py-4 text-sm text-slate-500 text-center">{{ index + 1 }}</td>
                                    <td class="px-6 py-4 text-sm font-semibold text-slate-800">{{ sched.hari }}</td>
                                    <td class="px-6 py-4 text-sm text-slate-600">
                                        {{ slots[sched.slot_mulai].split(' - ')[0] }} - {{ slots[sched.slot_selesai].split(' - ')[1] }} ({{ sched.mata_kuliah.sks }} SKS)
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <span
                                            class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg border font-bold"
                                            :class="badgeClassesForSemester(sched.mata_kuliah.semester)"
                                        >
                                            {{ sched.mata_kuliah.nama }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-800 text-center">
                                        <span class="bg-slate-100 px-2 py-1 rounded text-xs font-bold border border-slate-200">{{ sched.kelas.nama_kelas }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-700">
                                        {{ sched.ruangan.nama_ruangan }} ({{ sched.ruangan.tipe }})
                                    </td>
                                </tr>
                                <tr v-if="schedules.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center text-sm text-slate-500">
                                        Anda belum memiliki jadwal mengajar pada semester aktif.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>