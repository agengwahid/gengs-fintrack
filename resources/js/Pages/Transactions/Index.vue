<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DynamicIcon from '@/Components/DynamicIcon.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { formatRupiah, formatDateIndo, formatPeriodIndo } from '@/Utils/formatters';
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

// Transaction Modal State
const isModalOpen = ref(false);
const isEditing = ref(false);
const editingTransactionId = ref(null);
const isDeleteModalOpen = ref(false);
const transactionToDelete = ref(null);

const today = new Date().toISOString().slice(0, 10);

const form = useForm({
    type: 'expense',
    amount: '',
    date: today,
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

function submitForm() {
    if (isEditing.value) {
        form.put(route('transactions.update', editingTransactionId.value), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('transactions.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
}

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
            <button
                @click="openCreateModal('expense')"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm shadow-emerald-600/30 transition transform hover:-translate-y-0.5"
            >
                <Plus class="w-4 h-4" />
                <span>Catat Transaksi</span>
            </button>
        </template>

        <div class="space-y-6">
            <!-- Summary Stats for Selected Month -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- Total Income -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
                    <div>
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">
                            Pemasukan ({{ formatPeriodIndo(filterForm.month) }})
                        </div>
                        <div class="text-xl sm:text-2xl font-black text-emerald-600 mt-1">
                            +{{ formatRupiah(summary.total_income) }}
                        </div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <TrendingUp class="w-6 h-6" />
                    </div>
                </div>

                <!-- Total Expense -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
                    <div>
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">
                            Pengeluaran ({{ formatPeriodIndo(filterForm.month) }})
                        </div>
                        <div class="text-xl sm:text-2xl font-black text-rose-600 mt-1">
                            -{{ formatRupiah(summary.total_expense) }}
                        </div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                        <TrendingDown class="w-6 h-6" />
                    </div>
                </div>

                <!-- Net Cashflow -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
                    <div>
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">
                            Arus Kas Bersih
                        </div>
                        <div
                            class="text-xl sm:text-2xl font-black mt-1"
                            :class="summary.net_cashflow >= 0 ? 'text-teal-600' : 'text-amber-600'"
                        >
                            {{ formatRupiah(summary.net_cashflow) }}
                        </div>
                    </div>
                    <div
                        class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0"
                        :class="summary.net_cashflow >= 0 ? 'bg-teal-50 text-teal-600' : 'bg-amber-50 text-amber-600'"
                    >
                        <Wallet class="w-6 h-6" />
                    </div>
                </div>
            </div>

            <!-- Filters Toolbar -->
            <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-sm space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                    <!-- Period / Month -->
                    <div>
                        <InputLabel for="filter-month" value="Periode Bulan" class="text-xs mb-1" />
                        <input
                            id="filter-month"
                            type="month"
                            v-model="filterForm.month"
                            @change="applyFilters"
                            class="block w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-emerald-500 focus:ring-emerald-500"
                        />
                    </div>

                    <!-- Type Filter -->
                    <div>
                        <InputLabel for="filter-type" value="Tipe Transaksi" class="text-xs mb-1" />
                        <select
                            id="filter-type"
                            v-model="filterForm.type"
                            @change="applyFilters"
                            class="block w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-emerald-500 focus:ring-emerald-500"
                        >
                            <option value="">Semua Tipe</option>
                            <option value="expense">Pengeluaran</option>
                            <option value="income">Pemasukan</option>
                            <option value="transfer">Transfer</option>
                        </select>
                    </div>

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
                        v-if="filterForm.search || filterForm.type || filterForm.account_id || filterForm.category_id || filterForm.month !== new Date().toISOString().slice(0, 7)"
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
                <div v-if="transactions.data.length === 0" class="p-12 text-center">
                    <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-4">
                        <ArrowLeftRight class="w-8 h-8" />
                    </div>
                    <h3 class="text-base font-bold text-slate-800">Tidak Ada Transaksi</h3>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-sm mx-auto">
                        Tidak ada riwayat transaksi yang cocok dengan filter yang dipilih.
                    </p>
                    <button
                        @click="openCreateModal('expense')"
                        class="mt-5 inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition"
                    >
                        <Plus class="w-4 h-4" />
                        <span>Catat Transaksi Sekarang</span>
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
                                    <span class="font-medium text-slate-600">{{ formatDateIndo(tx.date) }}</span>
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

                            <!-- Actions -->
                            <div class="flex items-center gap-1 opacity-80 group-hover:opacity-100 transition">
                                <button
                                    @click="openEditModal(tx)"
                                    class="p-1.5 rounded-lg text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 transition"
                                    title="Ubah Transaksi"
                                >
                                    <Pencil class="w-4 h-4" />
                                </button>
                                <button
                                    @click="confirmDelete(tx)"
                                    class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition"
                                    title="Hapus Transaksi"
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
                        <InputLabel for="amount" value="Nominal Transaksi (Rp) *" />
                        <div class="relative mt-1">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 font-bold text-slate-400 text-sm">
                                Rp
                            </span>
                            <TextInput
                                id="amount"
                                v-model="form.amount"
                                type="number"
                                step="any"
                                min="0.01"
                                placeholder="0"
                                class="pl-11 block w-full rounded-xl font-mono text-lg font-bold"
                                required
                                autofocus
                            />
                        </div>
                        <InputError :message="form.errors.amount" class="mt-1" />
                    </div>

                    <!-- Date -->
                    <div>
                        <InputLabel for="date" value="Tanggal Transaksi *" />
                        <input
                            id="date"
                            v-model="form.date"
                            type="date"
                            class="mt-1 block w-full rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 text-sm"
                            required
                        />
                        <InputError :message="form.errors.date" class="mt-1" />
                    </div>

                    <!-- Account & Category Fields -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Source Account -->
                        <div>
                            <InputLabel
                                for="account_id"
                                :value="form.type === 'transfer' ? 'Dari Rekening (Asal) *' : (form.type === 'income' ? 'Rekening Tujuan *' : 'Rekening Sumber *')"
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
                            <InputLabel for="dest_account" value="Ke Rekening (Tujuan) *" />
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
                                :value="form.type === 'expense' ? 'Kategori Pengeluaran *' : 'Kategori Pemasukan *'"
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
                        <InputLabel for="notes" value="Catatan / Keterangan (Opsional)" />
                        <textarea
                            id="notes"
                            v-model="form.notes"
                            rows="2"
                            class="mt-1 block w-full rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 text-sm shadow-sm"
                            placeholder="Catatan belanja, nama toko, keperluan..."
                        ></textarea>
                        <InputError :message="form.errors.notes" class="mt-1" />
                    </div>

                    <!-- Reference Number -->
                    <div>
                        <InputLabel for="ref-num" value="No. Referensi / ID Transaksi (Opsional)" />
                        <TextInput
                            id="ref-num"
                            v-model="form.reference_number"
                            type="text"
                            class="mt-1 block w-full rounded-xl"
                            placeholder="Contoh: INV-001, No. Resi, dll"
                        />
                        <InputError :message="form.errors.reference_number" class="mt-1" />
                    </div>

                    <!-- Footer Actions -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                        <SecondaryButton type="button" @click="closeModal">
                            Batal
                        </SecondaryButton>
                        <PrimaryButton :disabled="form.processing" class="bg-emerald-600 hover:bg-emerald-700">
                            {{ form.processing ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Catat Transaksi') }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Delete Confirmation Modal -->
        <Modal :show="isDeleteModalOpen" @close="closeDeleteModal" max-width="md">
            <div class="p-6">
                <div class="flex items-center gap-3 text-rose-600 mb-4">
                    <div class="w-10 h-10 rounded-full bg-rose-100 flex items-center justify-center shrink-0">
                        <AlertTriangle class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-800">Hapus Transaksi?</h3>
                        <p class="text-xs text-slate-500">Saldo rekening akan otomatis disesuaikan kembali</p>
                    </div>
                </div>

                <p class="text-sm text-slate-600 leading-relaxed">
                    Apakah Anda yakin ingin menghapus catatan transaksi senilai
                    <strong class="text-slate-900">{{ formatRupiah(transactionToDelete?.amount) }}</strong>?
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
