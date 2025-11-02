import { defineStore } from 'pinia';
import axios from 'axios';
import { useAuthStore } from './auth';

export interface StartResponse {
    pid: number;
    port: number;
    url: string;
}

export const useBrowserSessionsStore = defineStore('browserSessions', {
    state: () => ({
        url: null as string | null,
        pid: null as number | null,
        port: null as number | null
    }),

    actions: {
        // Start session through backend proxy. serviceId forwarded as service_id
        async startSession(serviceId: number | null): Promise<StartResponse | null> {
            try {
                const authStore = useAuthStore();
                const uiLanguage = (authStore.user as any)?.extension_settings?.uiLanguage || 'en';
                const response = await axios.get('/browser/new', {
                    params: {
                        service_id: serviceId ?? undefined,
                        uiLanguage
                    }
                });
                const { pid, port, url: sessionUrl } = response.data as StartResponse;
                this.pid = pid;
                this.port = port;
                this.url = sessionUrl;
                return { pid, port, url: sessionUrl };
            } catch (error) {
                console.error('Error while starting browser session:', error);
                return null;
            }
        },

        async stopSession(pid?: number) {
            const targetPid = typeof pid === 'number' ? pid : this.pid;
            if (!targetPid) return;
            try {
                await axios.post('/browser/stop', { pid: targetPid });
                if (this.pid === targetPid) {
                    this.pid = null;
                    this.port = null;
                    this.url = null;
                }
            } catch (error) {
                console.error('Error while stopping session by PID:', error);
            }
        },

        async stopSessionByPort(port?: number) {
            const targetPort = typeof port === 'number' ? port : this.port;
            if (!targetPort) return;
            try {
                await axios.post('/browser/stop', { port: targetPort });
                if (this.port === targetPort) {
                    this.pid = null;
                    this.port = null;
                    this.url = null;
                }
            } catch (error) {
                console.error('Error while stopping session by port:', error);
            }
        },

        async stopAllSessions(clean = false) {
            try {
                await axios.post('/browser/stop_all', clean ? { clean: true } : {});
                this.pid = null;
                this.port = null;
                this.url = null;
            } catch (error) {
                console.error('Error while stopping all sessions:', error);
            }
        },

        async listSessions(): Promise<any> {
            try {
                const response = await axios.get('/browser/list');
                return response.data;
            } catch (error) {
                console.error('Error while listing sessions:', error);
                return null;
            }
        }
    }
});
