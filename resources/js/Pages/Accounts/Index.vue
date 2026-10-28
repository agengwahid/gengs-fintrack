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
import Checkbox from '@/Components/Checkbox.vue';
import { formatRupiah } from '@/Utils/formatters';
import {
    Plus,
    Wallet,
    Building2,
    Smartphone,
    CreditCard,
    PiggyBank,
    Coins,
    Landmark,
    Pencil,
    Trash2,
    CheckCircle2,
    AlertTriangle,
    Check
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

const accountTypes = [
    { value: 'cash', label: 'Uang Tunai', icon: 'Wallet' },
    { value: 'bank', label: 'Rekening Bank', icon: 'Building2' },
    { value: 'ewallet', label: 'E-Wallet', icon: 'Smartphone' },
    { value: 'investment', label: 'Investasi', icon: 'PiggyBank' },
    { value: 'credit_card', label: 'Kartu Kredit', icon: 'CreditCard' },
    { value: 'other', label: 'Lainnya', icon: 'Coins' },
];

const availableIcons = [
    'Wallet', 'Building2', 'Smartphone', 'CreditCard', 'PiggyBank', 'Coins', 'Landmark'
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
            <button
                @click="openCreateModal"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm shadow-emerald-600/30 transition transform hover:-translate-y-0.5"
            >
                <Plus class="w-4 h-4" />
                <span>Tambah Rekening</span>
            </button>
        </template>

        <div class="space-y-6">
            <!-- Summary Banner -->
            <div class="bg-gradient-to-br from-emerald-600 via-teal-600 to-teal-800 rounded-3xl p-6 sm:p-8 text-white shadow-xl shadow-emerald-950/10 relative overflow-hidden">
                <div class="absolute right-0 bottom-0 opacity-10 translate-x-8 translate-y-8 pointer-events-none">
                    <Wallet class="w-64 h-64" />
                </div>

                <div class="relative z-10">
                    <div class="flex items-center gap-2 text-emerald-100 text-xs font-semibold uppercase tracking-wider">
                        <span>Total Kekayaan Bersih</span>
                    </div>
                    <div class="text-3xl sm:text-4xl font-black mt-2 tracking-tight">
                        {{ formatRupiah(totalBalance) }}
                    </div>
                    <p class="mt-2 text-xs sm:text-sm text-emerald-100/90 max-w-lg">
                        Akumulasi saldo dari seluruh rekening & dompet aktif Anda.
                    </p>

                    <div class="mt-6 flex flex-wrap gap-4 pt-4 border-t border-white/10 text-xs">
                        <div class="bg-white/10 backdrop-blur-sm px-3.5 py-1.5 rounded-xl flex items-center gap-2">
                            <span class="font-bold text-white">{{ accounts.filter(a => a.is_active).length }}</span>
                            <span class="text-emerald-100">Rekening Aktif</span>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm px-3.5 py-1.5 rounded-xl flex items-center gap-2">
                            <span class="font-bold text-white">{{ accounts.filter(a => a.type === 'bank').length }}</span>
                            <span class="text-emerald-100">Bank</span>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm px-3.5 py-1.5 rounded-xl flex items-center gap-2">
                            <span class="font-bold text-white">{{ accounts.filter(a => a.type === 'ewallet').length }}</span>
                            <span class="text-emerald-100">E-Wallet</span>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm px-3.5 py-1.5 rounded-xl flex items-center gap-2">
                            <span class="font-bold text-white">{{ accounts.filter(a => a.type === 'cash').length }}</span>
                            <span class="text-emerald-100">Tunai</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Accounts Grid -->
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base sm:text-lg font-bold text-slate-800">Daftar Rekening Saya</h2>
                    <span class="text-xs text-slate-500 font-medium">{{ accounts.length }} rekening terdaftar</span>
                </div>

                <div v-if="accounts.length === 0" class="bg-white rounded-3xl p-12 text-center border border-slate-200">
                    <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-4">
                        <Wallet class="w-8 h-8" />
                    </div>
                    <h3 class="text-base font-bold text-slate-800">Belum Ada Rekening</h3>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-sm mx-auto">
                        Mulai mencatat keuangan dengan menambahkan dompet tunai, rekening bank, atau akun e-wallet Anda.
                    </p>
                    <button
                        @click="openCreateModal"
                        class="mt-5 inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition"
                    >
                        <Plus class="w-4 h-4" />
                        <span>Tambah Rekening Sekarang</span>
                    </button>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    <div
                        v-for="account in accounts"
                        :key="account.id"
                        class="group relative bg-white rounded-2xl p-5 border transition-all duration-200 hover:shadow-lg flex flex-col justify-between"
                        :class="account.is_active ? 'border-slate-200 hover:border-slate-300' : 'border-slate-200/60 opacity-60 bg-slate-50/50'"
                    >
                        <!-- Header with Color Accent & Icon -->
                        <div>
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-12 h-12 rounded-xl flex items-center justify-center text-white shadow-sm shrink-0"
                                        :style="{ backgroundColor: account.color || '#10b981' }"
                                    >
                                        <DynamicIcon :name="account.icon || 'Wallet'" class="w-6 h-6" />
                                    </div>
                                    <div class="min-w-0">
                                        <h3 class="text-base font-bold text-slate-800 truncate">{{ account.name }}</h3>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-slate-100 text-slate-600">
                                                {{ getTypeLabel(account.type) }}
                                            </span>
                                            <span v-if="!account.is_active" class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-medium bg-amber-50 text-amber-600 border border-amber-200">
                                                Non-aktif
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex items-center gap-1 opacity-90 group-hover:opacity-100 transition">
                                    <button
                                        @click="openEditModal(account)"
                                        class="p-2 rounded-lg text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 transition"
                                        title="Ubah Rekening"
                                    >
                                        <Pencil class="w-4 h-4" />
                                    </button>
                                    <button
                                        @click="confirmDelete(account)"
                                        class="p-2 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition"
                                        title="Hapus Rekening"
                                    >
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>

                            <!-- Account Details -->
                            <div class="mt-4 pt-3 border-t border-slate-100 space-y-1">
                                <div v-if="account.account_number" class="text-xs text-slate-500 flex items-center justify-between">
                                    <span>No. Akun/Rekening</span>
                                    <span class="font-mono font-medium text-slate-700">{{ account.account_number }}</span>
                                </div>
                                <div v-if="account.description" class="text-xs text-slate-500 italic truncate">
                                    {{ account.description }}
                                </div>
                            </div>
                        </div>

                        <!-- Balance Section -->
                        <div class="mt-4 pt-3 border-t border-slate-100">
                            <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
                                Saldo Saat Ini
                            </div>
                            <div
                                class="text-xl sm:text-2xl font-black tracking-tight mt-0.5"
                                :class="Number(account.current_balance) < 0 ? 'text-rose-600' : 'text-slate-900'"
                            >
                                {{ formatRupiah(account.current_balance) }}
                            </div>
                            <div class="mt-1 text-[11px] text-slate-400">
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
                            placeholder="Contoh: BCA Tabungan, GoPay Utama"
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
                            <InputLabel for="account_number" value="No. Rekening / HP (Opsional)" />
                            <TextInput
                                id="account_number"
                                v-model="form.account_number"
                                type="text"
                                class="mt-1 block w-full rounded-xl"
                                placeholder="1234567890"
                            />
                            <InputError :message="form.errors.account_number" class="mt-1" />
                        </div>
                    </div>

                    <!-- Initial Balance -->
                    <div>
                        <InputLabel for="initial_balance" value="Saldo Awal (Rp) *" />
                        <TextInput
                            id="initial_balance"
                            v-model="form.initial_balance"
                            type="number"
                            step="any"
                            class="mt-1 block w-full rounded-xl font-mono"
                            placeholder="0"
                            required
                        />
                        <p class="text-[11px] text-slate-500 mt-1">
                            Saldo awal saat rekening ini pertama kali didaftarkan.
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
                        <InputLabel for="description" value="Catatan / Deskripsi" />
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="2"
                            class="mt-1 block w-full rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 text-sm shadow-sm"
                            placeholder="Catatan tambahan rekening ini..."
                        ></textarea>
                        <InputError :message="form.errors.description" class="mt-1" />
                    </div>

                    <!-- Active Toggle -->
                    <div class="flex items-center pt-1">
                        <label class="flex items-center cursor-pointer">
                            <Checkbox name="is_active" v-model:checked="form.is_active" class="text-emerald-600 rounded" />
                            <span class="ms-2 text-sm text-slate-700 font-medium">Rekening Aktif Digunakan</span>
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
