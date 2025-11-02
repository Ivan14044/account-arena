<template>
    <div class="min-h-screen flex items-center justify-center">
        <div class="text-center">
            <h2 class="text-2xl font-medium mb-4">
                {{ $t('auth.authenticating') }}
            </h2>
            <div class="spinner"></div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';

const router = useRouter();
const authStore = useAuthStore();

onMounted(async () => {
    // Получаем токен из URL
    const urlParams = new URLSearchParams(window.location.search);
    const token = urlParams.get('token');

    if (token) {
        // Сохраняем токен
        authStore.setToken(token);

        // Получаем данные пользователя
        await authStore.fetchUser();

        // Перенаправляем на главную
        router.push('/');
    } else {
        // В случае ошибки перенаправляем на страницу входа
        router.push('/login?error=callback');
    }
});
</script>

<style scoped>
.spinner {
    border: 3px solid rgba(0, 0, 0, 0.1);
    border-radius: 50%;
    border-top: 3px solid #000;
    width: 30px;
    height: 30px;
    animation: spin 1s linear infinite;
    margin: 0 auto;
}

@keyframes spin {
    0% {
        transform: rotate(0deg);
    }
    100% {
        transform: rotate(360deg);
    }
}
</style>
