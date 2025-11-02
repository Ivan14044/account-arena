<template>
    <div
        class="max-w-7xl mx-auto px-4 py-16 sm:px-6 lg:px-8 text-gray-900 dark:text-white relative"
    >
        <div class="max-w-3xl mx-auto">
            <div class="flex items-start justify-between mt-6 mb-10">
                <h1
                    v-if="article"
                    class="text-2xl font-medium md:text-4xl md:font-light text-dark dark:text-white min-w-0 truncate"
                    :title="article.title"
                >
                    {{ article.title }}
                </h1>
                <BackLink class="self-start" />
            </div>

            <div
                v-if="article"
                class="overflow-hidden rounded-2xl bg-white/80 dark:bg-white/[0.03] backdrop-blur-xl border border-black/10 dark:border-white/[0.08] shadow-lg"
            >
                <div class="relative aspect-video overflow-hidden bg-gray-100 dark:bg-white/[0.04]">
                    <ImageWithFallback
                        :src="articleImage"
                        :alt="article.title"
                        class="w-full h-full object-contain"
                    />
                </div>

                <div class="p-6">
                    <h1 class="text-3xl md:text-4xl font-semibold mb-5">{{ article.title }}</h1>
                    <div class="mb-5 flex flex-wrap gap-1">
                        <router-link
                            v-for="category in article.categories"
                            :key="category.id"
                            :to="`/categories/${category.id}`"
                            class="inline-flex items-center px-2 py-1 text-xs rounded border shadow-sm backdrop-blur-sm bg-black/50 text-white hover:bg-black/60 dark:bg-white/10 dark:hover:bg-white/20 cursor-pointer transition-colors"
                            :aria-label="`Open category ${resolveCategoryName(category)}`"
                        >
                            {{ resolveCategoryName(category) }}
                        </router-link>
                    </div>

                    <div class="prose prose-neutral dark:prose-invert max-w-none relative">
                        <div class="content" v-html="article.content"></div>
                        <div class="text-xs text-gray-500 dark:text-gray-300 my-4 float-end">
                            {{ formatDate(article.date) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useArticlesStore } from '../../stores/articles';
import BackLink from '../../components/layout/BackLink.vue';
import ImageWithFallback from '../../components/ImageWithFallback.vue';
import type { Category } from '../../types/article';

const route = useRoute();
const router = useRouter();
const id = Number(route.params.id);
const articlesStore = useArticlesStore();
const { locale } = useI18n();

onMounted(async () => {
    if (!Number.isFinite(id)) {
        return router.replace('/404');
    }
    try {
        await articlesStore.fetchArticleById(id);
    } catch (err: any) {
        if (err?.message === '404') {
            return router.replace('/404');
        }
    }
});

const article = computed(() => {
    const data = articlesStore.articleById[id];
    if (!data) return null;

    const translation = data.translations.find(tr => tr.locale === locale.value);
    return {
        ...data,
        title: translation?.title ?? 'No title',
        content: translation?.content ?? '',
        categories: data.categories
    };
});

const articleImage = computed(() => article.value?.img ?? '/img/no-logo.png');

function resolveCategoryName(category: Category): string {
    const current = locale.value;
    const translated = category.translations?.[current]?.name;
    return translated ?? category.name;
}

function formatDate(dateVal: Date | string | number): string {
    try {
        const d = dateVal instanceof Date ? dateVal : new Date(dateVal);
        return d.toLocaleString();
    } catch {
        return '';
    }
}
</script>

<style scoped>
.content :deep(p) {
    margin-bottom: 10px;
}
</style>
