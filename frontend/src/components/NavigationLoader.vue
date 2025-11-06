<template>
    <Transition name="fade">
        <div
            v-if="loadingStore.isLoading"
            class="fixed inset-0 z-[9998] flex items-center justify-center bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm"
        >
            <div class="flex flex-col items-center">
                <img
                    :src="logo"
                    alt="Loading..."
                    class="w-24 h-24 object-contain navigation-pulse"
                />
            </div>
        </div>
    </Transition>
</template>

<script setup lang="ts">
import { useLoadingStore } from '@/stores/loading';

const logo = '/img/logo_trans.webp';
const loadingStore = useLoadingStore();
</script>

<style scoped>
/* Плавная анимация появления/исчезновения */
.fade-enter-active {
    transition: opacity 0.15s ease;
}

.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.fade-enter-to,
.fade-leave-from {
    opacity: 1;
}

/* Анимация пульсации для навигационного прелоадера (быстрее чем основной) */
.navigation-pulse {
    animation: navigation-pulse 1.2s ease-in-out infinite;
}

@keyframes navigation-pulse {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.5;
        transform: scale(1.1);
    }
}
</style>

