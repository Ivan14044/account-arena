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

// Кэшируем форматтеры ГЛОБАЛЬНО
const globalPriceFormatters = new Map<string, Intl.NumberFormat>();

const getGlobalPriceFormatter = (currency: string) => {
    if (!globalPriceFormatters.has(currency)) {
        globalPriceFormatters.set(
            currency,
            new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: currency,
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            })
        );
    }
    return globalPriceFormatters.get(currency)!;
};

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
interface AccountsCache {
    locale: string;
    listLength: number;
    currency: string;
    data: any[];
}
const accountsCache = ref<AccountsCache | null>(null);

// КРИТИЧЕСКАЯ ОПТИМИЗАЦИЯ: Предвычисляем ВСЕ значения для всех товаров
const accounts = computed(() => {
    const currentLocale = locale.value;
    const listLength = accountsStore.list.length;
    const currency = optionStore.getOption('currency', 'USD');
    
    // Проверяем кэш
    if (accountsCache.value && 
        accountsCache.value.locale === currentLocale && 
        accountsCache.value.listLength === listLength &&
        accountsCache.value.currency === currency) {
        return accountsCache.value.data;
    }
    
    const priceFormatter = getGlobalPriceFormatter(currency);
    
    const data = accountsStore.list.map(account => {
        const cached: any = Object.assign({}, account);
        cached._cachedTitle = getProductTitle(account);
        cached._cachedDescription = getProductDescription(account);
        cached._discountPercentRounded = account.discount_percent 
            ? Math.round(account.discount_percent) 
            : 0;
        const priceToFormat = account.current_price || account.price;
        cached._formattedPrice = priceFormatter.format(priceToFormat);
        return cached;
    });
    
    accountsCache.value = {
        locale: currentLocale,
        listLength,
        currency,
        data
    };
    
    return data;
});

// КРИТИЧЕСКАЯ ОПТИМИЗАЦИЯ: Обогащаем displayedAccounts предвычисленными quantity, isFavorite и ценами
const enrichedDisplayedAccounts = computed(() => {
    const currency = optionStore.getOption('currency', 'USD');
    const priceFormatter = getGlobalPriceFormatter(currency);
    const favoritesSetValue = favorites.value;
    const quantitiesValue = quantities.value;
    
    return displayedAccounts.value.map(account => {
        const enriched: any = Object.assign({}, account);
        const quantity = quantitiesValue[account.id] || 1;
        enriched._cachedQuantity = quantity;
        enriched._isFavorite = favoritesSetValue.has(account.id);
        
        const price = account.current_price || account.price;
        enriched._formattedTotalPrice = priceFormatter.format(price * quantity);
        enriched.delivery_type = account.delivery_type || 'automatic';
        
        return enriched;
    });
});

// КРИТИЧЕСКАЯ ОПТИМИЗАЦИЯ: Объединение всех фильтров в один проход
const categoryCache = ref<Map<number, Set<number>>>(new Map());

const favoritesSet = computed(() => favorites.value);

const filteredAccounts = computed(() => {
    const filters = props.filters;
    const accountsList = accounts.value;
    
    if (!filters || 
        (filters.categoryId === null && 
         filters.subcategoryId === null && 
         !filters.hideOutOfStock && 
         !filters.showFavoritesOnly && 
         !filters.searchQuery)) {
        return accountsList;
    }
    
    const categoryId = filters.categoryId;
    const subcategoryId = filters.subcategoryId;
    const hideOutOfStock = filters.hideOutOfStock;
    const showFavoritesOnly = filters.showFavoritesOnly;
    const searchQuery = filters.searchQuery?.toLowerCase().trim();
    
    let categoryIds: Set<number> | null = null;
    if (categoryId !== null && categoryId !== undefined && subcategoryId === null) {
        if (categoryCache.value.has(categoryId)) {
            categoryIds = categoryCache.value.get(categoryId)!;
        } else {
            const category = categoriesStore.list.find(cat => cat.id === categoryId);
            const subcategoryIds = category?.subcategories?.map(sub => sub.id) || [];
            categoryIds = new Set([categoryId, ...subcategoryIds]);
            categoryCache.value.set(categoryId, categoryIds);
        }
    }
    
    const queryWords = searchQuery 
        ? searchQuery.split(/\s+/).filter(w => w.length > 0)
        : null;
    
    const favoritesSetValue = favoritesSet.value;
    
    return accountsList.filter(account => {
        if (subcategoryId !== null && subcategoryId !== undefined) {
            if (account.category?.id !== subcategoryId) return false;
        } 
        else if (categoryId !== null && categoryId !== undefined && categoryIds) {
            const accountCategoryId = account.category?.id;
            if (!accountCategoryId || !categoryIds.has(accountCategoryId)) return false;
        }
        
        if (hideOutOfStock && account.quantity <= 0) {
            return false;
        }
        
        if (showFavoritesOnly && !favoritesSetValue.has(account.id)) {
            return false;
        }
        
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

const quantities = ref<Record<number, number>>({});

const increaseQuantity = (accountId: number) => {
    const current = quantities.value[accountId] || 1;
    quantities.value[accountId] = current + 1;
    quantities.value = { ...quantities.value };
};

const decreaseQuantity = (accountId: number) => {
    const current = quantities.value[accountId] || 1;
    if (current > 1) {
        quantities.value[accountId] = current - 1;
        quantities.value = { ...quantities.value };
    }
};

const FAVORITES_STORAGE_KEY = 'product_favorites';

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
    favorites.value = new Set(favorites.value);
    saveFavoritesToStorage(favorites.value);
};

// Actions
const addToCart = (account: any) => {
    const quantity = (account as any)._cachedQuantity || 1;

    if (account.quantity <= 0) {
        toast.error(t('account.out_of_stock'));
        return;
    }

    if (account.quantity < 999 && quantity > account.quantity) {
        toast.error(t('account.detail.available_only', { count: account.quantity }));
        return;
    }

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

    quantities.value[account.id] = 1;
    quantities.value = { ...quantities.value };
};

const buyNow = (account: any) => {
    const quantity = (account as any)._cachedQuantity || 1;

    if (account.quantity <= 0) {
        toast.error(t('account.out_of_stock'));
        return;
    }

    productCartStore.clearCart();
    const priceToUse = account.current_price || account.price;
    productCartStore.addItem(
        {
            ...account,
            price: priceToUse
        },
        quantity
    );

    router.push('/checkout');
};

onMounted(async () => {
    await categoriesStore.fetchAll();
    try {
        if (!accountsStore.loaded) {
            await optionStore.fetchData();
            await accountsStore.fetchAll();
        }
    } catch (err: any) {
        console.error('[AccountSection] Ошибка загрузки товаров:', err);
    }
});
</script>

<style scoped>
.space-y-5 {
    content-visibility: auto;
    contain-intrinsic-size: 100px 1000px;
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
