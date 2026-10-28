<script setup>
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { CheckCircle2, AlertCircle, AlertTriangle, Info, X } from '@lucide/vue';

const page = usePage();
const visible = ref(false);
const message = ref('');
const type = ref('success');
let timer = null;

function showToast(msg, toastType = 'success') {
    if (!msg) return;
    message.value = msg;
    type.value = toastType;
    visible.value = true;
    if (timer) clearTimeout(timer);
    timer = setTimeout(() => {
        visible.value = false;
    }, 4500);
}

function dismiss() {
    visible.value = false;
    if (timer) clearTimeout(timer);
}

watch(
    () => page.props.flash,
    (flash) => {
        if (!flash) return;
        if (flash.success) {
            showToast(flash.success, 'success');
        } else if (flash.error) {
            showToast(flash.error, 'error');
        } else if (flash.warning) {
            showToast(flash.warning, 'warning');
        } else if (flash.info) {
            showToast(flash.info, 'info');
        }
    },
    { deep: true, immediate: true }
);
</script>

<template>
    <div
        v-if="visible"
        class="fixed top-5 right-5 z-50 flex items-center gap-3 rounded-xl p-4 shadow-lg ring-1 transition-all duration-300 max-w-md"
        :class="{
            'bg-emerald-50 text-emerald-900 ring-emerald-200': type === 'success',
            'bg-rose-50 text-rose-900 ring-rose-200': type === 'error',
            'bg-amber-50 text-amber-900 ring-amber-200': type === 'warning',
            'bg-sky-50 text-sky-900 ring-sky-200': type === 'info',
        }"
    >
        <CheckCircle2 v-if="type === 'success'" class="h-5 w-5 text-emerald-600 shrink-0" />
        <AlertCircle v-else-if="type === 'error'" class="h-5 w-5 text-rose-600 shrink-0" />
        <AlertTriangle v-else-if="type === 'warning'" class="h-5 w-5 text-amber-600 shrink-0" />
        <Info v-else class="h-5 w-5 text-sky-600 shrink-0" />

        <div class="text-sm font-medium flex-1">
            {{ message }}
        </div>

        <button
            type="button"
            @click="dismiss"
            class="rounded-lg p-1 text-slate-400 hover:text-slate-600 hover:bg-black/5 transition"
        >
            <X class="h-4 w-4" />
        </button>
    </div>
</template>
