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

// Generate comprehensive year options (2020 up to 2050)
const yearOptions = computed(() => {
    const startYear = 2020;
    const endYear = Math.max(2050, new Date().getFullYear() + 10);
    const years = [];
    for (let y = startYear; y <= endYear; y++) {
        years.push(y.toString());
    }
    // Ensure selected year is in options if outside range
    if (!years.includes(currentYear.value)) {
        years.push(currentYear.value);
        years.sort((a, b) => Number(a) - Number(b));
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
        class="flex flex-wrap items-center gap-2"
        :class="variant === 'card' ? 'p-3 sm:p-4 bg-white rounded-2xl border border-slate-200/80 shadow-sm' : ''"
    >
        <!-- Navigasi Panah & Pemilih Bulan/Tahun -->
        <div class="flex items-center gap-1.5 w-full sm:w-auto justify-between sm:justify-start">
            <!-- Tombol Bulan Sebelumnya -->
            <button
                type="button"
                @click="prevMonth"
                class="p-2 rounded-xl text-slate-500 hover:text-emerald-700 hover:bg-emerald-50 active:scale-95 transition border border-slate-200/80 bg-white sm:border-0"
                title="Bulan Sebelumnya"
                aria-label="Bulan Sebelumnya"
            >
                <ChevronLeft class="w-5 h-5" />
            </button>

            <!-- Box Pemilih Bulan dan Tahun yang Jelas -->
            <div class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl bg-slate-50 border border-slate-200/90 shadow-inner">
                <Calendar class="w-4 h-4 text-emerald-600 shrink-0 hidden xs:block" />

                <!-- Dropdown Pilihan Bulan Bahasa Indonesia -->
                <select
                    :value="currentMonth"
                    @change="handleMonthSelect"
                    class="bg-transparent border-0 py-0 pl-1 pr-6 text-xs sm:text-sm font-bold text-slate-800 focus:ring-0 focus:outline-none cursor-pointer"
                    aria-label="Pilih Bulan"
                >
                    <option v-if="allowAllMonths" value="all">Semua Bulan</option>
                    <option
                        v-for="m in months"
                        :key="m.value"
                        :value="m.value"
                    >
                        {{ m.label }}
                    </option>
                </select>

                <!-- Separator -->
                <span class="text-slate-300 font-light select-none">/</span>

                <!-- Dropdown Pilihan Tahun -->
                <select
                    :value="currentYear"
                    @change="handleYearSelect"
                    class="bg-transparent border-0 py-0 pl-1 pr-6 text-xs sm:text-sm font-bold text-slate-800 focus:ring-0 focus:outline-none cursor-pointer"
                    aria-label="Pilih Tahun"
                >
                    <option
                        v-for="y in yearOptions"
                        :key="y"
                        :value="y"
                    >
                        {{ y }}
                    </option>
                </select>
            </div>

            <!-- Tombol Bulan Berikutnya -->
            <button
                type="button"
                @click="nextMonth"
                class="p-2 rounded-xl text-slate-500 hover:text-emerald-700 hover:bg-emerald-50 active:scale-95 transition border border-slate-200/80 bg-white sm:border-0"
                title="Bulan Berikutnya"
                aria-label="Bulan Berikutnya"
            >
                <ChevronRight class="w-5 h-5" />
            </button>
        </div>

        <!-- Tombol Cepat: Bulan Ini -->
        <button
            v-if="showTodayButton"
            type="button"
            @click="goToCurrentMonth"
            class="px-3 py-1.5 rounded-xl text-xs font-bold transition ml-auto sm:ml-0"
            :class="isCurrentCalendarMonth
                ? 'bg-emerald-100 text-emerald-800 pointer-events-none'
                : 'bg-slate-100 hover:bg-emerald-50 text-slate-600 hover:text-emerald-700 active:scale-95'"
        >
            {{ isCurrentCalendarMonth ? 'Bulan Ini' : 'Ke Bulan Ini' }}
        </button>
    </div>
</template>

