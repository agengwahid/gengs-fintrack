<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import {
    Wallet,
    ArrowLeftRight,
    PieChart,
    FileSpreadsheet,
    ShieldCheck,
    Smartphone,
    CheckCircle2,
    ArrowRight
} from '@lucide/vue';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});
</script>

<template>
    <Head title="FinTrack - Kelola Keuangan Pribadi Jadi Lebih Pintar" />

    <div class="min-h-screen bg-slate-50 text-slate-800 antialiased selection:bg-emerald-500 selection:text-white">
        <!-- Navigation -->
        <header class="sticky top-0 z-30 bg-white/80 backdrop-blur-md border-b border-slate-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <ApplicationLogo />

                <div v-if="canLogin" class="flex items-center gap-3">
                    <Link
                        v-if="$page.props.auth?.user"
                        :href="route('dashboard')"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition"
                    >
                        Buka Dashboard
                        <ArrowRight class="w-4 h-4" />
                    </Link>

                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-700 hover:text-slate-900 hover:bg-slate-100 transition"
                        >
                            Masuk
                        </Link>

                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="px-4 py-2 rounded-xl text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 shadow-md shadow-emerald-600/20 transition"
                        >
                            Daftar Gratis
                        </Link>
                    </template>
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="relative overflow-hidden py-16 sm:py-24">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto">
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-50 border border-emerald-200 text-xs font-semibold text-emerald-700 mb-6">
                        <span class="flex h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Aplikasi Pencatatan Keuangan Modern & Terintegrasi
                    </div>

                    <h1 class="text-4xl sm:text-6xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        Kendalikan Uang Anda, Wujudkan <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-600 to-teal-500">Kemerdekaan Finansial</span>
                    </h1>

                    <p class="mt-6 text-base sm:text-lg text-slate-600 leading-relaxed max-w-2xl mx-auto">
                        Pantau pemasukan, kontrol pengeluaran harian, atur anggaran per kategori, dan sinkronkan transaksi secara otomatis ke Google Sheets tanpa ribet.
                    </p>

                    <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-4">
                        <Link
                            :href="$page.props.auth?.user ? route('dashboard') : route('register')"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-3.5 rounded-xl text-base font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-lg shadow-emerald-600/30 transition transform hover:-translate-y-0.5"
                        >
                            {{ $page.props.auth?.user ? 'Menuju Dashboard' : 'Mulai Sekarang' }}
                            <ArrowRight class="w-5 h-5" />
                        </Link>

                        <a
                            href="#features"
                            class="w-full sm:w-auto inline-flex items-center justify-center px-7 py-3.5 rounded-xl text-base font-semibold text-slate-700 bg-white hover:bg-slate-100 border border-slate-200 transition"
                        >
                            Pelajari Fitur
                        </a>
                    </div>
                </div>

                <!-- Feature Highlights Preview -->
                <div class="mt-14 max-w-4xl mx-auto bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-2xl shadow-slate-200/50">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 text-center divide-y sm:divide-y-0 sm:divide-x divide-slate-100">
                        <div class="pt-4 sm:pt-0">
                            <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Format Mata Uang</div>
                            <div class="text-2xl font-black text-slate-800 mt-1">Rupiah (IDR)</div>
                            <div class="text-xs text-emerald-600 font-medium mt-1">Rp1.250.000 akurat</div>
                        </div>
                        <div class="pt-4 sm:pt-0">
                            <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Integrasi Cloud</div>
                            <div class="text-2xl font-black text-slate-800 mt-1">Google Sheets</div>
                            <div class="text-xs text-teal-600 font-medium mt-1">Otomatis & Idempoten</div>
                        </div>
                        <div class="pt-4 sm:pt-0">
                            <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Tampilan Layar</div>
                            <div class="text-2xl font-black text-slate-800 mt-1">Mobile First</div>
                            <div class="text-xs text-sky-600 font-medium mt-1">Nyaman di HP & Laptop</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Grid -->
        <section id="features" class="py-16 bg-white border-y border-slate-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto mb-12">
                    <h2 class="text-3xl font-extrabold text-slate-900">Dirancang untuk Kemudahan Anda</h2>
                    <p class="mt-3 text-slate-600">Semua fitur yang Anda butuhkan untuk mencatat dan mengevaluasi arus kas bulanan.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <!-- Feature 1 -->
                    <div class="p-6 rounded-2xl bg-slate-50 border border-slate-100 hover:border-emerald-200 hover:shadow-md transition">
                        <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center mb-4">
                            <Wallet class="w-6 h-6" />
                        </div>
                        <h3 class="text-lg font-bold text-slate-800">Multi Rekening</h3>
                        <p class="mt-2 text-sm text-slate-600 leading-relaxed">
                            Pisahkan saldo uang tunai, bank, dan berbagai e-wallet. Saldo terhitung otomatis dari riwayat transaksi yang valid.
                        </p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="p-6 rounded-2xl bg-slate-50 border border-slate-100 hover:border-emerald-200 hover:shadow-md transition">
                        <div class="w-12 h-12 rounded-xl bg-teal-100 text-teal-700 flex items-center justify-center mb-4">
                            <ArrowLeftRight class="w-6 h-6" />
                        </div>
                        <h3 class="text-lg font-bold text-slate-800">Transaksi Cepat</h3>
                        <p class="mt-2 text-sm text-slate-600 leading-relaxed">
                            Input pemasukan dan pengeluaran dalam hitungan detik dengan kategori, nominal rupiah, tanggal, dan catatan detail.
                        </p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="p-6 rounded-2xl bg-slate-50 border border-slate-100 hover:border-emerald-200 hover:shadow-md transition">
                        <div class="w-12 h-12 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center mb-4">
                            <PieChart class="w-6 h-6" />
                        </div>
                        <h3 class="text-lg font-bold text-slate-800">Anggaran & Realisasi</h3>
                        <p class="mt-2 text-sm text-slate-600 leading-relaxed">
                            Pasang target anggaran per kategori setiap bulan. Pantau persentase penggunaan dan cegah overbudget sebelum terlambat.
                        </p>
                    </div>

                    <!-- Feature 4 -->
                    <div class="p-6 rounded-2xl bg-slate-50 border border-slate-100 hover:border-emerald-200 hover:shadow-md transition">
                        <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center mb-4">
                            <FileSpreadsheet class="w-6 h-6" />
                        </div>
                        <h3 class="text-lg font-bold text-slate-800">Sinkronisasi Google Sheets</h3>
                        <p class="mt-2 text-sm text-slate-600 leading-relaxed">
                            Setiap transaksi terkirim otomatis ke Google Spreadsheet Anda via background queue job, lengkap dengan ringkasan bulanan.
                        </p>
                    </div>

                    <!-- Feature 5 -->
                    <div class="p-6 rounded-2xl bg-slate-50 border border-slate-100 hover:border-emerald-200 hover:shadow-md transition">
                        <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center mb-4">
                            <Smartphone class="w-6 h-6" />
                        </div>
                        <h3 class="text-lg font-bold text-slate-800">Mobile First & Responsif</h3>
                        <p class="mt-2 text-sm text-slate-600 leading-relaxed">
                            Akses langsung melalui browser HP dengan navigasi bawah yang nyaman serta tampilan sidebar yang lega di laptop.
                        </p>
                    </div>

                    <!-- Feature 6 -->
                    <div class="p-6 rounded-2xl bg-slate-50 border border-slate-100 hover:border-emerald-200 hover:shadow-md transition">
                        <div class="w-12 h-12 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center mb-4">
                            <ShieldCheck class="w-6 h-6" />
                        </div>
                        <h3 class="text-lg font-bold text-slate-800">Aman & Terisolasi</h3>
                        <p class="mt-2 text-sm text-slate-600 leading-relaxed">
                            Proteksi CSRF, password hashing standar Laravel, UUID sebagai identitas, dan otorisasi ketat berbasis pengguna.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="py-8 bg-slate-50 text-slate-500 text-center text-xs">
            <div class="max-w-7xl mx-auto px-4">
                &copy; {{ new Date().getFullYear() }} FinTrack. Dirancang untuk pencatatan keuangan pribadi yang handal.
            </div>
        </footer>
    </div>
</template>
