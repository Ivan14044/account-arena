<template>
    <div class="min-h-screen flex flex-col bg-gray-100 dark:bg-gray-900">
        <AnimatedBackdrop :is-dark="isDark" />
        <Header v-if="!route.meta.requiresGuest" />
        <main class="flex-1">
            <router-view v-if="!isLoading" />
        </main>
        <Footer v-if="!route.meta.requiresGuest" />
        <ScrollToTop />
        <CookieBanner />
    </div>
</template>

<script setup lang="ts">
import { defineProps } from 'vue';
import { useRoute } from 'vue-router';
import { useTheme } from '@/composables/useTheme';

import Header from '@/components/layout/MainHeader.vue';
import Footer from '@/components/layout/MainFooter.vue';
import CookieBanner from '@/components/CookieBanner.vue';
import ScrollToTop from '@/components/ScrollToTop.vue';
import AnimatedBackdrop from '@/components/layout/AnimatedBackdrop.vue';

defineProps<{
    isLoading: boolean;
}>();

const route = useRoute();
const { isDark } = useTheme();
</script>
