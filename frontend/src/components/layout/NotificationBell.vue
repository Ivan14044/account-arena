<template>
    <div v-if="isAuthenticated" class="relative flex items-center">
        <button
            class="relative px-2 px-lg-3 h-[32px] flex items-center justify-center rounded-lg transition-all duration-300 hover:bg-indigo-200 dark:hover:bg-gray-700"
            :class="{ 'bounce-once': shouldAnimate }"
            aria-label="Notifications"
            @click.stop="toggleDropdown"
        >
            <Bell class="bell" />
            <span
                v-if="unreadCount > 0"
                class="counter flex items-center justify-center leading-none -top-1 -right-1 text-white"
                :aria-label="`${unreadCount} unread notifications`"
            >
                {{ unreadCount > 9 ? '9+' : unreadCount }}
            </span>
        </button>

        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-2"
        >
            <div
                v-if="isDropdownOpen"
                ref="dropdownRef"
                class="absolute right-0 top-[45px] w-80 liquid-glass-wrapper rounded-lg overflow-hidden z-50 notification-dropdown"
                role="dialog"
                aria-label="Notifications dropdown"
            >
                <div class="liquid-glass-effect"></div>
                <div class="liquid-glass-tint"></div>
                <div class="liquid-glass-shine"></div>
                
                <div class="liquid-glass-text flex flex-col w-full relative z-10">
                    <!-- Header -->
                    <div class="px-4 py-3 border-b border-white/10 dark:border-gray-700 font-semibold flex justify-between items-center">
                        <span>{{ $t('notifications.dropdown_title') }}</span>
                        <button
                            class="text-gray-900 dark:text-white text-2xl leading-none hover:opacity-70 transition-opacity"
                            :aria-label="$t('notifications.close')"
                            @click="closeDropdown"
                        >
                            ×
                        </button>
                    </div>

                    <!-- Notifications list -->
                    <div
                        v-if="displayedItems.length > 0"
                        ref="notificationsListRef"
                        class="max-h-96 overflow-y-auto"
                    >
                        <div
                            v-for="item in displayedItems"
                            :key="item.id"
                            class="p-3 border-b border-white/5 dark:border-gray-700/50 transition hover:bg-white/10 dark:hover:bg-gray-700/50"
                        >
                            <div class="text-sm font-medium flex justify-between items-center gap-2">
                                <span class="flex-1">{{ getTranslation(item, 'title') }}</span>
                                <span
                                    v-if="!item.read_at"
                                    class="inline-block w-2 h-2 rounded-full bg-blue-500 shrink-0"
                                    :title="$t('notifications.new')"
                                    aria-label="New notification"
                                />
                            </div>
                            <div
                                class="text-xs text-gray-600 dark:text-gray-300 mt-1"
                                v-html="getTranslation(item, 'message')"
                            />
                            <div class="flex justify-between items-center mt-2">
                                <time class="text-xs text-gray-500" :datetime="item.created_at">
                                    {{ formatDate(item.created_at) }}
                                </time>
                                <button
                                    v-if="!item.read_at"
                                    class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 rounded px-2 py-1 font-medium"
                                    :aria-label="$t('notifications.mark_as_read')"
                                    @click.stop="handleMarkAsRead(item)"
                                >
                                    {{ $t('notifications.mark_as_read') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Empty state -->
                    <div v-else class="p-4 text-sm text-gray-500 dark:text-gray-400 text-center">
                        {{ $t('notifications.empty') }}
                    </div>

                    <!-- Mark all as read button -->
                    <div
                        v-if="unreadCount > 2 && displayedItems.length > 0"
                        class="px-4 py-2 border-t border-white/10 dark:border-gray-700 bg-white/5 dark:bg-gray-700/30"
                    >
                        <button
                            class="w-full text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 rounded px-2 py-1 font-medium disabled:opacity-50"
                            :disabled="isMarkingAll"
                            @click="handleMarkAllAsRead"
                        >
                            {{ $t('notifications.mark_all_as_read') }}
                        </button>
                    </div>

                    <!-- Load more button -->
                    <div v-if="hasMoreItems" class="border-t border-white/10 dark:border-gray-700">
                        <button
                            class="w-full p-2 text-sm text-center hover:bg-white/10 dark:hover:bg-gray-700/30 transition focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50 flex items-center justify-center gap-2"
                            :disabled="isLoading"
                            @click="handleLoadMore"
                        >
                            <svg
                                v-if="isLoading"
                                class="w-4 h-4 animate-spin text-indigo-600 dark:text-indigo-400"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                ></circle>
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                ></path>
                            </svg>
                            <span
                                >{{ $t('notifications.dropdown_button') }} ({{ remainingCount }})</span
                            >
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { useNotificationStore } from '@/stores/notifications';
import { useAuthStore } from '@/stores/auth';
import { useI18n } from 'vue-i18n';
import { Bell } from 'lucide-vue-next';

const { locale } = useI18n();
const notificationStore = useNotificationStore();
const authStore = useAuthStore();

// Constants
const INITIAL_LIMIT = 3;
const POLLING_INTERVAL = 10000;
const ANIMATION_DURATION = 2000;

// State
const isDropdownOpen = ref(false);
const dropdownRef = ref(null);
const notificationsListRef = ref(null);
const isLoading = ref(false);
const isMarkingAll = ref(false);
const shouldAnimate = ref(false);
const recentlyReadIds = ref(new Set());
const loadedItems = ref([]);
const currentPage = ref(2);
const previousUnreadCount = ref(0);
const isFirstLoad = ref(true);
const pollingIntervalId = ref(null);
const animationTimeoutId = ref(null);
let notificationSound = null;

// Computed
const isAuthenticated = computed(() => !!authStore.user);
const unreadCount = computed(() => notificationStore.unread);
const storeItems = computed(() => notificationStore.items);
const displayedItems = computed(() =>
    loadedItems.value.length > 0 ? loadedItems.value : storeItems.value
);
const hasMoreItems = computed(() => notificationStore.total > displayedItems.value.length);
const remainingCount = computed(() => notificationStore.total - displayedItems.value.length);

// Initialize loaded items from store and sync when dropdown is open
watch(
    storeItems,
    newItems => {
        if (loadedItems.value.length === 0 && newItems.length > 0) {
            // Инициализация при первой загрузке
            loadedItems.value = [...newItems];
        } else if (isDropdownOpen.value && newItems.length > 0) {
            // Если дропдаун открыт, синхронизируем loadedItems с новыми сообщениями
            const existingIds = new Set(loadedItems.value.map(i => i.id));

            // Добавляем новые сообщения в начало (если их нет в loadedItems)
            const newItemsToAdd = newItems.filter(item => !existingIds.has(item.id));
            if (newItemsToAdd.length > 0) {
                loadedItems.value = [...newItemsToAdd, ...loadedItems.value];
            }

            // Обновляем существующие сообщения (на случай изменений)
            newItems.forEach(newItem => {
                const index = loadedItems.value.findIndex(i => i.id === newItem.id);
                if (index >= 0) {
                    loadedItems.value[index] = { ...newItem };
                }
            });
        }
    },
    { immediate: true }
);

// Methods
const getNotificationSound = () => {
    if (!notificationSound) {
        notificationSound = new Audio('/sounds/notification.mp3');
        notificationSound.volume = 0.5;
    }
    return notificationSound;
};

const closeDropdown = () => {
    isDropdownOpen.value = false;
    recentlyReadIds.value.clear();
};

const toggleDropdown = async () => {
    const wasOpen = isDropdownOpen.value;
    isDropdownOpen.value = !isDropdownOpen.value;

    if (isDropdownOpen.value && !wasOpen) {
        // Обновляем loadedItems актуальными данными из store
        loadedItems.value = [...storeItems.value];
        currentPage.value = 2;
    } else if (!isDropdownOpen.value) {
        recentlyReadIds.value.clear();
    }
};

const markNotificationsAsRead = async ids => {
    try {
        await notificationStore.markNotificationsAsRead(ids);
        ids.forEach(id => recentlyReadIds.value.add(id));
    } catch (error) {
        console.error('Failed to mark notifications as read:', error);
    }
};

const handleMarkAsRead = async item => {
    if (item.read_at) return;

    const now = new Date().toISOString();

    // Мгновенное локальное обновление (оптимистичное обновление)
    item.read_at = now;
    recentlyReadIds.value.add(item.id);

    // Обновляем в loadedItems для реактивности
    const index = loadedItems.value.findIndex(i => i.id === item.id);
    if (index >= 0) {
        loadedItems.value[index] = { ...item, read_at: now };
    }

    // Обновляем в store для реактивности
    const storeIndex = notificationStore.items.findIndex(i => i.id === item.id);
    if (storeIndex >= 0) {
        notificationStore.items[storeIndex] = { ...item, read_at: now };
    }

    // Обновляем счетчик непрочитанных
    notificationStore.unread = Math.max(0, notificationStore.unread - 1);

    // Выполняем запрос в фоне (не блокируем UI)
    markNotificationsAsRead([item.id]).catch(error => {
        console.error('Failed to mark notification as read:', error);
        // В случае ошибки можно откатить изменения, но обычно это не нужно
    });
};

const handleMarkAllAsRead = async () => {
    if (isMarkingAll.value || unreadCount.value === 0) return;

    isMarkingAll.value = true;

    // Мгновенное локальное обновление (оптимистичное обновление)
    const now = new Date().toISOString();
    const unreadItems = displayedItems.value.filter(item => !item.read_at);

    unreadItems.forEach(item => {
        item.read_at = now;
        recentlyReadIds.value.add(item.id);

        // Обновляем в loadedItems
        if (loadedItems.value.length > 0) {
            const loadedIndex = loadedItems.value.findIndex(i => i.id === item.id);
            if (loadedIndex >= 0) {
                loadedItems.value[loadedIndex] = { ...item, read_at: now };
            }
        }

        // Обновляем в store
        const storeIndex = notificationStore.items.findIndex(i => i.id === item.id);
        if (storeIndex >= 0) {
            notificationStore.items[storeIndex] = { ...item, read_at: now };
        }
    });

    // Обновляем счетчик непрочитанных
    notificationStore.unread = Math.max(0, notificationStore.unread - unreadItems.length);

    // Выполняем запрос в фоне (не блокируем UI)
    try {
        await notificationStore.markAllAsRead();
    } catch (error) {
        console.error('Failed to mark all as read:', error);
        // В случае ошибки можно откатить изменения, но обычно это не нужно
    } finally {
        isMarkingAll.value = false;
    }
};

const handleLoadMore = async () => {
    if (isLoading.value) return;

    isLoading.value = true;
    try {
        const offset = (currentPage.value - 1) * INITIAL_LIMIT;
        const newItems = await notificationStore.fetchChunk(INITIAL_LIMIT, offset, false);

        if (newItems.length > 0) {
            loadedItems.value = [...loadedItems.value, ...newItems];
            currentPage.value++;
        }

        // Scroll to bottom after loading
        await nextTick();
        if (notificationsListRef.value) {
            notificationsListRef.value.scrollTo({
                top: notificationsListRef.value.scrollHeight,
                behavior: 'smooth'
            });
        }
    } catch (error) {
        console.error('Failed to load more notifications:', error);
    } finally {
        isLoading.value = false;
    }
};

const getTranslation = (item, key) => {
    const translations = item.template?.translations || {};
    let text = translations[locale.value]?.[key] || translations['en']?.[key] || '';

    const variables = item.template?.variables || {};
    Object.entries(variables).forEach(([k, v]) => {
        text = text.replace(new RegExp(`:${k}\\b`, 'g'), String(v));
    });

    return text;
};

const formatDate = dateStr => {
    try {
        return new Date(dateStr).toLocaleString(locale.value);
    } catch {
        return dateStr;
    }
};

const startPolling = () => {
    if (pollingIntervalId.value) clearInterval(pollingIntervalId.value);

    pollingIntervalId.value = setInterval(async () => {
        try {
            notificationStore.isLoaded = false;
            await notificationStore.fetchData(INITIAL_LIMIT);
        } catch (error) {
            console.error('Failed to poll notifications:', error);
        }
    }, POLLING_INTERVAL);
};

const stopPolling = () => {
    if (pollingIntervalId.value) {
        clearInterval(pollingIntervalId.value);
        pollingIntervalId.value = null;
    }
};

const playNotificationSound = () => {
    try {
        getNotificationSound()
            .play()
            .catch(() => {});
    } catch (error) {
        console.error('Failed to play notification sound:', error);
    }
};

const triggerAnimation = () => {
    shouldAnimate.value = true;
    if (animationTimeoutId.value) clearTimeout(animationTimeoutId.value);
    animationTimeoutId.value = setTimeout(() => {
        shouldAnimate.value = false;
        animationTimeoutId.value = null;
    }, ANIMATION_DURATION);
};

// Watchers
watch(unreadCount, newCount => {
    if (isFirstLoad.value) {
        previousUnreadCount.value = newCount;
        isFirstLoad.value = false;
        return;
    }

    if (newCount > previousUnreadCount.value) {
        triggerAnimation();
        playNotificationSound();
    }

    previousUnreadCount.value = newCount;
});

watch(isAuthenticated, async isAuth => {
    if (isAuth) {
        try {
            await notificationStore.fetchData(INITIAL_LIMIT);
            previousUnreadCount.value = unreadCount.value;
            isFirstLoad.value = false;
            startPolling();
        } catch (error) {
            console.error('Failed to fetch notifications:', error);
        }
    } else {
        stopPolling();
        loadedItems.value = [];
        currentPage.value = 2;
        recentlyReadIds.value.clear();
    }
});

// Click outside handler
const handleClickOutside = event => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        if (isDropdownOpen.value) {
            closeDropdown();
        }
    }
};

// Lifecycle
onMounted(async () => {
    document.addEventListener('click', handleClickOutside);

    if (isAuthenticated.value) {
        try {
            await notificationStore.fetchData(INITIAL_LIMIT);
            previousUnreadCount.value = unreadCount.value;
            isFirstLoad.value = false;
            startPolling();
        } catch (error) {
            console.error('Failed to fetch notifications on mount:', error);
        }
    }
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
    stopPolling();

    if (animationTimeoutId.value) {
        clearTimeout(animationTimeoutId.value);
    }

    if (notificationSound) {
        notificationSound.pause();
        notificationSound = null;
    }

    recentlyReadIds.value.clear();
});
</script>

<style scoped>
@keyframes bounceOnce {
    0%,
    100% {
        transform: translateY(0);
    }
    25% {
        transform: translateY(-4px);
    }
    50% {
        transform: translateY(0);
    }
    75% {
        transform: translateY(-2px);
    }
}

.bounce-once {
    animation: bounceOnce 1s ease;
}

.counter {
    background: #0047ff;
    padding: 7px 0;
    border-radius: 50%;
    display: block;
    text-align: center;
    height: 22px;
    width: 22px;
    margin-left: 20px;
    margin-top: -3px;
    font-weight: normal;
    font-size: 10px;
    position: absolute;
    left: -11px;
}

.bell {
    width: 20px;
    height: auto;
}

@media (max-width: 992px) {
    .notification-dropdown {
        left: 10px !important;
        position: fixed !important;
        width: calc(100% - 20px) !important;
        top: 59px !important;
    }
}
</style>
