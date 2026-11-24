<script setup>
import { computed } from 'vue';
import { ChevronLeft, ChevronRight, Calendar } from '@lucide/vue';

const props = defineProps({
    modelValue: {
        type: String,
        required: true, // Format: 'YYYY-MM'
    },
    allowAllMonths: {
        type: Boolean,
        default: false,
    },
    showTodayButton: {
        type: Boolean,
        default: true,
    },
    variant: {
        type: String,
        default: 'card', // 'card' (prominent banner) | 'inline' (compact toolbar)
    },
});

const emit = defineEmits(['update:modelValue', 'change']);

const months = [
    { value: '01', label: 'Januari' },
    { value: '02', label: 'Februari' },
    { value: '03', label: 'Maret' },
    { value: '04', label: 'April' },
    { value: '05', label: 'Mei' },
    { value: '06', label: 'Juni' },
    { value: '07', label: 'Juli' },
    { value: '08', label: 'Agustus' },
    { value: '09', label: 'September' },
    { value: '10', label: 'Oktober' },
    { value: '11', label: 'November' },
    { value: '12', label: 'Desember' },
];

// Current active year and month
const currentYear = computed(() => {
    if (!props.modelValue || !props.modelValue.includes('-')) {
        return new Date().getFullYear().toString();
    }
    return props.modelValue.split('-')[0];
});

const currentMonth = computed(() => {
    if (!props.modelValue || !props.modelValue.includes('-')) {
        return String(new Date().getMonth() + 1).padStart(2, '0');
    }
    return props.modelValue.split('-')[1];
});

// Generate a clean, balanced dynamic year window (~10-11 years)
const yearOptions = computed(() => {
    const today = new Date().getFullYear();
    const active = parseInt(currentYear.value, 10) || today;

    // Responsive window: 5 years past and 5 years ahead from current year
    let minYear = today - 5;
    let maxYear = today + 5;

    // Gracefully expand if active year is outside default window
    if (active < minYear) {
        minYear = active - 1;
    }
    if (active > maxYear) {
        maxYear = active + 2;
    }

    const years = [];
    for (let y = minYear; y <= maxYear; y++) {
        years.push(y.toString());
    }
    return years;
});

const isCurrentCalendarMonth = computed(() => {
    const now = new Date();
    const nowStr = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
    return props.modelValue === nowStr;
});

function updatePeriod(year, month) {
    const newPeriod = `${year}-${month}`;
    emit('update:modelValue', newPeriod);
    emit('change', newPeriod);
}

function handleMonthSelect(event) {
    updatePeriod(currentYear.value, event.target.value);
}

function handleYearSelect(event) {
    updatePeriod(event.target.value, currentMonth.value);
}

function prevMonth() {
    let y = parseInt(currentYear.value, 10);
    let m = parseInt(currentMonth.value, 10);

    if (isNaN(m) || m < 1) m = 1;
    m -= 1;
    if (m < 1) {
        m = 12;
        y -= 1;
    }

    updatePeriod(y.toString(), String(m).padStart(2, '0'));
}

function nextMonth() {
    let y = parseInt(currentYear.value, 10);
    let m = parseInt(currentMonth.value, 10);

    if (isNaN(m) || m > 12) m = 12;
    m += 1;
    if (m > 12) {
        m = 1;
        y += 1;
    }

    updatePeriod(y.toString(), String(m).padStart(2, '0'));
}

function goToCurrentMonth() {
    const now = new Date();
    const y = now.getFullYear().toString();
    const m = String(now.getMonth() + 1).padStart(2, '0');
    updatePeriod(y, m);
}
</script>

<template>
    <div
        class="flex items-center justify-between gap-1.5 w-full sm:w-auto"
        :class="variant === 'card' ? 'p-3 sm:p-4 bg-white rounded-2xl border border-slate-200/80 shadow-sm' : ''"
    >
        <div class="flex items-center gap-1 min-w-0">
            <!-- Tombol Prev -->
            <button
                type="button"
                @click="prevMonth"
                class="w-8 h-8 sm:w-9 sm:h-9 flex items-center justify-center rounded-xl text-slate-500 hover:text-slate-900 hover:bg-slate-100 active:scale-95 transition shrink-0"
                title="Bulan Sebelumnya"
                aria-label="Bulan Sebelumnya"
            >
                <ChevronLeft class="w-4 h-4 sm:w-5 sm:h-5" />
            </button>

            <!-- Box Pemilih Bulan dan Tahun -->
            <div class="flex items-center gap-1 px-2 sm:px-2.5 py-1.5 rounded-xl bg-slate-50 border border-slate-200/90 text-xs sm:text-sm font-bold text-slate-800 shrink-0">
                <select
                    :value="currentMonth"
                    @change="handleMonthSelect"
                    class="bg-transparent border-0 p-0 pr-3 sm:pr-4 text-xs sm:text-sm font-bold text-slate-800 focus:ring-0 cursor-pointer"
                    aria-label="Pilih Bulan"
                >
                    <option v-if="allowAllMonths" value="all">Semua</option>
                    <option v-for="m in months" :key="m.value" :value="m.value">
                        {{ m.label }}
                    </option>
                </select>

                <span class="text-slate-300 font-light select-none">/</span>

                <select
                    :value="currentYear"
                    @change="handleYearSelect"
                    class="bg-transparent border-0 p-0 pr-3 sm:pr-4 text-xs sm:text-sm font-bold text-slate-800 focus:ring-0 cursor-pointer"
                    aria-label="Pilih Tahun"
                >
                    <option v-for="y in yearOptions" :key="y" :value="y">
                        {{ y }}
                    </option>
                </select>
            </div>

            <!-- Tombol Next -->
            <button
                type="button"
                @click="nextMonth"
                class="w-8 h-8 sm:w-9 sm:h-9 flex items-center justify-center rounded-xl text-slate-500 hover:text-slate-900 hover:bg-slate-100 active:scale-95 transition shrink-0"
                title="Bulan Berikutnya"
                aria-label="Bulan Berikutnya"
            >
                <ChevronRight class="w-4 h-4 sm:w-5 sm:h-5" />
            </button>
        </div>

        <!-- Tombol Kembali ke Bulan Ini (Hanya tampil jika bukan bulan saat ini) -->
        <button
            v-if="showTodayButton && !isCurrentCalendarMonth"
            type="button"
            @click="goToCurrentMonth"
            class="px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 active:scale-95 transition shrink-0 ml-1"
        >
            Bulan Ini
        </button>
    </div>
</template>

