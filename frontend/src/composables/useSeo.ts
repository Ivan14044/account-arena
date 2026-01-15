import { computed, watch, onMounted, onBeforeUnmount } from 'vue';
import { useRoute } from 'vue-router';

interface SeoOptions {
    title?: string | (() => string);
    description?: string | (() => string);
    ogImage?: string | (() => string);
    canonical?: string | (() => string);
    noindex?: boolean;
    ogType?: string;
}

const BASE_URL = 'https://account-arena.com';
const DEFAULT_DESCRIPTION = 'Account Arena - лучший маркетплейс для покупки аккаунтов';

/**
 * Composable для управления SEO мета-тегами
 * Автоматически обновляет title, description, OpenGraph, Twitter Cards, canonical
 */
export function useSeo(options: SeoOptions) {
    const route = useRoute();
    
    // Вычисляемые значения
    const title = computed(() => {
        const pageTitle = typeof options.title === 'function' 
            ? options.title() 
            : options.title;
        
        if (!pageTitle) {
            return 'Account Arena';
        }
        
        // Проверяем, содержит ли title уже "Account Arena"
        // Если содержит, не добавляем его повторно
        const titleLower = pageTitle.toLowerCase();
        if (titleLower.includes('account arena')) {
            return pageTitle;
        }
        
        return `${pageTitle} - Account Arena`;
    });
    
    const description = computed(() => {
        const desc = typeof options.description === 'function'
            ? options.description()
            : options.description;
        return desc || DEFAULT_DESCRIPTION;
    });
    
    const ogImage = computed(() => {
        const img = typeof options.ogImage === 'function'
            ? options.ogImage()
            : options.ogImage;
        if (!img) {
            return `${BASE_URL}/img/logo_trans.webp`;
        }
        return img.startsWith('http') ? img : `${BASE_URL}${img.startsWith('/') ? img : '/' + img}`;
    });
    
    const canonical = computed(() => {
        const url = typeof options.canonical === 'function'
            ? options.canonical()
            : options.canonical;
        
        if (!url) {
            // Используем текущий путь, очищая от параметров и trailing slash
            let path = route.path.replace(/\/$/, '').replace(/\?.*$/, '');
            if (!path.startsWith('/')) path = '/' + path;
            return `${BASE_URL}${path}`;
        }
        
        // Очищаем URL от trailing slash и параметров
        const cleanUrl = url.replace(/\/$/, '').replace(/\?.*$/, '');
        return cleanUrl.startsWith('http') 
            ? cleanUrl 
            : `${BASE_URL}${cleanUrl.startsWith('/') ? cleanUrl : '/' + cleanUrl}`;
    });
    
    const ogType = computed(() => options.ogType || 'website');
    
    // Функция для обновления title
    const updateTitle = () => {
        document.title = title.value;
    };
    
    // Функция для обновления всех мета-тегов
    const updateMeta = () => {
        // Description
        let metaDesc = document.querySelector('meta[name="description"]');
        if (!metaDesc) {
            metaDesc = document.createElement('meta');
            metaDesc.setAttribute('name', 'description');
            document.head.appendChild(metaDesc);
        }
        metaDesc.setAttribute('content', description.value);
        
        // Robots
        if (options.noindex) {
            let metaRobots = document.querySelector('meta[name="robots"]');
            if (!metaRobots) {
                metaRobots = document.createElement('meta');
                metaRobots.setAttribute('name', 'robots');
                document.head.appendChild(metaRobots);
            }
            metaRobots.setAttribute('content', 'noindex, nofollow');
        } else {
            // Удаляем noindex, если он был установлен ранее
            const metaRobots = document.querySelector('meta[name="robots"]');
            if (metaRobots && metaRobots.getAttribute('content') === 'noindex, nofollow') {
                metaRobots.remove();
            }
        }
        
        // OpenGraph теги
        const ogTags: Record<string, string> = {
            'og:title': title.value,
            'og:description': description.value,
            'og:url': canonical.value,
            'og:type': ogType.value,
            'og:image': ogImage.value,
            'og:site_name': 'Account Arena'
        };
        
        Object.entries(ogTags).forEach(([property, content]) => {
            let tag = document.querySelector(`meta[property="${property}"]`);
            if (!tag) {
                tag = document.createElement('meta');
                tag.setAttribute('property', property);
                document.head.appendChild(tag);
            }
            tag.setAttribute('content', content);
        });
        
        // Twitter Cards
        const twitterTags: Record<string, string> = {
            'twitter:card': 'summary_large_image',
            'twitter:title': title.value,
            'twitter:description': description.value,
            'twitter:image': ogImage.value
        };
        
        Object.entries(twitterTags).forEach(([name, content]) => {
            let tag = document.querySelector(`meta[name="${name}"]`);
            if (!tag) {
                tag = document.createElement('meta');
                tag.setAttribute('name', name);
                document.head.appendChild(tag);
            }
            tag.setAttribute('content', content);
        });
        
        // Canonical
        let canonicalLink = document.querySelector('link[rel="canonical"]');
        if (!canonicalLink) {
            canonicalLink = document.createElement('link');
            canonicalLink.setAttribute('rel', 'canonical');
            document.head.appendChild(canonicalLink);
        }
        canonicalLink.setAttribute('href', canonical.value);
    };
    
    // Обновляем при монтировании и изменении данных
    onMounted(() => {
        updateTitle();
        updateMeta();
    });
    
    // Следим за изменениями computed значений
    watch([title, description, ogImage, canonical], () => {
        updateTitle();
        updateMeta();
    }, { immediate: false });
    
    // Обновляем при смене роута
    watch(() => route.path, () => {
        updateTitle();
        updateMeta();
    });
    
    return {
        title,
        description,
        ogImage,
        canonical,
        updateTitle,
        updateMeta
    };
}
