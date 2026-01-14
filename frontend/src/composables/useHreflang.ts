import { computed, watch, onMounted, onBeforeUnmount } from 'vue';
import { useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';

const BASE_URL = 'https://account-arena.com';
const LANGUAGES = ['ru', 'en', 'uk'];

/**
 * Composable для генерации hreflang тегов для мультиязычности
 * Автоматически создаёт <link rel="alternate" hreflang="..."> для всех языков
 */
export function useHreflang(customPath?: () => string) {
    const route = useRoute();
    const { locale } = useI18n();
    
    let injectedLinks: HTMLLinkElement[] = [];
    
    const currentPath = computed(() => {
        if (customPath) {
            return customPath();
        }
        // Очищаем путь от параметров и trailing slash
        let path = route.path.replace(/\/$/, '').replace(/\?.*$/, '');
        if (!path.startsWith('/')) path = '/' + path;
        return path;
    });
    
    const injectHreflang = () => {
        // Удаляем все ранее добавленные hreflang ссылки
        injectedLinks.forEach(link => {
            if (link.parentNode) {
                link.parentNode.removeChild(link);
            }
        });
        injectedLinks = [];
        
        const path = currentPath.value;
        
        // Создаём hreflang для каждого языка
        LANGUAGES.forEach(lang => {
            const link = document.createElement('link');
            link.rel = 'alternate';
            link.hreflang = lang;
            link.href = `${BASE_URL}${path}?lang=${lang}`;
            document.head.appendChild(link);
            injectedLinks.push(link);
        });
        
        // Добавляем x-default
        const defaultLink = document.createElement('link');
        defaultLink.rel = 'alternate';
        defaultLink.hreflang = 'x-default';
        defaultLink.href = `${BASE_URL}${path}`;
        document.head.appendChild(defaultLink);
        injectedLinks.push(defaultLink);
    };
    
    // Инжектим при монтировании
    onMounted(() => {
        injectHreflang();
    });
    
    // Обновляем при смене роута или языка
    watch([() => route.path, locale], () => {
        injectHreflang();
    });
    
    // Очищаем при размонтировании
    onBeforeUnmount(() => {
        injectedLinks.forEach(link => {
            if (link.parentNode) {
                link.parentNode.removeChild(link);
            }
        });
        injectedLinks = [];
    });
    
    return {
        injectHreflang,
        clear: () => {
            injectedLinks.forEach(link => {
                if (link.parentNode) {
                    link.parentNode.removeChild(link);
                }
            });
            injectedLinks = [];
        }
    };
}
