<template>
    <div v-if="items.length">
        <div class="gallery-main">
            <ul class="gallery">
                <li
                    v-for="item in visibleItems"
                    :key="item.id"
                    :data-pos="item.dpos"
                    :style="{
                        transform: `translate3d(calc(var(--width) * ${isMobile ? 0.15 : 0.2} * ${item.dpos}), 0, 0) scale(var(--s))`
                    }"
                    class="sm:p-3 p-2 flex flex-col overflow-hidden rounded-lg"
                    @pointerdown="handlePointerDown"
                    @pointermove="handlePointerMove"
                    @pointerup="e => handlePointerUp(e, item)"
                >
                    <!-- background spinning logo -->
                    <div
                        class="absolute inset-0 z-0 flex items-center justify-center pointer-events-none"
                    >
                        <img
                            :src="logo"
                            alt=""
                            class="w-20 h-20 sm:w-24 sm:h-24 opacity-10 blur-sm spin-slow-reverse"
                        />
                    </div>

                    <!-- main content -->
                    <div class="relative z-10 text-center flex flex-col h-full">
                        <div class="flex flex-col items-center justify-center gap-1 mt-2 mb-2">
                            <img :src="item.url" alt="" class="w-10 h-10" />
                            <div class="text-[8px] text-gray-600 dark:text-gray-300">
                                {{ getServiceName(item.serviceId) }}
                            </div>
                            <h2 class="text-center font-bold text-xs">{{ item.title }}</h2>
                        </div>

                        <p
                            v-if="item.subtitle"
                            class="text-center text-gray-600 dark:text-gray-300 text-[8px] mb-1"
                        >
                            {{ item.subtitle }}
                        </p>

                        <div class="flex gap-2 justify-center w-full">
                            <p
                                v-if="item.oldPrice"
                                class="text-[8px] text-gray-500 dark:text-gray-300 line-through"
                            >
                                {{ item.oldPrice }}
                            </p>
                            <p class="text-[8px] text-green-600 dark:text-green-400 font-semibold">
                                {{ item.price }}
                            </p>
                        </div>

                        <div class="mt-auto pt-2">
                            <button
                                v-if="showOpen(item.serviceId)"
                                class="cta-btn bg-green-600 hover:bg-green-500"
                                @click.stop="openService(item.serviceId!)"
                            >
                                {{ $t('plans.open') }}
                            </button>
                            <button
                                v-else-if="showTrial(item.serviceId)"
                                class="cta-btn bg-blue-600 hover:bg-blue-500"
                                type="button"
                                @click.stop="tryTrial(item.serviceId!)"
                            >
                                {{ $t('plans.get_trial') }}
                            </button>
                            <button
                                v-else-if="showPremium(item.serviceId)"
                                class="cta-btn bg-blue-600 hover:bg-blue-500"
                                type="button"
                                @click.stop="addPremium(item.serviceId!)"
                            >
                                {{ $t('plans.add_to_cart') }}
                            </button>
                            <p
                                v-if="item.cancelText"
                                class="text-gray-500 dark:text-gray-300 text-[8px] text-center mt-1"
                            >
                                {{ item.cancelText }}
                            </p>
                        </div>
                    </div>
                </li>
            </ul>
        </div>

        <!-- arrows -->
        <div class="flex justify-center gap-4">
            <button
                class="nav-btn dark:bg-gray-700 dark:hover:bg-gray-600"
                aria-label="Previous"
                @click="move('prev')"
            >
                <svg
                    class="w-4 h-4 text-blue-600 dark:text-white"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M15 19l-7-7 7-7"
                    />
                </svg>
            </button>

            <button
                class="nav-btn dark:bg-gray-700 dark:hover:bg-gray-600"
                aria-label="Next"
                @click="move('next')"
            >
                <svg
                    class="w-4 h-4 text-blue-600 dark:text-white"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 5l7 7-7 7"
                    />
                </svg>
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { reactive, ref, computed, onMounted, onBeforeUnmount, watchEffect, watch } from 'vue';
import logo from '@/assets/logo.webp';
import { useContentsStore } from '@/stores/contents';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import { useServiceStore } from '@/stores/services';
import { useAuthStore } from '@/stores/auth';
import { useCartStore } from '@/stores/cart';
import { useRouter } from 'vue-router';

const isMobile = ref(window.innerWidth <= 768);

type SavingsItem = {
    id: number | string;
    pos: number;
    url?: string;
    title?: string;
    price?: string;
    oldPrice?: string;
    subtitle?: string;
    cancelText?: string;
    serviceId?: number;
};

const items = reactive<SavingsItem[]>([]);
const serviceIdByBlockId = new Map<any, number>();

const { locale } = useI18n();
const contentStore = useContentsStore();
const serviceStore = useServiceStore();
const authStore = useAuthStore();
const cartStore = useCartStore();
const router = useRouter();

const isAuthenticated = computed(() => !!authStore.user);
const ready = computed(() => serviceStore.isLoaded && authStore.userLoaded === true);

function isActiveService(serviceId: number) {
    const active = authStore.user?.active_services || [];
    const sid = Number(serviceId);
    return active.some((v: any) => Number(v) === sid);
}

function canShowTrial(serviceId: number) {
    const svc = serviceStore.getById(serviceId) as any;
    if (!svc || !svc.trial_amount) return false;
    if (isAuthenticated.value && isActiveService(serviceId)) return false;
    return cartStore.subscriptionTypes[serviceId] !== 'trial';
}

// новые обёртки, вся проверка готовности тут
function showOpen(id?: number) {
    if (!id || !ready.value) return false;
    return isAuthenticated.value && isActiveService(id);
}
function showTrial(id?: number) {
    if (!id || !ready.value) return false;
    return canShowTrial(id) && !(isAuthenticated.value && isActiveService(id));
}
function showPremium(id?: number) {
    if (!id || !ready.value) return false;
    return !showOpen(id) && !showTrial(id);
}

onMounted(() => {
    contentStore.fetchContent('saving_on_subscriptions', locale.value);
    if (!serviceStore.isLoaded) serviceStore.fetchData();
    window.addEventListener('resize', updateDeviceType);
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', updateDeviceType);
});

// Refetch content when language changes so mapping/serviceId stays correct
watch(
    () => locale.value,
    newLocale => {
        contentStore.fetchContent('saving_on_subscriptions', newLocale);
    }
);

// Blocks from API
const apiBlocks = computed<any[]>(() => {
    return contentStore.itemsByCode['saving_on_subscriptions']?.[locale.value] || [];
});

// Helpers
function toAbsoluteUrl(path?: string) {
    if (!path) return undefined;
    if (/^https?:\/\//i.test(path)) return path;
    const base = (import.meta as any).env?.VITE_API_BASE || axios.defaults.baseURL || '';
    return `${String(base).replace(/\/$/, '')}/${String(path).replace(/^\//, '')}`;
}

function normalizePath(u?: string) {
    if (!u) return '';
    try {
        const url = new URL(u, axios.defaults.baseURL || window.location.origin);
        return url.pathname.replace(/^\/+/, '/');
    } catch {
        return String(u)
            .replace(/^https?:\/\/[^/]+/i, '')
            .replace(/^\/+/, '/');
    }
}

// Fill items
watchEffect(() => {
    const blocks = apiBlocks.value || [];
    items.length = 0;

    blocks.forEach((block: any, index: number) => {
        let mappedServiceId: number | undefined;
        const linkVal = block?.service_id ?? block?.serviceId ?? block?.link;
        const blockIdKey = block?.id ?? null;

        if (linkVal != null) {
            const n = Number(linkVal);
            if (!Number.isNaN(n) && n > 0) mappedServiceId = n;
        }

        const apiLogoRaw = block?.logo || block?.image || block?.url;
        const apiLogoAbs = toAbsoluteUrl(apiLogoRaw);

        if (!mappedServiceId && apiLogoAbs) {
            const itemPath = normalizePath(apiLogoAbs);
            const found = serviceStore.services.find(svc => normalizePath(svc.logo) === itemPath);
            if (found) mappedServiceId = Number(found.id);
        }

        // If still not resolved, try cached mapping by block id
        if (!mappedServiceId && blockIdKey != null && serviceIdByBlockId.has(blockIdKey)) {
            mappedServiceId = serviceIdByBlockId.get(blockIdKey)!;
        }

        const svc = mappedServiceId ? serviceStore.getById(mappedServiceId) : undefined;
        if (!svc) return; // пропускаем блок, если сервис не найден в сторе

        const serviceLogo = svc.logo as any;
        const finalLogo = apiLogoAbs || toAbsoluteUrl(serviceLogo);

        // Update cache for next rebuilds
        if (blockIdKey != null && mappedServiceId) {
            serviceIdByBlockId.set(blockIdKey, mappedServiceId);
        }

        items.push({
            id: block?.id ?? index,
            pos: items.length,
            url: finalLogo,
            title: block?.text || block?.title,
            price: block?.our_price || block?.ourPrice || block?.price,
            oldPrice:
                block?.normal_price || block?.normalPrice || block?.old_price || block?.oldPrice,
            subtitle: block?.advantage || block?.subtitle,
            cancelText: block?.cancel_text || block?.cancelText,
            serviceId: mappedServiceId
        });
    });
});

// Actions
function openService(id: number) {
    window.open('/session-start/' + id, '_blank');
}
function tryTrial(serviceId: number) {
    const svc = serviceStore.getById(serviceId);
    if (!svc) return;
    if (cartStore.hasService(serviceId) && cartStore.subscriptionTypes[serviceId] === 'premium') {
        cartStore.removeFromCart(serviceId);
    }
    cartStore.addToCart(svc as any, 'trial');
    goToCheckout();
}
function addPremium(serviceId: number) {
    const svc = serviceStore.getById(serviceId);
    if (!svc) return goToServices();
    if (cartStore.hasService(serviceId)) return goToCheckout();
    cartStore.addToCart(svc as any, 'premium');
    goToCheckout();
}
function goToServices() {
    try {
        router.push({ path: '/', hash: '#services' });
    } catch {}
}
function goToCheckout() {
    try {
        router.push(
            isAuthenticated.value
                ? '/checkout'
                : { path: '/login', query: { redirect: 'checkout' } as any }
        );
    } catch {}
}

function getServiceName(serviceId?: number) {
    if (!serviceId) return '';
    const svc = serviceStore.getById(serviceId) as any;
    return (
        svc?.translations?.[locale.value]?.name || svc?.translations?.en?.name || svc?.name || ''
    );
}

// Carousel logic
function shuffle(item: SavingsItem) {
    const heroPos = Math.floor(items.length / 2);
    const hero = items.findIndex(({ pos }) => pos === heroPos);
    const target = items.findIndex(({ id }) => id === item.id);
    [items[target].pos, items[hero].pos] = [items[hero].pos, items[target].pos];
}

const startX = ref(0);
const isDragging = ref(false);

function handlePointerDown(e: PointerEvent) {
    startX.value = e.clientX;
    isDragging.value = false;
}
function handlePointerMove(e: PointerEvent) {
    if (Math.abs(e.clientX - startX.value) > 10) isDragging.value = true;
}
function handlePointerUp(e: PointerEvent, item: SavingsItem) {
    if (isDragging.value) {
        const diff = e.clientX - startX.value;
        if (diff > 50) move('prev');
        else if (diff < -50) move('next');
    } else {
        shuffle(item);
    }
}
function move(direction: 'next' | 'prev') {
    if (!items.length) return;
    if (direction === 'next') {
        items.forEach(item => (item.pos = (item.pos - 1 + items.length) % items.length));
    } else {
        items.forEach(item => (item.pos = (item.pos + 1) % items.length));
    }
}
function updateDeviceType() {
    isMobile.value = window.innerWidth <= 768;
}

// Visible items
const visibleItems = computed(() => {
    const total = items.length;
    if (total === 0) return [] as Array<SavingsItem & { dpos: number }>;
    const center = Math.floor(total / 2);
    const windowRadius = 2;
    const result: Array<SavingsItem & { dpos: number }> = [];

    for (const item of items) {
        let diff = (item.pos - center + total) % total;
        if (diff > total / 2) diff -= total;
        if (Math.abs(diff) <= windowRadius) {
            const dpos = diff + windowRadius;
            result.push({ ...item, dpos });
        }
    }
    return result.sort((a, b) => a.dpos - b.dpos);
});
</script>

<style scoped>
.gallery-main {
    padding: 80px 0;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 16px;
    position: relative;
}

/* gallery base */
.gallery {
    --width: min(1000px, 90vw);
    width: min(var(--width));
    list-style-type: none;
    display: grid;
    user-select: none;
    touch-action: pan-y;
}
.gallery li {
    --s: 1;
    position: relative;
    grid-column: 1;
    grid-row: 1;
    width: calc(var(--width) / 5);
    min-height: 180px;
    cursor: grab;
    transition:
        transform 0.6s cubic-bezier(0.22, 1, 0.36, 1),
        opacity 0.5s ease,
        filter 0.6s cubic-bezier(0.22, 1, 0.36, 1);
    will-change: transform;
    /* Light theme to match ServiceCard.vue theme-light */
    background: rgba(255, 255, 255, 0.4);
    border: 1px solid rgba(2, 6, 23, 0.05);
    box-shadow: 0 8px 30px rgba(10, 12, 25, 0.06);
    border-radius: 24px;
    color: #0b1220;
}
.gallery li:active {
    cursor: grabbing;
}
.gallery li::after {
    content: '';
    position: absolute;
    inset: 0;
}

/* Dark theme overrides: restore original dark palette for this block */
.dark .gallery li {
    background: rgba(12, 24, 60, 0.7);
    border: 1px solid rgba(255, 255, 255, 0.14);
    box-shadow:
        0px 20px 25px -5px rgba(0, 0, 0, 0.1),
        0px 8px 10px -6px rgba(0, 0, 0, 0.1);
    color: #ffffff;
}

/* responsive */
@media (max-width: 768px) {
    .gallery {
        --width: min(1000px, 100vw);
    }
    .gallery li {
        width: calc(var(--width) / 2);
    }
    .gallery-main {
        margin-left: -38px;
    }
}

/* positions */
.gallery li[data-pos='0'],
.gallery li[data-pos='4'] {
    --s: 1;
    z-index: 1;
    opacity: 0.6;
    filter: blur(2px);
}
.gallery li[data-pos='1'],
.gallery li[data-pos='3'] {
    --s: 1.2;
    z-index: 5;
    opacity: 0.8;
    filter: blur(1px);
}
.gallery li[data-pos='2'] {
    --s: 1.6;
    z-index: 10;
    opacity: 1;
    filter: none;
}

/* arrows */
.nav-btn {
    @apply bg-white border border-gray-300 rounded-full shadow-md w-10 h-10 flex items-center justify-center transition hover:bg-gray-100;
}

/* reusable cta-button */
.cta-btn {
    @apply w-full flex items-center justify-center py-1 rounded-[5px] h-[26px] text-[9px] font-medium text-white;
}
</style>
