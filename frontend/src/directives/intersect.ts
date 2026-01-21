// Глобальный observer для оптимизации производительности
let globalObserver: IntersectionObserver | null = null;
const observedElements = new WeakMap<HTMLElement, () => void>();

function getGlobalObserver() {
    if (!globalObserver) {
        globalObserver = new IntersectionObserver(
            (entries) => {
                entries.forEach(entry => {
                    const callback = observedElements.get(entry.target as HTMLElement);
                    if (callback) {
                        callback();
                    }
                });
            },
            {
                root: null,
                threshold: [0, 0.05, 0.2, 0.9999],
                rootMargin: '0px 0px -20% 0px'
            }
        );
    }
    return globalObserver;
}

export default {
    mounted(el: HTMLElement, binding: any) {
        const animationClass = binding.value || 'animate-fade-in-up';
        el.classList.add('opacity-0', 'translate-y-8', 'transition-all', 'duration-1000');

        // Support object binding: { class: 'animate-fade-in-up', once: true, threshold: 0.2, rootMargin: '0px 0px -20% 0px' }
        const options =
            typeof binding.value === 'object' && binding.value !== null
                ? binding.value
                : { class: animationClass };

        const targetClass = options.class || animationClass;
        const once = options.once !== false; // default true

        let hasAppeared = false; // guard against multiple triggers (iOS Safari can fire twice)

        // Earlier reveal on small screens: lower threshold and negative bottom rootMargin
        const isSmallScreen = window.innerWidth < 768;
        const baseThreshold =
            typeof options.threshold === 'number'
                ? options.threshold
                : isSmallScreen
                    ? 0.05
                    : 0.2;

        const callback = () => {
            if (hasAppeared && once) return;

            const rect = el.getBoundingClientRect();
            const isVisible = rect.top < window.innerHeight * 1.2 && rect.bottom > 0;

            if (isVisible) {
                const ratio = Math.min(1, Math.max(0, (window.innerHeight - rect.top) / (rect.height + window.innerHeight)));

                if (ratio >= baseThreshold) {
                    hasAppeared = true;
                    el.classList.add(targetClass);
                    el.classList.remove('opacity-0', 'translate-y-8');

                    if (once) {
                        const observer = getGlobalObserver();
                        observer.unobserve(el);
                        observedElements.delete(el);
                    }
                }
            }
        };

        observedElements.set(el, callback);
        const observer = getGlobalObserver();
        observer.observe(el);

        // Проверка сразу если элемент уже виден
        requestAnimationFrame(callback);

        // Если элемент изначально имеет нулевую высоту, повторяем проверку после загрузки
        if (el.clientHeight === 0) {
            setTimeout(() => {
                requestAnimationFrame(callback);
            }, 500);
        }
    },

    unmounted(el: HTMLElement) {
        const observer = getGlobalObserver();
        if (observer) {
            observer.unobserve(el);
            observedElements.delete(el);
        }
    }
};
