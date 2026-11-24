<script setup>
import { ref } from 'vue';
import { Head, router, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import HeaderActionButton from '@/Components/HeaderActionButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import { formatDateIndo, formatRupiah } from '@/Utils/formatters';
import {
    FileSpreadsheet,
    CheckCircle2,
    AlertCircle,
    Clock,
    RefreshCw,
    ExternalLink,
    Copy,
    ChevronDown,
    Layers,
    Send,
    Settings2,
    Upload,
    Check,
    Trash2,
    KeyRound,
} from '@lucide/vue';

const props = defineProps({
    config: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        required: true,
    },
    logs: {
        type: Object,
        required: true,
    },
});

const isTesting = ref(false);
const isSyncingAll = ref(false);
const showSettings = ref(false);
const showAdvancedCreds = ref(false);
const copiedEmail = ref(false);
const isDeleteCredsModalOpen = ref(false);
const fileInput = ref(null);
const selectedFileName = ref('');

const settingsForm = useForm({
    is_enabled: props.config.enabled ?? true,
    spreadsheet_id: props.config.spreadsheet_id || '',
    credentials_file: null,
    credentials_json: '',
});

function handleSpreadsheetInput() {
    if (settingsForm.spreadsheet_id && settingsForm.spreadsheet_id.trim() !== '') {
        settingsForm.is_enabled = true;
    }
}

function handleFileChange(event) {
    const file = event.target.files[0];
    if (file) {
        selectedFileName.value = file.name;
        settingsForm.credentials_file = file;
        settingsForm.is_enabled = true;
    }
}

function submitSettings() {
    settingsForm.post(route('sheets.settings.update'), {
        preserveScroll: true,
        onSuccess: () => {
            showSettings.value = false;
            selectedFileName.value = '';
            if (fileInput.value) fileInput.value.value = '';
        },
    });
}

function deleteCredentials() {
    router.delete(route('sheets.credentials.destroy'), {
        preserveScroll: true,
        onSuccess: () => {
            isDeleteCredsModalOpen.value = false;
        },
    });
}

function testConnection() {
    isTesting.value = true;
    router.post(route('sheets.test-connection'), {}, {
        preserveScroll: true,
        onFinish: () => {
            isTesting.value = false;
        },
    });
}

function syncAll() {
    if (!confirm('Apakah Anda yakin ingin menyinkronkan seluruh riwayat transaksi ke Google Sheets?')) {
        return;
    }
    isSyncingAll.value = true;
    router.post(route('sheets.sync-all'), {}, {
        preserveScroll: true,
        onFinish: () => {
            isSyncingAll.value = false;
        },
    });
}

function retryLog(logId) {
    router.post(route('sheets.retry', logId), {}, {
        preserveScroll: true,
    });
}

function copyEmail(email) {
    if (!email) return;
    navigator.clipboard.writeText(email);
    copiedEmail.value = true;
    setTimeout(() => {
        copiedEmail.value = false;
    }, 2500);
}

function getActionLabel(action) {
    switch (action) {
        case 'create':
            return 'Tambah Transaksi';
        case 'update':
            return 'Perbarui Transaksi';
        case 'delete':
            return 'Hapus Transaksi';
        case 'sync_all':
            return 'Sinkronisasi Massal';
        default:
            return action;
    }
}
</script>

<template>
    <Head title="Integrasi Google Sheets" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-2">
                <FileSpreadsheet class="w-6 h-6 text-emerald-600" />
                <span>Integrasi Google Sheets</span>
            </div>
        </template>

        <template #actions>
            <div class="flex items-center gap-1.5 sm:gap-2">
                <HeaderActionButton
                    @click="showSettings = !showSettings"
                    label="Pengaturan"
                    short-label="Atur"
                    variant="secondary"
                    :icon="Settings2"
                    icon-class="text-emerald-600"
                />

                <HeaderActionButton
                    @click="testConnection"
                    :disabled="isTesting"
                    :label="isTesting ? 'Menguji...' : 'Uji Koneksi'"
                    :short-label="isTesting ? 'Uji...' : 'Uji'"
                    variant="secondary"
                    :icon="RefreshCw"
                    :icon-class="isTesting ? 'animate-spin text-emerald-600' : 'text-slate-600'"
                />

                <HeaderActionButton
                    @click="syncAll"
                    :disabled="isSyncingAll || !config.is_ready"
                    :label="isSyncingAll ? 'Memproses...' : 'Sinkronkan Semua'"
                    :short-label="isSyncingAll ? 'Proses...' : 'Sinkron'"
                    variant="primary"
                    :icon="Send"
                    :icon-class="isSyncingAll ? 'animate-pulse' : ''"
                />
            </div>
        </template>

        <div class="space-y-6">
            <!-- Form Pengaturan Interaktif (Power UI) -->
            <div
                v-show="showSettings"
                class="bg-white rounded-2xl p-5 sm:p-6 border border-emerald-200 shadow-md ring-1 ring-emerald-500/10 transition-all duration-300"
            >
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <Settings2 class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Pengaturan Google Sheets</h3>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="showSettings = false"
                        class="text-xs text-slate-400 hover:text-slate-600 p-1"
                    >
                        Tutup
                    </button>
                </div>

                <form @submit.prevent="submitSettings" class="mt-5 space-y-5">
                    <!-- Toggle Switch: Aktifkan Sinkronisasi -->
                    <div class="flex items-center justify-between p-4 rounded-xl bg-slate-50 border border-slate-200/80">
                        <label for="is_enabled" class="text-xs font-bold text-slate-900 cursor-pointer">
                            Aktifkan Sinkronisasi Otomatis
                        </label>

                        <label class="relative inline-flex items-center cursor-pointer">
                            <input
                                id="is_enabled"
                                type="checkbox"
                                v-model="settingsForm.is_enabled"
                                class="sr-only peer"
                            />
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                        </label>
                    </div>

                    <!-- Input Utama: Spreadsheet ID / URL Lengkap -->
                    <div>
                        <InputLabel for="spreadsheet_id" value="Tautan atau ID Google Spreadsheet" class="font-bold text-xs" />
                        <TextInput
                            id="spreadsheet_id"
                            type="text"
                            v-model="settingsForm.spreadsheet_id"
                            @input="handleSpreadsheetInput"
                            class="mt-1 block w-full text-xs font-mono"
                            placeholder="Tempel tautan https://docs.google.com/spreadsheets/d/... atau ID"
                        />
                        <InputError class="mt-1.5" :message="settingsForm.errors.spreadsheet_id" />
                    </div>

                    <!-- Bagian Mode Lanjutan (Power User) -->
                    <div class="pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="showAdvancedCreds = !showAdvancedCreds"
                            class="text-xs font-semibold text-slate-500 hover:text-slate-800 inline-flex items-center gap-1.5 transition py-1"
                        >
                            <ChevronDown class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-180': showAdvancedCreds }" />
                            <span>{{ config.has_custom_credentials ? 'Kelola Kredensial Pribadi' : 'Gunakan Kunci Google Cloud Sendiri (Opsional)' }}</span>
                        </button>

                        <div v-show="showAdvancedCreds || !config.has_global_credentials" class="mt-3 p-4 bg-slate-50 rounded-xl border border-slate-200/80 space-y-3">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-bold text-slate-800">Kredensial Service Account Pribadi (File JSON)</h4>
                                <span v-if="config.has_custom_credentials" class="text-xs text-emerald-600 font-bold inline-flex items-center gap-1">
                                    <CheckCircle2 class="w-3.5 h-3.5" />
                                    Kunci Pribadi Aktif
                                </span>
                            </div>

                            <div class="flex flex-col sm:flex-row items-center gap-3">
                                <label class="w-full sm:w-auto flex-1 cursor-pointer flex items-center justify-center gap-2 px-4 py-3 border-2 border-dashed border-slate-300 rounded-xl hover:border-slate-500 hover:bg-slate-50 transition text-xs font-medium text-slate-600 bg-white">
                                    <Upload class="w-4 h-4 text-slate-600" />
                                    <span>{{ selectedFileName || 'Pilih File JSON Kredensial' }}</span>
                                    <input
                                        ref="fileInput"
                                        type="file"
                                        accept=".json,application/json"
                                        @change="handleFileChange"
                                        class="hidden"
                                    />
                                </label>

                                <button
                                    v-if="config.has_custom_credentials"
                                    type="button"
                                    @click="isDeleteCredsModalOpen = true"
                                    class="px-3 py-2.5 rounded-xl text-xs font-semibold text-rose-600 bg-rose-50 hover:bg-rose-100 transition inline-flex items-center gap-1 shrink-0"
                                    title="Hapus file kredensial dari akun dan gunakan kredensial server"
                                >
                                    <Trash2 class="w-3.5 h-3.5" />
                                    <span>Hapus & Gunakan Server</span>
                                </button>
                            </div>

                            <InputError class="mt-1" :message="settingsForm.errors.credentials_file" />
                        </div>
                    </div>

                    <!-- Tombol Aksi Simpan -->
                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                        <SecondaryButton type="button" @click="showSettings = false">
                            Batal
                        </SecondaryButton>
                        <PrimaryButton :disabled="settingsForm.processing" class="!bg-emerald-600 hover:!bg-emerald-700">
                            {{ settingsForm.processing ? 'Menyimpan...' : 'Simpan Pengaturan' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>

            <!-- Configuration Status Card -->
            <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/80 shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-5 border-b border-slate-100">
                    <div class="flex items-start gap-4">
                        <div
                            class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 shadow-sm"
                            :class="config.is_ready ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600'"
                        >
                            <FileSpreadsheet class="w-6 h-6" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-bold text-slate-900">Google Spreadsheet FinTrack</h3>
                                <span
                                    class="text-[11px] font-bold px-2 py-0.5 rounded-full inline-flex items-center gap-1"
                                    :class="config.is_ready ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full" :class="config.is_ready ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                                    {{ config.is_ready ? 'Terkoneksi & Siap Digunakan' : (config.enabled ? 'Kredensial Belum Lengkap' : 'Integrasi Nonaktif') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <button
                            @click="showSettings = true"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-slate-50 hover:bg-slate-100 border border-slate-200 transition"
                        >
                            <Settings2 class="w-3.5 h-3.5 text-slate-500" />
                            <span>Ubah Pengaturan</span>
                        </button>

                        <a
                            v-if="config.spreadsheet_url"
                            :href="config.spreadsheet_url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 transition border border-emerald-200/60"
                        >
                            <span>Buka Spreadsheet</span>
                            <ExternalLink class="w-3.5 h-3.5" />
                        </a>
                    </div>
                </div>

                <!-- Parameters list -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-5">
                    <!-- Spreadsheet ID -->
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Spreadsheet ID</span>
                        <div class="mt-1 font-mono text-xs font-semibold text-slate-800 truncate" :title="config.spreadsheet_id || 'Belum diatur'">
                            {{ config.spreadsheet_id || 'Belum diisi (Klik Ubah Pengaturan)' }}
                        </div>
                    </div>

                    <!-- Service Account Email -->
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Service Account Email</span>
                        <div v-if="config.client_email" class="mt-1 flex items-center justify-between gap-2">
                            <span class="font-mono text-xs font-semibold text-slate-800 truncate" :title="config.client_email">
                                {{ config.client_email }}
                            </span>
                            <button
                                @click="copyEmail(config.client_email)"
                                class="p-1 text-slate-400 hover:text-emerald-600 transition shrink-0"
                                title="Salin Email Service Account"
                            >
                                <Check v-if="copiedEmail" class="w-3.5 h-3.5 text-emerald-600" />
                                <Copy v-else class="w-3.5 h-3.5" />
                            </button>
                        </div>
                        <div v-else class="mt-1 text-xs text-slate-400 italic">
                            Belum diunggah
                        </div>
                    </div>

                    <!-- Status Kredensial -->
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Status Kredensial</span>
                        <div class="mt-1 text-xs font-semibold flex items-center gap-1.5"
                            :class="config.credentials_exist ? 'text-emerald-600' : 'text-amber-600'"
                        >
                            <CheckCircle2 v-if="config.credentials_exist" class="w-4 h-4 shrink-0" />
                            <AlertCircle v-else class="w-4 h-4 shrink-0" />
                            <span v-if="config.has_custom_credentials">Kustom Pribadi (Terenkripsi)</span>
                            <span v-else-if="config.has_global_credentials">Bawaan Server (Siap Pakai)</span>
                            <span v-else>{{ config.credentials_exist ? 'Tersimpan Terenkripsi' : 'Belum Ada Kredensial' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Petunjuk Share ringkas jika email service account ada -->
                <div v-if="config.client_email" class="mt-4 p-3 rounded-xl bg-slate-50 border border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 text-xs">
                    <div class="flex items-center gap-2 min-w-0">
                        <KeyRound class="w-4 h-4 text-emerald-600 shrink-0" />
                        <span class="text-slate-600 shrink-0">Beri akses Editor ke:</span>
                        <code class="px-2 py-0.5 rounded bg-white font-mono text-[11px] font-bold text-slate-800 border border-slate-200 truncate select-all">
                            {{ config.client_email }}
                        </code>
                    </div>
                    <button
                        @click="copyEmail(config.client_email)"
                        class="text-xs font-semibold text-emerald-700 hover:text-emerald-800 transition inline-flex items-center gap-1 shrink-0 self-start sm:self-auto"
                    >
                        <Check v-if="copiedEmail" class="w-3.5 h-3.5 text-emerald-600" />
                        <Copy v-else class="w-3.5 h-3.5" />
                        <span>{{ copiedEmail ? 'Tersalin' : 'Salin Email' }}</span>
                    </button>
                </div>

                <!-- Alert jika Integrasi belum diaktifkan -->
                <div v-if="!config.enabled" class="mt-4 p-3.5 rounded-xl bg-amber-50 border border-amber-200/90 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-amber-800">
                    <div class="flex items-center gap-2.5">
                        <AlertCircle class="w-4 h-4 text-amber-600 shrink-0" />
                        <span>Integrasi nonaktif. Aktifkan sakelar di Pengaturan untuk mulai sinkronisasi otomatis.</span>
                    </div>
                    <button
                        type="button"
                        @click="showSettings = true"
                        class="px-3 py-1.5 bg-amber-600 text-white font-bold rounded-lg text-xs hover:bg-amber-700 transition shrink-0 self-start sm:self-auto"
                    >
                        Buka Pengaturan
                    </button>
                </div>
            </div>

            <!-- Stats Overview Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-sm">
                    <div class="flex items-center justify-between text-slate-500 text-xs font-semibold uppercase tracking-wider">
                        <span>Total Job</span>
                        <Layers class="w-4 h-4 text-slate-400" />
                    </div>
                    <div class="mt-2 text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                        {{ stats.total }}
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-sm">
                    <div class="flex items-center justify-between text-slate-500 text-xs font-semibold uppercase tracking-wider">
                        <span>Berhasil</span>
                        <CheckCircle2 class="w-4 h-4 text-emerald-600" />
                    </div>
                    <div class="mt-2 text-xl sm:text-2xl font-black text-emerald-600 tracking-tight">
                        {{ stats.success }}
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-sm">
                    <div class="flex items-center justify-between text-slate-500 text-xs font-semibold uppercase tracking-wider">
                        <span>Antrean</span>
                        <Clock class="w-4 h-4 text-sky-600" />
                    </div>
                    <div class="mt-2 text-xl sm:text-2xl font-black text-sky-600 tracking-tight">
                        {{ stats.pending }}
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-sm">
                    <div class="flex items-center justify-between text-slate-500 text-xs font-semibold uppercase tracking-wider">
                        <span>Gagal</span>
                        <AlertCircle class="w-4 h-4 text-rose-600" />
                    </div>
                    <div class="mt-2 text-xl sm:text-2xl font-black text-rose-600 tracking-tight">
                        {{ stats.failed }}
                    </div>
                </div>
            </div>

            <!-- Sync Logs Table -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm sm:text-base font-bold text-slate-900">Riwayat Sinkronisasi</h3>
                    </div>

                    <span class="text-xs text-slate-500 font-semibold">
                        {{ logs.total || 0 }} Aktivitas
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50/70 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-100">
                            <tr>
                                <th class="py-3 px-4">Waktu</th>
                                <th class="py-3 px-4">Aksi</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4">Detail Transaksi</th>
                                <th class="py-3 px-4">Percobaan</th>
                                <th class="py-3 px-4">Pesan / Kesalahan</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr v-for="log in logs.data" :key="log.id" class="hover:bg-slate-50/60 transition">
                                <td class="py-3 px-4 font-medium text-slate-800 whitespace-nowrap">
                                    {{ formatDateIndo(log.created_at, 'datetime') }}
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <span class="font-bold text-slate-800">
                                        {{ getActionLabel(log.action) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold"
                                        :class="{
                                            'bg-emerald-100 text-emerald-700': log.status === 'success',
                                            'bg-rose-100 text-rose-700': log.status === 'failed',
                                            'bg-sky-100 text-sky-700': log.status === 'pending',
                                        }"
                                    >
                                        <CheckCircle2 v-if="log.status === 'success'" class="w-3 h-3" />
                                        <AlertCircle v-else-if="log.status === 'failed'" class="w-3 h-3" />
                                        <Clock v-else class="w-3 h-3" />
                                        <span>{{ log.status === 'success' ? 'Berhasil' : (log.status === 'failed' ? 'Gagal' : 'Antrean') }}</span>
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <div v-if="log.transaction">
                                        <div class="font-bold text-slate-800">
                                            {{ log.transaction.notes || log.transaction.category?.name || 'Transaksi' }}
                                        </div>
                                        <div class="text-[11px] text-slate-400">
                                            {{ formatRupiah(log.transaction.amount) }} &bull; {{ log.transaction.account?.name }}
                                        </div>
                                    </div>
                                    <div v-else-if="log.action === 'sync_all'" class="text-slate-500 italic">
                                        Seluruh transaksi
                                    </div>
                                    <div v-else class="text-slate-400 text-[11px]">
                                        ID: <span class="font-mono">{{ log.transaction_id ? log.transaction_id.substring(0, 8) + '...' : '-' }}</span>
                                    </div>
                                </td>
                                <td class="py-3 px-4 font-semibold text-slate-600">
                                    {{ log.attempts }}x
                                </td>
                                <td class="py-3 px-4 max-w-xs truncate" :title="log.last_error">
                                    <span v-if="log.last_error" class="text-rose-600 text-[11px] font-medium">
                                        {{ log.last_error }}
                                    </span>
                                    <span v-else-if="log.status === 'success'" class="text-emerald-600 text-[11px]">
                                        Tersinkron pada {{ formatDateIndo(log.synced_at, 'datetime') }}
                                    </span>
                                    <span v-else class="text-slate-400 text-[11px]">
                                        Dalam antrean worker...
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right whitespace-nowrap">
                                    <button
                                        v-if="log.status === 'failed'"
                                        @click="retryLog(log.id)"
                                        class="px-2.5 py-1 rounded-lg text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 transition inline-flex items-center gap-1"
                                        title="Coba sinkronkan ulang"
                                    >
                                        <RefreshCw class="w-3 h-3" />
                                        <span>Retry</span>
                                    </button>
                                    <span v-else class="text-slate-300">-</span>
                                </td>
                            </tr>
                            <tr v-if="!logs.data || logs.data.length === 0">
                                <td colspan="7" class="py-10 text-center text-slate-400">
                                    Belum ada riwayat sinkronisasi.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div v-if="logs.links && logs.links.length > 3" class="p-4 border-t border-slate-100 flex items-center justify-between">
                    <div class="text-xs text-slate-500">
                        Halaman {{ logs.current_page }} dari {{ logs.last_page }}
                    </div>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="(link, i) in logs.links"
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

        <!-- Modal Konfirmasi Hapus Kunci -->
        <Modal :show="isDeleteCredsModalOpen" @close="isDeleteCredsModalOpen = false" max-width="sm">
            <div class="p-6">
                <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center mx-auto mb-4">
                    <Trash2 class="w-6 h-6" />
                </div>
                <h3 class="text-lg font-bold text-slate-900 text-center">Hapus Kredensial?</h3>
                <p class="text-xs text-slate-500 text-center mt-2">
                    Kredensial Service Account yang tersimpan di akun Anda akan dihapus. Anda dapat mengunggah file kredensial baru kapan saja.
                </p>

                <div class="mt-6 flex items-center justify-center gap-3">
                    <SecondaryButton @click="isDeleteCredsModalOpen = false">
                        Batal
                    </SecondaryButton>
                    <DangerButton @click="deleteCredentials">
                        Hapus Kredensial
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
