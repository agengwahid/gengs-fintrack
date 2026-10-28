<script setup>
import { computed } from 'vue';
import {
    Wallet,
    Building2,
    Smartphone,
    CreditCard,
    PiggyBank,
    Coins,
    Landmark,
    Utensils,
    Car,
    ShoppingCart,
    ShoppingBag,
    Zap,
    Gamepad2,
    HeartPulse,
    GraduationCap,
    Home,
    Briefcase,
    Gift,
    Laptop,
    TrendingUp,
    TrendingDown,
    Coffee,
    Plane,
    Film,
    Book,
    Dumbbell,
    Tags,
    Folder,
    HelpCircle,
    MoreHorizontal
} from '@lucide/vue';

const props = defineProps({
    name: {
        type: String,
        default: 'Wallet',
    },
    class: {
        type: String,
        default: 'w-5 h-5',
    },
});

const iconMap = {
    Wallet,
    Building2,
    Smartphone,
    CreditCard,
    PiggyBank,
    Coins,
    Landmark,
    Utensils,
    Car,
    ShoppingCart,
    ShoppingBag,
    Zap,
    Gamepad2,
    HeartPulse,
    GraduationCap,
    Home,
    Briefcase,
    Gift,
    Laptop,
    TrendingUp,
    TrendingDown,
    Coffee,
    Plane,
    Film,
    Book,
    Dumbbell,
    Tags,
    Folder,
    HelpCircle,
    MoreHorizontal,
};

const iconComponent = computed(() => {
    return iconMap[props.name] || Folder;
});
</script>

<template>
    <component :is="iconComponent" :class="props.class" />
</template>
