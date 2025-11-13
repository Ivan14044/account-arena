import { defineStore } from 'pinia';
import axios from '../bootstrap'; // Используем настроенный axios из bootstrap

export const useOptionStore = defineStore('options', {
    state: () => ({
        options: [],
        isLoaded: false
    }),
    getters: {
        getOption:
            state =>
            (key, defaultValue = null) => {
                if (!Array.isArray(state.options)) return defaultValue;
                const option = state.options.find(opt => opt.key === key);
                return option?.value ?? defaultValue;
            }
    },
    actions: {
        async fetchData() {
            if (this.isLoaded) return;

            try {
                const response = await axios.get('/options');
                this.options = response.data;
                this.isLoaded = true;
            } catch {
                // Ошибка загрузки опций
            }
        }
    }
});
