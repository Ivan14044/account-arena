import { defineStore } from 'pinia';
import axios from 'axios';

export const usePageStore = defineStore('pages', {
    state: () => ({
        pages: [],
        page: null,
        isLoaded: false
    }),
    actions: {
        async fetchData() {
            if (this.isLoaded) return;

            try {
                const response = await axios.get('/pages');
                this.pages = response.data;
                this.isLoaded = true;
            } catch (error) {
                console.error('Error fetching pages:', error);
            }
        },
        setPage(payload) {
            this.page = payload;
        }
    }
});
