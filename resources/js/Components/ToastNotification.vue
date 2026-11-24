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
    const duration = toastType === 'error' ? 4000 : 2400;
    timer = setTimeout(() => {
        visible.value = false;
    }, duration);
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
    <!-- Outer container: fixed top-20 on mobile (below 64px header), pointer-events-none so nothing is blocked -->
    <div
        class="fixed top-20 sm:top-5 inset-x-0 sm:inset-x-auto sm:right-6 z-50 flex justify-center sm:justify-end px-4 sm:px-0 pointer-events-none"
    >
        <Transition
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="-translate-y-3 opacity-0 scale-95"
            enter-to-class="translate-y-0 opacity-100 scale-100"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="-translate-y-2 opacity-0 scale-95"
        >
            <div
                v-if="visible"
                @click="dismiss"
                class="pointer-events-auto cursor-pointer max-w-md w-full sm:w-auto flex items-center gap-3 rounded-2xl px-4 py-3 shadow-xl backdrop-blur-md border transition-all active:scale-[0.98]"
                :class="{
                    'bg-slate-900/95 text-white border-emerald-500/30 shadow-emerald-950/20': type === 'success',
                    'bg-slate-900/95 text-white border-rose-500/30 shadow-rose-950/20': type === 'error',
                    'bg-slate-900/95 text-white border-amber-500/30 shadow-amber-950/20': type === 'warning',
                    'bg-slate-900/95 text-white border-sky-500/30 shadow-sky-950/20': type === 'info',
                }"
                role="status"
                aria-live="polite"
            >
                <div
                    class="w-7 h-7 rounded-xl flex items-center justify-center shrink-0"
                    :class="{
                        'bg-emerald-500/20 text-emerald-400': type === 'success',
                        'bg-rose-500/20 text-rose-400': type === 'error',
                        'bg-amber-500/20 text-amber-400': type === 'warning',
                        'bg-sky-500/20 text-sky-400': type === 'info',
                    }"
                >
                    <CheckCircle2 v-if="type === 'success'" class="h-4 w-4" />
                    <AlertCircle v-else-if="type === 'error'" class="h-4 w-4" />
                    <AlertTriangle v-else-if="type === 'warning'" class="h-4 w-4" />
                    <Info v-else class="h-4 w-4" />
                </div>

                <div class="text-xs sm:text-sm font-medium flex-1 text-slate-100 leading-snug">
                    {{ message }}
                </div>

                <button
                    type="button"
                    @click.stop="dismiss"
                    class="rounded-lg p-1 text-slate-400 hover:text-white hover:bg-white/10 transition shrink-0 ml-1"
                    aria-label="Tutup notifikasi"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>
        </Transition>
    </div>
</template>
