import { defineStore } from 'pinia';
import axios from '../bootstrap'; // Используем настроенный axios из bootstrap

export interface ProductSubcategory {
    id: number;
    name: string;
    translations?: Record<string, Record<string, string>>;
}

export interface ProductCategory {
    id: number;
    type: string;
    image_url?: string | null;
    name: string;
    translations?: Record<string, Record<string, string>>;
    subcategories?: ProductSubcategory[];
}

export const useProductCategoriesStore = defineStore('productCategories', {
    state: () => ({
        list: [] as ProductCategory[],
        loaded: false
    }),

    actions: {
        async fetchAll(force = false) {
            if (this.loaded && !force) return;

            try {
                // Get locale from i18n instance available in the component context
                const response = await axios.get('/categories', {
                    params: { type: 'product' }
                });

                // Laravel Resource Collection может возвращать данные в response.data или напрямую в response.data
                const responseData = response.data?.data || response.data;
                const categories: ProductCategory[] = Array.isArray(responseData) ? responseData : [];

                // Transform categories - name should already be localized from backend
                this.list = categories.map((cat: any) => {
                    return {
                        id: cat.id,
                        type: cat.type || 'product',
                        image_url: cat.image_url || null,
                        name: cat.name || '',
                        translations: cat.translations || {},
                        subcategories: (cat.subcategories || []).map((sub: any) => ({
                            id: sub.id,
                            name: sub.name || '',
                            translations: sub.translations || {}
                        }))
                    };
                });

                this.loaded = true;
            } catch (error) {
                console.error('Error fetching product categories:', error);
                this.list = [];
            }
        },

        getCategoryName(category: ProductCategory, locale: string): string {
            if (
                category.translations &&
                category.translations[locale] &&
                category.translations[locale]['name']
            ) {
                return category.translations[locale]['name'];
            }
            return category.name;
        }
    }
});
