<template>
    <div
        class="login-page-background min-h-screen py-3 w-full flex items-center justify-center"
    >
        <div
            class="login-container max-w-sm w-full lg:w-1/2 flex items-center justify-center p-8 rounded-[12px] relative"
        >
            <div class="w-full">
                <div class="flex items-center justify-end absolute top-3 right-3">
                    <LanguageSelector />
                </div>
                <div class="flex items-center justify-center mb-6 -mt-2 w-full">
                    <router-link to="/" class="flex items-center flex-col">
                        <img
                            :src="logo"
                            alt="Loading..."
                            class="w-16 h-16 object-contain spin-slow-reverse mb-2"
                        />
                        <span
                            class="text-black dark:!text-white mt-1 text-sm font-semibold leading-none"
                        >
                            Account Arena
                        </span>
                    </router-link>
                </div>

                <h1 class="text-2xl text-gray-900 dark:text-white mb-2 text-center font-medium">
                    {{ $t('auth.forgotPasswordTitle') }}
                </h1>
                <p class="text-gray-500 dark:text-gray-300 mb-6 text-center">
                    {{ $t('auth.forgotPasswordSubtitle') }}
                </p>

                <form class="space-y-4" @submit.prevent="handleSubmit">
                    <p
                        v-if="sent"
                        class="text-center text-sm text-green-600 bg-green-100 border border-green-300 p-4 rounded"
                    >
                        {{ $t('auth.guide') }}
                    </p>
                    <template v-else>
                        <div class="space-y-2">
                            <div class="relative">
                                <input
                                    id="email"
                                    v-model="email"
                                    type="email"
                                    class="input-field dark:!border-gray-500 dark:text-gray-300"
                                    :placeholder="$t('auth.email')"
                                    required
                                />
                            </div>
                            <p v-if="errors.email" class="text-red-500 text-sm">
                                {{ errors.email[0] }}
                            </p>
                            <p v-if="errors.message" class="text-red-500 text-sm">
                                {{ errors.message[0] }}
                            </p>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <button type="submit" class="auth-button primary">
                                {{ $t('auth.forgotPasswordSubmit') }}
                            </button>
                        </div>
                    </template>
                </form>
                <SocialAuthButtons />
                <p class="text-center text-sm text-gray-500 mt-3">
                    {{ $t('auth.haveAccount') }}
                    <router-link
                        to="/login"
                        class="text-[#0065FF] dark:text-blue-600 hover:!text-blue-500"
                        >{{ $t('auth.loginLink') }}</router-link
                    >
                </p>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useSeo } from '@/composables/useSeo';
import SocialAuthButtons from './SocialAuthButtons.vue';
import LanguageSelector from '@/components/layout/LanguageSelector.vue';
import { useAuthStore } from '../../stores/auth';
import logo from '@/assets/logo.webp';

const { t } = useI18n();

// SEO мета-теги (noindex для служебной страницы)
useSeo({
    title: () => t('auth.forgotPasswordTitle') || 'Восстановление пароля',
    description: () => t('auth.forgotPasswordSubtitle') || 'Восстановите доступ к своему аккаунту Account Arena',
    noindex: true
});

const authStore = useAuthStore();

const email = ref('');
const errors = ref({});
const sent = ref(false);

const loading = ref(false);

const handleSubmit = async () => {
    loading.value = true;
    sent.value = false;
    errors.value = {};

    const success = await authStore.forgotPassword({
        email: email.value
    });
    errors.value = authStore.errors;

    if (success) {
        sent.value = success;
    }

    loading.value = false;
};
</script>

<style scoped>
/* Фон страницы восстановления пароля */
.login-page-background {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 50%, #f8f9fa 100%);
    background-size: 200% 200%;
    animation: gradientShift 15s ease infinite;
}

.dark .login-page-background {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 50%, #1e293b 100%);
    background-size: 200% 200%;
}

@keyframes gradientShift {
    0%,
    100% {
        background-position: 0% 50%;
    }
    50% {
        background-position: 100% 50%;
    }
}

/* Контейнер окна восстановления с glass effect */
.login-container {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(226, 232, 240, 0.6);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.dark .login-container {
    background: rgba(30, 41, 59, 0.85);
    border-color: rgba(51, 65, 85, 0.6);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
}

.login-container:hover {
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
    border-color: rgba(108, 92, 231, 0.3);
}

.dark .login-container:hover {
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.4);
    border-color: rgba(108, 92, 231, 0.4);
}

.input-field {
    @apply w-full px-4 py-3 border border-solid border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(8px);
}

.dark .input-field {
    background: rgba(30, 41, 59, 0.9);
    border-color: rgba(51, 65, 85, 0.8);
}

.input-field:focus {
    background: rgba(255, 255, 255, 1);
    border-color: rgba(108, 92, 231, 0.5);
}

.dark .input-field:focus {
    background: rgba(30, 41, 59, 1);
    border-color: rgba(108, 92, 231, 0.6);
}

.auth-button {
    @apply w-full text-gray-900 hover:text-white bg-white hover:!bg-blue-600 border font-medium py-[10px] rounded-lg transition-all flex items-center justify-center gap-2;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(8px);
    border-color: rgba(226, 232, 240, 0.8);
}

.dark .auth-button {
    background: rgba(30, 41, 59, 0.9);
    border-color: rgba(51, 65, 85, 0.8);
    color: #f3f4f6;
}

.auth-button:hover:not(:disabled) {
    background: rgba(37, 99, 235, 0.95);
    border-color: rgba(37, 99, 235, 0.8);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.auth-button:disabled {
    @apply opacity-50 cursor-not-allowed text-[#0065FF]/80 border-[#0065FF]/80;
}

.auth-button.primary {
    @apply bg-blue-500 dark:bg-blue-900 text-white hover:bg-blue-600 text-white;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    border: none;
    box-shadow: 0 4px 14px rgba(37, 99, 235, 0.3);
}

.dark .auth-button.primary {
    background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
    box-shadow: 0 4px 14px rgba(30, 64, 175, 0.4);
}

.auth-button.primary:hover:not(:disabled) {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4);
}

.dark .auth-button.primary:hover:not(:disabled) {
    background: linear-gradient(135deg, #1e3a8a 0%, #1e3a8a 100%);
    box-shadow: 0 6px 20px rgba(30, 64, 175, 0.5);
}

.auth-button.primary:disabled {
    @apply opacity-50 cursor-not-allowed bg-[#0065FF]/80;
}
</style>
