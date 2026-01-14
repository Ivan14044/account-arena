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
    if (!optionStore.isLoaded) return null;
    
    const options = optionStore.options;
    let optionsObj: Record<string, any>;
    
    if (Array.isArray(options)) {
        optionsObj = {};
        options.forEach(option => {
            if (option && option.key) {
                optionsObj[option.key] = option.value;
            }
        });
    } else if (typeof options === 'object' && options !== null) {
        optionsObj = options;
    } else {
        return null;
    }
    
    const pixelIdValue = optionsObj.facebook_pixel_id;
    return pixelIdValue && pixelIdValue.trim() ? pixelIdValue.trim() : null;
});

onMounted(() => {
    if (!pixelId.value) return;

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

    // Инициализация пикселя с ID
    if (window.fbq && pixelId.value) {
        window.fbq('init', pixelId.value);
        window.fbq('track', 'PageView');
    }
});

// Отслеживание событий при навигации
watch(() => route.path, () => {
    if (pixelId.value && window.fbq) {
        window.fbq('track', 'PageView');
    }
});
</script>
