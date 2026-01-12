<template>
    <component :is="layoutComponent" :is-loading="isLoading" />

    <!-- Глобальный прелоадер для навигации между страницами -->
    <NavigationLoader />
    <!-- Виджет чата поддержки -->
    <SupportChatWidget />
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
        await authStore.init();

        const pageStore = usePageStore();
        const optionStore = useOptionStore();
        const notificationStore = useNotificationStore();
        const accountsStore = useAccountsStore();

        // ОПТИМИЗАЦИЯ: Загружаем все критичные данные параллельно при старте приложения
        const promises = [
            pageStore.fetchData().catch(e => console.error('[APP] Ошибка загрузки pages:', e)),
            optionStore.fetchData().catch(e => console.error('[APP] Ошибка загрузки options:', e)),
            accountsStore
                .fetchAll()
                .catch(e => {
                    // Улучшенное логирование ошибок с деталями
                    const errorDetails = {
                        status: e?.response?.status,
                        statusText: e?.response?.statusText,
                        message: e?.message,
                        url: e?.config?.url || '/api/accounts',
                        responseData: e?.response?.data
                    };
                    console.error('[APP] Ошибка загрузки accounts:', errorDetails);
                    console.error('[APP] Полная ошибка accounts:', e);
                    // Ошибка не блокирует загрузку других данных благодаря Promise.allSettled
                }), // Предзагрузка товаров
            bannersStore
                .fetchBanners('home_top')
                .catch(e => console.error('[APP] Ошибка загрузки banners home_top:', e)), // Предзагрузка обычных баннеров с изображениями
            bannersStore
                .fetchBanners('home_top_wide')
                .catch(e => console.error('[APP] Ошибка загрузки banners home_top_wide:', e)) // Предзагрузка широкого баннера с изображением
        ];

        // Уведомления загружаем только для авторизованных пользователей
        if (authStore.isAuthenticated) {
            promises.push(
                notificationStore
                    .fetchData()
                    .catch(e => console.error('[APP] Ошибка загрузки notifications:', e))
            );
        }

        // Добавляем таймаут на случай зависания
        const timeoutPromise = new Promise((_, reject) =>
            setTimeout(
                () => reject(new Error('Timeout: загрузка данных заняла слишком много времени')),
                10000
            )
        );

        // Ждем загрузки всех данных с таймаутом
        await Promise.race([Promise.allSettled(promises), timeoutPromise]);

        // Предзагружаем критичные изображения
        preloadImages([logo, `/img/lang/${locale.value}.svg`]);

        // Даём небольшую задержку, чтобы Vue успел отрендерить первый кадр
        await new Promise(resolve => setTimeout(resolve, 100));

        loadingStore.stop();
        isLoading.value = false;

        // Скрываем прелоадер только после полной загрузки
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
