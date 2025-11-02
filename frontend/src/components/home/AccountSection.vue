<template>
    <div>
        <div class="space-y-5">
            <div
                v-for="account in displayedAccounts"
                :key="account.id"
                class="product-card"
            >
                <!-- Left: Product Image -->
                <div class="product-image-wrapper">
                    <img
                        :src="account.image_url || '/img/no-logo.png'"
                        :alt="account.title"
                        class="product-image"
                    />
                </div>

                <!-- Center: Product Info -->
                <div class="product-info">
                    <h3 class="product-title">{{ account.title }}</h3>
                    <p v-if="account.description" class="product-description">
                        {{ truncateText(account.description, 150) }}
                    </p>
                </div>

                <!-- Right: Actions -->
                <div class="product-actions">
                    <div class="quantity-control">
                        <button 
                            class="quantity-btn"
                            @click="decreaseQuantity(account.id)"
                            :disabled="getQuantity(account.id) <= 1"
                        >−</button>
                        <input 
                            type="text" 
                            :value="getQuantity(account.id)" 
                            readonly 
                            class="quantity-input"
                        />
                        <button 
                            class="quantity-btn"
                            @click="increaseQuantity(account.id)"
                            :disabled="getQuantity(account.id) >= (account.quantity || 1)"
                        >+</button>
                    </div>
                    
                    <div class="action-row">
                        <button 
                            class="info-btn"
                            @click="$router.push(`/account/${account.id}`)"
                            title="Подробнее"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </button>
                        
                        <div class="price">{{ formatPrice(account.price) }}</div>
                        
                        <button 
                            class="favorite-btn"
                            @click="toggleFavorite(account.id)"
                            :class="{ 'active': isFavorite(account.id) }"
                            title="Добавить в избранное"
                        >
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="button-row">
                        <button class="btn-cart" @click="addToCart(account)">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            Корзина
                        </button>
                        <button
                            class="btn-buy"
                            @click="buyNow(account)"
                            :disabled="!account.quantity || account.quantity === 0"
                        >
                            Купить
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="filteredAccounts.length === 0" class="text-center py-12">
                <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                </svg>
                <p class="text-gray-500 dark:text-gray-400 text-lg">
                    {{ $t('account.no_accounts') }}
                </p>
            </div>
        </div>
        
        <div v-if="filteredAccounts.length > 6 && !showAll" class="text-center mt-8">
            <button
                @click="showAll = true"
                class="px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg transition-colors"
            >
                {{ $t('account.show_all') }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { useAccountsStore } from '@/stores/accounts';
import { useI18n } from 'vue-i18n';
import { useOptionStore } from '@/stores/options';

interface FilterProps {
    categoryId?: number | null;
    hideOutOfStock?: boolean;
    showFavoritesOnly?: boolean;
    searchQuery?: string;
}

const props = defineProps<{
    filters?: FilterProps;
}>();

const accountsStore = useAccountsStore();
const { t } = useI18n();
const optionStore = useOptionStore();
const showAll = ref(false);

const accounts = computed(() => accountsStore.list);

const filteredAccounts = computed(() => {
    let result = [...accounts.value];
    
    // Filter by category
    if (props.filters?.categoryId !== null && props.filters?.categoryId !== undefined) {
        result = result.filter(account => account.category?.id === props.filters?.categoryId);
    }
    
    // Filter out of stock
    if (props.filters?.hideOutOfStock) {
        result = result.filter(account => account.quantity && account.quantity > 0);
    }
    
    // Filter favorites (placeholder - can be extended with favorites store)
    if (props.filters?.showFavoritesOnly) {
        // For now, just return empty array or implement favorites logic
        result = [];
    }
    
    // Search filter
    if (props.filters?.searchQuery && props.filters.searchQuery.trim()) {
        const query = props.filters.searchQuery.toLowerCase().trim();
        result = result.filter(account => {
            const title = (account.title || '').toLowerCase();
            const description = (account.description || '').toLowerCase();
            return title.includes(query) || description.includes(query);
        });
    }
    
    return result;
});

const displayedAccounts = computed(() => (showAll.value ? filteredAccounts.value : filteredAccounts.value.slice(0, 6)));

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

// Favorites management
const favorites = ref<Set<number>>(new Set());

const toggleFavorite = (accountId: number) => {
    if (favorites.value.has(accountId)) {
        favorites.value.delete(accountId);
    } else {
        favorites.value.add(accountId);
    }
};

const isFavorite = (accountId: number) => {
    return favorites.value.has(accountId);
};

// Actions
const addToCart = (account: any) => {
    // TODO: Implement cart functionality
    console.log('Add to cart:', account);
};

const buyNow = (account: any) => {
    // TODO: Implement buy now functionality
    console.log('Buy now:', account);
};

const truncateText = (text: string, maxLength: number) => {
    if (!text) return '';
    const stripped = text.replace(/<[^>]*>/g, '');
    return stripped.length > maxLength ? stripped.substring(0, maxLength) + '…' : stripped;
};

const formatPrice = (price: number) => {
    const currency = optionStore.getOption('currency', 'USD');
    const formatter = new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
    return formatter.format(price);
};

onMounted(async () => {
  try {
    await optionStore.fetchData();
    await accountsStore.fetchAll();
  } catch (err) {
    console.error('Error fetching accounts:', err);
  }
});
</script>

<style scoped>
.product-card {
  display: flex;
  align-items: center;
  gap: 20px;
  background: rgba(255, 255, 255, 0.6);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.3);
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  padding: 20px;
  transition: all 0.3s ease;
}

.dark .product-card {
  background: rgba(31, 41, 55, 0.6);
  border-color: rgba(75, 85, 99, 0.3);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.product-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.dark .product-card:hover {
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.4);
}

.product-image-wrapper {
  flex-shrink: 0;
}

.product-image {
  width: 80px;
  height: 80px;
  border-radius: 12px;
  object-fit: cover;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.product-info {
  flex: 1;
  min-width: 0;
}

.product-title {
  font-size: 18px;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 8px;
  font-family: 'SFT Schrifted Sans', sans-serif;
}

.dark .product-title {
  color: #ffffff;
}

.product-description {
  font-size: 14px;
  color: #6b7280;
  line-height: 1.5;
  margin: 0;
}

.dark .product-description {
  color: #9ca3af;
}

.product-actions {
  display: flex;
  flex-direction: column;
  gap: 12px;
  align-items: flex-end;
  flex-shrink: 0;
}

.quantity-control {
  display: flex;
  align-items: center;
  background: #f3f4f6;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.dark .quantity-control {
  background: #374151;
}

.quantity-btn {
  border: none;
  background: transparent;
  font-size: 20px;
  font-weight: 600;
  color: #6b7280;
  cursor: pointer;
  padding: 8px 12px;
  transition: all 0.2s ease;
}

.quantity-btn:hover:not(:disabled) {
  background: #e5e7eb;
  color: #6c5ce7;
}

.dark .quantity-btn:hover:not(:disabled) {
  background: #4b5563;
  color: #a29bfe;
}

.quantity-btn:disabled {
  cursor: not-allowed;
  opacity: 0.4;
}

.dark .quantity-btn {
  color: #9ca3af;
}

.quantity-input {
  width: 50px;
  border: none;
  background: transparent;
  text-align: center;
  font-weight: 600;
  font-size: 16px;
  color: #1f2937;
}

.dark .quantity-input {
  color: #ffffff;
}

.action-row {
  display: flex;
  align-items: center;
  gap: 12px;
}

.info-btn,
.favorite-btn {
  border: none;
  background: #f3f4f6;
  padding: 8px;
  border-radius: 10px;
  cursor: pointer;
  color: #6b7280;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.dark .info-btn,
.dark .favorite-btn {
  background: #374151;
  color: #9ca3af;
}

.info-btn:hover {
  background: #e5e7eb;
  color: #6c5ce7;
}

.dark .info-btn:hover {
  background: #4b5563;
  color: #a29bfe;
}

.favorite-btn:hover,
.favorite-btn.active {
  background: #fef2f2;
  color: #ef4444;
}

.dark .favorite-btn:hover,
.dark .favorite-btn.active {
  background: #450a0a;
  color: #fca5a5;
}

.price {
  font-size: 20px;
  font-weight: 700;
  color: #6c5ce7;
  font-family: 'SFT Schrifted Sans', sans-serif;
}

.dark .price {
  color: #a29bfe;
}

.button-row {
  display: flex;
  gap: 8px;
  width: 100%;
}

.btn-cart {
  flex: 1;
  background: #f3f4f6;
  border: 1px solid #e5e7eb;
  padding: 10px 16px;
  border-radius: 12px;
  cursor: pointer;
  font-weight: 600;
  font-size: 14px;
  color: #374151;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-family: 'SFT Schrifted Sans', sans-serif;
}

.dark .btn-cart {
  background: #374151;
  border-color: #4b5563;
  color: #e5e7eb;
}

.btn-cart:hover {
  background: #e5e7eb;
  border-color: #6c5ce7;
  color: #6c5ce7;
}

.dark .btn-cart:hover {
  background: #4b5563;
  border-color: #a29bfe;
  color: #a29bfe;
}

.btn-buy {
  flex: 1;
  background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);
  border: none;
  padding: 10px 16px;
  border-radius: 12px;
  cursor: pointer;
  font-weight: 600;
  font-size: 14px;
  color: #ffffff;
  transition: all 0.2s ease;
  box-shadow: 0 4px 12px rgba(108, 92, 231, 0.3);
  font-family: 'SFT Schrifted Sans', sans-serif;
}

.btn-buy:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(108, 92, 231, 0.4);
}

.btn-buy:disabled {
  background: #d1d5db;
  cursor: not-allowed;
  opacity: 0.5;
  box-shadow: none;
}

.dark .btn-buy:disabled {
  background: #4b5563;
}

@media (max-width: 1024px) {
  .product-card {
    flex-wrap: wrap;
  }

  .product-info {
    flex: 1 1 100%;
    order: 2;
  }

  .product-actions {
    order: 3;
    width: 100%;
    align-items: stretch;
  }

  .button-row {
    width: 100%;
  }
}

@media (max-width: 640px) {
  .product-card {
    padding: 16px;
    gap: 16px;
  }

  .product-image {
    width: 60px;
    height: 60px;
  }

  .product-title {
    font-size: 16px;
  }

  .product-description {
    font-size: 13px;
  }

  .action-row {
    width: 100%;
    justify-content: space-between;
  }

  .quantity-control {
    width: auto;
  }

  .price {
    font-size: 18px;
  }
}
</style>