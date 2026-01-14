<template>
    <header class="text-gray-900 dark:text-white">
        <!-- SVG фильтр для эффекта матового стекла (Liquid Glass) -->
        <svg style="display: none" class="liquid-glass-filter">
            <filter
                id="header-glass-distortion"
                x="0%"
                y="0%"
                width="100%"
                height="100%"
                filterUnits="objectBoundingBox"
            >
                <feTurbulence
                    type="fractalNoise"
                    baseFrequency="0.01 0.01"
                    numOctaves="1"
                    seed="5"
                    result="turbulence"
                />
                <feComponentTransfer in="turbulence" result="mapped">
                    <feFuncR type="gamma" amplitude="1" exponent="10" offset="0.5" />
                    <feFuncG type="gamma" amplitude="0" exponent="1" offset="0" />
                    <feFuncB type="gamma" amplitude="0" exponent="1" offset="0.5" />
                </feComponentTransfer>
                <feGaussianBlur in="turbulence" stdDeviation="3" result="softMap" />
                <feSpecularLighting
                    in="softMap"
                    surfaceScale="5"
                    specularConstant="1"
                    specularExponent="100"
                    lighting-color="white"
                    result="specLight"
                >
                    <fePointLight x="-200" y="-200" z="300" />
                </feSpecularLighting>
                <feComposite
                    in="specLight"
                    operator="arithmetic"
                    k1="0"
                    k2="1"
                    k3="1"
                    k4="0"
                    result="litImage"
                />
                <feDisplacementMap
                    in="SourceGraphic"
                    in2="softMap"
                    scale="150"
                    xChannelSelector="R"
                    yChannelSelector="G"
                />
            </filter>
        </svg>

        <div class="relative z-100 max-w-7xl w-full mx-auto px-2 sm:px-4 py-2 flex justify-center">
            <div
                class="liquid-glass-wrapper dashboard rounded-full px-4 py-1 flex items-center"
                :class="isReady ? 'w-full gap-2 justify-between' : 'w-[64px] justify-center'"
                style="contain: layout; will-change: width"
            >
                <div class="liquid-glass-effect"></div>
                <div class="liquid-glass-tint"></div>
                <div class="liquid-glass-shine"></div>
                
                <div class="liquid-glass-text flex items-center w-full"
                     :class="isReady ? 'gap-2 justify-between' : 'justify-center'"
                >
                    <div
                        class="flex items-center sm:gap-2 w-[32px] sm:w-[150px] xl:!w-[160px] cursor-pointer relative z-10"
                        @click="handleClick()"
                    >
                        <img
                            :src="logo"
                            alt="Logo"
                            class="!w-[29px] sm:w-8 sm:h-8 object-contain rounded-full spin-slow-reverse"
                            style="aspect-ratio: 1 / 1;"
                        />
                        <span class="text-xl font-semibold whitespace-nowrap hidden sm:flex text-gray-900 dark:text-white">
                            {{ printedText }}
                        </span>
                    </div>
                    <Transition
                        appear
                        enter-active-class="transition duration-500 ease-out"
                        enter-from-class="opacity-0 translate-y-2"
                        enter-to-class="opacity-100 translate-y-0"
                    >
                        <div v-if="headerStore.showMenu" class="w-full flex items-center justify-between sm:pl-6 relative z-20" style="overflow: visible; min-width: 0; pointer-events: auto;">
                            <div class="flex items-center" style="overflow: visible; flex-shrink: 0; position: relative; z-index: 21;">
                                <LanguageSelector />
                            </div>
                            <div class="flex items-center flex-shrink-1 min-w-0 hidden lg:block" style="overflow: hidden; text-overflow: ellipsis; position: relative; z-index: 21;">
                                <MainMenu />
                            </div>
                            <div class="relative flex items-center gap-0.5 flex-shrink-0" style="position: relative; z-index: 21;">
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
        </div>
    </header>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue';
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
import { useScroll } from '@/composables/useScroll';

const router = useRouter();
const loadingStore = useLoadingStore();

// Используем общий composable для scroll listeners
const { isScrolled } = useScroll({ throttleMs: 100 });

const headerStore = useHeaderStore();
const isReady = computed(() => headerStore.isReady);
const printedText = computed(() => headerStore.printedText);
const fullText = 'Account Arena';

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
</script>

<style scoped>
/* Liquid Glass Effect для хедера */
.liquid-glass-wrapper {
    position: relative;
    display: flex;
    box-shadow: 0 6px 6px rgba(0, 0, 0, 0.2), 0 0 20px rgba(0, 0, 0, 0.1);
    /* Плавная анимация раскрытия по ширине */
    transition: width 1.5s cubic-bezier(0.4, 0, 0.2, 1), 
                padding 1.5s cubic-bezier(0.4, 0, 0.2, 1),
                gap 1.5s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: default;
}

.liquid-glass-wrapper:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 12px rgba(0, 0, 0, 0.25), 0 0 30px rgba(0, 0, 0, 0.15);
    transition: width 1.5s cubic-bezier(0.4, 0, 0.2, 1),
                padding 1.5s cubic-bezier(0.4, 0, 0.2, 1),
                gap 1.5s cubic-bezier(0.4, 0, 0.2, 1),
                transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 2.2),
                box-shadow 0.4s cubic-bezier(0.175, 0.885, 0.32, 2.2);
}

.liquid-glass-effect {
    position: absolute;
    z-index: 0;
    inset: 0;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    filter: url(#header-glass-distortion);
    overflow: hidden;
    isolation: isolate;
    border-radius: 9999px;
}

.liquid-glass-tint {
    z-index: 1;
    position: absolute;
    inset: 0;
    background: rgba(255, 255, 255, 0.25);
    border-radius: 9999px;
}

.dark .liquid-glass-tint {
    background: rgba(31, 41, 55, 0.4);
}

.liquid-glass-shine {
    position: absolute;
    inset: 0;
    z-index: 2;
    overflow: hidden;
    border-radius: 9999px;
    box-shadow: 
        inset 2px 2px 1px 0 rgba(255, 255, 255, 0.5),
        inset -1px -1px 1px 1px rgba(255, 255, 255, 0.5);
    pointer-events: none;
}

.dark .liquid-glass-shine {
    box-shadow: 
        inset 2px 2px 1px 0 rgba(255, 255, 255, 0.1),
        inset -1px -1px 1px 1px rgba(255, 255, 255, 0.1);
}

.liquid-glass-text {
    z-index: 3;
    position: relative;
    display: flex;
}

.liquid-glass-filter {
    position: absolute;
    width: 0;
    height: 0;
    overflow: hidden;
}

.dashboard {
    height: 44px !important;
}
</style>
