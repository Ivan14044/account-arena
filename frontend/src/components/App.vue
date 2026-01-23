<template>
    <component :is="layoutComponent" :is-loading="isLoading" />

    <!-- Глобальный прелоадер для навигации между страницами -->
    <NavigationLoader />
    <!-- Виджет чата поддержки -->
    <SupportChatWidget />

    <!-- Глобальный SVG фильтр для эффекта матового стекла (Liquid Glass) -->
    <svg style="display: none" aria-hidden="true">
        <filter
            id="header-glass-distortion"
            x="0%"
            y="0%"
            width="100%"
            height="100%"
            filterUnits="objectBoundingBox"
        >
            <feTurbulence
                type="fractalNoise"
                baseFrequency="0.01 0.01"
                numOctaves="1"
                seed="5"
                result="turbulence"
            />
            <feComponentTransfer in="turbulence" result="mapped">
                <feFuncR type="gamma" amplitude="1" exponent="10" offset="0.5" />
                <feFuncG type="gamma" amplitude="0" exponent="1" offset="0" />
                <feFuncB type="gamma" amplitude="0" exponent="1" offset="0.5" />
            </feComponentTransfer>
            <feGaussianBlur in="turbulence" stdDeviation="3" result="softMap" />
            <feSpecularLighting
                in="softMap"
                surfaceScale="5"
                specularConstant="1"
                specularExponent="100"
                lighting-color="white"
                result="specLight"
            >
                <fePointLight x="-200" y="-200" z="300" />
            </feSpecularLighting>
            <feComposite
                in="specLight"
                operator="arithmetic"
                k1="0"
                k2="1"
                k3="1"
                k4="0"
                result="litImage"
            />
            <feDisplacementMap
                in="SourceGraphic"
                in2="softMap"
                scale="150"
                xChannelSelector="R"
                yChannelSelector="G"
            />
        </filter>
    </svg>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';

import DefaultLayout from '@/components/layout/DefaultLayout.vue';
import NavigationLoader from '@/components/NavigationLoader.vue';
import SupportChatWidget from '@/components/SupportChatWidget.vue';

import { usePageStore } from '@/stores/pages';
import { useOptionStore } from '@/stores/options';
import { useNotificationStore } from '@/stores/notifications';
import { useLoadingStore } from '@/stores/loading';
import { useAuthStore } from '@/stores/auth';
import { useAccountsStore } from '@/stores/accounts';
import { useBannersStore } from '@/stores/banners';
import { useProductCategoriesStore } from '@/stores/productCategories';

import logo from '@/assets/logo.webp';

const { locale } = useI18n();
const isLoading = ref(true);
const loadingStore = useLoadingStore();
const authStore = useAuthStore();
const bannersStore = useBannersStore();

const layoutComponent = computed(() => DefaultLayout);

/**
 * Скрывает начальный прелоадер с плавной анимацией
 */
function hideAppPreloader() {
    const preloader = document.getElementById('app-preloader');
    const appElement = document.getElementById('app');
    const body = document.body;

    if (preloader) {
        // Показываем контент приложения
        if (appElement) {
            appElement.classList.add('app-loaded');
        }

        // Скрываем прелоадер с анимацией
        preloader.classList.add('preloader-hidden');

        // Убираем класс loading с body
        body.classList.remove('loading');

        // Удаляем прелоадер из DOM после завершения анимации
        setTimeout(() => {
            if (preloader.parentNode) {
                preloader.remove();
            }
        }, 400); // Совпадает с transition в CSS (0.4s)
    }
}

onMounted(async () => {
    loadingStore.start();
    window.addEventListener('app:hide-loader', hideLoader);

    try {
        console.time('[APP] Critical Path');
        await authStore.init();

        const pageStore = usePageStore();
        const optionStore = useOptionStore();
        const notificationStore = useNotificationStore();
        const accountsStore = useAccountsStore();
        const categoriesStore = useProductCategoriesStore();

        // 1. КРИТИЧЕСКИЕ ДАННЫЕ (блокируют показ страницы)
        const criticalPromises = [
            pageStore.fetchData().catch(e => console.error('[APP] Ошибка загрузки pages:', e)),
            optionStore.fetchData().catch(e => console.error('[APP] Ошибка загрузки options:', e))
        ];

        // Уведомления загружаем только для авторизованных пользователей
        if (authStore.isAuthenticated) {
            criticalPromises.push(
                notificationStore
                    .fetchData()
                    .catch(e => console.error('[APP] Ошибка загрузки notifications:', e))
            );
        }

        // Ждем критических данных (быстро)
        await Promise.allSettled(criticalPromises);
        console.timeEnd('[APP] Critical Path');

        // 2. ФОНОВЫЕ ДАННЫЕ (не блокируют показ, загружаются после скрытия прелоадера)
        const backgroundLoad = async () => {
            console.time('[APP] Background Load');
            try {
                await Promise.allSettled([
                    categoriesStore.fetchAll().catch(e => console.error('[APP] BG: Categories error:', e)),
                    accountsStore.fetchAll().catch(e => console.error('[APP] BG: Accounts error:', e)),
                    bannersStore.fetchAll().catch(e => console.error('[APP] BG: Banners error:', e))
                ]);
                console.timeEnd('[APP] Background Load');
            } catch (e) {
                console.error('[APP] Background load failed:', e);
            }
        };

        // Запускаем фоновую загрузку без await
        backgroundLoad();

        // Предзагружаем критичные изображения
        preloadImages([logo, `/img/lang/${locale.value}.svg`]);

        // Даём небольшую задержку для отрисовки
        await new Promise(resolve => setTimeout(resolve, 50));

        loadingStore.stop();
        isLoading.value = false;

        // Скрываем прелоадер - теперь это происходит намного быстрее!
        hideAppPreloader();
    } catch (error) {
        console.error('Error loading application data:', error);
        // Даже при ошибке скрываем прелоадер
        hideAppPreloader();
        loadingStore.stop();
        isLoading.value = false;
    }
});

onUnmounted(() => {
    window.removeEventListener('app:hide-loader', hideLoader);
});

const preloadImages = (urls: string[]) => {
    urls.forEach(url => {
        const img = new Image();
        img.src = url;
    });
};

function hideLoader() {
    loadingStore.stop();
    isLoading.value = false;
    hideAppPreloader();
}
</script>
