import { defineStore } from 'pinia';
import axios from 'axios';
import { useLoadingStore } from '@/stores/loading';

export const useNotificationStore = defineStore('notifications', {
    state: () => ({
        items: [],
        total: 0,
        unread: 0,
        isLoaded: false
    }),

    actions: {
        async fetchData(limit = 3) {
            if (this.isLoaded) return;

            try {
                const token = localStorage.getItem('token');

                const response = await axios.get('/notifications', {
                    params: { limit },
                    headers: {
                        Authorization: `Bearer ${token}`
                    }
                });

                const { items, total, unread } = response.data;

                if (!this.items.length) {
                    this.items = items;
                }
                this.total = total;
                this.unread = unread;
                this.isLoaded = true;
            } catch (error) {
                console.error('Error fetching notifications:', error);
            }
        },

        async markNotificationsAsRead(ids) {
            try {
                const token = localStorage.getItem('token');

                await axios.post(
                    '/notifications/read',
                    { ids },
                    {
                        headers: {
                            Authorization: `Bearer ${token}`
                        }
                    }
                );

                this.isLoaded = false;
                await this.fetchData();
            } catch (error) {
                console.error('Error marking notifications as read:', error);
            }
        },

        async fetchChunk(limit = 10, offset = 0, loader = true) {
            const loadingStore = useLoadingStore();
            if (loader) {
                loadingStore.start();
            }

            try {
                const token = localStorage.getItem('token');

                const response = await axios.get('/notifications', {
                    params: { limit, offset },
                    headers: {
                        Authorization: `Bearer ${token}`
                    }
                });

                return response.data.items;
            } catch (e) {
                console.error('Error loading chunk:', e);
                return [];
            } finally {
                if (loader) {
                    loadingStore.stop();
                }
            }
        },

        resetStore() {
            this.items = [];
            this.total = 0;
            this.unread = 0;
            this.isLoaded = false;
        }
    }
});
