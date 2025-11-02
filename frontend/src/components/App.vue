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

    const promises = [
        pageStore.fetchData(),
        serviceStore.fetchData(),
        optionStore.fetchData(),
        notificationStore.fetchData()
    ];

    await Promise.all(promises);

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
