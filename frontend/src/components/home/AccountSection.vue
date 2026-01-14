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
            <p class="text-gray-500 dark:text-gray-400 text-lg mb-6">{{ $t('account.no_accounts') }}</p>
            
            <button 
                @click="retryFetch" 
                class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors duration-200 flex items-center gap-2 mx-auto"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Попробовать еще раз
            </button>
        </div>

        <!-- Products Grid -->
        <div v-else class="space-y-5">
            <ProductCard
                v-for="(account, index) in enrichedDisplayedAccounts"
                :key="account.id"
                v-memo="[account.id, account.quantity, account.has_discount, (account as any)._discountPercentRounded, (account as any)._cachedTitle, (account as any)._cachedQuantity, (account as any)._isFavorite]"
                :product="account"
                :index="index"
                :quantity="(account as any)._cachedQuantity"
                :is-favorite="(account as any)._isFavorite"
                :cached-title="(account as any)._cachedTitle"
                :cached-description="(account as any)._cachedDescription"
                :formatted-price="(account as any)._formattedPrice"
                :formatted-total-price="(account as any)._formattedTotalPrice"
                :discount-percent-rounded="(account as any)._discountPercentRounded"
                @increase-quantity="increaseQuantity"
                @decrease-quantity="decreaseQuantity"
                @add-to-cart="addToCart"
                @buy-now="buyNow"
                @toggle-favorite="toggleFavorite"
            />
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
import { computed, onMounted, ref, watch } from 'vue';
import { useAccountsStore } from '@/stores/accounts';
import { useProductCartStore } from '@/stores/productCart';
import { useI18n } from 'vue-i18n';
import { useOptionStore } from '@/stores/options';
import { useToast } from 'vue-toastification';
import { useRouter } from 'vue-router';
import { useProductTitle } from '@/composables/useProductTitle';
import { useProductCategoriesStore } from '@/stores/productCategories';
import ProductCard from '@/components/products/ProductCard.vue';

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

// КРИТИЧЕСКАЯ ОПТИМИЗАЦИЯ: Мемоизация accounts computed
// Кэш для accounts с мемоизацией по locale и списку
const accountsCache = ref<{
    locale: string;
    listLength: number;
    data: any[];
} | null>(null);

// КРИТИЧЕСКАЯ ОПТИМИЗАЦИЯ: Предвычисляем ВСЕ значения для всех товаров
// Это избавляет от множественных вызовов функций при рендеринге
const accounts = computed(() => {
    const currentLocale = locale.value;
    const listLength = accountsStore.list.length;
    const currency = optionStore.getOption('currency', 'USD');
    
    // Проверяем кэш - если локаль, длина списка и валюта не изменились, возвращаем кэш
    if (accountsCache.value && 
        accountsCache.value.locale === currentLocale && 
        accountsCache.value.listLength === listLength &&
        accountsCache.value.currency === currency) {
        return accountsCache.value.data;
    }
    
    // Создаем форматтер один раз для всех цен
    const priceFormatter = new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    
    // Создаем новый массив только если изменилась локаль, список или валюта
    // КРИТИЧЕСКАЯ ОПТИМИЗАЦИЯ: Используем Object.assign вместо spread для лучшей производительности
    const data = accountsStore.list.map(account => {
        const cached = Object.assign({}, account);
        // Предвычисляем все значения один раз
        cached._cachedTitle = getProductTitle(account);
        cached._cachedDescription = getProductDescription(account);
        cached._discountPercentRounded = account.discount_percent 
            ? Math.round(account.discount_percent) 
            : 0;
        // Предвычисляем форматированную цену
        const priceToFormat = account.current_price || account.price;
        cached._formattedPrice = priceFormatter.format(priceToFormat);
        return cached;
    });
    
    // Сохраняем в кэш
    accountsCache.value = {
        locale: currentLocale,
        listLength,
        currency,
        data
    };
    
    return data;
});

// КРИТИЧЕСКАЯ ОПТИМИЗАЦИЯ: Обогащаем displayedAccounts предвычисленными quantity, isFavorite и ценами
// Это избавляет от множественных вызовов getQuantity(), isFavorite(), formatPrice() и formatTotalPrice() в шаблоне
const enrichedDisplayedAccounts = computed(() => {
    const currency = optionStore.getOption('currency', 'USD');
    const priceFormatter = new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    
    return displayedAccounts.value.map(account => {
        const enriched = Object.assign({}, account);
        // Предвычисляем quantity для каждого товара
        const quantity = quantities.value[account.id] || 1;
        enriched._cachedQuantity = quantity;
        // Предвычисляем isFavorite для каждого товара
        enriched._isFavorite = favorites.value.has(account.id);
        
        // Предвычисляем форматированные цены
        const price = account.current_price || account.price;
        enriched._formattedPrice = priceFormatter.format(price);
        enriched._formattedTotalPrice = priceFormatter.format(price * quantity);
        
        // Сохраняем delivery_type для использования в шаблоне
        enriched.delivery_type = account.delivery_type || 'automatic';
        
        return enriched;
    });
});

// КРИТИЧЕСКАЯ ОПТИМИЗАЦИЯ: Объединение всех фильтров в один проход
// Вместо множественных filter операций делаем один проход
// Кэш для категорий (избегаем повторных find операций)
const categoryCache = ref<Map<number, Set<number>>>(new Map());

// КРИТИЧЕСКАЯ ОПТИМИЗАЦИЯ: Используем Set для favorites для O(1) проверки
const favoritesSet = computed(() => favorites.value);

const filteredAccounts = computed(() => {
    const filters = props.filters;
    const accountsList = accounts.value;
    
    // Ранний выход если нет фильтров или все фильтры пустые
    if (!filters || 
        (filters.categoryId === null && 
         filters.subcategoryId === null && 
         !filters.hideOutOfStock && 
         !filters.showFavoritesOnly && 
         !filters.searchQuery)) {
        return accountsList;
    }
    
    // Подготовка данных для фильтрации один раз
    const categoryId = filters.categoryId;
    const subcategoryId = filters.subcategoryId;
    const hideOutOfStock = filters.hideOutOfStock;
    const showFavoritesOnly = filters.showFavoritesOnly;
    const searchQuery = filters.searchQuery?.toLowerCase().trim();
    
    // КРИТИЧЕСКАЯ ОПТИМИЗАЦИЯ: Кэшируем categoryIds для избежания повторных find
    let categoryIds: Set<number> | null = null;
    if (categoryId !== null && categoryId !== undefined && subcategoryId === null) {
        // Проверяем кэш категорий
        if (categoryCache.value.has(categoryId)) {
            categoryIds = categoryCache.value.get(categoryId)!;
        } else {
            const category = categoriesStore.list.find(cat => cat.id === categoryId);
            const subcategoryIds = category?.subcategories?.map(sub => sub.id) || [];
            categoryIds = new Set([categoryId, ...subcategoryIds]);
            categoryCache.value.set(categoryId, categoryIds);
        }
    }
    
    // Подготовка поискового запроса один раз (только если есть поиск)
    const queryWords = searchQuery 
        ? searchQuery.split(/\s+/).filter(w => w.length > 0)
        : null;
    
    // Используем Set для быстрой проверки избранного
    const favoritesSetValue = favoritesSet.value;
    
    // ОДИН проход по массиву вместо множественных filter
    return accountsList.filter(account => {
        // Фильтр по подкатегории (самый селективный - проверяем первым)
        if (subcategoryId !== null && subcategoryId !== undefined) {
            if (account.category?.id !== subcategoryId) return false;
        } 
        // Фильтр по категории (включая подкатегории)
        else if (categoryId !== null && categoryId !== undefined && categoryIds) {
            const accountCategoryId = account.category?.id;
            if (!accountCategoryId || !categoryIds.has(accountCategoryId)) return false;
        }
        
        // Фильтр по наличию (быстрая проверка)
        if (hideOutOfStock && !isInStock(account)) {
            return false;
        }
        
        // Фильтр по избранному (O(1) проверка через Set)
        if (showFavoritesOnly && !favoritesSetValue.has(account.id)) {
            return false;
        }
        
        // Фильтр по поиску (самый тяжелый - проверяем последним)
        if (queryWords && queryWords.length > 0) {
            const title = ((account as any)._cachedTitle || '').toLowerCase();
            const description = ((account as any)._cachedDescription || '').toLowerCase();
            const sku = (account.sku || '').toLowerCase();
            
            const matches = queryWords.every(word => 
                title.includes(word) || description.includes(word) || sku.includes(word)
            );
            if (!matches) return false;
        }
        
        return true;
    });
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

const retryFetch = async () => {
    try {
        accountsStore.loaded = false;
        await accountsStore.fetchAll(true); // true для форсированного обновления
        console.log('[AccountSection] Повторная загрузка выполнена:', accountsStore.list.length);
    } catch (err) {
        console.error('[AccountSection] Ошибка при повторной загрузке:', err);
    }
};

// Оптимизация для больших списков через content-visibility CSS
// Это позволяет браузеру пропускать рендеринг невидимых элементов

// КРИТИЧЕСКАЯ ОПТИМИЗАЦИЯ: Объединяем все watch на фильтры в один
// Это избегает множественных пересчетов и улучшает производительность
watch(
    () => [
        props.filters?.categoryId,
        props.filters?.subcategoryId,
        props.filters?.searchQuery,
        props.filters?.hideOutOfStock,
        props.filters?.showFavoritesOnly
    ],
    () => {
        currentPage.value = 1;
    }
);

// Quantity management
const quantities = ref<Record<number, number>>({});

const getQuantity = (accountId: number) => {
    return quantities.value[accountId] || 1;
};

const increaseQuantity = (accountId: number) => {
    const current = quantities.value[accountId] || 1;
    quantities.value[accountId] = current + 1;
    // Принудительно обновляем enrichedDisplayedAccounts через изменение ref
    quantities.value = { ...quantities.value };
};

const decreaseQuantity = (accountId: number) => {
    const current = quantities.value[accountId] || 1;
    if (current > 1) {
        quantities.value[accountId] = current - 1;
        // Принудительно обновляем enrichedDisplayedAccounts через изменение ref
        quantities.value = { ...quantities.value };
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
    // Создаем новый Set для реактивности
    favorites.value = new Set(favorites.value);
    saveFavoritesToStorage(favorites.value);
};

const isFavorite = (accountId: number) => {
    return favorites.value.has(accountId);
};

// Actions
const addToCart = (account: any) => {
    // КРИТИЧЕСКАЯ ОПТИМИЗАЦИЯ: Используем предвычисленное quantity
    const quantity = (account as any)._cachedQuantity || getQuantity(account.id);

    if (!isInStock(account)) {
        toast.error(t('account.out_of_stock'));
        return;
    }

    // Для товаров с ручной выдачей (quantity >= 999) нет ограничения на количество
    if (account.quantity < 999 && quantity > account.quantity) {
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
    // Принудительно обновляем enrichedDisplayedAccounts
    quantities.value = { ...quantities.value };
};

const buyNow = (account: any) => {
    // КРИТИЧЕСКАЯ ОПТИМИЗАЦИЯ: Используем предвычисленное quantity
    const quantity = (account as any)._cachedQuantity || getQuantity(account.id);

    if (!isInStock(account)) {
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

// Функции для отображения наличия товара
const isInStock = (account: any): boolean => {
    // Для товаров с ручной выдачей (quantity = 999) считаем, что товар в наличии
    // если он активен (is_active) или quantity > 0
    return account.quantity > 0;
};

const formatStockQuantity = (account: any): string => {
    // Для товаров с ручной выдачей, когда quantity = 999, показываем "В наличии"
    if (account.quantity >= 999) {
        return 'В наличии';
    }
    // Для обычных товаров показываем количество
    return account.quantity > 0 ? account.quantity.toString() : '0';
};

// Функции для отображения способа выдачи товара
const getDeliveryTypeLabel = (account: any): string => {
    const deliveryType = account.delivery_type || 'automatic';
    try {
        if (deliveryType === 'manual') {
            return t('account.delivery.manual') || 'Ручная выдача';
        }
        return t('account.delivery.automatic') || 'Авто-выдача';
    } catch (e) {
        // Fallback если переводы не загружены
        return deliveryType === 'manual' ? 'Ручная выдача' : 'Авто-выдача';
    }
};

const getDeliveryTypeText = (account: any): string => {
    const deliveryType = account.delivery_type || 'automatic';
    try {
        if (deliveryType === 'manual') {
            return t('account.delivery.manual_description') || 'Товар выдается менеджером вручную после обработки заказа';
        }
        return t('account.delivery.automatic_description') || 'Товар выдается автоматически сразу после оплаты';
    } catch (e) {
        // Fallback если переводы не загружены
        return deliveryType === 'manual' 
            ? 'Товар выдается менеджером вручную после обработки заказа'
            : 'Товар выдается автоматически сразу после оплаты';
    }
};

// const truncateText = (text: string, maxLength: number) => {
//     if (!text) return '';
//     const stripped = text.replace(/<[^>]*>/g, '');
//     return stripped.length > maxLength ? stripped.substring(0, maxLength) + '…' : stripped;
// };

// КРИТИЧЕСКАЯ ОПТИМИЗАЦИЯ: Мемоизация formatPrice
// Кэш для форматированных цен
const priceFormatterCache = ref<Map<string, string>>(new Map());

const formatPrice = (price: number) => {
    const currency = optionStore.getOption('currency', 'USD');
    const cacheKey = `${price}_${currency}`;
    
    // Проверяем кэш
    if (priceFormatterCache.value.has(cacheKey)) {
        return priceFormatterCache.value.get(cacheKey)!;
    }
    
    // Форматируем и кэшируем
    const formatter = new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    const formatted = formatter.format(price);
    priceFormatterCache.value.set(cacheKey, formatted);
    return formatted;
};

// КРИТИЧЕСКАЯ ОПТИМИЗАЦИЯ: Мемоизация formatTotalPrice
const formatTotalPrice = (price: number, quantity: number) => {
    const total = price * quantity;
    const currency = optionStore.getOption('currency', 'USD');
    const cacheKey = `${total}_${currency}`;
    
    // Проверяем кэш
    if (priceFormatterCache.value.has(cacheKey)) {
        return priceFormatterCache.value.get(cacheKey)!;
    }
    
    // Форматируем и кэшируем
    const formatter = new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    const formatted = formatter.format(total);
    priceFormatterCache.value.set(cacheKey, formatted);
    return formatted;
};

onMounted(async () => {
    // Загружаем категории для фильтрации по подкатегориям
    await categoriesStore.fetchAll();
    try {
        // Загружаем только если еще не загружены (предзагрузка в App.vue)
        const wasLoaded = accountsStore.loaded;
        console.log('[AccountSection] Состояние загрузки товаров перед проверкой:', { loaded: wasLoaded, count: accountsStore.list.length });
        
        if (!accountsStore.loaded) {
            await optionStore.fetchData();
            await accountsStore.fetchAll();
        }
        
        // Логируем успешную загрузку с количеством товаров
        console.log('[AccountSection] Товары загружены:', {
            count: accountsStore.list.length,
            loaded: accountsStore.loaded,
            wasPreloaded: wasLoaded
        });
    } catch (err: any) {
        // Улучшенное логирование ошибок с деталями
        const errorDetails = {
            status: err?.response?.status,
            statusText: err?.response?.statusText,
            message: err?.message,
            url: err?.config?.url,
            responseData: err?.response?.data
        };
        console.error('[AccountSection] Ошибка загрузки товаров:', errorDetails);
        console.error('[AccountSection] Полная ошибка:', err);
    }
});
</script>

<style scoped>
/* Grid container styles */
.space-y-5 > :deep(.product-card) {
    /* Ensure cards in the list have proper spacing if needed, 
       though space-y-5 handles it */
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
