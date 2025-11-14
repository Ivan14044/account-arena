<template>
    <div class="max-w-7xl mx-auto px-4 py-16 sm:px-6 lg:px-8 position-relative z-1">
        <div
            :class="[
                'min-h-screen transition-colors duration-300 mt-3',
                isDark ? 'text-gray-100' : 'text-gray-900'
            ]"
        >
            <div class="fixed inset-0 pointer-events-none overflow-hidden">
                <div
                    :class="[
                        'animated-gradient absolute w-[120vw] h-[120vh]',
                        isDark ? 'opacity-60 blur-[80px]' : 'opacity-40 blur-[70px]'
                    ]"
                />
            </div>

            <div class="relative">
                <main>
                    <div class="max-w-7xl mx-auto mb-5 flex items-center justify-between">
                        <router-link
                            to="/"
                            class="flex items-center gap-2 text-dark dark:text-white hover:text-gray-400 transition-colors glass-button px-4 py-2 rounded-full"
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
                                    d="M10 19l-7-7m0 0l7-7m-7 7h18"
                                />
                            </svg>
                            {{ $t('services.page.back') }}
                        </router-link>
                    </div>

                    <div class="max-w-7xl mx-auto">
                        <div
                            v-if="service"
                            class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-stretch"
                        >
                            <div class="lg:col-span-4 flex flex-col">
                                <div
                                    class="big-hero-card glass-card rounded-3xl p-8 flex-1 flex flex-col justify-between"
                                >
                                    <div class="flex flex-col items-center gap-4">
                                        <div
                                            class="logo-round w-32 h-32 rounded-full flex items-center justify-center shadow-xl"
                                        >
                                            <img
                                                :src="service.logo"
                                                :alt="`${getTranslation(service, 'name')} Logo`"
                                                loading="lazy"
                                                class="w-20 h-20 object-contain"
                                            />
                                        </div>

                                        <div class="text-center">
                                            <h2 class="text-3xl font-bold">{{ service.name }}</h2>
                                            <p class="mt-1 text-sm text-dark dark:text-white">
                                                {{ getTranslation(service, 'subtitle') }}
                                            </p>
                                        </div>

                                        <div class="text-center text-dark dark:text-white">
                                            {{ service.amount.toFixed(2) }}
                                            {{ serviceOption.options.currency.toUpperCase() }}
                                        </div>
                                    </div>

                                    <div class="w-full mt-4">
                                        <div
                                            class="flex flex-col sm:flex-row gap-3 justify-center items-center"
                                        >
                                            <!-- Кнопка “Открыть” если подписка активна -->
                                            <button
                                                v-if="
                                                    isAuthenticated &&
                                                    authStore.user.active_services?.includes(
                                                        service.id
                                                    )
                                                "
                                                class="animated-button relative px-8 py-2.5 rounded-full font-medium transition-all duration-300 overflow-hidden flex items-center justify-center gap-2 btn-success"
                                                @click="openService()"
                                            >
                                                {{ $t('plans.open') }}
                                            </button>

                                            <!-- Основная кнопка: Добавить в корзину или Перейти к оплате -->
                                            <button
                                                v-else-if="!trialActivatedIds.includes(service.id)"
                                                class="animated-button relative px-8 py-2.5 rounded-full font-medium transition-all duration-300 min-w-[180px] overflow-hidden flex items-center justify-center gap-2"
                                                :class="{
                                                    'btn-success': isAdded,
                                                    'btn-primary': !isAdded
                                                }"
                                                :aria-pressed="isAdded"
                                                aria-label="Add service"
                                                type="button"
                                                @click.stop="onAdd()"
                                            >
                                                <span
                                                    v-if="!isAdded"
                                                    class="flex items-center gap-2"
                                                >
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
                                                    <template v-if="addedState === 'check'"
                                                        >✓</template
                                                    >
                                                    <template v-else>{{
                                                        $t('plans.go_to_checkout')
                                                    }}</template>
                                                </span>
                                            </button>

                                            <!-- Кнопка “Попробовать пробный период” -->
                                            <button
                                                v-if="canShowTrialButton"
                                                class="animated-button btn-outline px-6 py-2.5 rounded-full font-medium !border transition-all duration-300"
                                                type="button"
                                                @click="tryTrial()"
                                            >
                                                {{ $t('plans.get_trial') }}
                                            </button>

                                            <!-- Кнопка “Перейти к оплате” если trial активирован -->
                                            <button
                                                v-if="trialActivatedIds.includes(service.id)"
                                                class="animated-button relative px-8 py-2.5 rounded-full font-medium transition-all duration-300 overflow-hidden flex items-center justify-center gap-2 btn-success"
                                                @click="goToCheckout()"
                                            >
                                                {{ $t('plans.go_to_checkout') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="lg:col-span-8 flex flex-col">
                                <div
                                    class="info-panel glass-card rounded-3xl p-8 flex-1 flex flex-col justify-between"
                                >
                                    <div>
                                        <h1
                                            class="text-3xl lg:text-4xl text-dark dark:text-white font-extrabold mb-4 info-heading"
                                        >
                                            {{ getTranslation(service, 'name') }}
                                        </h1>

                                        <div
                                            class="text-dark dark:text-white mb-4 info-body service-content"
                                            v-html="getTranslation(service, 'full_description')"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useTheme } from '@/composables/useTheme';
import { useServiceStore } from '@/stores/services';
import { useAuthStore } from '@/stores/auth';
import { useOptionStore } from '@/stores/options';

interface Service {
    id: number;
    logo: string;
    amount: number;
    [key: string]: any;
}

const { locale } = useI18n();
const route = useRoute();
const router = useRouter();
const { isDark } = useTheme();
const serviceStore = useServiceStore();
const authStore = useAuthStore();
const serviceOption = useOptionStore();
const service = ref<Service | null>(null);
const trialActivatedIds = ref<number[]>([]);
const isAuthenticated = computed(() => !!authStore.user);
const addedState = ref<'check' | 'checkout'>('check');
const isAdded = ref(false);

const openService = async (id: number) => {
    const url = '/session-start/' + id;

    const isMobile =
        /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(
            navigator.userAgent
        ) || window.innerWidth <= 768;

    if (isMobile) {
        window.open(url, '_blank');
    } else {
        const screenWidth = window.screen.width;
        const screenHeight = window.screen.height;

        const windowWidth = Math.floor(screenWidth * 0.8);
        const windowHeight = Math.floor(screenHeight * 0.8);

        const left = Math.floor((screenWidth - windowWidth) / 2);
        const top = Math.floor((screenHeight - windowHeight) / 2);

        const features = `
            toolbar=no,
            location=no,
            status=no,
            menubar=no,
            scrollbars=yes,
            resizable=yes,
            width=${windowWidth},
            height=${windowHeight},
            left=${left},
            top=${top},
            popup=yes,
            noopener=no,
            noreferrer=no
        `;

        window.open(url, '_blank', features.replace(/\s+/g, ''));
    }
};

const canShowTrialButton = computed(() => {
    return (
        !trialActivatedIds.value.includes(service.value.id) &&
        service.value.trial_amount &&
        (!isAuthenticated.value || !authStore.user.active_services?.includes(service.value.id))
    );
});

const getTranslation = (service, key) => {
    return service.translations[locale.value]?.[key] ?? service.translations['en']?.[key] ?? null;
};

const tryTrial = () => {
    const serviceId = service.value.id;

    if (!trialActivatedIds.value.includes(serviceId)) {
        trialActivatedIds.value.push(serviceId);
    }

    isAdded.value = true;
    // Service cart functionality removed - only product cart is used
};

const onAdd = () => {
    isAdded.value = true;

    addedState.value = 'check';
    setTimeout(() => {
        addedState.value = 'checkout';
    }, 1000);

    // Service cart functionality removed - only product cart is used
    goToCheckout();
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

onMounted(async () => {
    const serviceId = Number(route.params.id);

    if (!serviceStore.isLoaded) {
        await serviceStore.fetchData();
    }

    service.value = serviceStore.getById(serviceId);

    if (!service.value) {
        await router.replace('/404');
    }

    isAdded.value = false;
    if (isAdded.value) {
        addedState.value = 'checkout';
    }
});

// Service cart watch removed - only product cart is used
</script>

<style scoped>
.service-content ::v-deep(p) {
    padding-bottom: 15px;
}

.big-hero-card {
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.03), rgba(255, 255, 255, 0.01));
    box-shadow: 0 8px 30px rgba(2, 6, 23, 0.35);
    min-height: 260px;
    display: flex;
    flex-direction: column;
}

.logo-round {
    background: linear-gradient(135deg, #7c3aed, #60a5fa);
    box-shadow: 0 8px 30px rgba(99, 102, 241, 0.18);
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
    border: 1px solid rgba(99, 102, 241, 0.9);
    color: rgba(99, 102, 241, 0.95);
    background: transparent;
    padding: 8px 14px;
    border-radius: 9999px;
}

.info-panel {
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.02), rgba(255, 255, 255, 0.01));
    border-radius: 24px;
}

.animated-gradient {
    background: linear-gradient(
        120deg,
        rgba(255, 106, 0, 0.35) 10%,
        rgba(255, 0, 204, 0.55) 35%,
        rgba(0, 170, 255, 0.75) 70%,
        rgba(0, 123, 255, 0.45) 90%
    );
    animation: gradientMove 30s ease-in-out infinite;
}
@keyframes gradientMove {
    0%,
    100% {
        transform: translate(-18%, -18%) rotate(0deg) scale(1);
    }
    25% {
        transform: translate(-10%, -22%) rotate(20deg) scale(1.03);
    }
    50% {
        transform: translate(8%, -12%) rotate(40deg) scale(0.98);
    }
    75% {
        transform: translate(-12%, 8%) rotate(25deg) scale(1.02);
    }
}

@media (max-width: 1024px) {
    .big-hero-card {
        min-height: 220px;
    }
    .animated-button {
        width: 100%;
        justify-content: center;
    }
    .btn-primary,
    .btn-outline {
        min-width: 0;
        width: 100%;
    }
}

@media (max-width: 640px) {
    main {
        padding-left: 12px;
        padding-right: 12px;
    }
    .big-hero-card {
        padding: 20px;
    }
    .info-panel {
        padding: 20px;
    }
    .logo-round {
        width: 88px;
        height: 88px;
    }
}
</style>
