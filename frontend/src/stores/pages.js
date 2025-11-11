import { defineStore } from 'pinia';
import axios from '../bootstrap'; // Используем настроенный axios из bootstrap

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
                // Ошибка загрузки страниц
            }
        },
        setPage(payload) {
            this.page = payload;
        }
    }
});
