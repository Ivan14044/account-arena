<template>
    <div v-if="products.length > 0" class="similar-products-section">
        <h2 class="section-title">{{ $t('products.similar.title') }}</h2>
        <div class="products-list">
            <ProductCard
                v-for="product in products"
                :key="product.id"
                :product="product"
                :quantity="getQuantity(product.id)"
                :is-favorite="isFavorite(product.id)"
                @increase-quantity="increaseQuantity"
                @decrease-quantity="decreaseQuantity"
                @add-to-cart="addToCart"
                @buy-now="buyNow"
                @toggle-favorite="toggleFavorite"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useAccountsStore, type AccountItem } from '@/stores/accounts';
import { useProductTitle } from '@/composables/useProductTitle';
import { useOptionStore } from '@/stores/options';
import { useProductCartStore } from '@/stores/productCart';
import { useToast } from 'vue-toastification';
import ProductCard from '@/components/products/ProductCard.vue';

const props = defineProps<{
    productId: string | number;
}>();

const router = useRouter();
const accountsStore = useAccountsStore();
const productCartStore = useProductCartStore();
const optionStore = useOptionStore();
const { getProductTitle, getProductDescription } = useProductTitle();
const { t } = useI18n();
const toast = useToast();
const products = ref<AccountItem[]>([]);

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

const isFavorite = (accountId: number) => {
    return favorites.value.has(accountId);
};

// Actions
const addToCart = (account: any) => {
    const quantity = getQuantity(account.id);

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
            title: getProductTitle(account),
            quantity: quantity
        })
    );

    quantities.value[account.id] = 1;
};

const buyNow = (account: any) => {
    const quantity = getQuantity(account.id);

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

const formatPrice = (price: number) => {
    const currency = optionStore.getOption('currency', 'USD');
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(price);
};

const goToProduct = (product: AccountItem) => {
    router.push(`/account/${product.sku || product.id}`);
};

// Функции для отображения наличия товара
const isInStock = (product: AccountItem): boolean => {
    return product.quantity > 0;
};

const formatStockQuantity = (product: AccountItem): string => {
    if (product.quantity >= 999) {
        return 'В наличии';
    }
    return product.quantity > 0 ? product.quantity.toString() : '0';
};

const getDeliveryTypeLabel = (product: AccountItem): string => {
    const deliveryType = product.delivery_type || 'automatic';
    if (deliveryType === 'manual') {
        return t('account.delivery.manual');
    }
    return t('account.delivery.automatic');
};

const getDeliveryTypeText = (product: AccountItem): string => {
    const deliveryType = product.delivery_type || 'automatic';
    if (deliveryType === 'manual') {
        return t('account.delivery.manual_description');
    }
    return t('account.delivery.automatic_description');
};

onMounted(async () => {
    try {
        products.value = await accountsStore.fetchSimilar(props.productId);
    } catch (error) {
        console.error('[SimilarProducts] Ошибка загрузки похожих товаров:', error);
    }
});
</script>

<style scoped>
.similar-products-section {
    margin-top: 3rem;
    padding-top: 2rem;
    border-top: 1px solid rgba(0, 0, 0, 0.1);
}

.dark .similar-products-section {
    border-top-color: rgba(255, 255, 255, 0.1);
}

.section-title {
    font-size: 1.75rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    color: #1f2937;
}

.dark .section-title {
    color: #f9fafb;
}

.products-list {
    display: flex;
    flex-direction: column;
    gap: 14px;
}
</style>
