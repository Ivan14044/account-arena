import { ref, onMounted, onBeforeUnmount, type Ref } from 'vue';

/**
 * Composable для оптимизированной ленивой загрузки изображений
 * Использует IntersectionObserver для более точного контроля загрузки
 */
export function useLazyImage(elementRef: Ref<HTMLElement | null>, options: {
    rootMargin?: string;
    threshold?: number;
} = {}) {
    const { rootMargin = '50px', threshold = 0.01 } = options;
    
    const isLoaded = ref(false);
    const isInView = ref(false);
    
    let observer: IntersectionObserver | null = null;
    
    onMounted(() => {
        if (!elementRef.value) return;
        
        // Проверяем поддержку IntersectionObserver
        if ('IntersectionObserver' in window) {
            observer = new IntersectionObserver(
                (entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            isInView.value = true;
                            // Загружаем изображение когда оно видно
                            if (elementRef.value instanceof HTMLImageElement) {
                                const img = elementRef.value;
                                if (img.dataset.src && !isLoaded.value) {
                                    img.src = img.dataset.src;
                                    img.removeAttribute('data-src');
                                    isLoaded.value = true;
                                }
                            }
                            // Отключаем observer после загрузки
                            if (observer && elementRef.value) {
                                observer.unobserve(elementRef.value);
                            }
                        }
                    });
                },
                {
                    rootMargin,
                    threshold
                }
            );
            
            observer.observe(elementRef.value);
        } else {
            // Fallback для старых браузеров - загружаем сразу
            isInView.value = true;
            if (elementRef.value instanceof HTMLImageElement) {
                const img = elementRef.value;
                if (img.dataset.src) {
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    isLoaded.value = true;
                }
            }
        }
    });
    
    onBeforeUnmount(() => {
        if (observer && elementRef.value) {
            observer.unobserve(elementRef.value);
            observer.disconnect();
        }
    });
    
    return {
        isLoaded,
        isInView
    };
}
