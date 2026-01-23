<template>
    <div class="guarantees-page">
        <div class="container mx-auto px-4 py-16 max-w-4xl">
            <Breadcrumbs :crumbs="[{ name: $t('guarantees.title'), path: '/guarantees' }]" />
            <div class="text-center mb-16">
                <h1 class="text-4xl font-bold mb-4 gradient-text">{{ $t('guarantees.title') }}</h1>
                <p class="text-lg text-gray-600 dark:text-gray-400">{{ $t('guarantees.subtitle') }}</p>
            </div>

            <div class="grid gap-8">
                <div v-for="(item, index) in items" :key="index" class="guarantee-card theme-card">
                    <div class="flex items-start gap-4">
                        <div class="icon-wrapper">
                            <component :is="item.icon" class="w-8 h-8 text-blue-500" />
                        </div>
                        <div>
                            <h3 class="text-xl font-semibold mb-2">{{ item.title }}</h3>
                            <p class="text-gray-600 dark:text-gray-400 leading-relaxed">{{ item.description }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-16 p-8 bg-blue-50 dark:bg-blue-900/20 rounded-2xl border border-blue-100 dark:border-blue-800">
                <h2 class="text-2xl font-bold mb-4 text-center">{{ $t('guarantees.safety_title') }}</h2>
                <p class="text-center text-gray-700 dark:text-gray-300">
                    {{ $t('guarantees.safety_text') }}
                </p>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { 
    ShieldCheck, 
    Zap, 
    CreditCard, 
    Headphones, 
    RefreshCcw 
} from 'lucide-vue-next';

const { t } = useI18n();

const items = computed(() => {
    return [
        {
            icon: Zap,
            title: t('guarantees.items.delivery.title'),
            description: t('guarantees.items.delivery.description')
        },
        {
            icon: ShieldCheck,
            title: t('guarantees.items.quality.title'),
            description: t('guarantees.items.quality.description')
        },
        {
            icon: CreditCard,
            title: t('guarantees.items.payment.title'),
            description: t('guarantees.items.payment.description')
        },
        {
            icon: Headphones,
            title: t('guarantees.items.support.title'),
            description: t('guarantees.items.support.description')
        },
        {
            icon: RefreshCcw,
            title: t('guarantees.items.replacement.title'),
            description: t('guarantees.items.replacement.description')
        }
    ];
});
</script>

<style scoped>
.guarantee-card {
    padding: 2rem;
    transition: transform 0.3s ease;
}

.guarantee-card:hover {
    transform: translateY(-4px);
}

.icon-wrapper {
    background: rgba(59, 130, 246, 0.1);
    padding: 1rem;
    border-radius: 1rem;
    flex-shrink: 0;
}

.gradient-text {
    background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
</style>
