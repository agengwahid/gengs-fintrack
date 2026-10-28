<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Wallet, ArrowLeftRight, PiggyBank, BarChart3, TrendingUp, TrendingDown } from '@lucide/vue';
</script>

<template>
    <Head title="Dashboard Keuangan" />

    <AuthenticatedLayout>
        <template #header>
            <span>Dashboard Keuangan</span>
        </template>

        <div class="space-y-6">
            <div class="bg-gradient-to-r from-emerald-600 to-teal-600 rounded-3xl p-6 sm:p-8 text-white shadow-xl shadow-emerald-900/10">
                <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Selamat Datang di FinTrack</h2>
                <p class="mt-2 text-emerald-100 max-w-xl text-sm leading-relaxed">
                    Fondasi sistem telah aktif dengan autentikasi sesi, PostgreSQL, dan antarmuka modern yang siap digunakan.
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
