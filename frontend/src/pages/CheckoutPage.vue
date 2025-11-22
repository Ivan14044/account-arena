<template>
    <div class="max-w-7xl mx-auto px-4 py-16 sm:px-6 lg:px-8 relative z-1">
        <div
            v-if="productCartStore.items.length"
            class="mx-auto grid gap-8 lg:grid-cols-[minmax(0,1fr)_24rem]"
        >
            <div class="flex items-center justify-between gap-4 mt-6 mb-2 lg:col-span-2">
                <h1
                    class="text-2xl font-medium md:text-4xl md:font-light text-dark dark:text-white break-words flex-1 min-w-0"
                >
                    {{ $t('checkout.title') }}
                </h1>

                <BackLink class="flex-shrink-0" />
            </div>

            <div class="min-w-0">
                <form class="space-y-6" @submit.prevent="handleSubmit">
                    <!-- Товары (если есть) -->
                    <div v-if="productCartStore.items.length" class="product-cart-section">
                        <h2
                            class="text-lg sm:text-xl font-semibold mb-3 sm:mb-4 text-dark dark:text-white break-words"
                        >
                            {{ $t('checkout.products_in_cart') }}
                        </h2>

                        <div class="space-y-4">
                            <div
                                v-for="item in productCartStore.items"
                                :key="item.id"
                                class="product-cart-item glass-card p-4 rounded-2xl"
                            >
                                <div
                                    class="flex flex-col sm:flex-row items-start sm:items-center gap-3 sm:gap-4"
                                >
                                    <!-- Изображение -->
                                    <div class="product-image-wrapper-checkout flex-shrink-0">
                                        <img
                                            :src="item.image_url || '/img/no-logo.png'"
                                            :alt="getProductTitle(item)"
                                            class="product-image-checkout"
                                        />
                                    </div>

                                    <!-- Информация -->
                                    <div class="flex-1 min-w-0 w-full sm:w-auto">
                                        <h3
                                            class="font-semibold text-sm sm:text-base text-dark dark:text-white break-words line-clamp-2"
                                        >
                                            {{ getProductTitle(item) }}
                                        </h3>
                                        <p
                                            class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 mt-1"
                                        >
                                            {{ formatCurrency(item.price) }} ×
                                            {{ item.quantity }} шт.
                                        </p>
                                    </div>

                                    <!-- Управление количеством -->
                                    <div
                                        class="flex items-center gap-2 sm:gap-3 w-full sm:w-auto justify-between sm:justify-start"
                                    >
                                        <div class="quantity-control-checkout">
                                            <button
                                                class="qty-btn"
                                                :disabled="item.quantity <= 1"
                                                @click="productCartStore.decreaseQuantity(item.id)"
                                            >
                                                −
                                            </button>
                                            <span class="qty-value">{{ item.quantity }}</span>
                                            <button
                                                class="qty-btn"
                                                :disabled="item.quantity >= item.max_quantity"
                                                @click="productCartStore.increaseQuantity(item.id)"
                                            >
                                                +
                                            </button>
                                        </div>

                                        <div class="font-bold text-lg text-dark dark:text-white">
                                            {{ formatCurrency(item.price * item.quantity) }}
                                        </div>

                                        <button
                                            class="remove-btn"
                                            :title="$t('cart.confirm.remove_single.title')"
                                            @click="productCartStore.removeItem(item.id)"
                                        >
                                            <svg
                                                class="w-5 h-5"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="w-full lg:max-w-sm text-dark dark:text-white font-normal space-y-6">
                <div class="glass-card rounded-3xl p-6 relative overflow-hidden">
                    <div class="space-y-4">
                        <div class="space-y-2">
                            <div class="flex justify-between items-center">
                                <h3 class="font-normal">
                                    {{
                                        personalDiscountPercent > 0 || promoDiscountPercent > 0
                                            ? $t('checkout.original_total')
                                            : $t('checkout.total_amount')
                                    }}:
                                </h3>
                                <span
                                    :class="
                                        personalDiscountPercent > 0 || promoDiscountPercent > 0
                                            ? 'line-through opacity-60'
                                            : ''
                                    "
                                    class="font-normal"
                                >
                                    {{ formatCurrency(subtotalPaid) }}
                                </span>
                            </div>
                            <div
                                v-if="personalDiscountPercent > 0"
                                class="flex justify-between items-center text-sm"
                            >
                                <span>{{
                                    $t('checkout.personal_discount', {
                                        percent: personalDiscountPercent
                                    })
                                }}</span>
                                <span>-{{ formatCurrency(personalDiscountAmount) }}</span>
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
                                v-if="personalDiscountPercent > 0 || promoDiscountPercent > 0"
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
                    </div>
                    <BoxLoader v-if="promo.loading" :expand-padding="true" />
                </div>

                <div class="glass-card rounded-3xl p-6">
                    <h3 class="font-normal mb-4">{{ $t('checkout.payment_method') }}</h3>
                    <PaymentList
                        v-model="selectedPayment"
                        :disabled="isZeroTotalWithServices"
                        :hide-balance="!authStore.isAuthenticated"
                    />
                    <p
                        v-if="!authStore.isAuthenticated"
                        class="text-xs text-gray-600 dark:text-gray-400 mt-3"
                    >
                        {{ $t('checkout.guest_payment_methods') }}
                    </p>
                </div>

                <!-- Поле Email для гостей (если не авторизован и есть товары) -->
                <div
                    v-if="!authStore.isAuthenticated && productCartStore.items.length"
                    class="glass-card rounded-2xl p-6"
                >
                    <label class="block text-sm font-medium text-dark dark:text-white mb-2">
                        {{ $t('checkout.guest_email_label') }}
                    </label>
                    <input
                        v-model="guestEmail"
                        type="email"
                        required
                        :placeholder="$t('checkout.guest_email_placeholder')"
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-dark dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                    <p class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                        {{ $t('checkout.guest_email_hint') }}
                    </p>
                </div>

                <!-- Purchase Rules Agreement - Новый дизайн -->
                <div
                    v-if="purchaseRulesEnabled && currentRulesText"
                    class="glass-card rounded-2xl p-5 purchase-rules-card"
                >
                    <label class="flex items-start gap-4 cursor-pointer group">
                        <div class="purchase-rules-checkbox-wrapper">
                            <input
                                v-model="agreedToRules"
                                type="checkbox"
                                class="purchase-rules-checkbox-custom"
                                required
                            />
                            <span class="purchase-rules-checkmark">
                                <svg
                                    class="w-3.5 h-3.5 text-white"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="3"
                                        d="M5 13l4 4L19 7"
                                    />
                                </svg>
                            </span>
                        </div>
                        <div class="flex-1">
                            <span
                                class="text-sm font-medium text-dark dark:text-white leading-relaxed"
                            >
                                {{ $t('checkout.agree_with') }}
                                <button
                                    class="purchase-rules-link-new"
                                    @click.prevent="showRulesModal = true"
                                >
                                    {{ $t('checkout.purchase_rules') }}
                                </button>
                            </span>
                            <p
                                class="text-xs text-gray-600 dark:text-gray-400 mt-1.5 leading-relaxed"
                            >
                                {{ $t('checkout.rules_hint') }}
                            </p>
                        </div>
                    </label>
                </div>

                <!-- Обертка для кнопки, чтобы ловить клики даже когда кнопка disabled -->
                <div class="w-full" @click.stop="handleCheckoutClick">
                    <button
                        type="button"
                        :disabled="
                            (isZeroTotalWithServices ? false : !selectedPayment) ||
                            productCartStore.items.length === 0 ||
                            (purchaseRulesEnabled && !agreedToRules)
                        "
                        class="checkout-btn w-full py-4 rounded-lg font-normal transition-all duration-300 disabled:opacity-50 pointer-events-none"
                    >
                        {{ $t('checkout.submit') }}
                    </button>
                </div>
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

        <!-- Modal with Purchase Rules -->
        <Teleport to="body">
            <Transition name="modal">
                <div
                    v-if="showRulesModal"
                    class="fixed inset-0 z-[9999] flex items-center justify-center p-4 purchase-rules-modal-backdrop"
                    @click.self="showRulesModal = false"
                >
                    <div class="purchase-rules-modal max-w-3xl w-full max-h-[85vh] overflow-hidden">
                        <!-- Заголовок с градиентом -->
                        <div class="purchase-rules-header">
                            <div class="flex items-center gap-3">
                                <div class="purchase-rules-icon">
                                    <svg
                                        class="w-6 h-6"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                        />
                                    </svg>
                                </div>
                                <h3 class="purchase-rules-title">
                                    {{ $t('checkout.purchase_rules') }}
                                </h3>
                            </div>
                            <button
                                class="purchase-rules-close"
                                :aria-label="$t('checkout.close')"
                                @click="showRulesModal = false"
                            >
                                <svg
                                    class="w-6 h-6"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>

                        <!-- Контент правил -->
                        <div class="purchase-rules-body">
                            <div
                                v-if="currentRulesText && currentRulesText.trim()"
                                class="purchase-rules-content"
                                v-html="currentRulesText"
                            ></div>
                            <!-- ✅ ИСПРАВЛЕНИЕ: Fallback на случай пустого текста -->
                            <div
                                v-else
                                class="purchase-rules-content text-center text-gray-500 dark:text-gray-400 py-8"
                            >
                                <p>{{ $t('checkout.rules_not_available') }}</p>
                            </div>
                        </div>

                        <!-- Футер с кнопками -->
                        <div class="purchase-rules-footer">
                            <button class="btn-rules-secondary" @click="showRulesModal = false">
                                {{ $t('checkout.close') }}
                            </button>
                            <button
                                class="btn-rules-primary"
                                @click="
                                    showRulesModal = false;
                                    agreedToRules = true;
                                "
                            >
                                <svg
                                    class="w-5 h-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M5 13l4 4L19 7"
                                    />
                                </svg>
                                {{ $t('checkout.agree') }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useToast } from 'vue-toastification';
import { useAlert } from '@/utils/alert';
import axios from '@/bootstrap'; // Используем настроенный axios из bootstrap
import { useProductCartStore } from '@/stores/productCart';
import { useProductTitle } from '@/composables/useProductTitle';
import { useAuthStore } from '@/stores/auth';
import { useLoadingStore } from '@/stores/loading';
import { useOptionStore } from '@/stores/options';
import { usePromoStore } from '@/stores/promo';
import PaymentList from '@/components/checkout/PaymentList.vue';
import BackLink from '@/components/layout/BackLink.vue';
import BoxLoader from '@/components/BoxLoader.vue';
import { Check, X } from 'lucide-vue-next';

const router = useRouter();
const toast = useToast();
const { showAlert } = useAlert();
const { locale, t } = useI18n();
const productCartStore = useProductCartStore();
const { getProductTitle } = useProductTitle();
const authStore = useAuthStore();
const loadingStore = useLoadingStore();
const optionStore = useOptionStore();
const promo = usePromoStore();
const selectedPayment = ref<'card' | 'crypto' | 'balance' | ''>('');
const inputCode = ref('');
const guestEmail = ref(''); // Email для гостевых покупок

// Purchase Rules
const purchaseRulesEnabled = ref(false);
const purchaseRulesText = ref<Record<string, string>>({});
const agreedToRules = ref(false);
const showRulesModal = ref(false);
const isProcessingCheckout = ref(false);
let applyTimer: number | null = null;
const isApplied = computed(
    () => !!promo.code && !!promo.result && !promo.error && promo.code === inputCode.value
);

const subtotalPaid = computed(() => {
    return productCartStore.totalAmount;
});

// Personal discount (only for authenticated users)
const personalDiscountPercent = computed(() => {
    if (!authStore.isAuthenticated || !authStore.user) {
        return 0;
    }

    const discount = authStore.user.personal_discount || 0;
    const expiresAt = authStore.user.personal_discount_expires_at;

    // Check if discount is active
    if (discount <= 0) {
        return 0;
    }

    // Check expiration date if exists
    if (expiresAt) {
        const expiryDate = new Date(expiresAt);
        if (new Date() > expiryDate) {
            return 0;
        }
    }

    return Number(discount);
});

const personalDiscountAmount = computed(() => {
    if (personalDiscountPercent.value <= 0) {
        return 0;
    }
    return (subtotalPaid.value * personalDiscountPercent.value) / 100;
});

// Apply personal discount first, then promo discount
const subtotalAfterPersonalDiscount = computed(() => {
    return Math.max(0, subtotalPaid.value - personalDiscountAmount.value);
});

const promoDiscountPercent = computed(() =>
    promo.result?.type === 'discount' ? Number(promo.result.discount_percent || 0) : 0
);
const promoDiscountAmount = computed(
    () => (subtotalAfterPersonalDiscount.value * promoDiscountPercent.value) / 100
);
const finalTotal = computed(() =>
    Math.max(0, subtotalAfterPersonalDiscount.value - promoDiscountAmount.value)
);
const isZeroTotalWithServices = computed(
    () => finalTotal.value === 0 && productCartStore.items.length > 0
);

const formatCurrency = (value: number) => {
    const currency = optionStore.getOption('currency', 'USD');
    return `${value.toFixed(2)} ${currency?.toUpperCase()}`;
};

// Computed property to get current locale rules text
const currentRulesText = computed(() => {
    const currentLocale = locale.value;
    const rulesText =
        purchaseRulesText.value[currentLocale] ||
        purchaseRulesText.value.ru ||
        purchaseRulesText.value.en ||
        '';
    return rulesText;
});

// Загрузка правил покупки
const loadPurchaseRules = async () => {
    try {
        const response = await axios.get('/purchase-rules');

        if (response.data.enabled && response.data.rules) {
            // Store all locales as object
            purchaseRulesText.value = response.data.rules || {};

            // Check if there's text for current locale or fallback locales
            const currentLocale = locale.value;
            const hasText =
                purchaseRulesText.value[currentLocale] ||
                purchaseRulesText.value.ru ||
                purchaseRulesText.value.en;

            // Enable only if there's non-empty text
            if (hasText && hasText.trim()) {
                purchaseRulesEnabled.value = true;
            }
        }
    } catch (error) {
        console.error('Error loading purchase rules:', error);
    }
};

onMounted(() => {
    loadingStore.stop();

    // Загружаем правила покупки
    loadPurchaseRules();

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
        productCartStore.clearCart();
        promo.clear();

        router.replace({ path: '/' }).then(() => {
            toast.success(t('checkout.success'));
        });
    }
});

const handleCheckoutClick = (event: Event) => {
    // ✅ ИСПРАВЛЕНИЕ: останавливаем событие полностью
    event.preventDefault();
    event.stopPropagation();

    // ✅ ИСПРАВЛЕНИЕ: защита от множественных вызовов
    if (isProcessingCheckout.value) {
        return;
    }

    isProcessingCheckout.value = true;

    // Сбрасываем флаг через 500ms
    setTimeout(() => {
        isProcessingCheckout.value = false;
    }, 500);

    // Проверяем правила покупки (без всплывающего окна)
    if (purchaseRulesEnabled.value && !agreedToRules.value) {
        // Прокручиваем к блоку с правилами и показываем анимацию встряхивания
        const rulesElement = document.querySelector('.purchase-rules-card');
        if (rulesElement) {
            // Используем nearest вместо center, чтобы избежать лишней прокрутки
            rulesElement.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            // Добавляем анимацию "встряхивания" для привлечения внимания
            rulesElement.classList.add('shake-animation');
            setTimeout(() => {
                rulesElement.classList.remove('shake-animation');
            }, 600);
        }
        return;
    }

    // Проверяем другие условия
    if (productCartStore.items.length === 0) {
        toast.error(t('checkout.empty'));
        return;
    }

    // Проверяем email для гостей (если есть товары и пользователь не авторизован)
    if (!authStore.isAuthenticated && productCartStore.items.length > 0) {
        if (!guestEmail.value || !guestEmail.value.trim()) {
            toast.error(t('checkout.guest_email_required'));
            // Прокручиваем к полю email
            const emailField = document.querySelector('input[type="email"]');
            if (emailField) {
                emailField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                (emailField as HTMLInputElement).focus();
            }
            return;
        }

        // Проверяем валидность email
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(guestEmail.value.trim())) {
            toast.error(t('checkout.guest_email_invalid'));
            return;
        }
    }

    if (!isZeroTotalWithServices.value && !selectedPayment.value) {
        toast.warning(t('checkout.select_payment_method'));
        return;
    }

    // Если все проверки пройдены, вызываем handleSubmit
    handleSubmit();
};

const handleSubmit = async () => {
    if (isZeroTotalWithServices.value) {
        await buyFree();
        return;
    }
    if (selectedPayment.value === 'card') {
        await processMonoPayment();
    } else if (selectedPayment.value === 'crypto') {
        await processCryptoPayment();
    } else if (selectedPayment.value === 'balance') {
        await processBalancePayment();
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
        // Проверяем, гость или авторизованный пользователь
        const isGuest = !authStore.isAuthenticated;

        if (isGuest) {
            // Гостевой платеж (только для товаров)
            if (!guestEmail.value || !guestEmail.value.trim()) {
                toast.error(t('checkout.guest_email_required_short'));
                loadingStore.stop();
                return;
            }

            const payload = {
                guest_email: guestEmail.value.trim(),
                products: productCartStore.items.map(item => ({
                    id: item.id,
                    quantity: item.quantity
                })),
                ...(promo.code ? { promocode: promo.code } : {})
            };

            const { data } = await axios.post('/guest/mono/create-payment', payload);
            if (data.url) {
                window.location.href = data.url;
            } else {
                loadingStore.stop();
                toast.error(t('checkout.payment_error'));
            }
        } else {
            // Авторизованный пользователь
            const payload = {
                products: productCartStore.items.map(item => ({
                    id: item.id,
                    quantity: item.quantity
                })),
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
        }
    } catch (error) {
        console.error('Mono payment error:', error);
        const errMsg =
            (error && (error as any).response?.data?.message) || t('checkout.payment_error');
        toast.error(errMsg as string);
        loadingStore.stop();
    }
};

const processCryptoPayment = async () => {
    loadingStore.start();

    try {
        // Проверяем, гость или авторизованный пользователь
        const isGuest = !authStore.isAuthenticated;

        if (isGuest) {
            // Гостевой платеж (только для товаров)
            if (!guestEmail.value || !guestEmail.value.trim()) {
                toast.error(t('checkout.guest_email_required_short'));
                loadingStore.stop();
                return;
            }

            const payload = {
                guest_email: guestEmail.value.trim(),
                products: productCartStore.items.map(item => ({
                    id: item.id,
                    quantity: item.quantity
                })),
                ...(promo.code ? { promocode: promo.code } : {})
            };

            const { data } = await axios.post('/guest/cryptomus/create-payment', payload);
            if (data.url) {
                window.location.href = data.url;
            } else {
                loadingStore.stop();
                toast.error(t('checkout.payment_error'));
            }
        } else {
            // Авторизованный пользователь
            const payload = {
                products: productCartStore.items.map(item => ({
                    id: item.id,
                    quantity: item.quantity
                })),
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
        }
    } catch (error) {
        console.error('Crypto payment error:', error);
        const errMsg =
            (error && (error as any).response?.data?.message) || t('checkout.payment_error');
        toast.error(errMsg as string);
        loadingStore.stop();
    }
};

const buyFree = async () => {
    try {
        // For products, free purchases are handled differently
        // This might need to be implemented if free products are needed
        await authStore.fetchUser();
        promo.clear();
        inputCode.value = '';
        toast.success(t('checkout.free_success'));
        await router.push('/');
    } catch (error) {
        console.error('Free order error:', error);
        const errMsg =
            (error && (error as any).response?.data?.message) || t('checkout.payment_error');
        toast.error(errMsg as string);
    }
};

const processBalancePayment = async () => {
    loadingStore.start();
    try {
        // Проверяем достаточно ли средств на балансе
        if (authStore.user && authStore.user.balance < finalTotal.value) {
            toast.error(t('checkout.insufficient_balance'));
            loadingStore.stop();
            return;
        }

        // Submit products purchase with balance
        const payload = {
            products: productCartStore.items.map(item => ({
                id: item.id,
                quantity: item.quantity
            })),
            payment_method: 'balance',
            ...(promo.code ? { promocode: promo.code } : {})
        };

        await axios.post('/cart', payload, {
            headers: { Authorization: `Bearer ${authStore.token}` }
        });

        productCartStore.clearCart();
        await authStore.fetchUser();
        promo.clear();
        inputCode.value = '';
        toast.success(t('checkout.balance_success'));
        // Перенаправляем на страницу успешного заказа
        await router.push('/order-success');
    } catch (error) {
        console.error('Balance payment error:', error);
        const errMsg =
            (error && (error as any).response?.data?.message) ||
            t('checkout.balance_payment_error');
        toast.error(errMsg as string);
    } finally {
        loadingStore.stop();
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
            // Free access for products might need different handling
            // This functionality may need to be implemented if needed
        } else if (promo.result?.type === 'discount' && productCartStore.items.length === 0) {
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

/* Стили для товаров в корзине */
.product-cart-section {
    margin-top: 2rem;
}

.product-cart-item {
    transition: all 0.3s ease;
}

.product-cart-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(108, 92, 231, 0.12);
}

.product-image-wrapper-checkout {
    width: 80px;
    height: 80px;
    border-radius: 12px;
    overflow: hidden;
    flex-shrink: 0;
}

.product-image-checkout {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.quantity-control-checkout {
    display: flex;
    align-items: center;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    opacity: 0.85;
    transition: opacity 0.2s ease;
}

.quantity-control-checkout:hover {
    opacity: 1;
}

.dark .quantity-control-checkout {
    background: #1e293b;
    border-color: #334155;
}

.qty-btn {
    border: none;
    background: transparent;
    font-size: 16px;
    font-weight: 600;
    color: #64748b;
    cursor: pointer;
    padding: 4px 10px;
    transition: all 0.2s ease;
    min-width: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.qty-btn:hover:not(:disabled) {
    background: #e2e8f0;
    color: #6c5ce7;
}

.dark .qty-btn:hover:not(:disabled) {
    background: #334155;
    color: #a29bfe;
}

.qty-btn:disabled {
    cursor: not-allowed;
    opacity: 0.3;
}

.dark .qty-btn {
    color: #94a3b8;
}

.qty-value {
    min-width: 40px;
    text-align: center;
    font-weight: 600;
    font-size: 13px;
    color: #1f2937;
}

.dark .qty-value {
    color: #f1f5f9;
}

.remove-btn {
    border: none;
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #dc2626;
    padding: 8px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.remove-btn:hover {
    background: #fee2e2;
    border-color: #ef4444;
    transform: scale(1.05);
}

.dark .remove-btn {
    background: rgba(239, 68, 68, 0.15);
    border-color: rgba(239, 68, 68, 0.3);
    color: #f87171;
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
/* ============================================
   БЛОК СОГЛАСИЯ С ПРАВИЛАМИ - НОВЫЙ ДИЗАЙН
   В едином стиле с сайтом
   ============================================ */

/* Карточка с правилами */
.purchase-rules-card {
    position: relative;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid rgba(108, 92, 231, 0.15);
}

.purchase-rules-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(108, 92, 231, 0.15);
    border-color: rgba(108, 92, 231, 0.25);
}

.dark .purchase-rules-card {
    border-color: rgba(162, 155, 254, 0.2);
}

.dark .purchase-rules-card:hover {
    box-shadow: 0 8px 24px rgba(162, 155, 254, 0.2);
    border-color: rgba(162, 155, 254, 0.35);
}

/* Кастомный чекбокс */
.purchase-rules-checkbox-wrapper {
    position: relative;
    width: 24px;
    height: 24px;
    flex-shrink: 0;
    margin-top: 2px;
}

.purchase-rules-checkbox-custom {
    position: absolute;
    opacity: 0;
    cursor: pointer;
    width: 24px;
    height: 24px;
    z-index: 2;
}

.purchase-rules-checkmark {
    position: absolute;
    top: 0;
    left: 0;
    width: 24px;
    height: 24px;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(248, 249, 250, 0.9));
    border: 2px solid #e5e7eb;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.dark .purchase-rules-checkmark {
    background: linear-gradient(135deg, rgba(51, 65, 85, 0.9), rgba(30, 41, 59, 0.9));
    border-color: #475569;
}

.purchase-rules-checkmark svg {
    opacity: 0;
    transform: scale(0.3);
    transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

/* Состояние checked */
.purchase-rules-checkbox-custom:checked ~ .purchase-rules-checkmark {
    background: linear-gradient(135deg, #6c5ce7, #a29bfe);
    border-color: #6c5ce7;
    box-shadow: 0 4px 12px rgba(108, 92, 231, 0.4);
}

.dark .purchase-rules-checkbox-custom:checked ~ .purchase-rules-checkmark {
    background: linear-gradient(135deg, #a29bfe, #b8b2fc);
    border-color: #a29bfe;
    box-shadow: 0 4px 12px rgba(162, 155, 254, 0.5);
}

.purchase-rules-checkbox-custom:checked ~ .purchase-rules-checkmark svg {
    opacity: 1;
    transform: scale(1);
}

/* Hover эффекты */
.purchase-rules-checkbox-custom:hover ~ .purchase-rules-checkmark {
    border-color: #6c5ce7;
    transform: scale(1.05);
}

.dark .purchase-rules-checkbox-custom:hover ~ .purchase-rules-checkmark {
    border-color: #a29bfe;
}

/* Focus эффекты */
.purchase-rules-checkbox-custom:focus ~ .purchase-rules-checkmark {
    box-shadow: 0 0 0 3px rgba(108, 92, 231, 0.2);
}

.dark .purchase-rules-checkbox-custom:focus ~ .purchase-rules-checkmark {
    box-shadow: 0 0 0 3px rgba(162, 155, 254, 0.2);
}

/* Ссылка на правила - новый стиль */
.purchase-rules-link-new {
    color: #6c5ce7;
    font-weight: 600;
    text-decoration: none;
    position: relative;
    transition: all 0.2s ease;
    padding-bottom: 1px;
    border-bottom: 2px solid transparent;
}

.purchase-rules-link-new:hover {
    color: #5b4bcf;
    border-bottom-color: #6c5ce7;
}

.dark .purchase-rules-link-new {
    color: #a29bfe;
}

.dark .purchase-rules-link-new:hover {
    color: #b8b2fc;
    border-bottom-color: #a29bfe;
}

/* Анимация встряхивания для привлечения внимания */
@keyframes shake {
    0%,
    100% {
        transform: translateX(0) translateY(0);
    }
    10%,
    30%,
    50%,
    70%,
    90% {
        transform: translateX(-8px) translateY(0);
    }
    20%,
    40%,
    60%,
    80% {
        transform: translateX(8px) translateY(0);
    }
}

.shake-animation {
    animation: shake 0.6s ease-in-out;
    border-color: rgba(239, 68, 68, 0.6) !important;
    box-shadow:
        0 0 0 4px rgba(239, 68, 68, 0.15),
        0 8px 24px rgba(239, 68, 68, 0.2) !important;
}

.dark .shake-animation {
    border-color: rgba(248, 113, 113, 0.6) !important;
    box-shadow:
        0 0 0 4px rgba(248, 113, 113, 0.15),
        0 8px 24px rgba(248, 113, 113, 0.2) !important;
}

/* ============================================
   МОДАЛЬНОЕ ОКНО ПРАВИЛ ПОКУПКИ
   В едином стиле с дизайном сайта
   ============================================ */

/* Фон модального окна - более контрастный */
.purchase-rules-modal-backdrop {
    background: rgba(0, 0, 0, 0.75);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

/* Само модальное окно - Glass Effect с большей прозрачностью */
.purchase-rules-modal {
    background: rgba(255, 255, 255, 0.65);
    backdrop-filter: blur(30px) saturate(180%);
    -webkit-backdrop-filter: blur(30px) saturate(180%);
    border-radius: 20px;
    box-shadow:
        0 25px 50px -12px rgba(0, 0, 0, 0.3),
        0 0 0 1px rgba(108, 92, 231, 0.2),
        inset 0 1px 1px rgba(255, 255, 255, 0.5);
    overflow: hidden;
    position: relative;
    animation: slideUp 0.4s cubic-bezier(0.22, 0.9, 0.36, 1);
}

.dark .purchase-rules-modal {
    background: rgba(30, 41, 59, 0.65);
    backdrop-filter: blur(30px) saturate(180%);
    -webkit-backdrop-filter: blur(30px) saturate(180%);
    box-shadow:
        0 25px 50px -12px rgba(0, 0, 0, 0.6),
        0 0 0 1px rgba(162, 155, 254, 0.3),
        inset 0 1px 1px rgba(255, 255, 255, 0.1);
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* Заголовок с градиентом */
.purchase-rules-header {
    background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);
    padding: 1.5rem 2rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: relative;
    overflow: hidden;
}

.purchase-rules-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 200px;
    height: 200px;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.15) 0%, transparent 70%);
    border-radius: 50%;
}

.purchase-rules-icon {
    width: 48px;
    height: 48px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.purchase-rules-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: white;
    margin: 0;
    letter-spacing: -0.5px;
}

.purchase-rules-close {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.purchase-rules-close:hover {
    background: rgba(255, 255, 255, 0.25);
    transform: rotate(90deg);
}

/* Тело модального окна - Glass Effect с прозрачностью */
.purchase-rules-body {
    padding: 2rem;
    max-height: calc(85vh - 240px);
    overflow-y: auto;
    background: rgba(248, 249, 250, 0.25);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

.dark .purchase-rules-body {
    background: rgba(15, 23, 42, 0.25);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

/* Кастомный скроллбар */
.purchase-rules-body::-webkit-scrollbar {
    width: 8px;
}

.purchase-rules-body::-webkit-scrollbar-track {
    background: rgba(108, 92, 231, 0.05);
    border-radius: 10px;
}

.purchase-rules-body::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);
    border-radius: 10px;
}

.purchase-rules-body::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #5b4bcf 0%, #9189e8 100%);
}

/* Контент правил - улучшенный контраст для прозрачного фона */
.purchase-rules-content {
    color: #1f2937;
    line-height: 1.8;
    font-size: 15px;
    text-shadow: 0 1px 2px rgba(255, 255, 255, 0.8);
}

.dark .purchase-rules-content {
    color: #f3f4f6;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
}

.purchase-rules-content * {
    color: inherit !important;
}

.purchase-rules-content h1,
.purchase-rules-content h2,
.purchase-rules-content h3,
.purchase-rules-content h4,
.purchase-rules-content h5,
.purchase-rules-content h6 {
    background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    font-weight: 700;
    margin-top: 1.5em;
    margin-bottom: 0.75em;
    letter-spacing: -0.5px;
}

.purchase-rules-content h1 {
    font-size: 1.875rem;
}
.purchase-rules-content h2 {
    font-size: 1.5rem;
}
.purchase-rules-content h3 {
    font-size: 1.25rem;
}

.purchase-rules-content p {
    margin-bottom: 1em;
    color: #1f2937;
}

.dark .purchase-rules-content p {
    color: #e5e7eb !important;
}

.purchase-rules-content ul,
.purchase-rules-content ol {
    margin-left: 1.5em;
    margin-bottom: 1em;
}

.purchase-rules-content li {
    margin-bottom: 0.5em;
    color: #1f2937;
    position: relative;
    padding-left: 0.5em;
}

.dark .purchase-rules-content li {
    color: #e5e7eb !important;
}

.purchase-rules-content ul li::marker {
    color: #6c5ce7;
}

.dark .purchase-rules-content ul li::marker {
    color: #a29bfe;
}

.purchase-rules-content a {
    color: #6c5ce7;
    text-decoration: none;
    font-weight: 500;
    border-bottom: 1px solid rgba(108, 92, 231, 0.3);
    transition: all 0.2s ease;
}

.purchase-rules-content a:hover {
    color: #5b4bcf;
    border-bottom-color: #6c5ce7;
}

.dark .purchase-rules-content a {
    color: #a29bfe !important;
    border-bottom-color: rgba(162, 155, 254, 0.3);
}

.dark .purchase-rules-content a:hover {
    color: #b8b2fc !important;
    border-bottom-color: #a29bfe;
}

.purchase-rules-content strong,
.purchase-rules-content b {
    font-weight: 600;
    color: #1f2937;
}

.dark .purchase-rules-content strong,
.dark .purchase-rules-content b {
    color: #f9fafb !important;
}

.purchase-rules-content code {
    background: linear-gradient(135deg, rgba(108, 92, 231, 0.1) 0%, rgba(162, 155, 254, 0.1) 100%);
    padding: 0.2em 0.5em;
    border-radius: 6px;
    font-size: 0.9em;
    color: #6c5ce7;
    font-family: 'Monaco', 'Courier New', monospace;
    border: 1px solid rgba(108, 92, 231, 0.2);
}

.dark .purchase-rules-content code {
    background: rgba(162, 155, 254, 0.15) !important;
    color: #a29bfe !important;
    border-color: rgba(162, 155, 254, 0.3);
}

.purchase-rules-content blockquote {
    border-left: 4px solid #6c5ce7;
    background: linear-gradient(
        135deg,
        rgba(108, 92, 231, 0.05) 0%,
        rgba(162, 155, 254, 0.05) 100%
    );
    padding: 1em 1.5em;
    margin: 1.5em 0;
    border-radius: 0 8px 8px 0;
    font-style: italic;
    color: #6b7280;
}

.dark .purchase-rules-content blockquote {
    border-left-color: #a29bfe;
    background: rgba(162, 155, 254, 0.1);
    color: #cbd5e1 !important;
}

.purchase-rules-content table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    margin: 1.5em 0;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.purchase-rules-content th,
.purchase-rules-content td {
    padding: 1em;
    border: 1px solid #e5e7eb;
    color: #374151;
}

.dark .purchase-rules-content th,
.dark .purchase-rules-content td {
    border-color: #334155 !important;
    color: #e5e7eb !important;
}

.purchase-rules-content th {
    background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);
    font-weight: 600;
    color: white !important;
    text-align: left;
}

.dark .purchase-rules-content th {
    background: linear-gradient(135deg, #5b4bcf 0%, #9189e8 100%) !important;
}

.purchase-rules-content tbody tr:nth-child(even) {
    background: rgba(108, 92, 231, 0.03);
}

.dark .purchase-rules-content tbody tr:nth-child(even) {
    background: rgba(162, 155, 254, 0.05);
}

.purchase-rules-content hr {
    border: none;
    height: 2px;
    background: linear-gradient(90deg, transparent, #6c5ce7, transparent);
    margin: 2em 0;
    opacity: 0.3;
}

.purchase-rules-content img {
    max-width: 100%;
    height: auto;
    border-radius: 12px;
    margin: 1.5em 0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.purchase-rules-content pre {
    background: #f8f9fa;
    padding: 1.5em;
    border-radius: 12px;
    overflow-x: auto;
    color: #1f2937;
    border: 1px solid #e5e7eb;
}

.dark .purchase-rules-content pre {
    background: #0f172a !important;
    color: #e5e7eb !important;
    border-color: #334155;
}

/* Футер модального окна - Glass Effect с прозрачностью */
.purchase-rules-footer {
    padding: 1.5rem 2rem;
    background: rgba(255, 255, 255, 0.4);
    backdrop-filter: blur(15px);
    -webkit-backdrop-filter: blur(15px);
    border-top: 1px solid rgba(229, 231, 235, 0.3);
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 1rem;
}

.dark .purchase-rules-footer {
    background: rgba(30, 41, 59, 0.4);
    backdrop-filter: blur(15px);
    -webkit-backdrop-filter: blur(15px);
    border-top-color: rgba(51, 65, 85, 0.3);
}

/* Кнопка "Согласен" */
.btn-rules-primary {
    background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);
    color: white;
    padding: 0.75rem 1.75rem;
    border-radius: 12px;
    border: none;
    font-weight: 600;
    font-size: 15px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(108, 92, 231, 0.3);
    position: relative;
    overflow: hidden;
}

.btn-rules-primary::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.5s ease;
}

.btn-rules-primary:hover::before {
    left: 100%;
}

.btn-rules-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(108, 92, 231, 0.4);
}

.btn-rules-primary:active {
    transform: translateY(0);
}

/* Кнопка "Закрыть" */
.btn-rules-secondary {
    background: #f3f4f6;
    color: #6b7280;
    padding: 0.75rem 1.75rem;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    font-weight: 500;
    font-size: 15px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.dark .btn-rules-secondary {
    background: #334155;
    color: #cbd5e1;
    border-color: #475569;
}

.btn-rules-secondary:hover {
    background: #e5e7eb;
    border-color: #d1d5db;
    transform: translateY(-1px);
}

.dark .btn-rules-secondary:hover {
    background: #475569;
    border-color: #64748b;
}

/* Modal Transition */
.modal-enter-active,
.modal-leave-active {
    transition: all 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-from .purchase-rules-modal,
.modal-leave-to .purchase-rules-modal {
    transform: translateY(30px) scale(0.95);
    opacity: 0;
}

/* Адаптивность */
@media (max-width: 768px) {
    .product-image-wrapper-checkout {
        width: 60px;
        height: 60px;
    }

    .product-cart-item {
        padding: 0.75rem;
    }

    .quantity-control-checkout {
        font-size: 0.875rem;
    }

    .purchase-rules-modal {
        max-height: 90vh;
    }

    .purchase-rules-header {
        padding: 1.25rem 1.5rem;
    }

    .purchase-rules-title {
        font-size: 1.25rem;
    }

    .purchase-rules-body {
        padding: 1.5rem;
        max-height: calc(90vh - 220px);
    }

    .purchase-rules-footer {
        padding: 1.25rem 1.5rem;
        flex-direction: column-reverse;
    }

    .btn-rules-primary,
    .btn-rules-secondary {
        width: 100%;
        justify-content: center;
    }
}
</style>
