import axios from 'axios';

// Поддерживаем оба варианта переменных окружения для совместимости:
// - VITE_API_BASE (текущая)
// - VITE_API_URL  (используется в документации/скриптах деплоя)
const apiBase =
    import.meta.env.VITE_API_BASE ||
    import.meta.env.VITE_API_URL ||
    'http://localhost:8000/api';

// Конфигурируем axios сразу (синхронно)
axios.defaults.baseURL = apiBase;
axios.defaults.withCredentials = true;
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Interceptor для установки локали (локаль будет установлена позже из main.js)
axios.interceptors.request.use((config) => {
    // Получаем текущую локаль из localStorage
    const currentLocale = localStorage.getItem('user-language') || 'uk';
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
    
    return config;
});

// Экспортируем настроенный экземпляр axios
export default axios;
