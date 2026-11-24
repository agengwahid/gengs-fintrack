<script setup>
import { ref, onMounted, watch, nextTick } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DynamicIcon from '@/Components/DynamicIcon.vue';
import HeaderActionButton from '@/Components/HeaderActionButton.vue';
import MonthYearPicker from '@/Components/MonthYearPicker.vue';
import Chart from 'chart.js/auto';
import { formatRupiah, formatDateIndo, formatPeriodIndo, formatTransactionDate } from '@/Utils/formatters';
import {
    Wallet,
    ArrowUpRight,
    ArrowDownRight,
    TrendingUp,
    TrendingDown,
    Target,
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
            <span>Dashboard</span>
        </template>

        <template #actions>
            <HeaderActionButton
                as="link"
                :href="route('transactions.index')"
                label="Catat Transaksi"
                short-label="Catat"
                :icon="Plus"
                class="hidden sm:inline-flex"
            />
        </template>

        <div class="space-y-5">
            <!-- Period Navigation Bar -->
            <div class="bg-white rounded-2xl p-3 sm:p-4 border border-slate-200/80 shadow-sm flex items-center justify-between gap-3">
                <MonthYearPicker
                    :model-value="period"
                    @change="changePeriod"
                    variant="inline"
                />

                <Link
                    :href="route('reports.index')"
                    class="hidden sm:flex text-xs font-semibold text-slate-500 hover:text-emerald-700 transition items-center gap-1"
                >
                    <span>Laporan</span>
                    <ChevronRight class="w-4 h-4" />
                </Link>
            </div>

            <!-- Financial Overview: Total Balance & Cashflow -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm">
                <!-- Top Row: Balance Header -->
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Saldo Rekening</span>
                    <Link :href="route('accounts.index')" class="text-xs font-semibold text-emerald-700 hover:underline">
                        {{ accounts.length }} Rekening &rarr;
                    </Link>
                </div>
                <div class="mt-2 text-3xl sm:text-4xl font-extrabold font-mono tracking-tight text-slate-900">
                    {{ formatRupiah(summary.total_balance) }}
                </div>

                <!-- 3-Column Cashflow Bar -->
                <div class="mt-5 pt-4 border-t border-slate-100 grid grid-cols-3 gap-2">
                    <div>
                        <div class="text-[11px] font-medium text-slate-400">Pemasukan</div>
                        <div class="text-sm sm:text-base font-bold font-mono text-emerald-700 truncate mt-0.5">
                            +{{ formatRupiah(summary.income) }}
                        </div>
                    </div>
                    <div>
                        <div class="text-[11px] font-medium text-slate-400">Pengeluaran</div>
                        <div class="text-sm sm:text-base font-bold font-mono text-rose-700 truncate mt-0.5">
                            -{{ formatRupiah(summary.expense) }}
                        </div>
                    </div>
                    <div>
                        <div class="text-[11px] font-medium text-slate-400">Arus Kas</div>
                        <div
                            class="text-sm sm:text-base font-bold font-mono truncate mt-0.5"
                            :class="summary.net_cashflow >= 0 ? 'text-slate-900' : 'text-rose-700'"
                        >
                            {{ formatRupiah(summary.net_cashflow) }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Budget Widget -->
            <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-sm">
                <div class="flex items-center justify-between mb-2.5">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-slate-800">Anggaran {{ formatPeriodIndo(period) }}</span>
                        <span
                            v-if="budgetSummary.count > 0"
                            class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                            :class="{
                                'bg-emerald-50 text-emerald-700': budgetSummary.percentage < 80,
                                'bg-amber-50 text-amber-700': budgetSummary.percentage >= 80 && budgetSummary.percentage <= 100,
                                'bg-rose-50 text-rose-700': budgetSummary.percentage > 100,
                            }"
                        >
                            {{ budgetSummary.percentage }}%
                        </span>
                    </div>
                    <Link :href="route('budgets.index')" class="text-xs font-semibold text-emerald-700 hover:underline flex items-center gap-0.5">
                        <span>Kelola</span>
                        <ChevronRightSmall class="w-3.5 h-3.5" />
                    </Link>
                </div>

                <div v-if="budgetSummary.count > 0">
                    <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                        <div
                            class="h-full rounded-full transition-all duration-500"
                            :class="{
                                'bg-emerald-500': budgetSummary.percentage < 80,
                                'bg-amber-500': budgetSummary.percentage >= 80 && budgetSummary.percentage <= 100,
                                'bg-rose-500': budgetSummary.percentage > 100,
                            }"
                            :style="{ width: `${Math.min(budgetSummary.percentage, 100)}%` }"
                        ></div>
                    </div>
                    <div class="flex items-center justify-between text-xs font-mono text-slate-600 mt-2">
                        <span>{{ formatRupiah(budgetSummary.total_spent) }}</span>
                        <span class="text-slate-400">dari {{ formatRupiah(budgetSummary.total_budget) }}</span>
                    </div>
                </div>
                <div v-else class="text-xs text-slate-400 py-1">
                    <Link :href="route('budgets.index')" class="text-emerald-700 font-semibold hover:underline">
                        + Pasang anggaran bulan ini
                    </Link>
                </div>
            </div>

            <!-- Charts Section (Grid: 2 cols on desktop) -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Bar Chart: Monthly Trends (6 Months) -->
                <div class="lg:col-span-2 bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/80 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-bold text-slate-900">Tren Pemasukan vs Pengeluaran</h3>
                        <div class="flex items-center gap-3 text-xs font-medium">
                            <span class="flex items-center gap-1 text-slate-600">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block"></span> Masuk
                            </span>
                            <span class="flex items-center gap-1 text-slate-600">
                                <span class="w-2.5 h-2.5 rounded-full bg-rose-500 inline-block"></span> Keluar
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
                        <h3 class="text-base font-bold text-slate-900">Pengeluaran per Kategori</h3>
                        <PieChart class="w-4 h-4 text-slate-400" />
                    </div>

                    <div v-if="categoryExpenses.length > 0" class="relative h-64 sm:h-72 w-full flex items-center justify-center">
                        <canvas ref="doughnutCanvas"></canvas>
                    </div>

                    <div v-else class="h-64 flex flex-col items-center justify-center text-center p-4">
                        <p class="text-xs text-slate-400">Belum ada pengeluaran</p>
                    </div>
                </div>
            </div>

            <!-- Bottom Section: Recent Transactions & Account Balances -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Recent 5 Transactions (2 cols on large) -->
                <div class="lg:col-span-2 bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/80 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-bold text-slate-900">Transaksi Terbaru</h3>
                        <Link
                            :href="route('transactions.index')"
                            class="text-xs font-semibold text-emerald-700 hover:underline flex items-center gap-1"
                        >
                            <span>Semua</span>
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
                                        <span>{{ formatTransactionDate(t.date, t.time, t.created_at) }}</span>
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
                            <h3 class="text-base font-bold text-slate-900">Rekening Saya</h3>
                            <Link
                                :href="route('accounts.index')"
                                class="text-xs font-semibold text-emerald-700 hover:underline transition"
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
