import { defineStore } from 'pinia';
import axios from 'axios';

type PromoResult =
    | { type: 'discount'; discount_percent: number }
    | { type: 'free_access'; services: Array<{ id: number; name?: string; free_days?: number }> };

interface PromoState {
    code: string;
    loading: boolean;
    error: string;
    result: PromoResult | null;
    lastAppliedAt: number | null;
}

export const usePromoStore = defineStore('promo', {
    state: (): PromoState => ({
        code: '',
        loading: false,
        error: '',
        result: null,
        lastAppliedAt: null
    }),
    actions: {
        async apply(inputCode: string) {
            const code = (inputCode || '').trim();
            if (!code) {
                this.error = '';
                return;
            }
            this.loading = true;
            this.error = '';
            this.result = null;
            try {
                const { data } = await axios.post('/promocodes/validate', { code });
                if (data?.type === 'discount') {
                    this.code = code;
                    this.result = { type: 'discount', discount_percent: Number(data.discount_percent || 0) };
                } else if (data?.type === 'free_access') {
                    this.code = code;
                    this.result = { type: 'free_access', services: Array.isArray(data.services) ? data.services : [] };
                } else {
                    this.error = data?.message || 'Invalid promocode';
                }
                this.lastAppliedAt = Date.now();
            } catch (e: any) {
                const status = e?.response?.data?.status;
                const msg = e?.response?.data?.message;
                this.error = msg || (status ? `Promocode ${status}` : 'Invalid promocode');
            } finally {
                this.loading = false;
            }
        },
        clear() {
            this.code = '';
            this.error = '';
            this.result = null;
            this.lastAppliedAt = null;
        }
    },
    persist: true
});


