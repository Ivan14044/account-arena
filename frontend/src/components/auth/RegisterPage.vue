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
                    {{ $t('auth.registerTitle') }}
                </h1>
                <p class="text-gray-500 dark:text-gray-300 mb-6 text-center">
                    {{ $t('auth.registerSubtitle') }}
                </p>

                <form class="space-y-4" @submit.prevent="handleSubmit">
                    <div class="space-y-2">
                        <div class="relative">
                            <input
                                id="name"
                                v-model="name"
                                type="text"
                                class="input-field dark:!border-gray-500 dark:text-gray-300"
                                :placeholder="$t('auth.name')"
                                :disabled="isSocialAuthLoading"
                                required
                            />
                        </div>
                        <p v-if="errors.name" class="text-red-500 text-sm">
                            {{ errors.name[0] }}
                        </p>
                    </div>

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

                        <!-- Индикатор сложности пароля -->
                        <div v-if="password" class="mt-2 space-y-2">
                            <div class="flex gap-1 h-1.5 w-full">
                                <div 
                                    v-for="i in 4" :key="i"
                                    class="h-full flex-1 rounded-full transition-all duration-500"
                                    :class="[
                                        passwordStrength >= i 
                                            ? strengthColors[passwordStrength - 1] 
                                            : 'bg-gray-200 dark:bg-gray-700'
                                    ]"
                                ></div>
                            </div>
                            <div class="flex justify-between items-center text-[11px] font-medium">
                                <span :class="strengthTextColors[passwordStrength - 1]">
                                    {{ $t(`auth.strength${strengthLabels[passwordStrength - 1]}`) }}
                                </span>
                            </div>
                            
                            <!-- Список требований -->
                            <ul class="grid grid-cols-2 gap-x-4 gap-y-1 mt-2">
                                <li 
                                    v-for="(req, key) in requirements" 
                                    :key="key"
                                    class="flex items-center gap-1.5 text-[11px] transition-colors duration-300"
                                    :class="req.met ? 'text-green-500' : 'text-gray-400'"
                                >
                                    <svg class="w-3 h-3 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                        <path v-if="req.met" fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        <circle v-else cx="10" cy="10" r="8" fill="none" stroke="currentColor" stroke-width="2" />
                                    </svg>
                                    {{ $t(`auth.req${key.charAt(0).toUpperCase() + key.slice(1)}`) }}
                                </li>
                            </ul>
                        </div>

                        <p v-if="errors.password" class="text-red-500 text-sm">
                            {{ errors.password[0] }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <div class="relative">
                            <input
                                id="passwordConfirmation"
                                v-model="passwordConfirmation"
                                type="password"
                                class="input-field dark:!border-gray-500 dark:text-gray-300"
                                :placeholder="$t('auth.passwordConfirmation')"
                                :disabled="isSocialAuthLoading"
                                required
                            />
                        </div>
                        <p v-if="errors.passwordConfirmation" class="text-red-500 text-sm">
                            {{ errors.passwordConfirmation[0] }}
                        </p>
                    </div>

                    <div>
                        <button
                            type="submit"
                            :disabled="loading || isSocialAuthLoading"
                            class="auth-button primary"
                        >
                            <span>{{ $t('auth.registerButton') }}</span>
                        </button>
                    </div>
                    <p class="text-center text-sm text-gray-600 dark:text-gray-300 mt-5">
                        {{ $t('auth.haveAccount') }}
                        <router-link
                            :to="{
                                path: '/login',
                                query: redirectQuery ? { redirect: redirectQuery } : undefined
                            }"
                            class="inline-flex items-center justify-center ml-2 px-2.5 py-1 text-xs border rounded-md bg-white text-gray-900 hover:!text-white hover:!bg-blue-600 transition font-medium dark:bg-gray-800 dark:text-gray-100 dark:hover:!bg-blue-700 dark:hover:!text-white"
                        >
                            {{ $t('auth.loginLink') }}
                        </router-link>
                    </p>
                </form>
                <SocialAuthButtons @social-auth-status="handleSocialAuthStatus" />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '../../stores/auth';
import { useSeo } from '@/composables/useSeo';
import LanguageSelector from '@/components/layout/LanguageSelector.vue';
import SocialAuthButtons from './SocialAuthButtons.vue';
import logo from '@/assets/logo.webp';

const { t } = useI18n();

// SEO мета-теги (noindex для служебной страницы)
useSeo({
    title: () => t('auth.registerTitle') || 'Регистрация',
    description: () => t('auth.registerSubtitle') || 'Создайте новый аккаунт в Account Arena',
    noindex: true
});

const name = ref('');
const email = ref('');
const password = ref('');
const passwordConfirmation = ref('');

// Логика сложности пароля
const strengthColors = ['bg-red-500', 'bg-orange-500', 'bg-yellow-500', 'bg-green-500'];
const strengthTextColors = ['text-red-500', 'text-orange-500', 'text-yellow-500', 'text-green-500'];
const strengthLabels = ['Weak', 'Medium', 'Strong', 'VeryStrong'];

const requirements = computed(() => ({
    length: { met: password.value.length >= 8 },
    letters: { met: /[a-zA-Zа-яА-ЯёЁ]/.test(password.value) },
    numbers: { met: /[0-9]/.test(password.value) }
}));

const passwordStrength = computed(() => {
    if (!password.value) return 0;
    let score = 0;
    const reqs = requirements.value;
    
    if (reqs.length.met) score++;
    if (reqs.letters.met) score++;
    if (reqs.numbers.met) score++;
    
    // Бонус за сложность: разный регистр ИЛИ спецсимволы
    if ((/[A-ZА-ЯЁ]/.test(password.value) && /[a-zа-яё]/.test(password.value)) || 
        /[^A-Za-z0-9а-яА-ЯёЁ]/.test(password.value)) {
        score++;
    }
    
    return Math.min(score, 4);
});

const errors = ref<{
    name?: string;
    email?: string;
    password?: string;
    passwordConfirmation?: string;
}>({});
const loading = ref(false);
const isSocialAuthLoading = ref(false);
const authStore = useAuthStore();
const router = useRouter();
const route = useRoute();

const redirectQuery = route.query.redirect as string | undefined;

const handleSocialAuthStatus = (loading: boolean) => {
    isSocialAuthLoading.value = loading;
};

// Removed inline login button; navigation link is placed under the submit button

const handleSubmit = async () => {
    loading.value = true;
    errors.value = {};

    const success = await authStore.register({
        name: name.value,
        email: email.value,
        password: password.value,
        password_confirmation: passwordConfirmation.value
    });
    errors.value = authStore.errors;

    if (success) {
        const redirectTo = route.query.redirect as string;
        router.push(redirectTo || '/');
    }

    loading.value = false;
};
</script>

<style scoped>
/* Фон страницы регистрации */
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

/* Контейнер окна регистрации с glass effect */
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
