import type { Ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '@/stores/auth';

interface ChatFormattersDeps {
    /** Current chat (for resolving the guest name on guest messages). */
    chat: Ref<any>;
}

/**
 * Display formatters for the support chat (extracted from SupportChatWidget.vue):
 * message sender label, relative time, calendar date, file size and image-type detection.
 */
export function useChatFormatters({ chat }: ChatFormattersDeps) {
    const { t } = useI18n();
    const authStore = useAuthStore();

    const getMessageSender = (message: any): string => {
        if (message.sender_type === 'admin') {
            return message.user?.name || t('supportChat.admin');
        }
        if (message.sender_type === 'guest') {
            return chat.value?.guest_name || t('supportChat.guest');
        }
        return message.user?.name || authStore.user?.name || t('supportChat.you');
    };

    const formatTime = (dateString: string): string => {
        const date = new Date(dateString);
        const now = new Date();
        const diff = now.getTime() - date.getTime();
        const minutes = Math.floor(diff / 60000);

        if (minutes < 1) return t('supportChat.justNow');
        if (minutes < 60) return `${minutes} ${t('supportChat.minutesAgo')}`;

        const hours = Math.floor(minutes / 60);
        if (hours < 24) return `${hours} ${t('supportChat.hoursAgo')}`;

        return date.toLocaleDateString();
    };

    const formatDate = (dateString: string | null | undefined): string => {
        if (!dateString) return '';

        try {
            const date = new Date(dateString);
            if (isNaN(date.getTime())) return '';

            const now = new Date();
            const diff = now.getTime() - date.getTime();
            const days = Math.floor(diff / (1000 * 60 * 60 * 24));

            if (days === 0) {
                return date.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
            } else if (days === 1) {
                return t('supportChat.yesterday');
            } else if (days < 7) {
                return date.toLocaleDateString('ru-RU', { weekday: 'short' });
            } else {
                return date.toLocaleDateString('ru-RU', {
                    day: '2-digit',
                    month: '2-digit',
                    year: '2-digit'
                });
            }
        } catch (error) {
            console.error('[SupportChat] Error formatting date:', error);
            return '';
        }
    };

    const isImageAttachment = (attachment: any): boolean => {
        const imageMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        return imageMimes.includes(attachment.mime_type);
    };

    const formatFileSize = (bytes: number): string => {
        if (!bytes) return '0 B';
        const units = ['B', 'KB', 'MB', 'GB'];
        let size = bytes;
        let unit = 0;

        while (size >= 1024 && unit < units.length - 1) {
            size /= 1024;
            unit++;
        }

        return `${size.toFixed(2)} ${units[unit]}`;
    };

    return {
        getMessageSender,
        formatTime,
        formatDate,
        isImageAttachment,
        formatFileSize
    };
}
