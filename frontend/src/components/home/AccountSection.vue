<template>
    <div>
        <!-- Loading skeleton -->
        <div v-if="!accountsStore.loaded" class="space-y-5">
            <div v-for="i in 6" :key="'skeleton-' + i" class="product-card-skeleton">
                <div class="skeleton-image"></div>
                <div class="flex-1 space-y-3">
                    <div class="skeleton-title"></div>
                    <div class="skeleton-text"></div>
                    <div class="skeleton-text w-3/4"></div>
                </div>
                <div class="skeleton-actions"></div>
            </div>
        </div>

        <!-- No results message -->
        <div v-else-if="filteredAccounts.length === 0" class="text-center py-12">
            <svg
                class="w-16 h-16 mx-auto text-gray-400 mb-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
                />
            </svg>
            <p class="text-gray-500 dark:text-gray-400 text-lg">{{ $t('account.no_accounts') }}</p>
        </div>

        <!-- Products Grid -->
        <div v-else class="space-y-5">
            <div
                v-for="(account, index) in displayedAccounts"
                :key="account.id"
                v-memo="[account.id, account.quantity, account.has_discount, account.discount_percent, (account as any)._cachedTitle]"
                class="product-card"
                :class="{
                    'out-of-stock-card': account.quantity <= 0,
                    'with-discount': account.has_discount && account.discount_percent
                }"
            >
                <!-- Полоса со скидкой вверху карточки -->
                <div
                    v-if="account.has_discount && account.discount_percent"
                    class="discount-stripe"
                >
                    Скидка -{{ Math.round(account.discount_percent) }}%
                </div>

                <!-- Left: Product Image -->
                <div
                    class="product-image-wrapper clickable"
                    :title="$t('account.detail.go_to_product', { title: (account as any)._cachedTitle })"
                    @click="$router.push(`/account/${account.sku || account.id}`)"
                >
                    <img
                        :src="account.image_url || '/img/no-logo.png'"
                        :alt="(account as any)._cachedTitle"
                        class="product-image"
                        :loading="index < 6 ? 'eager' : 'lazy'"
                        :fetchpriority="index < 3 ? 'high' : 'auto'"
                        decoding="async"
                        width="400"
                        height="300"
                    />
                </div>

                <!-- Center: Product Info -->
                <div class="product-info">
                    <div class="title-with-badge">
                        <!-- Stock Badge - слева от названия -->
                        <div
                            class="stock-badge-inline"
                            :class="account.quantity > 0 ? 'in-stock' : 'out-of-stock'"
                        >
                            <svg
                                v-if="account.quantity > 0"
                                class="w-3 h-3"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            <svg v-else class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            <span>{{ account.quantity > 0 ? account.quantity : '0' }}</span>
                        </div>

                        <h3
                            class="product-title clickable-title"
                            :title="'Перейти к ' + (account as any)._cachedTitle"
                            @click="$router.push(`/account/${account.sku || account.id}`)"
                        >
                            {{ (account as any)._cachedTitle }}
                        </h3>
                    </div>

                    <!-- Артикул товара -->
                    <div v-if="account.sku" class="product-sku product-sku--desktop">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"
                            />
                        </svg>
                        <span>{{ account.sku }}</span>
                    </div>

                    <p
                        v-if="(account as any)._cachedDescription"
                        class="product-description"
                        v-html="(account as any)._cachedDescription"
                    ></p>
                </div>

                <!-- Right: Actions -->
                <div class="product-actions">
                    <!-- Top Row: Price and Quantity Control -->
                    <div class="top-actions-row">
                        <!-- Price Section -->
                        <div class="price-section">
                            <div class="price-wrapper">
                                <span
                                    v-if="account.has_discount && account.price"
                                    class="price-old"
                                >
                                    {{ formatPrice(account.price) }}
                                </span>
                                <div class="price">
                                    {{
                                        formatTotalPrice(
                                            account.current_price || account.price,
                                            getQuantity(account.id)
                                        )
                                    }}
                                </div>
                            </div>
                            <div class="price-per-unit">
                                {{
                                    $t('account.detail.price_per_unit', {
                                        price: formatPrice(account.current_price || account.price),
                                        quantity: getQuantity(account.id)
                                    })
                                }}
                            </div>
                            <div class="product-sku--mobile">                                                <div v-if="account.sku" class="product-sku">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"
                            />
                        </svg>
                        <span>{{ account.sku }}</span>
                    </div></div>
                        </div>

                        <!-- Quantity Control -->
                        <div class="quantity-control">
                            <button
                                class="quantity-btn"
                                :disabled="getQuantity(account.id) <= 1"
                                @click="decreaseQuantity(account.id)"
                            >
                                −
                            </button>
                            <input
                                type="text"
                                :value="getQuantity(account.id)"
                                readonly
                                class="quantity-input"
                            />
                            <button
                                class="quantity-btn"
                                :disabled="getQuantity(account.id) >= (account.quantity || 1)"
                                @click="increaseQuantity(account.id)"
                            >
                                +
                            </button>
                        </div>
                    </div>

                    <!-- Bottom Row: Action Buttons -->
                    <div class="actions-row">
                        <button
                            class="btn-secondary btn-icon"
                            title="Подробнее"
                            @click="$router.push(`/account/${account.sku || account.id}`)"
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
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </button>

                        <button
                            class="btn-secondary btn-icon"
                            :class="{ active: isFavorite(account.id) }"
                            title="В избранное"
                            @click="toggleFavorite(account.id)"
                        >
                            <svg
                                class="w-5 h-5"
                                :fill="isFavorite(account.id) ? 'currentColor' : 'none'"
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

                        <button
                            class="btn-cart"
                            :disabled="!account.quantity || account.quantity === 0"
                            title="Добавить в корзину"
                            @click="addToCart(account)"
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
                                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"
                                />
                            </svg>
                        </button>

                        <button
                            class="btn-primary"
                            :disabled="!account.quantity || account.quantity === 0"
                            @click="buyNow(account)"
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
                                    d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"
                                />
                            </svg>
                            {{ $t('account.buy_now') }}
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="filteredAccounts.length === 0" class="text-center py-12">
                <svg
                    class="w-16 h-16 mx-auto text-gray-400 mb-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
                    />
                </svg>
                <p class="text-gray-500 dark:text-gray-400 text-lg">
                    {{ $t('account.no_accounts') }}
                </p>
            </div>
        </div>

        <div v-if="hasMore" class="text-center mt-8">
            <button
                class="cta-button dark:border-gray-300 dark:text-white dark:hover:border-blue-900 pointer-events-auto cursor-pointer"
                @click="loadMore"
            >
                {{ $t('account.show_more') || 'Показать еще' }} ({{ filteredAccounts.length - displayedAccounts.length }})
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useAccountsStore } from '@/stores/accounts';
import { useProductCartStore } from '@/stores/productCart';
import { useI18n } from 'vue-i18n';
import { useOptionStore } from '@/stores/options';
import { useToast } from 'vue-toastification';
import { useRouter } from 'vue-router';
import { useProductTitle } from '@/composables/useProductTitle';
import { useProductCategoriesStore } from '@/stores/productCategories';

interface FilterProps {
    categoryId?: number | null;
    subcategoryId?: number | null;
    hideOutOfStock?: boolean;
    showFavoritesOnly?: boolean;
    searchQuery?: string;
}

const props = defineProps<{
    filters?: FilterProps;
}>();

const accountsStore = useAccountsStore();
const productCartStore = useProductCartStore();
const categoriesStore = useProductCategoriesStore();
const { t } = useI18n();
const optionStore = useOptionStore();
const toast = useToast();
const router = useRouter();
const { getProductTitle, getProductDescription } = useProductTitle();
const { locale } = useI18n();

// Пагинация вместо показа всех карточек (критическая оптимизация FPS)
const itemsPerPage = 12; // Показываем по 12 карточек за раз
const currentPage = ref(1);

// КРИТИЧЕСКАЯ ОПТИМИЗАЦИЯ: Предвычисляем title и description для всех товаров
// Это избавляет от множественных вызовов getProductTitle/getProductDescription
const accounts = computed(() => {
    const currentLocale = locale.value;
    return accountsStore.list.map(account => ({
        ...account,
        // Предвычисляем title и description один раз при изменении списка или локали
        _cachedTitle: getProductTitle(account),
        _cachedDescription: getProductDescription(account)
    }));
});

const filteredAccounts = computed(() => {
    let result = accounts.value;
    
    // Ранний выход если нет фильтров
    if (!props.filters || Object.keys(props.filters).length === 0) {
        return result;
    }
    
    // Оптимизация: фильтруем по самому селективному фильтру первым
    // Filter by category and subcategory
    if (props.filters?.subcategoryId !== null && props.filters?.subcategoryId !== undefined) {
        // Если выбрана подкатегория - показываем только товары из этой подкатегории
        result = result.filter(account => account.category?.id === props.filters?.subcategoryId);
    } else if (props.filters?.categoryId !== null && props.filters?.categoryId !== undefined) {
        // Если выбрана только категория - показываем товары из категории и всех её подкатегорий
        const category = categoriesStore.list.find(cat => cat.id === props.filters?.categoryId);
        const subcategoryIds = category?.subcategories?.map(sub => sub.id) || [];
        // Используем Set для быстрого поиска вместо массива
        const categoryIds = new Set([props.filters.categoryId, ...subcategoryIds]);
        result = result.filter(account => {
            const accountCategoryId = account.category?.id;
            return accountCategoryId && categoryIds.has(accountCategoryId);
        });
    }

    // Filter out of stock
    if (props.filters?.hideOutOfStock) {
        result = result.filter(account => account.quantity && account.quantity > 0);
    }

    // Filter favorites
    if (props.filters?.showFavoritesOnly) {
        result = result.filter(account => favorites.value.has(account.id));
    }

    // Search filter (поиск по названию, описанию и артикулу) - используем кэшированные значения
    if (props.filters?.searchQuery && props.filters.searchQuery.trim()) {
        const query = props.filters.searchQuery.toLowerCase().trim();
        const queryWords = query.split(/\s+/).filter(w => w.length > 0); // Разбиваем на слова
        
        result = result.filter(account => {
            // Используем предвычисленные значения вместо вызова функций
            const title = ((account as any)._cachedTitle || '').toLowerCase();
            const description = ((account as any)._cachedDescription || '').toLowerCase();
            const sku = (account.sku || '').toLowerCase();
            
            // Проверяем каждое слово запроса
            return queryWords.every(word => 
                title.includes(word) || description.includes(word) || sku.includes(word)
            );
        });
    }
    return result;
});

// Показываем только видимые карточки (пагинация)
const displayedAccounts = computed(() => {
    const endIndex = currentPage.value * itemsPerPage;
    return filteredAccounts.value.slice(0, endIndex);
});

const hasMore = computed(() => {
    return displayedAccounts.value.length < filteredAccounts.value.length;
});

const loadMore = () => {
    currentPage.value++;
};

// Сбрасываем страницу при изменении фильтров
watch(() => props.filters, () => {
    currentPage.value = 1;
}, { deep: true });

// Quantity management
const quantities = ref<Record<number, number>>({});

const getQuantity = (accountId: number) => {
    return quantities.value[accountId] || 1;
};

const increaseQuantity = (accountId: number) => {
    const current = quantities.value[accountId] || 1;
    quantities.value[accountId] = current + 1;
};

const decreaseQuantity = (accountId: number) => {
    const current = quantities.value[accountId] || 1;
    if (current > 1) {
        quantities.value[accountId] = current - 1;
    }
};

// Favorites management (с сохранением в localStorage)
const FAVORITES_STORAGE_KEY = 'product_favorites';

// Загружаем избранное из localStorage при инициализации
const loadFavoritesFromStorage = (): Set<number> => {
    try {
        const stored = localStorage.getItem(FAVORITES_STORAGE_KEY);
        if (stored) {
            const parsed = JSON.parse(stored);
            return new Set(parsed);
        }
    } catch (error) {
        console.error('Error loading favorites:', error);
    }
    return new Set();
};

// Сохраняем избранное в localStorage
const saveFavoritesToStorage = (favs: Set<number>) => {
    try {
        localStorage.setItem(FAVORITES_STORAGE_KEY, JSON.stringify([...favs]));
    } catch (error) {
        console.error('Error saving favorites:', error);
    }
};

const favorites = ref<Set<number>>(loadFavoritesFromStorage());

const toggleFavorite = (accountId: number) => {
    if (favorites.value.has(accountId)) {
        favorites.value.delete(accountId);
    } else {
        favorites.value.add(accountId);
    }
    saveFavoritesToStorage(favorites.value);
};

const isFavorite = (accountId: number) => {
    return favorites.value.has(accountId);
};

// Actions
const addToCart = (account: any) => {
    const quantity = getQuantity(account.id);

    if (!account.quantity || account.quantity === 0) {
        toast.error(t('account.out_of_stock'));
        return;
    }

    if (quantity > account.quantity) {
        toast.error(t('account.detail.available_only', { count: account.quantity }));
        return;
    }

    // Используем цену со скидкой, если она есть
    const priceToUse = account.current_price || account.price;
    productCartStore.addItem(
        {
            ...account,
            price: priceToUse
        },
        quantity
    );

    toast.success(
        t('account.detail.product_added_to_cart', {
            title: (account as any)._cachedTitle || getProductTitle(account),
            quantity: quantity
        })
    );

    // Сбрасываем количество после добавления
    quantities.value[account.id] = 1;
};

const buyNow = (account: any) => {
    const quantity = getQuantity(account.id);

    if (!account.quantity || account.quantity === 0) {
        toast.error(t('account.out_of_stock'));
        return;
    }

    // Очищаем корзину товаров и добавляем только этот товар
    productCartStore.clearCart();
    // Используем цену со скидкой, если она есть
    const priceToUse = account.current_price || account.price;
    productCartStore.addItem(
        {
            ...account,
            price: priceToUse
        },
        quantity
    );

    // Переходим на страницу оформления заказа
    router.push('/checkout');
};

// const truncateText = (text: string, maxLength: number) => {
//     if (!text) return '';
//     const stripped = text.replace(/<[^>]*>/g, '');
//     return stripped.length > maxLength ? stripped.substring(0, maxLength) + '…' : stripped;
// };

const formatPrice = (price: number) => {
    const currency = optionStore.getOption('currency', 'USD');
    const formatter = new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    return formatter.format(price);
};

const formatTotalPrice = (price: number, quantity: number) => {
    const total = price * quantity;
    const currency = optionStore.getOption('currency', 'USD');
    const formatter = new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    return formatter.format(total);
};

onMounted(async () => {
    // Загружаем категории для фильтрации по подкатегориям
    await categoriesStore.fetchAll();
    try {
        // Загружаем только если еще не загружены (предзагрузка в App.vue)
        if (!accountsStore.loaded) {
            await optionStore.fetchData();
            await accountsStore.fetchAll();
        }
    } catch (err) {
        console.error('Error fetching accounts:', err);
    }
});
</script>

<style scoped>
/* Основная карточка товара - КРИТИЧЕСКАЯ ОПТИМИЗАЦИЯ FPS */
.product-card {
    display: grid;
    grid-template-columns: 90px 1fr auto;
    gap: 16px;
    align-items: start;
    /* УБИРАЕМ backdrop-filter в покое - это убивает FPS на слабых ПК */
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: none; /* Убираем блюр в покое */
    border: 1px solid rgba(226, 232, 240, 0.6);
    border-radius: 16px;
    padding: 16px;
    /* Упрощаем тени для производительности */
    transition: transform 0.2s ease, box-shadow 0.2s ease, backdrop-filter 0.2s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04); /* Упрощенная тень */
    position: relative;
    overflow: hidden;
    /* GPU acceleration только для transform */
    will-change: transform;
    transform: translateZ(0);
}

.dark .product-card {
    background: rgba(30, 41, 59, 0.95);
    backdrop-filter: none; /* Убираем блюр в покое */
    border-color: rgba(51, 65, 85, 0.6);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2); /* Упрощенная тень */
}

.dark .product-card:hover {
    backdrop-filter: blur(8px);
    background: rgba(30, 41, 59, 0.9);
}

/* Акцентная линия слева */
.product-card::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 5px;
    background: linear-gradient(180deg, #6c5ce7 0%, #a29bfe 100%);
    opacity: 0;
    transition: opacity 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 16px 0 0 16px;
}

.product-card:hover::before {
    opacity: 1;
}

.product-card:hover {
    transform: translateX(6px) translateY(-2px) translateZ(0);
    /* Включаем блюр только при hover для интерактивности */
    backdrop-filter: blur(8px);
    background: rgba(255, 255, 255, 0.9);
    box-shadow: 0 8px 24px rgba(108, 92, 231, 0.15); /* Упрощенная тень */
    border-color: rgba(108, 92, 231, 0.4);
    will-change: backdrop-filter, transform;
}

/* Убираем will-change после hover для экономии памяти */
.product-card:not(:hover) {
    will-change: auto;
}

.dark .product-card:hover {
    background: rgba(30, 41, 59, 0.95);
    box-shadow: 0 12px 32px rgba(108, 92, 231, 0.3);
    border-color: rgba(108, 92, 231, 0.5);
}

/* Стили для товаров, которые закончились */
.product-card.out-of-stock-card {
    opacity: 0.6;
    background: #f5f5f5;
    border-color: #d1d5db;
    filter: grayscale(0.4);
}

.dark .product-card.out-of-stock-card {
    background: #2d3748;
    border-color: #4a5568;
    opacity: 0.5;
}

.product-card.out-of-stock-card:hover {
    transform: none;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    border-color: #d1d5db;
}

.dark .product-card.out-of-stock-card:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    border-color: #4a5568;
}

.product-card.out-of-stock-card::before {
    display: none;
}

.product-card.out-of-stock-card .product-image {
    filter: grayscale(0.5) brightness(0.9);
}

.product-card.out-of-stock-card .product-title {
    color: #6b7280;
}

.dark .product-card.out-of-stock-card .product-title {
    color: #9ca3af;
}

.product-card.out-of-stock-card .product-description {
    color: #9ca3af;
}

.dark .product-card.out-of-stock-card .product-description {
    color: #6b7280;
}

.product-card.out-of-stock-card .price {
    color: #9ca3af;
}

.dark .product-card.out-of-stock-card .price {
    color: #6b7280;
}

.product-card.out-of-stock-card .price-per-unit {
    color: #9ca3af;
}

.dark .product-card.out-of-stock-card .price-per-unit {
    color: #6b7280;
}

.product-card.out-of-stock-card .btn-cart {
    opacity: 0.5;
    cursor: not-allowed;
    filter: grayscale(0.5);
}

.product-card.out-of-stock-card .btn-secondary {
    opacity: 0.6;
    filter: grayscale(0.3);
}

.product-card.out-of-stock-card .quantity-control {
    opacity: 0.6;
}

.product-card.out-of-stock-card .quantity-btn {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Обертка изображения */
.product-image-wrapper {
    position: relative;
    width: 90px;
    height: 90px;
    border-radius: 12px;
    overflow: hidden;
    background: linear-gradient(135deg, rgba(248, 249, 250, 0.95) 0%, rgba(233, 236, 239, 0.95) 100%);
    backdrop-filter: none; /* Убираем блюр */
    border: 1px solid rgba(226, 232, 240, 0.5);
    flex-shrink: 0;
    transition: transform 0.2s ease;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05); /* Упрощенная тень */
    will-change: transform;
    transform: translateZ(0);
}

.product-image-wrapper.clickable {
    cursor: pointer;
}

.product-image-wrapper.clickable:hover {
    transform: scale(1.08) rotate(2deg);
    box-shadow: 0 8px 20px rgba(108, 92, 231, 0.25);
    border-color: rgba(108, 92, 231, 0.4);
}

.dark .product-image-wrapper {
    background: linear-gradient(135deg, rgba(30, 41, 59, 0.9) 0%, rgba(51, 65, 85, 0.9) 100%);
    border-color: rgba(51, 65, 85, 0.5);
}

.dark .product-image-wrapper.clickable:hover {
    box-shadow: 0 8px 20px rgba(108, 92, 231, 0.35);
    border-color: rgba(108, 92, 231, 0.5);
}

.product-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.product-card:hover .product-image {
    transform: scale(1.1);
}

.product-image-wrapper.clickable:hover .product-image {
    transform: scale(1.2);
}

/* Компактный бейдж со скидкой в правом верхнем углу */
.discount-stripe {
    position: absolute;
    top: 8px;
    right: 8px;
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    padding: 4px 10px;
    text-align: center;
    font-size: 10px;
    font-weight: 700;
    z-index: 10;
    letter-spacing: 0.3px;
    text-transform: uppercase;
    line-height: 1.2;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.35);
    border: 1px solid rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(8px);
}

/* Интеграция бейджа с карточкой */
.product-card.with-discount {
    padding-top: 16px;
}

.dark .discount-stripe {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.9) 0%, rgba(199, 29, 29, 0.9) 100%);
    box-shadow: 0 1px 4px rgba(239, 68, 68, 0.35);
    border-bottom-color: rgba(255, 255, 255, 0.1);
}

/* Контейнер для названия с бейджем */
.title-with-badge {
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Компактный бейдж слева от названия */
.stock-badge-inline {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    padding: 3px 8px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 600;
    flex-shrink: 0;
    transition: all 0.2s ease;
    line-height: 1;
}

/* Зеленый бейдж - товар в наличии */
.stock-badge-inline.in-stock {
    background: rgba(16, 185, 129, 0.12);
    color: #059669;
    border: 1px solid rgba(16, 185, 129, 0.3);
}

/* Красный бейдж - товара нет */
.stock-badge-inline.out-of-stock {
    background: rgba(239, 68, 68, 0.12);
    color: #dc2626;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

/* Dark theme */
.dark .stock-badge-inline.in-stock {
    background: rgba(16, 185, 129, 0.15);
    color: #34d399;
    border-color: rgba(16, 185, 129, 0.4);
}

.dark .stock-badge-inline.out-of-stock {
    background: rgba(239, 68, 68, 0.15);
    color: #f87171;
    border-color: rgba(239, 68, 68, 0.4);
}

/* Информация о товаре */
.product-info {
    display: flex;
    flex-direction: column;
    gap: 6px;
    min-width: 0;
    flex: 1;
    padding-top: 2px;
}

.product-title {
    font-size: 15px;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
    word-break: break-word;
    overflow-wrap: break-word;
    font-family: 'SFT Schrifted Sans', sans-serif;
    max-height: calc(1.3em * 2);
}

.dark .product-title {
    color: #f1f5f9;
}

/* Кликабельное название */
.product-title.clickable-title {
    cursor: pointer;
    transition: color 0.3s ease;
}

.product-title.clickable-title:hover {
    color: #6c5ce7;
}

.dark .product-title.clickable-title:hover {
    color: #a78bfa;
}

.product-description {
    font-size: 13px;
    color: #64748b;
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    word-break: break-word;
    overflow-wrap: break-word;
    margin: 0;
    max-height: 3em;
}

.dark .product-description {
    color: #94a3b8;
}

/* Артикул товара на карточке */
.product-sku {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.125rem 0.5rem;
    background: rgba(99, 102, 241, 0.08);
    backdrop-filter: blur(4px);
    border: 1px solid rgba(99, 102, 241, 0.15);
    border-radius: 0.375rem;
    font-size: 0.625rem;
    font-weight: 600;
    color: #6366f1;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    margin-top: 4px;
    width: fit-content;
}

.dark .product-sku {
    background: rgba(139, 92, 231, 0.1);
    border-color: rgba(139, 92, 231, 0.2);
    color: #a78bfa;
}

.product-sku svg {
    flex-shrink: 0;
    opacity: 0.7;
}

.product-sku--mobile {
  display: none;
}
.product-sku--desktop {
  display: flex;
        justify-content: flex-start;
}

/* Блок действий */
.product-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
    align-items: flex-end;
    min-width: 200px;
    flex-shrink: 0;
    padding-top: 2px;
}

/* Верхняя строка: цена и количество */
.top-actions-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    width: 100%;
}

/* Секция цены */
.price-section {
    text-align: center;
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 2px;
}

.price-wrapper {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 2px;
}

.price-old {
    font-size: 12px;
    color: #9ca3af;
    text-decoration: line-through;
    white-space: nowrap;
}

.dark .price-old {
    color: #6b7280;
}

.price {
    font-size: 20px;
    font-weight: 800;
    background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    line-height: 1;
    display: block;
    font-family: 'SFT Schrifted Sans', sans-serif;
    white-space: nowrap;
    filter: drop-shadow(0 2px 4px rgba(108, 92, 231, 0.2));
    transition: all 0.3s ease;
}

.product-card:hover .price {
    filter: drop-shadow(0 4px 8px rgba(108, 92, 231, 0.3));
}

.price-per-unit {
    font-size: 10px;
    color: #94a3b8;
    margin-top: 2px;
    white-space: nowrap;
}

.dark .price-per-unit {
    color: #64748b;
}

/* Управление количеством - убираем блюр */
.quantity-control {
    display: flex;
    align-items: center;
    background: rgba(248, 250, 252, 0.95);
    backdrop-filter: none; /* Убираем блюр */
    border: 1px solid rgba(226, 232, 240, 0.8);
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04); /* Упрощенная тень */
    margin-left: auto;
    transition: transform 0.2s ease;
}

.quantity-control:hover {
    box-shadow: 0 4px 12px rgba(108, 92, 231, 0.1);
    border-color: rgba(108, 92, 231, 0.3);
}

.dark .quantity-control {
    background: rgba(30, 41, 59, 0.9);
    border-color: rgba(51, 65, 85, 0.8);
}

.quantity-btn {
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

.quantity-btn:hover:not(:disabled) {
    background: #e2e8f0;
    color: #6c5ce7;
}

.dark .quantity-btn:hover:not(:disabled) {
    background: #334155;
    color: #a29bfe;
}

.quantity-btn:disabled {
    cursor: not-allowed;
    opacity: 0.3;
}

.dark .quantity-btn {
    color: #94a3b8;
}

.quantity-input {
    width: 40px;
    border: none;
    background: transparent;
    text-align: center;
    font-weight: 600;
    font-size: 13px;
    color: #1f2937;
    outline: none;
}

.dark .quantity-input {
    color: #f1f5f9;
}

/* Ряд кнопок действий */
.actions-row {
    display: flex;
    gap: 6px;
    width: 100%;
}

/* Вторичные кнопки */
.btn-secondary {
    background: rgba(248, 250, 252, 0.95);
    backdrop-filter: none; /* Убираем блюр */
    border: 1px solid rgba(226, 232, 240, 0.8);
    padding: 9px 15px;
    border-radius: 10px;
    cursor: pointer;
    color: #64748b;
    transition: transform 0.2s ease, background 0.2s ease;
    font-weight: 600;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 5px;
    font-family: 'SFT Schrifted Sans', sans-serif;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04); /* Упрощенная тень */
    will-change: transform;
    transform: translateZ(0);
}

.btn-secondary.btn-icon {
    padding: 8px;
    min-width: 36px;
    justify-content: center;
}

.dark .btn-secondary {
    background: #334155;
    border-color: #475569;
    color: #cbd5e1;
}

.btn-secondary:hover {
    border-color: #6c5ce7;
    color: #6c5ce7;
    background: rgba(108, 92, 231, 0.08);
    transform: translateY(-2px) scale(1.05);
    box-shadow: 0 4px 12px rgba(108, 92, 231, 0.15);
}

.dark .btn-secondary:hover {
    background: rgba(162, 155, 254, 0.15);
    border-color: #a29bfe;
    color: #a29bfe;
    box-shadow: 0 4px 12px rgba(162, 155, 254, 0.2);
}

/* Активная кнопка избранного */
.btn-secondary.active {
    background: #fef2f2;
    border-color: #ef4444;
    color: #ef4444;
}

.dark .btn-secondary.active {
    background: rgba(239, 68, 68, 0.15);
    border-color: #ef4444;
    color: #fca5a5;
}

/* Кнопка корзины - убираем блюр */
.btn-cart {
    background: rgba(248, 250, 252, 0.95);
    backdrop-filter: none; /* Убираем блюр */
    border: 1px solid rgba(226, 232, 240, 0.8);
    padding: 9px;
    border-radius: 10px;
    cursor: pointer;
    color: #64748b;
    transition: transform 0.2s ease, background 0.2s ease;
    font-weight: 600;
    font-size: 13px;
    display: flex;
    will-change: transform;
    transform: translateZ(0);
    align-items: center;
    justify-content: center;
    min-width: 38px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.dark .btn-cart {
    background: rgba(51, 65, 85, 0.9);
    border-color: rgba(71, 85, 105, 0.8);
    color: #cbd5e1;
}

.btn-cart:hover:not(:disabled) {
    border-color: #6c5ce7;
    color: #6c5ce7;
    background: rgba(108, 92, 231, 0.08);
    transform: translateY(-2px) scale(1.05);
    box-shadow: 0 4px 12px rgba(108, 92, 231, 0.15);
}

.dark .btn-cart:hover:not(:disabled) {
    background: rgba(162, 155, 254, 0.15);
    border-color: #a29bfe;
    color: #a29bfe;
    box-shadow: 0 4px 12px rgba(162, 155, 254, 0.2);
}

.btn-cart:disabled {
    cursor: not-allowed;
    opacity: 0.5;
}

/* Основная кнопка */
.btn-primary {
    background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);
    border: none;
    padding: 10px 18px;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 600;
    font-size: 13px;
    color: white;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 14px rgba(108, 92, 231, 0.3);
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    font-family: 'SFT Schrifted Sans', sans-serif;
    position: relative;
    overflow: hidden;
}

.btn-primary::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.5s ease;
}

.btn-primary:hover::before {
    left: 100%;
}

.btn-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 28px rgba(108, 92, 231, 0.4);
    background: linear-gradient(135deg, #5b4dd6 0%, #9285f0 100%);
}

.btn-primary:disabled {
    background: #cbd5e1;
    cursor: not-allowed;
    opacity: 0.6;
    box-shadow: none;
    transform: none;
}

.dark .btn-primary:disabled {
    background: #475569;
}

/* Адаптивность */
@media (max-width: 1024px) {
    .product-card {
        grid-template-columns: 80px 1fr;
        gap: 12px;
    }

    .product-image-wrapper {
        width: 80px;
        height: 80px;
    }

    .product-actions {
        grid-column: 1 / -1;
        flex-direction: column;
        align-items: stretch;
        width: 100%;
        min-width: auto;
    }

    .top-actions-row {
        justify-content: space-between;
    }

    .quantity-control {
        margin-left: 0;
    }

    .actions-row {
        width: 100%;
    }
}

@media (max-width: 768px) {
    .product-card {
        padding: 10px;
        gap: 10px;
    }

    .product-image-wrapper {
        width: 70px;
        height: 70px;
    }

    .product-title {
        font-size: 14px;
    }

    .product-description {
        font-size: 11px;
    }

    .price {
        font-size: 16px;
    }
}

@media (max-width: 640px) {
    .product-card {
        grid-template-columns: 1fr;
        padding: 12px;
        gap: 12px;
    }

    .product-image-wrapper {
        display: none !important;
    }

    .product-info {
        order: 2;
    }

    .product-actions {
        order: 3;
        flex-direction: column;
        align-items: stretch;
    }

    .top-actions-row {
        flex-direction: column;
        gap: 10px;
    }

    .price-section {
        text-align: center;
        width: 100%;
        flex-direction: row-reverse;
    }

    .quantity-control {
        width: 100%;
        justify-content: center;
        margin-left: 0;
    }

    .actions-row {
        flex-direction: row-reverse;
        gap: 10px;
    }

    .btn-secondary.btn-icon {
        padding: 12px;
        width: initial;
    }

    .btn-primary {
        width: 100%;
    }
      .product-sku--mobile {
display: flex;
        justify-content: flex-start;
        width: 100%;     /* выравнивание по правому краю в price-section */
  }
  .product-sku--desktop {
    display: none;
  }
}

/* Skeleton Loaders */
.product-card-skeleton {
    display: grid;
    grid-template-columns: 90px 1fr auto;
    gap: 14px;
    align-items: start;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 14px;
    min-height: 120px;
}

.dark .product-card-skeleton {
    background: #1f2937;
    border-color: #374151;
}

.skeleton-image {
    width: 90px;
    height: 90px;
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: skeleton-loading 1.5s infinite;
    border-radius: 8px;
}

.dark .skeleton-image {
    background: linear-gradient(90deg, #374151 25%, #4b5563 50%, #374151 75%);
    background-size: 200% 100%;
}

.skeleton-title {
    height: 20px;
    width: 70%;
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: skeleton-loading 1.5s infinite;
    border-radius: 4px;
}

.dark .skeleton-title {
    background: linear-gradient(90deg, #374151 25%, #4b5563 50%, #374151 75%);
    background-size: 200% 100%;
}

.skeleton-text {
    height: 14px;
    width: 100%;
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: skeleton-loading 1.5s infinite;
    border-radius: 4px;
}

.dark .skeleton-text {
    background: linear-gradient(90deg, #374151 25%, #4b5563 50%, #374151 75%);
    background-size: 200% 100%;
}

.skeleton-actions {
    width: 200px;
    height: 100px;
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: skeleton-loading 1.5s infinite;
    border-radius: 8px;
}

.dark .skeleton-actions {
    background: linear-gradient(90deg, #374151 25%, #4b5563 50%, #374151 75%);
    background-size: 200% 100%;
}

@keyframes skeleton-loading {
    0% {
        background-position: 200% 0;
    }
    100% {
        background-position: -200% 0;
    }
}
</style>
