<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import HeaderActionButton from '@/Components/HeaderActionButton.vue';
import MonthYearPicker from '@/Components/MonthYearPicker.vue';
import DynamicIcon from '@/Components/DynamicIcon.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import MoneyInput from '@/Components/MoneyInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { formatRupiah, formatDateIndo, formatPeriodIndo, formatTransactionDate, formatTime } from '@/Utils/formatters';
import {
    ArrowLeftRight,
    Plus,
    TrendingDown,
    TrendingUp,
    Search,
    Filter,
    Calendar,
    Wallet,
    Tags,
    Pencil,
    Trash2,
    RotateCcw,
    AlertTriangle,
    ArrowRight
} from '@lucide/vue';

const props = defineProps({
    transactions: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    summary: {
        type: Object,
        default: () => ({
            total_income: 0,
            total_expense: 0,
            net_cashflow: 0,
        }),
    },
    accounts: {
        type: Array,
        default: () => [],
    },
    expenseCategories: {
        type: Array,
        default: () => [],
    },
    incomeCategories: {
        type: Array,
        default: () => [],
    },
});

// Filters state
const filterForm = ref({
    search: props.filters.search || '',
    type: props.filters.type || '',
    account_id: props.filters.account_id || '',
    category_id: props.filters.category_id || '',
    month: props.filters.month || new Date().toISOString().slice(0, 7),
});

function applyFilters() {
    router.get(route('transactions.index'), filterForm.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function onMonthChange(newMonth) {
    filterForm.value.month = newMonth;
    applyFilters();
}

function resetFilters() {
    filterForm.value = {
        search: '',
        type: '',
        account_id: '',
        category_id: '',
        month: new Date().toISOString().slice(0, 7),
    };
    applyFilters();
}

const isAnyFilterActive = computed(() => {
    const currentMonth = new Date().toISOString().slice(0, 7);
    return Boolean(
        filterForm.value.search ||
        filterForm.value.type ||
        filterForm.value.account_id ||
        filterForm.value.category_id ||
        filterForm.value.month !== currentMonth
    );
});

// Transaction Modal State
const isModalOpen = ref(false);
const isEditing = ref(false);
const editingTransactionId = ref(null);
const isDeleteModalOpen = ref(false);
const transactionToDelete = ref(null);

const today = new Date().toISOString().slice(0, 10);

function getCurrentTime() {
    const now = new Date();
    return `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
}

const form = useForm({
    type: 'expense',
    amount: '',
    date: today,
    time: getCurrentTime(),
    account_id: '',
    destination_account_id: '',
    category_id: '',
    notes: '',
    reference_number: '',
});

// Default active accounts
const activeAccounts = computed(() => {
    return props.accounts.filter(a => a.is_active);
});

// Destination accounts (exclude selected source account)
const availableDestAccounts = computed(() => {
    return activeAccounts.value.filter(a => a.id !== form.account_id);
});

function openCreateModal(defaultType = 'expense') {
    isEditing.value = false;
    editingTransactionId.value = null;
    form.reset();
    form.clearErrors();
    form.type = defaultType;
    form.date = today;
    form.time = getCurrentTime();
    form.amount = '';

    // Set sensible default account if available
    if (activeAccounts.value.length > 0) {
        form.account_id = activeAccounts.value[0].id;
        if (activeAccounts.value.length > 1) {
            form.destination_account_id = activeAccounts.value[1].id;
        }
    }

    // Set sensible default category
    if (defaultType === 'expense' && props.expenseCategories.length > 0) {
        form.category_id = props.expenseCategories[0].id;
    } else if (defaultType === 'income' && props.incomeCategories.length > 0) {
        form.category_id = props.incomeCategories[0].id;
    }

    isModalOpen.value = true;
}

function openEditModal(tx) {
    isEditing.value = true;
    editingTransactionId.value = tx.id;
    form.clearErrors();
    form.type = tx.type;
    form.amount = tx.amount;
    form.date = tx.date;
    form.time = tx.time ? String(tx.time).slice(0, 5) : (tx.created_at ? formatTime(tx.created_at, false) : getCurrentTime());
    form.account_id = tx.account_id;
    form.destination_account_id = tx.destination_account_id || '';
    form.category_id = tx.category_id || '';
    form.notes = tx.notes || '';
    form.reference_number = tx.reference_number || '';
    isModalOpen.value = true;
}

function closeModal() {
    isModalOpen.value = false;
    form.reset();
    form.clearErrors();
}

function setCurrentTime() {
    form.time = getCurrentTime();
}

function setTransactionType(type) {
    form.type = type;
    form.clearErrors();

    if (type === 'expense' && props.expenseCategories.length > 0) {
        form.category_id = props.expenseCategories[0].id;
        form.destination_account_id = '';
    } else if (type === 'income' && props.incomeCategories.length > 0) {
        form.category_id = props.incomeCategories[0].id;
        form.destination_account_id = '';
    } else if (type === 'transfer') {
        form.category_id = '';
        if (activeAccounts.value.length > 1 && !form.destination_account_id) {
            form.destination_account_id = availableDestAccounts.value[0]?.id || '';
        }
    }
}

const keepOpenAfterSave = ref(false);

function submitForm(continueAdding = false) {
    keepOpenAfterSave.value = continueAdding;
    if (isEditing.value) {
        form.put(route('transactions.update', editingTransactionId.value), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('transactions.store'), {
            preserveScroll: true,
            onSuccess: () => {
                if (keepOpenAfterSave.value) {
                    form.amount = '';
                    form.notes = '';
                    form.reference_number = '';
                    form.time = getCurrentTime();
                    form.clearErrors();
                } else {
                    closeModal();
                }
            },
        });
    }
}

function handleCustomOpen() {
    openCreateModal('expense');
}

onMounted(() => {
    window.addEventListener('fintrack:open-create-transaction', handleCustomOpen);
    if (typeof window !== 'undefined') {
        const params = new URLSearchParams(window.location.search);
        if (params.get('create') === '1') {
            openCreateModal('expense');
            const cleanUrl = new URL(window.location.href);
            cleanUrl.searchParams.delete('create');
            window.history.replaceState({}, '', cleanUrl.toString());
        }
    }
});

onUnmounted(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('fintrack:open-create-transaction', handleCustomOpen);
    }
});

function confirmDelete(tx) {
    transactionToDelete.value = tx;
    isDeleteModalOpen.value = true;
}

function closeDeleteModal() {
    isDeleteModalOpen.value = false;
    transactionToDelete.value = null;
}

function executeDelete() {
    if (!transactionToDelete.value) return;

    router.delete(route('transactions.destroy', transactionToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => closeDeleteModal(),
    });
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Catatan Transaksi" />

        <template #header>
            <div class="flex items-center gap-2">
                <ArrowLeftRight class="w-6 h-6 text-emerald-600" />
                <span>Transaksi</span>
            </div>
        </template>

        <template #actions>
            <HeaderActionButton
                @click="openCreateModal('expense')"
                label="Catat Transaksi"
                short-label="Catat"
                :icon="Plus"
            />
        </template>

        <div class="space-y-6">
            <!-- Period Navigation Bar -->
            <div class="bg-white rounded-2xl p-3 sm:p-4 border border-slate-200/80 shadow-sm flex items-center justify-between gap-3">
                <MonthYearPicker
                    :model-value="filterForm.month"
                    @change="onMonthChange"
                    variant="inline"
                />
            </div>

            <!-- Summary Stats for Selected Month (Clean Data-First Architecture) -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                <!-- Total Income -->
                <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-sm flex flex-col justify-between">
                    <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                        Pemasukan ({{ formatPeriodIndo(filterForm.month) }})
                    </div>
                    <div class="text-xl sm:text-2xl font-bold font-mono tracking-tight text-emerald-700 mt-2">
                        +{{ formatRupiah(summary.total_income) }}
                    </div>
                </div>

                <!-- Total Expense -->
                <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-sm flex flex-col justify-between">
                    <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                        Pengeluaran ({{ formatPeriodIndo(filterForm.month) }})
                    </div>
                    <div class="text-xl sm:text-2xl font-bold font-mono tracking-tight text-rose-700 mt-2">
                        -{{ formatRupiah(summary.total_expense) }}
                    </div>
                </div>

                <!-- Net Cashflow -->
                <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center justify-between text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                        <span>Arus Kas Bersih</span>
                        <span
                            class="text-[10px] font-bold px-2 py-0.5 rounded-md"
                            :class="summary.net_cashflow >= 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'"
                        >
                            {{ summary.net_cashflow >= 0 ? 'Surplus' : 'Defisit' }}
                        </span>
                    </div>
                    <div
                        class="text-xl sm:text-2xl font-bold font-mono tracking-tight mt-2"
                        :class="summary.net_cashflow >= 0 ? 'text-slate-900' : 'text-rose-700'"
                    >
                        {{ formatRupiah(summary.net_cashflow) }}
                    </div>
                </div>
            </div>

            <!-- Filters Toolbar -->
            <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-3.5">
                <!-- Quick Type Filter Chips (One-Tap on Mobile) -->
                <div class="flex items-center gap-1.5 p-1 bg-slate-100/90 rounded-xl overflow-x-auto text-xs">
                    <button
                        type="button"
                        @click="filterForm.type = ''; applyFilters()"
                        class="flex-1 min-w-[70px] py-1.5 px-3 rounded-lg font-semibold transition-all text-center whitespace-nowrap"
                        :class="!filterForm.type ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                    >
                        Semua
                    </button>
                    <button
                        type="button"
                        @click="filterForm.type = 'expense'; applyFilters()"
                        class="flex-1 min-w-[85px] py-1.5 px-3 rounded-lg font-semibold transition-all text-center whitespace-nowrap"
                        :class="filterForm.type === 'expense' ? 'bg-white text-rose-700 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                    >
                        Pengeluaran
                    </button>
                    <button
                        type="button"
                        @click="filterForm.type = 'income'; applyFilters()"
                        class="flex-1 min-w-[85px] py-1.5 px-3 rounded-lg font-semibold transition-all text-center whitespace-nowrap"
                        :class="filterForm.type === 'income' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                    >
                        Pemasukan
                    </button>
                    <button
                        type="button"
                        @click="filterForm.type = 'transfer'; applyFilters()"
                        class="flex-1 min-w-[75px] py-1.5 px-3 rounded-lg font-semibold transition-all text-center whitespace-nowrap"
                        :class="filterForm.type === 'transfer' ? 'bg-white text-sky-700 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                    >
                        Transfer
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <!-- Account Filter -->
                    <div>
                        <InputLabel for="filter-account" value="Rekening" class="text-xs mb-1" />
                        <select
                            id="filter-account"
                            v-model="filterForm.account_id"
                            @change="applyFilters"
                            class="block w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-emerald-500 focus:ring-emerald-500"
                        >
                            <option value="">Semua Rekening</option>
                            <option v-for="acc in accounts" :key="acc.id" :value="acc.id">
                                {{ acc.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Category Filter -->
                    <div>
                        <InputLabel for="filter-category" value="Kategori" class="text-xs mb-1" />
                        <select
                            id="filter-category"
                            v-model="filterForm.category_id"
                            @change="applyFilters"
                            class="block w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-emerald-500 focus:ring-emerald-500"
                        >
                            <option value="">Semua Kategori</option>
                            <optgroup label="Pengeluaran">
                                <option v-for="cat in expenseCategories" :key="cat.id" :value="cat.id">
                                    {{ cat.name }}
                                </option>
                            </optgroup>
                            <optgroup label="Pemasukan">
                                <option v-for="cat in incomeCategories" :key="cat.id" :value="cat.id">
                                    {{ cat.name }}
                                </option>
                            </optgroup>
                        </select>
                    </div>

                    <!-- Search Input -->
                    <div>
                        <InputLabel for="filter-search" value="Cari Catatan" class="text-xs mb-1" />
                        <div class="relative">
                            <TextInput
                                id="filter-search"
                                v-model="filterForm.search"
                                @keyup.enter="applyFilters"
                                type="text"
                                placeholder="Cari catatan..."
                                class="w-full rounded-xl text-xs sm:text-sm pr-8"
                            />
                            <button
                                @click="applyFilters"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                                title="Cari"
                            >
                                <Search class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Reset Button -->
                <div class="flex items-center justify-between pt-1 text-xs">
                    <span class="text-slate-500">
                        Menampilkan {{ transactions.total }} transaksi
                    </span>
                    <button
                        v-if="isAnyFilterActive"
                        @click="resetFilters"
                        class="inline-flex items-center gap-1.5 text-xs text-rose-600 hover:text-rose-700 font-semibold"
                    >
                        <RotateCcw class="w-3.5 h-3.5" />
                        <span>Reset Filter</span>
                    </button>
                </div>
            </div>

            <!-- Transaction List -->
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
                <div v-if="transactions.data.length === 0" class="p-10 text-center">
                    <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                        <ArrowLeftRight class="w-6 h-6" />
                    </div>
                    <h3 class="text-sm font-bold text-slate-700">Tidak ada transaksi</h3>
                    <button
                        @click="openCreateModal('expense')"
                        class="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 transition"
                    >
                        <Plus class="w-4 h-4" />
                        <span>Catat Transaksi</span>
                    </button>
                </div>

                <div v-else class="divide-y divide-slate-100">
                    <div
                        v-for="tx in transactions.data"
                        :key="tx.id"
                        class="group p-4 sm:p-5 hover:bg-slate-50/80 transition flex items-center justify-between gap-4"
                    >
                        <!-- Left: Icon & Details -->
                        <div class="flex items-center gap-3.5 min-w-0">
                            <!-- Category / Type Icon Badge -->
                            <div
                                class="w-11 h-11 rounded-xl flex items-center justify-center text-white shrink-0 shadow-sm"
                                :style="{
                                    backgroundColor: tx.type === 'transfer'
                                        ? '#0284c7'
                                        : (tx.category?.color || (tx.type === 'expense' ? '#ef4444' : '#10b981'))
                                }"
                            >
                                <DynamicIcon
                                    :name="tx.type === 'transfer' ? 'ArrowLeftRight' : (tx.category?.icon || (tx.type === 'expense' ? 'ShoppingBag' : 'Briefcase'))"
                                    class="w-5 h-5"
                                />
                            </div>

                            <div class="min-w-0">
                                <!-- Title: Category name or Transfer info -->
                                <div class="flex items-center gap-2">
                                    <h4 class="text-sm font-bold text-slate-800 truncate">
                                        <template v-if="tx.type === 'transfer'">
                                            Transfer Saldo
                                        </template>
                                        <template v-else>
                                            {{ tx.category?.name || 'Kategori Dihapus' }}
                                        </template>
                                    </h4>

                                    <!-- Type Pill Badge -->
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold"
                                        :class="{
                                            'bg-rose-50 text-rose-700': tx.type === 'expense',
                                            'bg-emerald-50 text-emerald-700': tx.type === 'income',
                                            'bg-sky-50 text-sky-700': tx.type === 'transfer',
                                        }"
                                    >
                                        {{ tx.type === 'expense' ? 'Pengeluaran' : (tx.type === 'income' ? 'Pemasukan' : 'Transfer') }}
                                    </span>
                                </div>

                                <!-- Sub-info: Date, Account(s), Notes -->
                                <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5 text-xs text-slate-500 mt-1">
                                    <span class="font-medium text-slate-600">{{ formatTransactionDate(tx.date, tx.time, tx.created_at) }}</span>
                                    <span>&bull;</span>

                                    <!-- Account info -->
                                    <span class="inline-flex items-center gap-1">
                                        <span class="font-semibold text-slate-700">{{ tx.account?.name || 'Rekening' }}</span>
                                        <template v-if="tx.type === 'transfer'">
                                            <ArrowRight class="w-3 h-3 text-slate-400 inline" />
                                            <span class="font-semibold text-slate-700">{{ tx.destination_account?.name || 'Rekening Tujuan' }}</span>
                                        </template>
                                    </span>

                                    <!-- Notes -->
                                    <template v-if="tx.notes">
                                        <span>&bull;</span>
                                        <span class="truncate italic text-slate-500 max-w-xs">{{ tx.notes }}</span>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Amount & Actions -->
                        <div class="flex items-center gap-3 shrink-0">
                            <!-- Amount -->
                            <div class="text-right">
                                <div
                                    class="text-sm sm:text-base font-black tracking-tight"
                                    :class="{
                                        'text-rose-600': tx.type === 'expense',
                                        'text-emerald-600': tx.type === 'income',
                                        'text-sky-600': tx.type === 'transfer',
                                    }"
                                >
                                    <template v-if="tx.type === 'expense'">-{{ formatRupiah(tx.amount) }}</template>
                                    <template v-else-if="tx.type === 'income'">+{{ formatRupiah(tx.amount) }}</template>
                                    <template v-else>{{ formatRupiah(tx.amount) }}</template>
                                </div>
                                <div v-if="tx.reference_number" class="text-[10px] text-slate-400 font-mono">
                                    Ref: {{ tx.reference_number }}
                                </div>
                            </div>

                            <!-- Actions (Touch-friendly targets >= 40px) -->
                            <div class="flex items-center gap-1 transition">
                                <button
                                    type="button"
                                    @click="openEditModal(tx)"
                                    class="w-9 h-9 sm:w-8 sm:h-8 flex items-center justify-center rounded-xl text-slate-400 hover:text-emerald-700 hover:bg-emerald-50 active:scale-95 transition"
                                    title="Ubah Transaksi"
                                    aria-label="Ubah Transaksi"
                                >
                                    <Pencil class="w-4 h-4" />
                                </button>
                                <button
                                    type="button"
                                    @click="confirmDelete(tx)"
                                    class="w-9 h-9 sm:w-8 sm:h-8 flex items-center justify-center rounded-xl text-slate-400 hover:text-rose-700 hover:bg-rose-50 active:scale-95 transition"
                                    title="Hapus Transaksi"
                                    aria-label="Hapus Transaksi"
                                >
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="transactions.links && transactions.links.length > 3" class="p-4 border-t border-slate-100 bg-slate-50/50">
                    <Pagination :links="transactions.links" />
                </div>
            </div>
        </div>

        <!-- Add / Edit Modal -->
        <Modal :show="isModalOpen" @close="closeModal" max-width="lg">
            <div class="p-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <h3 class="text-lg font-bold text-slate-800">
                        {{ isEditing ? 'Ubah Transaksi' : 'Catat Transaksi Baru' }}
                    </h3>
                    <button @click="closeModal" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                        ✕
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="mt-4 space-y-4">
                    <!-- Type Selector Buttons -->
                    <div>
                        <InputLabel value="Tipe Transaksi *" class="mb-1" />
                        <div class="grid grid-cols-3 gap-2">
                            <button
                                type="button"
                                @click="setTransactionType('expense')"
                                class="flex items-center justify-center gap-2 py-2 px-3 rounded-xl text-xs font-bold border transition"
                                :class="form.type === 'expense'
                                    ? 'bg-rose-50 border-rose-300 text-rose-700 shadow-sm'
                                    : 'border-slate-200 text-slate-600 hover:bg-slate-50'"
                            >
                                <TrendingDown class="w-4 h-4 text-rose-600" />
                                <span>Pengeluaran</span>
                            </button>

                            <button
                                type="button"
                                @click="setTransactionType('income')"
                                class="flex items-center justify-center gap-2 py-2 px-3 rounded-xl text-xs font-bold border transition"
                                :class="form.type === 'income'
                                    ? 'bg-emerald-50 border-emerald-300 text-emerald-700 shadow-sm'
                                    : 'border-slate-200 text-slate-600 hover:bg-slate-50'"
                            >
                                <TrendingUp class="w-4 h-4 text-emerald-600" />
                                <span>Pemasukan</span>
                            </button>

                            <button
                                type="button"
                                @click="setTransactionType('transfer')"
                                class="flex items-center justify-center gap-2 py-2 px-3 rounded-xl text-xs font-bold border transition"
                                :class="form.type === 'transfer'
                                    ? 'bg-sky-50 border-sky-300 text-sky-700 shadow-sm'
                                    : 'border-slate-200 text-slate-600 hover:bg-slate-50'"
                            >
                                <ArrowLeftRight class="w-4 h-4 text-sky-600" />
                                <span>Transfer</span>
                            </button>
                        </div>
                    </div>

                    <!-- Nominal Amount -->
                    <div>
                        <InputLabel for="amount" value="Nominal *" />
                        <div class="relative mt-1">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 font-bold text-slate-400 text-sm">
                                Rp
                            </span>
                            <MoneyInput
                                id="amount"
                                v-model="form.amount"
                                placeholder="0"
                                class="pl-11 block w-full rounded-xl font-mono text-lg font-bold"
                                required
                                autofocus
                            />
                        </div>
                        <p v-if="form.amount && Number(form.amount) > 0" class="mt-1 text-xs font-semibold text-slate-500 font-mono">
                            {{ formatRupiah(form.amount) }}
                        </p>
                        <InputError :message="form.errors.amount" class="mt-1" />
                    </div>

                    <!-- Date & Time -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Date -->
                        <div>
                            <InputLabel for="date" value="Tanggal *" />
                            <input
                                id="date"
                                v-model="form.date"
                                type="date"
                                class="mt-1 block w-full rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 text-sm"
                                required
                            />
                            <InputError :message="form.errors.date" class="mt-1" />
                        </div>

                        <!-- Time -->
                        <div>
                            <div class="flex items-center justify-between">
                                <InputLabel for="time" value="Waktu" />
                                <button
                                    type="button"
                                    @click="setCurrentTime"
                                    class="text-[11px] font-semibold text-emerald-600 hover:text-emerald-700 transition"
                                >
                                    Sekarang
                                </button>
                            </div>
                            <input
                                id="time"
                                v-model="form.time"
                                type="time"
                                class="mt-1 block w-full rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 text-sm"
                            />
                            <InputError :message="form.errors.time" class="mt-1" />
                        </div>
                    </div>

                    <!-- Account & Category Fields -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Source Account -->
                        <div>
                            <InputLabel
                                for="account_id"
                                :value="form.type === 'transfer' ? 'Dari Rekening *' : 'Rekening *'"
                            />
                            <select
                                id="account_id"
                                v-model="form.account_id"
                                class="mt-1 block w-full rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 text-sm"
                                required
                            >
                                <option value="" disabled>Pilih Rekening</option>
                                <option v-for="acc in activeAccounts" :key="acc.id" :value="acc.id">
                                    {{ acc.name }} ({{ formatRupiah(acc.current_balance) }})
                                </option>
                            </select>
                            <InputError :message="form.errors.account_id" class="mt-1" />
                        </div>

                        <!-- Destination Account for Transfer -->
                        <div v-if="form.type === 'transfer'">
                            <InputLabel for="dest_account" value="Ke Rekening *" />
                            <select
                                id="dest_account"
                                v-model="form.destination_account_id"
                                class="mt-1 block w-full rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 text-sm"
                                required
                            >
                                <option value="" disabled>Pilih Rekening Tujuan</option>
                                <option v-for="acc in availableDestAccounts" :key="acc.id" :value="acc.id">
                                    {{ acc.name }} ({{ formatRupiah(acc.current_balance) }})
                                </option>
                            </select>
                            <InputError :message="form.errors.destination_account_id" class="mt-1" />
                        </div>

                        <!-- Category for Expense/Income -->
                        <div v-else>
                            <InputLabel
                                for="category_id"
                                value="Kategori *"
                            />
                            <select
                                id="category_id"
                                v-model="form.category_id"
                                class="mt-1 block w-full rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 text-sm"
                                required
                            >
                                <option value="" disabled>Pilih Kategori</option>
                                <option
                                    v-for="cat in (form.type === 'expense' ? expenseCategories : incomeCategories)"
                                    :key="cat.id"
                                    :value="cat.id"
                                >
                                    {{ cat.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.category_id" class="mt-1" />
                        </div>
                    </div>

                    <!-- Notes -->
                    <div>
                        <InputLabel for="notes" value="Catatan" />
                        <textarea
                            id="notes"
                            v-model="form.notes"
                            rows="2"
                            class="mt-1 block w-full rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 text-sm shadow-sm"
                            placeholder="Catatan..."
                        ></textarea>
                        <InputError :message="form.errors.notes" class="mt-1" />
                    </div>

                    <!-- Reference Number -->
                    <div>
                        <InputLabel for="ref-num" value="No. Referensi" />
                        <TextInput
                            id="ref-num"
                            v-model="form.reference_number"
                            type="text"
                            class="mt-1 block w-full rounded-xl text-sm"
                            placeholder="Opsional"
                        />
                        <InputError :message="form.errors.reference_number" class="mt-1" />
                    </div>

                    <!-- Footer Actions -->
                    <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2 sm:gap-3 pt-4 border-t border-slate-100">
                        <SecondaryButton type="button" @click="closeModal" class="w-full sm:w-auto justify-center">
                            Batal
                        </SecondaryButton>
                        <button
                            v-if="!isEditing"
                            type="button"
                            :disabled="form.processing"
                            @click="submitForm(true)"
                            class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 transition-colors disabled:opacity-50"
                        >
                            Simpan & Catat Lagi
                        </button>
                        <PrimaryButton :disabled="form.processing" @click="submitForm(false)" class="w-full sm:w-auto justify-center bg-emerald-600 hover:bg-emerald-700">
                            {{ form.processing ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Catat Transaksi') }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Delete Confirmation Modal -->
        <Modal :show="isDeleteModalOpen" @close="closeDeleteModal" max-width="md">
            <div class="p-6">
                <div class="flex items-center gap-3 text-rose-600 mb-3">
                    <div class="w-9 h-9 rounded-xl bg-rose-100 flex items-center justify-center shrink-0">
                        <AlertTriangle class="w-5 h-5" />
                    </div>
                    <h3 class="text-base font-bold text-slate-800">Hapus Transaksi</h3>
                </div>

                <p class="text-sm text-slate-600">
                    Hapus catatan transaksi senilai
                    <strong class="text-slate-900 font-bold">{{ formatRupiah(transactionToDelete?.amount) }}</strong>?
                </p>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <SecondaryButton @click="closeDeleteModal">
                        Batal
                    </SecondaryButton>
                    <DangerButton @click="executeDelete">
                        Ya, Hapus Transaksi
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
