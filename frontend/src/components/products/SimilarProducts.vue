<template>
    <div v-if="products.length > 0" class="similar-products-section">
        <h2 class="section-title">{{ $t('products.similar.title') }}</h2>
        <div class="products-list">
            <div
                v-for="product in products"
                :key="product.id"
                class="product-card"
                :class="{
                    'out-of-stock-card': product.quantity <= 0,
                    'with-discount': product.has_discount && product.discount_percent
                }"
            >
                <!-- Компактный бейдж со скидкой в правом верхнем углу карточки -->
                <div v-if="product.has_discount && product.discount_percent" class="discount-badge">
                    -{{ Math.round(product.discount_percent || 0) }}%
                </div>

                <!-- Left: Product Image -->
                <div
                    class="product-image-wrapper main_card clickable"
                    :title="getProductTitle(product)"
                    @click="goToProduct(product)"
                >
                    <img
                        :src="product.image_url || '/img/no-logo.png'"
                        :alt="getProductTitle(product)"
                        class="product-image"
                        loading="lazy"
                    />
                </div>

                <!-- Center: Product Info -->
                <div class="product-info">
                    <div class="title-with-badge">
                        <!-- Контейнер для бейджей: Наличие + Способ выдачи -->
                        <div class="delivery-status-badge">
                            <!-- Stock Badge - наличие товара -->
                            <div
                                class="stock-badge-inline"
                                :class="product.quantity > 0 ? 'in-stock' : 'out-of-stock'"
                            >
                                <svg
                                    v-if="product.quantity > 0"
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
                                <span>{{ product.quantity > 0 ? product.quantity : '0' }}</span>
                            </div>
                            
                            <!-- Бейдж способа выдачи (только если товар в наличии) -->
                            <div
                                v-if="product.quantity > 0"
                                class="delivery-type-badge"
                                :class="(product.delivery_type || 'automatic') === 'manual' ? 'manual-delivery' : 'auto-delivery'"
                                :title="getDeliveryTypeText(product)"
                            >
                                <svg
                                    v-if="(product.delivery_type || 'automatic') === 'manual'"
                                    class="w-3 h-3"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                                    <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                                </svg>
                                <svg
                                    v-else
                                    class="w-3 h-3"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="delivery-type-text">
                                    {{ getDeliveryTypeLabel(product) }}
                                </span>
                            </div>
                        </div>

                        <h3
                            class="product-title clickable-title"
                            :title="getProductTitle(product)"
                            @click="goToProduct(product)"
                        >
                            {{ getProductTitle(product) }}
                        </h3>
                    </div>

                    <!-- Артикул товара -->
                    <div v-if="product.sku" class="product-sku">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"
                            />
                        </svg>
                        <span>{{ product.sku }}</span>
                    </div>

                    <p
                        v-if="getProductDescription(product)"
                        class="product-description"
                        v-html="getProductDescription(product)"
                    ></p>
                </div>

                <!-- Right: Actions -->
                <div class="product-actions">
                    <div class="price-section">
                        <div class="price-wrapper">
                            <span v-if="product.has_discount" class="price-old">
                                {{ formatPrice(product.price) }}
                            </span>
                            <div class="price">
                                {{ formatPrice(product.current_price || product.price) }}
                            </div>
                        </div>
                    </div>

                    <button class="btn-primary" @click.stop="goToProduct(product)">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                        {{ $t('account.detail.view_details') }}
                    </button>
                </div>
            </div>
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
const props = defineProps<{
    productId: string | number;
}>();

const router = useRouter();
const accountsStore = useAccountsStore();
const optionStore = useOptionStore();
const { getProductTitle, getProductDescription } = useProductTitle();
const { t } = useI18n();
const products = ref<AccountItem[]>([]);

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

// Функции для отображения способа выдачи товара
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

/* Основная карточка товара - горизонтальная */
.product-card {
    display: grid;
    grid-template-columns: 90px 1fr auto;
    gap: 14px;
    align-items: start;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 12px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    position: relative;
    overflow: hidden;
}

.dark .product-card {
    background: #1e293b;
    border-color: #334155;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

/* Акцентная линия слева */
.product-card::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: linear-gradient(180deg, #6c5ce7 0%, #a29bfe 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.product-card:hover::before {
    opacity: 1;
}

.product-card:hover {
    transform: translateX(4px);
    box-shadow: 0 8px 24px rgba(108, 92, 231, 0.12);
    border-color: rgba(108, 92, 231, 0.3);
}

.dark .product-card:hover {
    box-shadow: 0 8px 24px rgba(108, 92, 231, 0.2);
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

/* Обертка изображения */
.product-image-wrapper {
    position: relative;
    width: 90px;
    height: 90px;
    border-radius: 10px;
    overflow: hidden;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    flex-shrink: 0;
    transition: all 0.3s ease;
}

.product-image-wrapper.clickable {
    cursor: pointer;
}

.product-image-wrapper.clickable:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(108, 92, 231, 0.2);
}

.dark .product-image-wrapper {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
}

.dark .product-image-wrapper.clickable:hover {
    box-shadow: 0 4px 12px rgba(108, 92, 231, 0.3);
}

.product-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}

.product-card:hover .product-image {
    transform: scale(1.08);
}

.product-image-wrapper.clickable:hover .product-image {
    transform: scale(1.15);
}

/* Компактный бейдж со скидкой в правом верхнем углу карточки */
.discount-badge {
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

/* Информация о товаре */
.product-info {
    display: flex;
    flex-direction: column;
    gap: 6px;
    min-width: 0;
    flex: 1;
    padding-top: 2px;
}

/* Контейнер для названия с бейджем */
.title-with-badge {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 6px;
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

/* Контейнер для бейджей: Наличие + Способ выдачи */
.delivery-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    flex-shrink: 0;
}

/* Бейдж способа выдачи товара */
.delivery-type-badge {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    padding: 3px 8px;
    border-radius: 10px;
    font-size: 10px;
    font-weight: 600;
    transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
    line-height: 1;
    cursor: help;
}

.delivery-type-badge .delivery-type-text {
    white-space: nowrap;
}

/* Синий бейдж - автоматическая выдача */
.delivery-type-badge.auto-delivery {
    background: rgba(37, 99, 235, 0.12);
    color: #2563eb;
    border: 1px solid rgba(37, 99, 235, 0.3);
}

/* Оранжевый бейдж - ручная выдача */
.delivery-type-badge.manual-delivery {
    background: rgba(217, 119, 6, 0.12);
    color: #d97706;
    border: 1px solid rgba(217, 119, 6, 0.3);
}

/* Dark theme для бейджей способа выдачи */
.dark .delivery-type-badge.auto-delivery {
    background: rgba(59, 130, 246, 0.15);
    color: #60a5fa;
    border-color: rgba(59, 130, 246, 0.4);
}

.dark .delivery-type-badge.manual-delivery {
    background: rgba(245, 158, 11, 0.15);
    color: #fbbf24;
    border-color: rgba(245, 158, 11, 0.4);
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
    flex: 1;
    cursor: pointer;
    transition: color 0.3s ease;
}

.dark .product-title {
    color: #f1f5f9;
}

.product-title.clickable-title:hover {
    color: #6c5ce7;
}

.dark .product-title.clickable-title:hover {
    color: #a78bfa;
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

.price-section {
    text-align: center;
    flex: 1;
    width: 100%;
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
    font-size: 10px;
    color: #94a3b8;
    text-decoration: line-through;
    white-space: nowrap;
}

.dark .price-old {
    color: #64748b;
}

.price {
    font-size: 19px;
    font-weight: 800;
    background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    line-height: 1;
    display: block;
    font-family: 'SFT Schrifted Sans', sans-serif;
    white-space: nowrap;
}

/* Основная кнопка */
.btn-primary {
    background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);
    border: none;
    padding: 8px 16px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 13px;
    color: white;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(108, 92, 231, 0.25);
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    font-family: 'SFT Schrifted Sans', sans-serif;
    position: relative;
    overflow: hidden;
    white-space: nowrap;
    width: 100%;
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
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(108, 92, 231, 0.35);
}

.btn-primary:disabled {
    background: #cbd5e1;
    cursor: not-allowed;
    opacity: 0.6;
    box-shadow: none;
}

/* Адаптивность */
@media (max-width: 768px) {
    .product-card {
        grid-template-columns: 70px 1fr;
        gap: 10px;
    }

    .product-actions {
        grid-column: 1 / -1;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        min-width: auto;
        width: 100%;
        margin-top: 8px;
    }

    .product-image-wrapper {
        width: 70px;
        height: 70px;
    }
}

@media (max-width: 640px) {
    .product-card {
        grid-template-columns: 1fr;
        padding: 12px;
        gap: 12px;
    }

    .main_card {
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
}
</style>
