<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    auth: Object,
    rightButtonText: {
        type: String,
        default: 'Jadwal Saya'
    },
    rightButtonRoute: {
        type: String,
        default: 'guest.krs'
    },
    showAuthButton: {
        type: Boolean,
        default: true
    }
});
</script>

<template>
    <header class="border-b border-slate-200 bg-white/95 backdrop-blur sticky top-0 z-50 shadow-sm">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
            <div class="flex items-center min-w-0">
                <Link :href="route('welcome')" class="flex items-center min-w-0 group cursor-pointer">
                    <img src="/logo.svg" alt="Logo UTM" class="w-14 h-14 shrink-0 object-contain" />
                    <div class="px-4 min-w-0">
                        <h1 class="font-bold text-sm md:text-lg xl:text-xl tracking-tight text-slate-900 leading-tight truncate group-hover:text-blue-900 transition-colors">
                            Sistem Informasi Penjadwalan Mata Kuliah
                        </h1>
                        <p class="text-xs text-slate-500 mt-0.5 truncate">Prodi Teknik Informatika, Universitas Trunojoyo Madura</p>
                    </div>
                </Link>
            </div>
            
            <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                <Link
                    :href="route(rightButtonRoute)"
                    class="inline-flex px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold border border-slate-300 text-slate-700 hover:bg-slate-100 transition-all"
                >
                    {{ rightButtonText }}
                </Link>

                <template v-if="showAuthButton">
                    <Link
                        v-if="auth?.user"
                        :href="route('dashboard')"
                        class="px-3 sm:px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold bg-blue-900 text-white hover:bg-blue-800 transition-all shadow-sm"
                    >
                        Dashboard
                    </Link>
                    <Link
                        v-else
                        :href="route('login')"
                        class="px-3 sm:px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold bg-blue-900 text-white hover:bg-blue-800 transition-all shadow-sm"
                    >
                        Login
                    </Link>
                </template>
            </div>
        </div>
    </header>
</template>