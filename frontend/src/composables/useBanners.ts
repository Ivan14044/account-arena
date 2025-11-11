import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
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

export function useBanners(position: string = 'home_top') {
    const { locale } = useI18n();
    const banners = ref<Banner[]>([]);
    const loading = ref(false);
    const error = ref<string | null>(null);

    /**
     * Get banner title based on current locale
     */
    const getBannerTitle = (banner: Banner): string => {
        if (locale.value === 'en' && banner.title_en) {
            return banner.title_en;
        }
        if (locale.value === 'uk' && banner.title_uk) {
            return banner.title_uk;
        }
        return banner.title;
    };

    /**
     * Load banners from API by position
     */
    const loadBanners = async () => {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.get('/banners', {
                params: { position }
            });
            banners.value = response.data;
        } catch (err) {
            console.error('Error loading banners:', err);
            error.value = 'Failed to load banners';
            banners.value = [];
        } finally {
            loading.value = false;
        }
    };

    /**
     * Load all banners grouped by position
     */
    const loadAllBanners = async () => {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.get('/banners/all');
            return response.data;
        } catch (err) {
            console.error('Error loading all banners:', err);
            error.value = 'Failed to load banners';
            return {};
        } finally {
            loading.value = false;
        }
    };

    /**
     * Handle banner click
     */
    const handleBannerClick = (banner: Banner) => {
        if (!banner.link) return;

        if (banner.open_new_tab) {
            window.open(banner.link, '_blank', 'noopener,noreferrer');
        } else {
            window.location.href = banner.link;
        }
    };

    /**
     * Check if banners are available
     */
    const hasBanners = computed(() => banners.value.length > 0);

    return {
        banners,
        loading,
        error,
        hasBanners,
        getBannerTitle,
        loadBanners,
        loadAllBanners,
        handleBannerClick,
    };
}

