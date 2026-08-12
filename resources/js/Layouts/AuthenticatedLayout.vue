<script setup>
import { ref } from 'vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);

// Daftar menu admin dipusatkan di satu tempat supaya navbar desktop & mobile selalu sinkron
const adminMenu = [
    { route: 'admin.jadwal', label: 'Jadwal' },
    { route: 'admin.setup.index', label: 'Setup Jadwal' },
    { route: 'admin.semester.index', label: 'Semester' },
    { route: 'admin.dosen.index', label: 'Dosen' },
    { route: 'admin.matkul.index', label: 'Mata Kuliah' },
    { route: 'admin.ruangan.index', label: 'Ruangan' },
    { route: 'admin.kelas.index', label: 'Kelas' },
    { route: 'admin.pengguna.index', label: 'Akun Pengguna' },
];

const roleLabels = {
    admin: 'Administrator',
    dosen: 'Dosen',
};
</script>

<template>
    <div>
        <div class="min-h-screen bg-slate-50 dark:bg-gray-900">
            <nav
                class="sticky top-0 z-50 border-b border-slate-200 bg-white/95 backdrop-blur dark:border-gray-700 dark:bg-gray-800/95"
            >
                <!-- Baris atas: logo, judul, aksi -->
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 items-center justify-between gap-2">
                        <!-- Logo -->
                        <div class="flex min-w-0 flex-1 items-center">
                            <Link :href="route('dashboard')" class="group flex min-w-0 cursor-pointer items-center">
                                <img src="/logo.svg" alt="Logo UTM" class="h-14 w-14 shrink-0 object-contain" />
                                <div class="min-w-0 px-4">
                                    <h1 class="truncate text-sm font-bold leading-tight tracking-tight text-slate-900 transition-colors group-hover:text-blue-900 dark:text-gray-100 md:text-lg xl:text-xl">
                                        Sistem Informasi Penjadwalan Mata Kuliah
                                    </h1>
                                    <p class="mt-0.5 truncate text-xs text-slate-500 dark:text-gray-400">
                                        Prodi Teknik Informatika, Universitas Trunojoyo Madura
                                    </p>
                                </div>
                            </Link>
                        </div>

                        <!-- Aksi kanan: dropdown user (desktop) + hamburger (mobile/tablet) -->
                        <div class="flex shrink-0 items-center gap-2">
                            <div class="hidden lg:block">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <button
                                            type="button"
                                            class="group inline-flex items-center gap-2.5 rounded-lg border border-slate-200 bg-white py-1.5 pl-2 pr-3 text-sm font-medium text-slate-700 transition duration-150 ease-in-out hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500/40 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                                        >
                                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-50 text-xs font-bold text-blue-800 ring-1 ring-inset ring-blue-100 dark:bg-blue-950 dark:text-blue-400 dark:ring-blue-900">
                                                {{ $page.props.auth.user.name?.charAt(0)?.toUpperCase() }}
                                            </span>
                                            <span class="flex min-w-0 flex-col items-start leading-tight">
                                                <span class="max-w-[140px] truncate xl:max-w-xs">{{ $page.props.auth.user.name }}</span>
                                                <span class="text-[11px] font-normal text-slate-400 dark:text-gray-500">
                                                    {{ roleLabels[$page.props.auth.user.role] ?? $page.props.auth.user.role }}
                                                </span>
                                            </span>
                                            <svg class="h-4 w-4 shrink-0 text-slate-400 transition-transform group-hover:text-slate-600 dark:group-hover:text-gray-300" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </template>
                                    <template #content>
                                        <DropdownLink :href="route('profile.edit')">Profil Saya</DropdownLink>
                                        <DropdownLink :href="route('logout')" method="post" as="button">Keluar</DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>

                            <!-- Hamburger -->
                            <button
                                @click="showingNavigationDropdown = !showingNavigationDropdown"
                                class="inline-flex items-center justify-center rounded-lg p-2 text-slate-500 transition duration-150 ease-in-out hover:bg-slate-100 hover:text-slate-700 focus:bg-slate-100 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-900 dark:hover:text-gray-200 lg:hidden"
                            >
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path
                                        :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }"
                                        stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }"
                                        stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Baris menu admin: tab text dengan indikator aktif, bisa di-scroll horizontal di layar sempit -->
                <div
                    v-if="$page.props.auth.user.role === 'admin'"
                    class="hidden border-t border-slate-100 dark:border-gray-700/60 dark:bg-gray-900/30 lg:block"
                >
                    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <div class="scrollbar-none flex gap-1 overflow-x-auto">
                            <Link
                                v-for="item in adminMenu"
                                :key="item.route"
                                :href="route(item.route)"
                                :class="[
                                    'relative whitespace-nowrap px-3.5 py-4 text-sm font-medium transition-colors',
                                    route().current(item.route)
                                        ? 'text-blue-800 dark:text-blue-400'
                                        : 'text-slate-500 hover:text-slate-800 dark:text-gray-400 dark:hover:text-gray-200',
                                ]"
                            >
                                {{ item.label }}
                                <span
                                    class="absolute inset-x-3 bottom-0 h-[2px] rounded-full transition-colors"
                                    :class="route().current(item.route) ? 'bg-blue-700 dark:bg-blue-400' : 'bg-transparent'"
                                />
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Menu Mobile/Tablet -->
                <div
                    :class="showingNavigationDropdown ? 'max-h-[36rem]' : 'max-h-0'"
                    class="overflow-hidden border-t border-slate-100 bg-white transition-[max-height] duration-200 ease-in-out dark:border-gray-700 dark:bg-gray-800 lg:hidden"
                >
                    <div class="px-3 py-3">
                        <template v-if="$page.props.auth.user.role === 'admin'">
                            <p class="px-2 pb-1.5 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-gray-500">
                                Menu Admin
                            </p>
                            <div class="flex flex-col gap-0.5">
                                <Link
                                    v-for="item in adminMenu"
                                    :key="item.route"
                                    :href="route(item.route)"
                                    :class="[
                                        'rounded-lg px-3 py-2.5 text-sm transition-colors',
                                        route().current(item.route)
                                            ? 'bg-blue-50 font-semibold text-blue-800 dark:bg-blue-950/40 dark:text-blue-400'
                                            : 'text-slate-600 hover:bg-slate-50 dark:text-gray-300 dark:hover:bg-gray-700/40',
                                    ]"
                                >
                                    {{ item.label }}
                                </Link>
                            </div>
                        </template>

                        <template v-else-if="$page.props.auth.user.role === 'dosen'">
                            <Link
                                :href="route('dosen.dashboard')"
                                :class="[
                                    'block rounded-lg px-3 py-2.5 text-sm transition-colors',
                                    route().current('dosen.dashboard')
                                        ? 'bg-blue-50 font-semibold text-blue-800 dark:bg-blue-950/40 dark:text-blue-400'
                                        : 'text-slate-600 hover:bg-slate-50 dark:text-gray-300 dark:hover:bg-gray-700/40',
                                ]"
                            >
                                Jadwal Mengajar
                            </Link>
                        </template>

                        <div class="mt-3 border-t border-slate-100 pt-3 dark:border-gray-700">
                            <div class="flex items-center gap-3 px-2 pb-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-50 text-sm font-bold text-blue-800 ring-1 ring-inset ring-blue-100 dark:bg-blue-950 dark:text-blue-400 dark:ring-blue-900">
                                    {{ $page.props.auth.user.name?.charAt(0)?.toUpperCase() }}
                                </span>
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-semibold text-slate-800 dark:text-gray-200">
                                        {{ $page.props.auth.user.name }}
                                    </div>
                                    <div class="truncate text-xs text-slate-500 dark:text-gray-400">
                                        {{ $page.props.auth.user.email }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col gap-0.5">
                                <Link
                                    :href="route('profile.edit')"
                                    :class="[
                                        'rounded-lg px-3 py-2.5 text-sm transition-colors',
                                        route().current('profile.edit')
                                            ? 'bg-blue-50 font-semibold text-blue-800 dark:bg-blue-950/40 dark:text-blue-400'
                                            : 'text-slate-600 hover:bg-slate-50 dark:text-gray-300 dark:hover:bg-gray-700/40',
                                    ]"
                                >
                                    Profil Saya
                                </Link>
                                <Link
                                    :href="route('logout')"
                                    method="post"
                                    as="button"
                                    class="w-full rounded-lg px-3 py-2.5 text-left text-sm text-slate-600 transition-colors hover:bg-slate-50 dark:text-gray-300 dark:hover:bg-gray-700/40"
                                >
                                    Keluar
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header class="border-b border-slate-200 bg-white dark:border-gray-700 dark:bg-gray-800" v-if="$slots.header">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>

<style scoped>
/* Sembunyikan scrollbar strip menu admin tapi tetap bisa di-scroll (untuk resolusi lg-xl yang pas-pasan) */
.scrollbar-none {
    scrollbar-width: none;
    -ms-overflow-style: none;
}
.scrollbar-none::-webkit-scrollbar {
    display: none;
}
</style>