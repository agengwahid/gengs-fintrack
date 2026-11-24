<script setup>
import { ref, watch, onMounted, nextTick } from 'vue';

const props = defineProps({
    modelValue: {
        type: [Number, String],
        default: '',
    },
    placeholder: {
        type: String,
        default: '0',
    },
    required: {
        type: Boolean,
        default: false,
    },
    autofocus: {
        type: Boolean,
        default: false,
    },
    id: {
        type: String,
        default: undefined,
    },
    name: {
        type: String,
        default: undefined,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    class: {
        type: [String, Object, Array],
        default: '',
    },
});

const emit = defineEmits(['update:modelValue']);

const inputRef = ref(null);
const displayValue = ref('');

/**
 * Format string/number to Indonesian currency format with thousand dots (e.g. 1.500.000)
 */
function formatNumberToIndo(val) {
    if (val === null || val === undefined || val === '') return '';

    // If incoming val has decimal from DB or number type (e.g. "150000.00" or 150000)
    if (typeof val === 'number' || /^\d+(\.\d+)?$/.test(String(val).trim())) {
        const num = Number(val);
        if (!isNaN(num)) {
            const parts = String(val).split('.');
            const intPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            if (parts.length > 1 && Number(parts[1]) > 0) {
                return `${intPart},${parts[1]}`;
            }
            return intPart;
        }
    }

    // Clean input (keep digits and comma for decimal)
    const clean = String(val).replace(/[^\d,]/g, '');
    const parts = clean.split(',');
    const intFormatted = parts[0].replace(/^0+(?=\d)/, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    if (parts.length > 1) {
        return `${intFormatted},${parts.slice(1).join('').slice(0, 2)}`;
    }
    return intFormatted;
}

/**
 * Parse Indonesian formatted number to raw float/int
 */
function unformatIndoNumber(val) {
    if (val === null || val === undefined || val === '') return '';
    const cleaned = String(val).replace(/\./g, '').replace(',', '.');
    if (cleaned === '' || cleaned === '.') return '';
    const num = Number(cleaned);
    return isNaN(num) ? '' : num;
}

function updateDisplayFromModel(val) {
    const formatted = formatNumberToIndo(val);
    if (displayValue.value !== formatted) {
        displayValue.value = formatted;
    }
}

watch(
    () => props.modelValue,
    (newVal) => {
        const currentRaw = unformatIndoNumber(displayValue.value);
        if (currentRaw !== newVal) {
            updateDisplayFromModel(newVal);
        }
    },
    { immediate: true }
);

function handleInput(event) {
    const input = event.target;
    const oldVal = displayValue.value;
    const oldCursor = input.selectionStart || 0;

    const rawValue = unformatIndoNumber(input.value);
    const newFormatted = formatNumberToIndo(input.value);

    displayValue.value = newFormatted;
    emit('update:modelValue', rawValue);

    // Maintain cursor position
    nextTick(() => {
        if (!inputRef.value) return;
        const diff = newFormatted.length - oldVal.length;
        const newCursor = Math.max(0, oldCursor + diff);
        inputRef.value.setSelectionRange(newCursor, newCursor);
    });
}

onMounted(() => {
    updateDisplayFromModel(props.modelValue);
    if (props.autofocus && inputRef.value) {
        inputRef.value.focus();
    }
});

defineExpose({ focus: () => inputRef.value?.focus() });
</script>

<template>
    <input
        ref="inputRef"
        :id="id"
        :name="name"
        type="text"
        inputmode="numeric"
        :value="displayValue"
        @input="handleInput"
        :placeholder="placeholder"
        :required="required"
        :disabled="disabled"
        :class="props.class || 'rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500'"
    />
</template>
