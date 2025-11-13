<template>
    <div
        class="grid grid-cols-1 px-5 sm:px-0 sm:grid-cols-2 md:grid-cols-3 gap-[20px] sm:gap-[30px] md:gap-[50px]"
    >
        <div class="text-center flex flex-col items-center justify-start">
            <div class="access-icon mb-4">
                <img src="/img/emoji-star.png" class="max-h-[40px] max-w-[40px]" alt="Access" />
            </div>
            <h3 class="text-[20px] mb-6 dark:text-white">{{ accessTitle }}</h3>
            <p class="text-gray-500 dark:text-gray-400 leading-5">
                {{ accessDescription }}
            </p>
        </div>
        <div class="text-center flex flex-col items-center justify-start">
            <div class="pricing-icon mb-4">
                <img src="/img/emoji-cash.png" class="max-h-[40px] max-w-[40px]" alt="Pricing" />
            </div>
            <h3 class="text-[20px] mb-6 dark:text-white">{{ pricingTitle }}</h3>
            <p class="text-gray-500 dark:text-gray-400 leading-5">
                {{ pricingDescription }}
            </p>
        </div>
        <div class="text-center flex flex-col items-center justify-start">
            <div class="refund-icon mb-4">
                <img src="/img/emoji-hands.png" class="max-h-[40px] max-w-[40px]" alt="Refund" />
            </div>
            <h3 class="text-[20px] mb-6 dark:text-white">{{ refundTitle }}</h3>
            <p class="text-gray-500 dark:text-gray-400 leading-5">
                {{ refundDescription }}
            </p>
        </div>
        <div class="text-center flex flex-col items-center justify-start">
            <div class="activation-icon mb-4">
                <img
                    src="/img/emoji-roket.png"
                    class="max-h-[40px] max-w-[40px]"
                    alt="Activation"
                />
            </div>
            <h3 class="text-[20px] mb-6 dark:text-white">
                {{ activationTitle }}
            </h3>
            <p class="text-gray-500 dark:text-gray-400 leading-5">
                {{ activationDescription }}
            </p>
        </div>
        <div class="text-center flex flex-col items-center justify-start">
            <div class="support-icon mb-4">
                <img src="/img/emoji-proger.png" class="max-h-[40px] max-w-[40px]" alt="Support" />
            </div>
            <h3 class="text-[20px] mb-6 dark:text-white">
                {{ supportTitle }}
            </h3>
            <p class="text-gray-500 dark:text-gray-400 leading-5">
                {{ supportDescription }}
            </p>
        </div>
        <div class="text-center flex flex-col items-center justify-start">
            <div class="payment-icon mb-4">
                <img src="/img/emoji-card.png" class="max-h-[40px] max-w-[40px]" alt="Payment" />
            </div>
            <h3 class="text-[20px] mb-6 dark:text-white">
                {{ paymentTitle }}
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 leading-5">
                {{ paymentDescription }}
            </p>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useSiteContentStore } from '@/stores/siteContent';

const { t, locale } = useI18n();
const siteContentStore = useSiteContentStore();

const promoteContent = computed(() => siteContentStore.promote(locale.value));

const accessTitle = computed(
    () => promoteContent.value?.access?.title || t('promote.access.title')
);
const accessDescription = computed(
    () => promoteContent.value?.access?.description || t('promote.access.description')
);

const pricingTitle = computed(
    () => promoteContent.value?.pricing?.title || t('promote.pricing.title')
);
const pricingDescription = computed(
    () => promoteContent.value?.pricing?.description || t('promote.pricing.description')
);

const refundTitle = computed(
    () => promoteContent.value?.refund?.title || t('promote.refund.title')
);
const refundDescription = computed(
    () => promoteContent.value?.refund?.description || t('promote.refund.description')
);

const activationTitle = computed(
    () => promoteContent.value?.activation?.title || t('promote.activation.title')
);
const activationDescription = computed(
    () => promoteContent.value?.activation?.description || t('promote.activation.description')
);

const supportTitle = computed(
    () => promoteContent.value?.support?.title || t('promote.support.title')
);
const supportDescription = computed(
    () => promoteContent.value?.support?.description || t('promote.support.description')
);

const paymentTitle = computed(
    () => promoteContent.value?.payment?.title || t('promote.payment.title')
);
const paymentDescription = computed(
    () => promoteContent.value?.payment?.description || t('promote.payment.description')
);
</script>

<style scoped>
.access-icon {
    background: linear-gradient(92.47deg, #0047ff 0%, #5a60f6 100%);
    border-radius: 50%;
    width: 100px;
    height: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.pricing-icon {
    background: linear-gradient(92.47deg, #f66a1b 0%, #f8aa36 100%);
    border-radius: 50%;
    width: 100px;
    height: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.refund-icon {
    background: linear-gradient(92.47deg, #2cc7a4 0%, #7ee1ce 100%);
    border-radius: 50%;
    width: 100px;
    height: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.activation-icon {
    background: linear-gradient(92.47deg, #ffb800 0%, #ffe49e 100%);
    border-radius: 50%;
    width: 100px;
    height: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.support-icon {
    background: linear-gradient(92.47deg, #fa73ae 0%, #febbda 100%);
    border-radius: 50%;
    width: 100px;
    height: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.payment-icon {
    background: linear-gradient(92.47deg, #1b8df6 0%, #74bcff 100%);
    border-radius: 50%;
    width: 100px;
    height: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
