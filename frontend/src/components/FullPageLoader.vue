<template>
    <Transition name="fade">
        <div
            v-if="loadingStore.isLoading"
            :class="{ 'bg-black/40': hasOverlay, 'bg-white dark:!bg-gray-900': !hasOverlay }"
            class="fixed inset-0 z-[9999] flex items-center justify-center"
        >
            <div class="flex flex-col items-center">
                <img :src="logo" alt="Loading..." class="w-32 h-32 object-contain loader-pulse" />
            </div>
        </div>
    </Transition>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useLoadingStore } from '../stores/loading';

const logo = '/img/logo_trans.webp'; // Новое изображение прелоадера с социальными сетями

const props = defineProps<{
    overlay?: boolean;
    isLoading?: boolean; // оставляем для обратной совместимости, но не используем (как и раньше)
}>();

defineEmits<{
    callHideLoader: [];
}>();

const loadingStore = useLoadingStore();
const hasOverlay = computed(() => props.overlay !== false);
</script>

<style scoped>
.fade-enter-active {
    transition: none;
}
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.fade-enter-to,
.fade-leave-from {
    opacity: 1;
}

.ellipsis {
    display: inline-block;
    width: 3ch;
    text-align: left;
}

/* Плавная анимация пульсации для прелоадера (без вращения) */
.loader-pulse {
    animation: loader-pulse 2s ease-in-out infinite;
}

@keyframes loader-pulse {
    0%,
    100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.6;
        transform: scale(1.15);
    }
}

/* smoother crossfade/slide for status text only */
.text-fade-enter-active,
.text-fade-leave-active {
    transition:
        opacity 0.5s ease,
        transform 0.5s ease;
    will-change: opacity, transform;
}

.text-fade-enter-from {
    opacity: 0;
    transform: translateY(4px);
}
.text-fade-enter-to {
    opacity: 1;
    transform: translateY(0);
}

.text-fade-leave-from {
    opacity: 1;
    transform: translateY(0);
}
.text-fade-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>
