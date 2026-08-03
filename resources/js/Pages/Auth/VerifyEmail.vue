<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <Head title="Verifikasi Email" />

    <div class="min-h-screen bg-slate-50 text-slate-800 font-sans flex flex-col">
        <!-- Navigation Header -->
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
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="px-3 sm:px-4 py-2 rounded-lg text-sm font-semibold border border-slate-300 text-slate-700 hover:bg-slate-100 transition-all"
                    >
                        Keluar (Log Out)
                    </Link>
                </div>
            </div>
        </header>

        <!-- Main Workspace -->
        <main class="flex-1 flex items-center justify-center mx-auto max-w-md w-full px-4 py-12">
            <div class="w-full bg-white border border-slate-200 rounded-2xl p-8 shadow-sm">
                <div class="mb-6 text-center">
                    <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Verifikasi Email</h2>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                        Terima kasih telah mendaftar! Sebelum mulai, mohon verifikasi alamat email Anda dengan mengeklik tautan yang baru saja kami kirimkan. Jika Anda tidak menerima email tersebut, dengan senang hati kami akan mengirimkannya kembali.
                    </p>
                </div>

                <div v-if="verificationLinkSent" class="mb-4 text-sm font-medium text-emerald-600 bg-emerald-50 border border-emerald-200 p-3 rounded-xl text-center">
                    Tautan verifikasi baru telah dikirim ke alamat email yang Anda berikan saat pendaftaran.
                </div>

                <form @submit.prevent="submit" class="flex flex-col gap-4">
                    <div class="mt-2">
                        <button
                            type="submit"
                            class="w-full py-2.5 px-4 rounded-xl text-sm font-bold bg-blue-900 text-white hover:bg-blue-800 transition-all shadow-sm flex items-center justify-center"
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                        >
                            Kirim Ulang Email Verifikasi
                        </button>
                    </div>

                    <div class="flex items-center justify-center mt-2">
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="text-xs text-blue-900 hover:underline font-semibold"
                        >
                            Keluar dari akun
                        </Link>
                    </div>
                </form>
            </div>
        </main>
    </div>
</template>