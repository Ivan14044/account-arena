<template>
    <component :is="layoutComponent" :is-loading="isLoading" />
    <FullPageLoader :overlay="!isLoading" @call-hide-loader="hideLoader" />
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';

import DefaultLayout from '@/components/layout/DefaultLayout.vue';
import EmptyLayout from '@/components/layout/EmptyLayout.vue';
import FullPageLoader from '@/components/FullPageLoader.vue';

import { useServiceStore } from '@/stores/services';
import { usePageStore } from '@/stores/pages';
import { useOptionStore } from '@/stores/options';
import { useNotificationStore } from '@/stores/notifications';
import { useLoadingStore } from '@/stores/loading';
import { useAuthStore } from '@/stores/auth';
import { useAccountsStore } from '@/stores/accounts';
import axios from '@/bootstrap';

import logo from '@/assets/logo.webp';

const { locale } = useI18n();
const isLoading = ref(true);
const loadingStore = useLoadingStore();
const authStore = useAuthStore();

const isStartSessionPage = /^\/session-start(\/\d+)?$/.test(window.location.pathname);

const layoutComponent = computed(() => (isStartSessionPage ? EmptyLayout : DefaultLayout));

onMounted(async () => {
    loadingStore.start();
    window.addEventListener('app:hide-loader', hideLoader);

    if (isStartSessionPage) {
        return;
    }

    authStore.init();

    const pageStore = usePageStore();
    const serviceStore = useServiceStore();
    const optionStore = useOptionStore();
    const notificationStore = useNotificationStore();
    const accountsStore = useAccountsStore();

    // ОПТИМИЗАЦИЯ: Загружаем все критичные данные параллельно при старте приложения
    const promises = [
        pageStore.fetchData(),
        serviceStore.fetchData(),
        optionStore.fetchData(),
        notificationStore.fetchData(),
        accountsStore.fetchAll(), // Добавлено: Предзагрузка товаров
    ];

    // Параллельно загружаем баннеры для предзагрузки изображений
    const bannersPromise = axios.get('/banners', { params: { position: 'home_top' } })
        .then(response => {
            // Предзагружаем изображения баннеров
            if (Array.isArray(response.data)) {
                response.data.forEach((banner: any) => {
                    if (banner.image_url) {
                        preloadImages([banner.image_url]);
                    }
                });
            }
        })
        .catch(err => console.error('Error preloading banners:', err));

    await Promise.all([...promises, bannersPromise]);

    preloadImages([logo, `/img/lang/${locale.value}.png`]);

    loadingStore.stop();
    isLoading.value = false;
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
}
</script>
