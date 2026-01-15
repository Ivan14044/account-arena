<template>
    <div v-if="isAuthenticated" id="userMenu" ref="dropdownRef" class="flex gap-5 relative" style="overflow: visible;">
        <button
            ref="buttonRef"
            class="px-2 lg:px-3 flex py-2 h-[32px] text-base leading-4 items-center rounded-lg hover:bg-indigo-200 dark:hover:bg-gray-700 transition-all duration-300 group"
            @click="toggleDropdown"
            :aria-label="$t('auth.userMenu') || 'User menu'"
            :aria-expanded="isOpen"
        >
            <User class="w-5 h-5 flex-shrink-0" />

            <span class="flex items-center sm:pl-2 min-w-0 gap-2">
                <span
                    v-if="authStore.user?.name"
                    class="hidden md:inline-block truncate whitespace-nowrap overflow-hidden font-normal text-[15px]"
                    :class="[
                        'max-w-[90px]', // < md
                        'xl:max-w-[150px]', // ≥ xl
                        '2xl:max-w-[220px]' // ≥ 2xl
                    ]"
                    :title="authStore.user.name"
                >
                    {{ authStore.user.name }}
                </span>

                <ChevronDown
                    :class="[
                        'xl:ml-1 w-4 h-4 flex-shrink-0 transition-transform duration-300',
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
                ref="menuRef"
                class="user-menu-dropdown absolute top-full mt-2 right-0 min-w-[160px] z-[9999]"
            >
                <div class="liquid-glass-effect"></div>
                <div class="liquid-glass-tint"></div>
                <div class="liquid-glass-shine"></div>
                
                <div class="liquid-glass-text flex flex-col w-full relative z-10">
                    <!-- Баланс (мобильная версия + всегда показываем) -->
                    <div
                        class="px-4 py-3 border-b border-white/10 dark:border-gray-700 bg-green-500/10 dark:bg-green-600/10"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-600 dark:text-gray-400"
                                >{{ $t('profile.balance') }}:</span
                            >
                            <span
                                class="text-sm font-bold text-green-700 dark:text-green-400 flex items-center gap-1"
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                                {{ formatBalance(authStore.user?.balance || 0) }}
                            </span>
                        </div>
                    </div>

                    <button
                        class="flex items-center gap-3 w-full px-4 py-3 text-sm text-left hover:bg-indigo-200 dark:hover:bg-gray-700 transition-colors relative"
                        @click="navigateTo('/balance/topup')"
                    >
                        <span class="relative z-10 flex whitespace-nowrap gap-2 items-center">
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 4v16m8-8H4"
                                />
                            </svg>
                            {{ $t('profile.topup') }}
                        </span>
                    </button>

                    <button
                        class="flex items-center gap-3 w-full px-4 py-3 text-sm text-left hover:bg-indigo-200 dark:hover:bg-gray-700 transition-colors relative"
                        @click="navigateTo('/profile')"
                    >
                        <span class="relative z-10 flex whitespace-nowrap gap-2 items-center">
                            <UserPen class="w-5" />
                            {{ $t('auth.profile') }}
                        </span>
                    </button>
                    <button
                        class="flex items-center gap-3 w-full px-4 py-3 text-sm text-left hover:bg-indigo-200 dark:hover:bg-gray-700 transition-colors relative"
                        @click="handleAuthAction"
                    >
                        <span class="relative z-10 flex whitespace-nowrap gap-2 items-center text-red-500 dark:text-red-400">
                            <LogOut class="w-5" />
                            {{ $t('auth.logoutLink') }}
                        </span>
                    </button>
                </div>
            </div>
        </Transition>
    </div>
    <div v-else id="loginMenu" ref="dropdownRef" class="d-flex align-center top-3 right-6 z-50">
        <button
            class="px-2 px-lg-3 d-flex py-2 h-[32px] text-base leading-4 align-center backdrop-blur-sm rounded-lg hover:bg-indigo-200 dark:hover:bg-gray-700 transition-all duration-300 group"
            @click="handleAuthAction"
        >
            <LogIn class="w-5 h-5" />
            <span class="pl-2">
                {{ $t('auth.loginLink') }}
            </span>
        </button>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';
import { LogIn, User, ChevronDown, LogOut, UserPen } from 'lucide-vue-next';
import { useOptionStore } from '@/stores/options';
import { useProductCartStore } from '@/stores/productCart';
import { useNotificationStore } from '@/stores/notifications';

const authStore = useAuthStore();
const productCartStore = useProductCartStore();
const optionStore = useOptionStore();
const notificationStore = useNotificationStore();
const router = useRouter();
const isOpen = ref(false);
const dropdownRef = ref<HTMLElement | null>(null);
const buttonRef = ref<HTMLElement | null>(null);
const menuRef = ref<HTMLElement | null>(null);
const isAuthenticated = computed(() => !!authStore.user);

// Форматирование баланса
const formatBalance = (balance: number | string) => {
    const currency = optionStore.getOption('currency', 'USD');
    const numBalance = typeof balance === 'string' ? parseFloat(balance) : Number(balance);
    
    // Используем Intl.NumberFormat для консистентности
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(numBalance);
};

const handleAuthAction = () => {
    if (isAuthenticated.value) {
        authStore.logout();
        isOpen.value = false;
        productCartStore.clearCart();
        notificationStore.resetStore();
        router.push('/');
    } else {
        router.push('/login');
    }
};

const navigateTo = async (path: string) => {
    isOpen.value = false;
    await router.push(path);
};

const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
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
        menuRef.value &&
        !menuRef.value.contains(target)
    ) {
        isOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('mousedown', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('mousedown', handleClickOutside);
});
</script>

<style scoped>
/* Liquid Glass Effect для dropdown меню пользователя */
.user-menu-dropdown {
    position: absolute;
    box-shadow: 0 6px 6px rgba(0, 0, 0, 0.2), 0 0 20px rgba(0, 0, 0, 0.1);
    border-radius: 0.5rem; /* rounded-lg */
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.user-menu-dropdown .liquid-glass-effect {
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

.user-menu-dropdown .liquid-glass-tint {
    z-index: 1;
    position: absolute;
    inset: 0;
    background: rgba(255, 255, 255, 0.25);
    border-radius: 0.5rem;
}

.dark .user-menu-dropdown .liquid-glass-tint {
    background: rgba(31, 41, 55, 0.4);
}

.user-menu-dropdown .liquid-glass-shine {
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

.dark .user-menu-dropdown .liquid-glass-shine {
    box-shadow: 
        inset 2px 2px 1px 0 rgba(255, 255, 255, 0.1),
        inset -1px -1px 1px 1px rgba(255, 255, 255, 0.1);
}

.user-menu-dropdown .liquid-glass-text {
    z-index: 3;
    position: relative;
    display: flex;
    flex-direction: column;
    width: 100%;
}

.user-menu-dropdown .liquid-glass-text button {
    color: #1f2937; /* text-gray-900 */
}

.dark .user-menu-dropdown .liquid-glass-text button {
    color: #f9fafb; /* dark:text-white */
}
</style>
