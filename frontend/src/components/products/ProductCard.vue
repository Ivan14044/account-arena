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
            −{{ discountPercentRounded }}%
        </div>

        <!-- Left: Product Image -->
        <div
            class="product-image-wrapper clickable"
            :title="$t('account.detail.go_to_product', { title: displayTitle })"
            @click="navigateToProduct"
        >
            <img
                :src="product.image_url || '/img/logo_trans.webp'"
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
                @click="navigateToProduct"
            >
                {{ displayTitle }}
            </h3>

            <p
                v-if="displayDescription"
                class="product-description"
                v-safe-html="displayDescription"
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
                            {{ localFormattedTotalPrice }}
                        </div>
                    </div>
                    <div class="price-per-unit">
                        {{
                            $t('account.detail.price_per_unit', {
                                price: localFormattedPrice,
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
                    @click="navigateToProduct"
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
import { formatPrice, effectivePrice } from '@/utils/money';

import { useRouter } from 'vue-router';

const formatPriceValue = (price: number, currency: string = 'USD') => {
    return formatPrice(price, currency);
};

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

const currentCurrency = computed(() => optionStore.getOption('currency', 'USD'));

const displayTitle = computed(() => props.cachedTitle || getProductTitle(props.product));
const displayDescription = computed(() => props.cachedDescription || getProductDescription(props.product));
const discountPercentRounded = computed(() => props.discountPercentRounded || Math.round(props.product.discount_percent || 0));
const isInStock = computed(() => props.product.quantity > 0);

const localFormattedPrice = computed(() => {
    if (props.formattedPrice) return props.formattedPrice;
    const price = effectivePrice(props.product);
    return formatPriceValue(price, currentCurrency.value);
});

const localFormattedTotalPrice = computed(() => {
    if (props.formattedTotalPrice) return props.formattedTotalPrice;
    const price = effectivePrice(props.product);
    return formatPriceValue(price * (props.quantity || 1), currentCurrency.value);
});

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

const router = useRouter();

const navigateToProduct = () => {
    // Prefer slug and /products/ prefix if available
    if (props.product.slug) {
        router.push(`/products/${props.product.slug}`);
    } else {
        // Fallback to old URL structure
        router.push(`/account/${props.product.sku || props.product.id}`);
    }
};
</script>

<style scoped>
/* ============================================================
   ПРЕМИАЛЬНАЯ КАРТОЧКА ТОВАРА — «Obsidian + Champagne»
   Токены берутся из app.css (:root / .dark)
   ============================================================ */
.product-card {
    display: grid;
    grid-template-columns: 96px 1fr auto;
    gap: 18px;
    align-items: center;
    background: var(--aa-surface);
    border: 1px solid var(--aa-border);
    border-radius: var(--aa-radius);
    padding: 16px 20px;
    transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1),
                box-shadow 0.4s cubic-bezier(0.16, 1, 0.3, 1),
                border-color 0.4s ease;
    box-shadow: var(--aa-shadow-sm);
    position: relative;
    overflow: hidden;
    transform: translateZ(0);
    backface-visibility: hidden;
    contain: content;
}

/* Тонкая золотая грань-акцент слева (появляется на hover) */
.product-card::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 3px;
    background: var(--aa-gold-gradient);
    transform: scaleY(0);
    transform-origin: top;
    transition: transform 0.45s cubic-bezier(0.16, 1, 0.3, 1);
}

.product-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--aa-shadow-md);
    border-color: var(--aa-gold-line);
}

.product-card:hover::before {
    transform: scaleY(1);
}

/* Товары, которые закончились */
.product-card.out-of-stock-card {
    opacity: 0.62;
    background: var(--aa-surface-soft);
    filter: grayscale(0.5);
}

.product-card.out-of-stock-card:hover {
    transform: none;
    box-shadow: var(--aa-shadow-sm);
    border-color: var(--aa-border);
}

.product-card.out-of-stock-card::before {
    display: none;
}

/* Обертка изображения */
.product-image-wrapper {
    position: relative;
    width: 96px;
    height: 96px;
    border-radius: 13px;
    overflow: hidden;
    background: var(--aa-surface-soft);
    border: 1px solid var(--aa-border);
    flex-shrink: 0;
    transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1),
                box-shadow 0.4s ease, border-color 0.4s ease;
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.35);
    will-change: transform;
    transform: translateZ(0);
}

.product-image-wrapper.clickable {
    cursor: pointer;
}

.product-image-wrapper.clickable:hover {
    box-shadow: var(--aa-shadow-gold);
    border-color: var(--aa-gold-line);
}

.product-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: none;
}

.product-card:hover .product-image {
    transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
    transform: scale(1.06);
}

.product-image-wrapper.clickable:hover .product-image {
    transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
    transform: scale(1.12);
}

/* Премиальный шильд скидки */
.discount-stripe {
    position: absolute;
    top: 10px;
    right: 10px;
    background: var(--aa-ink);
    color: var(--aa-gold-strong);
    padding: 4px 11px;
    text-align: center;
    font-size: 11px;
    font-weight: 700;
    z-index: 10;
    letter-spacing: 0.06em;
    line-height: 1.2;
    border-radius: 999px;
    box-shadow: 0 4px 14px rgba(20, 22, 28, 0.25);
    border: 1px solid var(--aa-gold-line);
}

.dark .discount-stripe {
    background: #0c0d11;
}

.product-card.with-discount {
    padding-top: 20px;
}

/* Информация о товаре */
.product-info {
    display: flex;
    flex-direction: column;
    gap: 5px;
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
    gap: 4px;
    padding: 3px 9px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 600;
    flex-shrink: 0;
    line-height: 1;
    letter-spacing: 0.01em;
    border: 1px solid transparent;
}

.stock-badge-inline.in-stock {
    background: var(--aa-success-soft);
    color: var(--aa-success);
    border-color: var(--aa-success-soft);
}

.stock-badge-inline.out-of-stock {
    background: var(--aa-danger-soft);
    color: var(--aa-danger);
    border-color: var(--aa-danger-soft);
}

.delivery-type-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 9px;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 600;
    line-height: 1;
    cursor: help;
    letter-spacing: 0.02em;
    background: var(--aa-surface-sunk);
    color: var(--aa-ink-soft);
    border: 1px solid var(--aa-border);
}

.delivery-type-badge.auto-delivery {
    color: var(--aa-gold-strong);
    border-color: var(--aa-gold-line);
    background: var(--aa-gold-soft);
}

.product-title {
    font-size: 15.5px;
    font-weight: 700;
    color: var(--aa-ink);
    margin: 0;
    line-height: 1.32;
    letter-spacing: -0.01em;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    word-break: break-word;
    font-family: 'SFT Schrifted Sans', sans-serif;
}

.product-title.clickable-title {
    cursor: pointer;
    transition: color 0.25s ease;
}

.product-title.clickable-title:hover {
    color: var(--aa-gold-strong);
}

.product-description {
    font-size: 13px;
    color: var(--aa-ink-soft);
    line-height: 1.55;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    word-break: break-word;
    margin: 2px 0 0 0;
}

.product-sku {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.125rem 0.5rem;
    background: transparent;
    border: 1px solid var(--aa-border);
    border-radius: 999px;
    font-size: 10.5px;
    font-weight: 600;
    color: var(--aa-ink-faint);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-top: 2px;
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
    gap: 14px;
    align-items: flex-end;
    min-width: 184px;
    flex-shrink: 0;
}

.top-actions-row {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 14px;
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
    color: var(--aa-ink-faint);
    text-decoration: line-through;
    margin-bottom: 1px;
}

.price {
    font-size: 23px;
    font-weight: 800;
    color: var(--aa-ink);
    line-height: 1;
    letter-spacing: -0.02em;
    font-family: 'SFT Schrifted Sans', sans-serif;
    font-variant-numeric: tabular-nums;
}

.price-per-unit {
    font-size: 10px;
    color: var(--aa-ink-faint);
    margin-top: 4px;
    letter-spacing: 0.01em;
}

.quantity-control {
    display: flex;
    align-items: center;
    background: var(--aa-surface-soft);
    border: 1px solid var(--aa-border);
    border-radius: 11px;
    overflow: hidden;
}

.quantity-btn {
    border: none;
    background: transparent;
    font-size: 16px;
    font-weight: 600;
    color: var(--aa-ink-soft);
    cursor: pointer;
    padding: 5px 12px;
    min-width: 36px;
    transition: color 0.2s ease, background-color 0.2s ease;
}

.quantity-btn:hover:not(:disabled) {
    color: var(--aa-gold-strong);
    background: var(--aa-gold-soft);
}

.quantity-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.quantity-input {
    width: 36px;
    border: none;
    background: transparent;
    text-align: center;
    font-weight: 700;
    font-size: 14px;
    color: var(--aa-ink);
    font-variant-numeric: tabular-nums;
}

.actions-row {
    display: flex;
    gap: 8px;
    width: 100%;
}

/* Кнопки */
.btn-secondary {
    background: var(--aa-surface-soft);
    border: 1px solid var(--aa-border);
    padding: 10px;
    border-radius: 11px;
    cursor: pointer;
    color: var(--aa-ink-soft);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background-color 0.25s ease, color 0.25s ease,
                border-color 0.25s ease, transform 0.25s ease;
}

.btn-secondary:hover {
    background: var(--aa-ink);
    color: var(--aa-surface);
    border-color: var(--aa-ink);
    transform: translateY(-1px);
}

.btn-secondary.active {
    background: var(--aa-danger-soft);
    border-color: var(--aa-danger-soft);
    color: var(--aa-danger);
}

.btn-cart {
    background: var(--aa-surface-soft);
    border: 1px solid var(--aa-border);
    padding: 10px;
    border-radius: 11px;
    cursor: pointer;
    color: var(--aa-ink-soft);
    min-width: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background-color 0.25s ease, color 0.25s ease,
                border-color 0.25s ease, transform 0.25s ease;
}

.btn-cart:hover:not(:disabled) {
    background: var(--aa-ink);
    border-color: var(--aa-ink);
    color: var(--aa-surface);
    transform: translateY(-1px);
}

.btn-cart:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}

.btn-primary {
    position: relative;
    background: var(--aa-gold-gradient);
    border: none;
    padding: 11px 22px;
    border-radius: 11px;
    cursor: pointer;
    font-weight: 700;
    font-size: 14px;
    letter-spacing: 0.01em;
    color: #14161c;
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    overflow: hidden;
    transition: transform 0.25s ease, box-shadow 0.25s ease, filter 0.25s ease;
}

/* Световой блик при наведении */
.btn-primary::after {
    content: '';
    position: absolute;
    top: 0;
    left: -120%;
    width: 60%;
    height: 100%;
    background: linear-gradient(
        100deg,
        transparent 0%,
        rgba(255, 255, 255, 0.55) 50%,
        transparent 100%
    );
    transform: skewX(-18deg);
    transition: left 0.7s cubic-bezier(0.16, 1, 0.3, 1);
}

.btn-primary:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: var(--aa-shadow-gold);
    filter: saturate(1.05);
}

.btn-primary:hover:not(:disabled)::after {
    left: 130%;
}

.btn-primary:active:not(:disabled) {
    transform: translateY(0);
}

.btn-primary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

@media (max-width: 1024px) {
    .product-card {
        grid-template-columns: 88px 1fr;
    }
    .product-actions {
        grid-column: 1 / -1;
        align-items: stretch;
        min-width: 0;
    }
}

@media (max-width: 640px) {
    .product-card {
        display: flex;
        flex-direction: column;
        padding: 16px;
        gap: 12px;
    }

    .product-image-wrapper {
        display: none !important;
    }

    .product-info {
        gap: 8px;
        width: 100%;
    }

    .delivery-status-badge {
        gap: 6px;
    }

    .product-title {
        font-size: 16px;
        -webkit-line-clamp: 3;
    }

    .product-description {
        -webkit-line-clamp: 4;
        margin-top: 2px;
    }

    .top-actions-row {
        flex-direction: row;
        justify-content: space-between;
        align-items: flex-end;
        gap: 8px;
        border-top: 1px solid var(--aa-border);
        padding-top: 14px;
    }

    .price-section {
        align-items: flex-start;
        flex: 1;
    }

    .price-wrapper {
        align-items: flex-start;
    }

    .price {
        font-size: 24px;
    }

    .price-per-unit {
        font-size: 11px;
    }

    .quantity-control {
        height: 40px;
    }

    .actions-row {
        margin-top: 4px;
        gap: 6px;
    }

    .btn-secondary, .btn-cart {
        padding: 10px;
        min-width: 44px;
        flex-shrink: 0;
    }

    .btn-primary {
        flex: 2;
        font-size: 15px;
        padding: 11px 16px;
    }

    .product-sku--desktop {
        display: none;
    }

    .product-sku--mobile {
        display: flex;
        margin-top: 6px;
    }
}
</style>
