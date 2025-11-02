// composables/useArticle.ts
import { computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute } from 'vue-router';
import { useArticlesStore } from '../stores/articles';

export function useArticleDetail() {
    const articlesStore = useArticlesStore();
    const { locale } = useI18n();
    const route = useRoute();
    function toNumericId(value: unknown): number | null {
        const n = Number(value);
        return Number.isFinite(n) ? n : null;
    }

    const initialId = toNumericId(route.params.id);

    // Загружаем данные, если ещё не загружены и id валиден
    if (initialId !== null) {
        articlesStore.fetchArticleById(initialId);
    }

    // Если id может меняться при маршруте
    watch(
        () => route.params.id,
        newId => {
            const numericId = toNumericId(newId);
            if (numericId !== null) {
                articlesStore.fetchArticleById(numericId);
            }
        }
    );

    // computed для текущей статьи с переводом
    const article = computed(() => {
        const currentId = toNumericId(route.params.id);
        if (currentId === null) return null;
        const a = articlesStore.articleById[currentId];
        if (!a) return null;

        const translation = a.translations.find(t => t.locale === locale.value);
        return {
            ...a,
            title: translation?.title ?? 'No title',
            img: a.img,
            date: a.date,
            categories: a.categories
        };
    });

    return { article };
}
