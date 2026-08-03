<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    semester: Object,
    schedules: Array,
    slots: Object,
});

const sortedSchedules = computed(() => {
    return [...props.schedules].sort((a, b) => {
        const dayWeights = { 'Senin': 1, 'Selasa': 2, 'Rabu': 3, 'Kamis': 4, 'Jumat': 5 };
        const dayA = dayWeights[a.hari] || 9;
        const dayB = dayWeights[b.hari] || 9;
        if (dayA !== dayB) return dayA - dayB;
        return a.slot_mulai - b.slot_mulai;
    });
});
</script>

<template>
    <Head :title="'Arsip Semester ' + semester.nama + ' ' + semester.tahun_ajaran" />

    <AuthenticatedLayout>
        <div class="py-8 bg-slate-50 dark:bg-gray-900 min-h-screen">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Header Section (disamakan dengan halaman Index Semester) -->
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between bg-white dark:bg-gray-800 p-6 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm">
                    <div>
                        <h2 class="text-2xl font-extrabold text-slate-900 dark:text-gray-100 tracking-tight">
                            Arsip Jadwal Kuliah
                        </h2>
                        <p class="text-sm text-slate-500 dark:text-gray-400 mt-1">
                            Menampilkan jadwal historis untuk Semester <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ semester.nama }} {{ semester.tahun_ajaran }}</span>
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <Link
                            :href="route('admin.semester.index')"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold bg-slate-100 dark:bg-gray-900 text-slate-600 dark:text-gray-300 hover:bg-slate-200 dark:hover:bg-gray-800 transition-all border border-slate-200 dark:border-gray-700 whitespace-nowrap shadow-sm"
                        >
                            Kembali ke Semester
                        </Link>
                    </div>
                </div>

                <!-- Schedule List Card (disamakan desain card & tabelnya) -->
                <div class="bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-2xl overflow-hidden shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse min-w-[750px]">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-gray-900/60 border-b border-slate-200 dark:border-gray-700">
                                    <th class="w-[80px] px-6 py-4 text-xs font-bold text-slate-500 dark:text-gray-400 tracking-wider text-center">No</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-gray-400 tracking-wider">Hari</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-gray-400 tracking-wider">Waktu</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-gray-400 tracking-wider">Mata Kuliah</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-gray-400 tracking-wider text-center">Kelas</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-gray-400 tracking-wider">Ruangan</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-gray-400 tracking-wider">Dosen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(sched, index) in sortedSchedules"
                                    :key="sched.id"
                                    class="border-b border-slate-100 dark:border-gray-700/50 hover:bg-slate-50/60 dark:hover:bg-gray-900/10 transition-colors"
                                >
                                    <td class="px-6 py-4 text-sm text-slate-500 dark:text-gray-400 text-center">{{ index + 1 }}</td>
                                    <td class="px-6 py-4 text-sm font-semibold text-slate-800 dark:text-gray-200">{{ sched.hari }}</td>
                                    <td class="px-6 py-4 text-sm text-slate-600 dark:text-gray-400">
                                        {{ slots[sched.slot_mulai].split(' - ')[0] }} - {{ slots[sched.slot_selesai].split(' - ')[1] }} ({{ sched.mata_kuliah.sks }} SKS)
                                    </td>
                                    <td class="px-6 py-4 text-sm font-bold text-indigo-600 dark:text-indigo-400">{{ sched.mata_kuliah.nama }}</td>
                                    <td class="px-6 py-4 text-sm text-slate-800 dark:text-gray-200 text-center">
                                        <span class="bg-slate-100 dark:bg-gray-900 px-2 py-1 rounded text-xs font-bold border border-slate-200 dark:border-gray-800">{{ sched.kelas.nama_kelas }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-700 dark:text-gray-300">
                                        {{ sched.ruangan.nama_ruangan }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-700 dark:text-gray-300 font-medium">
                                        {{ sched.dosen.nama }}
                                    </td>
                                </tr>
                                <tr v-if="schedules.length === 0">
                                    <td colspan="7" class="px-6 py-12 text-center text-sm text-slate-500 dark:text-gray-400">
                                        Tidak ada jadwal kuliah yang tercatat pada semester ini.
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