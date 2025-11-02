<template>
    <div v-if="isAuthenticated" id="userMenu" ref="dropdownRef" class="d-flex gap-5 relative">
        <button
            class="px-2 px-lg-3 d-flex py-2 h-[32px] text-base leading-4 items-center rounded-lg hover:bg-indigo-200 dark:hover:bg-gray-700 transition-all duration-300 group"
            @click="toggleDropdown"
        >
            <User class="w-5 h-5 flex-shrink-0" />

            <span class="flex items-center sm:pl-2 min-w-0">
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
                class="absolute top-[45px] right-0 bg-indigo-soft-200/90 dark:bg-gray-800/90 rounded-lg border !border-indigo-soft-400 dark:!border-gray-700 overflow-hidden min-w-[160px]"
            >
                <div>
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
                        <span class="relative z-10 flex whitespace-nowrap gap-2 items-center">
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
import { useCartStore } from '@/stores/cart';

const cartStore = useCartStore();
const authStore = useAuthStore();
const router = useRouter();
const isOpen = ref(false);
const dropdownRef = ref<HTMLElement | null>(null);
const isAuthenticated = computed(() => !!authStore.user);

const handleAuthAction = () => {
    if (isAuthenticated.value) {
        authStore.logout();
        isOpen.value = false;
        cartStore.clearCart();
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
    if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
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
