<template>
    <header class="text-gray-900 dark:text-white">
        <div
            class="relative z-100 max-w-7xl w-full mx-auto px-2 sm:px-4 py-2 flex justify-center"
        >
            <div
                class="bg-indigo-soft-200/90 dark:bg-gray-800/90 rounded-full px-4 py-1 flex items-center transition-all duration-1000 dashboard border !border-indigo-soft-400 dark:!border-gray-700"
                :class="isReady ? 'w-full gap-2 justify-between' : 'w-[64px] justify-center'"
            >
                <div
                    class="flex items-center sm:gap-2 w-[32px] sm:w-[150px] xl:!w-[160px] cursor-pointer"
                    @click="handleClick()"
                >
                    <img
                        :src="logo"
                        alt="Logo"
                        class="!w-[29px] sm:w-8 sm:h-8 object-contain rounded-full spin-slow-reverse"
                    />
                    <span class="text-xl font-semibold whitespace-nowrap hidden sm:flex">
                        {{ printedText }}
                    </span>
                </div>
                <Transition
                    appear
                    enter-active-class="transition duration-500 ease-out"
                    enter-from-class="opacity-0 translate-y-2"
                    enter-to-class="opacity-100 translate-y-0"
                >
                    <div v-if="headerStore.showMenu" class="w-full d-flex justify-between sm:pl-6">
                        <LanguageSelector />
                        <MainMenu class="hidden lg:block" />
                        <div class="relative flex items-center gap-1">
                            <ThemeSwitcher />
                            <ServiceCart />
                            <NotificationBell />
                            <UserMenu />
                            <MainMenu class="block lg:hidden" />
                        </div>
                    </div>
                </Transition>
            </div>
        </div>
    </header>
</template>

<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted, computed } from 'vue';
import logo from '@/assets/logo.webp';
import { useLoadingStore } from '@/stores/loading';
import UserMenu from '@/components/layout/UserMenu.vue';
import { useHeaderStore } from '@/stores/header';
import MainMenu from '@/components/MainMenu.vue';
import NotificationBell from '@/components/layout/NotificationBell.vue';
import ServiceCart from '@/components/layout/ServiceCart.vue';
import LanguageSelector from '@/components/layout/LanguageSelector.vue';
import ThemeSwitcher from '@/components/layout/ThemeSwitcher.vue';
import { useRouter } from 'vue-router';

const router = useRouter();
const loadingStore = useLoadingStore();

const isScrolled = ref(false);
const headerStore = useHeaderStore();
const isReady = computed(() => headerStore.isReady);
const printedText = computed(() => headerStore.printedText);
const fullText = 'SubCloudy';

const handleScroll = () => {
    isScrolled.value = window.scrollY > 0;
};

function handleClick() {
    router.push('/');
}

watch(
    () => loadingStore.isLoading,
    isLoading => {
        if (isLoading || headerStore.isReady) return;

        setTimeout(() => {
            headerStore.isReady = true;

            setTimeout(startTypingEffect, 900);
        }, 500);
    },
    { immediate: true }
);

function startTypingEffect() {
    if (headerStore.showMenu) {
        return;
    }

    headerStore.showMenu = true;

    let i = 0;
    const interval = setInterval(() => {
        headerStore.printedText += fullText[i++];
        if (i === fullText.length) {
            clearInterval(interval);
        }
    }, 80);
}

onMounted(() => {
    window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});
</script>

<style scoped>
.dashboard {
    height: 44px !important;
}
</style>
