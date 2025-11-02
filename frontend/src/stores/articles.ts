/// <reference types="vite/client" />
import { defineStore } from 'pinia';
import axios from 'axios';
import type { Article, Category, Translation } from '../types/article';

type ArticleMapById = Record<number, Article>;
type ArticlesByKey = Record<string, Article[]>;
type LoadedByKey = Record<string, boolean>;

interface LoadedFlags {
    categories: boolean;
}

export const useArticlesStore = defineStore('articles', {
    state: () => ({
        articleById: {} as ArticleMapById,
        categories: [] as Category[],
        articlesByKey: {} as ArticlesByKey,
        loadedByKey: {} as LoadedByKey,
        loaded: {
            categories: false
        } as LoadedFlags,
        paginatedItems: [] as Article[],
        paginatedTotal: 0,
        paginatedParams: { limit: 12, offset: 0, categoryId: undefined as number | undefined },
        paginatedCache: {} as Record<string, { items: Article[]; total: number }>
    }),

    getters: {
        getArticles:
            state =>
            (limit?: number): Article[] => {
                const key = typeof limit === 'number' ? `l${limit}` : 'all';
                return state.articlesByKey[key] ?? [];
            },

        getLocalizedArticles: state => (locale: string, limit?: number) => {
            const key = typeof limit === 'number' ? `l${limit}` : 'all';
            const list = state.articlesByKey[key] ?? [];
            return list
                .filter(a => Number.isFinite(a.id))
                .map(article => {
                    const tr = article.translations.find(t => t.locale === locale);
                    return {
                        ...article,
                        title: tr?.title ?? 'No title',
                        excerpt: tr?.content ? String(tr.content).slice(0, 140) : '',
                        short: tr?.short
                            ? String(tr.short)
                            : tr?.content
                              ? String(tr.content).slice(0, 140)
                              : '',
                        dateString:
                            article.date instanceof Date
                                ? article.date.toISOString()
                                : String(article.date ?? '')
                    };
                });
        },

        getLocalizedPaginatedArticles: state => (locale: string) => {
            return (state.paginatedItems ?? [])
                .filter(a => Number.isFinite(a.id))
                .map(article => {
                    const tr = article.translations.find(t => t.locale === locale);
                    return {
                        ...article,
                        title: tr?.title ?? 'No title',
                        excerpt: tr?.content ? String(tr.content).slice(0, 140) : '',
                        short: tr?.short
                            ? String(tr.short)
                            : tr?.content
                              ? String(tr.content).slice(0, 140)
                              : '',
                        dateString:
                            article.date instanceof Date
                                ? article.date.toISOString()
                                : String(article.date ?? '')
                    };
                });
        },

        getCategoryById: state => (id: number) => state.categories.find(c => c.id === id)
    },

    actions: {
        getPaginatedKey(params: { limit?: number; offset?: number; categoryId?: number }): string {
            const limit = params.limit ?? 12;
            const offset = params.offset ?? 0;
            const categoryId = typeof params.categoryId === 'number' ? params.categoryId : 'all';
            return `l${limit}-o${offset}-c${categoryId}`;
        },

        hasCachedPage(params: { limit?: number; offset?: number; categoryId?: number }): boolean {
            const key = this.getPaginatedKey(params);
            return Boolean(this.paginatedCache[key]);
        },

        normalizeImagePath(input: unknown): string | null {
            if (!input || typeof input !== 'string') return null;
            const domain = import.meta.env.VITE_APP_DOMAIN;
            if (!domain) return input;
            return input.startsWith('http') ? input : `${domain}${input}`;
        },

        transformTranslations(rawTranslations: unknown): Translation[] {
            if (Array.isArray(rawTranslations)) {
                return rawTranslations.map((rawTranslation: any) => ({
                    locale: rawTranslation.locale,
                    title: rawTranslation.title,
                    content: rawTranslation.content,
                    short: rawTranslation.short
                }));
            }
            if (rawTranslations && typeof rawTranslations === 'object') {
                const obj = rawTranslations as Record<string, Record<string, unknown>>;
                return Object.entries(obj).map(([locale, data]) => ({
                    locale,
                    title: (data?.title as string) ?? '',
                    content: (data?.content as string) ?? '',
                    short: (data?.short as string) ?? ''
                }));
            }
            return [];
        },

        transformCategories(rawCategories: any[]): Category[] {
            if (!Array.isArray(rawCategories)) return [];
            return rawCategories.map((rawCategory: any) => ({
                id: rawCategory.id,
                name: rawCategory.name,
                translations: rawCategory.translations ?? undefined
            }));
        },

        transformListItem(rawArticle: any): Article {
            const categories = this.transformCategories(
                rawArticle.category ?? rawArticle.categories ?? []
            );
            const translations = this.transformTranslations(rawArticle.translations ?? []);
            const dateSource = rawArticle.date ?? rawArticle.created_at ?? new Date().toISOString();
            const img = this.normalizeImagePath(rawArticle.img);
            return {
                id: Number(rawArticle.id),
                categories,
                translations,
                img,
                date: new Date(dateSource)
            };
        },

        transformDetailData(rawArticleData: any): Article {
            const categories = this.transformCategories(
                rawArticleData.categories ?? rawArticleData.category ?? []
            );
            const translations = this.transformTranslations(rawArticleData.translations ?? []);
            const dateSource =
                rawArticleData.date ?? rawArticleData.created_at ?? new Date().toISOString();
            const img = this.normalizeImagePath(rawArticleData.img);
            return {
                id: Number(rawArticleData.id),
                categories,
                translations,
                img,
                date: new Date(dateSource)
            };
        },

        async fetchArticles(limit?: number) {
            const key = typeof limit === 'number' ? `l${limit}` : 'all';
            if (this.loadedByKey[key]) return;
            try {
                const response = await axios.get('/articles', {
                    params: typeof limit === 'number' ? { limit } : undefined
                });
                const responseData = response.data;
                const items = Array.isArray(responseData?.items)
                    ? responseData.items
                    : Array.isArray(responseData)
                        ? responseData
                        : [];
                const list = items
                    .map((rawArticle: any) => this.transformListItem(rawArticle))
                    .filter((a: Article) => Number.isFinite(a.id));
                this.articlesByKey[key] = list;
                // Populate per-ID cache from list results to avoid refetching on detail
                for (const article of list) {
                    const id = Number(article.id);
                    if (!Number.isFinite(id)) continue;
                    if (!this.articleById[id]) {
                        this.articleById[id] = article;
                    }
                }
                this.loadedByKey[key] = true;
            } catch (fetchError) {
                console.error('Error fetching articles:', fetchError);
            }
        },

        async fetchArticlesPage(params: { limit?: number; offset?: number; categoryId?: number }) {
            const limit = params.limit ?? 12;
            const offset = params.offset ?? 0;
            const categoryId = params.categoryId;
            const key = this.getPaginatedKey({ limit, offset, categoryId });

            // Serve from cache if available
            const cached = this.paginatedCache[key];
            if (cached) {
                this.paginatedItems = cached.items;
                this.paginatedTotal = cached.total;
                this.paginatedParams = { limit, offset, categoryId } as any;
                // Populate per-ID cache from paginated results to avoid refetching on detail
                for (const article of this.paginatedItems) {
                    const id = Number(article.id);
                    if (!Number.isFinite(id)) continue;
                    if (!this.articleById[id]) {
                        this.articleById[id] = article;
                    }
                }
                return;
            }

            try {
                const response = await axios.get('/articles', {
                    params: {
                        limit,
                        offset,
                        ...(categoryId ? { category_id: categoryId } : {})
                    }
                });
                const responseData = response.data;
                const total = Number(responseData?.total ?? 0);
                const items = Array.isArray(responseData?.items) ? responseData.items : [];
                this.paginatedItems = items
                    .map((rawArticle: any) => this.transformListItem(rawArticle))
                    .filter((a: Article) => Number.isFinite(a.id));
                // Populate per-ID cache from paginated results to avoid refetching on detail
                for (const article of this.paginatedItems) {
                    const id = Number(article.id);
                    if (!Number.isFinite(id)) continue;
                    if (!this.articleById[id]) {
                        this.articleById[id] = article;
                    }
                }
                this.paginatedTotal = total;
                this.paginatedParams = { limit, offset, categoryId } as any;
                // Save to cache
                this.paginatedCache[key] = { items: this.paginatedItems, total: this.paginatedTotal };
            } catch (fetchError) {
                console.error('Error fetching paginated articles:', fetchError);
                this.paginatedItems = [];
                this.paginatedTotal = 0;
                this.paginatedParams = { limit: 12, offset: 0, categoryId: params.categoryId } as any;
            }
        },

        async fetchArticleById(id: string | number) {
            const numericId = Number(id);
            if (this.articleById[numericId]) return;
            try {
                const articleResponse = await axios.get(`/articles/${numericId}`);
                const articleData = articleResponse.data ?? null;
                if (!articleData) return;
                const article = this.transformDetailData(articleData);
                this.articleById[numericId] = article;
            } catch (fetchError: any) {
                if (fetchError?.response?.status === 404) {
                    throw new Error('404');
                }
                console.error('Error fetching article detail:', fetchError);
            }
        },

        async fetchCategories() {
            if (this.loaded.categories) return;
            try {
                const response = await axios.get('/categories');
                const responseData = response.data;
                this.categories = Array.isArray(responseData)
                    ? this.transformCategories(responseData)
                    : [];
                this.loaded.categories = true;
            } catch (fetchError) {
                console.error('Error fetching categories:', fetchError);
            }
        }
    }
});
