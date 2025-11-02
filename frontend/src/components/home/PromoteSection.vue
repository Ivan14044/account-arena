<template>
    <div
        class="grid grid-cols-1 px-5 sm:px-0 sm:grid-cols-2 md:grid-cols-3 gap-[20px] sm:gap-[30px] md:gap-[50px]"
    >
        <div class="text-center flex flex-col items-center justify-start">
            <div class="access-icon mb-4">
                <img src="/img/emoji-star.png" class="max-h-[40px] max-w-[40px]" alt="Access" />
            </div>
            <h3 class="text-[20px] mb-6 dark:text-white">{{ $t('promote.access.title') }}</h3>
            <p class="text-gray-500 dark:text-gray-400 leading-5">
                {{ $t('promote.access.description') }}
            </p>
        </div>
        <div class="text-center flex flex-col items-center justify-start">
            <div class="pricing-icon mb-4">
                <img src="/img/emoji-cash.png" class="max-h-[40px] max-w-[40px]" alt="Pricing" />
            </div>
            <h3 class="text-[20px] mb-6 dark:text-white">{{ $t('promote.pricing.title') }}</h3>
            <p class="text-gray-500 dark:text-gray-400 leading-5">
                {{ $t('promote.pricing.description') }}
            </p>
        </div>
        <div class="text-center flex flex-col items-center justify-start">
            <div class="refund-icon mb-4">
                <img src="/img/emoji-hands.png" class="max-h-[40px] max-w-[40px]" alt="Refund" />
            </div>
            <h3 class="text-[20px] mb-6 dark:text-white">{{ $t('promote.refund.title') }}</h3>
            <p class="text-gray-500 dark:text-gray-400 leading-5">
                {{ $t('promote.refund.description') }}
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
                {{ $t('promote.activation.title') }}
            </h3>
            <p class="text-gray-500 dark:text-gray-400 leading-5">
                {{ $t('promote.activation.description') }}
            </p>
        </div>
        <div class="text-center flex flex-col items-center justify-start">
            <div class="support-icon mb-4">
                <img src="/img/emoji-proger.png" class="max-h-[40px] max-w-[40px]" alt="Support" />
            </div>
            <h3 class="text-[20px] mb-6 dark:text-white">
                {{ $t('promote.support.title') }}
            </h3>
            <p class="text-gray-500 dark:text-gray-400 leading-5">
                {{ $t('promote.support.description') }}
            </p>
        </div>
        <div class="text-center flex flex-col items-center justify-start">
            <div class="payment-icon mb-4">
                <img src="/img/emoji-card.png" class="max-h-[40px] max-w-[40px]" alt="Payment" />
            </div>
            <h3 class="text-[20px] mb-6 dark:text-white">
                {{ $t('promote.payment.title') }}
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 leading-5">
                {{ $t('promote.payment.description') }}
            </p>
        </div>
    </div>
</template>

<script setup lang="ts"></script>

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
