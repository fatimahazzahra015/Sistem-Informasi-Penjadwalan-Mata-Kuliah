<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    schedules: Array,
    dosen: Object,
    activeSemester: Object,
    slots: Object,
    allocatedClasses: Array,
    preferensi: Array,
    isReleased: Boolean,
    isSchedulePublished: Boolean,
});

const days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
const viewMode = ref('timetable');

// Preference Form
const formPref = useForm({
    kelas_dibuka_id: '',
    hari: 'Senin',
    sesi: 1,
});

const submitPreferensi = () => {
    formPref.post(route('dosen.preferensi.store'), {
        preserveScroll: true,
    });
};

const deletePrefForm = useForm({});
const confirmingDeletePreferensi = ref(false);
const preferensiToDelete = ref(null);

const confirmDeletePreferensi = (preferensi) => {
    preferensiToDelete.value = preferensi;
    confirmingDeletePreferensi.value = true;
};

const deletePreferensi = () => {
    deletePrefForm.delete(route('dosen.preferensi.destroy', preferensiToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => closeDeleteModal(),
    });
};

const closeDeleteModal = () => {
    confirmingDeletePreferensi.value = false;
    preferensiToDelete.value = null;
    deletePrefForm.clearErrors();
};

// Calculate time grid: Columns = DAYS, Rows = TIME SLOTS
const timetableGrid = computed(() => {
    const grid = {};
    const covered = {};
    const slotKeys = Object.keys(props.slots || {}).map(Number).sort((a, b) => a - b);

    slotKeys.forEach(slotNum => {
        grid[slotNum] = {};
        covered[slotNum] = {};
        days.forEach(day => {
            grid[slotNum][day] = null;
            covered[slotNum][day] = false;
        });
    });

    (props.schedules || []).forEach(sched => {
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
    return [...(props.schedules || [])].sort((a, b) => {
        const dayWeights = { 'Senin': 1, 'Selasa': 2, 'Rabu': 3, 'Kamis': 4, 'Jumat': 5 };
        const dayA = dayWeights[a.hari] || 9;
        const dayB = dayWeights[b.hari] || 9;
        if (dayA !== dayB) return dayA - dayB;
        return a.slot_mulai - b.slot_mulai;
    });
});

// Card / badge color coding by course semester
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

                <!-- Flash Notification -->
                <div v-if="$page.props.flash?.success" class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl font-medium text-sm">
                    {{ $page.props.flash.success }}
                </div>

                <!-- Header Section -->
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                    <div>
                        <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">
                            Selamat Datang, {{ dosen?.nama }}
                        </h2>
                        <p class="text-sm text-slate-500 mt-1">
                            Semester Aktif:
                            <span class="font-semibold text-blue-600">
                                {{ activeSemester ? activeSemester.nama + ' ' + activeSemester.tahun_ajaran : 'Belum Ada' }}
                            </span>
                        </p>
                    </div>

                    <a
                        :href="route('export.pdf.dosen')"
                        target="_blank"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-rose-600 text-white hover:bg-rose-700 transition-all shadow-sm whitespace-nowrap"
                    >
                        <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Ekspor PDF
                    </a>
                </div>

                <!-- Jika Jadwal Belum Published (Masih Proses Penjadwalan) -->
                <template v-if="!isSchedulePublished">
                    <!-- Announcement Banner when NOT released -->
                    <div v-if="!isReleased" class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm flex items-start gap-3">
                        <svg class="h-5 w-5 flex-shrink-0 mt-0.5" style="color: #b45309;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                        </svg>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Alokasi Kelas Belum Dipublikasi</h3>
                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                Admin/Kaprodi sedang menyiapkan dan membagi slot alokasi kelas untuk semester ini. Penginputan preferensi jadwal mengajar akan terbuka otomatis di halaman ini setelah Admin merilis alokasi kelas ke Dosen.
                            </p>
                        </div>
                    </div>

                    <!-- Section Tahap 2: Input Preferensi Mengajar Dosen (Enabled ONLY when isReleased) -->
                    <div v-else class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
                        <div class="p-6 border-b border-slate-200">
                            <h3 class="text-sm font-bold text-slate-600 uppercase tracking-wider">
                                Input Preferensi Mengajar
                            </h3>
                            <p class="text-xs text-slate-500 mt-2 normal-case tracking-normal">
                                Pilih kelas, hari, dan sesi awal yang diharapkan. Sesi yang paling awal disubmit menjadi prioritas utama alokasi sistem.
                            </p>
                        </div>

                        <div class="p-6">

                        <!-- Form Input Preferensi -->
                        <form @submit.prevent="submitPreferensi" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end mb-6 bg-slate-50 p-4 rounded-xl border border-slate-200">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Kelas Mengajar</label>
                                <select
                                    v-model="formPref.kelas_dibuka_id"
                                    required
                                    class="w-full rounded-lg border-slate-300 text-sm focus:ring-blue-500"
                                >
                                    <option value="" disabled>-- Pilih Kelas --</option>
                                    <option v-for="c in allocatedClasses" :key="c.id" :value="c.id">
                                        [Sem {{ c.mata_kuliah?.semester }}] {{ c.mata_kuliah?.nama }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Hari</label>
                                <select
                                    v-model="formPref.hari"
                                    required
                                    class="w-full rounded-lg border-slate-300 text-sm focus:ring-blue-500"
                                >
                                    <option v-for="h in days" :key="h" :value="h">{{ h }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Sesi Jam Awal (Prioritas)</label>
                                <select
                                    v-model="formPref.sesi"
                                    required
                                    class="w-full rounded-lg border-slate-300 text-sm focus:ring-blue-500"
                                >
                                    <option v-for="(timeStr, slotNum) in slots" :key="slotNum" :value="Number(slotNum)">
                                        Sesi {{ slotNum }} ({{ timeStr }})
                                    </option>
                                </select>
                            </div>

                            <div>
                                <button
                                    type="submit"
                                    :disabled="formPref.processing"
                                    class="w-full py-2.5 px-4 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold text-sm shadow-sm transition disabled:opacity-50"
                                >
                                    Submit Preferensi
                                </button>
                            </div>
                        </form>

                        <!-- Table Daftar Preferensi Terkirim -->
                        <div class="overflow-x-auto rounded-xl border border-slate-200">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-200">
                                    <tr>
                                        <th class="py-3 px-4">Kelas & Matkul</th>
                                        <th class="py-3 px-4">Hari</th>
                                        <th class="py-3 px-4">Sesi Awal</th>
                                        <th class="py-3 px-4">Waktu Submit</th>
                                        <th class="py-3 px-4 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="p in preferensi" :key="p.id" class="hover:bg-slate-50/60">
                                        <td class="py-3 px-4 font-medium text-slate-900">
                                            [Sem {{ p.kelas_dibuka?.mata_kuliah?.semester }}] {{ p.kelas_dibuka?.mata_kuliah?.nama }}
                                        </td>
                                        <td class="py-3 px-4 font-semibold text-blue-600">
                                            {{ p.hari }}
                                        </td>
                                        <td class="py-3 px-4 font-semibold text-purple-600">
                                            Sesi {{ p.sesi }} ({{ slots ? slots[p.sesi] : '' }})
                                        </td>
                                        <td class="py-3 px-4 text-slate-500 font-mono text-[11px]">
                                            {{ new Date(p.created_at).toLocaleString('id-ID') }}
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <button
                                                @click="confirmDeletePreferensi(p)"
                                                class="px-2.5 py-1 text-xs font-semibold text-rose-600 hover:bg-rose-50 rounded transition"
                                            >
                                                Batalkan
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="!preferensi || preferensi.length === 0">
                                        <td colspan="5" class="py-6 text-center text-slate-400">
                                            Belum ada preferensi jadwal yang disubmit.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        </div>
                    </div>
                </template>

                <!-- Semester Warning -->
                <div
                    v-if="!activeSemester"
                    class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm flex items-start gap-3"
                >
                    <svg class="h-5 w-5 flex-shrink-0 mt-0.5" style="color: #b91c1c;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <p class="text-sm font-semibold" style="color: #b91c1c;">
                        Tidak ada semester aktif yang dikonfigurasi. Jadwal mengajar tidak dapat ditampilkan.
                    </p>
                </div>

                <!-- Section Jadwal Akhir Dosen (Hasil Auto-Generate) -->
                <div v-else class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between p-6 border-b border-slate-200">
                        <h3 class="text-sm font-bold text-slate-600 uppercase tracking-wider">
                            Jadwal Mengajar Terjadwal
                        </h3>

                        <div class="bg-slate-100 p-1 rounded-xl flex border border-slate-200 self-start sm:self-auto">
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
                    </div>

                    <!-- Timetable Grid Mode -->
                    <div v-if="viewMode === 'timetable'" class="overflow-x-auto">
                        <table class="w-full min-w-[700px] border-collapse">
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
                                        Istirahat
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
                                                    :class="cardClassesForSemester(timetableGrid.grid[slotNum][day].mata_kuliah?.semester)"
                                                >
                                                    <div class="flex items-center justify-between gap-1 mb-1">
                                                        <span class="font-extrabold text-xs leading-snug break-words">
                                                            {{ timetableGrid.grid[slotNum][day].mata_kuliah?.nama }} {{ timetableGrid.grid[slotNum][day].kelas?.nama_kelas }}
                                                        </span>
                                                    </div>
                                                    <div class="mt-2 flex flex-col gap-1 text-[10px] opacity-80">
                                                        <span>Ruang: <strong class="font-semibold">{{ timetableGrid.grid[slotNum][day].ruangan?.nama_ruangan }}</strong></span>
                                                        <span>Jam: <strong class="font-semibold">{{ slots[timetableGrid.grid[slotNum][day].slot_mulai]?.split(' - ')[0] }} - {{ slots[timetableGrid.grid[slotNum][day].slot_selesai]?.split(' - ')[1] }}</strong></span>
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
                                        {{ slots[sched.slot_mulai]?.split(' - ')[0] }} - {{ slots[sched.slot_selesai]?.split(' - ')[1] }}
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <span
                                            class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg border font-bold"
                                            :class="badgeClassesForSemester(sched.mata_kuliah?.semester)"
                                        >
                                            {{ sched.mata_kuliah?.nama }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-800 text-center">
                                        <span class="bg-slate-100 px-2 py-1 rounded text-xs font-bold border border-slate-200">{{ sched.kelas?.nama_kelas }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-700">
                                        {{ sched.ruangan?.nama_ruangan }}
                                    </td>
                                </tr>
                                <tr v-if="!sortedList || sortedList.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center text-sm text-slate-500">
                                        Belum ada jadwal mengajar yang terbit untuk semester ini.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <!-- Modal Konfirmasi Batalkan Preferensi -->
        <Modal :show="confirmingDeletePreferensi" @close="closeDeleteModal" max-width="md">
            <div class="p-6">
                <div class="flex items-start gap-3">
                    <svg class="h-5 w-5 flex-shrink-0 mt-0.5" style="color: #b91c1c;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Batalkan Preferensi Jadwal</h3>
                        <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                            Preferensi untuk kelas
                            <span class="font-semibold text-slate-700">
                                {{ preferensiToDelete?.kelas_dibuka?.mata_kuliah?.nama }}
                            </span>
                            pada hari <span class="font-semibold text-slate-700">{{ preferensiToDelete?.hari }}</span>,
                            sesi <span class="font-semibold text-slate-700">{{ preferensiToDelete?.sesi }}</span>
                            Jam <span class="font-semibold text-slate-700">{{ slots[preferensiToDelete?.sesi] }}</span>
                            akan dihapus.
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button
                        type="button"
                        @click="closeDeleteModal"
                        class="px-4 py-2 rounded-lg text-xs font-bold text-slate-600 hover:bg-slate-100 transition"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        :disabled="deletePrefForm.processing"
                        @click="deletePreferensi"
                        class="px-4 py-2 rounded-lg text-xs font-bold bg-rose-600 text-white hover:bg-rose-700 shadow-sm transition disabled:opacity-50"
                    >
                        Ya, Batalkan
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>