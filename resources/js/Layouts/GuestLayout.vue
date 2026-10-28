<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-gradient-to-br from-slate-50 via-slate-100 to-emerald-50/40 p-4 sm:p-6 antialiased">
        <div class="mb-6">
            <Link href="/" class="flex flex-col items-center gap-1 group">
                <ApplicationLogo class="transform group-hover:scale-105 transition duration-200" />
                <p class="text-xs text-slate-500 font-medium tracking-wide">Pencatatan Keuangan Pribadi Modern</p>
            </Link>
        </div>

        <div class="w-full sm:max-w-md bg-white rounded-2xl shadow-xl shadow-slate-200/60 border border-slate-100 p-6 sm:p-8">
            <slot />
        </div>

        <div class="mt-8 text-center text-xs text-slate-400">
            &copy; {{ new Date().getFullYear() }} FinTrack. Hak cipta dilindungi.
        </div>
    </div>
</template>
