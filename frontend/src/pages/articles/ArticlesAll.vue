<template>
    <div class="max-w-7xl mx-auto px-4 py-16 sm:px-6 lg:px-8 relative">
        <div class="flex items-start justify-between mt-6 mb-10">
            <div>
                <h1
                    class="text-2xl font-medium md:text-4xl md:font-light text-dark dark:text-white"
                    v-html="pageTitle"
                ></h1>
                <p v-if="!isCategoryPage" class="mt-3 text-gray-600 dark:text-gray-300">
                    {{ $t('articles.description') }}
                </p>
                <div
                    v-else-if="categoryTextHtml"
                    class="mt-3 text-gray-600 dark:text-gray-300"
                    v-html="categoryTextHtml"
                ></div>
            </div>
            <BackLink class="self-start" />
        </div>
        <div v-if="articles.length > 0">
            <div
                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8 xl:gap-6"
            >
                <ArticleCard
                    v-for="article in articles"
                    :key="article.id"
                    :title="article.title"
                    :excerpt="article.excerpt"
                    :categories="article.categories"
                    :image-url="article.img ?? '/img/no-logo.png'"
                    :href="`/articles/${article.id}`"
                    :date="article.dateString"
                    :short="article.short"
                />
            </div>

            <div class="flex items-center justify-center gap-2 mt-10">
                <button
                    v-if="currentPage > 1"
                    class="inline-flex items-center justify-center leading-none px-3 py-1.5 rounded border border-gray-300 dark:!border-gray-600/60 text-sm text-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-400/30 transition disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-transparent disabled:hover:dark:bg-transparent"
                    :disabled="isLoading"
                    @click="onPrev"
                    aria-label="Previous page"
                >
                    ←
                </button>

                <button
                    v-for="btn in pageButtons"
                    :key="`p-${btn}`"
                    class="inline-flex items-center justify-center leading-none px-3 py-1.5 rounded border text-sm transition min-w-[40px] disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-transparent disabled:hover:dark:bg-transparent"
                    :class="[
                        btn === '...'
                            ? 'cursor-default border-transparent text-gray-400'
                            : btn === currentPage
                              ? 'border-gray-900 bg-gray-900 text-white dark:!bg-gray-300 dark:!text-gray-900 dark:!border-gray-600/60'
                              : 'border-gray-300 dark:!border-gray-600/60 text-gray-700 dark:!text-white hover:bg-gray-200 dark:hover:bg-gray-400/30'
                    ]"
                    :disabled="btn === '...' || isLoading"
                    @click="btn !== '...' && goToPage(Number(btn))"
                >
                    {{ btn }}
                </button>

                <button
                    v-if="currentPage < totalPages"
                    class="inline-flex items-center justify-center leading-none px-3 py-1.5 rounded border border-gray-300 dark:!border-gray-600/60 text-sm text-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-400/30 transition disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-transparent disabled:hover:dark:bg-transparent"
                    :disabled="isLoading"
                    @click="onNext"
                    aria-label="Next page"
                >
                    →
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useArticlesStore } from '../../stores/articles';
import ArticleCard from '../../components/ArticleCard.vue';
import { useRoute, useRouter } from 'vue-router';
import BackLink from '../../components/layout/BackLink.vue';
import { useLoadingStore } from '../../stores/loading';

const { t, locale } = useI18n();
const articlesStore = useArticlesStore();
const loadingStore = useLoadingStore();
const route = useRoute();
const router = useRouter();

const limit = computed(() => Number(route.query.limit ?? 12));
const rawOffset = computed(() => Number(route.query.offset ?? 0));
const routePage = computed(() => {
    const pParam = route.params.page !== undefined ? Number(route.params.page) : undefined;
    if (typeof pParam === 'number' && Number.isFinite(pParam) && pParam >= 1) return pParam;
    const l = Math.max(1, Number(limit.value) || 12);
    const off = Math.max(0, Number(rawOffset.value) || 0);
    return Math.floor(off / l) + 1;
});
const offset = computed(() => {
    const l = Math.max(1, Number(limit.value) || 12);
    return (routePage.value - 1) * l;
});
const categoryId = computed(() => {
    const paramVal = route.params.id !== undefined ? Number(route.params.id) : undefined;
    if (typeof paramVal === 'number' && Number.isFinite(paramVal)) return paramVal;
    const queryVal =
        route.query.category_id !== undefined ? Number(route.query.category_id) : undefined;
    if (typeof queryVal === 'number' && Number.isFinite(queryVal)) return queryVal;
    return undefined;
});
// Use store's confirmed paginated params (update after data load) to avoid UI flicker
const pageIndex = computed(() => {
    const confirmed = Number(articlesStore.paginatedParams?.offset ?? 0);
    const l = Math.max(1, Number(articlesStore.paginatedParams?.limit ?? limit.value) || 12);
    return Math.floor(confirmed / l);
});
const isCategoryPage = computed(() => typeof route.params.id !== 'undefined');
const category = computed(() =>
    typeof categoryId.value === 'number'
        ? articlesStore.getCategoryById(categoryId.value)
        : undefined
);
const categoryTitle = computed(() => {
    const cat = category.value as any;
    if (!cat) return '';
    const trName = cat.translations?.[locale.value]?.name;
    return trName ?? cat.name ?? '';
});
const pageTitle = computed(() => {
    return categoryId.value
        ? categoryTitle.value || String(t('articles.title'))
        : String(t('articles.title'));
});

const categoryTextHtml = computed(() => {
    const cat = category.value as any;
    if (!cat) return '';
    const tr = cat.translations?.[locale.value];
    const text = tr?.text;
    if (typeof text !== 'string') return '';
    const trimmed = text.trim();
    return trimmed.length > 0 ? trimmed : '';
});

onMounted(async () => {
    // If page is cached for current params, avoid showing loader
    const willUseCache = articlesStore.hasCachedPage({
        limit: limit.value,
        offset: offset.value,
        categoryId: categoryId.value
    });
    if (!willUseCache) loadingStore.start();
    try {
        // Redirect only if legacy query params exist
        if (typeof route.query.offset !== 'undefined' || typeof route.query.limit !== 'undefined') {
            const newQuery = { ...route.query } as Record<string, any>;
            delete newQuery.offset;
            delete newQuery.limit;
            delete (newQuery as any).category_id;
            const p = routePage.value;
            const isCategory = typeof route.params.id !== 'undefined';
            const targetPath = p <= 1
                ? (isCategory ? `/categories/${route.params.id}` : `/articles`)
                : (isCategory ? `/categories/${route.params.id}/page/${p}` : `/articles/page/${p}`);
            await router.replace({ path: targetPath, query: newQuery });
        }
        if (!articlesStore.loaded.categories) await articlesStore.fetchCategories();
        await fetchPage();
        // After data is loaded, scroll to top smoothly (deferred from router)
        requestAnimationFrame(() => {
            try {
                const usedSaved = sessionStorage.getItem('articlesUsedSavedPosition') === '1';
                if (usedSaved) {
                    sessionStorage.removeItem('articlesUsedSavedPosition');
                    return;
                }
            } catch (_) {}
            window.scrollTo({ top: 0, left: 0, behavior: 'smooth' });
        });
    } finally {
        loadingStore.stop();
    }
});

watch([limit, offset, categoryId], () => {
    fetchPageWithLoader();
});

async function fetchPage() {
    await articlesStore.fetchArticlesPage({
        limit: limit.value,
        offset: offset.value,
        categoryId: categoryId.value
    });
}

async function fetchPageWithLoader() {
    // Avoid loader if target page is already cached
    const willUseCache = articlesStore.hasCachedPage({
        limit: limit.value,
        offset: offset.value,
        categoryId: categoryId.value
    });
    if (!willUseCache) loadingStore.start();
    try {
        await fetchPage();
        // After data is loaded for paging/category change, scroll to top smoothly
        requestAnimationFrame(() => {
            try {
                const usedSaved = sessionStorage.getItem('articlesUsedSavedPosition') === '1';
                if (usedSaved) {
                    sessionStorage.removeItem('articlesUsedSavedPosition');
                    return;
                }
            } catch (_) {}
            window.scrollTo({ top: 0, left: 0, behavior: 'smooth' });
        });
    } finally {
        loadingStore.stop();
    }
}

const articles = computed(() => articlesStore.getLocalizedPaginatedArticles(locale.value));
const total = computed(() => articlesStore.paginatedTotal);
const totalPages = computed(() => {
    const l = Math.max(1, Number(limit.value) || 12);
    const t = Math.max(0, Number(total.value) || 0);
    return Math.max(1, Math.ceil(t / l));
});

const isLoading = computed(() => loadingStore.isLoading);

const currentPage = computed(() => {
    const target = routePage.value;
    const l = Math.max(1, Number(articlesStore.paginatedParams?.limit ?? limit.value) || 12);
    const off = Math.max(0, Number(articlesStore.paginatedParams?.offset ?? 0) || 0);
    const confirmed = Math.floor(off / l) + 1;
    // Show confirmed page until store updates to target (prevents early switch)
    return target === confirmed ? target : confirmed;
});
const pageButtons = computed<(number | string)[]>(() => {
    const total = totalPages.value;
    const current = currentPage.value;
    if (total <= 6) {
        return Array.from({ length: total }, (_, i) => i + 1);
    }
    if (current <= 3) {
        return [1, 2, 3, '...', total];
    }
    if (current >= total - 2) {
        return [1, '...', total - 2, total - 1, total];
    }
    return [1, '...', current - 1, current, current + 1, '...', total];
});

function goToPage(p: number) {
    if (!Number.isFinite(p) || p < 1 || p > totalPages.value) return;
    const isCategory = typeof categoryId.value === 'number';
    const basePath = p <= 1
        ? (isCategory ? `/categories/${categoryId.value}` : `/articles`)
        : (isCategory ? `/categories/${categoryId.value}/page/${p}` : `/articles/page/${p}`);
    const newQuery = { ...route.query } as Record<string, any>;
    delete newQuery.offset;
    delete newQuery.limit;
    delete (newQuery as any).category_id;
    router.push({ path: basePath, query: newQuery });
}

function onPrev() {
    const p = Math.max(1, currentPage.value - 1);
    const isCategory = typeof categoryId.value === 'number';
    const basePath = p <= 1
        ? (isCategory ? `/categories/${categoryId.value}` : `/articles`)
        : (isCategory ? `/categories/${categoryId.value}/page/${p}` : `/articles/page/${p}`);
    const newQuery = { ...route.query } as Record<string, any>;
    delete newQuery.offset;
    delete newQuery.limit;
    delete (newQuery as any).category_id;
    router.push({ path: basePath, query: newQuery });
}
function onNext() {
    const p = Math.min(totalPages.value, currentPage.value + 1);
    const isCategory = typeof categoryId.value === 'number';
    const basePath = p <= 1
        ? (isCategory ? `/categories/${categoryId.value}` : `/articles`)
        : (isCategory ? `/categories/${categoryId.value}/page/${p}` : `/articles/page/${p}`);
    const newQuery = { ...route.query } as Record<string, any>;
    delete newQuery.offset;
    delete newQuery.limit;
    delete (newQuery as any).category_id;
    router.push({ path: basePath, query: newQuery });
}
</script>
