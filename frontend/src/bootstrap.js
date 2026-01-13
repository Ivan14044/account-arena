import axios from 'axios';
import { createToastInterface } from 'vue-toastification';

const toast = createToastInterface({
    position: 'top-right',
    timeout: 5000
});

// Поддерживаем оба варианта переменных окружения для совместимости:
// - VITE_API_BASE (текущая)
// - VITE_API_URL  (используется в документации/скриптах деплоя)
const apiBase =
    import.meta.env.VITE_API_BASE || import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

// Конфигурируем axios сразу (синхронно)
axios.defaults.baseURL = apiBase;
axios.defaults.withCredentials = true;
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Interceptor для установки локали (локаль будет установлена позже из main.js)
axios.interceptors.request.use(config => {
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

// Interceptor для обработки ошибок
axios.interceptors.response.use(
    response => response,
    error => {
        const status = error.response ? error.response.status : null;

        if (status === 401) {
            // Обработка 401 Unauthorized - например, перенаправление на логин
            // Но в этом проекте может быть гостевой доступ, поэтому просто уведомляем
            console.warn('Unauthorized access');
        } else if (status === 403) {
            toast.error('Доступ запрещен');
        } else if (status === 422) {
            // Ошибки валидации - обычно обрабатываются в компонентах,
            // но можем вывести общее уведомление
            const message = error.response.data.message || 'Ошибка валидации данных';
            toast.error(message);
        } else if (status >= 500) {
            toast.error('Ошибка сервера. Пожалуйста, попробуйте позже.');
        } else if (!error.response) {
            toast.error('Проблема с сетью. Проверьте подключение.');
        }

        return Promise.reject(error);
    }
);

// Экспортируем настроенный экземпляр axios
export default axios;
