<template>
    <div class="fixed inset-0">
        <iframe
            v-if="url"
            :src="url"
            class="fixed inset-0 w-screen h-screen"
            style="border: 0"
        ></iframe>
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref, onBeforeUnmount } from 'vue';
import { useRoute } from 'vue-router';
import { useBrowserSessionsStore } from '@/stores/browserSessions';
import { useAuthStore } from '@/stores/auth';
import { useServiceStore } from '@/stores/services';
import { usePluginDetection } from '@/composables/usePluginDetection';

const url = ref<string | null>(null);
const canStartSession = ref(false);
const hasStarted = ref(false);

const route = useRoute();
const sessions = useBrowserSessionsStore();
const auth = useAuthStore();
const serviceStore = useServiceStore();
const { checkPluginInstalled } = usePluginDetection();

let timeoutId: number | null = null;
let pluginStatusHandler: ((e: Event) => void) | null = null;

const parseServiceId = (): number | null => {
    const raw = route.params.id as string | undefined;
    const id = raw ? Number(raw) : NaN;
    return Number.isFinite(id) ? id : null;
};

const setFavicon = (href: string, type?: string) => {
    let link = document.querySelector<HTMLLinkElement>("link[rel='icon']");
    if (!link) {
        link = document.createElement('link');
        link.rel = 'icon';
        document.head.appendChild(link);
    }
    link.type = type ?? 'image/png';
    link.href = href;
};

const cleanup = () => {
    if (timeoutId !== null) {
        clearTimeout(timeoutId);
        timeoutId = null;
    }
    if (pluginStatusHandler) {
        window.removeEventListener('app:plugin-status', pluginStatusHandler as EventListener);
        pluginStatusHandler = null;
    }
};

onMounted(async () => {
    const serviceId = parseServiceId();
    await serviceStore.fetchData();

    // Проверяем плагин сразу
    const pluginResult = await checkPluginInstalled();
    console.log('Plugin check result:', pluginResult);

    if (pluginResult.isInstalled) {
        console.log('Plugin already installed, starting session...');
        canStartSession.value = true;
        void startSessionLogic(serviceId);
    } else {
        // Слушаем события от FullPageLoader
        pluginStatusHandler = (event: Event) => {
            const { pluginInstalled, pluginSkipped } = (event as CustomEvent).detail || {};
            console.log('SessionStart: Plugin status received:', { pluginInstalled, pluginSkipped });

            if (pluginInstalled || pluginSkipped) {
                console.log('SessionStart: Starting session logic...');
                canStartSession.value = true;
                void startSessionLogic(serviceId);
            }
        };

        window.addEventListener('app:plugin-status', pluginStatusHandler as EventListener);

        // Просим FullPageLoader показать модалку
        console.log('Plugin not installed, showing modal...');
        window.dispatchEvent(new CustomEvent('app:check-plugin-status'));
    }
});

// Запуск сессии (только когда плагин установлен или пропущен)
const startSessionLogic = async (serviceId: number | null) => {
    if (!canStartSession.value) {
        console.log('Cannot start session: plugin not installed and not skipped');
        return;
    }
    if (hasStarted.value || url.value) {
        console.log('Session already started, skipping duplicate call');
        return;
    }

    try {
        hasStarted.value = true;
        console.log('Starting session logic...');
        const result = await sessions.startSession(serviceId);
        if (!result) {
            console.error('Failed to start browser session', result);
            hasStarted.value = false; // позволим повторить при необходимости
            return;
        }

        url.value = result.url;

        timeoutId = window.setTimeout(() => {
            window.dispatchEvent(new CustomEvent('app:hide-loader'));

            const currentService = serviceId ? serviceStore.getById(serviceId) : undefined;
            const params = (currentService as any)?.params as { icon?: string; title?: string } | undefined;

            if (params?.title && typeof params.title === 'string' && params.title.length > 0) {
                document.title = params.title;
            }

            if (params?.icon && typeof params.icon === 'string' && params.icon.length > 0) {
                const rawIcon = params.icon;
                const isAbsolute = /^https?:\/\//i.test(rawIcon);
                const domain = (import.meta as any).env?.VITE_APP_DOMAIN || '';
                const iconUrl = isAbsolute ? rawIcon : `${domain}${rawIcon}`;

                const iconType = iconUrl.endsWith('.ico') ? 'image/x-icon' : 'image/png';
                setFavicon(iconUrl, iconType);
            }
        }, 5250);

        void auth.update({ browser_session_pid: result.pid }).catch(e => {
            console.error('Failed to save PID to user:', e);
        });
    } catch (error) {
        console.error('Error in startSessionLogic:', error);
        hasStarted.value = false; // дать шанс повтору
    }
};

onBeforeUnmount(() => {
    cleanup();
});
</script>
