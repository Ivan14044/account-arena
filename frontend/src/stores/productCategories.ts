import { defineStore } from 'pinia';
import axios from '../bootstrap'; // Используем настроенный axios из bootstrap

export interface ProductCategory {
    id: number;
    type: string;
    image_url?: string | null;
    name: string;
    translations?: Record<string, Record<string, string>>;
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
                const { data } = await axios.get('/categories', {
                    params: { type: 'product' }
                });

                const categories: ProductCategory[] = Array.isArray(data) ? data : [];

                // Transform categories - name should already be localized from backend
                this.list = categories.map((cat: any) => {
                    return {
                        id: cat.id,
                        type: cat.type || 'product',
                        image_url: cat.image_url || null,
                        name: cat.name || '',
                        translations: cat.translations || {}
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
