<template>
    <button
        class="scroll-to-top-btn dark:bg-gray-700 dark:hover:bg-gray-600"
        :class="{ visible: isVisible }"
        aria-label="Scroll to top"
        @click="scrollToTop"
    >
        <svg
            class="w-4 h-4 text-blue-600 dark:text-white"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M5 15l7-7 7 7"
            />
        </svg>
    </button>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue';

const isVisible = ref(false);

const handleScroll = () => {
    isVisible.value = window.scrollY > 300;
};

const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll);
});

onBeforeUnmount(() => {
    window.removeEventListener('scroll', handleScroll);
});
</script>

<style scoped>
.scroll-to-top-btn {
    @apply fixed bottom-6 right-6 z-50 bg-white border border-gray-300 rounded-full shadow-md w-10 h-10 flex items-center justify-center transition hover:bg-gray-100;

    opacity: 0;
    transform: translateY(10px);
    pointer-events: none;
    transition:
        opacity 0.3s ease,
        transform 0.3s ease;
}

.scroll-to-top-btn.visible {
    opacity: 1;
    transform: translateY(0);
    pointer-events: auto;
}
</style>
