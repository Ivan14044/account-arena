<template v-if="articles.length === 0">
    <div class="text-center mb-16">
        <h2
            class="text-[32px] md:text-[48px] lg:text-[64px] font-medium text-gray-900 dark:text-white mt-3"
            v-html="$t('articles.title')"
        ></h2>
    </div>
    <div>
        <div
            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8 xl:gap-6 px-4 mb-20"
        >
            <ArticleCard
                v-for="article in visibleArticles"
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
        <div class="text-center mt-12">
            <a
                class="cta-button dark:border-gray-300 dark:text-white dark:hover:border-blue-900 pointer-events-auto cursor-pointer"
                @click="router.push('/articles')"
            >
                {{ t('articles.allArticles') }}
            </a>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onBeforeUnmount, ref } from 'vue';
import { useArticlesStore } from '../../stores/articles';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import ArticleCard from '../ArticleCard.vue';

const router = useRouter();
const { t, locale } = useI18n();
const articlesStore = useArticlesStore();

onMounted(() => {
    articlesStore.fetchArticles(6);
    setupResponsiveLimit();
});

const articles = computed(() => articlesStore.getLocalizedArticles(locale.value, 6));

const visibleCount = ref(4);
let teardownMediaListeners: (() => void) | null = null;

function computeVisibleCount() {
    if (typeof window === 'undefined') {
        visibleCount.value = 4;
        return;
    }
    const isMobileOneCol = window.matchMedia('(max-width: 639.98px)').matches;
    const isLgOnly = window.matchMedia('(min-width: 1024px) and (max-width: 1279.98px)').matches;
    visibleCount.value = isMobileOneCol || isLgOnly ? 3 : 4;
}

function setupResponsiveLimit() {
    if (typeof window === 'undefined') return;
    const mobileOneCol = window.matchMedia('(max-width: 639.98px)');
    const lgOnly = window.matchMedia('(min-width: 1024px) and (max-width: 1279.98px)');
    const xlUp = window.matchMedia('(min-width: 1280px)');
    const handler = () => computeVisibleCount();
    computeVisibleCount();
    if (mobileOneCol.addEventListener) {
        mobileOneCol.addEventListener('change', handler);
    } else {
        // @ts-ignore legacy Safari
        mobileOneCol.addListener(handler);
    }
    if (lgOnly.addEventListener) {
        lgOnly.addEventListener('change', handler);
    } else {
        // @ts-ignore legacy Safari
        lgOnly.addListener(handler);
    }
    if (xlUp.addEventListener) {
        xlUp.addEventListener('change', handler);
    } else {
        // @ts-ignore legacy Safari
        xlUp.addListener(handler);
    }
    teardownMediaListeners = () => {
        if (mobileOneCol.removeEventListener) {
            mobileOneCol.removeEventListener('change', handler);
        } else {
            // @ts-ignore legacy Safari
            mobileOneCol.removeListener(handler);
        }
        if (lgOnly.removeEventListener) {
            lgOnly.removeEventListener('change', handler);
        } else {
            // @ts-ignore legacy Safari
            lgOnly.removeListener(handler);
        }
        if (xlUp.removeEventListener) {
            xlUp.removeEventListener('change', handler);
        } else {
            // @ts-ignore legacy Safari
            xlUp.removeListener(handler);
        }
    };
}

onBeforeUnmount(() => {
    if (teardownMediaListeners) teardownMediaListeners();
});

const visibleArticles = computed(() => articles.value.slice(0, visibleCount.value));
</script>
