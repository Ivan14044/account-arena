import { defineStore } from 'pinia';
import axios from '../bootstrap'; // Используем настроенный axios из bootstrap

interface ContentSection {
    title: string;
    description: string;
    button?: string;
}

interface PromoteItem {
    title: string;
    description: string;
}

interface PromoteSection {
    title: string;
    access: PromoteItem;
    pricing: PromoteItem;
    refund: PromoteItem;
    activation: PromoteItem;
    support: PromoteItem;
    payment: PromoteItem;
}

interface SiteContentData {
    hero: Record<string, ContentSection>;
    about: Record<string, ContentSection>;
    promote: Record<string, PromoteSection>;
    steps: Record<string, ContentSection>;
}

export const useSiteContentStore = defineStore('siteContent', {
    state: () => ({
        content: null as SiteContentData | null,
        isLoaded: false,
        isLoading: false,
        error: null as string | null,
    }),

    getters: {
        /**
         * Get content by section and current locale
         */
        getSection: (state) => (section: keyof SiteContentData, locale: string = 'ru') => {
            if (!state.content || !state.content[section]) return null;
            return state.content[section][locale] || state.content[section]['ru'] || null;
        },

        /**
         * Get hero content for current locale
         */
        hero: (state) => (locale: string = 'ru') => {
            return state.content?.hero?.[locale] || state.content?.hero?.ru || null;
        },

        /**
         * Get about content for current locale
         */
        about: (state) => (locale: string = 'ru') => {
            return state.content?.about?.[locale] || state.content?.about?.ru || null;
        },

        /**
         * Get promote content for current locale
         */
        promote: (state) => (locale: string = 'ru') => {
            return state.content?.promote?.[locale] || state.content?.promote?.ru || null;
        },

        /**
         * Get steps content for current locale
         */
        steps: (state) => (locale: string = 'ru') => {
            return state.content?.steps?.[locale] || state.content?.steps?.ru || null;
        },
    },

    actions: {
        /**
         * Load site content from API
         */
        async loadContent() {
            if (this.isLoaded || this.isLoading) return;

            this.isLoading = true;
            this.error = null;

            try {
                const response = await axios.get('/site-content');
                this.content = response.data;
                this.isLoaded = true;
            } catch (error) {
                this.error = 'Failed to load site content';
                // Keep content as null to use fallback from locale files
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Clear content cache (useful for reloading)
         */
        clearCache() {
            this.content = null;
            this.isLoaded = false;
        },
    },
});




