<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { AlertCircle } from '@lucide/vue';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    error: {
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
    <GuestLayout>
        <Head title="Masuk" />

        <div class="mb-6">
            <h2 class="text-xl font-bold text-slate-800">Selamat Datang Kembali</h2>
            <p class="text-xs text-slate-500 mt-1">Masuk untuk mengelola keuangan pribadi Anda</p>
        </div>

        <div v-if="status" class="mb-4 text-xs sm:text-sm font-medium text-emerald-700 bg-emerald-50 p-3 rounded-xl border border-emerald-200">
            {{ status }}
        </div>

        <div v-if="error" class="mb-4 text-xs sm:text-sm font-medium text-rose-700 bg-rose-50 p-3 rounded-xl border border-rose-200 flex items-start gap-2">
            <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
            <span>{{ error }}</span>
        </div>

        <!-- Google Login Button -->
        <div>
            <a
                :href="route('auth.google.redirect')"
                class="w-full flex items-center justify-center gap-3 py-2.5 px-4 rounded-xl text-sm font-semibold text-slate-700 bg-white hover:bg-slate-50 active:bg-slate-100 border border-slate-300 shadow-sm transition duration-150 ease-in-out"
            >
                <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" aria-hidden="true">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                    <path fill="#FBBC05" d="M5.84 14.1c-.22-.66-.35-1.36-.35-2.1s.13-1.44.35-2.1V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.62z" />
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z" />
                </svg>
                <span>Masuk dengan Google</span>
            </a>
        </div>

        <div class="relative my-5">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-slate-200"></div>
            </div>
            <div class="relative flex justify-center text-xs uppercase">
                <span class="bg-white px-3 text-slate-400 font-medium tracking-wider">
                    atau dengan email
                </span>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <InputLabel for="email" value="Alamat Email" />
                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 text-sm"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="nama@email.com"
                />
                <InputError class="mt-1.5" :message="form.errors.email" />
            </div>

            <div>
                <div class="flex items-center justify-between">
                    <InputLabel for="password" value="Kata Sandi" />
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-xs text-emerald-600 hover:text-emerald-700 font-medium"
                    >
                        Lupa sandi?
                    </Link>
                </div>
                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 text-sm"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                />
                <InputError class="mt-1.5" :message="form.errors.password" />
            </div>

            <div class="flex items-center">
                <label class="flex items-center cursor-pointer">
                    <Checkbox name="remember" v-model:checked="form.remember" class="text-emerald-600 rounded focus:ring-emerald-500" />
                    <span class="ms-2 text-xs text-slate-600">Ingat sesi saya</span>
                </label>
            </div>

            <div class="pt-2">
                <button
                    type="submit"
                    class="w-full flex items-center justify-center py-2.5 px-4 rounded-xl text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 shadow-md shadow-emerald-600/20 transition duration-150 ease-in-out disabled:opacity-50"
                    :disabled="form.processing"
                >
                    <span v-if="form.processing">Memproses...</span>
                    <span v-else>Masuk ke Akun</span>
                </button>
            </div>

            <div class="pt-3 text-center text-xs text-slate-500">
                Belum memiliki akun?
                <Link :href="route('register')" class="font-semibold text-emerald-600 hover:text-emerald-700 ml-1">
                    Daftar sekarang
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
