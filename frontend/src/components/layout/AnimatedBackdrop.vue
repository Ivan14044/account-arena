<template>
    <div class="fixed inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
        <svg
            viewBox="0 0 1000 1000"
            preserveAspectRatio="xMidYMid slice"
            :class="[
                'absolute inset-0 w-full h-full transition-opacity duration-700',
                isDark ? 'opacity-50' : 'opacity-30'
            ]"
            xmlns="http://www.w3.org/2000/svg"
        >
            <defs>
                <radialGradient id="grad1" cx="50%" cy="50%" r="50%" fx="50%" fy="50%">
                    <stop offset="0%" stop-color="rgba(255, 106, 0, 0.8)" />
                    <stop offset="100%" stop-color="rgba(255, 106, 0, 0)" />
                </radialGradient>
                <radialGradient id="grad2" cx="50%" cy="50%" r="50%" fx="50%" fy="50%">
                    <stop offset="0%" stop-color="rgba(255, 0, 204, 0.8)" />
                    <stop offset="100%" stop-color="rgba(255, 0, 204, 0)" />
                </radialGradient>
                <radialGradient id="grad3" cx="50%" cy="50%" r="50%" fx="50%" fy="50%">
                    <stop offset="0%" stop-color="rgba(0, 170, 255, 0.8)" />
                    <stop offset="100%" stop-color="rgba(0, 170, 255, 0)" />
                </radialGradient>
                <radialGradient id="grad4" cx="50%" cy="50%" r="50%" fx="50%" fy="50%">
                    <stop offset="0%" stop-color="rgba(0, 123, 255, 0.8)" />
                    <stop offset="100%" stop-color="rgba(0, 123, 255, 0)" />
                </radialGradient>
            </defs>

            <!-- Blobs -->
            <circle class="blob blob-1" cx="200" cy="200" r="450" fill="url(#grad1)" />
            <circle class="blob blob-2" cx="800" cy="300" r="500" fill="url(#grad2)" />
            <circle class="blob blob-3" cx="300" cy="800" r="550" fill="url(#grad3)" />
            <circle class="blob blob-4" cx="900" cy="900" r="400" fill="url(#grad4)" />
        </svg>
    </div>
</template>

<script setup lang="ts">
defineProps<{
    isDark?: boolean;
}>();
</script>

<style scoped>
.blob {
    will-change: transform;
    mix-blend-mode: normal;
}

.blob-1 {
    animation: move1 45s ease-in-out infinite;
}
.blob-2 {
    animation: move2 50s ease-in-out infinite;
}
.blob-3 {
    animation: move3 55s ease-in-out infinite;
}
.blob-4 {
    animation: move4 40s ease-in-out infinite;
}

@media (prefers-reduced-motion: reduce) {
    .blob {
        animation: none !important;
    }
}

@keyframes move1 {
    0%, 100% { transform: translate3d(0, 0, 0) scale(1); }
    33% { transform: translate3d(100px, 150px, 0) scale(1.1); }
    66% { transform: translate3d(-50px, 200px, 0) scale(0.9); }
}

@keyframes move2 {
    0%, 100% { transform: translate3d(0, 0, 0) scale(1); }
    33% { transform: translate3d(-150px, 50px, 0) scale(0.95); }
    66% { transform: translate3d(-100px, -100px, 0) scale(1.05); }
}

@keyframes move3 {
    0%, 100% { transform: translate3d(0, 0, 0) scale(1); }
    33% { transform: translate3d(50px, -150px, 0) scale(1.05); }
    66% { transform: translate3d(150px, -50px, 0) scale(0.95); }
}

@keyframes move4 {
    0%, 100% { transform: translate3d(0, 0, 0) scale(1); }
    33% { transform: translate3d(-100px, -100px, 0) scale(1.1); }
    66% { transform: translate3d(-200px, 50px, 0) scale(0.9); }
}
</style>
