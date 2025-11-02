<template>
    <div class="space-y-3 text-dark dark:text-white">
        <div :class="paymentClass('card')" @click="selectPaymentMethod('card')">
            <div class="flex items-center gap-3 w-100">
                <div class="w-10 h-10 rounded flex items-center justify-center">
                    <svg
                        width="28"
                        height="20"
                        viewBox="0 0 28 20"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <rect
                            x="0.5"
                            y="1"
                            width="27"
                            height="18"
                            rx="3"
                            stroke="currentColor"
                            stroke-opacity="0.9"
                        />
                        <rect
                            x="3"
                            y="6"
                            width="8"
                            height="2"
                            rx="1"
                            fill="currentColor"
                            fill-opacity="0.9"
                        />
                        <rect
                            x="3"
                            y="10"
                            width="5"
                            height="2"
                            rx="1"
                            fill="currentColor"
                            fill-opacity="0.9"
                        />
                    </svg>
                </div>

                <div class="flex-1">
                    <div class="font-medium">{{ $t('checkout.credit_card') }}</div>
                    <div class="text-xs">{{ $t('checkout.credit_card_placeholder') }}</div>
                    <div class="mt-2 flex items-center gap-2">
                        <div class="icon-pill" title="Apple Pay">
                            <img
                                :src="applePayLogo"
                                alt="Apple Pay"
                                class="h-3 object-contain object-center"
                            />
                        </div>
                        <div class="icon-pill" title="Google Pay">
                            <img
                                :src="googlePayLogo"
                                alt="google pay"
                                class="h-3 object-contain object-center"
                            />
                        </div>
                        <div class="icon-pill" title="Visa">
                            <img
                                :src="visaLogo"
                                alt="Visa"
                                class="h-4 object-contain object-center"
                            />
                        </div>
                        <div class="icon-pill" title="Mastercard">
                            <img
                                :src="mastercardLogo"
                                alt="mastercard"
                                class="h-4 object-contain object-center"
                            />
                        </div>
                    </div>
                </div>

                <div class="ml-auto">
                    <div
                        :class="[
                            'w-4 h-4 rounded-full border-2 transition-all duration-300',
                            selectedPaymentMethod === 'card'
                                ? 'border-blue-400 bg-blue-400'
                                : 'border-gray-800 dark:border-gray-100'
                        ]"
                    />
                </div>
            </div>
        </div>

        <div :class="paymentClass('crypto')" @click="selectPaymentMethod('crypto')">
            <div class="flex items-center gap-3 w-100">
                <div class="w-10 h-10 rounded flex items-center justify-center">
                    <span class="text-xl">₿</span>
                </div>

                <div class="flex-1">
                    <div class="font-medium">{{ $t('checkout.crypto') }}</div>
                    <div class="text-xs">{{ $t('checkout.crypto_placeholder') }}</div>

                    <div class="mt-2 flex items-center gap-2">
                        <div class="icon-pill" title="Bitcoin">
                            <img
                                :src="bitcoinLogo"
                                alt="bitcoin"
                                class="h-4 object-contain object-center"
                            />
                        </div>
                        <div class="icon-pill" title="Ethereum">
                            <img
                                :src="ethereumLogo"
                                alt="ethereum"
                                class="h-4 object-contain object-center"
                            />
                        </div>
                        <div class="icon-pill" title="Tether">
                            <img
                                :src="tetherLogo"
                                alt="tether"
                                class="h-4 object-contain object-center"
                            />
                        </div>
                    </div>
                </div>

                <div class="ml-auto">
                    <div
                        :class="[
                            'w-4 h-4 rounded-full border-2 transition-all duration-300',
                            selectedPaymentMethod === 'crypto'
                                ? 'border-blue-400 bg-blue-400'
                                : 'border-gray-800 dark:border-gray-100'
                        ]"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { computed } from 'vue';
import { useCartStore } from '@/stores/cart';
import applePayLogo from '@/assets/payment-icons/Apple_Pay_logo.svg.png';
import googlePayLogo from '@/assets/payment-icons/Google_Pay_Logo.svg.png';
import visaLogo from '@/assets/payment-icons/VISA-logo.png';
import mastercardLogo from '@/assets/payment-icons/mastercard-logo.png';
import bitcoinLogo from '@/assets/payment-icons/Bitcoin.svg.png';
import ethereumLogo from '@/assets/payment-icons/Ethereum_logo_translucent.png';
import tetherLogo from '@/assets/payment-icons/tether-logo.png';

const props = defineProps<{
    modelValue: 'card' | 'crypto' | '';
    disabled?: boolean;
}>();
const emit = defineEmits<{
    (e: 'update:modelValue', value: 'card' | 'crypto' | ''): void;
}>();

const selectedPaymentMethod = ref(props.modelValue);
const cartStore = useCartStore();

watch(
    () => props.modelValue,
    val => {
        selectedPaymentMethod.value = val;
    }
);

const hasTrial = computed(() => {
    return cartStore.items.some(service => cartStore.getSubscriptionType(service.id) === 'trial');
});

const selectPaymentMethod = (method: 'card' | 'crypto') => {
    if (props.disabled) return;
    selectedPaymentMethod.value = method;
    emit('update:modelValue', method);
};

const paymentClass = (method: 'card' | 'crypto') => {
    return [
        'payment-method cursor-pointer rounded-lg p-4 border-2 flex items-center gap-3',
        selectedPaymentMethod.value === method
            ? 'border-blue-400 bg-blue-400/10'
            : 'border-transparent glass-card hover:border-blue-400/50',
        hasTrial.value && method === 'crypto' ? 'opacity-50 pointer-events-none' : '',
        props.disabled ? 'opacity-50 pointer-events-none' : ''
    ];
};
</script>

<style scoped>
.icon-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 34px;
    height: 28px;
    padding: 0 8px;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.3);
    border: 1px solid rgba(255, 255, 255, 0.04);
    font-size: 12px;
}
</style>
