<script setup>
import { ref, onMounted, watch, nextTick } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DynamicIcon from '@/Components/DynamicIcon.vue';
import Chart from 'chart.js/auto';
import { formatRupiah, formatDateIndo, formatPeriodIndo } from '@/Utils/formatters';
import {
    BarChart3,
    Download,
    Filter,
    Calendar,
    ArrowUpRight,
    ArrowDownRight,
    TrendingUp,
    TrendingDown,
    Wallet,
    Layers,
    RotateCcw,
    ChevronLeft,
    ChevronRight,
    PieChart,
    FileSpreadsheet
} from '@lucide/vue';

const props = defineProps({
    filters: {
        type: Object,
        required: true,
    },
    summary: {
        type: Object,
        required: true,
    },
    categoryBreakdown: {
        type: Array,
        default: () => [],
    },
    accountBreakdown: {
        type: Array,
        default: () => [],
    },
    trendData: {
        type: Array,
        default: () => [],
    },
    transactions: {
        type: Object,
        required: true,
    },
    accounts: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Array,
        default: () => [],
    },
});

const filterForm = ref({
    period_type: props.filters.period_type || 'monthly',
    month: props.filters.month || '',
    date: props.filters.date || '',
    year: props.filters.year || new Date().getFullYear().toString(),
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
    account_id: props.filters.account_id || '',
    category_id: props.filters.category_id || '',
    type: props.filters.type || '',
});

const trendCanvas = ref(null);
let trendChartInstance = null;

function applyFilters() {
    router.get(route('reports.index'), filterForm.value, {
        preserveState: true,
        preserveScroll: true,
    });
}

function resetFilters() {
    const today = new Date();
    const currentMonthStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}`;
    filterForm.value = {
        period_type: 'monthly',
        month: currentMonthStr,
        date: '',
        year: today.getFullYear().toString(),
        start_date: '',
        end_date: '',
        account_id: '',
        category_id: '',
        type: '',
    };
    applyFilters();
}

function exportCsvUrl() {
    const params = new URLSearchParams();
    params.append('start_date', props.filters.start_date);
    params.append('end_date', props.filters.end_date);
    if (props.filters.account_id) params.append('account_id', props.filters.account_id);
    if (props.filters.category_id) params.append('category_id', props.filters.category_id);
    if (props.filters.type) params.append('type', props.filters.type);
    return `${route('reports.export-csv')}?${params.toString()}`;
}

function renderTrendChart() {
    if (!trendCanvas.value) return;

    if (trendChartInstance) {
        trendChartInstance.destroy();
    }

    if (props.trendData.length === 0) return;

    const labels = props.trendData.map(d => formatDateIndo(d.date));
    const incomeData = props.trendData.map(d => d.income);
    const expenseData = props.trendData.map(d => d.expense);

    trendChartInstance = new Chart(trendCanvas.value, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Pemasukan',
                    data: incomeData,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                },
                {
                    label: 'Pengeluaran',
                    data: expenseData,
                    borderColor: '#f43f5e',
                    backgroundColor: 'rgba(244, 63, 94, 0.1)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 4,
                    pointHoverRadius: 6,
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
                    ticks: { font: { family: 'Plus Jakarta Sans', size: 10 } },
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

onMounted(() => {
    nextTick(() => {
        renderTrendChart();
    });
});

watch(
    () => props.trendData,
    () => {
        nextTick(() => {
            renderTrendChart();
        });
    },
    { deep: true }
);
</script>

<template>
    <Head title="Laporan Keuangan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-2">
                <BarChart3 class="w-6 h-6 text-emerald-600" />
                <span>Laporan Keuangan</span>
            </div>
        </template>

        <template #actions>
            <a
                :href="exportCsvUrl()"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 shadow-md shadow-emerald-600/20 transition"
                title="Download CSV"
            >
                <Download class="w-4 h-4" />
                <span>Ekspor CSV</span>
            </a>
        </template>

        <div class="space-y-6">
            <!-- Filter Bar Card -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm space-y-4">
                <!-- Period Type Segmented Controls -->
                <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-1.5 p-1 bg-slate-100 rounded-xl">
                        <button
                            type="button"
                            @click="filterForm.period_type = 'monthly'; applyFilters()"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition"
                            :class="filterForm.period_type === 'monthly' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                        >
                            Bulanan
                        </button>
                        <button
                            type="button"
                            @click="filterForm.period_type = 'daily'; applyFilters()"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition"
                            :class="filterForm.period_type === 'daily' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                        >
                            Harian
                        </button>
                        <button
                            type="button"
                            @click="filterForm.period_type = 'yearly'; applyFilters()"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition"
                            :class="filterForm.period_type === 'yearly' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                        >
                            Tahunan
                        </button>
                        <button
                            type="button"
                            @click="filterForm.period_type = 'custom'"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition"
                            :class="filterForm.period_type === 'custom' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                        >
                            Kustom
                        </button>
                    </div>

                    <div class="text-xs text-slate-500 font-medium">
                        Rentang: <span class="font-bold text-slate-800">{{ formatDateIndo(filters.start_date) }}</span> s/d <span class="font-bold text-slate-800">{{ formatDateIndo(filters.end_date) }}</span>
                    </div>
                </div>

                <!-- Input Controls Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <!-- Period Specific Input -->
                    <div v-if="filterForm.period_type === 'monthly'">
                        <label class="block text-xs font-bold text-slate-600 mb-1">Pilih Bulan</label>
                        <input
                            type="month"
                            v-model="filterForm.month"
                            @change="applyFilters"
                            class="w-full rounded-xl border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500"
                        />
                    </div>

                    <div v-else-if="filterForm.period_type === 'daily'">
                        <label class="block text-xs font-bold text-slate-600 mb-1">Pilih Tanggal</label>
                        <input
                            type="date"
                            v-model="filterForm.date"
                            @change="applyFilters"
                            class="w-full rounded-xl border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500"
                        />
                    </div>

                    <div v-else-if="filterForm.period_type === 'yearly'">
                        <label class="block text-xs font-bold text-slate-600 mb-1">Pilih Tahun</label>
                        <input
                            type="number"
                            min="2020"
                            max="2035"
                            v-model="filterForm.year"
                            @change="applyFilters"
                            class="w-full rounded-xl border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500"
                        />
                    </div>

                    <template v-else-if="filterForm.period_type === 'custom'">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Dari Tanggal</label>
                            <input
                                type="date"
                                v-model="filterForm.start_date"
                                class="w-full rounded-xl border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Sampai Tanggal</label>
                            <input
                                type="date"
                                v-model="filterForm.end_date"
                                class="w-full rounded-xl border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500"
                            />
                        </div>
                    </template>

                    <!-- Type Filter -->
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Tipe Transaksi</label>
                        <select
                            v-model="filterForm.type"
                            @change="applyFilters"
                            class="w-full rounded-xl border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500"
                        >
                            <option value="">Semua Tipe</option>
                            <option value="income">Pemasukan Saja</option>
                            <option value="expense">Pengeluaran Saja</option>
                            <option value="transfer">Transfer Saja</option>
                        </select>
                    </div>

                    <!-- Account Filter -->
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Rekening</label>
                        <select
                            v-model="filterForm.account_id"
                            @change="applyFilters"
                            class="w-full rounded-xl border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500"
                        >
                            <option value="">Semua Rekening</option>
                            <option v-for="acc in accounts" :key="acc.id" :value="acc.id">
                                {{ acc.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Category Filter -->
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Kategori</label>
                        <select
                            v-model="filterForm.category_id"
                            @change="applyFilters"
                            class="w-full rounded-xl border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500"
                        >
                            <option value="">Semua Kategori</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                {{ cat.name }} ({{ cat.type === 'income' ? 'Masuk' : 'Keluar' }})
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Action buttons -->
                <div class="flex items-center justify-end gap-2 pt-2">
                    <button
                        type="button"
                        @click="resetFilters"
                        class="px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition inline-flex items-center gap-1.5"
                    >
                        <RotateCcw class="w-3.5 h-3.5" />
                        <span>Reset Filter</span>
                    </button>
                    <button
                        type="button"
                        @click="applyFilters"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 transition inline-flex items-center gap-1.5"
                    >
                        <Filter class="w-3.5 h-3.5" />
                        <span>Terapkan Filter</span>
                    </button>
                </div>
            </div>

            <!-- Summary KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm">
                    <div class="flex items-center justify-between text-slate-500 text-xs font-semibold uppercase tracking-wider">
                        <span>Total Pemasukan</span>
                        <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <ArrowUpRight class="w-4 h-4" />
                        </div>
                    </div>
                    <div class="mt-2 text-2xl font-black text-emerald-600 tracking-tight">
                        {{ formatRupiah(summary.total_income) }}
                    </div>
                    <div class="mt-1 text-xs text-slate-400">Total uang masuk</div>
                </div>

                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm">
                    <div class="flex items-center justify-between text-slate-500 text-xs font-semibold uppercase tracking-wider">
                        <span>Total Pengeluaran</span>
                        <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                            <ArrowDownRight class="w-4 h-4" />
                        </div>
                    </div>
                    <div class="mt-2 text-2xl font-black text-rose-600 tracking-tight">
                        {{ formatRupiah(summary.total_expense) }}
                    </div>
                    <div class="mt-1 text-xs text-slate-400">Total uang keluar</div>
                </div>

                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm">
                    <div class="flex items-center justify-between text-slate-500 text-xs font-semibold uppercase tracking-wider">
                        <span>Selisih Arus Kas</span>
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
                    <div
                        class="mt-1 text-xs font-semibold"
                        :class="summary.net_cashflow >= 0 ? 'text-emerald-600' : 'text-rose-600'"
                    >
                        {{ summary.net_cashflow >= 0 ? 'Surplus Finansial' : 'Defisit Pengeluaran' }}
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm">
                    <div class="flex items-center justify-between text-slate-500 text-xs font-semibold uppercase tracking-wider">
                        <span>Jumlah Transaksi</span>
                        <div class="w-8 h-8 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center">
                            <Layers class="w-4 h-4" />
                        </div>
                    </div>
                    <div class="mt-2 text-2xl font-black text-slate-900 tracking-tight">
                        {{ summary.transaction_count }}
                    </div>
                    <div class="mt-1 text-xs text-slate-400">Tercatat dalam periode ini</div>
                </div>
            </div>

            <!-- Trend Chart -->
            <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/80 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Grafik Tren Arus Kas</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Dinamika pemasukan vs pengeluaran harian</p>
                    </div>
                    <div class="flex items-center gap-3 text-xs font-medium">
                        <span class="flex items-center gap-1 text-slate-600">
                            <span class="w-3 h-3 rounded-full bg-emerald-500 inline-block"></span> Pemasukan
                        </span>
                        <span class="flex items-center gap-1 text-slate-600">
                            <span class="w-3 h-3 rounded-full bg-rose-500 inline-block"></span> Pengeluaran
                        </span>
                    </div>
                </div>

                <div v-if="trendData.length > 0" class="relative h-64 sm:h-72 w-full">
                    <canvas ref="trendCanvas"></canvas>
                </div>
                <div v-else class="h-48 flex items-center justify-center text-xs text-slate-400">
                    Tidak ada aktivitas transaksi pada rentang periode yang dipilih.
                </div>
            </div>

            <!-- Category & Account Breakdowns (Grid: 2 Cols) -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Category Breakdown -->
                <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/80 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Rincian Pengeluaran per Kategori</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Distribusi pengeluaran berdasarkan pos anggaran</p>
                        </div>
                        <PieChart class="w-5 h-5 text-slate-400" />
                    </div>

                    <div v-if="categoryBreakdown.length > 0" class="space-y-4">
                        <div v-for="cat in categoryBreakdown" :key="cat.category_id" class="space-y-1.5">
                            <div class="flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2 min-w-0">
                                    <div
                                        class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0"
                                        :style="{ backgroundColor: cat.color + '15', color: cat.color }"
                                    >
                                        <DynamicIcon :name="cat.icon" class="w-4 h-4" />
                                    </div>
                                    <span class="font-bold text-slate-800 truncate">{{ cat.name }}</span>
                                    <span class="text-slate-400">({{ cat.count }})</span>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="font-black text-slate-900">{{ formatRupiah(cat.total) }}</span>
                                    <span class="text-slate-400 font-semibold ml-1.5">{{ cat.percentage }}%</span>
                                </div>
                            </div>

                            <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                <div
                                    class="h-2 rounded-full transition-all duration-500"
                                    :style="{ width: `${cat.percentage}%`, backgroundColor: cat.color }"
                                ></div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="py-10 text-center text-xs text-slate-400">
                        Tidak ada pengeluaran pada periode ini.
                    </div>
                </div>

                <!-- Account Breakdown -->
                <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/80 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Aktivitas Kas per Rekening</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Arus masuk dan keluar di setiap rekening</p>
                        </div>
                        <Wallet class="w-5 h-5 text-slate-400" />
                    </div>

                    <div v-if="accountBreakdown.length > 0" class="divide-y divide-slate-100">
                        <div v-for="acc in accountBreakdown" :key="acc.id" class="py-3 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div
                                    class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                                    :style="{ backgroundColor: acc.color + '15', color: acc.color }"
                                >
                                    <DynamicIcon :name="acc.icon || 'Wallet'" class="w-5 h-5" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-slate-800 truncate">{{ acc.name }}</p>
                                    <p class="text-xs text-slate-400">Saldo saat ini: {{ formatRupiah(acc.current_balance) }}</p>
                                </div>
                            </div>

                            <div class="text-right shrink-0 text-xs space-y-0.5">
                                <div class="text-emerald-600 font-medium">+{{ formatRupiah(acc.incoming) }}</div>
                                <div class="text-rose-600 font-medium">-{{ formatRupiah(acc.outgoing) }}</div>
                                <div class="font-bold text-slate-800 pt-0.5 border-t border-slate-100">
                                    Net: {{ formatRupiah(acc.net) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="py-10 text-center text-xs text-slate-400">
                        Tidak ada rekening aktif.
                    </div>
                </div>
            </div>

            <!-- Detailed Transactions Table -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Rincian Riwayat Transaksi</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Seluruh transaksi yang terfilter dalam laporan ini</p>
                    </div>
                    <span class="text-xs text-slate-500 font-semibold">
                        Menampilkan {{ transactions.data?.length || 0 }} dari {{ transactions.total || 0 }} data
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50/70 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-100">
                            <tr>
                                <th class="py-3 px-4">Tanggal</th>
                                <th class="py-3 px-4">Tipe</th>
                                <th class="py-3 px-4">Kategori / Rekening</th>
                                <th class="py-3 px-4">Deskripsi / Catatan</th>
                                <th class="py-3 px-4 text-right">Nominal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr v-for="t in transactions.data" :key="t.id" class="hover:bg-slate-50/60 transition">
                                <td class="py-3 px-4 font-medium text-slate-800 whitespace-nowrap">
                                    {{ formatDateIndo(t.date) }}
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <span
                                        class="inline-block px-2 py-0.5 rounded-full text-[11px] font-bold"
                                        :class="{
                                            'bg-emerald-100 text-emerald-700': t.type === 'income',
                                            'bg-rose-100 text-rose-700': t.type === 'expense',
                                            'bg-slate-100 text-slate-700': t.type === 'transfer',
                                        }"
                                    >
                                        {{ t.type === 'income' ? 'Pemasukan' : (t.type === 'expense' ? 'Pengeluaran' : 'Transfer') }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-800">{{ t.category?.name || '-' }}</div>
                                    <div class="text-[11px] text-slate-400">
                                        {{ t.account?.name }}
                                        <span v-if="t.destination_account"> &rarr; {{ t.destination_account?.name }}</span>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <p class="truncate max-w-xs">{{ t.notes || '-' }}</p>
                                    <p v-if="t.reference_number" class="text-[10px] text-slate-400">Ref: {{ t.reference_number }}</p>
                                </td>
                                <td class="py-3 px-4 text-right font-black whitespace-nowrap"
                                    :class="{
                                        'text-emerald-600': t.type === 'income',
                                        'text-rose-600': t.type === 'expense',
                                        'text-slate-800': t.type === 'transfer',
                                    }"
                                >
                                    {{ t.type === 'income' ? '+' : (t.type === 'expense' ? '-' : '') }}{{ formatRupiah(t.amount) }}
                                </td>
                            </tr>
                            <tr v-if="!transactions.data || transactions.data.length === 0">
                                <td colspan="5" class="py-8 text-center text-slate-400">
                                    Tidak ada transaksi yang cocok dengan kriteria filter laporan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div v-if="transactions.links && transactions.links.length > 3" class="p-4 border-t border-slate-100 flex items-center justify-between">
                    <div class="text-xs text-slate-500">
                        Halaman {{ transactions.current_page }} dari {{ transactions.last_page }}
                    </div>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="(link, i) in transactions.links"
                            :key="i"
                            :href="link.url || '#'"
                            class="px-2.5 py-1 rounded-lg text-xs font-semibold transition"
                            :class="{
                                'bg-emerald-600 text-white': link.active,
                                'text-slate-600 hover:bg-slate-100': !link.active && link.url,
                                'text-slate-300 cursor-not-allowed': !link.url
                            }"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
