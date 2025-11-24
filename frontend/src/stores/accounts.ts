import { defineStore } from 'pinia';
import axios from '../bootstrap'; // Используем настроенный axios из bootstrap

export interface AccountItem {
    id: number;
    sku?: string; // Артикул товара
    title: string;
    title_en?: string;
    title_uk?: string;
    description?: string;
    description_en?: string;
    description_uk?: string;
    price: number; // coerced to number in transform
    current_price?: number; // Цена с учетом скидки
    discount_percent?: number; // Процент скидки
    has_discount?: boolean; // Есть ли активная скидка
    image_url?: string | null;
    quantity?: number;
    total_quantity?: number;
    sold?: number;
    views?: number; // Количество просмотров
    created_at?: string;
    category?: {
        id?: number;
        name?: string;
    };
}

export const useAccountsStore = defineStore('accounts', {
    state: () => ({
        list: [] as AccountItem[],
        byId: {} as Record<number, AccountItem>,
        loaded: false
    }),

    actions: {
        transform(raw: any): AccountItem | null {
            if (!raw || typeof raw !== 'object') return null;
            const priceNum =
                typeof raw.price === 'number'
                    ? raw.price
                    : Number.parseFloat(String(raw.price ?? '0'));
            return {
                id: Number(raw.id),
                sku: raw.sku, // Артикул товара
                title: raw.title ?? '',
                title_en: raw.title_en,
                title_uk: raw.title_uk,
                description: raw.description,
                description_en: raw.description_en,
                description_uk: raw.description_uk,
                price: Number.isFinite(priceNum) ? priceNum : 0,
                current_price: raw.current_price ? Number(raw.current_price) : undefined,
                discount_percent: raw.discount_percent ? Number(raw.discount_percent) : undefined,
                has_discount: raw.has_discount ?? false,
                image_url: raw.image_url ?? null,
                quantity: Number(raw.quantity ?? 0),
                total_quantity: Number(raw.total_quantity ?? 0),
                sold: Number(raw.sold ?? 0),
                views: Number(raw.views ?? 0), // Количество просмотров
                created_at: raw.created_at,
                category: raw.category || null
            };
        },

        async fetchAll(force = false) {
            if (this.loaded && !force) return;
            const { data } = await axios.get('/accounts');
            const items = Array.isArray(data) ? data : Array.isArray(data?.items) ? data.items : [];
            const list: AccountItem[] = [];
            for (const raw of items) {
                const t = this.transform(raw);
                if (t && Number.isFinite(t.id)) {
                    list.push(t);
                    this.byId[t.id] = t;
                }
            }
            this.list = list;
            this.loaded = true;
        },

        async fetchById(idOrSku: string | number, force = false): Promise<AccountItem | null> {
            // Пытаемся преобразовать в число, если это ID
            const numericId = Number(idOrSku);
            const cacheKey = Number.isFinite(numericId) ? numericId : 0;

            // Проверяем кэш только для числовых ID
            if (!force && cacheKey > 0 && this.byId[cacheKey]) {
                return this.byId[cacheKey];
            }

            // Делаем запрос к API (поддерживает и ID, и артикул)
            const { data } = await axios.get(`/accounts/${idOrSku}`);
            const t = this.transform(data);

            if (t && Number.isFinite(t.id)) {
                // Кэшируем по числовому ID
                this.byId[t.id] = t;
                return t;
            }

            return null;
        },

        async fetchSimilar(idOrSku: string | number): Promise<AccountItem[]> {
            try {
                const { data } = await axios.get(`/accounts/${idOrSku}/similar`);
                const items = Array.isArray(data) ? data : [];
                const list: AccountItem[] = [];
                
                for (const raw of items) {
                    const t = this.transform(raw);
                    if (t && Number.isFinite(t.id)) {
                        list.push(t);
                        // Кэшируем товары в byId для быстрого доступа
                        this.byId[t.id] = t;
                    }
                }
                
                return list;
            } catch (error) {
                console.error('[AccountsStore] Ошибка загрузки похожих товаров:', error);
                return [];
            }
        }
    }
});
