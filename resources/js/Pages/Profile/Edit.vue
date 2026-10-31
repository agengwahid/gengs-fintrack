<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';
import { Settings, User, Lock, Trash2, Globe, ShieldCheck } from '@lucide/vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Pengaturan Akun - FinTrack" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-2">
                <Settings class="w-6 h-6 text-emerald-600" />
                <span>Pengaturan Akun & Keamanan</span>
            </div>
        </template>

        <div class="space-y-6 max-w-4xl">
            <!-- Informasi Preferensi FinTrack -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm">
                <div class="flex items-center gap-3 pb-4 border-b border-slate-100">
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <Globe class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Preferensi & Lokalisasi FinTrack</h3>
                        <p class="text-xs text-slate-500">Standar regional dan format tampilan aplikasi</p>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Zona Waktu</span>
                        <div class="mt-1 text-xs font-bold text-slate-800">
                            Asia/Jakarta (WIB)
                        </div>
                    </div>

                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Format Mata Uang</span>
                        <div class="mt-1 text-xs font-bold text-slate-800">
                            Rupiah Indonesia (Rp)
                        </div>
                    </div>

                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Format Tanggal</span>
                        <div class="mt-1 text-xs font-bold text-slate-800">
                            Bahasa Indonesia (DD MMMM YYYY)
                        </div>
                    </div>
                </div>
            </div>

            <!-- Update Profil -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm">
                <div class="flex items-center gap-3 pb-4 border-b border-slate-100">
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <User class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Informasi Profil</h3>
                        <p class="text-xs text-slate-500">Perbarui nama pengguna dan alamat email Anda</p>
                    </div>
                </div>

                <div class="mt-5">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                    />
                </div>
            </div>

            <!-- Update Password -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm">
                <div class="flex items-center gap-3 pb-4 border-b border-slate-100">
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <Lock class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Perbarui Kata Sandi</h3>
                        <p class="text-xs text-slate-500">Pastikan akun Anda menggunakan kata sandi yang kuat dan aman</p>
                    </div>
                </div>

                <div class="mt-5">
                    <UpdatePasswordForm />
                </div>
            </div>

            <!-- Hapus Akun -->
            <div class="bg-white rounded-2xl p-6 border border-rose-200/80 shadow-sm">
                <div class="flex items-center gap-3 pb-4 border-b border-slate-100">
                    <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                        <Trash2 class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-rose-900">Hapus Akun Pengguna</h3>
                        <p class="text-xs text-slate-500">Tindakan ini permanen dan akan menghapus seluruh data rekening, transaksi, dan anggaran</p>
                    </div>
                </div>

                <div class="mt-5">
                    <DeleteUserForm />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
