import { defineStore } from 'pinia';

export const useLoadingStore = defineStore('loading', {
    state: () => ({
        isLoading: false,
        activeRequests: 0
    }),
    actions: {
        start() {
            this.activeRequests++;
            this.isLoading = true;
        },
        stop() {
            this.activeRequests--;
            if (this.activeRequests <= 0) {
                this.activeRequests = 0;
                this.isLoading = false;
            }
        },
        reset() {
            this.activeRequests = 0;
            this.isLoading = false;
        }
    }
});
