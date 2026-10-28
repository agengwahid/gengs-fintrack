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
import {
    Tags,
    Plus,
    Pencil,
    Trash2,
    TrendingDown,
    TrendingUp,
    AlertTriangle,
    Check,
    Folder
} from '@lucide/vue';

const props = defineProps({
    expenseCategories: {
        type: Array,
        default: () => [],
    },
    incomeCategories: {
        type: Array,
        default: () => [],
    },
});

const activeTab = ref('expense'); // 'expense' | 'income'
const isModalOpen = ref(false);
const isEditing = ref(false);
const editingCategoryId = ref(null);
const isDeleteModalOpen = ref(false);
const categoryToDelete = ref(null);

const availableIcons = [
    'Utensils', 'Car', 'ShoppingCart', 'ShoppingBag', 'Zap',
    'Gamepad2', 'HeartPulse', 'GraduationCap', 'Home', 'Briefcase',
    'Gift', 'Laptop', 'TrendingUp', 'Coins', 'Coffee',
    'Plane', 'Film', 'Book', 'Dumbbell', 'Wallet', 'MoreHorizontal'
];

const availableColors = [
    '#ef4444', '#f97316', '#f59e0b', '#eab308', '#10b981',
    '#06b6d4', '#3b82f6', '#6366f1', '#8b5cf6', '#ec4899', '#64748b'
];

const form = useForm({
    name: '',
    type: 'expense',
    color: '#ef4444',
    icon: 'ShoppingBag',
    description: '',
});

const displayedCategories = computed(() => {
    return activeTab.value === 'expense' ? props.expenseCategories : props.incomeCategories;
});

function openCreateModal(defaultType = null) {
    isEditing.value = false;
    editingCategoryId.value = null;
    form.reset();
    form.clearErrors();
    form.type = defaultType || activeTab.value;
    form.color = form.type === 'expense' ? '#ef4444' : '#10b981';
    form.icon = form.type === 'expense' ? 'ShoppingBag' : 'Briefcase';
    isModalOpen.value = true;
}

function openEditModal(category) {
    isEditing.value = true;
    editingCategoryId.value = category.id;
    form.clearErrors();
    form.name = category.name;
    form.type = category.type;
    form.color = category.color || (category.type === 'expense' ? '#ef4444' : '#10b981');
    form.icon = category.icon || 'Tags';
    form.description = category.description || '';
    isModalOpen.value = true;
}

function closeModal() {
    isModalOpen.value = false;
    form.reset();
    form.clearErrors();
}

function submitForm() {
    if (isEditing.value) {
        form.put(route('categories.update', editingCategoryId.value), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('categories.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
}

function confirmDelete(category) {
    categoryToDelete.value = category;
    isDeleteModalOpen.value = true;
}

function closeDeleteModal() {
    isDeleteModalOpen.value = false;
    categoryToDelete.value = null;
}

function executeDelete() {
    if (!categoryToDelete.value) return;

    router.delete(route('categories.destroy', categoryToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => closeDeleteModal(),
    });
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Kategori Transaksi" />

        <template #header>
            <div class="flex items-center gap-2">
                <Tags class="w-6 h-6 text-emerald-600" />
                <span>Kategori Transaksi</span>
            </div>
        </template>

        <template #actions>
            <button
                @click="openCreateModal()"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm shadow-emerald-600/30 transition transform hover:-translate-y-0.5"
            >
                <Plus class="w-4 h-4" />
                <span>Tambah Kategori</span>
            </button>
        </template>

        <div class="space-y-6">
            <!-- Tabs -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-2 sm:p-2.5 rounded-2xl border border-slate-200">
                <div class="flex items-center gap-1.5 w-full sm:w-auto">
                    <button
                        @click="activeTab = 'expense'"
                        class="flex-1 sm:flex-none flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold transition-all"
                        :class="activeTab === 'expense'
                            ? 'bg-rose-50 text-rose-700 shadow-sm border border-rose-200/60'
                            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                    >
                        <TrendingDown class="w-4 h-4 text-rose-600" />
                        <span>Kategori Pengeluaran</span>
                        <span class="ml-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-700">
                            {{ expenseCategories.length }}
                        </span>
                    </button>

                    <button
                        @click="activeTab = 'income'"
                        class="flex-1 sm:flex-none flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold transition-all"
                        :class="activeTab === 'income'
                            ? 'bg-emerald-50 text-emerald-700 shadow-sm border border-emerald-200/60'
                            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                    >
                        <TrendingUp class="w-4 h-4 text-emerald-600" />
                        <span>Kategori Pemasukan</span>
                        <span class="ml-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                            {{ incomeCategories.length }}
                        </span>
                    </button>
                </div>

                <div class="text-xs text-slate-500 px-3 hidden sm:block">
                    Kelola label untuk mengelompokkan arus kas Anda
                </div>
            </div>

            <!-- Categories List -->
            <div>
                <div v-if="displayedCategories.length === 0" class="bg-white rounded-3xl p-12 text-center border border-slate-200">
                    <div
                        class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4"
                        :class="activeTab === 'expense' ? 'bg-rose-50 text-rose-600' : 'bg-emerald-50 text-emerald-600'"
                    >
                        <Folder class="w-8 h-8" />
                    </div>
                    <h3 class="text-base font-bold text-slate-800">
                        Belum Ada Kategori {{ activeTab === 'expense' ? 'Pengeluaran' : 'Pemasukan' }}
                    </h3>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-sm mx-auto">
                        Tambahkan kategori agar Anda dapat melacak ke mana saja uang Anda mengalir.
                    </p>
                    <button
                        @click="openCreateModal()"
                        class="mt-5 inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition"
                    >
                        <Plus class="w-4 h-4" />
                        <span>Buat Kategori Pertama</span>
                    </button>
                </div>

                <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <div
                        v-for="cat in displayedCategories"
                        :key="cat.id"
                        class="group bg-white rounded-2xl p-4 border border-slate-200 hover:border-slate-300 hover:shadow-md transition flex items-center justify-between gap-3"
                    >
                        <div class="flex items-center gap-3 min-w-0">
                            <div
                                class="w-11 h-11 rounded-xl flex items-center justify-center text-white shrink-0 shadow-sm"
                                :style="{ backgroundColor: cat.color || '#64748b' }"
                            >
                                <DynamicIcon :name="cat.icon || 'Tags'" class="w-5 h-5" />
                            </div>
                            <div class="min-w-0">
                                <h4 class="text-sm font-bold text-slate-800 truncate">{{ cat.name }}</h4>
                                <p class="text-[11px] text-slate-500 truncate mt-0.5">
                                    {{ cat.description || (cat.type === 'expense' ? 'Pengeluaran' : 'Pemasukan') }}
                                </p>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-1 opacity-90 group-hover:opacity-100 transition shrink-0">
                            <button
                                @click="openEditModal(cat)"
                                class="p-1.5 rounded-lg text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 transition"
                                title="Ubah Kategori"
                            >
                                <Pencil class="w-4 h-4" />
                            </button>
                            <button
                                @click="confirmDelete(cat)"
                                class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition"
                                title="Hapus Kategori"
                            >
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add / Edit Modal -->
        <Modal :show="isModalOpen" @close="closeModal" max-width="md">
            <div class="p-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <h3 class="text-lg font-bold text-slate-800">
                        {{ isEditing ? 'Ubah Kategori' : 'Tambah Kategori Baru' }}
                    </h3>
                    <button @click="closeModal" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                        ✕
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="mt-4 space-y-4">
                    <!-- Type Selector -->
                    <div>
                        <InputLabel value="Tipe Kategori *" class="mb-1" />
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                @click="form.type = 'expense'; if (!isEditing) form.color = '#ef4444';"
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
                                @click="form.type = 'income'; if (!isEditing) form.color = '#10b981';"
                                class="flex items-center justify-center gap-2 py-2 px-3 rounded-xl text-xs font-bold border transition"
                                :class="form.type === 'income'
                                    ? 'bg-emerald-50 border-emerald-300 text-emerald-700 shadow-sm'
                                    : 'border-slate-200 text-slate-600 hover:bg-slate-50'"
                            >
                                <TrendingUp class="w-4 h-4 text-emerald-600" />
                                <span>Pemasukan</span>
                            </button>
                        </div>
                    </div>

                    <!-- Name -->
                    <div>
                        <InputLabel for="cat-name" value="Nama Kategori *" />
                        <TextInput
                            id="cat-name"
                            v-model="form.name"
                            type="text"
                            class="mt-1 block w-full rounded-xl"
                            placeholder="Contoh: Makanan & Minuman, Bonus, dll"
                            required
                        />
                        <InputError :message="form.errors.name" class="mt-1" />
                    </div>

                    <!-- Color Pickers -->
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

                    <!-- Icon Pickers -->
                    <div>
                        <InputLabel value="Pilih Ikon" class="mb-1.5" />
                        <div class="flex flex-wrap gap-2 max-h-36 overflow-y-auto p-1 border border-slate-200 rounded-xl">
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

                    <!-- Description -->
                    <div>
                        <InputLabel for="cat-desc" value="Catatan / Keterangan (Opsional)" />
                        <TextInput
                            id="cat-desc"
                            v-model="form.description"
                            type="text"
                            class="mt-1 block w-full rounded-xl"
                            placeholder="Keterangan singkat..."
                        />
                        <InputError :message="form.errors.description" class="mt-1" />
                    </div>

                    <!-- Footer Actions -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                        <SecondaryButton type="button" @click="closeModal">
                            Batal
                        </SecondaryButton>
                        <PrimaryButton :disabled="form.processing" class="bg-emerald-600 hover:bg-emerald-700">
                            {{ form.processing ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Tambah Kategori') }}
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
                        <h3 class="text-base font-bold text-slate-800">Hapus Kategori?</h3>
                        <p class="text-xs text-slate-500">Tindakan ini tidak dapat dibatalkan</p>
                    </div>
                </div>

                <p class="text-sm text-slate-600 leading-relaxed">
                    Apakah Anda yakin ingin menghapus kategori <strong class="text-slate-900">{{ categoryToDelete?.name }}</strong>?
                </p>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <SecondaryButton @click="closeDeleteModal">
                        Batal
                    </SecondaryButton>
                    <DangerButton @click="executeDelete">
                        Ya, Hapus Kategori
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
