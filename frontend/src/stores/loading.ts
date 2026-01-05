import { defineStore } from 'pinia';

export const useLoadingStore = defineStore('loading', {
    state: () => ({
        isLoading: false,
        activeRequests: 0,
        message: null as string | null // Поддержка кастомных сообщений
    }),
    actions: {
        start(message: string | null = null) {
            this.activeRequests++;
            this.isLoading = true;
            this.message = message; // Устанавливаем сообщение
        },
        stop() {
            this.activeRequests--;
            if (this.activeRequests <= 0) {
                this.activeRequests = 0;
                this.isLoading = false;
                this.message = null; // Очищаем сообщение
            }
        },
        reset() {
            this.activeRequests = 0;
            this.isLoading = false;
            this.message = null;
        }
    }
});
