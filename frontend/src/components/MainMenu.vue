<template>
    <div class="relative flex items-center gap-5">
        <ul class="h-100 items-center hidden lg:flex gap-2">
            <li
                v-for="(item, index) in headerMenu"
                :key="index"
                class="cursor-pointer !text-[14px] h-[30px] d-flex align-center leading-none hover:bg-indigo-200 dark:hover:bg-gray-700 transition-all duration-300 px-2 px-lg-3 py-2 rounded-lg"
                @click="handleClick(item)"
            >
                {{ item.title }}
            </li>
        </ul>

        <!-- Кнопка бургер для мобильной версии -->
        <button
            v-if="headerMenu.length > 0"
            class="flex lg:hidden text-gray-700 hover:bg-indigo-200 dark:hover:bg-gray-700 transition-colors !text-[15px] h-[30px] align-center leading-none duration-300 px-2 px-lg-3 py-2 rounded-lg"
            :class="{ 'mr-[-20px]': isMobileMenuOpen }"
            @click="isMobileMenuOpen = true"
        >
            <Menu class="w-5 h-5 text-gray-700 dark:text-white" />
        </button>

        <!-- Мобильное меню -->
        <MobileMenu
            v-if="isMobileMenuOpen"
            :is-open="isMobileMenuOpen"
            :menu-items="headerMenu"
            @close="isMobileMenuOpen = false"
        />
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useOptionStore } from '../stores/options';
import { useI18n } from 'vue-i18n';
import MobileMenu from './MobileMenu.vue';
import { Menu } from 'lucide-vue-next';
import { scrollToElement } from '@/utils/scrollToElement';

const serviceOption = useOptionStore();
const router = useRouter();
const route = useRoute();
const { locale } = useI18n();
const isMobileMenuOpen = ref(false);

interface MenuItem {
    title: string;
    link: string;
    is_blank?: boolean;
    is_scroll?: boolean; // Для якорных ссылок
}

function parseJson<T>(str: string, fallback: T): T {
    if (!str || typeof str !== 'string') {
        return fallback;
    }
    try {
        const parsed = JSON.parse(str);
        return parsed as T;
    } catch (e) {
        console.warn('[MainMenu] JSON parse error:', e, 'String:', str.substring(0, 100));
        return fallback;
    }
}

const headerMenu = computed<MenuItem[]>(() => {
    // Проверяем, загружены ли опции
    if (!serviceOption.isLoaded) {
        return [];
    }
    
    const options = serviceOption.options;
    let optionsObj: Record<string, any>;
    
    // Нормализуем options - может быть массивом или объектом
    if (Array.isArray(options)) {
        optionsObj = {};
        try {
            options.forEach(option => {
                if (option && option.key) {
                    optionsObj[option.key] = option.value;
                }
            });
        } catch (e) {
            console.error('[MainMenu] Error in forEach:', e);
            return [];
        }
    } else if (typeof options === 'object' && options !== null && !Array.isArray(options)) {
        optionsObj = options;
    } else {
        return [];
    }
    
    const raw = optionsObj.header_menu;
    if (!raw) return [];

    const menusByLocale = parseJson<Record<string, string>>(raw, {});
    const menuStr = menusByLocale[locale.value] ?? menusByLocale['ru'] ?? '[]';

    return parseJson<MenuItem[]>(menuStr, []);
});

function handleClick(item: MenuItem) {
    if (!item || !item.link) {
        console.warn('[MainMenu] Invalid item or link:', item);
        return;
    }
    
    if (item.is_blank) {
        window.open(item.link, '_blank', 'noopener,noreferrer');
        isMobileMenuOpen.value = false;
        return;
    }

    // Если это якорная ссылка и мы на главной странице
    if (item.is_scroll && route.path === '/') {
        scrollToElement(item.link);
        isMobileMenuOpen.value = false;
        return;
    }

    // Если это якорная ссылка, но мы не на главной
    if (item.is_scroll && route.path !== '/') {
        router.push('/').then(() => {
            // Небольшая задержка для загрузки страницы, затем скролл
            setTimeout(() => {
                scrollToElement(item.link);
            }, 300);
        }).catch(err => {
            console.error('[MainMenu] Navigation error:', err);
            if (err.name !== 'NavigationDuplicated') {
                window.location.href = item.link;
            }
        });
        isMobileMenuOpen.value = false;
        return;
    }

    // Обычная навигация
    router.push(item.link).then(() => {
        console.log('[MainMenu] Navigation successful to:', item.link);
    }).catch(err => {
        console.error('[MainMenu] Navigation error:', err);
        if (err.name !== 'NavigationDuplicated') {
            window.location.href = item.link;
        }
    });
    isMobileMenuOpen.value = false;
}
</script>
