<template>
    <div class="fixed inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
        <div
            :class="[
                'animated-gradient absolute w-[120vw] h-[120vh] transition-opacity duration-700',
                isDark ? 'opacity-50 blur-[60px] md:blur-[80px]' : 'opacity-30 blur-[50px] md:blur-[70px]'
            ]"
        />
    </div>
</template>

<script setup lang="ts">
defineProps<{
    isDark?: boolean;
}>();
</script>

<style scoped>
.animated-gradient {
    background: linear-gradient(
        120deg,
        rgba(255, 106, 0, 0.35) 10%,
        rgba(255, 0, 204, 0.55) 35%,
        rgba(0, 170, 255, 0.75) 70%,
        rgba(0, 123, 255, 0.45) 90%
    );
    animation: gradientMove 45s ease-in-out infinite;
    will-change: transform, opacity;
    transform: translate3d(-18%, -18%, 0);
    backface-visibility: hidden;
    perspective: 1000px;
}

@keyframes gradientMove {
    0%,
    100% {
        transform: translate3d(-18%, -18%, 0) rotate(0deg) scale(1);
    }
    25% {
        transform: translate3d(-10%, -22%, 0) rotate(15deg) scale(1.02);
    }
    50% {
        transform: translate3d(8%, -12%, 0) rotate(30deg) scale(0.98);
    }
    75% {
        transform: translate3d(-12%, 8%, 0) rotate(15deg) scale(1.01);
    }
}
</style>
