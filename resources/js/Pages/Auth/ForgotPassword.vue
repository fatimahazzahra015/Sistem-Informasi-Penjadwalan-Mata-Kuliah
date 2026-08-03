<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import Navbar from '@/Components/Navbar.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <Head title="Lupa Password" />

    <div class="min-h-screen bg-slate-50 text-slate-800 font-sans flex flex-col">
        <Navbar 
            rightButtonText="Log In" 
            rightButtonRoute="login" 
            :showAuthButton="false" 
        />

        <!-- Main Workspace -->
        <main class="flex-1 flex items-center justify-center mx-auto max-w-md w-full px-4 py-12">
            <div class="w-full bg-white border border-slate-200 rounded-2xl p-8 shadow-sm">
                <div class="mb-6 text-center">
                    <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Lupa Password</h2>
                    <p class="text-xs text-slate-500 mt-1">
                        Masukkan email Anda dan kami akan mengirimkan tautan reset password.
                    </p>
                </div>

                <div v-if="status" class="mb-4 text-sm font-medium text-emerald-600 bg-emerald-50 border border-emerald-200 p-3 rounded-xl">
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="flex flex-col gap-4">
                    <div>
                        <InputLabel for="email" value="Email" class="text-slate-700 font-semibold" />

                        <TextInput
                            id="email"
                            type="email"
                            class="mt-1 block w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-sm text-slate-800 focus:outline-none focus:border-blue-700 focus:ring-1 focus:ring-blue-700 transition-all"
                            v-model="form.email"
                            required
                            autofocus
                            autocomplete="username"
                        />

                        <InputError class="mt-1" :message="form.errors.email" />
                    </div>

                    <div class="mt-2">
                        <button
                            type="submit"
                            class="w-full py-2.5 px-4 rounded-xl text-sm font-bold bg-blue-900 text-white hover:bg-blue-800 transition-all shadow-sm flex items-center justify-center"
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                        >
                            Kirim Tautan Reset
                        </button>
                    </div>

                    <div class="text-center">
                        <Link
                            :href="route('login')"
                            class="text-xs text-blue-900 hover:underline font-semibold"
                        >
                            Kembali ke halaman login
                        </Link>
                    </div>
                </form>
            </div>
        </main>
    </div>
</template>