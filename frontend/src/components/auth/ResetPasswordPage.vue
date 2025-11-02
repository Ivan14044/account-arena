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
                    {{ $t('auth.resetPasswordTitle') }}
                </h1>
                <p class="text-gray-500 dark:text-gray-300 mb-6 text-center">
                    {{ $t('auth.resetPasswordSubtitle') }}
                </p>

                <form v-if="!resetSuccess" class="space-y-4" @submit.prevent="handleSubmit">
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

                    <div class="space-y-2">
                        <div class="relative">
                            <input
                                id="password_confirmation"
                                v-model="passwordConfirmation"
                                type="password"
                                class="input-field dark:!border-gray-500 dark:text-gray-300"
                                :placeholder="$t('auth.passwordConfirmation')"
                                required
                            />
                        </div>
                        <p v-if="errors.password_confirmation" class="text-red-500 text-sm">
                            {{ errors.password_confirmation[0] }}
                        </p>
                        <p v-if="errors.message" class="text-red-500 text-sm">
                            {{ errors.message[0] }}
                        </p>
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        <button type="submit" class="auth-button primary">
                            {{ $t('auth.resetPasswordSubmit') }}
                        </button>
                    </div>
                </form>

                <div v-else class="text-center">
                    <p
                        class="text-center text-sm text-green-600 bg-green-100 border border-green-300 p-4 rounded my-5"
                    >
                        {{ $t('auth.resetPasswordSuccess') }}
                    </p>
                    <button class="auth-button primary" @click="router.push('/login')">
                        {{ $t('auth.loginButton') }}
                    </button>
                </div>

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
import { useRouter, useRoute } from 'vue-router';
import LanguageSelect from '../layout/LanguageSelect.vue';
import { useAuthStore } from '../../stores/auth';
import logo from '@/assets/logo.webp';

const props = defineProps({
    token: {
        type: String,
        required: true
    }
});

const router = useRouter();
const authStore = useAuthStore();

const password = ref('');
const passwordConfirmation = ref('');
const errors = ref({});
const resetSuccess = ref(false);
const loading = ref(false);

const route = useRoute();
const email = ref(route.query.email || '');

const handleSubmit = async () => {
    loading.value = true;
    errors.value = {};

    const success = await authStore.resetPassword({
        email: email.value,
        password: password.value,
        password_confirmation: passwordConfirmation.value,
        token: props.token
    });

    errors.value = authStore.errors;

    if (success) {
        resetSuccess.value = true;
    }

    loading.value = false;
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
