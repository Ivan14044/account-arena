<template>
    <div
        class="article-card group h-full flex flex-col relative overflow-hidden rounded-2xl bg-white/20 dark:bg-white/[0.02] backdrop-blur-xl border border-black/10 dark:border-white/[0.08] hover:border-black/20 dark:hover:border-white/[0.15] transition-all duration-500 shadow-lg before:absolute before:inset-0 before:bg-gradient-to-br before:from-white/60 dark:before:from-white/[0.08] before:to-transparent before:pointer-events-none cursor-pointer"
        role="link"
        tabindex="0"
        @click="goToArticle"
        @keydown.enter="goToArticle"
        :aria-label="title"
    >
        <div class="relative aspect-video overflow-hidden bg-gray-100 dark:bg-white/[0.04]">
            <ImageWithFallback :src="imageUrl" :alt="title" class="w-full h-full object-contain" />
        </div>

        <div
            class="relative p-4 flex flex-col flex-1 bg-gradient-to-t from-black/[0.01] to-transparent dark:from-black/[0.02] backdrop-blur-sm"
        >
            <h3
                class="text-xl font-semibold text-gray-900 dark:text-white mb-2 line-clamp-2"
                style="max-height: 150px"
            >
                {{ title }}
            </h3>
            <p v-if="short" class="text-sm text-gray-700 dark:text-gray-300 line-clamp-2 mb-0">
                {{ short }}
            </p>
            <div class="flex flex-wrap gap-1 my-3">
                <router-link
                    v-for="category in categories"
                    :key="category.id"
                    :to="`/categories/${category.id}`"
                    class="inline-flex items-center px-2 py-1 text-xs rounded border shadow-sm backdrop-blur-sm bg-black/50 text-white hover:bg-black/60 dark:bg-white/10 dark:hover:bg-white/20 cursor-pointer transition-colors"
                    :aria-label="`Open category ${resolveCategoryName(category)}`"
                    @click.stop
                >
                    {{ resolveCategoryName(category) }}
                </router-link>
            </div>
            <div class="flex items-center justify-end gap-2 mt-auto">
                <span
                    class="text-sm text-gray-700 dark:text-white/80 group-hover:text-gray-900 dark:group-hover:text-white transition-colors duration-300"
                    >{{ t('articles.readMore') }}</span
                >
                <svg
                    class="w-4 h-4 text-gray-600 dark:text-white/60 group-hover:text-gray-900 dark:group-hover:text-white transition-all duration-300"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M5 12h14" />
                    <path d="m12 5 7 7-7 7" />
                </svg>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import ImageWithFallback from './ImageWithFallback.vue';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';

const { t, locale } = useI18n();
const router = useRouter();

import type { Category } from '../types/article';

const props = defineProps<{
    title: string;
    excerpt: string;
    categories: Category[];
    imageUrl: string;
    href: string;
    date: string;
    short?: string;
}>();

function resolveCategoryName(category: Category): string {
    const current = locale.value;
    const translated = category.translations?.[current]?.name;
    return translated ?? category.name;
}

function goToArticle() {
    router.push(props.href);
}
</script>

<style scoped>
.article-card {
    transition:
        transform 0.3s ease,
        box-shadow 0.3s ease;
}

.article-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 25px rgba(0, 0, 0, 0.15);
}

:global(.dark) .article-card:hover {
    box-shadow: 0 12px 25px rgba(0, 0, 0, 0.4);
}

@media (hover: none) and (pointer: coarse) {
    .article-card:hover {
        transform: none;
    }
}
</style>
