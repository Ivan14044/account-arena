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
            >
                {{ $t('becomeSupplier.welcomeBanner.headline') }}
            </h1>
            <p class="text-xl md:text-2xl text-gray-600 dark:text-gray-400 mb-8 max-w-3xl mx-auto">
                {{ $t('becomeSupplier.welcomeBanner.subtitle') }}
            </p>
            <button
                class="bg-blue-500 dark:bg-blue-600 hover:bg-blue-600 dark:hover:bg-blue-700 text-white font-medium px-8 py-3 rounded-lg text-lg transition-colors"
                @click="navigateToRegister"
            >
                {{ $t('becomeSupplier.welcomeBanner.ctaButton') }}
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
                {{ $t('becomeSupplier.supplierStats.title') }}
            </h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div
                    class="bg-gradient-to-br from-purple-500/20 via-indigo-500/20 to-blue-500/20 dark:from-indigo-900/30 dark:via-purple-900/30 dark:to-blue-900/30 border border-indigo-400/30 dark:border-gray-700/40 backdrop-blur-md rounded-2xl p-6 text-center"
                >
                    <div class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-2">
                        500+
                    </div>
                    <div class="text-sm md:text-base text-gray-600 dark:text-gray-400">
                        {{ $t('becomeSupplier.supplierStats.activeSuppliers') }}
                    </div>
                </div>
                <div
                    class="bg-gradient-to-br from-purple-500/20 via-indigo-500/20 to-blue-500/20 dark:from-indigo-900/30 dark:via-purple-900/30 dark:to-blue-900/30 border border-indigo-400/30 dark:border-gray-700/40 backdrop-blur-md rounded-2xl p-6 text-center"
                >
                    <div class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-2">
                        50K+
                    </div>
                    <div class="text-sm md:text-base text-gray-600 dark:text-gray-400">
                        {{ $t('becomeSupplier.supplierStats.totalSales') }}
                    </div>
                </div>
                <div
                    class="bg-gradient-to-br from-purple-500/20 via-indigo-500/20 to-blue-500/20 dark:from-indigo-900/30 dark:via-purple-900/30 dark:to-blue-900/30 border border-indigo-400/30 dark:border-gray-700/40 backdrop-blur-md rounded-2xl p-6 text-center"
                >
                    <div class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-2">
                        4.8
                    </div>
                    <div class="text-sm md:text-base text-gray-600 dark:text-gray-400">
                        {{ $t('becomeSupplier.supplierStats.averageRating') }}
                    </div>
                </div>
                <div
                    class="bg-gradient-to-br from-purple-500/20 via-indigo-500/20 to-blue-500/20 dark:from-indigo-900/30 dark:via-purple-900/30 dark:to-blue-900/30 border border-indigo-400/30 dark:border-gray-700/40 backdrop-blur-md rounded-2xl p-6 text-center"
                >
                    <div class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-2">
                        50+
                    </div>
                    <div class="text-sm md:text-base text-gray-600 dark:text-gray-400">
                        {{ $t('becomeSupplier.supplierStats.countries') }}
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
                {{ $t('becomeSupplier.processSteps.title') }}
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
                        {{ $t('becomeSupplier.processSteps.step1.title') }}
                    </h3>
                    <p class="text-gray-700 dark:text-gray-300">
                        {{ $t('becomeSupplier.processSteps.step1.description') }}
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
                        {{ $t('becomeSupplier.processSteps.step2.title') }}
                    </h3>
                    <p class="text-gray-700 dark:text-gray-300">
                        {{ $t('becomeSupplier.processSteps.step2.description') }}
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
                        {{ $t('becomeSupplier.processSteps.step3.title') }}
                    </h3>
                    <p class="text-gray-700 dark:text-gray-300">
                        {{ $t('becomeSupplier.processSteps.step3.description') }}
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
                        {{ $t('becomeSupplier.processSteps.step4.title') }}
                    </h3>
                    <p class="text-gray-700 dark:text-gray-300">
                        {{ $t('becomeSupplier.processSteps.step4.description') }}
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
                {{ $t('becomeSupplier.digitalGoodsCategories.title') }}
            </h2>
            <p class="text-center text-gray-600 dark:text-gray-400 mb-12 text-lg">
                {{ $t('becomeSupplier.digitalGoodsCategories.subtitle') }}
            </p>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6">
                <div
                    class="bg-gradient-to-br from-purple-500/20 via-indigo-500/20 to-blue-500/20 dark:from-indigo-900/30 dark:via-purple-900/30 dark:to-blue-900/30 border border-indigo-400/30 dark:border-gray-700/40 backdrop-blur-md rounded-2xl p-6 text-center"
                >
                    <div class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ $t('becomeSupplier.digitalGoodsCategories.socialMedia') }}
                    </div>
                </div>
                <div
                    class="bg-gradient-to-br from-purple-500/20 via-indigo-500/20 to-blue-500/20 dark:from-indigo-900/30 dark:via-purple-900/30 dark:to-blue-900/30 border border-indigo-400/30 dark:border-gray-700/40 backdrop-blur-md rounded-2xl p-6 text-center"
                >
                    <div class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ $t('becomeSupplier.digitalGoodsCategories.gaming') }}
                    </div>
                </div>
                <div
                    class="bg-gradient-to-br from-purple-500/20 via-indigo-500/20 to-blue-500/20 dark:from-indigo-900/30 dark:via-purple-900/30 dark:to-blue-900/30 border border-indigo-400/30 dark:border-gray-700/40 backdrop-blur-md rounded-2xl p-6 text-center"
                >
                    <div class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ $t('becomeSupplier.digitalGoodsCategories.streaming') }}
                    </div>
                </div>
                <div
                    class="bg-gradient-to-br from-purple-500/20 via-indigo-500/20 to-blue-500/20 dark:from-indigo-900/30 dark:via-purple-900/30 dark:to-blue-900/30 border border-indigo-400/30 dark:border-gray-700/40 backdrop-blur-md rounded-2xl p-6 text-center"
                >
                    <div class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ $t('becomeSupplier.digitalGoodsCategories.software') }}
                    </div>
                </div>
                <div
                    class="bg-gradient-to-br from-purple-500/20 via-indigo-500/20 to-blue-500/20 dark:from-indigo-900/30 dark:via-purple-900/30 dark:to-blue-900/30 border border-indigo-400/30 dark:border-gray-700/40 backdrop-blur-md rounded-2xl p-6 text-center"
                >
                    <div class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ $t('becomeSupplier.digitalGoodsCategories.other') }}
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
                {{ $t('becomeSupplier.restrictedItems.title') }}
            </h2>
            <p class="text-center text-gray-600 dark:text-gray-400 mb-8 text-lg">
                {{ $t('becomeSupplier.restrictedItems.subtitle') }}
            </p>
            <div
                class="bg-gradient-to-br from-red-500/20 via-orange-500/20 to-yellow-500/20 dark:from-red-900/30 dark:via-orange-900/30 dark:to-yellow-900/30 border border-red-400/30 dark:border-gray-700/40 backdrop-blur-md rounded-2xl p-8 max-w-3xl mx-auto"
            >
                <ul class="space-y-4 mb-6">
                    <li
                        v-for="(item, index) in restrictedItems"
                        :key="index"
                        class="flex items-start text-gray-900 dark:text-white"
                    >
                        <span class="text-red-500 dark:text-red-400 mr-3">✗</span>
                        <span>{{ item }}</span>
                    </li>
                </ul>
                <p class="text-center text-gray-700 dark:text-gray-300">
                    {{ $t('becomeSupplier.restrictedItems.contactMessage') }}
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
                {{ $t('becomeSupplier.partnerBenefits.title') }}
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div
                    class="bg-gradient-to-br from-purple-500/20 via-indigo-500/20 to-blue-500/20 dark:from-indigo-900/30 dark:via-purple-900/30 dark:to-blue-900/30 border border-indigo-400/30 dark:border-gray-700/40 backdrop-blur-md rounded-2xl p-6"
                >
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">
                        {{ $t('becomeSupplier.partnerBenefits.benefit1.title') }}
                    </h3>
                    <p class="text-gray-700 dark:text-gray-300">
                        {{ $t('becomeSupplier.partnerBenefits.benefit1.description') }}
                    </p>
                </div>
                <div
                    class="bg-gradient-to-br from-purple-500/20 via-indigo-500/20 to-blue-500/20 dark:from-indigo-900/30 dark:via-purple-900/30 dark:to-blue-900/30 border border-indigo-400/30 dark:border-gray-700/40 backdrop-blur-md rounded-2xl p-6"
                >
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">
                        {{ $t('becomeSupplier.partnerBenefits.benefit2.title') }}
                    </h3>
                    <p class="text-gray-700 dark:text-gray-300">
                        {{ $t('becomeSupplier.partnerBenefits.benefit2.description') }}
                    </p>
                </div>
                <div
                    class="bg-gradient-to-br from-purple-500/20 via-indigo-500/20 to-blue-500/20 dark:from-indigo-900/30 dark:via-purple-900/30 dark:to-blue-900/30 border border-indigo-400/30 dark:border-gray-700/40 backdrop-blur-md rounded-2xl p-6"
                >
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">
                        {{ $t('becomeSupplier.partnerBenefits.benefit3.title') }}
                    </h3>
                    <p class="text-gray-700 dark:text-gray-300">
                        {{ $t('becomeSupplier.partnerBenefits.benefit3.description') }}
                    </p>
                </div>
                <div
                    class="bg-gradient-to-br from-purple-500/20 via-indigo-500/20 to-blue-500/20 dark:from-indigo-900/30 dark:via-purple-900/30 dark:to-blue-900/30 border border-indigo-400/30 dark:border-gray-700/40 backdrop-blur-md rounded-2xl p-6"
                >
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">
                        {{ $t('becomeSupplier.partnerBenefits.benefit4.title') }}
                    </h3>
                    <p class="text-gray-700 dark:text-gray-300">
                        {{ $t('becomeSupplier.partnerBenefits.benefit4.description') }}
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
                {{ $t('becomeSupplier.payoutMethods.title') }}
            </h2>
            <p class="text-center text-gray-600 dark:text-gray-400 mb-8 text-lg max-w-3xl mx-auto">
                {{ $t('becomeSupplier.payoutMethods.subtitle') }}
            </p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div
                    v-for="(method, index) in payoutMethods"
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
                    @click="navigateToRegister"
                >
                    {{ $t('becomeSupplier.payoutMethods.ctaButton') }}
                </button>
            </div>
        </section>

        <!-- FAQ Section -->
        <section id="become-supplier-page-faq" v-intersect="intersectConfig" class="mb-20">
            <h2
                class="text-3xl md:text-4xl lg:text-5xl font-medium text-gray-900 dark:text-white text-center mb-12"
            >
                {{ $t('becomeSupplier.faq.title') }}
            </h2>
            <div class="max-w-3xl mx-auto space-y-4">
                <div
                    v-for="(question, index) in faqItems"
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
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';

const { t } = useI18n();
const router = useRouter();
const openFaqIndex = ref<number | null>(null);

// Shared intersection observer configuration
const intersectConfig = {
    class: 'animate-fade-in-up',
    once: true,
    threshold: 0.08,
    rootMargin: '0px 0px -40% 0px'
};

const restrictedItems = computed(() => {
    const items = t('becomeSupplier.restrictedItems.items');
    return Array.isArray(items) ? items : [];
});

const payoutMethods = computed(() => {
    const methods = t('becomeSupplier.payoutMethods.methods');
    return Array.isArray(methods) ? methods : [];
});

const faqItems = computed(() => [
    {
        question: t('becomeSupplier.faq.question1.question'),
        answer: t('becomeSupplier.faq.question1.answer')
    },
    {
        question: t('becomeSupplier.faq.question2.question'),
        answer: t('becomeSupplier.faq.question2.answer')
    },
    {
        question: t('becomeSupplier.faq.question3.question'),
        answer: t('becomeSupplier.faq.question3.answer')
    },
    {
        question: t('becomeSupplier.faq.question4.question'),
        answer: t('becomeSupplier.faq.question4.answer')
    }
]);

function toggleFaq(index: number) {
    openFaqIndex.value = openFaqIndex.value === index ? null : index;
}

function navigateToRegister() {
    router.push('/register');
}
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
