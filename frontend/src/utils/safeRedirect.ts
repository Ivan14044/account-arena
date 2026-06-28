// SECURITY FIX (M4 / bug M4): защита от open redirect.
// Разрешаем только внутренние (same-origin) относительные пути. Любой абсолютный
// URL, protocol-relative (//evil.com), backslash-трюки и управляющие символы
// заменяются на безопасный fallback. Используется для query-параметра ?redirect=.

// Управляющие символы (U+0000..U+001F) недопустимы в пути редиректа.
const CONTROL_CHARS = new RegExp('[\\u0000-\\u001f]');

export function safeRedirectPath(raw: unknown, fallback = '/'): string {
    if (typeof raw !== 'string' || raw.length === 0) {
        return fallback;
    }
    // Должен быть абсолютным путём внутри сайта: начинается с одного '/'
    if (!raw.startsWith('/')) {
        return fallback;
    }
    // Отсекаем protocol-relative (//host) и backslash-обходы (/\host)
    if (raw.startsWith('//') || raw.startsWith('/\\')) {
        return fallback;
    }
    if (CONTROL_CHARS.test(raw)) {
        return fallback;
    }
    return raw;
}
