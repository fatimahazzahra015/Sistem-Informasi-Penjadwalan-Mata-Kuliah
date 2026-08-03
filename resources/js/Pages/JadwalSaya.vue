<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import Modal from '@/Components/Modal.vue';
import Navbar from '@/Components/Navbar.vue';

const props = defineProps({
    auth: Object,
    allSchedules: Array,
    activeSemester: Object,
    slots: Object,
});

const days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
const selectedIds = ref([]);
const searchQuery = ref('');

onMounted(() => {
    const saved = localStorage.getItem('utm_krs_schedules');
    if (saved) {
        try {
            selectedIds.value = JSON.parse(saved);
        } catch (e) {
            selectedIds.value = [];
        }
    }
});

// --- Modal state ---
const modal = ref({
    show: false,
    type: 'alert',
    title: '',
    message: '',
    confirmLabel: 'Mengerti',
    onConfirm: null,
});

function openAlert(title, message) {
    modal.value = {
        show: true,
        type: 'alert',
        title,
        message,
        confirmLabel: 'Mengerti',
        onConfirm: null,
    };
}

function openConfirm(title, message, onConfirm, confirmLabel = 'Ganti Jadwal') {
    modal.value = {
        show: true,
        type: 'confirm',
        title,
        message,
        confirmLabel,
        onConfirm,
    };
}

function closeModal() {
    modal.value.show = false;
}

function confirmModal() {
    if (modal.value.type === 'confirm' && typeof modal.value.onConfirm === 'function') {
        modal.value.onConfirm();
    }
    closeModal();
}

function formatJam(schedule) {
    const startLabel = props.slots[schedule.slot_mulai] || '';
    const endLabel = props.slots[schedule.slot_selesai] || '';
    const startTime = startLabel.split(' - ')[0] || startLabel;
    const endTime = endLabel.split(' - ')[1] || endLabel;
    return `${startTime} - ${endTime}`;
}

function isScheduleConflict(newSched, existingSched) {
    if (newSched.hari !== existingSched.hari) return false;

    const newStart = parseInt(newSched.slot_mulai);
    const newEnd = parseInt(newSched.slot_selesai);
    const existStart = parseInt(existingSched.slot_mulai);
    const existEnd = parseInt(existingSched.slot_selesai);

    return Math.max(newStart, existStart) <= Math.min(newEnd, existEnd);
}

function commitSelection(id) {
    selectedIds.value.push(id);
    localStorage.setItem('utm_krs_schedules', JSON.stringify(selectedIds.value));
}

function toggleSelect(id) {
    const targetSchedule = props.allSchedules.find(s => s.id === id);
    if (!targetSchedule) return;

    if (selectedIds.value.includes(id)) {
        selectedIds.value = selectedIds.value.filter(item => item !== id);
        localStorage.setItem('utm_krs_schedules', JSON.stringify(selectedIds.value));
        return;
    }

    const isSameCourseAlreadySelected = selectedSchedules.value.some(
        s => s.mata_kuliah_id === targetSchedule.mata_kuliah_id
    );

    if (isSameCourseAlreadySelected) {
        openAlert(
            'Mata Kuliah Sudah Dipilih',
            `Anda sudah mengambil mata kuliah "${targetSchedule.mata_kuliah.nama}" pada kelas lain. Batalkan pilihan kelas sebelumnya jika ingin memindah kelas.`
        );
        return;
    }

    const conflictingSchedule = selectedSchedules.value.find(s => isScheduleConflict(targetSchedule, s));

    if (conflictingSchedule) {
        openConfirm(
            'Jadwal Bentrok!',
            `"${targetSchedule.mata_kuliah.nama} (Kelas ${targetSchedule.kelas.nama_kelas})" bentrok dengan "${conflictingSchedule.mata_kuliah.nama} (Kelas ${conflictingSchedule.kelas.nama_kelas})" pada hari ${targetSchedule.hari}.\n\nApakah Anda ingin mengganti jadwal tersebut?`,
            () => {
                selectedIds.value = selectedIds.value.filter(item => item !== conflictingSchedule.id);
                commitSelection(id);
            }
        );
        return;
    }

    commitSelection(id);
}

function removeSchedule(id, event) {
    event.stopPropagation();
    selectedIds.value = selectedIds.value.filter(item => item !== id);
    localStorage.setItem('utm_krs_schedules', JSON.stringify(selectedIds.value));
}

function clearSelection() {
    selectedIds.value = [];
    localStorage.removeItem('utm_krs_schedules');
}

const exportUrl = computed(() => {
    if (selectedIds.value.length === 0) return '#';
    return route('export.pdf.student') + '?ids=' + selectedIds.value.join(',');
});

const filteredAvailableSchedules = computed(() => {
    if (!searchQuery.value.trim()) return props.allSchedules;
    const query = searchQuery.value.toLowerCase();
    return props.allSchedules.filter(s =>
        s.mata_kuliah.nama.toLowerCase().includes(query) ||
        s.dosen.nama.toLowerCase().includes(query) ||
        s.hari.toLowerCase().includes(query) ||
        s.ruangan.nama_ruangan.toLowerCase().includes(query)
    );
});

const selectedSchedules = computed(() => {
    return props.allSchedules.filter(s => selectedIds.value.includes(s.id));
});

const timetableGrid = computed(() => {
    const grid = {};
    const covered = {};
    const slotKeys = Object.keys(props.slots).map(Number).sort((a, b) => a - b);

    slotKeys.forEach(slotNum => {
        grid[slotNum] = {};
        covered[slotNum] = {};
        days.forEach(day => {
            grid[slotNum][day] = null;
            covered[slotNum][day] = false;
        });
    });

    selectedSchedules.value.forEach(sched => {
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

const SEMESTER_STYLES = {
    1: 'bg-amber-50/80 border-amber-200 text-amber-900 hover:border-amber-400 shadow-sm',
    2: 'bg-blue-50/80 border-blue-200 text-blue-900 hover:border-blue-400 shadow-sm',
    3: 'bg-purple-50/80 border-purple-200 text-purple-900 hover:border-purple-400 shadow-sm',
    4: 'bg-rose-50/80 border-rose-200 text-rose-900 hover:border-rose-400 shadow-sm',
    5: 'bg-fuchsia-50/80 border-fuchsia-200 text-fuchsia-900 hover:border-fuchsia-400 shadow-sm',
    6: 'bg-emerald-50/80 border-emerald-200 text-emerald-900 hover:border-emerald-400 shadow-sm',
    7: 'bg-cyan-50/80 border-cyan-200 text-cyan-900 hover:border-cyan-400 shadow-sm',
    8: 'bg-orange-50/80 border-orange-200 text-orange-900 hover:border-orange-400 shadow-sm',
};
function cardClassesForSemester(semester) {
    return SEMESTER_STYLES[Number(semester)] || 'bg-slate-50 border-slate-200 text-slate-900 hover:border-slate-400 shadow-sm';
}
</script>

<template>
    <Head title="Jadwal Saya" />

    <div class="min-h-screen bg-slate-100/60 text-slate-800 font-sans antialiased">
        <Navbar 
            :auth="auth" 
            rightButtonText="Beranda" 
            rightButtonRoute="welcome" 
        />
        <!-- Main Workspace -->
        <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <h2 class="text-2xl xl:text-3xl font-extrabold text-slate-900 tracking-tight">
                        Jadwal Saya
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1" v-if="activeSemester">
                        Semester Aktif: <span class="text-blue-900 font-semibold">{{ activeSemester.nama }} {{ activeSemester.tahun_ajaran }}</span>
                    </p>
                    <p class="text-xs sm:text-sm mt-1 font-semibold text-rose-600" v-else>
                        Tidak ada semester aktif.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                <!-- Left Column: Course Selection Panel (Cols 4) -->
                <div class="lg:col-span-4 flex flex-col gap-6">
                    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 sm:p-6 shadow-xs">
                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 flex items-center gap-2">
                            <svg class="h-4 w-4 text-blue-900" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Pilih Mata Kuliah &amp; Kelas
                        </h3>
                        <p class="text-xs text-slate-400 mb-4">Pilih mata kuliah yang ingin Anda masukkan ke dalam jadwal kuliah Anda</p>

                        <!-- Search -->
                        <div class="relative mb-4">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </span>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Cari matkul, dosen, hari..."
                                class="w-full bg-slate-50/50 border border-slate-200 rounded-xl pl-9 pr-3.5 py-2.5 text-xs sm:text-sm text-slate-800 focus:outline-none focus:border-blue-700 focus:bg-white focus:ring-1 focus:ring-blue-700 transition-all"
                            />
                        </div>

                        <!-- Available classes list -->
                        <div class="max-h-[480px] sm:max-h-[540px] overflow-y-auto pr-1 flex flex-col gap-2.5 scrollbar-thin">
                            <div
                                v-for="s in filteredAvailableSchedules"
                                :key="s.id"
                                @click="toggleSelect(s.id)"
                                class="p-3.5 rounded-xl border text-left cursor-pointer transition-all duration-200 relative group"
                                :class="
                                    selectedIds.includes(s.id)
                                        ? 'bg-blue-50/90 border-blue-400 text-blue-900 shadow-xs ring-1 ring-blue-400/30'
                                        : 'bg-slate-50/60 border-slate-200 text-slate-700 hover:border-slate-300 hover:bg-slate-50'
                                "
                            >
                                <div class="flex items-start justify-between gap-2 mb-1.5">
                                    <div class="font-bold text-xs sm:text-sm leading-snug break-words">
                                        {{ s.mata_kuliah.nama }} <span class="text-blue-900 font-extrabold">({{ s.kelas.nama_kelas }})</span>
                                    </div>
                                    <div v-if="selectedIds.includes(s.id)" class="shrink-0 w-5 h-5 rounded-full bg-blue-900 text-white flex items-center justify-center text-[10px]">
                                        ✓
                                    </div>
                                </div>
                                <div class="flex flex-col gap-1 text-[10px] text-slate-500">
                                    <span>Hari: <strong class="text-slate-700 font-semibold">{{ s.hari }}</strong></span>
                                    <span>Jam: <strong class="text-slate-700 font-semibold">{{ formatJam(s) }}</strong></span>
                                    <span>Ruang: <strong class="text-slate-700 font-semibold">{{ s.ruangan.nama_ruangan }}</strong></span>
                                    <span>Dosen: <strong class="text-slate-700 font-semibold">{{ s.dosen.nama }}</strong></span>
                                </div>
                            </div>

                            <div v-if="filteredAvailableSchedules.length === 0" class="text-center py-10 text-slate-400 text-xs">
                                Mata kuliah tidak ditemukan.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Personal Timetable Grid (Cols 8) -->
                <div class="lg:col-span-8 flex flex-col gap-6">
                    <!-- Actions Bar -->
                    <div class="bg-white border border-slate-200/80 rounded-2xl p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shadow-xs">
                        <div>
                            <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Ringkasan Pilihan</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Total terpilih: <span class="text-blue-900 font-bold">{{ selectedIds.length }} Kelas</span></p>
                        </div>

                        <div class="flex items-center gap-2.5">
                            <button
                                v-if="selectedIds.length > 0"
                                @click="clearSelection"
                                class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-slate-100 text-slate-600 hover:bg-slate-200 transition-all border border-slate-200"
                            >
                                Bersihkan Pilihan
                            </button>

                            <a
                                v-if="selectedIds.length > 0"
                                :href="exportUrl"
                                target="_blank"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs sm:text-sm font-bold bg-rose-600 text-white hover:bg-rose-700 transition-all shadow-sm"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Ekspor PDF
                            </a>
                        </div>
                    </div>

                    <!-- Timetable View -->
                    <div class="bg-white border border-slate-200/80 rounded-2xl overflow-hidden shadow-xs">
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[780px] border-collapse table-layout-fixed">
                                <thead>
                                    <tr class="bg-slate-50/80 border-b border-slate-200">
                                        <th class="w-[100px] sm:w-[110px] px-3 py-3.5 text-xs font-bold text-slate-500 tracking-wider text-center sticky left-0 bg-slate-50 z-10">Waktu</th>
                                        <th
                                            v-for="d in days"
                                            :key="d"
                                            class="px-3 py-3.5 text-xs font-bold text-slate-600 tracking-wider text-center border-l border-slate-200"
                                        >
                                            {{ d }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="selectedIds.length === 0">
                                        <td :colspan="days.length + 1" class="py-16 text-center">
                                            <div class="max-w-xs mx-auto flex flex-col items-center">
                                                <div class="w-12 h-12 rounded-full bg-blue-50 text-blue-900 flex items-center justify-center mb-3">
                                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                                <p class="text-sm font-bold text-slate-700">Belum ada jadwal dipilih</p>
                                                <p class="text-xs text-slate-400 mt-1">Pilih mata kuliah di panel sebelah kiri untuk menyusun tata letak jadwal Anda.</p>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr
                                        v-else
                                        v-for="slotNum in timetableGrid.slotKeys"
                                        :key="slotNum"
                                        class="border-b border-slate-100 hover:bg-slate-50/40 transition-colors"
                                    >
                                        <!-- Time Column -->
                                        <td class="px-2 py-3 text-[11px] sm:text-xs font-bold text-blue-900 text-center bg-white sticky left-0 z-10 border-r border-slate-100">
                                            {{ slots[slotNum] }}
                                        </td>

                                        <!-- If slot is 7 (ISTIRAHAT) -->
                                        <td
                                            v-if="slotNum === 7"
                                            :colspan="days.length"
                                            class="py-2.5 text-center text-xs font-bold uppercase tracking-widest border-l border-slate-200 bg-rose-50/60 text-rose-700"
                                        >
                                            — ISTIRAHAT —
                                        </td>

                                        <!-- Regular Days -->
                                        <template v-else>
                                            <template v-for="day in days" :key="day">
                                                <!-- Skip if covered by rowspan -->
                                                <td
                                                    v-if="timetableGrid.covered[slotNum][day]"
                                                    class="hidden"
                                                ></td>

                                                <!-- Render schedule card -->
                                                <td
                                                    v-else-if="timetableGrid.grid[slotNum][day]"
                                                    :rowspan="timetableGrid.grid[slotNum][day].slot_selesai - timetableGrid.grid[slotNum][day].slot_mulai + 1"
                                                    class="p-1.5 border-l border-slate-200 align-top h-full"
                                                >
                                                    <div
                                                        class="group relative rounded-xl p-3 h-full border text-left transition-all duration-200 flex flex-col justify-between"
                                                        :class="cardClassesForSemester(timetableGrid.grid[slotNum][day].mata_kuliah.semester)"
                                                    >
                                                        <!-- Tombol X Hapus Jadwal -->
                                                        <button
                                                            @click="removeSchedule(timetableGrid.grid[slotNum][day].id, $event)"
                                                            class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity w-5 h-5 rounded-full bg-slate-200/80 hover:bg-rose-600 hover:text-white text-slate-700 flex items-center justify-center text-xs font-bold shadow-xs"
                                                            title="Hapus jadwal"
                                                        >
                                                            &times;
                                                        </button>

                                                        <div>
                                                            <div class="font-extrabold text-xs leading-snug text-slate-900 break-words pr-5">
                                                                {{ timetableGrid.grid[slotNum][day].mata_kuliah.nama }} <span class="font-bold text-blue-900">({{ timetableGrid.grid[slotNum][day].kelas.nama_kelas }})</span>
                                                            </div>
                                                        </div>

                                                        <div class="mt-2.5 flex flex-col gap-0.5 text-[11px] text-slate-600">
                                                            <div class="truncate">
                                                                Ruang: <strong class="text-slate-800 font-semibold">{{ timetableGrid.grid[slotNum][day].ruangan.nama_ruangan }}</strong>
                                                            </div>
                                                            <div class="truncate">
                                                                Dosen: <strong class="text-slate-800 font-semibold">{{ timetableGrid.grid[slotNum][day].dosen.nama }}</strong>
                                                            </div>
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
                    </div>
                </div>
            </div>
        </main>

        <!-- Modal -->
        <Modal :show="modal.show" max-width="md" @close="closeModal">
            <div class="p-6">
                <h3 class="text-base font-extrabold text-slate-900 mb-2">{{ modal.title }}</h3>
                <p class="text-xs sm:text-sm text-slate-600 whitespace-pre-line leading-relaxed">{{ modal.message }}</p>

                <div class="mt-6 flex justify-end gap-2.5">
                    <button
                        v-if="modal.type === 'confirm'"
                        @click="closeModal"
                        class="px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold bg-slate-100 text-slate-600 hover:bg-slate-200 transition-all border border-slate-200"
                    >
                        Batal
                    </button>
                    <button
                        @click="confirmModal"
                        class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold bg-blue-900 text-white hover:bg-blue-800 transition-all shadow-sm"
                    >
                        {{ modal.confirmLabel }}
                    </button>
                </div>
            </div>
        </Modal>
    </div>
</template>

<style scoped>
.scrollbar-thin::-webkit-scrollbar {
    width: 4px;
    height: 4px;
}
.scrollbar-thin::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 4px;
}
</style>