import { ref, onMounted, onBeforeUnmount, type Ref } from 'vue';
import { throttle } from 'lodash-es';

/**
 * Composable для оптимизированной работы со scroll событиями
 * Объединяет все scroll listeners в один для лучшей производительности
 */
export function useScroll(options: {
    throttleMs?: number;
    onScroll?: (scrollY: number) => void;
} = {}) {
    const { throttleMs = 100, onScroll } = options;
    
    const scrollY = ref(0);
    const isScrolled = ref(false);
    
    // Throttled scroll handler для производительности
    const handleScroll = throttle(() => {
        const currentScrollY = window.scrollY;
        scrollY.value = currentScrollY;
        isScrolled.value = currentScrollY > 0;
        
        // Вызываем пользовательский callback если он есть
        if (onScroll) {
            onScroll(currentScrollY);
        }
    }, throttleMs, { leading: true, trailing: true });
    
    onMounted(() => {
        window.addEventListener('scroll', handleScroll, { passive: true });
        // Инициализируем значения при монтировании
        handleScroll();
    });
    
    onBeforeUnmount(() => {
        window.removeEventListener('scroll', handleScroll);
        handleScroll.cancel();
    });
    
    return {
        scrollY,
        isScrolled,
        handleScroll
    };
}
