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

interface BecomeSupplierData {
    welcomeBanner: {
        headline: string;
        subtitle: string;
        ctaButton: string;
    };
    supplierStats: {
        title: string;
        activeSuppliers: string;
        totalSales: string;
        averageRating: string;
        countries: string;
    };
    processSteps: {
        title: string;
        step1: { title: string; description: string };
        step2: { title: string; description: string };
        step3: { title: string; description: string };
        step4: { title: string; description: string };
    };
    digitalGoodsCategories: {
        title: string;
        subtitle: string;
        socialMedia: string;
        gaming: string;
        streaming: string;
        software: string;
        other: string;
    };
    restrictedItems: {
        title: string;
        subtitle: string;
        items: string[];
        contactMessage: string;
    };
    partnerBenefits: {
        title: string;
        benefit1: { title: string; description: string };
        benefit2: { title: string; description: string };
        benefit3: { title: string; description: string };
        benefit4: { title: string; description: string };
    };
    payoutMethods: {
        title: string;
        subtitle: string;
        methods: string[];
        ctaButton: string;
    };
    faq: {
        title: string;
        question1: { question: string; answer: string };
        question2: { question: string; answer: string };
        question3: { question: string; answer: string };
        question4: { question: string; answer: string };
    };
}

interface SiteContentData {
    hero: Record<string, ContentSection>;
    about: Record<string, ContentSection>;
    promote: Record<string, PromoteSection>;
    steps: Record<string, ContentSection>;
    becomeSupplier: Record<string, BecomeSupplierData>;
}

export const useSiteContentStore = defineStore('siteContent', {
    state: () => ({
        content: null as SiteContentData | null,
        isLoaded: false,
        isLoading: false,
        error: null as string | null
    }),

    getters: {
        /**
         * Get content by section and current locale
         */
        getSection:
            state =>
            (section: keyof SiteContentData, locale: string = 'ru') => {
                if (!state.content || !state.content[section]) return null;
                return state.content[section][locale] || state.content[section]['ru'] || null;
            },

        /**
         * Get hero content for current locale
         */
        hero:
            state =>
            (locale: string = 'ru') => {
                return state.content?.hero?.[locale] || state.content?.hero?.ru || null;
            },

        /**
         * Get about content for current locale
         */
        about:
            state =>
            (locale: string = 'ru') => {
                return state.content?.about?.[locale] || state.content?.about?.ru || null;
            },

        /**
         * Get promote content for current locale
         */
        promote:
            state =>
            (locale: string = 'ru') => {
                return state.content?.promote?.[locale] || state.content?.promote?.ru || null;
            },

        /**
         * Get steps content for current locale
         */
        steps:
            state =>
            (locale: string = 'ru') => {
                return state.content?.steps?.[locale] || state.content?.steps?.ru || null;
            },

        /**
         * Get become supplier content for current locale
         */
        becomeSupplier:
            state =>
            (locale: string = 'ru') => {
                return (
                    state.content?.becomeSupplier?.[locale] ||
                    state.content?.becomeSupplier?.ru ||
                    null
                );
            }
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
            } catch {
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
        }
    }
});
