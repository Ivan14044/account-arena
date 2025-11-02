import axios from 'axios';
import i18n from './i18n';

const apiBase = import.meta.env.VITE_API_BASE || 'http://localhost:8000/api';

axios.defaults.baseURL = apiBase;
axios.defaults.withCredentials = true;
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

axios.interceptors.request.use((config) => {
    const currentLocale = i18n?.global?.locale?.value ?? i18n?.global?.locale;
    if (currentLocale) {
        const headers = config.headers;
        if (headers && typeof headers.set === 'function') {
            if (!headers.has('X-Locale')) {
                headers.set('X-Locale', currentLocale);
            }
        } else {
            config.headers = {
                ...(headers || {}),
                'X-Locale': headers?.['X-Locale'] ?? currentLocale
            };
        }
    }
    return config;
});

export default axios;
