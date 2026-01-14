<template>
    <!-- Facebook Pixel Code -->
    <div v-if="pixelId" id="fb-pixel-container"></div>
</template>

<script setup lang="ts">
import { computed, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useOptionStore } from '@/stores/options';

// Расширяем Window интерфейс для TypeScript
declare global {
    interface Window {
        fbq?: (...args: any[]) => void;
        _fbq?: any;
    }
}

const optionStore = useOptionStore();
const route = useRoute();

// Получаем Pixel ID из настроек
const pixelId = computed(() => {
    // Ждем загрузки опций
    if (!optionStore.isLoaded) return null;
    
    const options = optionStore.options;
    if (!options || typeof options !== 'object' || Array.isArray(options)) {
        return null;
    }
    
    const pixelIdValue = options.facebook_pixel_id;
    if (!pixelIdValue || typeof pixelIdValue !== 'string') {
        return null;
    }
    
    const trimmed = pixelIdValue.trim();
    return trimmed || null;
});

onMounted(() => {
    if (!pixelId.value) return;

    // Проверяем, не загружен ли уже Pixel
    if (window.fbq) {
        // Pixel уже загружен, просто инициализируем
        window.fbq('init', pixelId.value);
        window.fbq('track', 'PageView');
        return;
    }

    // Инициализация Facebook Pixel
    !function(f,b,e,v,n,t,s) {
        if(f.fbq)return;
        n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;
        n.push=n;
        n.loaded=!0;
        n.version='2.0';
        n.queue=[];
        t=b.createElement(e);
        t.async=!0;
        t.src=v;
        s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s)
    }(window, document,'script','https://connect.facebook.net/en_US/fbevents.js');

    // Инициализация пикселя с ID после загрузки скрипта
    const initPixel = () => {
        if (window.fbq && pixelId.value) {
            try {
                window.fbq('init', pixelId.value);
                window.fbq('track', 'PageView');
            } catch (error) {
                console.error('[FacebookPixel] Error initializing pixel:', error);
            }
        } else {
            // Повторяем попытку через небольшую задержку
            setTimeout(initPixel, 100);
        }
    };

    // Ждем загрузки скрипта
    setTimeout(initPixel, 100);
});

// Отслеживание событий при навигации
watch(() => route.path, () => {
    if (pixelId.value && window.fbq) {
        window.fbq('track', 'PageView');
    }
});
</script>
