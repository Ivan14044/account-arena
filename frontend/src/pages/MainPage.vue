<template>
    <div
        class="section-container max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pointer-events-none min-h-screen flex items-center justify-center mt-[3.5em]"
    >
        <!-- Hero Section -->
        <section id="hero" class="bg-transparent w-full">
            <HeroSection />
        </section>
    </div>
    <div
        v-intersect="{
            class: 'animate-fade-in-up',
            once: true,
            threshold: 0.08,
            rootMargin: '0px 0px -40% 0px'
        }"
        class="mx-auto px-4 pt-5 md:pt-24 pb-7 md:pb-16 sm:px-6 lg:px-8"
    >
        <!-- Steps Section -->
        <section id="steps">
            <div class="text-center mb-8 relative z-2">
                <h2
                    class="text-[32px] md:text-[48px] lg:text-[64px]m text-gray-900 dark:text-white mt-3 leading-none"
                    v-html="stepsTitle"
                ></h2>
            </div>

            <StepsSection />
        </section>
    </div>

    <!-- Accounts Section - Products from admin panel -->
    <div class="max-w-7xl mx-auto px-4 py-16 sm:px-6 lg:px-8">
        <section
            id="accounts"
            v-intersect="{
                class: 'animate-fade-in-up',
                once: true,
                threshold: 0.08,
                rootMargin: '0px 0px -40% 0px'
            }"
            class="mb-16"
        >
            <div class="text-center mb-16">
                <h2
                    class="text-[32px] md:text-[48px] lg:text-[64px] text-gray-900 dark:text-white mt-3 leading-none"
                    v-html="$t('account.shop_title')"
                ></h2>
                <p class="text-gray-600 dark:text-gray-400 mt-4">
                    {{ $t('account.shop_subtitle') }}
                </p>
            </div>

            <!-- Catalog Section -->
            <CatalogSection @filter-change="handleFilterChange" />

            <AccountSection :filters="filters" />
        </section>

        <!-- Articles Section -->
        <section
            id="articles"
            v-intersect="{
                class: 'animate-fade-in-up',
                once: true,
                threshold: 0.08,
                rootMargin: '0px 0px -40% 0px'
            }"
            class="mb-16"
        >
            <ArticleSection />
        </section>

        <!-- About Section -->
        <section
            id="about"
            v-intersect="{
                class: 'animate-fade-in-up',
                once: true,
                threshold: 0.08,
                rootMargin: '0px 0px -40% 0px'
            }"
            class="flex flex-col lg:flex-row items-center justify-center gap-20 pt-20 pb-24"
        >
            <AboutSection />
        </section>

        <!-- Promote Section -->
        <section
            id="promote"
            v-intersect="{
                class: 'animate-fade-in-up',
                once: true,
                threshold: 0.08,
                rootMargin: '0px 0px -40% 0px'
            }"
            class="mb-16"
        >
            <div class="text-center mb-16">
                <h2
                    class="text-[32px] md:text-[48px] lg:text-[64px] font-medium text-gray-900 dark:text-white mt-3"
                    v-html="promoteTitle"
                ></h2>
            </div>

            <PromoteSection />
        </section>

        <!-- Subscribe Section -->
        <section
            id="subscribe"
            v-intersect="{
                class: 'animate-fade-in-up',
                once: true,
                threshold: 0.08,
                rootMargin: '0px 0px -40% 0px'
            }"
            class="mb-16"
        >
            <SubscribeSection />
        </section>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useSiteContentStore } from '@/stores/siteContent';
import AboutSection from '../components/home/AboutSection.vue';
import ArticleSection from '../components/home/ArticleSection.vue';
import HeroSection from '../components/home/HeroSection.vue';
import PromoteSection from '../components/home/PromoteSection.vue';
import StepsSection from '../components/home/StepsSection.vue';
import SubscribeSection from '../components/home/SubscribeSection.vue';
import AccountSection from '../components/home/AccountSection.vue';
import CatalogSection from '../components/home/CatalogSection.vue';

const { t, locale } = useI18n();
const siteContentStore = useSiteContentStore();

// Filters for products
const filters = ref({
    categoryId: null as number | null,
    hideOutOfStock: false,
    showFavoritesOnly: false,
    searchQuery: ''
});

const handleFilterChange = (newFilters: {
    categoryId: number | null;
    hideOutOfStock: boolean;
    showFavoritesOnly: boolean;
    searchQuery: string;
}) => {
    filters.value = { ...newFilters };
};

onMounted(() => {
    // Load site content
    siteContentStore.loadContent();
});

// Get dynamic content with fallback to i18n
const promoteContent = computed(() => siteContentStore.promote(locale.value));
const stepsContent = computed(() => siteContentStore.steps(locale.value));

const promoteTitle = computed(() => {
    return promoteContent.value?.title || t('promote.title');
});

const stepsTitle = computed(() => {
    return stepsContent.value?.title || t('steps.title');
});
</script>
