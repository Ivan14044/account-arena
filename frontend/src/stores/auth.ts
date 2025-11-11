import { defineStore } from 'pinia';
import axios from '../bootstrap'; // Используем настроенный axios из bootstrap
import i18n from '@/i18n';
import { useLoadingStore } from '@/stores/loading';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: (() => {
            const raw = localStorage.getItem('user');
            const token = localStorage.getItem('token');
            
            try {
                if (!raw || !token) {
                    console.log('[AUTH STORE] Инициализация: нет данных в localStorage');
                    return null;
                }
                
                const parsed = JSON.parse(raw);
                
                // Проверка валидности данных пользователя
                if (!parsed || typeof parsed !== 'object' || !parsed.email) {
                    console.warn('[AUTH STORE] Инициализация: некорректные данные пользователя, очистка');
                    localStorage.removeItem('user');
                    localStorage.removeItem('token');
                    return null;
                }
                
                console.log('[AUTH STORE] Инициализация: пользователь загружен из localStorage', {
                    email: parsed.email,
                    name: parsed.name,
                    hasBalance: 'balance' in parsed
                });
                
                return parsed;
            } catch (error) {
                console.error('[AUTH STORE] Ошибка парсинга данных пользователя:', error);
                localStorage.removeItem('user');
                localStorage.removeItem('token');
                return null;
            }
        })(),
        token: localStorage.getItem('token') || '',
        errors: {},
        userLoaded: false
    }),

    getters: {
        // ИСПРАВЛЕНО: Более строгая проверка авторизации
        isAuthenticated: state => {
            const hasToken = !!state.token && state.token.length > 0;
            const hasUser = !!state.user && typeof state.user === 'object' && !!state.user.email;
            return hasToken && hasUser;
        },
        hasSession: state => !!state.token
    },

    actions: {
        async init() {
            // Устанавливаем токен из localStorage при инициализации
            const savedToken = localStorage.getItem('token');
            if (savedToken) {
                axios.defaults.headers.common['Authorization'] = `Bearer ${savedToken}`;
            }

            if (this.token) {
                axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;

                try {
                    await this.fetchUser();
                } finally {
                    this.userLoaded = true;
                }
            } else {
                this.userLoaded = true;
            }
        },

        async register(formData: any) {
            const loadingStore = useLoadingStore();
            this.errors = {};
            loadingStore.start();

            try {
                const lang = i18n.global.locale.value;
                const response = await axios.post('/register', { ...formData, lang });

                this.token = response.data.token;
                this.user = response.data.user;

                localStorage.setItem('token', this.token);
                localStorage.setItem('user', JSON.stringify(this.user));
                axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;

                this.userLoaded = true;
                return true;
            } catch (error: any) {
                if (
                    error.response?.data?.errors &&
                    typeof error.response.data.errors === 'object'
                ) {
                    this.errors = error.response.data.errors;
                }
                return false;
            } finally {
                loadingStore.stop();
            }
        },

        async login(formData: any) {
            const loadingStore = useLoadingStore();
            this.errors = {};
            loadingStore.start();

            try {
                console.log('[AUTH] Отправка запроса на авторизацию...', { email: formData.email });
                const response = await axios.post('/login', formData);
                
                console.log('[AUTH] Ответ получен:', response.data);

                if (!response.data.token) {
                    console.error('[AUTH] Токен не получен!');
                    this.errors = { email: ['Ошибка авторизации: токен не получен'] };
                    return false;
                }

                if (!response.data.user) {
                    console.error('[AUTH] Данные пользователя не получены!');
                    this.errors = { email: ['Ошибка авторизации: данные пользователя не получены'] };
                    return false;
                }

                this.token = response.data.token;
                this.user = response.data.user;

                console.log('[AUTH] Сохранение токена и пользователя...', {
                    token: this.token.substring(0, 20) + '...',
                    user: this.user.email
                });

                localStorage.setItem('token', this.token);
                localStorage.setItem('user', JSON.stringify(this.user));
                axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;

                const userLang = this.user?.lang ?? null;
                if (userLang) {
                    i18n.global.locale.value = userLang;
                    localStorage.setItem('user-language', userLang);
                }

                this.userLoaded = true;
                
                console.log('[AUTH] Авторизация успешна!', {
                    isAuthenticated: this.isAuthenticated,
                    hasUser: !!this.user,
                    hasToken: !!this.token
                });
                
                return true;
            } catch (error: any) {
                console.error('[AUTH] Ошибка авторизации:', error);
                console.error('[AUTH] Response:', error.response);
                
                // Обработка rate limiting (429 Too Many Requests)
                if (error.response?.status === 429) {
                    this.errors = { 
                        email: ['Слишком много попыток входа. Пожалуйста, подождите минуту и попробуйте снова.'] 
                    };
                } else if (
                    error.response?.data?.errors &&
                    typeof error.response.data.errors === 'object'
                ) {
                    this.errors = error.response.data.errors;
                } else {
                    this.errors = { email: [error.response?.data?.message || 'Ошибка авторизации'] };
                }
                return false;
            } finally {
                loadingStore.stop();
            }
        },

        async forgotPassword(formData: any) {
            const loadingStore = useLoadingStore();
            this.errors = {};
            loadingStore.start();

            try {
                await axios.post('/forgot-password', formData);
                return true;
            } catch (error: any) {
                if (
                    error.response?.data?.errors &&
                    typeof error.response.data.errors === 'object'
                ) {
                    this.errors = error.response.data.errors;
                }
                return false;
            } finally {
                loadingStore.stop();
            }
        },

        async resetPassword(formData: any) {
            const loadingStore = useLoadingStore();
            this.errors = {};
            loadingStore.start();

            try {
                await axios.post('/reset-password', formData);
                return true;
            } catch (error: any) {
                if (
                    error.response?.data?.errors &&
                    typeof error.response.data.errors === 'object'
                ) {
                    this.errors = error.response.data.errors;
                }
                return false;
            } finally {
                loadingStore.stop();
            }
        },

        async logout() {
            try {
                await axios.get('/logout', {
                    headers: { Authorization: `Bearer ${this.token}` }
                });
            } catch (error) {
                console.error(error);
            } finally {
                this.token = '';
                this.user = null;
                this.userLoaded = true;
                localStorage.removeItem('token');
                localStorage.removeItem('user');
                delete axios.defaults.headers.common['Authorization'];
            }
        },

        async fetchUser() {
            if (!this.token) {
                this.userLoaded = true;
                return;
            }
            try {
                const response = await axios.get('/user', {
                    headers: { Authorization: `Bearer ${this.token}` }
                });
                this.user = response.data;
                localStorage.setItem('user', JSON.stringify(this.user));
            } catch (error) {
                console.log(error);
                await this.logout();
            } finally {
                this.userLoaded = true;
            }
        },

        async cancelSubscription(id: number) {
            if (!this.token) return;
            const loadingStore = useLoadingStore();
            loadingStore.start();
            try {
                await axios.post(
                    '/cancel-subscription',
                    { subscription_id: id },
                    {
                        headers: { Authorization: `Bearer ${this.token}` }
                    }
                );
            } catch (error) {
                console.error(error);
            } finally {
                loadingStore.stop();
            }
        },

        async toggleAutoRenew(id: number) {
            if (!this.token) return;
            const loadingStore = useLoadingStore();
            loadingStore.start();
            try {
                await axios.post(
                    '/toggle-auto-renew',
                    { subscription_id: id },
                    {
                        headers: { Authorization: `Bearer ${this.token}` }
                    }
                );
            } catch (error) {
                console.error(error);
                throw error;
            } finally {
                loadingStore.stop();
            }
        },

        async update(formData: any) {
            if (!this.token) return;

            const isOnlyLang = Object.keys(formData).length === 1 && 'lang' in formData;
            this.errors = {};
            const loadingStore = useLoadingStore();
            if (!isOnlyLang) loadingStore.start();

            try {
                const response = await axios.post('/user', formData, {
                    headers: { Authorization: `Bearer ${this.token}` }
                });
                this.user = response.data.user;
                localStorage.setItem('user', JSON.stringify(this.user));
                return true;
            } catch (error: any) {
                if (error.response && error.response.status === 422) {
                    this.errors = error.response.data.errors;
                }
                return false;
            } finally {
                if (!isOnlyLang) loadingStore.stop();
            }
        },

        setToken(token: string) {
            this.token = token;
            localStorage.setItem('token', token);
            axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
        },

        setUser(user: any) {
            this.user = user;
            localStorage.setItem('user', JSON.stringify(user));
        }
    }
});
