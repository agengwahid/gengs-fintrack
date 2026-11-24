<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DynamicIcon from '@/Components/DynamicIcon.vue';
import HeaderActionButton from '@/Components/HeaderActionButton.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import MoneyInput from '@/Components/MoneyInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { formatRupiah } from '@/Utils/formatters';
import {
    Plus,
    Wallet,
    Building2,
    Smartphone,
    CreditCard,
    TrendingUp,
    Coins,
    Landmark,
    Pencil,
    Trash2,
    CheckCircle2,
    AlertTriangle,
    Check,
    Copy
} from '@lucide/vue';

const props = defineProps({
    accounts: {
        type: Array,
        default: () => [],
    },
    totalBalance: {
        type: Number,
        default: 0,
    },
});

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingAccountId = ref(null);
const isDeleteModalOpen = ref(false);
const accountToDelete = ref(null);
const copiedAccountId = ref(null);

function copyAccountNumber(account) {
    if (!account.account_number) return;
    if (navigator?.clipboard) {
        navigator.clipboard.writeText(account.account_number);
        copiedAccountId.value = account.id;
        setTimeout(() => {
            if (copiedAccountId.value === account.id) {
                copiedAccountId.value = null;
            }
        }, 2000);
    }
}

const accountTypes = [
    { value: 'cash', label: 'Uang Tunai', icon: 'Wallet' },
    { value: 'bank', label: 'Rekening Bank', icon: 'Building2' },
    { value: 'ewallet', label: 'E-Wallet', icon: 'Smartphone' },
    { value: 'investment', label: 'Investasi', icon: 'TrendingUp' },
    { value: 'credit_card', label: 'Kartu Kredit', icon: 'CreditCard' },
    { value: 'other', label: 'Lainnya', icon: 'Coins' },
];

const availableIcons = [
    'Wallet', 'Building2', 'Smartphone', 'CreditCard', 'TrendingUp', 'Coins', 'Landmark'
];

const availableColors = [
    '#10b981', '#06b6d4', '#2563eb', '#8b5cf6', '#ec4899', '#f59e0b', '#f97316', '#64748b'
];

const form = useForm({
    name: '',
    type: 'bank',
    account_number: '',
    initial_balance: 0,
    color: '#10b981',
    icon: 'Wallet',
    description: '',
    is_active: true,
});

function openCreateModal() {
    isEditing.value = false;
    editingAccountId.value = null;
    form.reset();
    form.clearErrors();
    form.color = '#10b981';
    form.icon = 'Building2';
    form.type = 'bank';
    form.is_active = true;
    isModalOpen.value = true;
}

function openEditModal(account) {
    isEditing.value = true;
    editingAccountId.value = account.id;
    form.clearErrors();
    form.name = account.name;
    form.type = account.type;
    form.account_number = account.account_number || '';
    form.initial_balance = Number(account.initial_balance);
    form.color = account.color || '#10b981';
    form.icon = account.icon || 'Wallet';
    form.description = account.description || '';
    form.is_active = Boolean(account.is_active);
    isModalOpen.value = true;
}

function closeModal() {
    isModalOpen.value = false;
    form.reset();
    form.clearErrors();
}

function submitForm() {
    if (isEditing.value) {
        form.put(route('accounts.update', editingAccountId.value), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('accounts.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
}

function confirmDelete(account) {
    accountToDelete.value = account;
    isDeleteModalOpen.value = true;
}

function closeDeleteModal() {
    isDeleteModalOpen.value = false;
    accountToDelete.value = null;
}

function executeDelete() {
    if (!accountToDelete.value) return;

    router.delete(route('accounts.destroy', accountToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => closeDeleteModal(),
    });
}

function getTypeLabel(type) {
    const found = accountTypes.find(t => t.value === type);
    return found ? found.label : type;
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Rekening & Dompet" />

        <template #header>
            <div class="flex items-center gap-2">
                <Wallet class="w-6 h-6 text-emerald-600" />
                <span>Rekening & Dompet</span>
            </div>
        </template>

        <template #actions>
            <HeaderActionButton
                @click="openCreateModal"
                label="Tambah Rekening"
                short-label="Rekening"
            />
        </template>

        <div class="space-y-5">
            <!-- Summary Banner (Clean, Compact Modern FinTech Header) -->
            <div class="relative overflow-hidden rounded-2xl sm:rounded-3xl bg-gradient-to-br from-emerald-600 via-teal-600 to-slate-900 p-5 sm:p-7 text-white shadow-lg shadow-emerald-950/10">
                <div class="relative z-10">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-emerald-200/90 uppercase tracking-wider">
                            Total Saldo Rekening
                        </span>
                        <span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-white/15 text-white backdrop-blur-sm">
                            {{ accounts.filter(a => a.is_active).length }} Aktif
                        </span>
                    </div>

                    <div class="text-2xl sm:text-4xl font-black font-mono tracking-tight mt-2 text-white">
                        {{ formatRupiah(totalBalance) }}
                    </div>

                    <!-- Breakdown Pills (Compact & Mobile Friendly) -->
                    <div class="grid grid-cols-3 gap-2 mt-4 pt-3 border-t border-white/10 text-xs">
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-2 text-center">
                            <span class="block font-bold text-white text-sm">{{ accounts.filter(a => a.type === 'bank').length }}</span>
                            <span class="text-[11px] text-emerald-100">Bank</span>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-2 text-center">
                            <span class="block font-bold text-white text-sm">{{ accounts.filter(a => a.type === 'ewallet').length }}</span>
                            <span class="text-[11px] text-emerald-100">E-Wallet</span>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-2 text-center">
                            <span class="block font-bold text-white text-sm">{{ accounts.filter(a => a.type === 'cash').length }}</span>
                            <span class="text-[11px] text-emerald-100">Tunai</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Accounts Grid -->
            <div>
                <div class="flex items-center justify-between mb-3.5">
                    <h2 class="text-sm sm:text-base font-bold text-slate-800">Daftar Rekening Saya</h2>
                    <span class="text-xs text-slate-500 font-medium">{{ accounts.length }} rekening terdaftar</span>
                </div>

                <div v-if="accounts.length === 0" class="bg-white rounded-3xl p-10 text-center border border-slate-200">
                    <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-3">
                        <Wallet class="w-7 h-7" />
                    </div>
                    <h3 class="text-sm sm:text-base font-bold text-slate-800">Belum Ada Rekening</h3>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-sm mx-auto">
                        Mulai mencatat keuangan dengan menambahkan dompet tunai, rekening bank, atau akun e-wallet Anda.
                    </p>
                    <button
                        @click="openCreateModal"
                        class="mt-4 inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition active:scale-95"
                    >
                        <Plus class="w-4 h-4" />
                        <span>Tambah Rekening Sekarang</span>
                    </button>
                </div>

                <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5 sm:gap-4">
                    <div
                        v-for="account in accounts"
                        :key="account.id"
                        class="group relative bg-white rounded-2xl p-4 sm:p-5 border transition-all duration-200 hover:shadow-md flex flex-col justify-between overflow-hidden"
                        :class="account.is_active ? 'border-slate-200/90 hover:border-slate-300' : 'border-slate-200/60 opacity-60 bg-slate-50/50'"
                    >
                        <!-- Top Accent Color Bar -->
                        <div
                            class="h-1.5 w-full absolute top-0 left-0"
                            :style="{ backgroundColor: account.color || '#10b981' }"
                        ></div>

                        <div>
                            <!-- Header with Color Accent & Icon -->
                            <div class="flex items-start justify-between gap-2.5 pt-1">
                                <div class="flex items-center gap-3 min-w-0 flex-1">
                                    <div
                                        class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 shadow-xs"
                                        :style="{
                                            backgroundColor: (account.color || '#10b981') + '18',
                                            color: account.color || '#10b981'
                                        }"
                                    >
                                        <DynamicIcon :name="account.icon || 'Wallet'" class="w-5 h-5" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <h3 class="text-sm sm:text-base font-bold text-slate-800 truncate">{{ account.name }}</h3>
                                        <div class="flex items-center gap-1.5 mt-0.5">
                                            <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-semibold bg-slate-100 text-slate-600">
                                                {{ getTypeLabel(account.type) }}
                                            </span>
                                            <span v-if="!account.is_active" class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                                Non-aktif
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons (Touch-friendly & Always Visible) -->
                                <div class="flex items-center gap-0.5 shrink-0">
                                    <button
                                        type="button"
                                        @click="openEditModal(account)"
                                        class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-emerald-700 hover:bg-emerald-50 active:scale-95 transition"
                                        title="Ubah Rekening"
                                        aria-label="Ubah Rekening"
                                    >
                                        <Pencil class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
                                    </button>
                                    <button
                                        type="button"
                                        @click="confirmDelete(account)"
                                        class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-rose-700 hover:bg-rose-50 active:scale-95 transition"
                                        title="Hapus Rekening"
                                        aria-label="Hapus Rekening"
                                    >
                                        <Trash2 class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
                                    </button>
                                </div>
                            </div>

                            <!-- Account Details: Monospace Account Number with 1-tap Copy -->
                            <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between gap-2 text-xs">
                                <div v-if="account.account_number" class="flex items-center gap-1.5 min-w-0">
                                    <span class="text-slate-400 text-[10px]">No:</span>
                                    <button
                                        type="button"
                                        @click="copyAccountNumber(account)"
                                        class="inline-flex items-center gap-1 font-mono font-medium text-slate-700 hover:text-emerald-700 bg-slate-50 hover:bg-emerald-50 px-2 py-0.5 rounded-md transition text-xs truncate"
                                        :title="copiedAccountId === account.id ? 'Tersalin!' : 'Salin Nomor Rekening'"
                                    >
                                        <span class="truncate">{{ account.account_number }}</span>
                                        <Check v-if="copiedAccountId === account.id" class="w-3 h-3 text-emerald-600 stroke-[3] shrink-0" />
                                        <Copy v-else class="w-3 h-3 text-slate-400 shrink-0" />
                                    </button>
                                </div>
                                <div v-else class="text-[10px] text-slate-400">
                                    Dompet Tunai / Akun
                                </div>

                                <div v-if="account.description" class="text-[10px] text-slate-400 italic truncate max-w-[120px]">
                                    {{ account.description }}
                                </div>
                            </div>
                        </div>

                        <!-- Balance Section -->
                        <div class="mt-3 pt-2.5 border-t border-slate-100">
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                Saldo Saat Ini
                            </div>
                            <div
                                class="text-xl sm:text-2xl font-black font-mono tracking-tight mt-0.5"
                                :class="Number(account.current_balance) < 0 ? 'text-rose-600' : 'text-slate-900'"
                            >
                                {{ formatRupiah(account.current_balance) }}
                            </div>
                            <div class="mt-0.5 text-[10px] text-slate-400">
                                Saldo awal: {{ formatRupiah(account.initial_balance) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add / Edit Modal -->
        <Modal :show="isModalOpen" @close="closeModal" max-width="lg">
            <div class="p-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <h3 class="text-lg font-bold text-slate-800">
                        {{ isEditing ? 'Ubah Rekening' : 'Tambah Rekening Baru' }}
                    </h3>
                    <button @click="closeModal" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                        ✕
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="mt-4 space-y-4">
                    <!-- Name -->
                    <div>
                        <InputLabel for="name" value="Nama Rekening *" />
                        <TextInput
                            id="name"
                            v-model="form.name"
                            type="text"
                            class="mt-1 block w-full rounded-xl"
                            placeholder="Nama rekening"
                            required
                        />
                        <InputError :message="form.errors.name" class="mt-1" />
                    </div>

                    <!-- Type & Icon -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="type" value="Tipe Rekening *" />
                            <select
                                id="type"
                                v-model="form.type"
                                class="mt-1 block w-full rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 text-sm shadow-sm"
                            >
                                <option v-for="t in accountTypes" :key="t.value" :value="t.value">
                                    {{ t.label }}
                                </option>
                            </select>
                            <InputError :message="form.errors.type" class="mt-1" />
                        </div>

                        <div>
                            <InputLabel for="account_number" value="No. Rekening" />
                            <TextInput
                                id="account_number"
                                v-model="form.account_number"
                                type="text"
                                class="mt-1 block w-full rounded-xl"
                                placeholder="Opsional"
                            />
                            <InputError :message="form.errors.account_number" class="mt-1" />
                        </div>
                    </div>

                    <!-- Initial Balance -->
                    <div>
                        <InputLabel for="initial_balance" value="Saldo Awal *" />
                        <MoneyInput
                            id="initial_balance"
                            v-model="form.initial_balance"
                            class="mt-1 block w-full rounded-xl font-mono"
                            placeholder="0"
                            required
                        />
                        <p v-if="form.initial_balance !== '' && Number(form.initial_balance) > 0" class="mt-1 text-xs font-semibold text-slate-500 font-mono">
                            {{ formatRupiah(form.initial_balance) }}
                        </p>
                        <InputError :message="form.errors.initial_balance" class="mt-1" />
                    </div>

                    <!-- Color & Icon Pickers -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                        <div>
                            <InputLabel value="Pilih Warna" class="mb-1.5" />
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="c in availableColors"
                                    :key="c"
                                    type="button"
                                    @click="form.color = c"
                                    class="w-7 h-7 rounded-full flex items-center justify-center transition transform hover:scale-110 shadow-sm"
                                    :style="{ backgroundColor: c }"
                                >
                                    <Check v-if="form.color === c" class="w-4 h-4 text-white stroke-[3]" />
                                </button>
                            </div>
                        </div>

                        <div>
                            <InputLabel value="Pilih Ikon" class="mb-1.5" />
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="ic in availableIcons"
                                    :key="ic"
                                    type="button"
                                    @click="form.icon = ic"
                                    class="w-8 h-8 rounded-lg flex items-center justify-center border transition"
                                    :class="form.icon === ic ? 'border-emerald-600 bg-emerald-50 text-emerald-700' : 'border-slate-200 text-slate-600 hover:bg-slate-50'"
                                >
                                    <DynamicIcon :name="ic" class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <InputLabel for="description" value="Catatan" />
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="2"
                            class="mt-1 block w-full rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 text-sm shadow-sm"
                            placeholder="Catatan (opsional)"
                        ></textarea>
                        <InputError :message="form.errors.description" class="mt-1" />
                    </div>

                    <!-- Active Toggle -->
                    <div class="flex items-center pt-1">
                        <label class="flex items-center cursor-pointer">
                            <Checkbox name="is_active" v-model:checked="form.is_active" class="text-emerald-600 rounded" />
                            <span class="ms-2 text-sm text-slate-700 font-medium">Rekening Aktif</span>
                        </label>
                    </div>

                    <!-- Footer Actions -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                        <SecondaryButton type="button" @click="closeModal">
                            Batal
                        </SecondaryButton>
                        <PrimaryButton :disabled="form.processing" class="bg-emerald-600 hover:bg-emerald-700">
                            {{ form.processing ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Tambah Rekening') }}
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
                        <h3 class="text-base font-bold text-slate-800">Hapus Rekening?</h3>
                        <p class="text-xs text-slate-500">Tindakan ini tidak dapat dibatalkan</p>
                    </div>
                </div>

                <p class="text-sm text-slate-600 leading-relaxed">
                    Apakah Anda yakin ingin menghapus rekening <strong class="text-slate-900">{{ accountToDelete?.name }}</strong>?
                </p>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <SecondaryButton @click="closeDeleteModal">
                        Batal
                    </SecondaryButton>
                    <DangerButton @click="executeDelete">
                        Ya, Hapus Rekening
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
