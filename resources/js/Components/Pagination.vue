<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <div v-if="links.length > 3" class="flex flex-wrap items-center justify-center gap-1.5 py-4">
        <template v-for="(link, key) in links" :key="key">
            <div
                v-if="link.url === null"
                class="px-3 py-1.5 text-xs text-slate-400 bg-slate-100 rounded-lg cursor-not-allowed select-none"
                v-html="link.label"
            />
            <Link
                v-else
                :href="link.url"
                class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors"
                :class="link.active
                    ? 'bg-emerald-600 text-white shadow-sm shadow-emerald-600/30'
                    : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200'"
                v-html="link.label"
            />
        </template>
    </div>
</template>
