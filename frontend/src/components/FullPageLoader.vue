<template>
    <Transition name="fade">
        <div
            v-if="loadingStore.isLoading"
            :class="{ 'bg-black/40': hasOverlay, 'bg-white dark:!bg-gray-900': !hasOverlay }"
            class="fixed inset-0 z-[9999] flex items-center justify-center"
        >
            <div class="flex flex-col items-center">
                <img
                    :src="logo"
                    alt="Loading..."
                    class="w-32 h-32 object-contain loader-pulse"
                />
                <div
                    v-if="isStartSessionPage"
                    class="mt-[35px] text-gray-700 dark:text-gray-200 text-sm sm:text-base text-center"
                >
                    <div class="relative">
                        <Transition name="text-fade" mode="out-in">
              <span :key="activeMessageKey" style="padding-left: 31px">
                {{ $t(activeMessageKey) }}<span class="ellipsis" aria-hidden="true">{{ dots }}</span>
              </span>
                        </Transition>
                    </div>
                </div>
            </div>
        </div>
    </Transition>

    <PluginWarningModal v-if="showPluginWarning" @close="showPluginWarning = false" />
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useLoadingStore } from '../stores/loading';
import PluginWarningModal from './PluginWarningModal.vue';
import { usePluginDetection } from '@/composables/usePluginDetection';

const logo = '/img/logo_trans.webp'; // Новое изображение прелоадера с социальными сетями
const isStartSessionPage = /^\/session-start(\/\d+)?$/.test(window.location.pathname);

const props = defineProps<{
    overlay?: boolean;
    isLoading?: boolean; // оставляем для обратной совместимости, но не используем (как и раньше)
}>();

defineEmits<{
    'callHideLoader': [];
}>();

const loadingStore = useLoadingStore();
const hasOverlay = computed(() => props.overlay !== false);

const activeMessageKey = ref<'loader.starting' | 'loader.checking_plugin' | 'loader.preparing' | 'loader.syncing' | 'loader.browser'>('loader.starting');
const showPluginWarning = ref(false);
const continueLoading = ref(false);
const dots = ref('.');

let dotsIntervalId: number | null = null;
const statusTimeoutIds: number[] = [];

const { checkPluginInstalled } = usePluginDetection();

/** --- helpers --- */
const startDots = () => {
    if (dotsIntervalId !== null) return;
    let step = 0;
    dotsIntervalId = window.setInterval(() => {
        step = (step + 1) % 3;
        dots.value = '.'.repeat(step + 1);
    }, 400);
};

const stopDots = () => {
    if (dotsIntervalId !== null) {
        clearInterval(dotsIntervalId);
        dotsIntervalId = null;
    }
};

const clearStatusTimeouts = () => {
    statusTimeoutIds.splice(0).forEach(id => clearTimeout(id));
};

const startLoadingSequence = () => {
    statusTimeoutIds.push(
        window.setTimeout(() => {
            activeMessageKey.value = 'loader.preparing';
        }, 1000)
    );
    statusTimeoutIds.push(
        window.setTimeout(() => {
            activeMessageKey.value = 'loader.syncing';
        }, 4000)
    );
    statusTimeoutIds.push(
        window.setTimeout(() => {
            activeMessageKey.value = 'loader.browser';
        }, 7000)
    );
};

/** --- handlers --- */
const handleContinueWithoutPlugin = () => {
    continueLoading.value = true;
    showPluginWarning.value = false;

    window.dispatchEvent(
        new CustomEvent('app:plugin-status', {
            detail: { pluginInstalled: false, pluginSkipped: true }
        })
    );

    startLoadingSequence();
};

const handleCheckPluginStatus = async () => {
    const pluginResult = await checkPluginInstalled();
    window.dispatchEvent(
        new CustomEvent('app:plugin-status', {
            detail: { pluginInstalled: pluginResult.isInstalled, pluginSkipped: false }
        })
    );
};

const handlePluginStatus = (event: Event) => {
    const { pluginInstalled, pluginSkipped } = (event as CustomEvent).detail || {};
    console.log('FullPageLoader: received app:plugin-status event:', { pluginInstalled, pluginSkipped });

    if (pluginInstalled) {
        console.log('FullPageLoader: Plugin installed successfully, starting loading sequence...');
        showPluginWarning.value = false;
        continueLoading.value = true;
        startLoadingSequence();
    } else if (pluginSkipped) {
        console.log('FullPageLoader: Plugin installation skipped, starting loading sequence...');
        showPluginWarning.value = false;
        continueLoading.value = true;
        startLoadingSequence();
    }
};

/** --- lifecycle --- */
if (isStartSessionPage) {
    activeMessageKey.value = 'loader.checking_plugin';

    onMounted(async () => {
        startDots();

        const pluginResult = await checkPluginInstalled();

        if (pluginResult.isInstalled) {
            window.dispatchEvent(
                new CustomEvent('app:plugin-status', {
                    detail: { pluginInstalled: true, pluginSkipped: false }
                })
            );
            startLoadingSequence();
        } else {
            console.warn('Account Arena plugin not detected. Please install the browser extension first.');

            if (!continueLoading.value) {
                activeMessageKey.value = 'loader.checking_plugin';

                const showModalTimeout = window.setTimeout(() => {
                    showPluginWarning.value = true;
                }, 1500);

                onBeforeUnmount(() => {
                    clearTimeout(showModalTimeout);
                });

                return;
            } else {
                window.dispatchEvent(
                    new CustomEvent('app:plugin-status', {
                        detail: { pluginInstalled: false, pluginSkipped: true }
                    })
                );
                startLoadingSequence();
            }
        }
    });

    onMounted(() => {
        window.addEventListener('app:continue-without-plugin', handleContinueWithoutPlugin);
        window.addEventListener('app:check-plugin-status', handleCheckPluginStatus);
        window.addEventListener('app:plugin-status', handlePluginStatus);
    });

    onBeforeUnmount(() => {
        clearStatusTimeouts();
        stopDots();
        window.removeEventListener('app:continue-without-plugin', handleContinueWithoutPlugin);
        window.removeEventListener('app:check-plugin-status', handleCheckPluginStatus);
        window.removeEventListener('app:plugin-status', handlePluginStatus);
    });
} else {
    // Если это не страница запуска сессии — убедимся, что анимация точек не крутится.
    onBeforeUnmount(() => {
        clearStatusTimeouts();
        stopDots();
    });
}
</script>

<style scoped>
.fade-enter-active { transition: none; }
.fade-leave-active { transition: opacity 0.3s ease; }

.fade-enter-from,
.fade-leave-to { opacity: 0; }

.fade-enter-to,
.fade-leave-from { opacity: 1; }

.ellipsis { display: inline-block; width: 3ch; text-align: left; }

/* Плавная анимация пульсации для прелоадера (без вращения) */
.loader-pulse {
    animation: loader-pulse 2s ease-in-out infinite;
}

@keyframes loader-pulse {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.6;
        transform: scale(1.15);
    }
}

/* smoother crossfade/slide for status text only */
.text-fade-enter-active,
.text-fade-leave-active {
    transition: opacity 0.5s ease, transform 0.5s ease;
    will-change: opacity, transform;
}

.text-fade-enter-from { opacity: 0; transform: translateY(4px); }
.text-fade-enter-to   { opacity: 1; transform: translateY(0); }

.text-fade-leave-from { opacity: 1; transform: translateY(0); }
.text-fade-leave-to   { opacity: 0; transform: translateY(-4px); }
</style>
