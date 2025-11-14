<template>
    <component :is="layoutComponent" :is-loading="isLoading" />

    <!-- FullPageLoader для session-start (с особой логикой плагина) -->
    <FullPageLoader
        v-if="isStartSessionPage"
        :overlay="!isLoading"
        @call-hide-loader="hideLoader"
    />

    <!-- Глобальный прелоадер для навигации между страницами -->
    <NavigationLoader v-else />

    <!-- Виджет чата поддержки -->
    <SupportChatWidget />
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';

import DefaultLayout from '@/components/layout/DefaultLayout.vue';
import EmptyLayout from '@/components/layout/EmptyLayout.vue';
import FullPageLoader from '@/components/FullPageLoader.vue';
import NavigationLoader from '@/components/NavigationLoader.vue';
import SupportChatWidget from '@/components/SupportChatWidget.vue';

import { useServiceStore } from '@/stores/services';
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

const isStartSessionPage = /^\/session-start(\/\d+)?$/.test(window.location.pathname);

const layoutComponent = computed(() => (isStartSessionPage ? EmptyLayout : DefaultLayout));

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

    // Для страницы session-start используем старую логику с FullPageLoader
    if (isStartSessionPage) {
        // На странице session-start скрываем начальный прелоадер сразу
        hideAppPreloader();
        return;
    }

    try {
        console.log('[APP] Начало инициализации приложения...');
        await authStore.init();
        console.log('[APP] Auth store инициализирован');

        const pageStore = usePageStore();
        const serviceStore = useServiceStore();
        const optionStore = useOptionStore();
        const notificationStore = useNotificationStore();
        const accountsStore = useAccountsStore();

        // ОПТИМИЗАЦИЯ: Загружаем все критичные данные параллельно при старте приложения
        const promises = [
            pageStore
                .fetchData()
                .then(() => console.log('[APP] Pages загружены'))
                .catch(e => console.error('[APP] Ошибка загрузки pages:', e)),
            serviceStore
                .fetchData()
                .then(() => console.log('[APP] Services загружены'))
                .catch(e => console.error('[APP] Ошибка загрузки services:', e)),
            optionStore
                .fetchData()
                .then(() => console.log('[APP] Options загружены'))
                .catch(e => console.error('[APP] Ошибка загрузки options:', e)),
            accountsStore
                .fetchAll()
                .then(() => console.log('[APP] Accounts загружены'))
                .catch(e => console.error('[APP] Ошибка загрузки accounts:', e)), // Предзагрузка товаров
            bannersStore
                .fetchBanners('home_top')
                .then(() => console.log('[APP] Banners home_top загружены'))
                .catch(e => console.error('[APP] Ошибка загрузки banners home_top:', e)), // Предзагрузка обычных баннеров с изображениями
            bannersStore
                .fetchBanners('home_top_wide')
                .then(() => console.log('[APP] Banners home_top_wide загружены'))
                .catch(e => console.error('[APP] Ошибка загрузки banners home_top_wide:', e)) // Предзагрузка широкого баннера с изображением
        ];

        // Уведомления загружаем только для авторизованных пользователей
        if (authStore.isAuthenticated) {
            promises.push(
                notificationStore
                    .fetchData()
                    .then(() => console.log('[APP] Notifications загружены'))
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
        console.log('[APP] Все данные загружены');

        // Предзагружаем критичные изображения
        preloadImages([logo, `/img/lang/${locale.value}.png`]);

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
