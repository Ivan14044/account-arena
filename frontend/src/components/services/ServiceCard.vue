<template>
    <div
        ref="cardRef"
        class="service-card relative group rounded-3xl transform-gpu"
        :class="{ 'theme-dark': isDark, 'theme-light': !isDark, flipped: isFlipped }"
        :style="[tiltStyle, containerHeightStyle]"
        tabindex="0"
        @pointermove.passive="onPointerMove"
        @pointerenter="onPointerEnter"
        @pointerleave="onPointerLeave"
        @pointerdown="onPointerDown"
        @pointerup="onPointerUp"
        @keydown.space.prevent="flipCard"
        @keydown.enter.prevent="flipCard"
        @click="onBackClick()"
    >
        <div
            ref="innerRef"
            class="card-inner relative w-full"
            :class="{ flipped: isFlipped }"
            role="group"
            :aria-expanded="isFlipped.toString()"
        >
            <section
                ref="frontRef"
                class="card-side front p-6 text-center"
                :aria-hidden="isFlipped.toString()"
            >
                <router-link
                    :to="`/service/${service.id}`"
                    class="top-4 left-4 z-40 w-10 h-10 rounded-full text-dark dark:text-white/90 glass-button flex items-center justify-center transition-none"
                    :class="{ 'glass-dark': isDark, 'glass-light': !isDark }"
                    aria-label="Open service"
                    @click.stop
                >
                    <svg
                        class="w-4 h-4 transition-none"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                        />
                    </svg>
                </router-link>
                <div
                    v-if="canShowTrialBadge"
                    class="absolute top-6 right-6 px-3 py-1.5 rounded-full text-xs font-medium !border border-black dark:border-white glass-badge text-dark dark:text-white/90"
                >
                    {{
                        $t('plans.trial_period', {
                            price: formatter.format(service.trial_amount)
                        })
                    }}
                </div>

                <div class="flex justify-center items-center mt-3 mb-6">
                    <div
                        class="w-24 h-24 rounded-2xl flex items-center justify-center text-2xl shadow-lg transition-all duration-300 group-hover:scale-105 glass-icon"
                    >
                        <img
                            :src="service.logo"
                            :alt="`${getTranslation(service, 'name')} Logo`"
                            class="w-20 h-20 object-contain"
                            loading="lazy"
                            @load="updateContainerHeight"
                        />
                    </div>
                </div>

                <h3 class="text-xl font-medium mb-1 text-dark dark:text-white">
                    {{ getTranslation(service, 'name') }}
                </h3>
                <p class="text-sm mb-4 test-white h-[20px]">
                    <span v-if="getTranslation(service, 'subtitle')">{{
                        getTranslation(service, 'subtitle')
                    }}</span>
                </p>

                <p
                    class="text-sm font-medium mb-4 text-dark dark:text-white"
                    v-html="
                        $t('plans.price_monthly', {
                            price: service.amount.toFixed(2),
                            currency: optionStore.options.currency.toUpperCase()
                        })
                    "
                ></p>

                <div class="mb-6 max-w-xs mx-auto text-left relative">
                    <p
                        class="line-clamp-3 text-sm test-white"
                        v-html="getTranslation(service, 'short_description_card')"
                    ></p>
                    <div
                        class="fade-bottom"
                        :class="{ 'fade-dark': isDark, 'fade-light': !isDark }"
                    ></div>

                    <div class="mt-3 flex justify-center">
                        <button
                            class="show-more-button text-sm font-medium text-blue-500 hover:text-blue-600 transition-colors focus:outline-none"
                            :aria-expanded="isFlipped.toString()"
                            type="button"
                            @click.stop="flipCard()"
                        >
                            {{ $t('services.card.show_more') }}
                        </button>
                    </div>
                </div>

                <div
                    class="flex flex-col sm:flex-row gap-3 justify-center items-stretch w-full max-w-3xl mx-auto"
                >
                    <!-- Кнопка “Открыть” если подписка активна -->
                    <button
                        v-if="
                            isAuthenticated && authStore.user.active_services?.includes(service.id)
                        "
                        class="animated-button flex-1 px-4 py-2.5 rounded-full font-medium text-[15px] transition-all duration-300 overflow-hidden flex items-center justify-center gap-2 btn-success"
                        @click="openService(service.id)"
                    >
                        {{ $t('plans.open') }}
                    </button>

                    <!-- Основная кнопка (бОльшая) -->
                    <button
                        v-else-if="!trialActivatedIds.includes(service.id)"
                        class="animated-button flex-[2] px-4 py-2.5 rounded-full font-medium text-[15px] transition-all duration-300 overflow-hidden flex items-center justify-center gap-2"
                        :class="{ 'btn-success': isAdded, 'btn-primary': !isAdded }"
                        :aria-pressed="isAdded"
                        aria-label="Add service"
                        type="button"
                        @click.stop="onAdd()"
                    >
                        <span v-if="!isAdded" class="flex items-center gap-2">
                            {{ $t('plans.add_to_cart') }}
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
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                                />
                            </svg>
                        </span>
                        <span v-else>
                            <template v-if="addedState === 'check'">✓</template>
                            <template v-else>{{ $t('plans.go_to_checkout') }}</template>
                        </span>
                    </button>

                    <!-- Кнопка “Попробовать пробный период” -->
                    <button
                        v-if="canShowTrialButton"
                        class="animated-button flex-1 btn-outline px-4 py-2.5 rounded-full font-medium text-[15px] !border transition-all duration-300"
                        type="button"
                        @click="tryTrial()"
                    >
                        {{ $t('plans.get_trial') }}
                    </button>

                    <!-- Кнопка “Перейти к оплате” если trial активирован -->
                    <button
                        v-if="trialActivatedIds.includes(service.id)"
                        class="animated-button flex-1 px-4 py-2.5 rounded-full font-medium text-[15px] transition-all duration-300 overflow-hidden flex items-center justify-center gap-2 btn-success"
                        @click="goToCheckout()"
                    >
                        {{ $t('plans.go_to_checkout') }}
                    </button>
                </div>
            </section>

            <section
                ref="backRef"
                class="card-side back p-6"
                :aria-hidden="(!isFlipped).toString()"
                @click="onBackClick()"
            >
                <div class="h-full flex flex-col">
                    <div class="flex items-center gap-4 mb-4">
                        <div
                            class="w-12 h-12 rounded-lg flex items-center justify-center glass-icon"
                        >
                            <img :src="service.logo" class="w-8 h-8 object-contain" alt="" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-dark dark:text-white">
                                {{ getTranslation(service, 'name') }}
                            </h3>
                            <div class="text-sm text-dark dark:text-white">
                                {{ getTranslation(service, 'subtitle') }}
                            </div>
                        </div>
                        <div class="text-sm font-medium ml-auto text-dark dark:text-white">
                            {{ service.amount.toFixed(2) }}
                            {{ optionStore.options.currency.toUpperCase() }}
                        </div>
                    </div>

                    <div class="flex-1 overflow-auto mb-4">
                        <div
                            class="typed-content text-sm text-center leading-relaxed text-dark dark:text-white"
                            v-html="getTranslation(service, 'short_description_card')"
                        ></div>
                    </div>

                    <div class="text-xs text-center text-dark dark:text-white/80">
                        {{ $t('services.card.back_to_front_side') }}
                    </div>
                </div>
            </section>

            <div class="absolute inset-0 rounded-3xl pointer-events-none overlay-wrap z-0">
                <div class="absolute inset-0 rounded-3xl glass-overlay"></div>
                <div
                    class="absolute inset-0 rounded-3xl glass-shine"
                    :style="{ '--shine-x': shineX, '--shine-y': shineY }"
                ></div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useTheme } from '@/composables/useTheme';
import { useAuthStore } from '@/stores/auth';
import { useOptionStore } from '@/stores/options';
import { useCartStore } from '@/stores/cart';
import { useRouter } from 'vue-router';

interface Props {
    service: {
        id: number;
        logo: string;
        amount: number;
        [key: string]: any;
    };
    isAdded?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    isAdded: false
});

const { locale } = useI18n();
const { isDark } = useTheme();
const router = useRouter();

const optionStore = useOptionStore();
const authStore = useAuthStore();
const cartStore = useCartStore();

const isAuthenticated = computed(() => !!authStore.user);
const addedState = ref<'check' | 'checkout'>('check');
const trialActivatedIds = ref<number[]>([]);

const tryTrial = () => {
    const serviceId = props.service.id;

    if (cartStore.hasService(serviceId) && cartStore.subscriptionTypes[serviceId] === 'premium') {
        cartStore.removeFromCart(serviceId);
    }

    if (!trialActivatedIds.value.includes(serviceId)) {
        trialActivatedIds.value.push(serviceId);
    }

    isAdded.value = true;
    cartStore.addToCart(props.service, 'trial');
};

const canShowTrialBadge = computed(() => {
    if (!props.service.trial_amount) return false;
    if (!isAuthenticated.value) return true;

    return !authStore.user.active_services?.includes(props.service.id);
});

const canShowTrialButton = computed(() => {
    return (
        !trialActivatedIds.value.includes(props.service.id) &&
        props.service.trial_amount &&
        (!isAuthenticated.value || !authStore.user.active_services?.includes(props.service.id)) &&
        cartStore.subscriptionTypes[props.service.id] !== 'trial'
    );
});

const formatter = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: optionStore.options.currency,
    minimumFractionDigits: 0,
    maximumFractionDigits: 2
});

const getTranslation = (service, key) => {
    return service.translations[locale.value]?.[key] ?? service.translations['en']?.[key] ?? null;
};

const goToCheckout = () => {
    if (isAuthenticated.value) {
        router.push('/checkout');
    } else {
        router.push({
            path: '/login',
            query: { redirect: 'checkout' }
        });
    }
};

const openService = async (id: number) => {
    const url = '/session-start/' + id;

    window.open(url, '_blank');
};

const cardRef = ref<HTMLElement | null>(null);
const innerRef = ref<HTMLElement | null>(null);
const frontRef = ref<HTMLElement | null>(null);
const backRef = ref<HTMLElement | null>(null);

const isAdded = ref(props.isAdded);
const isFlipped = ref(false);
const frontVisible = ref(true);

const isMobile = ref(false);

const targetX = ref(0.5);
const targetY = ref(0.5);
const displayX = ref(0.5);
const displayY = ref(0.5);
let rafId: number | null = null;

const containerHeight = ref<number | null>(null);
const containerHeightStyle = computed(() =>
    containerHeight.value ? { height: `${containerHeight.value}px` } : { height: 'auto' }
);

const shineX = computed(() => `${(displayX.value * 100).toFixed(2)}%`);
const shineY = computed(() => `${(displayY.value * 100).toFixed(2)}%`);

const baseMaxTilt = 4;
const tiltStyle = computed(() => {
    if (isMobile.value) return { transform: '', '--shine-x': '50%', '--shine-y': '50%' } as any;
    const dx = Math.abs(displayX.value - 0.5);
    const dy = Math.abs(displayY.value - 0.5);
    const distanceFromCenter = Math.sqrt(dx * dx + dy * dy);
    const minFactor = 0.85;
    const cornerFactor = 1 + distanceFromCenter * 0.9;
    const finalFactor = Math.max(minFactor, cornerFactor);
    const rotateX = (0.5 - displayY.value) * baseMaxTilt * finalFactor;
    const rotateY = (displayX.value - 0.5) * baseMaxTilt * finalFactor;
    const translateZ = isFlipped.value ? 0 : 10;

    return {
        transform: `perspective(1100px) rotateX(${rotateX.toFixed(3)}deg) rotateY(${rotateY.toFixed(3)}deg) translateZ(${translateZ}px)`,
        '--shine-x': shineX.value,
        '--shine-y': shineY.value
    } as any;
});

watch(
    () => props.isAdded,
    val => {
        isAdded.value = val;
        if (val) {
            addedState.value = 'check';
            setTimeout(() => {
                addedState.value = 'checkout';
            }, 1000);
        }
    },
    { immediate: true }
);

const tick = () => {
    displayX.value += (targetX.value - displayX.value) * 0.12;
    displayY.value += (targetY.value - displayY.value) * 0.12;
    rafId = requestAnimationFrame(tick);
};

const onPointerMove = (e: PointerEvent) => {
    if (!cardRef.value || isMobile.value) return;
    const rect = cardRef.value.getBoundingClientRect();
    const x = (e.clientX - rect.left) / rect.width;
    const y = (e.clientY - rect.top) / rect.height;
    targetX.value = Math.max(0, Math.min(1, x));
    targetY.value = Math.max(0, Math.min(1, y));
    displayX.value = targetX.value;
    displayY.value = targetY.value;
};
const onPointerEnter = () => {
    if (cardRef.value) cardRef.value.classList.add('is-active');
};
const onPointerLeave = () => {
    targetX.value = 0.5;
    targetY.value = 0.5;
    if (cardRef.value) cardRef.value.classList.remove('is-active');
};
const onPointerDown = (e: PointerEvent) => {
    (e.target as Element).setPointerCapture?.(e.pointerId);
};
const onPointerUp = (e: PointerEvent) => {
    (e.target as Element).releasePointerCapture?.(e.pointerId);
};

const updateContainerHeight = async () => {
    await nextTick();
    const frontEl = frontRef.value as HTMLElement | null;
    const backEl = backRef.value as HTMLElement | null;
    const frontH = frontEl ? frontEl.scrollHeight : 0;
    const backH = backEl ? backEl.scrollHeight : 0;
    const desired = Math.max(frontH, backH);
    containerHeight.value = desired > 0 ? Math.ceil(desired) : null;
};

let innerTransitionHandler: ((e: TransitionEvent) => void) | null = null;
let innerWebkitHandler: ((e: Event) => void) | null = null;
let transitionFallbackId: number | null = null;
const TRANSITION_EXPECTED_MS = 950;

const clearTransitionFallback = () => {
    if (transitionFallbackId !== null) {
        clearTimeout(transitionFallbackId);
        transitionFallbackId = null;
    }
};
const scheduleFallback = () => {
    clearTransitionFallback();
    transitionFallbackId = window.setTimeout(() => {
        frontVisible.value = !isFlipped.value;
        transitionFallbackId = null;
        requestAnimationFrame(updateContainerHeight);
    }, TRANSITION_EXPECTED_MS);
};

const onInnerTransitionEnd = (e?: TransitionEvent | Event) => {
    if (e && 'propertyName' in e) {
        const te = e as TransitionEvent;
        if (!te.propertyName || te.propertyName.indexOf('transform') === -1) return;
    }
    clearTransitionFallback();
    frontVisible.value = !isFlipped.value;
    requestAnimationFrame(updateContainerHeight);
};

const flipCard = async () => {
    isFlipped.value = !isFlipped.value;
    if (innerRef.value) frontVisible.value = false;
    scheduleFallback();
    await nextTick();
    requestAnimationFrame(updateContainerHeight);
};

const onBackClick = () => {
    if (isFlipped.value) flipCard();
};

const onAdd = () => {
    if (cartStore.hasService(props.service.id)) {
        goToCheckout();
    } else {
        isAdded.value = true;

        addedState.value = 'check';
        setTimeout(() => {
            addedState.value = 'checkout';
        }, 1000);

        cartStore.addToCart(props.service, 'premium');
    }
};

const handleResize = () => {
    isMobile.value = window.innerWidth < 768 || 'ontouchstart' in window;
    if (isMobile.value) {
        targetX.value = 0.5;
        targetY.value = 0.5;
        displayX.value = 0.5;
        displayY.value = 0.5;
    }
};

onMounted(() => {
    handleResize();
    rafId = requestAnimationFrame(tick);
    updateContainerHeight();

    innerTransitionHandler = (e: TransitionEvent) => onInnerTransitionEnd(e);
    innerWebkitHandler = (e: Event) => onInnerTransitionEnd(e);
    if (innerRef.value) {
        innerRef.value.addEventListener('transitionend', innerTransitionHandler as EventListener);
        innerRef.value.addEventListener('webkitTransitionEnd', innerWebkitHandler as EventListener);
    }

    window.addEventListener('resize', handleResize, { passive: true });
    window.addEventListener('resize', updateContainerHeight, { passive: true });
    setTimeout(updateContainerHeight, 160);
});

onUnmounted(() => {
    if (rafId !== null) {
        cancelAnimationFrame(rafId);
        rafId = null;
    }
    window.removeEventListener('resize', handleResize);
    window.removeEventListener('resize', updateContainerHeight);
    if (innerTransitionHandler && innerRef.value)
        innerRef.value.removeEventListener(
            'transitionend',
            innerTransitionHandler as EventListener
        );
    if (innerWebkitHandler && innerRef.value)
        innerRef.value.removeEventListener(
            'webkitTransitionEnd',
            innerWebkitHandler as EventListener
        );
    clearTransitionFallback();
});
</script>

<style scoped>
.service-card {
    position: relative;
    z-index: 1;
    isolation: isolate;
    will-change: transform;
    width: 100%;
}

.service-card.theme-light .card-side {
    background: rgba(255, 255, 255, 0.4);
    border: 1px solid rgba(2, 6, 23, 0.05);
    color: #0b1220;
    box-shadow: 0 8px 30px rgba(10, 12, 25, 0.06);
}

.service-card.theme-dark .card-side {
    background: rgba(10, 14, 24, 0.6);
    border: 1px solid rgba(255, 255, 255, 0.04);
    color: #ffffff;
    box-shadow: 0 10px 40px rgba(1, 4, 10, 0.55);
}

.service-card.is-active,
.service-card:hover,
.service-card:focus-within {
    z-index: 80;
}

.card-inner {
    width: 100%;
    transition: transform 0.85s cubic-bezier(0.22, 0.9, 0.36, 1);
    transform-style: preserve-3d;
    -webkit-transform-style: preserve-3d;
    position: relative;
    z-index: 2;
    backface-visibility: hidden;
}

.card-inner.flipped {
    transform: rotateY(180deg);
}

.card-side {
    box-sizing: border-box;
    padding: 1.5rem;
    border-radius: 1rem;
    -webkit-backface-visibility: hidden;
    backface-visibility: hidden;
    transform-style: preserve-3d;
    display: block;
    transition: opacity 0.35s ease;
    width: 100%;
    height: 100%;
}

.card-side.front {
    position: relative;
    transform: rotateY(0deg);
    z-index: 3;
}

.card-side.back {
    position: absolute;
    inset: 0;
    transform: rotateY(180deg);
    width: 100%;
    z-index: 2;
}

.card-inner.flipped .card-side.front {
    opacity: 0;
    pointer-events: none;
    z-index: 1;
}

.card-inner.flipped .card-side.back {
    opacity: 1;
    pointer-events: auto;
    z-index: 4;
}

.overlay-wrap {
    pointer-events: none;
    z-index: 0;
    border-radius: 1rem;
    overflow: hidden;
}

.service-card .glass-overlay {
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    background: rgba(255, 255, 255, 0.02);
    border: 1px solid rgba(255, 255, 255, 0.04);
}

.service-card:hover .glass-overlay,
.service-card.is-active .glass-overlay {
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    background: rgba(255, 255, 255, 0.035);
    border-color: rgba(255, 255, 255, 0.06);
    box-shadow: 0 12px 36px rgba(10, 12, 25, 0.12);
}

.service-card .glass-shine {
    pointer-events: none;
    mix-blend-mode: overlay;
    background: radial-gradient(
        600px circle at var(--shine-x, 50%) var(--shine-y, 50%),
        rgba(255, 255, 255, 0.14),
        rgba(255, 255, 255, 0.06) 20%,
        rgba(255, 255, 255, 0.02) 45%,
        transparent 60%
    );
    transition:
        opacity 160ms linear,
        background-position 120ms linear;
    opacity: 0.95;
}

.glass-dark {
    background: rgba(12, 24, 60, 0.24);
    border-color: rgba(255, 255, 255, 0.06);
}

.animated-button {
    position: relative;
    overflow: hidden;
    z-index: 30;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.animated-button::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.12) 0%, transparent 70%);
    transition: all 0.6s ease;
    transform: translate(-50%, -50%);
    border-radius: 50%;
    pointer-events: none;
}

.animated-button:hover::before {
    width: 300px;
    height: 300px;
}

.btn-primary {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
    padding: 8px 18px;
    border-radius: 9999px;
}

.btn-success {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    padding: 8px 18px;
    border-radius: 9999px;
}

.btn-outline {
    border: 1px solid #3b82f6;
    color: #3b82f6;
    background: transparent;
    padding: 8px 14px;
    border-radius: 9999px;
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.fade-bottom {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    height: 2rem;
    pointer-events: none;
    display: none;
}

.fade-light {
    background: linear-gradient(to bottom, transparent, rgba(255, 255, 255, 0.85));
}

.fade-dark {
    background: linear-gradient(to bottom, transparent, rgba(17, 24, 39, 0.85));
}

@media (max-width: 768px) {
    .service-card {
        transform: none !important;
    }

    .glass-overlay {
        backdrop-filter: blur(10px);
    }
}
</style>
