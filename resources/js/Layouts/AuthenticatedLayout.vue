<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import ToastNotification from '@/Components/ToastNotification.vue';
import {
    LayoutDashboard,
    ArrowLeftRight,
    Wallet,
    Tags,
    PiggyBank,
    BarChart3,
    FileSpreadsheet,
    Settings,
    LogOut,
    Menu,
    X,
    UserCircle,
    ChevronDown
} from '@lucide/vue';

const page = usePage();
const mobileMenuOpen = ref(false);
const userDropdownOpen = ref(false);

const navigation = [
    { name: 'Dashboard', routeName: 'dashboard', icon: LayoutDashboard },
    { name: 'Transaksi', routeName: 'transactions.index', icon: ArrowLeftRight },
    { name: 'Rekening', routeName: 'accounts.index', icon: Wallet },
    { name: 'Kategori', routeName: 'categories.index', icon: Tags },
    { name: 'Anggaran', routeName: 'budgets.index', icon: PiggyBank },
    { name: 'Laporan', routeName: 'reports.index', icon: BarChart3 },
    { name: 'Google Sheets', routeName: 'sheets.index', icon: FileSpreadsheet },
    { name: 'Pengaturan Akun', routeName: 'profile.edit', icon: Settings },
];

function getHref(routeName) {
    try {
        return route().has(routeName) ? route(routeName) : '#';
    } catch {
        return '#';
    }
}

function isActive(routeName) {
    try {
        return route().current(routeName) || route().current(routeName.replace('.index', '') + '.*');
    } catch {
        return false;
    }
}
</script>

<template>
    <div class="min-h-screen bg-slate-50 text-slate-800 flex flex-col antialiased">
        <ToastNotification />

        <!-- Mobile Drawer Overlay -->
        <div
            v-if="mobileMenuOpen"
            @click="mobileMenuOpen = false"
            class="fixed inset-0 z-40 bg-slate-900/40 backdrop-blur-sm lg:hidden transition-opacity"
        ></div>

        <!-- Mobile Sidebar Drawer -->
        <aside
            class="fixed inset-y-0 left-0 z-50 w-72 bg-white border-r border-slate-200 transform transition-transform duration-300 ease-in-out lg:hidden flex flex-col"
            :class="mobileMenuOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="flex items-center justify-between p-4 border-b border-slate-100">
                <Link :href="route('dashboard')" @click="mobileMenuOpen = false">
                    <ApplicationLogo />
                </Link>
                <button
                    @click="mobileMenuOpen = false"
                    class="p-2 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100"
                >
                    <X class="w-5 h-5" />
                </button>
            </div>

            <div class="px-4 py-3 bg-slate-50/70 border-b border-slate-100">
                <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Pengguna</div>
                <div class="text-sm font-bold text-slate-800 truncate mt-0.5">{{ $page.props.auth?.user?.name }}</div>
                <div class="text-xs text-slate-500 truncate">{{ $page.props.auth?.user?.email }}</div>
            </div>

            <nav class="flex-1 overflow-y-auto p-3 space-y-1">
                <Link
                    v-for="item in navigation"
                    :key="item.name"
                    :href="getHref(item.routeName)"
                    @click="mobileMenuOpen = false"
                    class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-colors"
                    :class="isActive(item.routeName)
                        ? 'bg-emerald-500 text-white shadow-sm shadow-emerald-500/25'
                        : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                >
                    <component :is="item.icon" class="w-5 h-5 shrink-0" />
                    <span>{{ item.name }}</span>
                </Link>
            </nav>

            <div class="p-3 border-t border-slate-100">
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold text-rose-600 hover:bg-rose-50 transition-colors"
                >
                    <LogOut class="w-5 h-5 shrink-0" />
                    <span>Keluar (Logout)</span>
                </Link>
            </div>
        </aside>

        <!-- Main Layout with Desktop Sidebar -->
        <div class="flex flex-1">
            <!-- Desktop Sidebar -->
            <aside class="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 bg-white border-r border-slate-200 z-30">
                <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                    <Link :href="route('dashboard')">
                        <ApplicationLogo />
                    </Link>
                </div>

                <!-- Navigation Links -->
                <nav class="flex-1 overflow-y-auto px-4 py-4 space-y-1.5">
                    <div class="px-3 pb-2 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                        Menu Utama
                    </div>

                    <Link
                        v-for="item in navigation.slice(0, 6)"
                        :key="item.name"
                        :href="getHref(item.routeName)"
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all"
                        :class="isActive(item.routeName)
                            ? 'bg-emerald-600 text-white font-semibold shadow-sm shadow-emerald-600/30'
                            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                    >
                        <component :is="item.icon" class="w-4 h-4 shrink-0" />
                        <span>{{ item.name }}</span>
                    </Link>

                    <div class="pt-4 px-3 pb-2 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                        Sistem & Integrasi
                    </div>

                    <Link
                        v-for="item in navigation.slice(6)"
                        :key="item.name"
                        :href="getHref(item.routeName)"
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all"
                        :class="isActive(item.routeName)
                            ? 'bg-emerald-600 text-white font-semibold shadow-sm shadow-emerald-600/30'
                            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                    >
                        <component :is="item.icon" class="w-4 h-4 shrink-0" />
                        <span>{{ item.name }}</span>
                    </Link>
                </nav>

                <!-- User profile & Logout footer -->
                <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-800 font-bold flex items-center justify-center shrink-0 text-sm">
                                {{ ($page.props.auth?.user?.name || 'U').charAt(0).toUpperCase() }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-slate-800 truncate">{{ $page.props.auth?.user?.name }}</p>
                                <p class="text-xs text-slate-500 truncate">{{ $page.props.auth?.user?.email }}</p>
                            </div>
                        </div>

                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            title="Keluar"
                            class="p-2 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition"
                        >
                            <LogOut class="w-4 h-4" />
                        </Link>
                    </div>
                </div>
            </aside>

            <!-- Main Content Area -->
            <div class="flex-1 lg:pl-64 flex flex-col min-w-0">
                <!-- Top Navbar -->
                <header class="sticky top-0 z-20 bg-white/90 backdrop-blur-md border-b border-slate-200">
                    <div class="flex items-center justify-between px-4 sm:px-6 lg:px-8 h-16">
                        <div class="flex items-center gap-3">
                            <button
                                @click="mobileMenuOpen = true"
                                class="p-2 rounded-xl text-slate-600 hover:bg-slate-100 lg:hidden"
                                aria-label="Buka menu"
                            >
                                <Menu class="w-6 h-6" />
                            </button>

                            <h1 class="text-lg sm:text-xl font-bold text-slate-800 flex items-center gap-2">
                                <slot name="header">FinTrack</slot>
                            </h1>
                        </div>

                        <!-- Top actions -->
                        <div class="flex items-center gap-2 sm:gap-4">
                            <slot name="actions" />

                            <!-- User badge on mobile -->
                            <div class="relative lg:hidden">
                                <Link
                                    :href="route('profile.edit')"
                                    class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-800 font-bold flex items-center justify-center text-sm"
                                >
                                    {{ ($page.props.auth?.user?.name || 'U').charAt(0).toUpperCase() }}
                                </Link>
                            </div>
                        </div>
                    </div>
                </header>

                <!-- Page Content -->
                <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto pb-24 lg:pb-8">
                    <slot />
                </main>
            </div>
        </div>

        <!-- Mobile Bottom Nav -->
        <nav class="lg:hidden fixed bottom-0 inset-x-0 bg-white border-t border-slate-200 z-30 px-2 py-1.5 flex items-center justify-around shadow-lg">
            <Link
                :href="route('dashboard')"
                class="flex flex-col items-center justify-center py-1 px-2 rounded-lg text-xs font-medium transition"
                :class="isActive('dashboard') ? 'text-emerald-600 font-semibold' : 'text-slate-500 hover:text-slate-900'"
            >
                <LayoutDashboard class="w-5 h-5 mb-0.5" />
                <span>Dashboard</span>
            </Link>

            <Link
                :href="getHref('transactions.index')"
                class="flex flex-col items-center justify-center py-1 px-2 rounded-lg text-xs font-medium transition"
                :class="isActive('transactions.index') ? 'text-emerald-600 font-semibold' : 'text-slate-500 hover:text-slate-900'"
            >
                <ArrowLeftRight class="w-5 h-5 mb-0.5" />
                <span>Transaksi</span>
            </Link>

            <Link
                :href="getHref('accounts.index')"
                class="flex flex-col items-center justify-center py-1 px-2 rounded-lg text-xs font-medium transition"
                :class="isActive('accounts.index') ? 'text-emerald-600 font-semibold' : 'text-slate-500 hover:text-slate-900'"
            >
                <Wallet class="w-5 h-5 mb-0.5" />
                <span>Rekening</span>
            </Link>

            <Link
                :href="getHref('reports.index')"
                class="flex flex-col items-center justify-center py-1 px-2 rounded-lg text-xs font-medium transition"
                :class="isActive('reports.index') ? 'text-emerald-600 font-semibold' : 'text-slate-500 hover:text-slate-900'"
            >
                <BarChart3 class="w-5 h-5 mb-0.5" />
                <span>Laporan</span>
            </Link>

            <button
                @click="mobileMenuOpen = true"
                class="flex flex-col items-center justify-center py-1 px-2 rounded-lg text-xs font-medium text-slate-500 hover:text-slate-900"
            >
                <Menu class="w-5 h-5 mb-0.5" />
                <span>Lainnya</span>
            </button>
        </nav>
    </div>
</template>
