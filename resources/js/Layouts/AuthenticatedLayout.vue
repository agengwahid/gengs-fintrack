<script setup>
import { ref } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import ToastNotification from '@/Components/ToastNotification.vue';
import {
    LayoutDashboard,
    ArrowLeftRight,
    Wallet,
    Tags,
    Target,
    BarChart3,
    FileSpreadsheet,
    Settings,
    LogOut,
    Menu,
    X,
    UserCircle,
    ChevronDown,
    PanelLeftClose,
    PanelLeftOpen,
    Plus
} from '@lucide/vue';

const page = usePage();
const mobileMenuOpen = ref(false);
const userDropdownOpen = ref(false);
const isSidebarCollapsed = ref(
    typeof window !== 'undefined' ? localStorage.getItem('fintrack_sidebar_collapsed') === 'true' : false
);

function toggleSidebar() {
    isSidebarCollapsed.value = !isSidebarCollapsed.value;
    if (typeof window !== 'undefined') {
        localStorage.setItem('fintrack_sidebar_collapsed', isSidebarCollapsed.value ? 'true' : 'false');
    }
}

const navigation = [
    { name: 'Dashboard', routeName: 'dashboard', icon: LayoutDashboard },
    { name: 'Transaksi', routeName: 'transactions.index', icon: ArrowLeftRight },
    { name: 'Rekening', routeName: 'accounts.index', icon: Wallet },
    { name: 'Kategori', routeName: 'categories.index', icon: Tags },
    { name: 'Anggaran', routeName: 'budgets.index', icon: Target },
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

function handleMobileQuickAdd() {
    if (route().current('transactions.*')) {
        window.dispatchEvent(new CustomEvent('fintrack:open-create-transaction'));
    } else {
        router.visit(route('transactions.index', { create: 1 }));
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
                        ? 'bg-emerald-50 text-emerald-800'
                        : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                >
                    <component :is="item.icon" class="w-5 h-5 shrink-0" :class="isActive(item.routeName) ? 'text-emerald-600' : 'text-slate-400'" />
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
            <aside
                class="hidden lg:flex lg:flex-col lg:fixed lg:inset-y-0 bg-white border-r border-slate-200 z-30 transition-[width] duration-300 ease-in-out overflow-hidden"
                :class="isSidebarCollapsed ? 'lg:w-20' : 'lg:w-64'"
            >
                <!-- Brand Header (Single branding, smoothly fades text when collapsed) -->
                <div class="h-16 border-b border-slate-100 flex items-center px-4 overflow-hidden shrink-0">
                    <Link :href="route('dashboard')" class="flex items-center gap-2.5 min-w-max">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-tr from-emerald-600 via-emerald-500 to-teal-500 text-white shadow-md shadow-emerald-500/20 shrink-0 ml-0.5">
                            <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 19h16" stroke="currentColor" stroke-width="2" opacity="0.6" stroke-linecap="round" />
                                <path d="M5 15l5-5 4 4 6-7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />
                                <polyline points="16 7 21 7 21 12" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>
                        <span
                            class="text-xl font-bold tracking-tight text-slate-800 whitespace-nowrap transition-all duration-300"
                            :class="isSidebarCollapsed ? 'opacity-0 max-w-0 -translate-x-2' : 'opacity-100 max-w-[160px] translate-x-0'"
                        >
                            Fin<span class="text-emerald-600">Track</span>
                        </span>
                    </Link>
                </div>

                <!-- Navigation Links -->
                <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1.5 overflow-x-hidden">
                    <div
                        class="px-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider whitespace-nowrap overflow-hidden transition-all duration-300"
                        :class="isSidebarCollapsed ? 'opacity-0 max-h-0 py-0' : 'opacity-100 max-h-8 pb-2'"
                    >
                        Menu Utama
                    </div>

                    <Link
                        v-for="item in navigation.slice(0, 6)"
                        :key="item.name"
                        :href="getHref(item.routeName)"
                        :title="item.name"
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-colors duration-150 group overflow-hidden whitespace-nowrap"
                        :class="isActive(item.routeName)
                            ? 'bg-emerald-50 text-emerald-800 font-semibold'
                            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                    >
                        <component :is="item.icon" class="w-5 h-5 shrink-0" :class="isActive(item.routeName) ? 'text-emerald-600' : 'text-slate-400 group-hover:text-slate-600'" />
                        <span
                            class="transition-all duration-300 truncate"
                            :class="isSidebarCollapsed ? 'opacity-0 max-w-0 -translate-x-2' : 'opacity-100 max-w-[180px] translate-x-0'"
                        >
                            {{ item.name }}
                        </span>
                    </Link>

                    <div
                        class="px-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider whitespace-nowrap overflow-hidden transition-all duration-300"
                        :class="isSidebarCollapsed ? 'opacity-0 max-h-0 py-0' : 'opacity-100 max-h-8 pt-4 pb-2'"
                    >
                        Sistem & Integrasi
                    </div>

                    <Link
                        v-for="item in navigation.slice(6)"
                        :key="item.name"
                        :href="getHref(item.routeName)"
                        :title="item.name"
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-colors duration-150 group overflow-hidden whitespace-nowrap"
                        :class="isActive(item.routeName)
                            ? 'bg-emerald-50 text-emerald-800 font-semibold'
                            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                    >
                        <component :is="item.icon" class="w-5 h-5 shrink-0" :class="isActive(item.routeName) ? 'text-emerald-600' : 'text-slate-400 group-hover:text-slate-600'" />
                        <span
                            class="transition-all duration-300 truncate"
                            :class="isSidebarCollapsed ? 'opacity-0 max-w-0 -translate-x-2' : 'opacity-100 max-w-[180px] translate-x-0'"
                        >
                            {{ item.name }}
                        </span>
                    </Link>
                </nav>

                <!-- User profile & Logout footer -->
                <div class="h-16 border-t border-slate-100 bg-slate-50/50 px-3 flex items-center overflow-hidden shrink-0">
                    <div class="flex items-center gap-3 w-full min-w-max">
                        <Link
                            :href="route('profile.edit')"
                            :title="$page.props.auth?.user?.name"
                            class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-800 font-bold flex items-center justify-center shrink-0 text-sm hover:ring-2 hover:ring-emerald-400 transition ml-0.5"
                        >
                            {{ ($page.props.auth?.user?.name || 'U').charAt(0).toUpperCase() }}
                        </Link>
                        <div
                            class="min-w-0 flex-1 transition-all duration-300 overflow-hidden"
                            :class="isSidebarCollapsed ? 'opacity-0 max-w-0' : 'opacity-100 max-w-[130px]'"
                        >
                            <p class="text-sm font-semibold text-slate-800 truncate">{{ $page.props.auth?.user?.name }}</p>
                            <p class="text-xs text-slate-500 truncate">{{ $page.props.auth?.user?.email }}</p>
                        </div>
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            title="Keluar"
                            class="p-2 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition shrink-0"
                            :class="isSidebarCollapsed ? 'opacity-0 pointer-events-none' : 'opacity-100'"
                        >
                            <LogOut class="w-4 h-4" />
                        </Link>
                    </div>
                </div>
            </aside>

            <!-- Main Content Area -->
            <div class="flex-1 flex flex-col min-w-0 transition-[padding-left] duration-300 ease-in-out" :class="isSidebarCollapsed ? 'lg:pl-20' : 'lg:pl-64'">
                <!-- Top Navbar -->
                <header class="sticky top-0 z-20 bg-white/90 backdrop-blur-md border-b border-slate-200/80">
                    <div class="flex items-center justify-between px-3 sm:px-6 lg:px-8 h-14 sm:h-16 gap-2">
                        <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                            <!-- Mobile menu trigger -->
                            <button
                                @click="mobileMenuOpen = true"
                                class="p-2 rounded-xl text-slate-600 hover:bg-slate-100 lg:hidden shrink-0"
                                aria-label="Buka menu"
                            >
                                <Menu class="w-5 h-5" />
                            </button>

                            <!-- Desktop single sidebar toggle button -->
                            <button
                                @click="toggleSidebar"
                                class="hidden lg:flex items-center justify-center p-2 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-colors shrink-0"
                                :title="isSidebarCollapsed ? 'Buka Sidebar' : 'Tutup Sidebar'"
                                :aria-label="isSidebarCollapsed ? 'Buka Sidebar' : 'Tutup Sidebar'"
                            >
                                <component :is="isSidebarCollapsed ? PanelLeftOpen : PanelLeftClose" class="w-5 h-5 text-slate-600" />
                            </button>

                            <h1 class="text-base sm:text-lg font-bold text-slate-900 truncate">
                                <slot name="header">FinTrack</slot>
                            </h1>
                        </div>

                        <!-- Top actions -->
                        <div class="flex items-center gap-2 shrink-0">
                            <slot name="actions" />

                            <!-- User badge on mobile -->
                            <div class="relative lg:hidden shrink-0">
                                <Link
                                    :href="route('profile.edit')"
                                    class="w-8 h-8 rounded-full bg-slate-100 text-slate-700 font-bold flex items-center justify-center text-xs hover:bg-slate-200 transition"
                                >
                                    {{ ($page.props.auth?.user?.name || 'U').charAt(0).toUpperCase() }}
                                </Link>
                            </div>
                        </div>
                    </div>
                </header>

                <!-- Page Content -->
                <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto pb-[calc(env(safe-area-inset-bottom)+5.5rem)] lg:pb-8">
                    <slot />
                </main>
            </div>
        </div>

        <!-- Mobile Bottom Nav with Center Quick-Add Action -->
        <nav
            class="lg:hidden fixed bottom-0 inset-x-0 bg-white/95 backdrop-blur-md border-t border-slate-200/80 z-30 px-3 pt-1.5 pb-[calc(env(safe-area-inset-bottom)+0.35rem)] flex items-center justify-between shadow-[0_-4px_16px_rgba(0,0,0,0.04)]"
        >
            <Link
                :href="route('dashboard')"
                class="flex flex-1 flex-col items-center justify-center py-1 text-[11px] font-medium transition-colors"
                :class="isActive('dashboard') ? 'text-emerald-600 font-bold' : 'text-slate-500 hover:text-slate-900'"
            >
                <LayoutDashboard class="w-5 h-5 mb-0.5" :class="isActive('dashboard') ? 'text-emerald-600' : 'text-slate-400'" />
                <span>Dashboard</span>
            </Link>

            <Link
                :href="getHref('transactions.index')"
                class="flex flex-1 flex-col items-center justify-center py-1 text-[11px] font-medium transition-colors"
                :class="isActive('transactions.index') ? 'text-emerald-600 font-bold' : 'text-slate-500 hover:text-slate-900'"
            >
                <ArrowLeftRight class="w-5 h-5 mb-0.5" :class="isActive('transactions.index') ? 'text-emerald-600' : 'text-slate-400'" />
                <span>Transaksi</span>
            </Link>

            <!-- Center Elevated Quick-Add Button -->
            <div class="flex-1 flex justify-center -mt-6">
                <button
                    type="button"
                    @click="handleMobileQuickAdd"
                    class="w-12 h-12 rounded-full bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white flex items-center justify-center shadow-lg shadow-emerald-600/30 ring-4 ring-white transition-all"
                    aria-label="Catat Transaksi Cepat"
                    title="Catat Transaksi"
                >
                    <Plus class="w-6 h-6 stroke-[2.5]" />
                </button>
            </div>

            <Link
                :href="getHref('accounts.index')"
                class="flex flex-1 flex-col items-center justify-center py-1 text-[11px] font-medium transition-colors"
                :class="isActive('accounts.index') ? 'text-emerald-600 font-bold' : 'text-slate-500 hover:text-slate-900'"
            >
                <Wallet class="w-5 h-5 mb-0.5" :class="isActive('accounts.index') ? 'text-emerald-600' : 'text-slate-400'" />
                <span>Rekening</span>
            </Link>

            <button
                type="button"
                @click="mobileMenuOpen = true"
                class="flex flex-1 flex-col items-center justify-center py-1 text-[11px] font-medium text-slate-500 hover:text-slate-900 transition-colors"
            >
                <Menu class="w-5 h-5 mb-0.5" />
                <span>Menu</span>
            </button>
        </nav>
    </div>
</template>
