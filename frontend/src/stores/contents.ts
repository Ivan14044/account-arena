import { defineStore } from 'pinia';
import axios from 'axios';

export const useContentsStore = defineStore('contents', {
    state: () => ({
        itemsByCode: {} as Record<string, any>,
        loading: false,
        error: null as string | null
    }),

    actions: {
        async fetchContent(code: string, lang: string) {
            this.loading = true;
            this.error = null;

            try {
                const { data } = await axios.get(`/contents/${code}`, {
                    params: { lang }
                });

                this.itemsByCode[code] = data.items;
            } catch (err: any) {
                this.error = err?.response?.data?.message || 'Failed to fetch content';
                this.itemsByCode[code] = [];
            } finally {
                this.loading = false;
            }
        }
    }
});
