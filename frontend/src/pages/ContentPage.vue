<template>
    <div v-if="pageStore.page" class="max-w-7xl mx-auto px-4 py-16 sm:px-6 lg:px-8">
        <!-- Header Section -->
        <div class="text-center mb-16">
            <h1 class="text-4xl font-light text-gray-900 dark:text-white mt-3">
                {{ pageStore.page[locale].title }}
            </h1>
        </div>

        <div class="dark:text-gray-300" v-html="pageStore.page[locale].content"></div>
    </div>
</template>

<script setup lang="ts">
import { usePageStore } from '@/stores/pages';
import { useI18n } from 'vue-i18n';

const pageStore = usePageStore();
const { locale } = useI18n();
</script>
