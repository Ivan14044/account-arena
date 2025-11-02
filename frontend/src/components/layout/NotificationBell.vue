<template>
    <div v-if="isAuthenticated" class="relative">
        <div
            class="px-2 px-lg-3 d-flex h-[32px] rounded-lg transition-all duration-300 hover:bg-indigo-200 dark:hover:bg-gray-700 cursor-pointer"
            @click.stop="toggleDropdown"
        >
            <!-- Bell icon -->
            <button class="relative" :class="{ 'bounce-once': animate }">
                <Bell class="bell" />

                <span
                    v-if="unread > 0"
                    class="counter flex items-center justify-center leading-none -top-1 -right-1 text-white"
                >
                    {{ unread > 9 ? '9+' : unread }}
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
                v-if="dropdownOpen"
                ref="dropdownRef"
                class="absolute right-0 top-[45px] w-80 !bg-indigo-soft-200 !border-indigo-soft-400 dark:!border-gray-700 text-gray-900 dark:text-white dark:!bg-gray-800 border rounded shadow-lg z-50 notification-dropdown"
            >
                <BoxLoader v-if="loading" />
                <div class="px-2 px-lg-3 py-2 border-b font-semibold text-gray-900 dark:text-white">
                    {{ $t('notifications.dropdown_title') }}
                    <button
                        class="text-gray-900 dark:text-white text-2xl leading-none float-right close-dropdown"
                        @click="toggleDropdown"
                    >
                        ×
                    </button>
                </div>

                <div v-if="items.length > 0" class="max-h-96 overflow-y-auto">
                    <div
                        v-for="(item, index) in items"
                        :ref="el => setItemRef(el, item.id)"
                        :key="index"
                        class="p-3 border-b transition relative"
                    >
                        <div class="text-sm font-medium flex justify-between items-start">
                            <span>{{ getTranslation(item, 'title') }}</span>
                            <span
                                v-if="recentlyRead.has(item.id)"
                                class="inline-block w-2 h-2 rounded-full bg-blue-500 mt-1 ml-2 shrink-0"
                                title="New"
                            ></span>
                        </div>
                        <div
                            class="text-xs text-gray-600 dark:text-gray-300 mt-1"
                            v-html="getTranslation(item, 'message')"
                        ></div>
                        <div class="text-xs text-gray-500 mt-2">
                            {{ formatDate(item.created_at) }}
                        </div>
                    </div>
                </div>

                <div v-else class="p-4 text-sm text-gray-300 dark:text-gray-500 text-center">
                    {{ $t('notifications.empty') }}
                </div>

                <div
                    v-if="store.total > 3 && store.total > items.length"
                    class="text-gray-600 dark:text-blue-600 dark:text-white text-sm"
                    @click="loadMore"
                >
                    <div
                        class="p-2 text-center cursor-pointer leading-none hover:bg-indigo-200 dark:hover:bg-gray-700 transition"
                    >
                        {{ $t('notifications.dropdown_button') }} ({{ store.total - items.length }})
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, onUnmounted, watch, computed, nextTick } from 'vue';
import { useNotificationStore } from '@/stores/notifications';
import { useAuthStore } from '@/stores/auth';
import BoxLoader from '@/components/BoxLoader.vue';
import { useI18n } from 'vue-i18n';
import { useServiceStore } from '@/stores/services';
import { Bell } from 'lucide-vue-next';

const { locale } = useI18n();
const dropdownOpen = ref(false);
const intervalId = ref(null);
const animate = ref(false);
const previousUnread = ref(0);
const sound = new Audio('/sounds/notification.mp3');
sound.volume = 0.5;
const isFirstLoad = ref(true);
const dropdownRef = ref(null);

const notificationStore = useNotificationStore();
const serviceStore = useServiceStore();
const store = useNotificationStore();
const authStore = useAuthStore();
const isAuthenticated = computed(() => !!authStore.user);

const { fetchData } = store;
const recentlyRead = ref(new Set());

const unread = computed(() => store.unread);
const items = computed(() => store.items);
const loading = ref(false);

const limit = 3;
const page = ref(2);
const firstNewItemId = ref(null);

const loadMore = async () => {
    if (loading.value) return;
    loading.value = true;

    try {
        const offset = (page.value - 1) * limit;
        const response = await notificationStore.fetchChunk(limit, offset, false);

        firstNewItemId.value = response[0]?.id ?? null;

        items.value.push(...response);
        page.value++;

        await nextTick();

        const el = itemRefs.value[firstNewItemId.value];
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            firstNewItemId.value = null;
        }

        const unreadIds = response.filter(i => !i.read_at).map(i => i.id);
        if (unreadIds.length > 0) {
            unreadIds.forEach(id => recentlyRead.value.add(id));
            await notificationStore.markNotificationsAsRead(unreadIds);
        }
    } catch (e) {
        console.error('Failed to load notifications:', e);
    } finally {
        loading.value = false;
    }
};

const itemRefs = ref({});

function setItemRef(el, id) {
    if (el) itemRefs.value[id] = el;
    else delete itemRefs.value[id];
}

function handleClickOutside(event) {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        dropdownOpen.value = false;
        recentlyRead.value.clear();
    }
}

function toggleDropdown() {
    dropdownOpen.value = !dropdownOpen.value;

    if (dropdownOpen.value) {
        const unreadItems = store.items.filter(n => !n.read_at);
        const unreadIds = unreadItems.map(n => n.id);

        if (unreadIds.length > 0) {
            unreadIds.forEach(id => recentlyRead.value.add(id));
            store.markNotificationsAsRead(unreadIds);
        }
    } else {
        recentlyRead.value.clear();
    }
}

function getTranslation(item, key) {
    const translations = item.template?.translations || {};
    let text = translations[locale.value]?.[key] || translations['en']?.[key] || '';

    const variables = item.template?.variables;
    if (!variables || typeof variables !== 'object') return text;

    for (const [k, v] of Object.entries(variables)) {
        if (k === 'service') {
            const service = serviceStore.services.find(s => s.code === v);
            const name =
                service?.translations?.[locale.value]?.name ||
                service?.translations?.['en']?.name ||
                v;

            text = text.replace(new RegExp(`:${k}\\b`, 'g'), name);
        } else {
            text = text.replace(new RegExp(`:${k}\\b`, 'g'), v);
        }
    }

    return text;
}

function formatDate(dateStr) {
    return new Date(dateStr).toLocaleString();
}

onUnmounted(() => {
    clearInterval(intervalId.value);
    document.removeEventListener('click', handleClickOutside);
});

watch(unread, newVal => {
    // если первая загрузка — не реагируем
    if (isFirstLoad.value) {
        previousUnread.value = newVal;
        return;
    }

    // если кол-во увеличилось — проигрываем звук и анимацию
    if (newVal > previousUnread.value) {
        animate.value = true;
        sound.play().catch(() => {});
        setTimeout(() => {
            animate.value = false;
        }, 2000);
    }

    previousUnread.value = newVal;
});

watch(items, (newItems, oldItems) => {
    if (!dropdownOpen.value) return;

    const oldIds = new Set(oldItems?.map(i => i.id) ?? []);
    const newlyAdded = newItems.filter(item => !oldIds.has(item.id) && !item.read_at);

    if (newlyAdded.length > 0) {
        const ids = newlyAdded.map(n => n.id);
        ids.forEach(id => recentlyRead.value.add(id));
        store.markNotificationsAsRead(ids);
    }
});

watch(
    () => isAuthenticated.value,
    async newVal => {
        if (newVal) {
            document.addEventListener('click', handleClickOutside);

            await fetchData();
            previousUnread.value = unread.value;
            isFirstLoad.value = false;

            intervalId.value = setInterval(() => {
                store.isLoaded = false;
                fetchData();
            }, 10000);
        } else {
            clearInterval(intervalId.value);
            intervalId.value = null;
            document.removeEventListener('click', handleClickOutside);
        }
    },
    { immediate: true }
);
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
