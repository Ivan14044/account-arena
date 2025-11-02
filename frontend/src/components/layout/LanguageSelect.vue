<template>
    <div ref="dropdownRef" class="z-50 relative">
        <!-- Language Button -->
        <button class="language-selector" @click="toggleDropdown">
            <img :src="`/img/lang/${currentLanguage.code}.png`" style="width: 24px; height: 16px" />
            <Globe v-if="!currentLanguage.code" class="w-5 h-5 text-gray-600" />
            <span
                class="flex items-center text-gray-700 dark:text-white text-sm font-normal group-hover:text-gray-900 transition-colors pl-2"
            >
                <ChevronDown
                    :class="[
                        'w-4 h-4 transition-transform duration-300',
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
                style="will-change: transform; transform: translateZ(0)"
                class="absolute top-[35px] bg-indigo-soft-200/90 dark:bg-gray-800/90 right-0 mt-2 rounded-lg border !border-indigo-soft-400 dark:!border-gray-700 overflow-hidden min-w-[160px]"
            >
                <div>
                    <button
                        v-for="language in languages"
                        :key="language.code"
                        class="flex h-[44px] items-center gap-3 w-full px-4 py-3 text-sm text-left hover:bg-indigo-200 dark:hover:bg-gray-700 transition-colors relative"
                        :class="[
                            language.code === currentLocale
                                ? 'text-gray-800 dark:text-blue-700 font-weight-bold'
                                : 'text-gray-900 dark:!text-white'
                        ]"
                        @click="changeLanguage(language.code)"
                    >
                        <span class="relative z-10 flex items-center">
                            <span class="pr-2 flex items-center">
                                <img
                                    :src="`/img/lang/${language.code}.png`"
                                    class="w-6 h-4 object-cover transition-opacity duration-300 align-middle inline-block"
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
import { ChevronDown, Globe } from 'lucide-vue-next';

const languages = [
    { code: 'en', name: 'English' },
    { code: 'uk', name: 'Українська' },
    { code: 'ru', name: 'Русский' },
    { code: 'zh', name: '中文' },
    { code: 'es', name: 'Español' }
];

const { locale } = useI18n();
const isOpen = ref(false);
const dropdownRef = ref<HTMLElement | null>(null);

const currentLocale = computed(() => locale.value);

const currentLanguage = computed(
    () => languages.find(lang => lang.code === currentLocale.value) || languages[0]
);

const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
};

const changeLanguage = (code: string) => {
    locale.value = code;
    localStorage.setItem('user-language', code);
    isOpen.value = false;
};

const handleClickOutside = (event: MouseEvent) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
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
        img.src = `/img/lang/${lang.code}.png`;
    });

    document.addEventListener('mousedown', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('mousedown', handleClickOutside);
});
</script>

<style scoped>
.language-selector {
    @apply px-3 flex py-2 bg-white/80 dark:bg-gray-700 backdrop-blur-sm rounded-lg hover:bg-gray-100/80 dark:hover:bg-gray-600 transition-all duration-300;
}
</style>
