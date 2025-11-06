<template>
    <div class="flex-1 max-w-lg lg:text-left">
        <div class="about-image-container relative">
            <img
                src="/img/who.webp"
                alt="About Us"
                class="w-full max-w-md rounded-2xl mx-auto"
                loading="lazy"
            />
        </div>
    </div>

    <div class="flex-1 max-w-lg lg:text-left">
        <h2 class="text-4xl md:text-6xl font-normal mb-6 text-gray-900 dark:text-gray-100">
            {{ aboutTitle }}
        </h2>
        <p class="text-lg leading-relaxed text-gray-600 dark:text-gray-300">
            {{ aboutDescription }}
        </p>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useSiteContentStore } from '@/stores/siteContent';

const { t, locale } = useI18n();
const siteContentStore = useSiteContentStore();

const aboutContent = computed(() => siteContentStore.about(locale.value));

const aboutTitle = computed(() => {
    return aboutContent.value?.title || t('about.title');
});

const aboutDescription = computed(() => {
    return aboutContent.value?.description || t('about.description');
});
</script>
