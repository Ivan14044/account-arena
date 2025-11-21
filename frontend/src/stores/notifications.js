import { defineStore } from 'pinia';
import axios from '../bootstrap';
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

                // Always update items when refreshing
                if (!this.isLoaded || this.items.length === 0) {
                    this.items = items;
                } else {
                    // Merge: update existing items, add new ones at the beginning
                    const existingIds = new Set(this.items.map(i => i.id));
                    const newItemsToAdd = [];

                    items.forEach(newItem => {
                        if (existingIds.has(newItem.id)) {
                            // Update existing item
                            const existingIndex = this.items.findIndex(i => i.id === newItem.id);
                            if (existingIndex >= 0) {
                                this.items[existingIndex] = newItem;
                            }
                        } else {
                            // Collect new items to add at the beginning
                            newItemsToAdd.push(newItem);
                        }
                    });

                    // Add new items at the beginning (newest first)
                    if (newItemsToAdd.length > 0) {
                        this.items = [...newItemsToAdd, ...this.items];
                    }
                }
                this.total = total;
                this.unread = unread;
                this.isLoaded = true;
            } catch {
                // Ошибка загрузки уведомлений
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

                // Update local items immediately
                const now = new Date().toISOString();
                ids.forEach(id => {
                    const item = this.items.find(i => i.id === id);
                    if (item && !item.read_at) {
                        item.read_at = now;
                    }
                });

                // Update unread count
                this.unread = Math.max(0, this.unread - ids.length);

                // Refresh data to sync with server
                this.isLoaded = false;
                await this.fetchData();
            } catch {
                // Ошибка отметки уведомлений как прочитанных
            }
        },

        async markAllAsRead() {
            try {
                const token = localStorage.getItem('token');

                await axios.post(
                    '/notifications/read-all',
                    {},
                    {
                        headers: {
                            Authorization: `Bearer ${token}`
                        }
                    }
                );

                // Update all local items immediately
                const now = new Date().toISOString();
                this.items.forEach(item => {
                    if (!item.read_at) {
                        item.read_at = now;
                    }
                });

                // Update unread count to 0
                this.unread = 0;

                // Refresh data to sync with server
                this.isLoaded = false;
                await this.fetchData();
            } catch {
                // Ошибка отметки всех уведомлений как прочитанных
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
            } catch {
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
