import { ref, type Ref } from 'vue';

interface ChatNotificationsDeps {
    /** Whether the chat panel is currently open (suppresses browser notifications). */
    isChatOpen: Ref<boolean>;
    /** Invoked when the user clicks a browser notification (e.g. open the chat). */
    onActivate: () => void;
}

/**
 * Sound + browser notifications for the support chat (extracted from SupportChatWidget.vue).
 * Self-contained around the Web Audio / Notification APIs; chat-open state and the
 * notification-click action are injected so the widget keeps owning them.
 */
export function useChatNotifications({ isChatOpen, onActivate }: ChatNotificationsDeps) {
    const notificationPermission = ref<NotificationPermission>('default');
    const soundEnabled = ref(true); // Можно сделать настраиваемым

    const playNotificationSound = () => {
        if (!soundEnabled.value) return;

        try {
            const audio = new Audio('/sounds/notification.mp3');
            audio.volume = 0.3; // 30% громкости
            audio.play().catch(() => {
                // Игнорируем ошибки (например, если пользователь не взаимодействовал со страницей)
            });
        } catch (error) {
            console.debug('Failed to play notification sound:', error);
        }
    };

    const requestNotificationPermission = async () => {
        if (!('Notification' in window)) {
            return;
        }

        if (Notification.permission === 'default') {
            notificationPermission.value = await Notification.requestPermission();
        } else {
            notificationPermission.value = Notification.permission;
        }
    };

    const showBrowserNotification = (title: string, body: string, icon?: string) => {
        if (Notification.permission !== 'granted') return;
        if (document.visibilityState === 'visible' && isChatOpen.value) return; // Не показывать если чат открыт

        try {
            const notification = new Notification(title, {
                body,
                icon: icon || '/favicon.ico',
                badge: '/favicon.ico',
                tag: 'support-chat',
                requireInteraction: false
            });

            notification.onclick = () => {
                window.focus();
                notification.close();
                if (!isChatOpen.value) {
                    onActivate();
                }
            };

            // Автоматически закрывать через 5 секунд
            setTimeout(() => notification.close(), 5000);
        } catch (error) {
            console.debug('Failed to show browser notification:', error);
        }
    };

    return {
        notificationPermission,
        soundEnabled,
        playNotificationSound,
        requestNotificationPermission,
        showBrowserNotification
    };
}
