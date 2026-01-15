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
import { computed } from 'vue';
import { usePageStore } from '@/stores/pages';
import { useI18n } from 'vue-i18n';
import { useSeo } from '@/composables/useSeo';

const pageStore = usePageStore();
const { locale } = useI18n();

// SEO мета-теги
const pageTitle = computed(() => {
    return pageStore.page?.[locale.value]?.title || 'Страница';
});

const pageDescription = computed(() => {
    if (!pageStore.page?.[locale.value]?.content) return '';
    const text = pageStore.page[locale.value].content.replace(/<[^>]*>/g, '').trim();
    return text ? text.substring(0, 160) : '';
});

useSeo({
    title: () => pageTitle.value,
    description: () => pageDescription.value || 'Account Arena',
    ogImage: '/img/logo_trans.webp'
});
</script>
