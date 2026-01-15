<template>
    <div class="max-w-7xl mx-auto px-4 py-16 sm:px-6 lg:px-8">
        <!-- Welcome Banner Section -->
        <section
            id="become-supplier-page-welcome-banner"
            v-intersect="intersectConfig"
            class="text-center mb-20 py-16"
        >
            <h1
                class="text-4xl md:text-5xl lg:text-6xl font-light text-gray-900 dark:text-white mb-6 leading-tight"
                v-html="content.welcomeBanner.headline"
            ></h1>
            <p class="text-xl md:text-2xl text-gray-600 dark:text-gray-400 mb-8 max-w-3xl mx-auto">
                {{ content.welcomeBanner.subtitle }}
            </p>
            <button
                class="bg-blue-500 dark:bg-blue-600 hover:bg-blue-600 dark:hover:bg-blue-700 text-white font-medium px-8 py-3 rounded-lg text-lg transition-colors"
                @click="becomeSupportRedirect"
            >
                {{ content.welcomeBanner.ctaButton }}
            </button>
        </section>

        <!-- Supplier Stats Section -->
        <section
            id="become-supplier-page-supplier-stats"
            v-intersect="intersectConfig"
            class="mb-20"
        >
            <h2
                class="text-3xl md:text-4xl lg:text-5xl font-medium text-gray-900 dark:text-white text-center mb-12"
            >
                {{ content.supplierStats.title }}
            </h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div
                    class="bg-gradient-to-br from-purple-500/20 via-indigo-500/20 to-blue-500/20 dark:from-indigo-900/30 dark:via-purple-900/30 dark:to-blue-900/30 border border-indigo-400/30 dark:border-gray-700/40 backdrop-blur-md rounded-2xl p-6 text-center"
                >
                    <div class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-2">
                        500+
                    </div>
                    <div class="text-sm md:text-base text-gray-600 dark:text-gray-400">
                        {{ content.supplierStats.activeSuppliers }}
                    </div>
                </div>
                <div
                    class="bg-gradient-to-br from-purple-500/20 via-indigo-500/20 to-blue-500/20 dark:from-indigo-900/30 dark:via-purple-900/30 dark:to-blue-900/30 border border-indigo-400/30 dark:border-gray-700/40 backdrop-blur-md rounded-2xl p-6 text-center"
                >
                    <div class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-2">
                        50K+
                    </div>
                    <div class="text-sm md:text-base text-gray-600 dark:text-gray-400">
                        {{ content.supplierStats.totalSales }}
                    </div>
                </div>
                <div
                    class="bg-gradient-to-br from-purple-500/20 via-indigo-500/20 to-blue-500/20 dark:from-indigo-900/30 dark:via-purple-900/30 dark:to-blue-900/30 border border-indigo-400/30 dark:border-gray-700/40 backdrop-blur-md rounded-2xl p-6 text-center"
                >
                    <div class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-2">
                        4.8
                    </div>
                    <div class="text-sm md:text-base text-gray-600 dark:text-gray-400">
                        {{ content.supplierStats.averageRating }}
                    </div>
                </div>
                <div
                    class="bg-gradient-to-br from-purple-500/20 via-indigo-500/20 to-blue-500/20 dark:from-indigo-900/30 dark:via-purple-900/30 dark:to-blue-900/30 border border-indigo-400/30 dark:border-gray-700/40 backdrop-blur-md rounded-2xl p-6 text-center"
                >
                    <div class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-2">
                        50+
                    </div>
                    <div class="text-sm md:text-base text-gray-600 dark:text-gray-400">
                        {{ content.supplierStats.countries }}
                    </div>
                </div>
            </div>
        </section>

        <!-- Process Steps Section -->
        <section
            id="become-supplier-page-process-steps"
            v-intersect="intersectConfig"
            class="mb-20"
        >
            <h2
                class="text-3xl md:text-4xl lg:text-5xl font-medium text-gray-900 dark:text-white text-center mb-12"
            >
                {{ content.processSteps.title }}
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div
                    class="bg-gradient-to-br from-purple-500/20 via-indigo-500/20 to-blue-500/20 dark:from-indigo-900/30 dark:via-purple-900/30 dark:to-blue-900/30 border border-indigo-400/30 dark:border-gray-700/40 backdrop-blur-md rounded-2xl p-6"
                >
                    <div
                        class="w-12 h-12 flex items-center justify-center rounded-full text-white font-bold bg-pink-600 dark:bg-pink-700 shadow-md mb-4"
                    >
                        01
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">
                        {{ content.processSteps.step1.title }}
                    </h3>
                    <p class="text-gray-700 dark:text-gray-300">
                        {{ content.processSteps.step1.description }}
                    </p>
                </div>
                <div
                    class="bg-gradient-to-br from-purple-500/20 via-indigo-500/20 to-blue-500/20 dark:from-indigo-900/30 dark:via-purple-900/30 dark:to-blue-900/30 border border-indigo-400/30 dark:border-gray-700/40 backdrop-blur-md rounded-2xl p-6"
                >
                    <div
                        class="w-12 h-12 flex items-center justify-center rounded-full text-white font-bold bg-green-600 dark:bg-green-700 shadow-md mb-4"
                    >
                        02
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">
                        {{ content.processSteps.step2.title }}
                    </h3>
                    <p class="text-gray-700 dark:text-gray-300">
                        {{ content.processSteps.step2.description }}
                    </p>
                </div>
                <div
                    class="bg-gradient-to-br from-purple-500/20 via-indigo-500/20 to-blue-500/20 dark:from-indigo-900/30 dark:via-purple-900/30 dark:to-blue-900/30 border border-indigo-400/30 dark:border-gray-700/40 backdrop-blur-md rounded-2xl p-6"
                >
                    <div
                        class="w-12 h-12 flex items-center justify-center rounded-full text-white font-bold bg-blue-600 dark:bg-blue-700 shadow-md mb-4"
                    >
                        03
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">
                        {{ content.processSteps.step3.title }}
                    </h3>
                    <p class="text-gray-700 dark:text-gray-300">
                        {{ content.processSteps.step3.description }}
                    </p>
                </div>
                <div
                    class="bg-gradient-to-br from-purple-500/20 via-indigo-500/20 to-blue-500/20 dark:from-indigo-900/30 dark:via-purple-900/30 dark:to-blue-900/30 border border-indigo-400/30 dark:border-gray-700/40 backdrop-blur-md rounded-2xl p-6"
                >
                    <div
                        class="w-12 h-12 flex items-center justify-center rounded-full text-white font-bold bg-yellow-600 dark:bg-yellow-700 shadow-md mb-4"
                    >
                        04
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">
                        {{ content.processSteps.step4.title }}
                    </h3>
                    <p class="text-gray-700 dark:text-gray-300">
                        {{ content.processSteps.step4.description }}
                    </p>
                </div>
            </div>
        </section>

        <!-- Digital Goods Categories Section -->
        <section
            id="become-supplier-page-digital-goods-categories"
            v-intersect="intersectConfig"
            class="mb-20"
        >
            <h2
                class="text-3xl md:text-4xl lg:text-5xl font-medium text-gray-900 dark:text-white text-center mb-4"
            >
                {{ content.digitalGoodsCategories.title }}
            </h2>
            <p class="text-center text-gray-600 dark:text-gray-400 mb-12 text-lg">
                {{ content.digitalGoodsCategories.subtitle }}
            </p>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6">
                <div
                    class="bg-gradient-to-br from-purple-500/20 via-indigo-500/20 to-blue-500/20 dark:from-indigo-900/30 dark:via-purple-900/30 dark:to-blue-900/30 border border-indigo-400/30 dark:border-gray-700/40 backdrop-blur-md rounded-2xl p-6 text-center"
                >
                    <div class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ content.digitalGoodsCategories.socialMedia }}
                    </div>
                </div>
                <div
                    class="bg-gradient-to-br from-purple-500/20 via-indigo-500/20 to-blue-500/20 dark:from-indigo-900/30 dark:via-purple-900/30 dark:to-blue-900/30 border border-indigo-400/30 dark:border-gray-700/40 backdrop-blur-md rounded-2xl p-6 text-center"
                >
                    <div class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ content.digitalGoodsCategories.gaming }}
                    </div>
                </div>
                <div
                    class="bg-gradient-to-br from-purple-500/20 via-indigo-500/20 to-blue-500/20 dark:from-indigo-900/30 dark:via-purple-900/30 dark:to-blue-900/30 border border-indigo-400/30 dark:border-gray-700/40 backdrop-blur-md rounded-2xl p-6 text-center"
                >
                    <div class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ content.digitalGoodsCategories.streaming }}
                    </div>
                </div>
                <div
                    class="bg-gradient-to-br from-purple-500/20 via-indigo-500/20 to-blue-500/20 dark:from-indigo-900/30 dark:via-purple-900/30 dark:to-blue-900/30 border border-indigo-400/30 dark:border-gray-700/40 backdrop-blur-md rounded-2xl p-6 text-center"
                >
                    <div class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ content.digitalGoodsCategories.software }}
                    </div>
                </div>
                <div
                    class="bg-gradient-to-br from-purple-500/20 via-indigo-500/20 to-blue-500/20 dark:from-indigo-900/30 dark:via-purple-900/30 dark:to-blue-900/30 border border-indigo-400/30 dark:border-gray-700/40 backdrop-blur-md rounded-2xl p-6 text-center"
                >
                    <div class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ content.digitalGoodsCategories.other }}
                    </div>
                </div>
            </div>
        </section>

        <!-- Restricted Items Section -->
        <section
            id="become-supplier-page-restricted-items"
            v-intersect="intersectConfig"
            class="mb-20"
        >
            <h2
                class="text-3xl md:text-4xl lg:text-5xl font-medium text-gray-900 dark:text-white text-center mb-4"
            >
                {{ content.restrictedItems.title }}
            </h2>
            <p class="text-center text-gray-600 dark:text-gray-400 mb-8 text-lg">
                {{ content.restrictedItems.subtitle }}
            </p>
            <div
                class="bg-gradient-to-br from-red-500/20 via-orange-500/20 to-yellow-500/20 dark:from-red-900/30 dark:via-orange-900/30 dark:to-yellow-900/30 border border-red-400/30 dark:border-gray-700/40 backdrop-blur-md rounded-2xl p-8 max-w-3xl mx-auto"
            >
                <ul class="space-y-4 mb-6">
                    <li
                        v-for="(item, index) in content.restrictedItems.items"
                        :key="index"
                        class="flex items-start text-gray-900 dark:text-white"
                    >
                        <span class="text-red-500 dark:text-red-400 mr-3">✗</span>
                        <span>{{ item }}</span>
                    </li>
                </ul>
                <p class="text-center text-gray-700 dark:text-gray-300">
                    {{ content.restrictedItems.contactMessage }}
                </p>
            </div>
        </section>

        <!-- Partner Benefits Section -->
        <section
            id="become-supplier-page-partner-benefits"
            v-intersect="intersectConfig"
            class="mb-20"
        >
            <h2
                class="text-3xl md:text-4xl lg:text-5xl font-medium text-gray-900 dark:text-white text-center mb-12"
            >
                {{ content.partnerBenefits.title }}
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div
                    class="bg-gradient-to-br from-purple-500/20 via-indigo-500/20 to-blue-500/20 dark:from-indigo-900/30 dark:via-purple-900/30 dark:to-blue-900/30 border border-indigo-400/30 dark:border-gray-700/40 backdrop-blur-md rounded-2xl p-6"
                >
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">
                        {{ content.partnerBenefits.benefit1.title }}
                    </h3>
                    <p class="text-gray-700 dark:text-gray-300">
                        {{ content.partnerBenefits.benefit1.description }}
                    </p>
                </div>
                <div
                    class="bg-gradient-to-br from-purple-500/20 via-indigo-500/20 to-blue-500/20 dark:from-indigo-900/30 dark:via-purple-900/30 dark:to-blue-900/30 border border-indigo-400/30 dark:border-gray-700/40 backdrop-blur-md rounded-2xl p-6"
                >
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">
                        {{ content.partnerBenefits.benefit2.title }}
                    </h3>
                    <p class="text-gray-700 dark:text-gray-300">
                        {{ content.partnerBenefits.benefit2.description }}
                    </p>
                </div>
                <div
                    class="bg-gradient-to-br from-purple-500/20 via-indigo-500/20 to-blue-500/20 dark:from-indigo-900/30 dark:via-purple-900/30 dark:to-blue-900/30 border border-indigo-400/30 dark:border-gray-700/40 backdrop-blur-md rounded-2xl p-6"
                >
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">
                        {{ content.partnerBenefits.benefit3.title }}
                    </h3>
                    <p class="text-gray-700 dark:text-gray-300">
                        {{ content.partnerBenefits.benefit3.description }}
                    </p>
                </div>
                <div
                    class="bg-gradient-to-br from-purple-500/20 via-indigo-500/20 to-blue-500/20 dark:from-indigo-900/30 dark:via-purple-900/30 dark:to-blue-900/30 border border-indigo-400/30 dark:border-gray-700/40 backdrop-blur-md rounded-2xl p-6"
                >
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">
                        {{ content.partnerBenefits.benefit4.title }}
                    </h3>
                    <p class="text-gray-700 dark:text-gray-300">
                        {{ content.partnerBenefits.benefit4.description }}
                    </p>
                </div>
            </div>
        </section>

        <!-- Payout Methods Section -->
        <section
            id="become-supplier-page-payout-methods"
            v-intersect="intersectConfig"
            class="mb-20"
        >
            <h2
                class="text-3xl md:text-4xl lg:text-5xl font-medium text-gray-900 dark:text-white text-center mb-4"
            >
                {{ content.payoutMethods.title }}
            </h2>
            <p class="text-center text-gray-600 dark:text-gray-400 mb-8 text-lg max-w-3xl mx-auto">
                {{ content.payoutMethods.subtitle }}
            </p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div
                    v-for="(method, index) in content.payoutMethods.methods"
                    :key="index"
                    class="bg-gradient-to-br from-purple-500/20 via-indigo-500/20 to-blue-500/20 dark:from-indigo-900/30 dark:via-purple-900/30 dark:to-blue-900/30 border border-indigo-400/30 dark:border-gray-700/40 backdrop-blur-md rounded-2xl p-6 text-center"
                >
                    <div class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ method }}
                    </div>
                </div>
            </div>
            <div class="text-center">
                <button
                    class="bg-blue-500 dark:bg-blue-600 hover:bg-blue-600 dark:hover:bg-blue-700 text-white font-medium px-8 py-3 rounded-lg text-lg transition-colors"
                    @click="becomeSupportRedirect"
                >
                    {{ content.payoutMethods.ctaButton }}
                </button>
            </div>
        </section>

        <!-- FAQ Section -->
        <section id="become-supplier-page-faq" v-intersect="intersectConfig" class="mb-20">
            <h2
                class="text-3xl md:text-4xl lg:text-5xl font-medium text-gray-900 dark:text-white text-center mb-12"
            >
                {{ content.faq.title }}
            </h2>
            <div class="max-w-3xl mx-auto space-y-4">
                <div
                    v-for="(question, index) in [
                        content.faq.question1,
                        content.faq.question2,
                        content.faq.question3,
                        content.faq.question4
                    ]"
                    :key="index"
                    class="bg-gradient-to-br from-purple-500/20 via-indigo-500/20 to-blue-500/20 dark:from-indigo-900/30 dark:via-purple-900/30 dark:to-blue-900/30 border border-indigo-400/30 dark:border-gray-700/40 backdrop-blur-md rounded-2xl overflow-hidden"
                >
                    <button
                        class="w-full px-6 py-4 text-left flex items-center justify-between text-gray-900 dark:text-white font-semibold hover:bg-opacity-50 transition-colors"
                        @click="toggleFaq(index)"
                    >
                        <span>{{ question.question }}</span>
                        <svg
                            class="w-5 h-5 transition-transform"
                            :class="{ 'rotate-180': openFaqIndex === index }"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 9l-7 7-7-7"
                            />
                        </svg>
                    </button>
                    <div
                        v-if="openFaqIndex === index"
                        class="px-6 pb-4 text-gray-700 dark:text-gray-300"
                    >
                        {{ question.answer }}
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useSeo } from '@/composables/useSeo';
import { useOptionStore } from '@/stores/options';
import { useSiteContentStore } from '@/stores/siteContent';

const { t } = useI18n();

// SEO мета-теги
useSeo({
    title: () => 'Стать поставщиком',
    description: () => 'Станьте поставщиком на маркетплейсе Account Arena и зарабатывайте на продаже цифровых аккаунтов',
    ogImage: '/img/logo_trans.webp'
});

const { t, locale } = useI18n();
const optionStore = useOptionStore();
const siteContentStore = useSiteContentStore();
const openFaqIndex = ref<number | null>(null);

// Shared intersection observer configuration
const intersectConfig = {
    class: 'animate-fade-in-up',
    once: true,
    threshold: 0.08,
    rootMargin: '0px 0px -40% 0px'
};

// Load site content on mount
onMounted(() => {
    siteContentStore.loadContent();
});

// Single computed object containing all become supplier content
const content = computed(() => {
    const data = siteContentStore.becomeSupplier(locale.value);
    // Fallback to i18n if content is not loaded
    return {
        welcomeBanner: {
            headline: data?.welcomeBanner?.headline || t('becomeSupplier.welcomeBanner.headline'),
            subtitle: data?.welcomeBanner?.subtitle || t('becomeSupplier.welcomeBanner.subtitle'),
            ctaButton: data?.welcomeBanner?.ctaButton || t('becomeSupplier.welcomeBanner.ctaButton')
        },
        supplierStats: {
            title: data?.supplierStats?.title || t('becomeSupplier.supplierStats.title'),
            activeSuppliers:
                data?.supplierStats?.activeSuppliers ||
                t('becomeSupplier.supplierStats.activeSuppliers'),
            totalSales:
                data?.supplierStats?.totalSales || t('becomeSupplier.supplierStats.totalSales'),
            averageRating:
                data?.supplierStats?.averageRating ||
                t('becomeSupplier.supplierStats.averageRating'),
            countries: data?.supplierStats?.countries || t('becomeSupplier.supplierStats.countries')
        },
        processSteps: {
            title: data?.processSteps?.title || t('becomeSupplier.processSteps.title'),
            step1: {
                title:
                    data?.processSteps?.step1?.title ||
                    t('becomeSupplier.processSteps.step1.title'),
                description:
                    data?.processSteps?.step1?.description ||
                    t('becomeSupplier.processSteps.step1.description')
            },
            step2: {
                title:
                    data?.processSteps?.step2?.title ||
                    t('becomeSupplier.processSteps.step2.title'),
                description:
                    data?.processSteps?.step2?.description ||
                    t('becomeSupplier.processSteps.step2.description')
            },
            step3: {
                title:
                    data?.processSteps?.step3?.title ||
                    t('becomeSupplier.processSteps.step3.title'),
                description:
                    data?.processSteps?.step3?.description ||
                    t('becomeSupplier.processSteps.step3.description')
            },
            step4: {
                title:
                    data?.processSteps?.step4?.title ||
                    t('becomeSupplier.processSteps.step4.title'),
                description:
                    data?.processSteps?.step4?.description ||
                    t('becomeSupplier.processSteps.step4.description')
            }
        },
        digitalGoodsCategories: {
            title:
                data?.digitalGoodsCategories?.title ||
                t('becomeSupplier.digitalGoodsCategories.title'),
            subtitle:
                data?.digitalGoodsCategories?.subtitle ||
                t('becomeSupplier.digitalGoodsCategories.subtitle'),
            socialMedia:
                data?.digitalGoodsCategories?.socialMedia ||
                t('becomeSupplier.digitalGoodsCategories.socialMedia'),
            gaming:
                data?.digitalGoodsCategories?.gaming ||
                t('becomeSupplier.digitalGoodsCategories.gaming'),
            streaming:
                data?.digitalGoodsCategories?.streaming ||
                t('becomeSupplier.digitalGoodsCategories.streaming'),
            software:
                data?.digitalGoodsCategories?.software ||
                t('becomeSupplier.digitalGoodsCategories.software'),
            other:
                data?.digitalGoodsCategories?.other ||
                t('becomeSupplier.digitalGoodsCategories.other')
        },
        restrictedItems: {
            title: data?.restrictedItems?.title || t('becomeSupplier.restrictedItems.title'),
            subtitle:
                data?.restrictedItems?.subtitle || t('becomeSupplier.restrictedItems.subtitle'),
            items:
                Array.isArray(data?.restrictedItems?.items) && data.restrictedItems.items.length > 0
                    ? data.restrictedItems.items
                    : Array.isArray(t('becomeSupplier.restrictedItems.items'))
                      ? t('becomeSupplier.restrictedItems.items')
                      : [],
            contactMessage:
                data?.restrictedItems?.contactMessage ||
                t('becomeSupplier.restrictedItems.contactMessage')
        },
        partnerBenefits: {
            title: data?.partnerBenefits?.title || t('becomeSupplier.partnerBenefits.title'),
            benefit1: {
                title:
                    data?.partnerBenefits?.benefit1?.title ||
                    t('becomeSupplier.partnerBenefits.benefit1.title'),
                description:
                    data?.partnerBenefits?.benefit1?.description ||
                    t('becomeSupplier.partnerBenefits.benefit1.description')
            },
            benefit2: {
                title:
                    data?.partnerBenefits?.benefit2?.title ||
                    t('becomeSupplier.partnerBenefits.benefit2.title'),
                description:
                    data?.partnerBenefits?.benefit2?.description ||
                    t('becomeSupplier.partnerBenefits.benefit2.description')
            },
            benefit3: {
                title:
                    data?.partnerBenefits?.benefit3?.title ||
                    t('becomeSupplier.partnerBenefits.benefit3.title'),
                description:
                    data?.partnerBenefits?.benefit3?.description ||
                    t('becomeSupplier.partnerBenefits.benefit3.description')
            },
            benefit4: {
                title:
                    data?.partnerBenefits?.benefit4?.title ||
                    t('becomeSupplier.partnerBenefits.benefit4.title'),
                description:
                    data?.partnerBenefits?.benefit4?.description ||
                    t('becomeSupplier.partnerBenefits.benefit4.description')
            }
        },
        payoutMethods: {
            title: data?.payoutMethods?.title || t('becomeSupplier.payoutMethods.title'),
            subtitle: data?.payoutMethods?.subtitle || t('becomeSupplier.payoutMethods.subtitle'),
            methods:
                Array.isArray(data?.payoutMethods?.methods) && data.payoutMethods.methods.length > 0
                    ? data.payoutMethods.methods
                    : Array.isArray(t('becomeSupplier.payoutMethods.methods'))
                      ? t('becomeSupplier.payoutMethods.methods')
                      : [],
            ctaButton: data?.payoutMethods?.ctaButton || t('becomeSupplier.payoutMethods.ctaButton')
        },
        faq: {
            title: data?.faq?.title || t('becomeSupplier.faq.title'),
            question1: {
                question:
                    data?.faq?.question1?.question || t('becomeSupplier.faq.question1.question'),
                answer: data?.faq?.question1?.answer || t('becomeSupplier.faq.question1.answer')
            },
            question2: {
                question:
                    data?.faq?.question2?.question || t('becomeSupplier.faq.question2.question'),
                answer: data?.faq?.question2?.answer || t('becomeSupplier.faq.question2.answer')
            },
            question3: {
                question:
                    data?.faq?.question3?.question || t('becomeSupplier.faq.question3.question'),
                answer: data?.faq?.question3?.answer || t('becomeSupplier.faq.question3.answer')
            },
            question4: {
                question:
                    data?.faq?.question4?.question || t('becomeSupplier.faq.question4.question'),
                answer: data?.faq?.question4?.answer || t('becomeSupplier.faq.question4.answer')
            }
        }
    };
});

function toggleFaq(index: number) {
    openFaqIndex.value = openFaqIndex.value === index ? null : index;
}

const becomeSupportRedirect = () => {
    window.location.href = optionStore.getOption(
        'support_chat_telegram_link',
        'https://t.me/support'
    );
};
</script>

<style scoped>
.animate-fade-in-up {
    animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
