<template>
    <div class="flex flex-col space-y-4 mt-6">
        <div class="relative">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-200"></div>
            </div>
            <div class="relative flex justify-center text-sm">
                <span class="separator">{{ $t('auth.orContinueWith') }}</span>
            </div>
        </div>

        <div class="flex flex-col gap-3">
            <!-- Google кнопка -->
            <a
                class="social-auth-button google-button flex items-center justify-center border border-gray-300 rounded-lg px-4 py-3 hover:bg-gray-50 transition-colors cursor-pointer"
                :class="{ 'opacity-50 cursor-not-allowed': isSocialAuthLoading }"
                @click="!isSocialAuthLoading && openGoogleAuth()"
            >
                <img class="w-5 h-5 mr-2" src="/img/google.png" alt="google" />
                <span>{{ $t('auth.google') }}</span>
            </a>

            <!-- Telegram кнопка -->
            <button
                class="flex items-center justify-center border-[#1a94d2] rounded-lg px-4 py-3 bg-[#1a94d2] hover:bg-[#1a94d2]/80 transition-colors"
                :class="{ 'opacity-50 cursor-not-allowed': isSocialAuthLoading }"
                :disabled="isSocialAuthLoading"
                @click="initTelegramAuth"
            >
                <img class="w-5 h-5 mr-2" src="/img/telegram.png" alt="telegram" />
                <span class="text-white">{{ $t('auth.telegram') }}</span>
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import '../../types/telegram.d.ts';

const authStore = useAuthStore();

const router = useRouter();
const route = useRoute();

const emit = defineEmits<{
    'social-auth-status': [loading: boolean];
}>();

const isSocialAuthLoading = ref(false);
const emitStatus = () => {
    emit('social-auth-status', isSocialAuthLoading.value);
};

const openGoogleAuth = () => {
    // Set loading state
    isSocialAuthLoading.value = true;
    emit('social-auth-status', isSocialAuthLoading.value);

    // Используем специальный роут для принудительного выбора аккаунта
    const url = '/auth/google/reauth';
    const features = `
    toolbar=no,
    location=no,
    status=no,
    menubar=no,
    scrollbars=yes,
    resizable=yes,
    width=800,
    height=600
  `;

    const popup = window.open(url, 'googleAuth', features.replace(/\s+/g, ''));

    // Слушаем сообщения от popup окна
    const messageListener = async (event: MessageEvent) => {
        // Проверяем, что сообщение от нашего домена
        if (event.origin !== window.location.origin) {
            return;
        }

        if (event.data.type === 'GOOGLE_AUTH_SUCCESS') {
            // Успешная авторизация
            const { token, user } = event.data.data;

            // Устанавливаем новый токен и пользователя
            authStore.setToken(token);
            authStore.setUser(user);

            // Убираем слушатель
            window.removeEventListener('message', messageListener);

            // Reset loading state
            isSocialAuthLoading.value = false;
            emit('social-auth-status', isSocialAuthLoading.value);

            // Принудительно обновляем страницу для полного обновления состояния
            const redirectTo = route.query.redirect as string;
            router.push(redirectTo || '/');
        } else if (event.data.type === 'GOOGLE_AUTH_ERROR') {
            // Ошибка авторизации
            console.error('Google auth error:', event.data.data.error);

            // Убираем слушатель
            window.removeEventListener('message', messageListener);

            // Reset loading state
            isSocialAuthLoading.value = false;
            emit('social-auth-status', isSocialAuthLoading.value);
        }
    };

    // Добавляем слушатель сообщений
    window.addEventListener('message', messageListener);

    // Убираем слушатель если popup закрыт без авторизации
    const checkClosed = setInterval(() => {
        if (popup?.closed) {
            window.removeEventListener('message', messageListener);
            clearInterval(checkClosed);

            // Reset loading state if popup closed
            if (isSocialAuthLoading.value) {
                isSocialAuthLoading.value = false;
                emit('social-auth-status', isSocialAuthLoading.value);
            }
        }
    }, 1000);
};

// Метод для инициализации Telegram Login Widget
const initTelegramAuth = () => {
    // Set loading state
    isSocialAuthLoading.value = true;
    emit('social-auth-status', isSocialAuthLoading.value);

    // Проверяем, загружен ли Telegram Widget скрипт
    if (!window.Telegram) {
        loadTelegramScript();
    } else {
        showTelegramPopup();
    }
};

// Загрузка Telegram Widget скрипта
const loadTelegramScript = () => {
    if (document.getElementById('telegram-login-script')) return;

    const script = document.createElement('script');
    script.id = 'telegram-login-script';
    script.src = 'https://telegram.org/js/telegram-widget.js';
    script.async = true;
    script.onload = showTelegramPopup;
    script.onerror = () => {
        // Reset loading state if script fails to load
        isSocialAuthLoading.value = false;
        emitStatus();
        console.error('Failed to load Telegram widget script');
    };
    document.head.appendChild(script);
};

// Показать всплывающее окно Telegram авторизации
const showTelegramPopup = () => {
    // Для этого вам нужно создать Telegram бота и получить его имя
    const botId = (import.meta as any).env.VITE_APP_TELEGRAM_BOT_ID || '';

    if (window.Telegram && window.Telegram.Login) {
        window.Telegram.Login.auth({ bot_id: botId }, data => {
            if (data) {
                handleTelegramAuth(data);
            } else {
                // User cancelled or auth failed
                isSocialAuthLoading.value = false;
                emitStatus();
            }
        });
    } else {
        // Telegram widget not available
        isSocialAuthLoading.value = false;
        emitStatus();
        console.error('Telegram widget not available');
    }
};

// Обработка данных авторизации Telegram
const handleTelegramAuth = async (data: TelegramUser) => {
    try {
        const response = await fetch('/auth/telegram/callback', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.token) {
            authStore.setToken(result.token);
            authStore.setUser(result.user);

            // Reset loading state
            isSocialAuthLoading.value = false;
            emitStatus();

            const redirectTo = route.query.redirect as string;
            router.push(redirectTo || '/');
        } else {
            // Reset loading state on error
            isSocialAuthLoading.value = false;
            emitStatus();
        }
    } catch (error) {
        console.error('Telegram auth error:', error);
        // Reset loading state on error
        isSocialAuthLoading.value = false;
        emitStatus();
    }
};
</script>

<style scoped>
.separator {
    @apply bg-white dark:bg-gray-800 dark:text-gray-300 px-4 text-gray-500;
}

/* Стили для кнопки Google */
.google-button {
    color: #1f2937;
}

.dark .google-button {
    color: #ffffff;
}

.google-button:hover:not(.opacity-50) {
    background-color: rgba(249, 250, 251, 0.9);
}

.dark .google-button:hover:not(.opacity-50) {
    background-color: rgba(209, 213, 219, 0.2);
    color: #1f2937;
}
</style>
