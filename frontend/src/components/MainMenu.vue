<template>
    <div class="relative flex items-center gap-5">
        <ul class="h-100 items-center hidden lg:flex gap-2">
            <li
                v-for="(item, index) in headerMenu"
                :key="index"
                class="cursor-pointer text-base !text-[15px] h-[30px] d-flex align-center lh-[15px] hover:bg-indigo-200 dark:hover:bg-gray-700 transition-all duration-300 px-2 px-lg-3 py-2 rounded-lg"
                @click="handleClick(item)"
            >
                {{ item.title }}
            </li>
        </ul>

        <!-- Кнопка бургер для мобильной версии -->
        <button
            v-if="headerMenu.length > 0"
            class="flex lg:hidden text-gray-700 hover:bg-indigo-200 dark:hover:bg-gray-700 transition-colors !text-[15px] h-[30px] align-center lh-[15px] duration-300 px-2 px-lg-3 py-2 rounded-lg"
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
import { useRouter } from 'vue-router';
import { useOptionStore } from '../stores/options';
import { useI18n } from 'vue-i18n';
import MobileMenu from './MobileMenu.vue';
import { Menu } from 'lucide-vue-next';

const serviceOption = useOptionStore();
const router = useRouter();
const { locale } = useI18n();
const isMobileMenuOpen = ref(false);

interface MenuItem {
    title: string;
    link: string;
    is_blank: boolean;
}

function parseJson<T>(str: string, fallback: T): T {
    try {
        return JSON.parse(str) as T;
    } catch {
        return fallback;
    }
}

const headerMenu = computed<MenuItem[]>(() => {
    const raw = serviceOption.options.header_menu;
    if (!raw) return [];

    const menusByLocale = parseJson<Record<string, string>>(raw, {});
    const menuStr = menusByLocale[locale.value] ?? '[]';

    return parseJson<MenuItem[]>(menuStr, []);
});

function handleClick(_item: MenuItem) {
    if (_item.is_blank) {
        window.open(_item.link, '_blank');
    } else {
        router.push(_item.link);
    }
}
</script>
