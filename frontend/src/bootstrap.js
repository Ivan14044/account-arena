import axios from 'axios';

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

// Interceptor для очистки предупреждений MadelineProto из ответов
axios.interceptors.response.use(
    response => {
        // Обрабатываем как строковые, так и уже распарсенные ответы
        if (typeof response.data === 'string') {
            // Удаляем предупреждение MadelineProto из начала строки
            const cleanedData = response.data.replace(
                /^WARNING: MadelineProto runs around 10x slower on windows due to OS and PHP limitations\. Make sure to deploy MadelineProto in production only on Linux or Mac OS machines for maximum performance\.\s*\n*/,
                ''
            );
            try {
                // Пытаемся распарсить очищенный JSON
                response.data = JSON.parse(cleanedData);
            } catch {
                // Если не получилось, оставляем как есть
                console.warn('[Axios] Не удалось распарсить очищенный ответ:', e);
            }
        } else if (response.data && typeof response.data === 'object' && response.data.message) {
            // Если в ответе есть message с предупреждением, пытаемся его обработать
            const message = String(response.data.message);
            if (message.includes('WARNING: MadelineProto')) {
                // Пытаемся извлечь JSON из сообщения
                const jsonMatch = message.match(/\{[\s\S]*\}/);
                if (jsonMatch) {
                    try {
                        response.data = JSON.parse(jsonMatch[0]);
                    } catch {
                        // Игнорируем ошибку парсинга
                    }
                }
            }
        }
        return response;
    },
    error => {
        // Обрабатываем ошибки с предупреждениями MadelineProto
        if (error.response && error.response.data) {
            const data = error.response.data;
            if (typeof data === 'string' && data.includes('WARNING: MadelineProto')) {
                const jsonMatch = data.match(/\{[\s\S]*\}/);
                if (jsonMatch) {
                    try {
                        error.response.data = JSON.parse(jsonMatch[0]);
                    } catch {
                        // Игнорируем ошибку парсинга
                    }
                }
            }
        }
        return Promise.reject(error);
    }
);

// Экспортируем настроенный экземпляр axios
export default axios;
