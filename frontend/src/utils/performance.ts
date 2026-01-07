/**
 * Утилиты для оптимизации производительности
 * Проверяет поддержку браузером различных функций и добавляет классы для CSS оптимизаций
 */

/**
 * Инициализирует оптимизации производительности
 * Проверяет поддержку backdrop-filter и prefers-reduced-motion
 * Добавляет соответствующие классы к documentElement для CSS оптимизаций
 */
export function initPerformanceOptimizations() {
    // Проверяем, что DOM готов
    if (typeof document === 'undefined' || !document.documentElement) {
        return;
    }
    
    try {
        // Проверка поддержки backdrop-filter
        const supportsBackdropFilter = CSS.supports('backdrop-filter', 'blur(1px)');
        
        // Проверка prefers-reduced-motion
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        
        // Добавляем классы для CSS оптимизаций
        if (!supportsBackdropFilter) {
            document.documentElement.classList.add('no-backdrop-filter');
        }
        
        if (prefersReducedMotion) {
            document.documentElement.classList.add('reduced-motion');
        }
    } catch (error) {
        // Игнорируем ошибки при инициализации, чтобы не сломать приложение
        console.warn('[Performance] Ошибка инициализации оптимизаций:', error);
    }
}
