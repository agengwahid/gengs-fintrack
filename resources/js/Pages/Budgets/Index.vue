<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DynamicIcon from '@/Components/DynamicIcon.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { formatRupiah, formatPeriodIndo } from '@/Utils/formatters';
import {
    Plus,
    PiggyBank,
    TrendingDown,
    AlertTriangle,
    CheckCircle2,
    Calendar,
    ChevronLeft,
    ChevronRight,
    Pencil,
    Trash2,
    ArrowUpRight,
    Sparkles
} from '@lucide/vue';

const props = defineProps({
    budgets: {
        type: Array,
        default: () => [],
    },
    period: {
        type: String,
        required: true,
    },
    summary: {
        type: Object,
        default: () => ({
            total_budget: 0,
            total_spent: 0,
            total_remaining: 0,
            overall_percentage: 0,
        }),
    },
    categories: {
        type: Array,
        default: () => [],
    },
});

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingBudgetId = ref(null);
const isDeleteModalOpen = ref(false);
const budgetToDelete = ref(null);

const form = useForm({
    category_id: '',
    period: props.period,
    amount: '',
    notes: '',
});

// Filter categories that do NOT yet have budget in current period
const availableCategories = computed(() => {
    if (isEditing.value) {
        return props.categories;
    }
    const budgetedIds = new Set(props.budgets.map(b => b.category_id));
    return props.categories.filter(c => !budgetedIds.has(c.id));
});

function changePeriod(newPeriod) {
    router.get(route('budgets.index'), { period: newPeriod }, {
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

function openCreateModal() {
    isEditing.value = false;
    editingBudgetId.value = null;
    form.reset();
    form.clearErrors();
    form.period = props.period;
    if (availableCategories.value.length > 0) {
        form.category_id = availableCategories.value[0].id;
    }
    isModalOpen.value = true;
}

function openEditModal(budget) {
    isEditing.value = true;
    editingBudgetId.value = budget.id;
    form.clearErrors();
    form.category_id = budget.category_id;
    form.period = budget.period;
    form.amount = budget.amount;
    form.notes = budget.notes || '';
    isModalOpen.value = true;
}

function submitForm() {
    if (isEditing.value) {
        form.put(route('budgets.update', editingBudgetId.value), {
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
            },
        });
    } else {
        form.post(route('budgets.store'), {
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
            },
        });
    }
}

function confirmDelete(budget) {
    budgetToDelete.value = budget;
    isDeleteModalOpen.value = true;
}

function deleteBudget() {
    if (!budgetToDelete.value) return;

    router.delete(route('budgets.destroy', budgetToDelete.value.id), {
        onSuccess: () => {
            isDeleteModalOpen.value = false;
            budgetToDelete.value = null;
        },
    });
}
</script>

<template>
    <Head title="Anggaran Bulanan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-2">
                <PiggyBank class="w-6 h-6 text-emerald-600" />
                <span>Anggaran Bulanan</span>
            </div>
        </template>

        <template #actions>
            <button
                @click="openCreateModal"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 shadow-md shadow-emerald-600/20 transition"
            >
                <Plus class="w-4 h-4" />
                <span class="hidden sm:inline">Tambah Anggaran</span>
                <span class="sm:hidden">Tambah</span>
            </button>
        </template>

        <div class="space-y-6">
            <!-- Period Navigation & Filter Bar -->
            <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <button
                        @click="prevMonth"
                        class="p-2 rounded-xl text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition"
                        title="Bulan Sebelumnya"
                    >
                        <ChevronLeft class="w-5 h-5" />
                    </button>

                    <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-bold text-base sm:text-lg">
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

                <div class="text-xs text-slate-500 flex items-center gap-1.5">
                    <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
                    Menampilkan target & realisasi pengeluaran bulan kalender.
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Budget -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm">
                    <div class="flex items-center justify-between text-slate-500 text-xs font-semibold uppercase tracking-wider">
                        <span>Total Anggaran</span>
                        <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <PiggyBank class="w-4 h-4" />
                        </div>
                    </div>
                    <div class="mt-2 text-2xl font-black text-slate-900 tracking-tight">
                        {{ formatRupiah(summary.total_budget) }}
                    </div>
                    <div class="mt-1 text-xs text-slate-400">
                        {{ budgets.length }} kategori dianggarkan
                    </div>
                </div>

                <!-- Total Spent -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm">
                    <div class="flex items-center justify-between text-slate-500 text-xs font-semibold uppercase tracking-wider">
                        <span>Realisasi Pengeluaran</span>
                        <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                            <TrendingDown class="w-4 h-4" />
                        </div>
                    </div>
                    <div class="mt-2 text-2xl font-black text-rose-600 tracking-tight">
                        {{ formatRupiah(summary.total_spent) }}
                    </div>
                    <div class="mt-1 text-xs text-slate-400">
                        Pengeluaran aktual bulan ini
                    </div>
                </div>

                <!-- Remaining Budget -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm">
                    <div class="flex items-center justify-between text-slate-500 text-xs font-semibold uppercase tracking-wider">
                        <span>Sisa Anggaran</span>
                        <div
                            class="w-8 h-8 rounded-xl flex items-center justify-center"
                            :class="summary.total_remaining >= 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600'"
                        >
                            <CheckCircle2 v-if="summary.total_remaining >= 0" class="w-4 h-4" />
                            <AlertTriangle v-else class="w-4 h-4" />
                        </div>
                    </div>
                    <div
                        class="mt-2 text-2xl font-black tracking-tight"
                        :class="summary.total_remaining >= 0 ? 'text-slate-900' : 'text-rose-600'"
                    >
                        {{ formatRupiah(summary.total_remaining) }}
                    </div>
                    <div class="mt-1 text-xs font-medium" :class="summary.total_remaining >= 0 ? 'text-emerald-600' : 'text-rose-600'">
                        {{ summary.total_remaining >= 0 ? 'Dana masih tersedia' : 'Overbudget / Defisit' }}
                    </div>
                </div>

                <!-- Overall Percentage -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between text-slate-500 text-xs font-semibold uppercase tracking-wider">
                            <span>Status Terpakai</span>
                            <span
                                class="text-xs font-bold px-2 py-0.5 rounded-full"
                                :class="{
                                    'bg-emerald-100 text-emerald-700': summary.overall_percentage < 75,
                                    'bg-amber-100 text-amber-700': summary.overall_percentage >= 75 && summary.overall_percentage < 90,
                                    'bg-orange-100 text-orange-700': summary.overall_percentage >= 90 && summary.overall_percentage < 100,
                                    'bg-rose-100 text-rose-700': summary.overall_percentage >= 100,
                                }"
                            >
                                {{ summary.overall_percentage }}%
                            </span>
                        </div>
                        <div class="mt-2 text-2xl font-black text-slate-900 tracking-tight">
                            {{ summary.overall_percentage }}%
                        </div>
                    </div>

                    <div class="mt-3">
                        <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                            <div
                                class="h-2.5 rounded-full transition-all duration-500"
                                :class="{
                                    'bg-emerald-500': summary.overall_percentage < 75,
                                    'bg-amber-500': summary.overall_percentage >= 75 && summary.overall_percentage < 90,
                                    'bg-orange-500': summary.overall_percentage >= 90 && summary.overall_percentage < 100,
                                    'bg-rose-500': summary.overall_percentage >= 100,
                                }"
                                :style="{ width: `${Math.min(summary.overall_percentage, 100)}%` }"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Budgets List / Grid -->
            <div v-if="budgets.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <div
                    v-for="b in budgets"
                    :key="b.id"
                    class="bg-white rounded-2xl p-5 border shadow-sm transition hover:shadow-md flex flex-col justify-between"
                    :class="b.is_over_budget ? 'border-rose-200 ring-1 ring-rose-200' : 'border-slate-200/80'"
                >
                    <div>
                        <!-- Header: Icon, Category Name, & Actions -->
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div
                                    class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 shadow-sm"
                                    :style="{ backgroundColor: (b.category?.color || '#10b981') + '15', color: b.category?.color || '#10b981' }"
                                >
                                    <DynamicIcon :name="b.category?.icon || 'Folder'" class="w-6 h-6" />
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-base font-bold text-slate-900 truncate">
                                        {{ b.category?.name || 'Kategori' }}
                                    </h3>
                                    <p v-if="b.notes" class="text-xs text-slate-400 truncate mt-0.5">
                                        {{ b.notes }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-1 shrink-0">
                                <button
                                    @click="openEditModal(b)"
                                    class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition"
                                    title="Edit Anggaran"
                                >
                                    <Pencil class="w-4 h-4" />
                                </button>
                                <button
                                    @click="confirmDelete(b)"
                                    class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition"
                                    title="Hapus Anggaran"
                                >
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <!-- Amount Realization Info -->
                        <div class="mt-5 space-y-1">
                            <div class="flex items-baseline justify-between">
                                <span class="text-xs text-slate-400 font-medium">Realisasi</span>
                                <div class="text-right">
                                    <span class="text-base font-extrabold text-slate-900">{{ formatRupiah(b.spent) }}</span>
                                    <span class="text-xs text-slate-400 font-medium ml-1">/ {{ formatRupiah(b.amount) }}</span>
                                </div>
                            </div>

                            <!-- Progress Bar -->
                            <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden my-2">
                                <div
                                    class="h-2.5 rounded-full transition-all duration-500"
                                    :class="{
                                        'bg-emerald-500': b.percentage < 70,
                                        'bg-amber-500': b.percentage >= 70 && b.percentage < 90,
                                        'bg-orange-500': b.percentage >= 90 && b.percentage < 100,
                                        'bg-rose-500': b.percentage >= 100,
                                    }"
                                    :style="{ width: `${Math.min(b.percentage, 100)}%` }"
                                ></div>
                            </div>

                            <!-- Remaining & Status Info -->
                            <div class="flex items-center justify-between text-xs pt-1">
                                <div class="font-bold" :class="b.is_over_budget ? 'text-rose-600' : 'text-slate-600'">
                                    {{ b.percentage }}% Terpakai
                                </div>
                                <div
                                    class="font-semibold"
                                    :class="b.is_over_budget ? 'text-rose-600 font-bold' : 'text-emerald-600'"
                                >
                                    <span v-if="b.is_over_budget">
                                        Lebih {{ formatRupiah(Math.abs(b.remaining)) }}
                                    </span>
                                    <span v-else>
                                        Sisa {{ formatRupiah(b.remaining) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Warning footer badge for overbudget -->
                    <div
                        v-if="b.is_over_budget"
                        class="mt-4 pt-3 border-t border-rose-100 flex items-center gap-1.5 text-xs text-rose-700 font-semibold"
                    >
                        <AlertTriangle class="w-4 h-4 shrink-0 text-rose-600" />
                        <span>Melebihi target batas pengeluaran!</span>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-else
                class="bg-white rounded-3xl p-10 sm:p-14 border border-slate-200/80 shadow-sm text-center max-w-xl mx-auto"
            >
                <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-4 shadow-sm">
                    <PiggyBank class="w-8 h-8" />
                </div>
                <h3 class="text-xl font-bold text-slate-800">Belum Ada Anggaran di {{ formatPeriodIndo(period) }}</h3>
                <p class="text-sm text-slate-500 mt-2 leading-relaxed">
                    Menetapkan anggaran bulanan membantu Anda mengontrol gaya hidup dan mencegah pengeluaran berlebihan.
                </p>
                <button
                    @click="openCreateModal"
                    class="mt-6 inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-md shadow-emerald-600/20 transition"
                >
                    <Plus class="w-4 h-4" />
                    Buat Anggaran Sekarang
                </button>
            </div>
        </div>

        <!-- Modal Form: Tambah / Edit Anggaran -->
        <Modal :show="isModalOpen" @close="isModalOpen = false" max-width="md">
            <div class="p-6">
                <h3 class="text-lg font-bold text-slate-900 mb-1">
                    {{ isEditing ? 'Edit Anggaran' : 'Buat Anggaran Baru' }}
                </h3>
                <p class="text-xs text-slate-500 mb-5">
                    Tentukan target limit pengeluaran untuk periode {{ formatPeriodIndo(form.period) }}.
                </p>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <!-- Category Select (only for create) -->
                    <div v-if="!isEditing">
                        <InputLabel for="category_id" value="Kategori Pengeluaran" />
                        <select
                            id="category_id"
                            v-model="form.category_id"
                            class="mt-1 block w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                            required
                        >
                            <option value="" disabled>Pilih Kategori</option>
                            <option
                                v-for="cat in availableCategories"
                                :key="cat.id"
                                :value="cat.id"
                            >
                                {{ cat.name }}
                            </option>
                        </select>
                        <InputError class="mt-1.5" :message="form.errors.category_id" />
                        <p v-if="availableCategories.length === 0" class="text-xs text-amber-600 mt-1">
                            Semua kategori pengeluaran sudah memiliki anggaran di bulan ini.
                        </p>
                    </div>

                    <!-- Target Nominal -->
                    <div>
                        <InputLabel for="amount" value="Nominal Anggaran (Rp)" />
                        <TextInput
                            id="amount"
                            type="number"
                            step="1000"
                            min="1"
                            v-model="form.amount"
                            class="mt-1 block w-full rounded-xl text-sm"
                            placeholder="Contoh: 1500000"
                            required
                        />
                        <InputError class="mt-1.5" :message="form.errors.amount" />
                    </div>

                    <!-- Period Input -->
                    <div>
                        <InputLabel for="period" value="Periode Bulan" />
                        <TextInput
                            id="period"
                            type="month"
                            v-model="form.period"
                            class="mt-1 block w-full rounded-xl text-sm"
                            required
                            :disabled="isEditing"
                        />
                        <InputError class="mt-1.5" :message="form.errors.period" />
                    </div>

                    <!-- Notes -->
                    <div>
                        <InputLabel for="notes" value="Catatan Opsional" />
                        <textarea
                            id="notes"
                            v-model="form.notes"
                            rows="2"
                            class="mt-1 block w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                            placeholder="Contoh: Target hemat makan di luar"
                        ></textarea>
                        <InputError class="mt-1.5" :message="form.errors.notes" />
                    </div>

                    <!-- Modal Actions -->
                    <div class="mt-6 flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                        <SecondaryButton @click="isModalOpen = false" :disabled="form.processing">
                            Batal
                        </SecondaryButton>
                        <PrimaryButton :disabled="form.processing || (!isEditing && availableCategories.length === 0)">
                            {{ form.processing ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Buat Anggaran') }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Modal Konfirmasi Hapus -->
        <Modal :show="isDeleteModalOpen" @close="isDeleteModalOpen = false" max-width="sm">
            <div class="p-6">
                <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center mx-auto mb-4">
                    <Trash2 class="w-6 h-6" />
                </div>
                <h3 class="text-lg font-bold text-slate-900 text-center">Hapus Anggaran Ini?</h3>
                <p class="text-xs text-slate-500 text-center mt-2">
                    Anggaran untuk kategori <span class="font-bold text-slate-800">{{ budgetToDelete?.category?.name }}</span> pada periode ini akan dihapus. Riwayat transaksi pengeluaran tidak akan terhapus.
                </p>

                <div class="mt-6 flex items-center justify-center gap-3">
                    <SecondaryButton @click="isDeleteModalOpen = false">
                        Batal
                    </SecondaryButton>
                    <DangerButton @click="deleteBudget">
                        Hapus Anggaran
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
