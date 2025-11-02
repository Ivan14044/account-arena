<template>
    <swiper
        :modules="[Navigation, Pagination, Autoplay]"
        :slides-per-view="1"
        :space-between="30"
        :autoplay="{
            delay: 5000,
            disableOnInteraction: false
        }"
        :breakpoints="{
            640: {
                slidesPerView: 1,
                spaceBetween: 20
            },
            768: {
                slidesPerView: 2,
                spaceBetween: 20
            },
            1024: {
                slidesPerView: 3,
                spaceBetween: 30
            }
        }"
        :navigation="true"
        :pagination="{ clickable: true }"
        class="mt-8 px-6"
        @slide-change="onSlideChange"
    >
        <swiper-slide v-for="(review, index) in reviews" :key="index" class="pb-12">
            <div
                class="bg-white dark:!bg-gray-800 rounded-xl border border-gray-200 p-5 h-[214px] max-h-[214px] flex flex-col mt-1"
            >
                <div class="flex flex-row items-center mb-5">
                    <div class="relative">
                        <div class="w-[44px] h-[44px] rounded-full overflow-hidden mr-3">
                            <img
                                :src="review.photo"
                                alt="User avatar"
                                class="w-full h-full object-cover"
                            />
                        </div>
                        <img
                            :src="review.logo"
                            alt="Company logo"
                            class="w-[16px] h-[16px] object-cover absolute bottom-0 right-[8px] z-10 rounded-full bg-white border-2 border-white border-solid"
                        />
                    </div>
                    <div class="flex flex-col items-start">
                        <div class="font-medium text-base leading-5 m-0 dark:text-white">
                            {{ review.name }}
                        </div>
                        <div class="flex flex-row gap-0 mt-2">
                            <svg
                                v-for="i in 5"
                                :key="i"
                                width="15"
                                height="14"
                                viewBox="0 0 15 14"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <path
                                    d="M7.95212 10.0742L11.5816 12.25L10.6185 8.14919L13.8251 5.39002L9.60243 5.03419L7.95212 1.16669L6.3018 5.03419L2.0791 5.39002L5.28577 8.14919L4.32259 12.25L7.95212 10.0742Z"
                                    :fill="i <= review.rating ? '#FDCF2F' : '#E5E7EB'"
                                ></path>
                            </svg>
                        </div>
                    </div>
                </div>
                <p class="text-gray-500 dark:text-gray-400 text-base line-clamp-4">
                    {{ review.text }}
                </p>
            </div>
        </swiper-slide>
    </swiper>
</template>

<script setup lang="ts">
import { Swiper, SwiperSlide } from 'swiper/vue';
import { Navigation, Pagination, Autoplay } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

import { computed, onMounted } from 'vue';
import { useContentsStore } from '@/stores/contents';
import { useI18n } from 'vue-i18n';

const { locale } = useI18n();
const contentStore = useContentsStore();

onMounted(() => {
    contentStore.fetchContent('homepage_reviews', locale.value);
});

const reviews = computed(() => {
    return contentStore.itemsByCode['homepage_reviews']?.[locale.value] || [];
});

const onSlideChange = () => {};
</script>

<style scoped>
:deep(.swiper-button-next),
:deep(.swiper-button-prev) {
    @apply text-gray-800 bg-white/50 dark:bg-gray-600 dark:text-white hover:bg-[#0047ff] hover:text-white dark:hover:bg-gray-700 transition-all duration-300 w-10 h-10 rounded-full flex items-center justify-center top-[45%];
    backdrop-filter: blur(4px);
}

:deep(.swiper-button-prev) {
    @apply left-0;
}

:deep(.swiper-button-next) {
    @apply right-0;
}

:deep(.swiper-button-disabled) {
    @apply opacity-0;
}

:deep(.swiper-button-next)::after,
:deep(.swiper-button-prev)::after {
    @apply text-sm;
}

:deep(.swiper-pagination-bullet) {
    @apply w-1.5 h-1.5;
}

:deep(.swiper-pagination-bullet-active) {
    @apply bg-gray-800;
}
</style>
