import { ref, onMounted, onBeforeUnmount, type Ref } from 'vue';

/**
 * Composable для отслеживания видимости элементов через IntersectionObserver
 * Используется для оптимизации CSS transitions и других визуальных эффектов
 */
export function useIntersectionObserver(
    elementRef: Ref<HTMLElement | null>,
    options: {
        rootMargin?: string;
        threshold?: number;
        once?: boolean; // Отключить observer после первого пересечения
    } = {}
) {
    const { rootMargin = '0px', threshold = 0, once = false } = options;
    
    const isVisible = ref(false);
    const hasBeenVisible = ref(false);
    
    let observer: IntersectionObserver | null = null;
    
    onMounted(() => {
        if (!elementRef.value) return;
        
        // Проверяем поддержку IntersectionObserver
        if ('IntersectionObserver' in window) {
            observer = new IntersectionObserver(
                (entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            isVisible.value = true;
                            hasBeenVisible.value = true;
                            
                            // Отключаем observer после первого пересечения если once = true
                            if (once && observer && elementRef.value) {
                                observer.unobserve(elementRef.value);
                            }
                        } else {
                            isVisible.value = false;
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
            // Fallback для старых браузеров - считаем элемент видимым
            isVisible.value = true;
            hasBeenVisible.value = true;
        }
    });
    
    onBeforeUnmount(() => {
        if (observer && elementRef.value) {
            observer.unobserve(elementRef.value);
            observer.disconnect();
        }
    });
    
    return {
        isVisible,
        hasBeenVisible
    };
}
