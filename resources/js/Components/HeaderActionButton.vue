<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';

const props = defineProps({
    as: {
        type: String,
        default: 'button', // 'button' | 'link' | 'a'
    },
    href: {
        type: String,
        default: null,
    },
    label: {
        type: String,
        required: true,
    },
    shortLabel: {
        type: String,
        default: null,
    },
    icon: {
        type: [Object, Function],
        default: () => Plus,
    },
    iconClass: {
        type: String,
        default: '',
    },
    variant: {
        type: String,
        default: 'primary', // 'primary' | 'secondary' | 'danger'
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    type: {
        type: String,
        default: 'button',
    },
});

defineEmits(['click']);

const tag = computed(() => {
    if (props.as === 'link') return Link;
    if (props.as === 'a') return 'a';
    return 'button';
});

const resolvedShortLabel = computed(() => {
    return props.shortLabel || props.label.split(' ')[0];
});

const variantClasses = computed(() => {
    switch (props.variant) {
        case 'secondary':
            return 'bg-white hover:bg-slate-50 text-slate-700 border border-slate-200/90 shadow-sm active:bg-slate-100';
        case 'danger':
            return 'bg-rose-600 hover:bg-rose-700 text-white shadow-sm shadow-rose-600/20 active:bg-rose-800';
        case 'primary':
        default:
            return 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm shadow-emerald-600/25 active:bg-emerald-800';
    }
});
</script>

<template>
    <component
        :is="tag"
        :href="href"
        :type="tag === 'button' ? type : undefined"
        :disabled="tag === 'button' ? disabled : undefined"
        @click="$emit('click', $event)"
        class="inline-flex items-center justify-center h-9 px-2.5 sm:px-3.5 rounded-xl text-xs sm:text-sm font-semibold transition-all transform active:scale-95 shrink-0 disabled:opacity-50 disabled:pointer-events-none"
        :class="[variantClasses, disabled ? 'opacity-50 pointer-events-none' : '']"
    >
        <component :is="icon" class="w-4 h-4 shrink-0" :class="iconClass" />
        <span class="sm:hidden ml-1 truncate">{{ resolvedShortLabel }}</span>
        <span class="hidden sm:inline ml-1.5 whitespace-nowrap">{{ label }}</span>
    </component>
</template>
