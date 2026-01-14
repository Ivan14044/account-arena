<template>
    <div
        class="product-card"
        :class="{
            'out-of-stock-card': !isInStock,
            'with-discount': product.has_discount && product.discount_percent
        }"
    >
        <!-- Полоса со скидкой вверху карточки -->
        <div
            v-if="product.has_discount && product.discount_percent"
            class="discount-stripe"
        >
            Скидка -{{ discountPercentRounded }}%
        </div>

        <!-- Left: Product Image -->
        <div
            class="product-image-wrapper clickable"
            :title="$t('account.detail.go_to_product', { title: displayTitle })"
            @click="$router.push(`/account/${product.sku || product.id}`)"
        >
            <img
                :src="product.image_url || '/img/no-logo.png'"
                :alt="displayTitle"
                class="product-image"
                :loading="index !== undefined && index < 6 ? 'eager' : 'lazy'"
                decoding="async"
                width="400"
                height="300"
            />
        </div>

        <!-- Center: Product Info -->
        <div class="product-info">
            <!-- Контейнер для бейджей: Наличие + Способ выдачи + Артикул -->
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
                    <span>{{ formatStockQuantity(product) }}</span>
                </div>
                
                <!-- Бейдж способа выдачи (только если товар в наличии) -->
                <div
                    v-if="isInStock"
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

                <!-- Артикул товара (справа от бейджей) -->
                <div v-if="product.sku" class="product-sku product-sku--desktop product-sku--inline">
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
            </div>

            <h3
                class="product-title clickable-title"
                :title="'Перейти к ' + displayTitle"
                @click="$router.push(`/account/${product.sku || product.id}`)"
            >
                {{ displayTitle }}
            </h3>

            <p
                v-if="displayDescription"
                class="product-description"
                v-html="displayDescription"
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
                            v-if="product.has_discount && product.price"
                            class="price-old"
                        >
                            {{ formatPriceValue(product.price) }}
                        </span>
                        <div class="price">
                            {{ formattedTotalPrice || formatPriceValue((product.current_price || product.price) * (quantity || 1)) }}
                        </div>
                    </div>
                    <div class="price-per-unit">
                        {{
                            $t('account.detail.price_per_unit', {
                                price: formattedPrice || formatPriceValue(product.current_price || product.price),
                                quantity: quantity || 1
                            })
                        }}
                    </div>
                    <div class="product-sku--mobile">
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
                    </div>
                </div>

                <!-- Quantity Control -->
                <div class="quantity-control">
                    <button
                        class="quantity-btn"
                        :disabled="(quantity || 1) <= 1"
                        @click="$emit('decrease-quantity', product.id)"
                    >
                        −
                    </button>
                    <input
                        type="text"
                        :value="quantity || 1"
                        readonly
                        class="quantity-input"
                    />
                    <button
                        class="quantity-btn"
                        :disabled="product.quantity < 999 && (quantity || 1) >= (product.quantity || 1)"
                        @click="$emit('increase-quantity', product.id)"
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
                    @click="$router.push(`/account/${product.sku || product.id}`)"
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
                    :class="{ active: isFavorite }"
                    title="В избранное"
                    @click="$emit('toggle-favorite', product.id)"
                >
                    <svg
                        class="w-5 h-5"
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

                <button
                    class="btn-cart"
                    :disabled="!isInStock"
                    title="Добавить в корзину"
                    @click="$emit('add-to-cart', product)"
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
                    :disabled="!isInStock"
                    @click="$emit('buy-now', product)"
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
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useOptionStore } from '@/stores/options';
import { useProductTitle } from '@/composables/useProductTitle';

const props = defineProps<{
    product: any;
    quantity?: number;
    isFavorite?: boolean;
    index?: number;
    // Предвычисленные значения для производительности
    cachedTitle?: string;
    cachedDescription?: string;
    formattedPrice?: string;
    formattedTotalPrice?: string;
    discountPercentRounded?: number;
}>();

defineEmits(['increase-quantity', 'decrease-quantity', 'add-to-cart', 'buy-now', 'toggle-favorite']);

const { t } = useI18n();
const optionStore = useOptionStore();
const { getProductTitle, getProductDescription } = useProductTitle();

const displayTitle = computed(() => props.cachedTitle || getProductTitle(props.product));
const displayDescription = computed(() => props.cachedDescription || getProductDescription(props.product));
const discountPercentRounded = computed(() => props.discountPercentRounded || Math.round(props.product.discount_percent || 0));
const isInStock = computed(() => props.product.quantity > 0);

const formatPriceValue = (price: number) => {
    const currency = optionStore.getOption('currency', 'USD');
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(price);
};

const formatStockQuantity = (product: any): string => {
    if (product.quantity >= 999) return t('account.in_stock') || 'В наличии';
    return product.quantity > 0 ? product.quantity.toString() : '0';
};

const getDeliveryTypeLabel = (product: any): string => {
    const deliveryType = product.delivery_type || 'automatic';
    return deliveryType === 'manual' ? t('account.delivery.manual') : t('account.delivery.automatic');
};

const getDeliveryTypeText = (product: any): string => {
    const deliveryType = product.delivery_type || 'automatic';
    return deliveryType === 'manual' 
        ? t('account.delivery.manual_description') 
        : t('account.delivery.automatic_description');
};
</script>

<style scoped>
/* Основная карточка товара */
.product-card {
    display: grid;
    grid-template-columns: 90px 1fr auto;
    gap: 16px;
    align-items: center;
    background: rgba(255, 255, 255, 0.95);
    border: 1px solid rgba(226, 232, 240, 0.6);
    border-radius: 16px;
    padding: 12px 16px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    position: relative;
    overflow: hidden;
    transform: translateZ(0);
}

.dark .product-card {
    background: rgba(30, 41, 59, 0.7);
    backdrop-filter: blur(12px);
    border-color: rgba(255, 255, 255, 0.08);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
}

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

.product-card:hover {
    transform: translateX(4px) translateY(-2px);
    background: rgba(255, 255, 255, 1);
    box-shadow: 0 12px 24px rgba(108, 92, 231, 0.15);
    border-color: rgba(108, 92, 231, 0.3);
}

.dark .product-card:hover {
    background: rgba(30, 41, 59, 0.85);
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.4);
    border-color: rgba(108, 92, 231, 0.4);
}

.product-card:hover::before {
    opacity: 1;
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

.product-card.out-of-stock-card::before {
    display: none;
}

/* Обертка изображения */
.product-image-wrapper {
    position: relative;
    width: 90px;
    height: 90px;
    border-radius: 12px;
    overflow: hidden;
    background: linear-gradient(135deg, rgba(248, 249, 250, 0.95) 0%, rgba(233, 236, 239, 0.95) 100%);
    border: 1px solid rgba(226, 232, 240, 0.5);
    flex-shrink: 0;
    transition: transform 0.2s ease;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
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

.product-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: none;
}

.product-card:hover .product-image {
    transition: transform 0.3s ease;
    transform: scale(1.1);
}

.product-image-wrapper.clickable:hover .product-image {
    transition: transform 0.3s ease;
    transform: scale(1.2);
}

/* Компактный бейдж со скидкой */
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

.product-card.with-discount {
    padding-top: 16px;
}

/* Информация о товаре */
.product-info {
    display: flex;
    flex-direction: column;
    gap: 3px;
    min-width: 0;
    flex: 1;
}

.delivery-status-badge {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    margin-bottom: 2px;
}

.stock-badge-inline {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    padding: 3px 8px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 600;
    flex-shrink: 0;
    line-height: 1;
    border: 1px solid transparent;
}

.stock-badge-inline.in-stock {
    background: rgba(16, 185, 129, 0.12);
    color: #059669;
    border-color: rgba(16, 185, 129, 0.3);
}

.stock-badge-inline.out-of-stock {
    background: rgba(239, 68, 68, 0.12);
    color: #dc2626;
    border-color: rgba(239, 68, 68, 0.3);
}

.dark .stock-badge-inline.in-stock {
    background: rgba(16, 185, 129, 0.15);
    color: #34d399;
}

.dark .stock-badge-inline.out-of-stock {
    background: rgba(239, 68, 68, 0.15);
    color: #f87171;
}

.delivery-type-badge {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    padding: 3px 8px;
    border-radius: 10px;
    font-size: 10px;
    font-weight: 600;
    line-height: 1;
    cursor: help;
    border: 1px solid transparent;
}

.delivery-type-badge.auto-delivery {
    background: rgba(37, 99, 235, 0.12);
    color: #2563eb;
    border-color: rgba(37, 99, 235, 0.3);
}

.delivery-type-badge.manual-delivery {
    background: rgba(217, 119, 6, 0.12);
    color: #d97706;
    border-color: rgba(217, 119, 6, 0.3);
}

.dark .delivery-type-badge.auto-delivery {
    background: rgba(59, 130, 246, 0.15);
    color: #60a5fa;
}

.dark .delivery-type-badge.manual-delivery {
    background: rgba(245, 158, 11, 0.15);
    color: #fbbf24;
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
    word-break: break-word;
    font-family: 'SFT Schrifted Sans', sans-serif;
}

.dark .product-title {
    color: #f1f5f9;
}

.product-title.clickable-title {
    cursor: pointer;
    transition: color 0.2s ease;
}

.product-title.clickable-title:hover {
    color: #6c5ce7;
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
    margin: 4px 0 0 0;
}

.dark .product-description {
    color: #94a3b8;
}

.product-sku {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.125rem 0.5rem;
    background: rgba(99, 102, 241, 0.08);
    border: 1px solid rgba(99, 102, 241, 0.15);
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    color: #6366f1;
    text-transform: uppercase;
    margin-top: 2px;
}

.dark .product-sku {
    background: rgba(139, 92, 231, 0.1);
    border-color: rgba(139, 92, 231, 0.2);
    color: #a78bfa;
}

.product-sku--desktop {
    display: flex;
}

.product-sku--mobile {
    display: none;
}

.product-actions {
    display: flex;
    flex-direction: column;
    gap: 12px;
    align-items: flex-end;
    min-width: 170px;
    flex-shrink: 0;
}

.top-actions-row {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    width: 100%;
}

.price-section {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
}

.price-wrapper {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
}

.price-old {
    font-size: 11px;
    color: #9ca3af;
    text-decoration: line-through;
    margin-bottom: -1px;
}

.price {
    font-size: 20px;
    font-weight: 800;
    background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    line-height: 1;
    font-family: 'SFT Schrifted Sans', sans-serif;
}

.price-per-unit {
    font-size: 10px;
    color: #94a3b8;
}

.quantity-control {
    display: flex;
    align-items: center;
    background: #f8fafc;
    border: 1px solid rgba(226, 232, 240, 0.8);
    border-radius: 10px;
    overflow: hidden;
}

.dark .quantity-control {
    background: #1e293b;
    border-color: rgba(51, 65, 85, 0.8);
}

.quantity-btn {
    border: none;
    background: transparent;
    font-size: 16px;
    font-weight: 600;
    color: #64748b;
    cursor: pointer;
    padding: 4px 12px;
    min-width: 36px;
}

.quantity-input {
    width: 36px;
    border: none;
    background: transparent;
    text-align: center;
    font-weight: 700;
    font-size: 14px;
    color: #1f2937;
}

.dark .quantity-input {
    color: #f1f5f9;
}

.actions-row {
    display: flex;
    gap: 8px;
    width: 100%;
}

/* Стили для кнопок */
.btn-secondary {
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    padding: 10px;
    border-radius: 10px;
    cursor: pointer;
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.dark .btn-secondary {
    background: rgba(51, 65, 85, 0.4);
    border-color: rgba(255, 255, 255, 0.1);
    color: #cbd5e1;
}

.btn-secondary:hover {
    background: #e2e8f0;
    color: #475569;
}

.dark .btn-secondary:hover {
    background: rgba(51, 65, 85, 0.6);
    border-color: rgba(255, 255, 255, 0.2);
    color: white;
}

.btn-secondary.active {
    background: #fff1f2;
    border-color: #fecaca;
    color: #ef4444;
}

.btn-cart {
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    padding: 10px;
    border-radius: 10px;
    cursor: pointer;
    color: #64748b;
    min-width: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.dark .btn-cart {
    background: rgba(51, 65, 85, 0.4);
    border-color: rgba(255, 255, 255, 0.1);
    color: #cbd5e1;
}

.btn-cart:hover {
    background: #6c5ce7;
    border-color: #6c5ce7;
    color: white;
    box-shadow: 0 4px 12px rgba(108, 92, 231, 0.3);
}

.dark .btn-cart:hover {
    background: #6c5ce7;
    border-color: #6c5ce7;
    color: white;
    box-shadow: 0 4px 20px rgba(108, 92, 231, 0.4);
}

.btn-cart:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-primary {
    background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);
    border: none;
    padding: 10px 20px;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 700;
    font-size: 14px;
    color: white;
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s ease;
}

.btn-primary:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(108, 92, 231, 0.4);
    filter: brightness(1.1);
}

.btn-primary:active:not(:disabled) {
    transform: translateY(0);
}

@media (max-width: 1024px) {
    .product-card {
        grid-template-columns: 80px 1fr;
    }
    .product-actions {
        grid-column: 1 / -1;
    }
}

@media (max-width: 640px) {
    .product-image-wrapper {
        display: none !important;
    }
    .product-sku--desktop {
        display: none;
    }
    .product-sku--mobile {
        display: flex;
    }
}
</style>
