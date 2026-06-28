import DOMPurify from 'dompurify';

// SECURITY FIX (B5 / bug H2): безопасный рендер HTML вместо сырого v-html.
// Серверный/пользовательский HTML (описания товаров, CMS-контент, статьи,
// тексты уведомлений, баннеры, правила покупки) пропускается через DOMPurify
// перед вставкой в DOM. Это закрывает stored-XSS: ранее любой supplier/гость
// мог внедрить <script>/onerror=, а токен в localStorage делал XSS = захват
// аккаунта. Заменяет v-html на v-safe-html во всех точках с данными с сервера.

// Любая ссылка получает безопасные атрибуты (без доступа к window.opener).
DOMPurify.addHook('afterSanitizeAttributes', (node) => {
    if (node instanceof HTMLElement && node.tagName === 'A') {
        node.setAttribute('target', '_blank');
        node.setAttribute('rel', 'noopener noreferrer nofollow');
    }
});

function render(el: HTMLElement, value: unknown): void {
    const dirty = value == null ? '' : String(value);
    el.innerHTML = DOMPurify.sanitize(dirty, { ADD_ATTR: ['target', 'rel'] });
}

export default {
    mounted(el: HTMLElement, binding: { value: unknown }) {
        render(el, binding.value);
    },
    updated(el: HTMLElement, binding: { value: unknown; oldValue: unknown }) {
        if (binding.value !== binding.oldValue) {
            render(el, binding.value);
        }
    },
};
