<template>
    <button
        type="button"
        class="flex items-center gap-2 text-dark dark:text-white hover:text-gray-600 dark:hover:text-gray-200 transition-colors glass-button px-4 py-2 rounded-full"
        @click="goBack"
    >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M10 19l-7-7m0 0l7-7m-7 7h18"
            />
        </svg>
        <slot>
            {{ $t('services.page.back') }}
        </slot>
    </button>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router';

const router = useRouter();

const goBack = () => {
    const current = router.currentRoute.value;
    const isArticlesList = Boolean(current.meta?.isArticlesList);

    if (isArticlesList) {
        try {
            const saved = sessionStorage.getItem('articlesEntryFrom');
            if (saved) {
                router.push(saved);
                return;
            }
        } catch (_) {
            // ignore storage errors
        }
    }

    if (window.history.length > 1) {
        router.back();
        return;
    }

    router.push('/');
};
</script>
