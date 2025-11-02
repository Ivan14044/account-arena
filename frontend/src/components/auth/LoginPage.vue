<template>
    <div
        class="min-h-screen py-3 w-full bg-[#fafafa] dark:bg-gray-900 flex items-center justify-center"
    >
        <div
            class="max-w-sm w-full lg:w-1/2 flex items-center justify-center p-8 bg-white dark:!bg-gray-800 rounded-[12px] shadow-lg relative"
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
                            SubCloudy
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
                                required
                            />
                        </div>
                        <p v-if="errors.password" class="text-red-500 text-sm">
                            {{ errors.password[0] }}
                        </p>
                    </div>

                    <div class="flex flex-row items-center gap-2 justify-between">
                        <div class="relative">
                            <input id="remember" v-model="remember" type="checkbox" class="mr-2" />
                            <label for="remember" class="text-sm dark:text-gray-300">
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
                        <button type="submit" class="auth-button primary">
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
                <SocialAuthButtons />
                
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

const redirectQuery = route.query.redirect as string | undefined;

// Removed inline register navigation button; registration link remains below the form

const handleSubmit = async () => {
    const success = await authStore.login({
        email: email.value,
        password: password.value,
        remember: remember.value
    });
    errors.value = authStore.errors;

    if (success) {
        const redirectTo = route.query.redirect as string;
        router.push(redirectTo || '/');
    }
};
</script>

<style scoped>
.input-field {
    @apply w-full px-4 py-3 border border-solid border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all;
}
.auth-button {
    @apply w-full text-gray-900 hover:text-white bg-white hover:!bg-blue-600 border font-medium py-[10px] rounded-lg transition-all flex items-center justify-center gap-2;
}
.auth-button:disabled {
    @apply opacity-50 cursor-not-allowed text-[#0065FF]/80 border-[#0065FF]/80;
}
.auth-button.primary {
    @apply bg-blue-500 dark:bg-blue-900 text-white hover:bg-blue-600 text-white;
}
.auth-button.primary:disabled {
    @apply opacity-50 cursor-not-allowed bg-[#0065FF]/80;
}
</style>
