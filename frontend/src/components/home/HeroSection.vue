<template>
    <div
        class="flex flex-col items-center justify-center relative mt-[30px] sm:mt-0 min-h-[350px] lg:min-h-[600px] xl:min-h-[600px]"
    >
        <div class="w-full max-w-6xl mx-auto pointer-events-none">
            <div class="hero-content flex flex-col items-center">
                <h1
                    class="text-[32px] md:text-[48px] lg:text-[64px] font-medium leading-none text-gray-900 dark:text-white mb-4 text-center"
                    v-html="heroTitle"
                ></h1>
                <p
                    class="description text-gray-700 dark:text-gray-400 mb-6 md:mb-10 leading-6 text-lg text-center"
                    v-html="heroDescription"
                ></p>
                <a
                    class="cta-button dark:border-gray-300 dark:text-white dark:hover:border-blue-900 pointer-events-auto cursor-pointer mb-12"
                    @click.prevent="scrollToElement('#accounts')"
                >
                    {{ heroButton }}
                </a>
                
                <!-- Ad Banners - комбинация реальных баннеров и плейсхолдеров -->
                <div class="ad-banners-grid w-full mt-8">
                    <template v-for="(item, index) in displayBanners" :key="index">
                        <!-- Реальный баннер из админки -->
                        <a
                            v-if="item.type === 'real'"
                            :href="item.banner.link || '#'"
                            :target="item.banner.open_new_tab ? '_blank' : '_self'"
                            :rel="item.banner.open_new_tab ? 'noopener noreferrer' : ''"
                            class="ad-banner pointer-events-auto"
                            :class="{ 'cursor-pointer': item.banner.link, 'cursor-default': !item.banner.link }"
                            @click.prevent="item.banner.link ? handleBannerClick(item.banner) : null"
                        >
                            <div class="banner-image-wrapper">
                                <img 
                                    :src="item.banner.image_url" 
                                    :alt="getBannerTitle(item.banner)"
                                    class="banner-image"
                                    loading="eager"
                                    fetchpriority="high"
                                />
                            </div>
                        </a>
                        <!-- Плейсхолдер -->
                        <div
                            v-else
                            class="ad-banner"
                        >
                            <div class="banner-content">
                                <span class="banner-text">{{ item.text }}</span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
        <button
            class="dark:bg-gray-700 dark:hover:bg-gray-600 border border-gray-300 absolute bottom-[-60px] sm:bottom-[-50px] right-[50%] translate-x-[50%] pointer-events-auto cursor-pointer rounded-full shadow w-10 h-10 flex items-center justify-center transition hover:bg-gray-100"
            aria-label="Scroll to store"
            @click.prevent="scrollToElement('#accounts')"
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
                    d="M19 9l-7 7-7-7"
                />
            </svg>
        </button>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { scrollToElement } from '@/utils/scrollToElement';
import { useI18n } from 'vue-i18n';
import { useBanners } from '@/composables/useBanners';
import { useSiteContentStore } from '@/stores/siteContent';

const { t, locale } = useI18n();
const siteContentStore = useSiteContentStore();

// Use banners composable for 'home_top' position
const { banners, getBannerTitle, loadBanners, handleBannerClick } = useBanners('home_top');

// Get content from store or fallback to i18n
const heroContent = computed(() => siteContentStore.hero(locale.value));

const heroTitle = computed(() => {
    return heroContent.value?.title || t('hero.title');
});

const heroDescription = computed(() => {
    return heroContent.value?.description || t('hero.description');
});

const heroButton = computed(() => {
    return heroContent.value?.button || t('hero.button');
});

// Placeholders
const placeholderTexts = computed(() => [
    t('adBanners.banner1'),
    t('adBanners.banner2'),
    t('adBanners.banner3'),
    t('adBanners.banner4'),
]);

/**
 * Комбинируем реальные баннеры с плейсхолдерами по их позициям (order)
 * Если баннер имеет order=1, он займет первую позицию
 * Если баннер имеет order=3, он займет третью позицию
 * Остальные позиции - плейсхолдеры
 */
const displayBanners = computed(() => {
    const maxBanners = 4;
    const result = [];

    // Создаем мапу баннеров по их order
    const bannersByOrder = new Map();
    banners.value.forEach(banner => {
        bannersByOrder.set(banner.order, banner);
    });

    // Заполняем 4 позиции
    for (let i = 1; i <= maxBanners; i++) {
        if (bannersByOrder.has(i)) {
            // На этой позиции есть реальный баннер
            result.push({
                type: 'real',
                banner: bannersByOrder.get(i)
            });
        } else {
            // Показываем плейсхолдер
            result.push({
                type: 'placeholder',
                text: placeholderTexts.value[i - 1] // i-1 потому что массив с 0
            });
        }
    }

    return result;
});

// Предзагрузка изображений баннеров
const preloadBannerImages = () => {
    banners.value.forEach(banner => {
        if (banner.image_url) {
            const link = document.createElement('link');
            link.rel = 'preload';
            link.as = 'image';
            link.href = banner.image_url;
            link.fetchPriority = 'high';
            document.head.appendChild(link);
        }
    });
};

onMounted(async () => {
    // Загружаем баннеры с предзагрузкой изображений
    await loadBanners();
    
    // Предзагружаем изображения баннеров после получения данных
    preloadBannerImages();
    
    // Site content загружается быстро
    if (!siteContentStore.loaded) {
        await siteContentStore.loadContent();
    }
});
</script>

<style scoped>
.hero-content {
    text-align: center;
}

.ad-banners-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 24px;
    max-width: 100%;
}

.ad-banner {
    background: #ffffff;
    border-radius: 20px;
    box-shadow: 0 6px 25px rgba(0, 0, 0, 0.1);
    padding: 0; /* Убираем padding для изображений */
    min-height: 160px;
    height: 200px; /* Фиксированная высота для всех баннеров */
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    border: 1px solid #e5e7eb;
    overflow: hidden; /* Важно для правильного отображения изображений */
    position: relative;
}

.dark .ad-banner {
    background: #1f2937;
    border-color: #374151;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.ad-banner:hover {
    transform: translateY(-6px);
    box-shadow: 0 10px 35px rgba(0, 0, 0, 0.15);
}

.dark .ad-banner:hover {
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.4);
}

/* Стили для плейсхолдеров */
.ad-banner .banner-content {
    padding: 32px 24px; /* Padding только для плейсхолдеров */
}

.banner-content {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.banner-text {
    display: inline-block;
    padding: 14px 28px;
    background: #f9fafb;
    border: 2px solid #e5e7eb;
    border-radius: 9999px;
    color: #6b7280;
    font-size: 15px;
    font-weight: 600;
    text-align: center;
    font-family: 'SFT Schrifted Sans', sans-serif;
    transition: all 0.3s ease;
}

.dark .banner-text {
    background: #374151;
    border-color: #4b5563;
    color: #9ca3af;
}

.ad-banner:hover .banner-text {
    border-color: #6c5ce7;
    color: #6c5ce7;
}

.banner-image-wrapper {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.banner-image {
    width: 100%;
    height: 100%;
    object-fit: cover; /* Изображение заполнит контейнер с сохранением пропорций */
    border-radius: 20px;
    transition: transform 0.3s ease;
}

/* Альтернативный вариант: object-fit: contain для полного отображения */
.banner-image.contain {
    object-fit: contain;
    padding: 10px;
}

.ad-banner:hover .banner-image {
    transform: scale(1.05);
}

/* Tablet: 2 columns */
@media (max-width: 1024px) {
    .ad-banners-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }
    
    .ad-banner {
        height: 180px;
    }
    
    .ad-banner .banner-content {
        padding: 28px 20px;
    }
}

/* Mobile: 1 column */
@media (max-width: 640px) {
    .ad-banners-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    
    .ad-banner {
        height: 160px;
    }
    
    .ad-banner .banner-content {
        padding: 24px 20px;
    }
    
    .banner-text {
        font-size: 14px;
        padding: 12px 24px;
    }
}
</style>
