<template>
    <div class="max-w-4xl mx-auto px-4 py-16 sm:px-6 lg:px-8 relative z-1">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                {{ $t('balance_topup.title') }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                {{ $t('balance_topup.subtitle') }}
            </p>
        </div>

        <!-- Текущий баланс -->
        <div class="glass-card rounded-3xl p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">
                        {{ $t('balance_topup.current_balance') }}
                    </p>
                    <p
                        class="text-3xl font-bold text-green-600 dark:text-green-400 flex items-center gap-2"
                    >
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                        {{ formatCurrency(authStore.user?.balance ?? 0) }}
                    </p>
                </div>
                <div class="text-right">
                    <button
                        class="text-sm text-blue-600 dark:text-blue-400 hover:underline"
                        @click="router.push('/profile')"
                    >
                        {{ $t('balance_topup.history_link') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Выбор суммы -->
        <div class="glass-card rounded-3xl p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                {{ $t('balance_topup.amount_section') }}
            </h2>

            <!-- Быстрый выбор суммы -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
                <button
                    v-for="preset in presetAmounts"
                    :key="preset"
                    :class="[
                        'px-4 py-3 rounded-lg border-2 transition-all duration-200 font-semibold',
                        amount === preset
                            ? 'border-blue-500 bg-blue-500/10 text-blue-600 dark:text-blue-400'
                            : 'border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white hover:border-blue-400'
                    ]"
                    @click="amount = preset"
                >
                    {{ formatCurrency(preset) }}
                </button>
            </div>

            <!-- Своя сумма -->
            <div>
                <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                    {{ $t('balance_topup.enter_amount') }}
                </label>
                <input
                    v-model.number="amount"
                    type="number"
                    min="1"
                    step="0.01"
                    :placeholder="$t('balance_topup.amount_placeholder')"
                    class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-dark dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
                <p class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                    {{ $t('balance_topup.min_amount') }}: {{ formatCurrency(minAmount) }}
                </p>
            </div>
        </div>

        <!-- Способ оплаты -->
        <div class="glass-card rounded-3xl p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                {{ $t('balance_topup.payment_method_section') }}
            </h2>

            <div class="space-y-3">
                <!-- Карта -->
                <div
                    :class="[
                        'cursor-pointer rounded-lg p-4 border-2 transition-all duration-200',
                        paymentMethod === 'card'
                            ? 'border-blue-500 bg-blue-500/10'
                            : 'border-gray-300 dark:border-gray-600 hover:border-blue-400'
                    ]"
                    @click="paymentMethod = 'card'"
                >
                    <div class="flex items-center gap-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
                            />
                        </svg>
                        <div class="flex-1">
                            <div class="font-semibold text-gray-900 dark:text-white">
                                {{ $t('balance_topup.payment_card') }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $t('balance_topup.payment_card_desc') }}
                            </div>
                        </div>
                        <div
                            :class="[
                                'w-5 h-5 rounded-full border-2 transition-all duration-200',
                                paymentMethod === 'card'
                                    ? 'border-blue-500 bg-blue-500'
                                    : 'border-gray-300 dark:border-gray-600'
                            ]"
                        />
                    </div>
                </div>

                <!-- Криптовалюта -->
                <div
                    :class="[
                        'cursor-pointer rounded-lg p-4 border-2 transition-all duration-200',
                        paymentMethod === 'crypto'
                            ? 'border-blue-500 bg-blue-500/10'
                            : 'border-gray-300 dark:border-gray-600 hover:border-blue-400'
                    ]"
                    @click="paymentMethod = 'crypto'"
                >
                    <div class="flex items-center gap-3">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M23.638 14.904c-1.602 6.43-8.113 10.34-14.542 8.736C2.67 22.05-1.244 15.525.362 9.105 1.962 2.67 8.475-1.243 14.9.358c6.43 1.605 10.342 8.115 8.738 14.548v-.002zm-6.35-4.613c.24-1.59-.974-2.45-2.64-3.03l.54-2.153-1.315-.33-.525 2.107c-.345-.087-.705-.167-1.064-.25l.526-2.127-1.32-.33-.54 2.165c-.285-.067-.565-.132-.84-.2l-1.815-.45-.35 1.407s.975.225.955.236c.535.136.63.486.615.766l-1.477 5.92c-.075.166-.24.406-.614.314.015.02-.96-.24-.96-.24l-.66 1.51 1.71.426.93.242-.54 2.19 1.32.327.54-2.17c.36.1.705.19 1.05.273l-.51 2.154 1.32.33.545-2.19c2.24.427 3.93.257 4.64-1.774.57-1.637-.03-2.58-1.217-3.196.854-.193 1.5-.76 1.68-1.93h.01zm-3.01 4.22c-.404 1.64-3.157.75-4.05.53l.72-2.9c.896.23 3.757.67 3.33 2.37zm.41-4.24c-.37 1.49-2.662.735-3.405.55l.654-2.64c.744.18 3.137.524 2.75 2.084v.006z"
                            />
                        </svg>
                        <div class="flex-1">
                            <div class="font-semibold text-gray-900 dark:text-white">
                                {{ $t('balance_topup.payment_crypto') }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $t('balance_topup.payment_crypto_desc') }}
                            </div>
                        </div>
                        <div
                            :class="[
                                'w-5 h-5 rounded-full border-2 transition-all duration-200',
                                paymentMethod === 'crypto'
                                    ? 'border-blue-500 bg-blue-500'
                                    : 'border-gray-300 dark:border-gray-600'
                            ]"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Информация -->
        <div
            class="glass-card rounded-2xl p-5 mb-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800"
        >
            <div class="flex gap-3">
                <svg
                    class="w-6 h-6 text-blue-600 dark:text-blue-400 flex-shrink-0"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                </svg>
                <div class="text-sm text-blue-900 dark:text-blue-100">
                    <p class="font-semibold mb-1">{{ $t('balance_topup.info_title') }}</p>
                    <ul class="list-disc list-inside space-y-1 text-blue-800 dark:text-blue-200">
                        <li>{{ $t('balance_topup.info_instant') }}</li>
                        <li>{{ $t('balance_topup.info_time') }}</li>
                        <li>{{ $t('balance_topup.info_commission') }}</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Кнопка пополнения -->
        <div class="flex gap-3">
            <button
                class="px-6 py-3 rounded-lg border-2 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 transition-all duration-200"
                @click="router.push('/profile')"
            >
                {{ $t('balance_topup.cancel_button') }}
            </button>
            <button
                :disabled="!isValid || isProcessing"
                class="flex-1 px-6 py-3 rounded-lg bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-semibold transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                @click="handleTopUp"
            >
                <span v-if="!isProcessing">
                    {{ $t('balance_topup.topup_button') }} {{ formatCurrency(amount) }}
                </span>
                <span v-else class="flex items-center justify-center gap-2">
                    <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        ></circle>
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        ></path>
                    </svg>
                    {{ $t('balance_topup.processing') }}
                </span>
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useOptionStore } from '@/stores/options';
import { useLoadingStore } from '@/stores/loading';
import { useToast } from 'vue-toastification';
import { useI18n } from 'vue-i18n';
import axios from '@/bootstrap'; // Используем настроенный axios из bootstrap

const router = useRouter();
const authStore = useAuthStore();
const optionStore = useOptionStore();
const loadingStore = useLoadingStore();
const toast = useToast();
const { t } = useI18n();

// Состояния
const amount = ref(10);
const paymentMethod = ref<'card' | 'crypto'>('card');
const isProcessing = ref(false);

// Загрузка и инициализация компонента
onMounted(async () => {
    console.log('[BALANCE TOPUP] ====================================');
    console.log('[BALANCE TOPUP] Компонент загружен');

    // УЛУЧШЕНИЕ: Показываем прелоадер при загрузке данных
    loadingStore.start();

    try {
        console.log('[BALANCE TOPUP] User:', authStore.user);
        console.log('[BALANCE TOPUP] Balance:', authStore.user?.balance);
        console.log(
            '[BALANCE TOPUP] Token:',
            authStore.token ? authStore.token.substring(0, 20) + '...' : 'отсутствует'
        );
        console.log('[BALANCE TOPUP] isAuthenticated:', authStore.isAuthenticated);

        // Проверяем авторизацию
        if (!authStore.isAuthenticated) {
            console.log('[BALANCE TOPUP] Пользователь не авторизован, редирект на login');
            toast.warning(t('balance_topup.auth_required'));
            router.push('/login?redirect=/balance/topup');
            return;
        }

        // ВАЖНО: Всегда обновляем данные пользователя, чтобы получить актуальный баланс
        console.log(
            '[BALANCE TOPUP] Обновляем данные пользователя для получения актуального баланса...'
        );
        try {
            await authStore.fetchUser();
            console.log('[BALANCE TOPUP] Данные пользователя обновлены');
            console.log('[BALANCE TOPUP] Актуальный баланс:', authStore.user?.balance);
        } catch (error) {
            console.error('[BALANCE TOPUP] Ошибка обновления данных пользователя:', error);
            // Не критично, продолжаем работу с текущими данными
            toast.warning(t('balance_topup.failed_to_load'));
        }

        console.log('[BALANCE TOPUP] ✅ Компонент готов к работе');
    } finally {
        // ВАЖНО: Всегда останавливаем прелоадер
        console.log('[BALANCE TOPUP] ====================================');
        loadingStore.stop();
    }
});

// Предустановленные суммы
const presetAmounts = [5, 10, 25, 50, 100, 200];
const minAmount = 1;

// Валидация
const isValid = computed(() => {
    return amount.value >= minAmount && paymentMethod.value && !isProcessing.value;
});

// Форматирование валюты с защитой от некорректных значений
const formatCurrency = (value: number | string | null | undefined) => {
    const currency = optionStore.getOption('currency', 'USD');

    // Преобразуем значение в число и обрабатываем некорректные значения
    let numValue = 0;
    if (value !== null && value !== undefined) {
        numValue = typeof value === 'string' ? parseFloat(value) : value;
        if (isNaN(numValue)) {
            numValue = 0;
        }
    }

    return `${numValue.toFixed(2)} ${currency.toUpperCase()}`;
};

// Обработка пополнения
const handleTopUp = async () => {
    if (!isValid.value) return;

    isProcessing.value = true;
    loadingStore.start();

    try {
        let endpoint = '';

        if (paymentMethod.value === 'card') {
            endpoint = '/mono/topup';
        } else if (paymentMethod.value === 'crypto') {
            endpoint = '/cryptomus/topup';
        }

        const { data } = await axios.post(
            endpoint,
            {
                amount: amount.value
            },
            {
                headers: { Authorization: `Bearer ${authStore.token}` }
            }
        );

        if (data.url) {
            // Перенаправляем на страницу оплаты
            window.location.href = data.url;
        } else {
            toast.error(t('balance_topup.error_create_payment'));
            loadingStore.stop();
            isProcessing.value = false;
        }
    } catch (error: any) {
        console.error('Top up error:', error);
        const errMsg = error?.response?.data?.message || t('balance_topup.error_create_payment');
        toast.error(errMsg);
        loadingStore.stop();
        isProcessing.value = false;
    }
};
</script>

<style scoped>
.glass-card {
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.dark .glass-card {
    background: rgba(31, 41, 55, 0.7);
    border: 1px solid rgba(75, 85, 99, 0.3);
}
</style>
