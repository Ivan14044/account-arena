<template>
    <div class="max-w-7xl mx-auto px-4 py-16 sm:px-6 lg:px-8 relative z-1">
        <div
            v-if="cartStore.items.length"
            class="mx-auto grid gap-8 lg:grid-cols-[minmax(0,1fr)_24rem]"
        >
            <div class="flex items-center justify-between mt-6 mb-2 lg:col-span-2">
                <h1
                    class="text-2xl font-medium md:text-4xl md:font-light text-dark dark:text-white"
                >
                    {{ $t('checkout.title') }}
                </h1>

                <BackLink />
            </div>

            <div class="min-w-0">
                <form class="space-y-6" @submit.prevent="handleSubmit">
                    <ServiceList />
                </form>
            </div>

            <div class="w-full lg:max-w-sm text-dark dark:text-white font-normal space-y-6">
                <div class="glass-card rounded-3xl p-6 relative overflow-hidden">
                    <div class="space-y-4">
                        <div class="space-y-2">
                            <div class="flex justify-between items-center">
                                <h3 class="font-normal">
                                    {{
                                        discountPercent > 0 || promoDiscountPercent > 0
                                            ? $t('checkout.original_total')
                                            : $t('checkout.total_amount')
                                    }}:
                                </h3>
                                <span
                                    :class="
                                        discountPercent > 0 || promoDiscountPercent > 0
                                            ? 'line-through opacity-60'
                                            : ''
                                    "
                                    class="font-normal"
                                >
                                    {{ formatCurrency(subtotalPaid) }}
                                </span>
                            </div>
                            <div
                                v-if="discountPercent > 0"
                                class="flex justify-between items-center text-sm"
                            >
                                <span>{{ $t('checkout.discount') }} ({{ discountPercent }}%)</span>
                                <span>-{{ formatCurrency(optionDiscountAmount) }}</span>
                            </div>
                            <div
                                v-if="promoDiscountPercent > 0"
                                class="flex justify-between items-center text-sm"
                            >
                                <span
                                    >{{ $t('checkout.promocode_discount') }} ({{
                                        promoDiscountPercent
                                    }}%)</span
                                >
                                <span>-{{ formatCurrency(promoDiscountAmount) }}</span>
                            </div>
                            <div
                                v-if="discountPercent > 0 || promoDiscountPercent > 0"
                                class="flex justify-between items-center"
                            >
                                <h3 class="font-normal">{{ $t('checkout.total_amount') }}:</h3>
                                <span class="font-normal">{{ formatCurrency(finalTotal) }}</span>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-stretch gap-0">
                                <input
                                    v-model.trim="inputCode"
                                    :placeholder="$t('checkout.promocode_placeholder')"
                                    class="flex-1 h-11 px-3 border rounded-l-lg rounded-r-none"
                                    :class="
                                        isApplied
                                            ? '!border-green-400 !bg-green-400/10'
                                            : '!border-gray-700 dark:!border-gray-300'
                                    "
                                    :disabled="isApplied"
                                />
                                <button
                                    class="h-11 w-12 grid place-items-center border border-l-0 rounded-r-lg rounded-l-none transition-all text-white disabled:opacity-50"
                                    :class="
                                        isApplied
                                            ? 'border-red-500 bg-red-500 hover:bg-red-600 dark:border-red-700 dark:bg-red-900 dark:hover:bg-red-800'
                                            : 'border-blue-500 bg-blue-500 hover:bg-blue-600 dark:border-blue-700 dark:bg-blue-900 dark:hover:bg-blue-800'
                                    "
                                    :disabled="promo.loading || (!isApplied && !inputCode)"
                                    :aria-label="
                                        isApplied
                                            ? $t('checkout.promocode_clear_aria')
                                            : $t('checkout.promocode_apply_aria')
                                    "
                                    @click.prevent="onPrimaryPromoClick"
                                >
                                    <X v-if="isApplied" class="w-5 h-5" />
                                    <Check v-else class="w-5 h-5" />
                                </button>
                            </div>
                        </div>

                        <div class="mt-3 rounded-lg overflow-hidden glass-card">
                            <div class="px-3 py-2">
                                <div class="flex items-center justify-between gap-3">
                                    <div
                                        class="text-left text-xs text-dark dark:text-white leading-snug"
                                    >
                                        <p class="font-medium">{{ servicesSummary }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <transition name="fade" appear>
                            <div
                                v-if="selectedPayment !== ''"
                                class="mt-3 rounded-lg overflow-hidden glass-card"
                            >
                                <div class="px-3 py-2">
                                    <div class="text-xs text-dark dark:text-white font-medium mb-1">
                                        {{ $t('checkout.next_payments') }}:
                                    </div>
                                    <transition-group
                                        name="fade-slide"
                                        tag="ul"
                                        class="text-xs space-y-1 text-dark dark:text-white"
                                    >
                                        <li
                                            v-for="(d, idx) in nextPaymentPerItem"
                                            :key="d.id"
                                            class="flex items-center gap-2"
                                            :style="{ transitionDelay: `${idx * 60}ms` }"
                                        >
                                            <span class="font-medium">{{ d.name }}</span>
                                            <span class="ml-2">—</span>
                                            <span class="ml-2">{{ d.date }}</span>
                                        </li>
                                    </transition-group>
                                </div>
                            </div>
                        </transition>

                        <div
                            v-if="upsellPercent > 0"
                            class="mt-3 rounded-lg overflow-hidden glass-card"
                        >
                            <div class="px-3 py-2">
                                <div class="flex items-center justify-between gap-3">
                                    <div
                                        class="text-left text-xs text-dark dark:text-white leading-snug"
                                    >
                                        <div class="whitespace-pre-line">
                                            <i18n-t keypath="checkout.upsell_generic" tag="span">
                                                <span class="font-semibold">1</span>
                                            </i18n-t>
                                        </div>
                                    </div>
                                    <span
                                        class="px-2 py-0.5 bg-green-500 text-white rounded-xl text-sm font-semibold"
                                    >
                                        -{{ upsellPercent }}%
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <BoxLoader v-if="promo.loading" :expand-padding="true" />
                </div>

                <div class="glass-card rounded-3xl p-6">
                    <h3 class="font-normal mb-4">{{ $t('checkout.payment_method') }}</h3>
                    <PaymentList v-model="selectedPayment" :disabled="isZeroTotalWithServices" />
                </div>

                <button
                    type="submit"
                    :disabled="
                        (isZeroTotalWithServices ? false : !selectedPayment) ||
                        cartStore.items.length === 0 ||
                        (hasTrial && selectedPayment === 'crypto')
                    "
                    class="checkout-btn w-full py-4 rounded-lg font-normal transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
                    @click.prevent="handleSubmit"
                >
                    {{ $t('checkout.submit') }}
                </button>
            </div>
        </div>
        <div v-else class="min-h-[30vh] grid place-items-center">
            <div class="w-full max-w-md mx-auto px-4">
                <p
                    class="text-center dark:text-gray-100 text-xl mt-5 mb-10"
                    v-html="$t('checkout.empty')"
                ></p>
                <div class="flex items-center gap-3 mb-4">
                    <div class="h-px flex-1 bg-gray-500 dark:bg-gray-500"></div>
                    <div class="text-xs dark:text-gray-100">
                        {{ $t('checkout.promocode_empty_hint') }}
                    </div>
                    <div class="h-px flex-1 bg-gray-500 dark:bg-gray-500"></div>
                </div>
                <div
                    class="glass-card rounded-3xl p-4 relative overflow-hidden text-dark dark:text-white"
                >
                    <h3 class="font-normal mb-2">{{ $t('checkout.title') }}</h3>
                    <div class="flex items-stretch gap-0">
                        <input
                            v-model.trim="inputCode"
                            :placeholder="$t('checkout.promocode_placeholder')"
                            class="flex-1 h-11 px-3 border rounded-l-lg rounded-r-none"
                            :class="
                                isApplied
                                    ? '!border-green-400 !bg-green-400/10'
                                    : '!border-gray-700 dark:!border-gray-300'
                            "
                            :disabled="isApplied"
                        />
                        <button
                            class="h-11 w-12 grid place-items-center border border-l-0 rounded-r-lg rounded-l-none transition-all text-white disabled:opacity-50"
                            :class="
                                isApplied
                                    ? 'border-red-500 bg-red-500 hover:bg-red-600 dark:border-red-700 dark:bg-red-900 dark:hover:bg-red-800'
                                    : 'border-blue-500 bg-blue-500 hover:bg-blue-600 dark:border-blue-700 dark:bg-blue-900 dark:hover:bg-blue-800'
                            "
                            :disabled="promo.loading || (!isApplied && !inputCode)"
                            :aria-label="
                                isApplied
                                    ? $t('checkout.promocode_clear_aria')
                                    : $t('checkout.promocode_apply_aria')
                            "
                            @click.prevent="onPrimaryPromoClick"
                        >
                            <X v-if="isApplied" class="w-5 h-5" />
                            <Check v-else class="w-5 h-5" />
                        </button>
                    </div>
                    <BoxLoader v-if="promo.loading" />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useToast } from 'vue-toastification';
import { useAlert } from '@/utils/alert';
import axios from 'axios';
import { useCartStore } from '@/stores/cart';
import { useAuthStore } from '@/stores/auth';
import { useLoadingStore } from '@/stores/loading';
import { useOptionStore } from '@/stores/options';
import { usePromoStore } from '@/stores/promo';
import ServiceList from '@/components/checkout/ServiceList.vue';
import PaymentList from '@/components/checkout/PaymentList.vue';
import BackLink from '@/components/layout/BackLink.vue';
import BoxLoader from '@/components/BoxLoader.vue';
import { Check, X } from 'lucide-vue-next';

const router = useRouter();
const toast = useToast();
const { showAlert } = useAlert();
const { locale, t } = useI18n();
const cartStore = useCartStore();
const authStore = useAuthStore();
const loadingStore = useLoadingStore();
const optionStore = useOptionStore();
const promo = usePromoStore();
const selectedPayment = ref<'card' | 'crypto' | ''>('');
const inputCode = ref('');
let applyTimer: number | null = null;
const isApplied = computed(
    () => !!promo.code && !!promo.result && !promo.error && promo.code === inputCode.value
);

// Discounts (from options, only if > 0)
const itemCount = computed(() => cartStore.items.length);
const discountPercent = computed(() => {
    const d2 = Number(optionStore.options.discount_2 || 0);
    const d3 = Number(optionStore.options.discount_3 || 0);
    if (itemCount.value >= 3) return d3 > 0 ? d3 : 0;
    if (itemCount.value === 2) return d2 > 0 ? d2 : 0;
    return 0;
});

const subtotalPaid = computed(() => cartStore.subtotalPaid);
const optionDiscountAmount = computed(() => (subtotalPaid.value * discountPercent.value) / 100);
const promoDiscountPercent = computed(() =>
    promo.result?.type === 'discount' ? Number(promo.result.discount_percent || 0) : 0
);
const promoDiscountAmount = computed(() => (subtotalPaid.value * promoDiscountPercent.value) / 100);
const finalTotal = computed(() =>
    Math.max(0, subtotalPaid.value - optionDiscountAmount.value - promoDiscountAmount.value)
);
const isZeroTotalWithServices = computed(
    () => finalTotal.value === 0 && cartStore.items.length > 0
);

const upsellPercent = computed(() => {
    const d2 = Number(optionStore.options.discount_2 || 0);
    const d3 = Number(optionStore.options.discount_3 || 0);
    if (itemCount.value === 1) return d2 > 0 ? d2 : 0;
    if (itemCount.value === 2) return d3 > 0 ? d3 : 0;
    return 0;
});

const formatCurrency = (value: number) => {
    return `${value.toFixed(2)} ${optionStore.options.currency.toUpperCase()}`;
};

const nextPaymentPerItem = computed(() => {
    if (!selectedPayment.value) return [];
    const now = new Date();
    return cartStore.items.map(item => {
        const plan = cartStore.getSubscriptionType(item.id);
        let nextDate: Date;
        if (plan === 'trial') {
            nextDate = addDays(now, 3);
        } else {
            nextDate = addMonths(now, 1);
        }
        return { id: item.id, name: getTranslation(item, 'name'), date: fmt(nextDate) };
    });
});

const hasTrial = computed(() => {
    return cartStore.items.some(service => cartStore.getSubscriptionType(service.id) === 'trial');
});

const getTranslation = (service: any, key: string) => {
    return service.translations[locale.value]?.[key] ?? service.translations['en']?.[key] ?? null;
};

const addDays = (d: Date, days: number) => {
    const r = new Date(d.getTime());
    r.setDate(r.getDate() + days);
    return r;
};

const addMonths = (d: Date, months: number) => {
    const r = new Date(d.getTime());
    r.setMonth(r.getMonth() + months);
    return r;
};

const fmt = (d: Date) => {
    const dd = String(d.getDate()).padStart(2, '0');
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const yyyy = d.getFullYear();
    return `${dd}.${mm}.${yyyy}`;
};

const servicesSummary = computed(() => {
    const parts: string[] = [];
    if (cartStore.trialServicesCount > 0) {
        parts.push(`${cartStore.trialServicesCount} ${t('checkout.trial_services')}`);
    }
    if (cartStore.premiumServicesCount > 0) {
        parts.push(`${cartStore.premiumServicesCount} ${t('checkout.premium_services')}`);
    }
    return parts.join(', ');
});

onMounted(() => {
    loadingStore.stop();

    // Restore applied promocode into input and free access services after reload
    if (promo.code && promo.result) {
        inputCode.value = promo.code;
        if (promo.result.type === 'free_access' && cartStore.promoFreeIds.length === 0) {
            // Re-apply free access services if flags weren't persisted
            cartStore.applyFreeAccessServices(promo.result.services || []);
        }
    }

    window.addEventListener('pageshow', event => {
        const nav = performance.getEntriesByType('navigation')[0] as
            | PerformanceNavigationTiming
            | undefined;
        if (event.persisted || nav?.type === 'back_forward') {
            loadingStore.stop();
        }
    });

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            loadingStore.stop();
        }
    });

    const query = new URLSearchParams(window.location.search);
    if (query.get('success') === 'true') {
        cartStore.clearCart();
        promo.clear();

        router.replace({ path: '/' }).then(() => {
            toast.success(t('checkout.success'));
        });
    }
});

const handleSubmit = async () => {
    if (isZeroTotalWithServices.value) {
        await buyFree();
        return;
    }
    if (selectedPayment.value === 'card') {
        await processMonoPayment();
    } else if (selectedPayment.value === 'crypto') {
        await processCryptoPayment();
    }
};

watch(isZeroTotalWithServices, val => {
    if (val) {
        selectedPayment.value = '';
    }
});

const processMonoPayment = async () => {
    loadingStore.start();

    try {
        const payload = {
            services: cartStore.items.map(item => item.id),
            subscriptionTypes: cartStore.subscriptionTypes,
            ...(promo.code ? { promocode: promo.code } : {})
        };
        const { data } = await axios.post('/mono/create-payment', payload, {
            headers: { Authorization: `Bearer ${authStore.token}` }
        });
        if (data.url) {
            window.location.href = data.url;
        } else {
            loadingStore.stop();
            toast.error(t('checkout.payment_error'));
        }
    } catch (error) {
        console.error('Mono payment error:', error);
        const errMsg = (error && (error as any).response?.data?.message) || t('checkout.payment_error');
        toast.error(errMsg as string);
        loadingStore.stop();
    }
};

const processCryptoPayment = async () => {
    loadingStore.start();

    try {
        const payload = {
            services: cartStore.items.map(item => item.id),
            ...(promo.code ? { promocode: promo.code } : {})
        };
        const { data } = await axios.post('/cryptomus/create-payment', payload, {
            headers: { Authorization: `Bearer ${authStore.token}` }
        });
        if (data.url) {
            window.location.href = data.url;
        } else {
            loadingStore.stop();
            toast.error(t('checkout.payment_error'));
        }
    } catch (error) {
        console.error('Crypto payment error:', error);
        const errMsg = (error && (error as any).response?.data?.message) || t('checkout.payment_error');
        toast.error(errMsg as string);
        loadingStore.stop();
    }
};

const buyFree = async () => {
    try {
        await cartStore.submitCart({
            paymentMethod: 'free',
            promocode: promo.code || undefined
        });
        await authStore.fetchUser();
        promo.clear();
        inputCode.value = '';
        toast.success(t('checkout.free_success'));
        await router.push('/');
    } catch (error) {
        console.error('Free order error:', error);
        const errMsg = (error && (error as any).response?.data?.message) || t('checkout.payment_error');
        toast.error(errMsg as string);
    }
};

async function onApply() {
    if (applyTimer) {
        clearTimeout(applyTimer as unknown as number);
    }
    applyTimer = window.setTimeout(async () => {
        await promo.apply(inputCode.value);
        if (promo.error) {
            await showAlert({
                title: t('alert.title'),
                text: promo.error,
                icon: 'error',
                confirmText: t('alert.ok')
            });
            return;
        }
        if (promo.result?.type === 'free_access') {
            await cartStore.applyFreeAccessServices(promo.result.services || []);
        } else if (promo.result?.type === 'discount' && cartStore.items.length === 0) {
            await showAlert({
                title: t('alert.title'),
                text: t('checkout.promocode_discount_empty_cart'),
                icon: 'success',
                confirmText: t('alert.ok')
            });
        }
    }, 500);
}

function onClear() {
    promo.clear();
    cartStore.removeFreeAccessServices();
    inputCode.value = '';
}

function onPrimaryPromoClick() {
    if (isApplied.value) {
        onClear();
    } else {
        onApply();
    }
}
</script>

<style scoped>
.checkout-btn {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
    box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
}
.checkout-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 15px 35px rgba(59, 130, 246, 0.4);
}

@media (max-width: 1024px) {
    .checkout-btn {
        width: 100%;
    }
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: translateY(6px);
}
.fade-enter-active,
.fade-leave-active {
    transition:
        opacity 320ms cubic-bezier(0.2, 0.9, 0.2, 1),
        transform 320ms cubic-bezier(0.2, 0.9, 0.2, 1);
}

.fade-slide-enter-from {
    opacity: 0;
    transform: translateY(8px);
}
.fade-slide-enter-to {
    opacity: 1;
    transform: translateY(0);
}
.fade-slide-enter-active {
    transition:
        opacity 380ms cubic-bezier(0.22, 0.9, 0.36, 1),
        transform 380ms cubic-bezier(0.22, 0.9, 0.36, 1);
}

.fade-slide-leave-from {
    opacity: 1;
    transform: translateY(0);
}
.fade-slide-leave-to {
    opacity: 0;
    transform: translateY(6px);
}
.fade-slide-leave-active {
    transition:
        opacity 260ms ease,
        transform 260ms ease;
}
</style>
