<template>
    <div ref="dropdownRef" class="relative" style="overflow: visible;">
        <!-- Language Button -->
        <button
            ref="buttonRef"
            class="language-button cursor-pointer gap-[7px] text-sm leading-4 transition-all duration-300 pl-2 pr-1 lg:pl-3 lg:pr-2 py-2 rounded-lg flex items-center relative z-10"
            @click="toggleDropdown"
            :aria-label="`${$t('language.selector') || 'Language selector'} ${currentLanguage.name}`"
            :aria-expanded="isOpen"
        >
            <img
                :src="`/img/lang/${currentLanguage.code}.svg`"
                :alt="`${currentLanguage.name} flag`"
                width="24"
                height="16"
                style="aspect-ratio: 3 / 2;"
            />
            <Globe v-if="!currentLanguage.code" class="w-5 h-5 text-gray-600" />
            <span class="flex items-center">
                <span class="hidden xl:flex text-[15px]">{{ currentLanguage.name }}</span>
                <ChevronDown
                    :class="[
                        'xl:ml-1 w-4 h-4 transition-transform duration-300',
                        isOpen ? 'rotate-180' : 'rotate-0'
                    ]"
                />
            </span>
        </button>

        <!-- Dropdown Menu -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-2"
        >
            <div
                v-if="isOpen"
                ref="dropdownMenuRef"
                class="language-dropdown absolute top-full mt-2 left-0 min-w-[160px] z-[9999]"
            >
                <div class="liquid-glass-effect"></div>
                <div class="liquid-glass-tint"></div>
                <div class="liquid-glass-shine"></div>
                <div class="liquid-glass-text">
                    <button
                        v-for="language in languages"
                        :key="language.code"
                        class="flex h-[44px] items-center gap-3 w-full px-4 text-sm text-left hover:bg-indigo-200 dark:hover:bg-gray-700 transition-colors relative"
                        :class="[
                            language.code === currentLocale
                                ? 'text-gray-800 dark:text-blue-700 font-bold'
                                : 'text-gray-900 dark:!text-white'
                        ]"
                        :aria-label="`Выбрать язык ${language.name}`"
                        @click="changeLanguage(language.code)"
                    >
                        <span class="relative z-10 flex items-center">
                            <span class="pr-2 flex items-center">
                                <img
                                    :src="`/img/lang/${language.code}.svg`"
                                    :alt="`${language.name} flag`"
                                    width="24"
                                    height="16"
                                    class="w-6 h-4 object-cover transition-opacity duration-300 align-middle inline-block"
                                    style="aspect-ratio: 3 / 2;"
                                />
                            </span>
                            {{ language.name }}
                        </span>
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { Globe, ChevronDown } from 'lucide-vue-next';
import { useAuthStore } from '@/stores/auth';

// Доступные языки интерфейса
const languages = [
    { code: 'en', name: 'English' },
    { code: 'uk', name: 'Українська' },
    { code: 'ru', name: 'Русский' }
];

const { locale, t } = useI18n();
const isOpen = ref(false);
const dropdownRef = ref<HTMLElement | null>(null);
const buttonRef = ref<HTMLElement | null>(null);
const dropdownMenuRef = ref<HTMLElement | null>(null);
const authStore = useAuthStore();

const currentLocale = computed(() => locale.value);

const currentLanguage = computed(
    () => languages.find(lang => lang.code === currentLocale.value) || languages[0]
);

const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
};

const changeLanguage = async (code: string) => {
    locale.value = code;
    localStorage.setItem('user-language', code);
    isOpen.value = false;

    if (authStore.isAuthenticated && authStore.user) {
        await authStore.update({ lang: code });
    }
};

const handleClickOutside = (event: MouseEvent) => {
    const target = event.target as Node;
    
    // ИГНОРИРУЕМ клики внутри модального окна поддержки
    const supportModal = document.querySelector('.support-modal-container');
    if (supportModal && supportModal.contains(target)) {
        return;
    }
    
    if (
        dropdownRef.value && 
        !dropdownRef.value.contains(target) &&
        dropdownMenuRef.value &&
        !dropdownMenuRef.value.contains(target)
    ) {
        isOpen.value = false;
    }
};

const savedLanguage = localStorage.getItem('user-language');
if (savedLanguage) {
    locale.value = savedLanguage;
}

onMounted(() => {
    const savedLanguage = localStorage.getItem('user-language');
    if (savedLanguage) {
        locale.value = savedLanguage;
    }

    document.addEventListener('mousedown', handleClickOutside);

    languages.forEach(lang => {
        const img = new Image();
        img.src = `/img/lang/${lang.code}.svg`;
    });
});

onUnmounted(() => {
    document.removeEventListener('mousedown', handleClickOutside);
});
</script>

<style scoped>
.language-button {
    background: rgba(255, 255, 255, 0.5);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border: 1px solid rgba(226, 232, 240, 0.8);
    transition: all 0.3s ease;
}

.dark .language-button {
    background: rgba(30, 41, 59, 0.5);
    border-color: rgba(255, 255, 255, 0.1);
}

.language-button:hover {
    background: rgba(255, 255, 255, 0.8);
    border-color: rgba(108, 92, 231, 0.3);
}

.dark .language-button:hover {
    background: rgba(30, 41, 59, 0.8);
    border-color: rgba(108, 92, 231, 0.4);
}

/* Liquid Glass Effect для dropdown меню языка */
.language-dropdown {
    position: absolute;
    box-shadow: 0 6px 6px rgba(0, 0, 0, 0.2), 0 0 20px rgba(0, 0, 0, 0.1);
    border-radius: 0.5rem; /* rounded-lg */
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.language-dropdown .liquid-glass-effect {
    position: absolute;
    z-index: 0;
    inset: 0;
    backdrop-filter: blur(3px);
    -webkit-backdrop-filter: blur(3px);
    filter: url(#header-glass-distortion);
    overflow: hidden;
    isolation: isolate;
    border-radius: 0.5rem;
}

.language-dropdown .liquid-glass-tint {
    z-index: 1;
    position: absolute;
    inset: 0;
    background: rgba(255, 255, 255, 0.25);
    border-radius: 0.5rem;
}

.dark .language-dropdown .liquid-glass-tint {
    background: rgba(31, 41, 55, 0.4);
}

.language-dropdown .liquid-glass-shine {
    position: absolute;
    inset: 0;
    z-index: 2;
    overflow: hidden;
    border-radius: 0.5rem;
    box-shadow: 
        inset 2px 2px 1px 0 rgba(255, 255, 255, 0.5),
        inset -1px -1px 1px 1px rgba(255, 255, 255, 0.5);
    pointer-events: none;
}

.dark .language-dropdown .liquid-glass-shine {
    box-shadow: 
        inset 2px 2px 1px 0 rgba(255, 255, 255, 0.1),
        inset -1px -1px 1px 1px rgba(255, 255, 255, 0.1);
}

.language-dropdown .liquid-glass-text {
    z-index: 3;
    position: relative;
    display: flex;
    flex-direction: column;
    width: 100%;
}
</style>
