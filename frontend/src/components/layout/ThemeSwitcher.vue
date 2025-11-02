<template>
    <button
        class="relative w-12 h-6 rounded-full flex items-center justify-between px-1 bg-indigo-soft-300 dark:bg-gray-700 transition-colors duration-300"
        aria-label="Toggle dark mode"
        @click="toggleTheme"
    >
        <!-- Left icon (light) -->
        <SunMoon class="w-4 h-4 text-yellow-500" />

        <!-- Right icon (dark) -->
        <Moon class="w-4 h-4 text-gray-200 text-indigo-400" />

        <!-- Slider -->
        <span
            class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white shadow transition-all duration-300"
            :class="isDark ? 'translate-x-6' : 'translate-x-0'"
        ></span>
    </button>
</template>

<script setup lang="ts">
import { SunMoon, Moon } from 'lucide-vue-next';
import { useTheme } from '@/composables/useTheme';

const { isDark, toggleTheme } = useTheme();
</script>
