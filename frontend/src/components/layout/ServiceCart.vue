<template>
    <div
        class="px-2 px-lg-3 d-flex h-[32px] rounded-lg transition-all duration-300 hover:bg-indigo-200 dark:hover:bg-gray-700 cursor-pointer"
        @click="router.push('/checkout')"
    >
        <!-- ShoppingBag icon -->
        <button class="relative">
            <ShoppingBag class="w-[20px] h-auto" />

            <span
                v-if="cartStore.items.length > 0"
                class="counter flex items-center justify-center leading-none -top-1 -right-1 text-white"
            >
                {{ cartStore.items.length > 9 ? '9+' : cartStore.items.length }}
            </span>
        </button>
    </div>
</template>

<script setup>
import { ShoppingBag } from 'lucide-vue-next';
import { useCartStore } from '@/stores/cart';
import { useRouter } from 'vue-router';

const cartStore = useCartStore();
const router = useRouter();
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
