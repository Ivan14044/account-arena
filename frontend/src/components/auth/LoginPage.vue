<template>
    <div
        class="login-page-background min-h-screen py-3 w-full flex items-center justify-center"
    >
        <div
            class="login-container max-w-sm w-full lg:w-1/2 flex items-center justify-center p-8 rounded-[12px] relative"
        >
            <div class="w-full">
                <div class="flex items-center justify-end absolute top-3 right-3">
                    <LanguageSelect />
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
                    {{ $t('auth.loginTitle') }}
                </h1>
                <p class="text-gray-500 dark:text-gray-300 mb-6 text-center">
                    {{ $t('auth.loginSubtitle') }}
                </p>

                <form class="space-y-4" @submit.prevent="handleSubmit">
                    <div class="space-y-2">
                        <div class="relative">
                            <input
                                id="email"
                                v-model="email"
                                type="email"
                                class="input-field dark:!border-gray-500 dark:text-gray-300"
                                :placeholder="$t('auth.email')"
                                :disabled="isSocialAuthLoading"
                                required
                            />
                        </div>
                        <p v-if="errors.email" class="text-red-500 text-sm">
                            {{ errors.email[0] }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <div class="relative">
                            <input
                                id="password"
                                v-model="password"
                                type="password"
                                class="input-field dark:!border-gray-500 dark:text-gray-300"
                                :placeholder="$t('auth.password')"
                                :disabled="isSocialAuthLoading"
                                required
                            />
                        </div>
                        <p v-if="errors.password" class="text-red-500 text-sm">
                            {{ errors.password[0] }}
                        </p>
                    </div>

                    <div class="flex flex-row items-center gap-2 justify-between">
                        <div class="relative">
                            <input
                                id="remember"
                                v-model="remember"
                                type="checkbox"
                                :disabled="isSocialAuthLoading"
                                class="mr-2"
                            />
                            <label for="remember" class="remember-label text-sm">
                                {{ $t('auth.rememberMe') }}
                            </label>
                        </div>
                        <div class="relative">
                            <router-link
                                to="/forgot-password"
                                class="text-sm text-[#0065FF] dark:text-blue-600 hover:!text-blue-500"
                            >
                                {{ $t('auth.forgotPassword') }}
                            </router-link>
                        </div>
                    </div>

                    <div>
                        <button
                            type="submit"
                            :disabled="isSocialAuthLoading"
                            class="auth-button primary"
                        >
                            {{ $t('auth.loginButton') }}
                        </button>
                    </div>
                    <p class="text-center text-sm text-gray-600 dark:text-gray-300 mt-5">
                        {{ $t('auth.noAccount') }}
                        <router-link
                            :to="{
                                path: '/register',
                                query: redirectQuery ? { redirect: redirectQuery } : undefined
                            }"
                            class="inline-flex items-center justify-center ml-2 px-2.5 py-1 text-xs border rounded-md bg-white text-gray-900 hover:!text-white hover:!bg-blue-600 transition font-medium dark:bg-gray-800 dark:text-gray-100 dark:hover:!bg-blue-700 dark:hover:!text-white"
                        >
                            {{ $t('auth.registerLink') }}
                        </router-link>
                    </p>
                </form>
                <SocialAuthButtons @social-auth-status="handleSocialAuthStatus" />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import SocialAuthButtons from './SocialAuthButtons.vue';
import LanguageSelect from '@/components/layout/LanguageSelect.vue';
import logo from '@/assets/logo.webp';

const email = ref('');
const password = ref('');
const remember = ref(false);
const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const errors = ref({});
const isSocialAuthLoading = ref(false);

const redirectQuery = route.query.redirect as string | undefined;

const handleSocialAuthStatus = (loading: boolean) => {
    isSocialAuthLoading.value = loading;
};

// Removed inline register navigation button; registration link remains below the form

const handleSubmit = async () => {
    console.log('[LOGIN PAGE] Начало обработки формы логина');
    console.log('[LOGIN PAGE] Email:', email.value);

    try {
        const success = await authStore.login({
            email: email.value,
            password: password.value,
            remember: remember.value
        });

        console.log('[LOGIN PAGE] Результат авторизации:', success);
        errors.value = authStore.errors;

        if (success) {
            console.log('[LOGIN PAGE] Авторизация успешна, подготовка к редиректу...');

            // Небольшая задержка для гарантии сохранения данных
            await new Promise(resolve => setTimeout(resolve, 50));

            const redirectTo = route.query.redirect as string;
            console.log('[LOGIN PAGE] Редирект на:', redirectTo || '/');

            await router.push(redirectTo || '/');
            console.log('[LOGIN PAGE] Редирект выполнен');
        } else {
            console.log('[LOGIN PAGE] Авторизация не удалась');
            console.log('[LOGIN PAGE] Ошибки:', errors.value);
        }
    } catch (error) {
        console.error('[LOGIN PAGE] Критическая ошибка:', error);
        errors.value = authStore.errors || {};
    }
};
</script>

<style scoped>
/* Фон страницы входа */
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
    0%, 100% {
        background-position: 0% 50%;
    }
    50% {
        background-position: 100% 50%;
    }
}

/* Контейнер окна входа с glass effect */
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

/* Стили для метки "Запомнить меня" */
.remember-label {
    color: #1f2937;
}

.dark .remember-label {
    color: #d1d5e1;
}
</style>
