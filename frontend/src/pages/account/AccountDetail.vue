<template>
    <div class="max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8 position-relative z-1">
        <div
            :class="[
                'min-h-screen transition-colors duration-300 mt-3',
                isDark ? 'text-gray-100' : 'text-gray-900'
            ]"
        >
            <!-- Анимированный градиентный фон -->
            <div class="fixed inset-0 pointer-events-none overflow-hidden">
                <div
                    :class="[
                        'animated-gradient absolute w-[120vw] h-[120vh]',
                        isDark ? 'opacity-60 blur-[80px]' : 'opacity-40 blur-[70px]'
                    ]"
                />
            </div>

            <div class="relative">
                <main>
                    <!-- Breadcrumbs и кнопка назад -->
                    <div
                        class="max-w-7xl mx-auto mt-6 mb-4 flex items-center justify-between flex-wrap gap-3"
                    >
                        <div class="flex items-center gap-2 text-sm">
                            <router-link
                                to="/"
                                class="text-gray-500 dark:text-gray-400 hover:text-purple-500 dark:hover:text-purple-400 transition-colors"
                            >
                                {{ $t('account.detail.home') }}
                            </router-link>
                            <svg
                                class="w-4 h-4 text-gray-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5l7 7-7 7"
                                />
                            </svg>
                            <span v-if="account?.category" class="text-gray-500 dark:text-gray-400">
                                {{ account.category.name }}
                            </span>
                            <svg
                                v-if="account?.category"
                                class="w-4 h-4 text-gray-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5l7 7-7 7"
                                />
                            </svg>
                            <span class="text-gray-700 dark:text-gray-200 font-medium">{{
                                account?.title || $t('account.detail.product')
                            }}</span>
                        </div>
                        <router-link
                            to="/"
                            class="flex items-center gap-2 text-dark dark:text-white hover:text-purple-500 dark:hover:text-purple-400 transition-colors glass-button px-4 py-2 rounded-full"
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
                                    d="M10 19l-7-7m0 0l7-7m-7 7h18"
                                />
                            </svg>
                            {{ $t('account.detail.back_to_catalog') }}
                        </router-link>
                    </div>

                    <!-- Состояние загрузки -->
                    <div v-if="!account" class="max-w-7xl mx-auto text-center py-20">
                        <div class="glass-card rounded-3xl p-12">
                            <div
                                class="animate-spin rounded-full h-16 w-16 border-b-2 border-purple-500 mx-auto"
                            ></div>
                            <p class="mt-4 text-gray-600 dark:text-gray-300">
                                {{ $t('account.detail.loading') }}
                            </p>
                        </div>
                    </div>

                    <!-- Основной контент -->
                    <div v-else class="max-w-7xl mx-auto">
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
                            <!-- Левая колонка: Карточка с изображением и действиями -->
                            <div class="lg:col-span-4 flex flex-col">
                                <div
                                    class="big-hero-card glass-card rounded-2xl p-5 flex flex-col gap-3 relative overflow-hidden"
                                >
                                    <!-- Декоративные элементы фона -->
                                    <div
                                        class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-purple-500/10 to-transparent rounded-full blur-2xl"
                                    ></div>
                                    <div
                                        class="absolute bottom-0 left-0 w-24 h-24 bg-gradient-to-tr from-blue-500/10 to-transparent rounded-full blur-2xl"
                                    ></div>

                                    <div class="flex flex-col items-center gap-2 relative z-10">
                                        <!-- Изображение товара с кнопкой избранного -->
                                        <div class="product-image-wrapper relative group">
                                            <img
                                                v-if="account.image_url"
                                                :src="account.image_url"
                                                :alt="getProductTitle(account)"
                                                loading="lazy"
                                                class="product-image"
                                            />
                                            <div
                                                v-else
                                                class="w-full h-full flex items-center justify-center bg-gray-100 dark:bg-gray-800"
                                            >
                                                <svg
                                                    class="w-24 h-24 text-gray-400"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                                    />
                                                </svg>
                                            </div>

                                            <!-- Кнопка избранного -->
                                            <button
                                                class="favorite-button absolute top-3 right-3 transition-all duration-300"
                                                :class="{ 'is-favorite': isFavorite }"
                                                :title="
                                                    isFavorite
                                                        ? $t('account.detail.remove_from_favorites')
                                                        : $t('account.detail.add_to_favorites')
                                                "
                                                @click="toggleFavorite"
                                            >
                                                <svg
                                                    class="w-6 h-6"
                                                    :fill="isFavorite ? 'currentColor' : 'none'"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
                                                    />
                                                </svg>
                                            </button>
                                        </div>

                                        <!-- Заголовок -->
                                        <div class="text-center w-full">
                                            <h2
                                                class="text-xl font-bold text-dark dark:text-white mb-1"
                                            >
                                                {{ getProductTitle(account) }}
                                            </h2>
                                            <!-- Артикул товара -->
                                            <div
                                                v-if="account.sku"
                                                class="sku-badge"
                                                :title="
                                                    $t('account.detail.sku') +
                                                    ': ' +
                                                    account.sku +
                                                    ' (' +
                                                    $t('account.detail.click_to_copy') +
                                                    ')'
                                                "
                                                @click="copySku"
                                            >
                                                <svg
                                                    class="w-3 h-3"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"
                                                    />
                                                </svg>
                                                <span>{{ account.sku }}</span>
                                                <svg
                                                    class="w-3 h-3 copy-icon"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"
                                                    />
                                                </svg>
                                            </div>
                                        </div>

                                        <!-- Цена с дизайном -->
                                        <div class="text-center w-full">
                                            <div class="price-tag-wrapper">
                                                <span
                                                    class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                                    >{{ $t('account.detail.price') }}</span
                                                >
                                                <div class="price-tag text-3xl">
                                                    {{ formatPrice(account.price) }}
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Индикатор наличия - улучшенный дизайн -->
                                        <div
                                            :class="[
                                                'stock-indicator',
                                                account.quantity && account.quantity > 0
                                                    ? 'in-stock'
                                                    : 'out-of-stock'
                                            ]"
                                        >
                                            <svg
                                                class="w-5 h-5"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    v-if="account.quantity && account.quantity > 0"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M5 13l4 4L19 7"
                                                />
                                                <path
                                                    v-else
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12"
                                                />
                                            </svg>
                                            {{
                                                account.quantity && account.quantity > 0
                                                    ? $t('account.detail.in_stock_count', {
                                                          count: account.quantity
                                                      })
                                                    : $t('account.detail.no_stock')
                                            }}
                                        </div>

                                        <!-- Счетчик количества -->
                                        <div
                                            v-if="account.quantity && account.quantity > 0"
                                            class="quantity-selector"
                                        >
                                            <button
                                                type="button"
                                                class="quantity-btn"
                                                :disabled="quantity <= 1"
                                                @click="decreaseQuantity"
                                            >
                                                <svg
                                                    class="w-4 h-4"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M20 12H4"
                                                    />
                                                </svg>
                                            </button>
                                            <input
                                                v-model.number="quantity"
                                                type="number"
                                                class="quantity-input"
                                                min="1"
                                                :max="account.quantity"
                                                @input="validateQuantity"
                                            />
                                            <button
                                                type="button"
                                                class="quantity-btn"
                                                :disabled="quantity >= account.quantity"
                                                @click="increaseQuantity"
                                            >
                                                <svg
                                                    class="w-4 h-4"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M12 4v16m8-8H4"
                                                    />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Кнопки действий - улучшенный дизайн -->
                                    <div class="w-full relative z-10">
                                        <div class="flex flex-col gap-2.5">
                                            <button
                                                class="modern-button modern-button-primary"
                                                :disabled="
                                                    !account.quantity || account.quantity === 0
                                                "
                                                type="button"
                                                @click="addToCart"
                                            >
                                                <span class="modern-button-content">
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
                                                            d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"
                                                        />
                                                    </svg>
                                                    {{ $t('account.detail.add_to_cart') }}
                                                </span>
                                            </button>

                                            <button
                                                class="modern-button modern-button-outline"
                                                :disabled="
                                                    !account.quantity || account.quantity === 0
                                                "
                                                type="button"
                                                @click="buyNow"
                                            >
                                                <span class="modern-button-content">
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
                                                            d="M13 10V3L4 14h7v7l9-11h-7z"
                                                        />
                                                    </svg>
                                                    {{ $t('account.detail.buy_now') }}
                                                </span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Правая колонка: Информация о товаре -->
                            <div class="lg:col-span-8 flex flex-col">
                                <!-- Основная информация -->
                                <div
                                    class="info-panel glass-card rounded-2xl p-4 relative overflow-hidden"
                                >
                                    <div>
                                        <div class="flex items-start justify-between mb-3">
                                            <div>
                                                <h1
                                                    class="text-2xl lg:text-3xl text-gray-900 dark:text-white font-extrabold mb-1.5 info-heading"
                                                >
                                                    {{ getProductTitle(account) }}
                                                </h1>
                                                <div
                                                    class="flex items-center gap-2.5 text-xs text-gray-500 dark:text-gray-400"
                                                >
                                                    <span class="flex items-center gap-1">
                                                        <svg
                                                            class="w-4 h-4"
                                                            fill="none"
                                                            stroke="currentColor"
                                                            viewBox="0 0 24 24"
                                                        >
                                                            <path
                                                                stroke-linecap="round"
                                                                stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                                            />
                                                            <path
                                                                stroke-linecap="round"
                                                                stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                                            />
                                                        </svg>
                                                        {{ account.views ?? 0 }} просмотров
                                                    </span>
                                                    <span class="flex items-center gap-1">
                                                        <svg
                                                            class="w-4 h-4"
                                                            fill="none"
                                                            stroke="currentColor"
                                                            viewBox="0 0 24 24"
                                                        >
                                                            <path
                                                                stroke-linecap="round"
                                                                stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                                            />
                                                        </svg>
                                                        {{ account.sold ?? 0 }} продано
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Описание -->
                                        <div class="description-section">
                                            <h3
                                                class="text-base font-semibold text-gray-900 dark:text-white mb-2 flex items-center gap-2"
                                            >
                                                <svg
                                                    class="w-4 h-4 text-purple-500"
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
                                                {{ $t('account.detail.description_title') }}
                                            </h3>
                                            <div
                                                v-if="description"
                                                class="text-gray-900 dark:text-gray-300 info-body product-content"
                                                v-html="description"
                                            />
                                            <p
                                                v-else
                                                class="text-gray-500 dark:text-gray-400 italic"
                                            >
                                                {{ $t('account.detail.no_description') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Похожие товары -->
                    <div v-if="account" class="max-w-7xl mx-auto mt-8">
                        <SimilarProducts :product-id="account.id || account.sku" />
                    </div>
                </main>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAccountsStore, type AccountItem } from '@/stores/accounts';
import { useProductCartStore } from '@/stores/productCart';
import { useOptionStore } from '@/stores/options';
import { useTheme } from '@/composables/useTheme';
import { useToast } from 'vue-toastification';
import { useI18n } from 'vue-i18n';
import { useLoadingStore } from '@/stores/loading';
import { useProductTitle } from '@/composables/useProductTitle';
import SimilarProducts from '@/components/products/SimilarProducts.vue';

const route = useRoute();
const router = useRouter();
const accountsStore = useAccountsStore();
const productCartStore = useProductCartStore();
const optionStore = useOptionStore();
const loadingStore = useLoadingStore();
const { isDark } = useTheme();
const toast = useToast();
const { t } = useI18n();
const { getProductTitle, getProductDescription } = useProductTitle();
const account = ref<AccountItem | null>(null);
const quantity = ref(1);

// Управление избранным (сохранение в localStorage)
const FAVORITES_KEY = 'product_favorites';

const favorites = ref<Set<number>>(new Set());

const description = computed(() => {
    return account.value ? getProductDescription(account.value, true) : '';
});

// Загрузка избранных из localStorage
const loadFavorites = () => {
    try {
        const stored = localStorage.getItem(FAVORITES_KEY);
        if (stored) {
            favorites.value = new Set(JSON.parse(stored));
        }
    } catch (e) {
        console.error('Ошибка загрузки избранного:', e);
    }
};

// Сохранение избранных в localStorage
const saveFavorites = () => {
    try {
        localStorage.setItem(FAVORITES_KEY, JSON.stringify(Array.from(favorites.value)));
    } catch (e) {
        console.error('Ошибка сохранения избранного:', e);
    }
};

// Проверка, находится ли товар в избранном
const isFavorite = computed(() => {
    return account.value ? favorites.value.has(account.value.id) : false;
});

// Переключение избранного
const toggleFavorite = () => {
    if (!account.value) return;

    if (favorites.value.has(account.value.id)) {
        favorites.value.delete(account.value.id);
        toast.info(t('account.detail.removed_from_favorites'));
    } else {
        favorites.value.add(account.value.id);
        toast.success(t('account.detail.added_to_favorites'));
    }

    saveFavorites();
};

// Копирование артикула в буфер обмена
const copySku = async () => {
    if (!account.value?.sku) return;

    try {
        await navigator.clipboard.writeText(account.value.sku);
        toast.success(t('account.detail.sku_copied'));
    } catch {
        // Fallback для старых браузеров
        const textarea = document.createElement('textarea');
        textarea.value = account.value.sku;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        try {
            document.execCommand('copy');
            toast.success(t('account.detail.sku_copied'));
        } catch {
            toast.error(t('profile.purchases.copy_error'));
        }
        document.body.removeChild(textarea);
    }
};

// Управление количеством товара
const increaseQuantity = () => {
    if (!account.value) return;
    if (quantity.value < account.value.quantity) {
        quantity.value++;
    }
};

const decreaseQuantity = () => {
    if (quantity.value > 1) {
        quantity.value--;
    }
};

const validateQuantity = () => {
    if (!account.value) return;

    // Проверяем что значение числовое
    if (isNaN(quantity.value) || quantity.value < 1) {
        quantity.value = 1;
    }

    // Проверяем что не превышает доступное количество
    if (quantity.value > account.value.quantity) {
        quantity.value = account.value.quantity;
    }

    // Округляем до целого числа
    quantity.value = Math.floor(quantity.value);
};

// Форматирование цены
function formatPrice(value: number): string {
    const num = Number(value ?? 0);
    const currency = optionStore.getOption('currency', 'USD');
    try {
        return new Intl.NumberFormat('ru-RU', {
            style: 'currency',
            currency: currency,
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(num);
    } catch {
        return `${currency} ${num.toFixed(2)}`;
    }
}

// Добавить в корзину
const addToCart = () => {
    if (!account.value) return;
    if (!account.value.quantity || account.value.quantity === 0) {
        toast.error(t('account.detail.out_of_stock_error'));
        return;
    }
    productCartStore.addItem(account.value, quantity.value);
    toast.success(t('account.detail.added_to_cart', { count: quantity.value }));
};

// Купить сейчас
const buyNow = () => {
    if (!account.value) return;
    if (!account.value.quantity || account.value.quantity === 0) {
        toast.error(t('account.detail.out_of_stock_error'));
        return;
    }
    productCartStore.clearCart();
    productCartStore.addItem(account.value, quantity.value);
    router.push('/checkout');
};

onMounted(async () => {
    // Загружаем избранное
    loadFavorites();

    // УЛУЧШЕНИЕ: Показываем прелоадер при загрузке товара
    loadingStore.start();

    try {
        // Загружаем товар (ID или артикул)
        const idOrSku = route.params.id as string;
        if (!idOrSku) {
            router.replace('/404');
            return;
        }

        // Пытаемся загрузить товар (поддерживает и ID, и артикул)
        account.value = await accountsStore.fetchById(idOrSku).catch(() => null);
        if (!account.value) {
            router.replace('/404');
            return;
        }
    } finally {
        // Останавливаем прелоадер после загрузки
        loadingStore.stop();
    }
});
</script>

<style scoped>
/* Анимированный градиент - точно как на других страницах */
.animated-gradient {
    background: linear-gradient(
        120deg,
        rgba(255, 106, 0, 0.35) 10%,
        rgba(255, 0, 204, 0.55) 35%,
        rgba(0, 170, 255, 0.75) 70%,
        rgba(0, 123, 255, 0.45) 90%
    );
    animation: gradientMove 30s ease-in-out infinite;
}

@keyframes gradientMove {
    0%,
    100% {
        transform: translate(-18%, -18%) rotate(0deg) scale(1);
    }
    25% {
        transform: translate(-10%, -22%) rotate(20deg) scale(1.03);
    }
    50% {
        transform: translate(8%, -12%) rotate(40deg) scale(0.98);
    }
    75% {
        transform: translate(-12%, 8%) rotate(25deg) scale(1.02);
    }
}

/* Glass button - точно как на других страницах */
.glass-button {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    transition: all 0.3s ease;
}

.glass-button:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.2);
}

/* Glass card - точно как на других страницах */
.glass-card {
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.03), rgba(255, 255, 255, 0.01));
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow: 0 8px 30px rgba(2, 6, 23, 0.35);
}

/* Big hero card - точно как на ServicePage */
.big-hero-card {
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.03), rgba(255, 255, 255, 0.01));
    box-shadow: 0 8px 30px rgba(2, 6, 23, 0.35);
    display: flex;
    flex-direction: column;
}

/* Изображение товара - улучшенный дизайн */
.product-image-wrapper {
    width: 100%;
    height: 180px;
    border-radius: 1rem;
    overflow: hidden;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.product-image-wrapper:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    border-color: rgba(139, 92, 231, 0.3);
}

.dark .product-image-wrapper {
    background: rgba(30, 41, 59, 0.3);
    border-color: rgba(255, 255, 255, 0.05);
}

.product-image {
    width: 100%;
    height: 100%;
    object-fit: contain;
    transition: transform 0.4s ease;
}

.product-image-wrapper:hover .product-image {
    transform: scale(1.05);
}

/* Кнопка избранного */
.favorite-button {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 50%;
    border: 2px solid rgba(255, 255, 255, 0.3);
    color: #6b7280;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    z-index: 10;
}

.favorite-button svg {
    width: 1.25rem;
    height: 1.25rem;
    transition: transform 0.3s ease;
}

.favorite-button:hover {
    background: rgba(255, 255, 255, 1);
    transform: scale(1.15);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
}

.favorite-button:hover svg {
    transform: scale(1.1);
}

.favorite-button:active {
    transform: scale(0.95);
}

.favorite-button.is-favorite {
    background: linear-gradient(135deg, #ec4899, #ef4444);
    color: white;
    border-color: rgba(255, 255, 255, 0.5);
    animation: heartbeat 0.3s ease;
}

.favorite-button.is-favorite:hover {
    background: linear-gradient(135deg, #db2777, #dc2626);
    box-shadow: 0 6px 20px rgba(236, 72, 153, 0.4);
}

@keyframes heartbeat {
    0%,
    100% {
        transform: scale(1);
    }
    25% {
        transform: scale(1.2);
    }
    50% {
        transform: scale(1.1);
    }
    75% {
        transform: scale(1.25);
    }
}

/* Артикул товара */
.sku-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.25rem 0.75rem;
    background: rgba(99, 102, 241, 0.08);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(99, 102, 241, 0.2);
    border-radius: 0.5rem;
    font-size: 0.6875rem;
    font-weight: 600;
    color: #6366f1;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-top: 0.25rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.sku-badge:hover {
    background: rgba(99, 102, 241, 0.15);
    border-color: rgba(99, 102, 241, 0.4);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
}

.sku-badge:active {
    transform: translateY(0);
}

.dark .sku-badge {
    background: rgba(139, 92, 231, 0.12);
    border-color: rgba(139, 92, 231, 0.3);
    color: #a78bfa;
}

.dark .sku-badge:hover {
    background: rgba(139, 92, 231, 0.2);
    border-color: rgba(139, 92, 231, 0.5);
    box-shadow: 0 4px 12px rgba(139, 92, 231, 0.3);
}

.sku-badge svg {
    flex-shrink: 0;
}

.sku-badge .copy-icon {
    opacity: 0.5;
    transition: opacity 0.3s ease;
}

.sku-badge:hover .copy-icon {
    opacity: 1;
}

/* Обертка цены */
.price-tag-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
}

/* Цена - улучшенный дизайн */
.price-tag {
    font-size: 1.875rem;
    font-weight: 800;
    background: linear-gradient(135deg, #6366f1, #8b5cf6, #ec4899);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    letter-spacing: -0.02em;
}

/* Индикатор наличия - улучшенный дизайн */
.stock-indicator {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 9999px;
    font-size: 0.8125rem;
    font-weight: 600;
    border: 2px solid;
    transition: all 0.3s ease;
}

.stock-indicator:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.in-stock {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(34, 197, 94, 0.1));
    border-color: rgba(16, 185, 129, 0.4);
    color: #10b981;
}

.out-of-stock {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(220, 38, 38, 0.1));
    border-color: rgba(239, 68, 68, 0.4);
    color: #ef4444;
}

/* Счетчик количества товара */
.quantity-selector {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.5rem;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(0, 0, 0, 0.1);
    border-radius: 0.75rem;
    width: fit-content;
    margin: 0 auto;
}

.dark .quantity-selector {
    background: rgba(30, 41, 59, 0.3);
    backdrop-filter: blur(10px);
    border-color: rgba(255, 255, 255, 0.08);
}

.quantity-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border-radius: 0.5rem;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: white;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.quantity-btn:hover:not(:disabled) {
    background: linear-gradient(135deg, #8b5cf6, #a855f7);
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.quantity-btn:active:not(:disabled) {
    transform: scale(0.95);
}

.quantity-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
    background: #9ca3af;
}

.quantity-input {
    width: 3.5rem;
    height: 2rem;
    text-align: center;
    font-size: 0.9375rem;
    font-weight: 600;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(0, 0, 0, 0.1);
    border-radius: 0.5rem;
    color: #000;
    outline: none;
    transition: all 0.3s ease;
}

.quantity-input:focus {
    border-color: rgba(139, 92, 231, 0.5);
    background: rgba(255, 255, 255, 0.25);
    backdrop-filter: blur(10px);
    box-shadow: 0 0 0 3px rgba(139, 92, 231, 0.1);
}

.quantity-input::-webkit-inner-spin-button,
.quantity-input::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.quantity-input[type='number'] {
    appearance: textfield;
    -moz-appearance: textfield;
}

.dark .quantity-input {
    background: rgba(30, 41, 59, 0.5);
    backdrop-filter: blur(10px);
    border-color: rgba(255, 255, 255, 0.08);
    color: white;
}

/* Современные кнопки */
.modern-button {
    position: relative;
    overflow: hidden;
    z-index: 10;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.625rem 1.25rem;
    border-radius: 0.625rem;
    font-weight: 600;
    font-size: 0.9375rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: none;
    cursor: pointer;
    width: 100%;
}

.modern-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.modern-button-content {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.625rem;
    position: relative;
    z-index: 10;
}

/* Основная кнопка с градиентом */
.modern-button-primary {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #a855f7 100%);
    color: white;
    box-shadow:
        0 4px 12px rgba(99, 102, 241, 0.3),
        0 0 0 1px rgba(255, 255, 255, 0.1) inset;
}

.modern-button-primary::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, #8b5cf6 0%, #a855f7 50%, #d946ef 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
    border-radius: 0.75rem;
}

.modern-button-primary:hover:not(:disabled)::before {
    opacity: 1;
}

.modern-button-primary:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow:
        0 8px 24px rgba(99, 102, 241, 0.4),
        0 0 0 1px rgba(255, 255, 255, 0.15) inset;
}

.modern-button-primary:active:not(:disabled) {
    transform: translateY(0);
}

/* Кнопка с обводкой */
.modern-button-outline {
    background: rgba(99, 102, 241, 0.05);
    backdrop-filter: blur(10px);
    border: 2px solid;
    border-image: linear-gradient(135deg, #6366f1, #8b5cf6, #a855f7) 1;
    color: #8b5cf6;
    box-shadow: 0 2px 8px rgba(99, 102, 241, 0.15);
}

.modern-button-outline:hover:not(:disabled) {
    background: rgba(99, 102, 241, 0.1);
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(99, 102, 241, 0.25);
}

.modern-button-outline:active:not(:disabled) {
    transform: translateY(0);
}

.dark .modern-button-outline {
    background: rgba(139, 92, 231, 0.1);
    color: #a78bfa;
}

.dark .modern-button-outline:hover:not(:disabled) {
    background: rgba(139, 92, 231, 0.15);
}

/* Info panel - точно как на других страницах */
.info-panel {
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.02), rgba(255, 255, 255, 0.01));
    border-radius: 24px;
}

.info-heading {
    line-height: 1.2;
    letter-spacing: -0.02em;
}

.info-body {
    line-height: 1.8;
}

/* Секция описания */
.description-section {
    padding: 0.75rem;
    background: rgba(99, 102, 241, 0.02);
    border-radius: 0.625rem;
    border: 1px solid rgba(99, 102, 241, 0.1);
}

/* Контент продукта */
.product-content {
    line-height: 1.6;
    font-size: 0.9375rem;
}

.product-content :deep(p) {
    margin-bottom: 0.75rem;
    color: inherit;
}

.product-content :deep(ul),
.product-content :deep(ol) {
    margin-left: 1.5rem;
    margin-bottom: 1rem;
}

.product-content :deep(li) {
    margin-bottom: 0.5rem;
}

.product-content :deep(strong) {
    font-weight: 600;
    color: inherit;
}

/* Панель статистики */
.stats-panel {
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.02), rgba(255, 255, 255, 0.01));
}

/* Современная сетка статистики */
.stats-grid-modern {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 0.875rem;
}

/* Карточка статистики */
.stat-card {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.875rem;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 0.75rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 2px;
    background: linear-gradient(90deg, transparent, currentColor, transparent);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.stat-card:hover {
    background: rgba(255, 255, 255, 0.05);
    border-color: rgba(255, 255, 255, 0.15);
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
}

.stat-card:hover::before {
    opacity: 0.5;
}

/* Иконка карточки статистики */
.stat-card-icon {
    flex-shrink: 0;
    width: 2.75rem;
    height: 2.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.625rem;
    transition: transform 0.3s ease;
}

.stat-card-icon svg {
    width: 1.125rem;
    height: 1.125rem;
}

.stat-card:hover .stat-card-icon {
    transform: scale(1.1) rotate(5deg);
}

.stat-card-icon.purple {
    background: linear-gradient(135deg, rgba(124, 58, 237, 0.2), rgba(139, 92, 231, 0.3));
    color: #a78bfa;
}

.stat-card-icon.blue {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(96, 165, 250, 0.3));
    color: #60a5fa;
}

.stat-card-icon.green {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(34, 197, 94, 0.3));
    color: #34d399;
}

.stat-card-icon.orange {
    background: linear-gradient(135deg, rgba(249, 115, 22, 0.2), rgba(251, 146, 60, 0.3));
    color: #fb923c;
}

/* Контент карточки статистики */
.stat-card-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.stat-card-label {
    font-size: 0.6875rem;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 600;
}

.stat-card-value {
    font-size: 1.375rem;
    font-weight: 700;
    color: #fff;
    line-height: 1;
}

.stat-card-info {
    margin-top: 0.25rem;
}

/* Прогресс-бар в карточке статистики */
.stat-card-progress {
    width: 100%;
    height: 4px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 9999px;
    overflow: hidden;
    margin-top: 0.5rem;
}

.stat-card-progress-bar {
    height: 100%;
    border-radius: 9999px;
    transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

.stat-card-progress-bar.purple {
    background: linear-gradient(90deg, #7c3aed, #a78bfa);
}

.stat-card-progress-bar.blue {
    background: linear-gradient(90deg, #3b82f6, #60a5fa);
}

.stat-card-progress-bar.green {
    background: linear-gradient(90deg, #10b981, #34d399);
}

/* Адаптивный дизайн */
@media (max-width: 1024px) {
    .product-image-wrapper {
        height: 200px;
    }

    .stats-grid-modern {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .product-image-wrapper {
        height: 180px;
    }

    .price-tag {
        font-size: 1.875rem;
    }

    .stat-card-value {
        font-size: 1.5rem;
    }

    .stats-grid-modern {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 640px) {
    main {
        padding-left: 12px;
        padding-right: 12px;
    }

    .big-hero-card {
        padding: 1.25rem;
    }

    .info-panel,
    .stats-panel {
        padding: 1rem;
    }

    .product-image-wrapper {
        height: 160px;
    }

    .favorite-button {
        width: 42px;
        height: 42px;
    }

    .favorite-button svg {
        width: 1.375rem;
        height: 1.375rem;
    }

    .price-tag {
        font-size: 1.5rem;
    }

    .stat-card {
        padding: 1rem;
    }

    .stat-card-icon {
        width: 3rem;
        height: 3rem;
    }

    .stat-card-value {
        font-size: 1.375rem;
    }

    .modern-button {
        padding: 0.75rem 1.25rem;
        font-size: 0.9375rem;
    }

    .description-section {
        padding: 0.625rem;
    }

    .product-content {
        font-size: 0.875rem;
        line-height: 1.5;
    }

    h1.info-heading {
        font-size: 1.75rem !important;
    }

    h3 {
        font-size: 1.125rem !important;
    }

    /* Счетчик количества на мобильных */
    .quantity-selector {
        width: 100%;
        justify-content: center;
    }

    .quantity-btn {
        width: 2.5rem;
        height: 2.5rem;
    }

    .quantity-input {
        width: 4rem;
        height: 2.5rem;
        font-size: 1rem;
    }
}

/* Dark mode улучшения */
.dark .description-section {
    background: rgba(139, 92, 231, 0.05);
    border-color: rgba(139, 92, 231, 0.15);
}

.dark .stat-card {
    background: rgba(255, 255, 255, 0.02);
    border-color: rgba(255, 255, 255, 0.06);
}

.dark .stat-card:hover {
    background: rgba(255, 255, 255, 0.04);
    border-color: rgba(255, 255, 255, 0.12);
}

.dark .favorite-button {
    background: rgba(30, 41, 59, 0.95);
    backdrop-filter: blur(10px);
    border-color: rgba(255, 255, 255, 0.15);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.dark .favorite-button:hover {
    background: rgba(30, 41, 59, 1);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
}

.dark .favorite-button.is-favorite {
    box-shadow: 0 6px 20px rgba(236, 72, 153, 0.5);
}
</style>
