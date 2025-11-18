<template>
    <div v-if="isAuthenticated" class="relative">
        <div
            class="px-2 px-lg-3 d-flex h-[32px] rounded-lg transition-all duration-300 hover:bg-indigo-200 dark:hover:bg-gray-700 cursor-pointer"
            @click.stop="toggleDropdown"
        >
            <!-- Bell icon -->
            <button class="relative" :class="{ 'bounce-once': shouldAnimate }" aria-label="Notifications">
                <Bell class="bell" />

                <span
                    v-if="unreadCount > 0"
                    class="counter flex items-center justify-center leading-none -top-1 -right-1 text-white"
                    :aria-label="`${unreadCount} unread notifications`"
                >
                    {{ unreadCount > 9 ? '9+' : unreadCount }}
                </span>
            </button>
        </div>

        <!-- Dropdown -->
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
                    class="absolute right-0 top-[45px] w-80 !bg-indigo-soft-200 !border-indigo-soft-400 dark:!border-gray-700 text-gray-900 dark:text-white dark:!bg-gray-800 border rounded shadow-lg z-50 notification-dropdown"
                    role="dialog"
                    aria-label="Notifications dropdown"
                >
                    <BoxLoader v-if="isLoading" />
                    <div class="px-2 px-lg-3 py-2 border-b font-semibold text-gray-900 dark:text-white flex justify-between items-center">
                        <span>{{ $t('notifications.dropdown_title') }}</span>
                        <button
                            class="text-gray-900 dark:text-white text-2xl leading-none close-dropdown hover:opacity-70 transition-opacity"
                            :aria-label="$t('notifications.close')"
                            @click="closeDropdown"
                        >
                            ×
                        </button>
                    </div>

                    <div v-if="displayedItems.length > 0" class="max-h-96 overflow-y-auto">
                        <div
                            v-for="item in displayedItems"
                            :key="item.id"
                            :ref="el => setItemRef(el, item.id)"
                            class="p-3 border-b transition relative hover:bg-indigo-50 dark:hover:bg-gray-700/50"
                        >
                            <div class="text-sm font-medium flex justify-between items-start gap-2">
                                <span class="flex-1">{{ getTranslation(item, 'title') }}</span>
                                <span
                                    v-if="!item.read_at"
                                    class="inline-block w-2 h-2 rounded-full bg-blue-500 mt-1 shrink-0"
                                    :title="$t('notifications.new')"
                                    aria-label="New notification"
                                ></span>
                            </div>
                            <div
                                class="text-xs text-gray-600 dark:text-gray-300 mt-1"
                                v-html="getTranslation(item, 'message')"
                            ></div>
                            <div class="flex justify-between items-center mt-2">
                                <time class="text-xs text-gray-500" :datetime="item.created_at">
                                    {{ formatDate(item.created_at) }}
                                </time>
                                <button
                                    v-if="!item.read_at"
                                    @click.stop="handleMarkAsRead(item)"
                                    class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 rounded px-1"
                                    :aria-label="$t('notifications.mark_as_read')"
                                >
                                    {{ $t('notifications.mark_as_read') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-else class="p-4 text-sm text-gray-900 dark:text-gray-500 text-center">
                        {{ $t('notifications.empty') }}
                    </div>

                    <div
                        v-if="hasMoreItems"
                        class="text-gray-600 dark:text-white text-sm"
                    >
                        <button
                            class="w-full p-2 text-center cursor-pointer leading-none hover:bg-indigo-200 dark:hover:bg-gray-700 transition focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded"
                            :disabled="isLoading"
                            @click="handleLoadMore"
                        >
                            {{ $t('notifications.dropdown_button') }} ({{ remainingCount }})
                        </button>
                    </div>
                </div>
            </Transition>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { useNotificationStore } from '@/stores/notifications';
import { useAuthStore } from '@/stores/auth';
import BoxLoader from '@/components/BoxLoader.vue';
import { useI18n } from 'vue-i18n';
import { Bell } from 'lucide-vue-next';

const { locale, t } = useI18n();
const notificationStore = useNotificationStore();
const authStore = useAuthStore();

// Refs
const isDropdownOpen = ref(false);
const dropdownRef = ref(null);
const isLoading = ref(false);
const shouldAnimate = ref(false);
const recentlyReadIds = ref(new Set());
const itemRefs = ref(new Map());
const animationTimeoutId = ref(null);
const pollingIntervalId = ref(null);
const previousUnreadCount = ref(0);
const isFirstLoad = ref(true);
const currentPage = ref(2);
const loadedItems = ref([]);
const firstNewItemId = ref(null);

// Constants
const INITIAL_LIMIT = 3;
const POLLING_INTERVAL = 10000;
const ANIMATION_DURATION = 2000;

// Computed
const isAuthenticated = computed(() => !!authStore.user);
const unreadCount = computed(() => notificationStore.unread);
const storeItems = computed(() => notificationStore.items);
const displayedItems = computed(() => {
    return loadedItems.value.length > 0 ? loadedItems.value : storeItems.value;
});
const hasMoreItems = computed(() => {
    return notificationStore.total > INITIAL_LIMIT && 
           notificationStore.total > displayedItems.value.length;
});
const remainingCount = computed(() => {
    return notificationStore.total - displayedItems.value.length;
});

// Initialize loaded items from store
watch(storeItems, (newItems) => {
    if (loadedItems.value.length === 0 && newItems.length > 0) {
        loadedItems.value = [...newItems];
    }
}, { immediate: true });

// Sound initialization (lazy)
let notificationSound = null;
const getNotificationSound = () => {
    if (!notificationSound) {
        notificationSound = new Audio('/sounds/notification.mp3');
        notificationSound.volume = 0.5;
    }
    return notificationSound;
};

// Methods
const setItemRef = (el, id) => {
    if (el) {
        itemRefs.value.set(id, el);
    } else {
        itemRefs.value.delete(id);
    }
};

const closeDropdown = () => {
    isDropdownOpen.value = false;
    recentlyReadIds.value.clear();
};

const toggleDropdown = async () => {
    const wasOpen = isDropdownOpen.value;
    isDropdownOpen.value = !isDropdownOpen.value;

    if (isDropdownOpen.value && !wasOpen) {
        // Reset loaded items when opening
        loadedItems.value = [...storeItems.value];
        currentPage.value = 2;
        
        // Mark visible unread items as read
        const unreadItems = storeItems.value.filter(n => !n.read_at);
        const unreadIds = unreadItems.map(n => n.id);

        if (unreadIds.length > 0) {
            unreadIds.forEach(id => recentlyReadIds.value.add(id));
            try {
                await notificationStore.markNotificationsAsRead(unreadIds);
            } catch (error) {
                console.error('Failed to mark notifications as read:', error);
            }
        }
    } else if (!isDropdownOpen.value) {
        recentlyReadIds.value.clear();
    }
};

const handleMarkAsRead = async (item) => {
    if (item.read_at) return;

    try {
        recentlyReadIds.value.delete(item.id);
        item.read_at = new Date().toISOString();
        await notificationStore.markNotificationsAsRead([item.id]);
    } catch (error) {
        console.error('Failed to mark notification as read:', error);
    }
};

const handleLoadMore = async () => {
    if (isLoading.value) return;

    isLoading.value = true;

    try {
        const offset = (currentPage.value - 1) * INITIAL_LIMIT;
        const newItems = await notificationStore.fetchChunk(INITIAL_LIMIT, offset, false);

        if (newItems.length === 0) {
            return;
        }

        firstNewItemId.value = newItems[0]?.id ?? null;
        loadedItems.value = [...loadedItems.value, ...newItems];
        currentPage.value++;

        await nextTick();

        // Scroll to first new item
        if (firstNewItemId.value) {
            const element = itemRefs.value.get(firstNewItemId.value);
            if (element) {
                element.scrollIntoView({ behavior: 'smooth', block: 'start' });
                firstNewItemId.value = null;
            }
        }

        // Mark new unread items as read
        const unreadIds = newItems.filter(i => !i.read_at).map(i => i.id);
        if (unreadIds.length > 0) {
            unreadIds.forEach(id => recentlyReadIds.value.add(id));
            try {
                await notificationStore.markNotificationsAsRead(unreadIds);
            } catch (error) {
                console.error('Failed to mark new notifications as read:', error);
            }
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

    const variables = item.template?.variables;
    if (!variables || typeof variables !== 'object') {
        return text;
    }

    // Replace variables in text
    for (const [k, v] of Object.entries(variables)) {
        text = text.replace(new RegExp(`:${k}\\b`, 'g'), String(v));
    }

    return text;
};

const formatDate = (dateStr) => {
    try {
        return new Date(dateStr).toLocaleString(locale.value);
    } catch {
        return dateStr;
    }
};

const startPolling = () => {
    if (pollingIntervalId.value) {
        clearInterval(pollingIntervalId.value);
    }

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
        const sound = getNotificationSound();
        sound.play().catch(() => {
            // Ignore play errors (e.g., user hasn't interacted with page)
        });
    } catch (error) {
        console.error('Failed to play notification sound:', error);
    }
};

const triggerAnimation = () => {
    shouldAnimate.value = true;
    
    if (animationTimeoutId.value) {
        clearTimeout(animationTimeoutId.value);
    }

    animationTimeoutId.value = setTimeout(() => {
        shouldAnimate.value = false;
        animationTimeoutId.value = null;
    }, ANIMATION_DURATION);
};

// Watchers
watch(unreadCount, (newCount) => {
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

watch(storeItems, (newItems, oldItems) => {
    if (!isDropdownOpen.value) return;

    const oldIds = new Set((oldItems || []).map(i => i.id));
    const newlyAdded = newItems.filter(
        item => !oldIds.has(item.id) && !item.read_at
    );

    if (newlyAdded.length > 0) {
        const ids = newlyAdded.map(n => n.id);
        ids.forEach(id => recentlyReadIds.value.add(id));
        
        notificationStore.markNotificationsAsRead(ids).catch(error => {
            console.error('Failed to mark newly added notifications as read:', error);
        });
    }
});

watch(isAuthenticated, async (isAuth) => {
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
const handleClickOutside = (event) => {
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
    itemRefs.value.clear();
});
</script>

<style scoped>
@keyframes bounceOnce {
    0% {
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
    100% {
        transform: translateY(0);
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

.close-dropdown {
    margin-top: -1px;
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
