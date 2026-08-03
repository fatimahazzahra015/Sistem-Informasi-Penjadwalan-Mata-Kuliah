<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Konfirmasi Password" />

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
                        :href="route('welcome')"
                        class="px-3 sm:px-4 py-2 rounded-lg text-sm font-semibold border border-slate-300 text-slate-700 hover:bg-slate-100 transition-all"
                    >
                        Beranda
                    </Link>
                </div>
            </div>
        </header>

        <!-- Main Workspace -->
        <main class="flex-1 flex items-center justify-center mx-auto max-w-md w-full px-4 py-12">
            <div class="w-full bg-white border border-slate-200 rounded-2xl p-8 shadow-sm">
                <div class="mb-6 text-center">
                    <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Konfirmasi Password</h2>
                    <p class="text-xs text-slate-500 mt-1">
                        Ini adalah area aman dari aplikasi. Silakan konfirmasi password Anda sebelum melanjutkan.
                    </p>
                </div>

                <form @submit.prevent="submit" class="flex flex-col gap-4">
                    <div>
                        <InputLabel for="password" value="Password" class="text-slate-700 font-semibold" />

                        <TextInput
                            id="password"
                            type="password"
                            class="mt-1 block w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-sm text-slate-800 focus:outline-none focus:border-blue-700 focus:ring-1 focus:ring-blue-700 transition-all"
                            v-model="form.password"
                            required
                            autocomplete="current-password"
                            autofocus
                        />

                        <InputError class="mt-1" :message="form.errors.password" />
                    </div>

                    <div class="mt-2">
                        <button
                            type="submit"
                            class="w-full py-2.5 px-4 rounded-xl text-sm font-bold bg-blue-900 text-white hover:bg-blue-800 transition-all shadow-sm flex items-center justify-center"
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                        >
                            Konfirmasi
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</template>