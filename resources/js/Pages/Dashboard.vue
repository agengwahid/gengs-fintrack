<script setup>
import { ref, onMounted, watch, nextTick } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DynamicIcon from '@/Components/DynamicIcon.vue';
import Chart from 'chart.js/auto';
import { formatRupiah, formatDateIndo, formatPeriodIndo } from '@/Utils/formatters';
import {
    Wallet,
    ArrowUpRight,
    ArrowDownRight,
    TrendingUp,
    TrendingDown,
    PiggyBank,
    ArrowLeftRight,
    Calendar,
    ChevronLeft,
    ChevronRight,
    ChevronRight as ChevronRightSmall,
    Plus,
    AlertCircle,
    CheckCircle2,
    PieChart,
    BarChart3
} from '@lucide/vue';

const props = defineProps({
    period: {
        type: String,
        required: true,
    },
    summary: {
        type: Object,
        required: true,
    },
    budgetSummary: {
        type: Object,
        required: true,
    },
    monthlyTrends: {
        type: Array,
        default: () => [],
    },
    categoryExpenses: {
        type: Array,
        default: () => [],
    },
    recentTransactions: {
        type: Array,
        default: () => [],
    },
    accounts: {
        type: Array,
        default: () => [],
    },
});

const barCanvas = ref(null);
const doughnutCanvas = ref(null);
let barChartInstance = null;
let doughnutChartInstance = null;

function changePeriod(newPeriod) {
    router.get(route('dashboard'), { period: newPeriod }, {
        preserveState: true,
        preserveScroll: true,
    });
}

function prevMonth() {
    const [year, month] = props.period.split('-').map(Number);
    const date = new Date(year, month - 2, 1);
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    changePeriod(`${y}-${m}`);
}

function nextMonth() {
    const [year, month] = props.period.split('-').map(Number);
    const date = new Date(year, month, 1);
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    changePeriod(`${y}-${m}`);
}

function currentMonth() {
    const now = new Date();
    const y = now.getFullYear();
    const m = String(now.getMonth() + 1).padStart(2, '0');
    changePeriod(`${y}-${m}`);
}

function renderCharts() {
    // 1. Render Bar Chart (Monthly Trends)
    if (barCanvas.value) {
        if (barChartInstance) {
            barChartInstance.destroy();
        }

        const labels = props.monthlyTrends.map(t => t.label);
        const incomeData = props.monthlyTrends.map(t => t.income);
        const expenseData = props.monthlyTrends.map(t => t.expense);

        barChartInstance = new Chart(barCanvas.value, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Pemasukan',
                        data: incomeData,
                        backgroundColor: '#10b981',
                        borderRadius: 6,
                        maxBarThickness: 28,
                    },
                    {
                        label: 'Pengeluaran',
                        data: expenseData,
                        backgroundColor: '#f43f5e',
                        borderRadius: 6,
                        maxBarThickness: 28,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            font: { family: 'Plus Jakarta Sans', size: 12, weight: '500' },
                        },
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return ` ${context.dataset.label}: ${formatRupiah(context.raw)}`;
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: 'Plus Jakarta Sans', size: 11 } },
                    },
                    y: {
                        grid: { color: '#f1f5f9' },
                        ticks: {
                            font: { family: 'Plus Jakarta Sans', size: 10 },
                            callback: function (val) {
                                if (val >= 1000000) return `${val / 1000000} jt`;
                                if (val >= 1000) return `${val / 1000} rb`;
                                return val;
                            },
                        },
                    },
                },
            },
        });
    }

    // 2. Render Doughnut Chart (Category Expenses)
    if (doughnutCanvas.value) {
        if (doughnutChartInstance) {
            doughnutChartInstance.destroy();
        }

        if (props.categoryExpenses.length > 0) {
            const labels = props.categoryExpenses.map(c => c.category_name);
            const data = props.categoryExpenses.map(c => c.total);
            const colors = props.categoryExpenses.map(c => c.color || '#10b981');

            doughnutChartInstance = new Chart(doughnutCanvas.value, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            data: data,
                            backgroundColor: colors,
                            borderWidth: 2,
                            borderColor: '#ffffff',
                            hoverOffset: 6,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                usePointStyle: true,
                                font: { family: 'Plus Jakarta Sans', size: 11 },
                            },
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? Math.round((context.raw / total) * 100) : 0;
                                    return ` ${context.label}: ${formatRupiah(context.raw)} (${percentage}%)`;
                                },
                            },
                        },
                    },
                    cutout: '68%',
                },
            });
        }
    }
}

onMounted(() => {
    nextTick(() => {
        renderCharts();
    });
});

watch(
    () => [props.monthlyTrends, props.categoryExpenses],
    () => {
        nextTick(() => {
            renderCharts();
        });
    },
    { deep: true }
);
</script>

<template>
    <Head title="Dashboard Keuangan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-2">
                <BarChart3 class="w-6 h-6 text-emerald-600" />
                <span>Dashboard Keuangan</span>
            </div>
        </template>

        <template #actions>
            <div class="flex items-center gap-2">
                <Link
                    :href="route('transactions.index')"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition"
                >
                    <Plus class="w-4 h-4" />
                    <span>Catat Transaksi</span>
                </Link>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Period Navigation Bar -->
            <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <button
                        @click="prevMonth"
                        class="p-2 rounded-xl text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition"
                        title="Bulan Sebelumnya"
                    >
                        <ChevronLeft class="w-5 h-5" />
                    </button>

                    <div class="flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-bold text-base sm:text-lg">
                        <Calendar class="w-5 h-5 text-emerald-600 shrink-0" />
                        <span>{{ formatPeriodIndo(period) }}</span>
                    </div>

                    <button
                        @click="nextMonth"
                        class="p-2 rounded-xl text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition"
                        title="Bulan Berikutnya"
                    >
                        <ChevronRight class="w-5 h-5" />
                    </button>

                    <button
                        @click="currentMonth"
                        class="ml-1 px-3 py-1.5 rounded-xl text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 transition"
                    >
                        Bulan Ini
                    </button>
                </div>

                <div class="flex items-center gap-3">
                    <Link
                        :href="route('reports.index')"
                        class="text-xs font-semibold text-slate-600 hover:text-emerald-600 transition flex items-center gap-1"
                    >
                        <span>Lihat Laporan Lengkap</span>
                        <ChevronRightSmall class="w-4 h-4" />
                    </Link>
                </div>
            </div>

            <!-- Top 4 Financial Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Balance (All Accounts) -->
                <div class="bg-gradient-to-br from-slate-900 to-slate-800 text-white rounded-2xl p-5 shadow-lg shadow-slate-900/10 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between text-slate-400 text-xs font-semibold uppercase tracking-wider">
                            <span>Total Saldo Rekening</span>
                            <div class="w-8 h-8 rounded-xl bg-white/10 text-white flex items-center justify-center">
                                <Wallet class="w-4 h-4" />
                            </div>
                        </div>
                        <div class="mt-2 text-2xl font-black tracking-tight text-white">
                            {{ formatRupiah(summary.total_balance) }}
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-white/10 flex items-center justify-between text-xs text-slate-400">
                        <span>{{ accounts.length }} Rekening Aktif</span>
                        <Link :href="route('accounts.index')" class="text-emerald-400 hover:underline font-medium">
                            Kelola
                        </Link>
                    </div>
                </div>

                <!-- Income This Month -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between text-slate-500 text-xs font-semibold uppercase tracking-wider">
                            <span>Pemasukan Bulan Ini</span>
                            <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                                <ArrowUpRight class="w-4 h-4" />
                            </div>
                        </div>
                        <div class="mt-2 text-2xl font-black text-emerald-600 tracking-tight">
                            {{ formatRupiah(summary.income) }}
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 text-xs text-slate-400">
                        Total uang masuk di {{ formatPeriodIndo(period) }}
                    </div>
                </div>

                <!-- Expense This Month -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between text-slate-500 text-xs font-semibold uppercase tracking-wider">
                            <span>Pengeluaran Bulan Ini</span>
                            <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                                <ArrowDownRight class="w-4 h-4" />
                            </div>
                        </div>
                        <div class="mt-2 text-2xl font-black text-rose-600 tracking-tight">
                            {{ formatRupiah(summary.expense) }}
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 text-xs text-slate-400">
                        Total uang keluar di {{ formatPeriodIndo(period) }}
                    </div>
                </div>

                <!-- Net Cashflow -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between text-slate-500 text-xs font-semibold uppercase tracking-wider">
                            <span>Arus Kas Bersih</span>
                            <div
                                class="w-8 h-8 rounded-xl flex items-center justify-center"
                                :class="summary.net_cashflow >= 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600'"
                            >
                                <TrendingUp v-if="summary.net_cashflow >= 0" class="w-4 h-4" />
                                <TrendingDown v-else class="w-4 h-4" />
                            </div>
                        </div>
                        <div
                            class="mt-2 text-2xl font-black tracking-tight"
                            :class="summary.net_cashflow >= 0 ? 'text-emerald-700' : 'text-rose-600'"
                        >
                            {{ formatRupiah(summary.net_cashflow) }}
                        </div>
                    </div>
                    <div
                        class="mt-4 pt-3 border-t border-slate-100 text-xs font-semibold"
                        :class="summary.net_cashflow >= 0 ? 'text-emerald-600' : 'text-rose-600'"
                    >
                        {{ summary.net_cashflow >= 0 ? 'Surplus (Pemasukan > Pengeluaran)' : 'Defisit (Pengeluaran > Pemasukan)' }}
                    </div>
                </div>
            </div>

            <!-- Budget Progress Banner -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4 min-w-0">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <PiggyBank class="w-6 h-6" />
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-bold text-slate-900">Realisasi Anggaran {{ formatPeriodIndo(period) }}</h3>
                            <span
                                v-if="budgetSummary.count > 0"
                                class="text-xs font-bold px-2 py-0.5 rounded-full"
                                :class="{
                                    'bg-emerald-100 text-emerald-700': budgetSummary.percentage < 75,
                                    'bg-amber-100 text-amber-700': budgetSummary.percentage >= 75 && budgetSummary.percentage < 90,
                                    'bg-rose-100 text-rose-700': budgetSummary.percentage >= 90,
                                }"
                            >
                                {{ budgetSummary.percentage }}% Terpakai
                            </span>
                        </div>
                        <p v-if="budgetSummary.count > 0" class="text-xs text-slate-500 mt-1">
                            Terpakai <span class="font-bold text-slate-800">{{ formatRupiah(budgetSummary.total_spent) }}</span> dari target <span class="font-bold text-slate-800">{{ formatRupiah(budgetSummary.total_budget) }}</span> ({{ budgetSummary.count }} kategori).
                        </p>
                        <p v-else class="text-xs text-slate-400 mt-1">
                            Belum ada anggaran yang ditetapkan untuk periode ini.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    <Link
                        :href="route('budgets.index')"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 transition inline-flex items-center gap-1.5"
                    >
                        <span>{{ budgetSummary.count > 0 ? 'Detail Anggaran' : 'Atur Anggaran' }}</span>
                        <ChevronRightSmall class="w-4 h-4" />
                    </Link>
                </div>
            </div>

            <!-- Charts Section (Grid: 2 cols on desktop) -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Bar Chart: Monthly Trends (6 Months) -->
                <div class="lg:col-span-2 bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/80 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Tren Pemasukan vs Pengeluaran</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Perbandingan arus kas selama 6 bulan terakhir</p>
                        </div>
                        <div class="flex items-center gap-3 text-xs font-medium">
                            <span class="flex items-center gap-1 text-slate-600">
                                <span class="w-3 h-3 rounded-sm bg-emerald-500 inline-block"></span> Masuk
                            </span>
                            <span class="flex items-center gap-1 text-slate-600">
                                <span class="w-3 h-3 rounded-sm bg-rose-500 inline-block"></span> Keluar
                            </span>
                        </div>
                    </div>

                    <div class="relative h-64 sm:h-72 w-full">
                        <canvas ref="barCanvas"></canvas>
                    </div>
                </div>

                <!-- Doughnut Chart: Expenses by Category -->
                <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/80 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Pengeluaran per Kategori</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Proporsi pengeluaran di {{ formatPeriodIndo(period) }}</p>
                        </div>
                        <PieChart class="w-5 h-5 text-slate-400" />
                    </div>

                    <div v-if="categoryExpenses.length > 0" class="relative h-64 sm:h-72 w-full flex items-center justify-center">
                        <canvas ref="doughnutCanvas"></canvas>
                    </div>

                    <div v-else class="h-64 flex flex-col items-center justify-center text-center p-4">
                        <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center mb-2">
                            <PieChart class="w-6 h-6" />
                        </div>
                        <p class="text-xs text-slate-500 font-medium">Belum ada pengeluaran</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Transaksi pengeluaran di bulan ini akan muncul di sini.</p>
                    </div>
                </div>
            </div>

            <!-- Bottom Section: Recent Transactions & Account Balances -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Recent 5 Transactions (2 cols on large) -->
                <div class="lg:col-span-2 bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/80 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Transaksi Terbaru</h3>
                            <p class="text-xs text-slate-400 mt-0.5">5 catatan transaksi finansial terakhir Anda</p>
                        </div>
                        <Link
                            :href="route('transactions.index')"
                            class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 transition flex items-center gap-1"
                        >
                            <span>Lihat Semua</span>
                            <ChevronRightSmall class="w-4 h-4" />
                        </Link>
                    </div>

                    <div v-if="recentTransactions.length > 0" class="divide-y divide-slate-100">
                        <div
                            v-for="t in recentTransactions"
                            :key="t.id"
                            class="py-3 flex items-center justify-between gap-3 hover:bg-slate-50/60 rounded-xl px-2 -mx-2 transition"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <div
                                    class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 shadow-sm"
                                    :style="{
                                        backgroundColor: (t.category?.color || (t.type === 'income' ? '#10b981' : '#f43f5e')) + '15',
                                        color: t.category?.color || (t.type === 'income' ? '#10b981' : '#f43f5e')
                                    }"
                                >
                                    <DynamicIcon :name="t.category?.icon || (t.type === 'income' ? 'ArrowUpRight' : 'ArrowDownRight')" class="w-5 h-5" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-slate-800 truncate">
                                        {{ t.notes || t.category?.name || (t.type === 'transfer' ? 'Transfer Antar Rekening' : 'Transaksi') }}
                                    </p>
                                    <div class="flex items-center gap-2 text-xs text-slate-400 mt-0.5">
                                        <span>{{ formatDateIndo(t.date) }}</span>
                                        <span>&bull;</span>
                                        <span class="truncate">{{ t.account?.name }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="text-right shrink-0">
                                <div
                                    class="text-sm font-black"
                                    :class="{
                                        'text-emerald-600': t.type === 'income',
                                        'text-rose-600': t.type === 'expense',
                                        'text-slate-800': t.type === 'transfer'
                                    }"
                                >
                                    {{ t.type === 'income' ? '+' : (t.type === 'expense' ? '-' : '') }}{{ formatRupiah(t.amount) }}
                                </div>
                                <div class="text-[11px] text-slate-400 font-medium">
                                    {{ t.type === 'income' ? 'Pemasukan' : (t.type === 'expense' ? 'Pengeluaran' : 'Transfer') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="py-10 text-center text-slate-400 text-xs">
                        Belum ada transaksi tercatat.
                    </div>
                </div>

                <!-- Account Balances Summary (1 col on large) -->
                <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/80 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Rekening Saya</h3>
                                <p class="text-xs text-slate-400 mt-0.5">Saldo riil di setiap rekening</p>
                            </div>
                            <Link
                                :href="route('accounts.index')"
                                class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 transition"
                            >
                                Kelola
                            </Link>
                        </div>

                        <div class="space-y-3">
                            <div
                                v-for="acc in accounts"
                                :key="acc.id"
                                class="p-3 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between"
                            >
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div
                                        class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
                                        :style="{ backgroundColor: (acc.color || '#2563eb') + '15', color: acc.color || '#2563eb' }"
                                    >
                                        <DynamicIcon :name="acc.icon || 'Wallet'" class="w-5 h-5" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold text-slate-800 truncate">{{ acc.name }}</p>
                                        <p class="text-[10px] text-slate-400 uppercase tracking-wider">{{ acc.type }}</p>
                                    </div>
                                </div>

                                <div class="text-right shrink-0">
                                    <span class="text-xs font-bold text-slate-900">{{ formatRupiah(acc.current_balance) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-slate-100">
                        <Link
                            :href="route('accounts.index')"
                            class="w-full flex items-center justify-center gap-1.5 py-2 px-3 rounded-xl text-xs font-semibold text-slate-600 bg-slate-50 hover:bg-slate-100 border border-slate-200/80 transition"
                        >
                            <Wallet class="w-3.5 h-3.5" />
                            <span>Lihat Semua Rekening</span>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
