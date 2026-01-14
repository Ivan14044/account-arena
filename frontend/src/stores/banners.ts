import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from '../bootstrap'; // Используем настроенный axios из bootstrap

export interface Banner {
    id: number;
    title: string;
    title_en?: string;
    title_uk?: string;
    image_url: string;
    link?: string;
    position: string;
    open_new_tab: boolean;
    order: number;
}

/**
 * Store для управления баннерами с кешированием
 */
export const useBannersStore = defineStore('banners', () => {
    // Баннеры сгруппированные по позициям
    const bannersByPosition = ref<Record<string, Banner[]>>({});

    // Флаги загрузки для каждой позиции
    const loadingByPosition = ref<Record<string, boolean>>({});

    // Ошибки для каждой позиции
    const errorsByPosition = ref<Record<string, string | null>>({});

    const allLoaded = ref(false);
    const isLoadingAll = ref(false);

    /**
     * Загрузить все баннеры одним запросом
     */
    const fetchAll = async (force = false): Promise<Record<string, Banner[]>> => {
        if (allLoaded.value && !force) {
            return bannersByPosition.value;
        }

        if (isLoadingAll.value) {
            return new Promise(resolve => {
                const checkInterval = setInterval(() => {
                    if (!isLoadingAll.value) {
                        clearInterval(checkInterval);
                        resolve(bannersByPosition.value);
                    }
                }, 50);
            });
        }

        isLoadingAll.value = true;
        try {
            const response = await axios.get('/banners/all');
            const data = response.data || {};
            
            // Обновляем все баннеры
            bannersByPosition.value = data;
            allLoaded.value = true;

            // Предзагружаем изображения всех баннеров
            Object.values(data).forEach((banners: any) => {
                preloadBannerImages(banners);
            });

            return data;
        } catch (error) {
            console.error('[BannersStore] Ошибка при загрузке всех баннеров:', error);
            return bannersByPosition.value;
        } finally {
            isLoadingAll.value = false;
        }
    };

    /**
     * Загрузить баннеры для определённой позиции
     * Использует кеширование - повторные вызовы не делают запрос к API
     */
    const fetchBanners = async (position: string): Promise<Banner[]> => {
        // Если все баннеры уже загружены, возвращаем из кеша
        if (bannersByPosition.value[position] || allLoaded.value) {
            return bannersByPosition.value[position] || [];
        }

        // Если уже идёт загрузка, ждём её завершения
        if (loadingByPosition.value[position]) {
            return new Promise(resolve => {
                const checkInterval = setInterval(() => {
                    if (!loadingByPosition.value[position]) {
                        clearInterval(checkInterval);
                        resolve(bannersByPosition.value[position] || []);
                    }
                }, 50);
            });
        }

        // Начинаем загрузку
        loadingByPosition.value[position] = true;
        errorsByPosition.value[position] = null;

        try {
            const response = await axios.get('/banners', {
                params: { position }
            });

            const banners = response.data || [];
            bannersByPosition.value[position] = banners;

            // Предзагружаем изображения баннеров
            preloadBannerImages(banners);

            return banners;
        } catch {
            errorsByPosition.value[position] = 'Failed to load banners';
            bannersByPosition.value[position] = [];
            return [];
        } finally {
            loadingByPosition.value[position] = false;
        }
    };

    /**
     * Предзагрузка изображений баннеров для быстрого отображения
     */
    const preloadBannerImages = (banners: Banner[]) => {
        banners.forEach(banner => {
            if (banner.image_url) {
                // Создаем Image объект для предзагрузки
                const img = new Image();
                img.src = banner.image_url;

                // Также добавляем preload link в head для приоритетной загрузки
                const link = document.createElement('link');
                link.rel = 'preload';
                link.as = 'image';
                link.href = banner.image_url;
                link.setAttribute('fetchpriority', 'high');
                document.head.appendChild(link);
            }
        });
    };

    /**
     * Получить баннеры для позиции (из кеша или undefined если не загружены)
     */
    const getBanners = (position: string): Banner[] | undefined => {
        return bannersByPosition.value[position];
    };

    /**
     * Проверить, загружены ли баннеры для позиции
     */
    const isLoaded = (position: string): boolean => {
        return !!bannersByPosition.value[position];
    };

    /**
     * Проверить, идёт ли загрузка для позиции
     */
    const isLoading = (position: string): boolean => {
        return !!loadingByPosition.value[position];
    };

    /**
     * Получить ошибку для позиции
     */
    const getError = (position: string): string | null => {
        return errorsByPosition.value[position] || null;
    };

    /**
     * Очистить кеш для определённой позиции
     */
    const clearCache = (position?: string) => {
        if (position) {
            delete bannersByPosition.value[position];
            delete loadingByPosition.value[position];
            delete errorsByPosition.value[position];
        } else {
            // Очистить весь кеш
            bannersByPosition.value = {};
            loadingByPosition.value = {};
            errorsByPosition.value = {};
        }
    };

    return {
        bannersByPosition,
        fetchBanners,
        getBanners,
        isLoaded,
        isLoading,
        getError,
        clearCache
    };
});
