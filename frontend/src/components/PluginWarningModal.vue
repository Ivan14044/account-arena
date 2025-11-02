<template>
    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="show"
                class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/30"
            >
                <div
                    class="glass-morphism rounded-3xl shadow-2xl max-w-lg w-full mx-4 p-8 relative overflow-hidden"
                    @click.stop
                >
                    <!-- bg -->
                    <div
                        class="absolute inset-0 bg-gradient-to-br from-white/10 via-transparent to-blue-500/5 dark:from-gray-800/10 dark:to-purple-500/5 rounded-3xl"
                    ></div>
                    <div
                        class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-bl from-amber-400/10 to-transparent rounded-full blur-3xl animate-float-gentle"
                    ></div>
                    <div
                        class="absolute bottom-0 left-0 w-24 h-24 bg-gradient-to-tr from-orange-400/10 to-transparent rounded-full blur-3xl animate-float-gentle"
                        style="animation-delay: 2s"
                    ></div>

                    <div class="relative z-10">
                        <!-- icon -->
                        <div class="flex items-center justify-center mb-8">
                            <div class="w-24 h-24 flex items-center justify-center">
                                <img :src="warningIcon" alt="Warning" class="w-20 h-20 object-contain drop-shadow-lg" />
                            </div>
                        </div>

                        <!-- text -->
                        <div class="text-center mb-8">
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                                {{ $t('plugin.not_installed.title') }}
                            </h3>
                            <p class="text-gray-600 dark:text-gray-300 leading-relaxed">
                <span v-if="!isWaiting">
                  {{ $t('plugin.not_installed.message') }}
                </span>
                                <span v-else>
                  {{
                                        $t('plugin.not_installed.wait_instructions')
                                        || 'Install the extension on the opened tab, then return here and press “Done”.'
                                    }}
                </span>
                            </p>
                        </div>

                        <!-- buttons -->
                        <div class="flex gap-3">
                            <button
                                v-if="!isWaiting"
                                class="flex-1 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-medium py-3 px-6 rounded-xl transition-all duration-300 hover:shadow-lg hover:shadow-blue-500/25 text-sm"
                                @click="startInstallFlow"
                            >
                                {{ $t('plugin.not_installed.install_button') }}
                            </button>

                            <button
                                class="flex-1 bg-gray-100/80 dark:bg-gray-800/80 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 font-medium py-3 px-6 rounded-xl transition-all duration-300 border border-gray-200 dark:border-gray-600 text-sm"
                                @click="continueWithoutPlugin"
                            >
                                {{ $t('plugin.not_installed.later_button') }}
                            </button>
                        </div>

                        <!-- waiting -->
                        <div v-if="isWaiting" class="mt-4 text-center">
                            <div class="flex items-center justify-center mb-4">
                                <div class="animate-spin rounded-full h-6 w-6 border-2 border-blue-500 border-t-transparent"></div>
                                <span class="ml-3 text-sm text-gray-600 dark:text-gray-300">
                  {{ $t('plugin.not_installed.waiting') || 'Waiting for extension installation...' }}
                </span>
                            </div>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-3 leading-relaxed opacity-80">
                                {{ $t('plugin.not_installed.auto_check_hint') || 'We will detect the installation automatically.' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue';
import warningIcon from '@/assets/img/warning.png';
import { usePluginDetection } from '@/composables/usePluginDetection';

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'continue'): void;
}>();

const show = ref(true);
const isWaiting = ref(false);

let focusHandler: (() => void) | null = null;
let visHandler: (() => void) | null = null;
let checkIntervalId: number | null = null;

const { checkPluginInstalled, EXTENSION_ID } = usePluginDetection();
const extensionUrl = `https://chrome.google.com/webstore/detail/${encodeURIComponent(EXTENSION_ID)}`;

const detectInstalledOnce = async (): Promise<boolean> => {
    try {
        const result = await checkPluginInstalled();
        return !!result.isInstalled;
    } catch {
        return false;
    }
};

function closeModal() {
    show.value = false;
    emit('close');
}

function continueWithPlugin() {
    console.log('PluginWarningModal: continueWithPlugin called, show.value:', show.value);

    // Закрываем модалку если она показана
    if (show.value) {
        closeModal();
    }

    // Всегда отправляем события для продолжения сессии
    console.log('PluginWarningModal: sending app:plugin-status and app:continue-with-plugin events');
    window.dispatchEvent(
        new CustomEvent('app:plugin-status', {
            detail: { pluginInstalled: true, pluginSkipped: false },
        })
    );

    window.dispatchEvent(new CustomEvent('app:continue-with-plugin'));
    emit('continue');
}

function continueWithoutPlugin() {
    show.value = false;
    window.dispatchEvent(new CustomEvent('app:continue-without-plugin'));
}

function openStorePage() {
    window.open(extensionUrl, '_blank');
}

/** Auto-detect after user installs the extension */
function startAutoDetect() {
    stopAutoDetect();

    const check = async () => {
        const ok = await detectInstalledOnce();
        if (ok) {
            stopAutoDetect();
            closeModal();
            continueWithPlugin();
        }
    };

    focusHandler = check;
    visHandler = check;

    window.addEventListener('focus', focusHandler);
    document.addEventListener('visibilitychange', visHandler);

    if (checkIntervalId === null) {
        checkIntervalId = window.setInterval(check, 2000);
    }
}

function stopAutoDetect() {
    if (focusHandler) {
        window.removeEventListener('focus', focusHandler);
        focusHandler = null;
    }
    if (visHandler) {
        document.removeEventListener('visibilitychange', visHandler);
        visHandler = null;
    }
    if (checkIntervalId !== null) {
        clearInterval(checkIntervalId);
        checkIntervalId = null;
    }
}

function startInstallFlow() {
    if (!EXTENSION_ID) {
        console.error('VITE_EXTENSION_ID is not defined in env');
        return;
    }
    if (isWaiting.value) return;

    openStorePage();
    isWaiting.value = true;
    startAutoDetect();
}

onMounted(async () => {
    const installed = await detectInstalledOnce();
    if (installed) {
        // Плагин уже установлен - сразу отправляем событие и закрываем модалку
        show.value = false;
        window.dispatchEvent(
            new CustomEvent('app:plugin-status', {
                detail: { pluginInstalled: true, pluginSkipped: false },
            })
        );
        window.dispatchEvent(new CustomEvent('app:continue-with-plugin'));
        emit('continue');
    } else {
        show.value = true;
    }
});

onBeforeUnmount(() => stopAutoDetect());
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
    transform: scale(0.9) translateY(20px);
}
.modal-enter-to,
.modal-leave-from {
    opacity: 1;
    transform: scale(1) translateY(0);
}

/* glass morphism */
.glass-morphism {
    backdrop-filter: blur(20px) saturate(180%);
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow:
        0 8px 32px 0 rgba(0, 0, 0, 0.37),
        inset 0 1px 0 rgba(255, 255, 255, 0.1);
}

.dark .glass-morphism {
    background: rgba(17, 24, 39, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow:
        0 8px 32px 0 rgba(0, 0, 0, 0.37),
        inset 0 1px 0 rgba(255, 255, 255, 0.05);
}

/* subtle float */
@keyframes float-gentle {
    0%, 100% { transform: translateY(0px); }
    50%      { transform: translateY(-5px); }
}
.animate-float-gentle { animation: float-gentle 4s ease-in-out infinite; }
</style>
