<template>
    <div
        class="px-2 px-lg-3 flex h-[32px] items-center rounded-lg transition-all duration-300 hover:bg-indigo-200 dark:hover:bg-gray-700 cursor-pointer gap-2"
        @click="router.push('/checkout')"
    >
        <!-- Общая сумма в корзине (только для десктопа) -->
        <span 
            v-if="totalAmount > 0" 
            class="text-sm font-bold text-gray-700 dark:text-gray-200 hidden sm:block"
        >
            {{ formatPrice(totalAmount) }}
        </span>

        <!-- ShoppingBag icon -->
        <button class="relative flex items-center">
            <ShoppingBag class="w-[20px] h-auto" />

            <span
                v-if="totalItems > 0"
                class="counter flex items-center justify-center leading-none -top-1 -right-1 text-white"
            >
                {{ totalItems > 9 ? '9+' : totalItems }}
            </span>
        </button>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { ShoppingBag } from 'lucide-vue-next';
import { useProductCartStore } from '@/stores/productCart';
import { useRouter } from 'vue-router';
import { useOptionStore } from '@/stores/options';

const productCartStore = useProductCartStore();
const optionStore = useOptionStore();
const router = useRouter();

// Количество уникальных товаров
const totalItems = computed(() => productCartStore.itemCount);

// Общая сумма
const totalAmount = computed(() => productCartStore.totalAmount);

// Форматирование цены
const formatPrice = (price) => {
    const currency = optionStore.getOption('currency', 'USD');
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(price);
};
</script>

<style scoped>
.counter {
    background: #0047ff;
    padding: 7px 0;
    border-radius: 50%;
    display: block;
    text-align: center;
    height: 22px;
    width: 22px;
    margin-left: 20px;
    margin-top: -3px;
    font-weight: normal;
    font-size: 10px;
    position: absolute;
    left: -11px;
}
</style>
