<template>
    <div
        class="max-w-7xl mx-auto px-4 py-16 sm:px-6 lg:px-8 text-gray-900 dark:text-white relative"
    >
        <div class="max-w-3xl mx-auto">
            <Breadcrumbs :crumbs="breadcrumbCrumbs" />
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
import { useLoadingStore } from '@/stores/loading';
import { useSeo } from '@/composables/useSeo';
import { useStructuredData } from '@/composables/useStructuredData';
import { useHreflang } from '@/composables/useHreflang';
import BackLink from '../../components/layout/BackLink.vue';
import ImageWithFallback from '../../components/ImageWithFallback.vue';
import Breadcrumbs from '../../components/Breadcrumbs.vue';
import type { Category } from '../../types/article';

const route = useRoute();
const router = useRouter();
const id = Number(route.params.id);
const articlesStore = useArticlesStore();
const loadingStore = useLoadingStore();
const { t, locale } = useI18n();

onMounted(async () => {
    // УЛУЧШЕНИЕ: Показываем прелоадер при загрузке статьи
    loadingStore.start();

    try {
        if (!Number.isFinite(id)) {
            return router.replace('/404');
        }

        await articlesStore.fetchArticleById(id);
    } catch (err: any) {
        if (err?.message === '404') {
            return router.replace('/404');
        }
    } finally {
        // Останавливаем прелоадер после загрузки
        loadingStore.stop();
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

const breadcrumbCrumbs = computed(() => {
    const crumbs = [
        { name: t('articles.title'), path: '/articles' }
    ];

    if (article.value?.categories && article.value.categories.length > 0) {
        const category = article.value.categories[0];
        crumbs.push({
            name: resolveCategoryName(category),
            path: `/categories/${category.id}`
        });
    }

    if (article.value) {
        crumbs.push({
            name: article.value.title,
            path: `/articles/${id}`
        });
    }

    return crumbs;
});

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

// SEO мета-теги
useSeo({
    title: () => article.value?.title || 'Статья',
    description: () => {
        if (!article.value?.content) return '';
        // Удаляем HTML теги и ограничиваем длину
        const text = article.value.content.replace(/<[^>]*>/g, '').trim();
        return text ? text.substring(0, 160) : '';
    },
    ogImage: () => article.value?.img || '/img/logo_trans.webp',
    canonical: () => `https://account-arena.com/seo/articles/${id}`,
    ogType: 'article'
});

// Structured Data: Article Schema и BreadcrumbList Schema
useStructuredData(() => {
    if (!article.value) return [];
    
    const articleTitle = article.value.title || 'Статья';
    const articleContent = article.value.content || '';
    const textContent = articleContent.replace(/<[^>]*>/g, '').trim();
    const imageUrl = article.value.img 
        ? (article.value.img.startsWith('http') 
            ? article.value.img 
            : `https://account-arena.com${article.value.img.startsWith('/') ? article.value.img : '/' + article.value.img}`)
        : 'https://account-arena.com/img/logo_trans.webp';
    
    // Article Schema
    const articleData: any = {
        '@context': 'https://schema.org',
        '@type': 'Article',
        'headline': articleTitle,
        'description': textContent ? textContent.substring(0, 160) : articleTitle,
        'datePublished': (article.value as any).created_at || new Date().toISOString(),
        'dateModified': (article.value as any).updated_at || (article.value as any).created_at || new Date().toISOString(),
        'author': {
            '@type': 'Organization',
            'name': 'Account Arena'
        },
        'publisher': {
            '@type': 'Organization',
            'name': 'Account Arena',
            'logo': {
                '@type': 'ImageObject',
                'url': 'https://account-arena.com/img/logo_trans.webp'
            }
        },
        'image': imageUrl
    };
    
    if (article.value.categories && article.value.categories.length > 0) {
        articleData['articleSection'] = resolveCategoryName(article.value.categories[0]);
    }
    
    // BreadcrumbList Schema
    const breadcrumbs: any[] = [
        {
            '@type': 'ListItem',
            'position': 1,
            'name': 'Главная',
            'item': 'https://account-arena.com/'
        },
        {
            '@type': 'ListItem',
            'position': 2,
            'name': 'Статьи',
            'item': 'https://account-arena.com/articles'
        }
    ];
    
    if (article.value.categories && article.value.categories.length > 0) {
        const category = article.value.categories[0];
        breadcrumbs.push({
            '@type': 'ListItem',
            'position': 3,
            'name': resolveCategoryName(category),
            'item': `https://account-arena.com/categories/${category.id}`
        });
    }
    
    breadcrumbs.push({
        '@type': 'ListItem',
        'position': article.value.categories && article.value.categories.length > 0 ? 4 : 3,
        'name': articleTitle,
        'item': `https://account-arena.com/articles/${id}`
    });
    
    const breadcrumbSchema = {
        '@context': 'https://schema.org',
        '@type': 'BreadcrumbList',
        'itemListElement': breadcrumbs
    };
    
    return [articleData, breadcrumbSchema];
});

// Hreflang для мультиязычности
useHreflang(() => `/articles/${id}`);
</script>

<style scoped>
.content :deep(p) {
    margin-bottom: 10px;
}
</style>
