<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Navbar from '@/Components/Navbar.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Log in" />

    <div class="min-h-screen bg-slate-50 text-slate-800 font-sans flex flex-col">
        <Navbar 
            rightButtonText="Beranda" 
            rightButtonRoute="welcome" 
            :showAuthButton="false" 
        />

        <!-- Main Workspace -->
        <main class="flex-1 flex items-center justify-center mx-auto max-w-md w-full px-4 py-12">
            <div class="w-full bg-white border border-slate-200 rounded-2xl p-8 shadow-sm">
                <div class="mb-6 text-center">
                    <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Masuk ke Akun</h2>
                    <p class="text-xs text-slate-500 mt-1">Silakan masukkan email dan password Anda</p>
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

                    <div>
                        <InputLabel for="password" value="Password" class="text-slate-700 font-semibold" />

                        <TextInput
                            id="password"
                            type="password"
                            class="mt-1 block w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-sm text-slate-800 focus:outline-none focus:border-blue-700 focus:ring-1 focus:ring-blue-700 transition-all"
                            v-model="form.password"
                            required
                            autocomplete="current-password"
                        />

                        <InputError class="mt-1" :message="form.errors.password" />
                    </div>

                    <div class="flex items-center justify-end">
                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-xs text-blue-900 hover:underline font-semibold"
                        >
                            Lupa password?
                        </Link>
                    </div>

                    <div class="mt-2">
                        <button
                            type="submit"
                            class="w-full py-2.5 px-4 rounded-xl text-sm font-bold bg-blue-900 text-white hover:bg-blue-800 transition-all shadow-sm flex items-center justify-center"
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                        >
                            Log in
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</template>