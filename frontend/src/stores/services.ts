import { defineStore } from 'pinia';
import axios from 'axios';

export interface Services {
    id: number;
    name: string;
    code: string;
    logo: string;
    [key: string]: unknown;
}

interface ServiceState {
    services: Services[];
    isLoaded: boolean;
}

export const useServiceStore = defineStore('services', {
    state: (): ServiceState => ({
        services: [],
        isLoaded: false
    }),

    actions: {
        async fetchData() {
            if (this.isLoaded) return;

            try {
                const response = await axios.get<Services[]>('/services');
                const domain = import.meta.env.VITE_APP_DOMAIN;

                this.services = response.data.map(service => ({
                    ...service,
                    logo: service.logo.startsWith('http')
                        ? service.logo
                        : `${domain}${service.logo}`
                }));

                this.isLoaded = true;
            } catch (error) {
                console.error('Error fetching services:', error);
            }
        },

        getById(id: number): Services | undefined {
            return this.services.find(service => service.id === id);
        }
    }
});
