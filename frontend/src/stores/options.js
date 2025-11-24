import { defineStore } from 'pinia';
import axios from '../bootstrap'; // Используем настроенный axios из bootstrap

export const useOptionStore = defineStore('options', {
    state: () => ({
        options: {}, // Changed from [] to {} to match API response format
        isLoaded: false
    }),
    getters: {
        getOption:
            state =>
            (key, defaultValue = null) => {
                // API returns object format: {currency: 'USD', header_menu: '...', ...}
                if (typeof state.options !== 'object' || state.options === null) {
                    return defaultValue;
                }
                return state.options[key] ?? defaultValue;
            }
    },
    actions: {
        async fetchData() {
            if (this.isLoaded) return;

            try {
                const response = await axios.get('/options');
                // API returns object format: {currency: 'USD', header_menu: '...', ...}
                this.options = response.data || {};
                this.isLoaded = true;
            } catch {
                // Ошибка загрузки опций
            }
        }
    }
});
