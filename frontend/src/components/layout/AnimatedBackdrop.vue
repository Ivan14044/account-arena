<template>
    <div class="fixed inset-0 pointer-events-none overflow-hidden">
        <div
            :class="[
                'animated-gradient absolute w-[120vw] h-[120vh] transition-all duration-300',
                isDark ? 'opacity-60 blur-[80px]' : 'opacity-40 blur-[70px]'
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
    animation: gradientMove 30s ease-in-out infinite;
}

@keyframes gradientMove {
    0%,
    100% {
        transform: translate(-18%, -18%) rotate(0deg) scale(1);
    }
    25% {
        transform: translate(-10%, -22%) rotate(20deg) scale(1.03);
    }
    50% {
        transform: translate(8%, -12%) rotate(40deg) scale(0.98);
    }
    75% {
        transform: translate(-12%, 8%) rotate(25deg) scale(1.02);
    }
}
</style>
