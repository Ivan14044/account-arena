<template>
    <div
        class="flex flex-col items-center justify-center relative mt-[30px] sm:mt-0 min-h-[350px] lg:min-h-[600px] xl:min-h-[600px]"
    >
        <div class="w-full max-w-6xl mx-auto pointer-events-none">
            <div class="hero-content flex flex-col items-center">
                <h1
                    class="text-[32px] md:text-[48px] lg:text-[64px] font-medium leading-none text-gray-900 dark:text-white mb-4 text-center"
                    v-html="$t('hero.title')"
                ></h1>
                <p
                    class="description text-gray-700 dark:text-gray-400 mb-6 md:mb-10 leading-6 text-lg text-center"
                    v-html="$t('hero.description')"
                ></p>
                <a
                    class="cta-button dark:border-gray-300 dark:text-white dark:hover:border-blue-900 pointer-events-auto cursor-pointer mb-12"
                    @click.prevent="scrollToElement('#accounts')"
                >
                    {{ $t('hero.button') }}
                </a>
                
                <!-- Ad Banners -->
                <div class="ad-banners-grid w-full mt-8">
                    <div
                        v-for="(banner, index) in banners"
                        :key="index"
                        class="ad-banner"
                    >
                        <div class="banner-content">
                            <span class="banner-text">{{ banner.text }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <button
            class="dark:bg-gray-700 dark:hover:bg-gray-600 border border-gray-300 absolute bottom-0 sm:bottom-[10px] right-[50%] translate-x-[50%] pointer-events-auto cursor-pointer rounded-full shadow w-10 h-10 flex items-center justify-center transition hover:bg-gray-100"
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
import { computed } from 'vue';
import { scrollToElement } from '@/utils/scrollToElement';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const banners = computed(() => [
    { text: t('adBanners.banner1') },
    { text: t('adBanners.banner2') },
    { text: t('adBanners.banner3') },
    { text: t('adBanners.banner4') },
]);
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
    padding: 32px 24px;
    min-height: 160px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    border: 1px solid #e5e7eb;
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

/* Tablet: 2 columns */
@media (max-width: 1024px) {
    .ad-banners-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }
    
    .ad-banner {
        min-height: 140px;
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
        min-height: 120px;
        padding: 24px 20px;
    }
    
    .banner-text {
        font-size: 14px;
        padding: 12px 24px;
    }
}
</style>
