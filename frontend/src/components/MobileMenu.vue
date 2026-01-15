<template>
    <Teleport to="body">
        <Transition name="slide-x">
            <div v-if="isOpen">
                <!-- Overlay -->
                <div class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[9998]" @click="$emit('close')"></div>

                <!-- Menu Panel -->
                <div class="fixed top-0 left-0 h-full w-64 bg-gray-900 dark:bg-gray-800 z-[9999] shadow-2xl">
                    <div class="flex justify-between items-center p-4 border-b border-gray-700 dark:border-gray-600">
                        <div
                            class="flex items-center gap-2 cursor-pointer"
                            @click="handleClick({ link: '/', is_scroll: false })"
                        >
                            <img
                                :src="logo"
                                alt="Account Arena Logo"
                                class="w-8 h-8 object-contain rounded-full"
                            />
                            <span class="text-white text-xl font-bold">Account Arena</span>
                        </div>
                        <button 
                            class="text-white hover:text-gray-300 transition-colors p-1"
                            @click="$emit('close')"
                            aria-label="Close menu"
                        >
                            <svg
                                width="24"
                                height="24"
                                viewBox="0 0 24 24"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <path
                                    d="M18 6L6 18M6 6L18 18"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </button>
                    </div>

                    <!-- Menu Items -->
                    <nav class="p-4 overflow-y-auto" style="max-height: calc(100vh - 80px);">
                        <ul class="flex flex-col gap-2">
                            <li
                                v-for="(item, index) in menuItems"
                                :key="index"
                                class="text-white cursor-pointer hover:bg-gray-800 dark:hover:bg-gray-700 transition-colors px-4 py-3 rounded-lg border border-gray-800 dark:border-gray-700"
                                @click="handleClick(item)"
                            >
                                {{ item.title }}
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { useRouter, useRoute } from 'vue-router';
import { onMounted, onUnmounted, watch } from 'vue';
import logo from '@/assets/logo.webp';
import { scrollToElement } from '@/utils/scrollToElement';

const router = useRouter();
const route = useRoute();

const props = defineProps<{
    isOpen: boolean;
    menuItems: any[];
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const handleClick = (item: any) => {
    if (item.is_blank) {
        window.open(item.link, '_blank');
        emit('close');
        return;
    }

    // Если это якорная ссылка и мы на главной странице
    if (item.is_scroll && route.path === '/') {
        emit('close');
        // Небольшая задержка для плавного закрытия меню перед скроллом
        setTimeout(() => {
            scrollToElement(item.link);
        }, 200);
        return;
    }

    // Если это якорная ссылка, но мы не на главной
    if (item.is_scroll && route.path !== '/') {
        emit('close');
        router.push('/').then(() => {
            // Небольшая задержка для загрузки страницы, затем скролл
            setTimeout(() => {
                scrollToElement(item.link);
            }, 300);
        });
        return;
    }

    // Обычная навигация
    emit('close');
    router.push(item.link);
};

// Закрытие по Escape
const handleEscape = (e: KeyboardEvent) => {
    if (e.key === 'Escape' && props.isOpen) {
        emit('close');
    }
};

// Блокировка скролла body при открытом меню
onMounted(() => {
    document.addEventListener('keydown', handleEscape);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleEscape);
});

// Блокировка скролла body при открытом меню
watch(() => props.isOpen, (isOpen) => {
    if (isOpen) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
});
</script>

<style scoped>
/* Анимация для мобильного меню */
.slide-x-enter-active {
    transition: all 0.3s ease-out;
}

.slide-x-leave-active {
    transition: all 0.25s ease-in;
}

.slide-x-enter-from {
    transform: translateX(-100%);
    opacity: 0;
}

.slide-x-leave-to {
    transform: translateX(-100%);
    opacity: 0;
}

.slide-x-enter-to,
.slide-x-leave-from {
    transform: translateX(0);
    opacity: 1;
}

/* Анимация для overlay */
.slide-x-enter-active > div:first-child,
.slide-x-leave-active > div:first-child {
    transition: opacity 0.3s ease;
}

.slide-x-enter-from > div:first-child,
.slide-x-leave-to > div:first-child {
    opacity: 0;
}

.slide-x-enter-to > div:first-child,
.slide-x-leave-from > div:first-child {
    opacity: 1;
}
</style>
