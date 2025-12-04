<template>
    <div v-if="enabled" class="support-chat-widget">
        <!-- Кнопка чата -->
        <button
            v-if="!isChatOpen || isMinimized"
            class="support-chat-button"
            :aria-label="$t('supportChat.title')"
            @click="toggleChat"
        >
            <svg class="support-chat-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
                />
            </svg>
            <span v-if="unreadCount > 0" class="support-chat-badge">{{ unreadCount }}</span>
        </button>

        <!-- Окно чата -->
        <Transition name="slide-up">
            <div v-if="isChatOpen && !isMinimized" class="support-chat-window">
                <div class="support-chat-header">
                    <div class="support-chat-header-info">
                        <h3 class="support-chat-title">{{ $t('supportChat.title') }}</h3>
                        <p v-if="chat" class="support-chat-status">
                            <span v-if="chat.status === 'open'">{{
                                $t('supportChat.statusOpen')
                            }}</span>
                            <span v-else-if="chat.status === 'closed'">{{
                                $t('supportChat.statusClosed')
                            }}</span>
                            <span v-else-if="chat.status === 'pending'">{{
                                $t('supportChat.statusPending')
                            }}</span>
                        </p>
                    </div>
                    <div class="support-chat-header-actions">
                        <!-- Кнопка истории (только для авторизованных пользователей) -->
                        <button
                            v-if="authStore.isAuthenticated && !showChoiceScreen"
                            :class="['support-chat-history-button', { active: showChatHistory }]"
                            :aria-label="$t('supportChat.chatHistory')"
                            :title="
                                showChatHistory
                                    ? $t('supportChat.hideHistory')
                                    : $t('supportChat.showHistory')
                            "
                            @click.stop.prevent="toggleChatHistory"
                        >
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
                                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                                />
                            </svg>
                        </button>
                        <!-- Кнопка свернуть -->
                        <button
                            class="support-chat-minimize"
                            :aria-label="$t('supportChat.minimize')"
                            @click.stop.prevent="minimizeChat"
                        >
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
                                    d="M20 12H4"
                                />
                            </svg>
                        </button>
                        <!-- Кнопка закрыть -->
                        <button
                            class="support-chat-close"
                            :aria-label="$t('supportChat.close')"
                            @click.stop.prevent="closeChat"
                        >
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
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Экран выбора между Telegram и встроенным чатом -->
                <div
                    v-if="showChoiceScreen && !chat && isChatOpen"
                    class="support-chat-choice-screen"
                >
                    <div class="choice-content">
                        <h4 class="choice-title">{{ $t('supportChat.chooseMethod') }}</h4>
                        <p class="choice-description">
                            {{ $t('supportChat.chooseMethodDescription') }}
                        </p>

                        <div class="choice-buttons">
                            <button class="choice-button choice-inline" @click="chooseInlineChat">
                                <svg
                                    class="choice-icon"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
                                    />
                                </svg>
                                <span class="choice-button-text">
                                    <strong>{{ $t('supportChat.inlineChat') }}</strong>
                                    <small>{{ $t('supportChat.inlineChatDescription') }}</small>
                                </span>
                            </button>

                            <button class="choice-button choice-telegram" @click="goToTelegram">
                                <svg class="choice-icon" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"
                                    />
                                </svg>
                                <span class="choice-button-text">
                                    <strong>{{ $t('supportChat.telegram') }}</strong>
                                    <small>{{ $t('supportChat.telegramDescription') }}</small>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Форма для гостей (если не авторизован, нет чата и не показываем экран выбора) -->
                <div
                    v-if="!authStore.isAuthenticated && !chat && !showChoiceScreen && isChatOpen"
                    class="support-chat-guest-form"
                >
                    <div class="form-group">
                        <label>{{ $t('supportChat.guestName') }}</label>
                        <input
                            v-model="guestName"
                            type="text"
                            class="form-input"
                            :placeholder="$t('supportChat.guestNamePlaceholder')"
                            required
                        />
                    </div>
                    <div class="form-group">
                        <label>{{ $t('supportChat.guestEmail') }}</label>
                        <input
                            v-model="guestEmail"
                            type="email"
                            class="form-input"
                            :placeholder="$t('supportChat.guestEmailPlaceholder')"
                            required
                        />
                    </div>
                    <button
                        :disabled="!guestName || !guestEmail || isLoading"
                        class="btn-primary"
                        @click="createGuestChat"
                    >
                        {{ isLoading ? $t('supportChat.loading') : $t('supportChat.startChat') }}
                    </button>
                </div>

                <!-- Список истории чатов -->
                <div
                    v-if="
                        authStore.isAuthenticated &&
                        showChatHistory &&
                        isChatOpen &&
                        !showChoiceScreen
                    "
                    class="support-chat-history-view"
                    @click.stop
                >
                    <div class="chat-history-header-inline">
                        <h4>{{ $t('supportChat.chatHistory') }}</h4>
                    </div>
                    <div ref="chatHistoryList" class="chat-history-list-inline">
                        <div v-if="isLoadingChatHistory" class="chat-history-loading">
                            {{ $t('supportChat.loading') }}...
                        </div>
                        <div v-else-if="chatHistory.length === 0" class="chat-history-empty">
                            {{ $t('supportChat.noChatHistory') }}
                        </div>
                        <template v-else>
                            <button
                                v-for="historyChat in chatHistory"
                                :key="historyChat.id"
                                :class="[
                                    'chat-history-item',
                                    { active: chat?.id === historyChat.id }
                                ]"
                                @click.stop.prevent="selectChatFromHistory(historyChat)"
                            >
                                <div class="chat-history-item-header">
                                    <span class="chat-history-item-date">
                                        {{
                                            formatDate(
                                                historyChat.last_message_at ||
                                                    historyChat.created_at
                                            )
                                        }}
                                    </span>
                                    <span
                                        :class="[
                                            'chat-history-item-status',
                                            `status-${historyChat.status}`
                                        ]"
                                    >
                                        <span v-if="historyChat.status === 'open'">{{
                                            $t('supportChat.statusOpen')
                                        }}</span>
                                        <span v-else-if="historyChat.status === 'closed'">{{
                                            $t('supportChat.statusClosed')
                                        }}</span>
                                        <span v-else-if="historyChat.status === 'pending'">{{
                                            $t('supportChat.statusPending')
                                        }}</span>
                                    </span>
                                </div>
                                <div
                                    v-if="
                                        historyChat.last_message && historyChat.last_message.message
                                    "
                                    class="chat-history-item-preview"
                                >
                                    {{ historyChat.last_message.message.substring(0, 60)
                                    }}{{
                                        historyChat.last_message.message.length > 60 ? '...' : ''
                                    }}
                                </div>
                                <div v-else class="chat-history-item-preview">
                                    {{ $t('supportChat.noMessages') }}
                                </div>
                            </button>
                        </template>
                    </div>
                    <div class="chat-history-footer-inline">
                        <button
                            class="btn-primary new-chat-from-history-button"
                            :disabled="isLoading"
                            @click="startNewChat"
                        >
                            {{ $t('supportChat.startNewChat') }}
                        </button>
                    </div>
                </div>

                <!-- Окно сообщений (показываем только когда есть активный чат, окно открыто, не показываем экран выбора и не показываем историю) -->
                <div
                    v-if="chat && isChatOpen && !showChoiceScreen && !showChatHistory"
                    ref="messagesContainer"
                    class="support-chat-messages"
                >
                    <div v-if="messages.length === 0" class="support-chat-empty">
                        <p>{{ $t('supportChat.noMessages') }}</p>
                    </div>
                    <div
                        v-for="message in messages"
                        :key="message.id"
                        :class="[
                            'support-chat-message',
                            message.sender_type === 'admin' ? 'message-admin' : 'message-user'
                        ]"
                    >
                        <div class="message-content">
                            <div class="message-header">
                                <span class="message-sender">{{ getMessageSender(message) }}</span>
                                <span class="message-time">{{
                                    formatTime(message.created_at)
                                }}</span>
                            </div>
                            <div class="message-text">{{ message.message }}</div>
                            <!-- Вложения -->
                            <div
                                v-if="message.attachments && message.attachments.length > 0"
                                class="message-attachments"
                            >
                                <div
                                    v-for="attachment in message.attachments"
                                    :key="attachment.id"
                                    class="attachment-item"
                                >
                                    <a
                                        v-if="isImageAttachment(attachment)"
                                        :href="attachment.file_url || attachment.full_url"
                                        target="_blank"
                                        class="attachment-link"
                                    >
                                        <img
                                            :src="attachment.file_url || attachment.full_url"
                                            :alt="attachment.file_name"
                                            class="attachment-image"
                                        />
                                    </a>
                                    <a
                                        v-else
                                        :href="attachment.file_url || attachment.full_url"
                                        target="_blank"
                                        :download="attachment.file_name"
                                        class="attachment-link attachment-file"
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
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                            />
                                        </svg>
                                        <span>{{ attachment.file_name }}</span>
                                        <span v-if="attachment.file_size" class="attachment-size"
                                            >({{ formatFileSize(attachment.file_size) }})</span
                                        >
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Индикатор печати администратора -->
                    <div
                        v-if="adminIsTyping && chat && chat.status !== 'closed'"
                        class="support-chat-message message-admin"
                    >
                        <div class="message-content">
                            <div class="message-header">
                                <span class="message-sender">{{ $t('supportChat.admin') }}</span>
                            </div>
                            <div class="message-text typing-message">
                                <div class="typing-indicator">
                                    <span></span>
                                    <span></span>
                                    <span></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Сообщение о закрытом диалоге и форма оценки -->
                <div
                    v-if="
                        chat &&
                        isChatOpen &&
                        !showChoiceScreen &&
                        !showChatHistory &&
                        chat.status === 'closed'
                    "
                    class="support-chat-closed-info"
                >
                    <!-- Форма оценки качества поддержки -->
                    <div v-if="!chat.rating && !showRatingForm" class="rating-prompt">
                        <p class="closed-text">{{ $t('supportChat.closedInfo') }}</p>
                        <p class="rating-text">{{ $t('supportChat.rateSupport') }}</p>
                        <button
                            class="btn-primary rating-button"
                            :disabled="isLoading"
                            @click="showRatingForm = true"
                        >
                            {{ $t('supportChat.rateSupportButton') }}
                        </button>
                    </div>
                    <!-- Форма оценки -->
                    <div v-if="showRatingForm && !chat.rating" class="rating-form">
                        <h4 class="rating-title">{{ $t('supportChat.rateSupport') }}</h4>
                        <div class="rating-stars">
                            <button
                                v-for="star in 5"
                                :key="star"
                                class="star-button"
                                :class="{ active: star <= selectedRating }"
                                @click="selectedRating = star"
                            >
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                                    />
                                </svg>
                            </button>
                        </div>
                        <textarea
                            v-model="ratingComment"
                            class="rating-comment"
                            :placeholder="$t('supportChat.ratingCommentPlaceholder')"
                            rows="3"
                        ></textarea>
                        <div class="rating-buttons">
                            <button
                                class="btn-primary"
                                :disabled="!selectedRating || isSending"
                                @click="submitRating"
                            >
                                {{ $t('supportChat.submitRating') }}
                            </button>
                            <button
                                class="btn-secondary"
                                :disabled="isSending"
                                @click="showRatingForm = false"
                            >
                                {{ $t('supportChat.cancel') }}
                            </button>
                        </div>
                    </div>
                    <!-- Сообщение после оценки -->
                    <div v-if="chat.rating" class="rating-thanks">
                        <p class="thanks-text">{{ $t('supportChat.thanksForRating') }}</p>
                        <div class="rating-display">
                            <div class="rating-stars-display">
                                <svg
                                    v-for="star in 5"
                                    :key="star"
                                    class="w-5 h-5"
                                    :class="{
                                        'text-warning': star <= chat.rating,
                                        'text-muted': star > chat.rating
                                    }"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                                    />
                                </svg>
                            </div>
                            <p v-if="chat.rating_comment" class="rating-comment-text">
                                {{ chat.rating_comment }}
                            </p>
                        </div>
                        <button
                            class="btn-primary new-chat-button"
                            :disabled="isLoading"
                            @click="startNewChat"
                        >
                            {{ $t('supportChat.startNewChat') }}
                        </button>
                    </div>
                </div>

                <!-- Форма отправки сообщения -->
                <div
                    v-if="
                        chat &&
                        isChatOpen &&
                        !showChoiceScreen &&
                        !showChatHistory &&
                        chat.status !== 'closed'
                    "
                    class="support-chat-input"
                >
                    <div class="message-input-wrapper">
                        <textarea
                            v-model="newMessage"
                            class="message-input"
                            :placeholder="$t('supportChat.messagePlaceholder')"
                            rows="2"
                            @keydown.enter.exact.prevent="sendMessage"
                            @keydown.shift.enter.exact="newLine"
                            @keydown.exact="handleTyping"
                            @input="handleTyping"
                        ></textarea>
                    </div>
                    <input
                        ref="fileInputRef"
                        type="file"
                        multiple
                        accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar"
                        style="display: none"
                        @change="handleFileSelect"
                    />
                    <!-- Превью выбранных файлов -->
                    <div v-if="selectedFiles.length > 0" class="attachments-preview">
                        <div
                            v-for="(file, index) in selectedFiles"
                            :key="index"
                            class="attachment-preview-item"
                        >
                            <span class="attachment-preview-name">{{ file.name }}</span>
                            <span class="attachment-preview-size"
                                >({{ formatFileSize(file.size) }})</span
                            >
                            <button
                                class="attachment-preview-remove"
                                type="button"
                                @click="removeFile(index)"
                            >
                                ×
                            </button>
                        </div>
                    </div>
                    <div class="message-input-actions">
                        <button
                            class="attach-button"
                            :disabled="isSending"
                            title="Прикрепить файл"
                            @click.stop.prevent="triggerFileInput"
                        >
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
                                    d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"
                                />
                            </svg>
                        </button>
                        <button
                            :disabled="
                                (!newMessage.trim() && selectedFiles.length === 0) || isSending
                            "
                            class="send-button"
                            @click="sendMessage"
                        >
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
                                    d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"
                                />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '@/stores/auth';
import axios from '@/bootstrap';

const { t } = useI18n();
const authStore = useAuthStore();

const enabled = ref(false);
const isChatOpen = ref(false);
const isMinimized = ref(false);
const chat = ref<any>(null);
const messages = ref<any[]>([]);
const newMessage = ref('');
const isLoading = ref(false);
const isSending = ref(false);
const unreadCount = ref(0);
const guestName = ref('');
const guestEmail = ref('');
const messagesContainer = ref<HTMLElement | null>(null);
const chatHistoryList = ref<HTMLElement | null>(null);
const telegramLink = ref<string>('');
const showChoiceScreen = ref(false);
const showChatHistory = ref(false);
const chatHistory = ref<any[]>([]);
const isLoadingChatHistory = ref(false);
let pollInterval: number | null = null;
// const previousMessagesCount = ref(0);
const previousUnreadCountForSound = ref(0);
const notificationPermission = ref<NotificationPermission>('default');
const soundEnabled = ref(true); // Можно сделать настраиваемым
const isTyping = ref(false);
const adminIsTyping = ref(false);
let typingTimeout: number | null = null;
let typingThrottleTimeout: number | null = null;
const selectedFiles = ref<File[]>([]);
const fileInputRef = ref<HTMLInputElement | null>(null);
const showRatingForm = ref(false);
const selectedRating = ref<number>(0);
const ratingComment = ref<string>('');

// Звуковые уведомления
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

// Browser Notifications
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
                toggleChat();
            }
        };

        // Автоматически закрывать через 5 секунд
        setTimeout(() => notification.close(), 5000);
    } catch (error) {
        console.debug('Failed to show browser notification:', error);
    }
};

// Загрузка настроек
const loadSettings = async () => {
    try {
        const response = await axios.get('/support-chat-settings', { timeout: 5000 });

        // Если ответ содержит WARNING от MadelineProto, парсим JSON вручную
        let settings = response.data;
        if (typeof response.data === 'string') {
            // Удаляем все WARNING и другой текст перед JSON
            const jsonMatch = response.data.match(/\{.*\}/s);
            if (jsonMatch) {
                settings = JSON.parse(jsonMatch[0]);
            } else {
                throw new Error('Не удалось найти JSON в ответе');
            }
        }

        enabled.value =
            typeof settings.enabled === 'string'
                ? settings.enabled === 'true'
                : Boolean(settings.enabled);
        telegramLink.value = settings.telegram_link || 'https://t.me/support';

        // Запрашиваем разрешение на уведомления при первой загрузке
        if (enabled.value && 'Notification' in window) {
            await requestNotificationPermission();
        }
    } catch {
        enabled.value = false;
        telegramLink.value = 'https://t.me/support';
    }
};

// Создание или получение чата
const getOrCreateChat = async () => {
    isLoading.value = true;
    try {
        const data: any = {};

        if (!authStore.isAuthenticated) {
            if (!guestEmail.value || !guestName.value) {
                isLoading.value = false;
                return;
            }
            data.email = guestEmail.value;
            data.name = guestName.value;
        }

        const response = await axios.post('/support-chat/create', data, { timeout: 10000 });

        if (response.data.success && response.data.chat) {
            chat.value = response.data.chat;
            messages.value = response.data.chat.messages || [];
            await nextTick();
            scrollToBottom();
            startPolling();
            updateUnreadCount();
        }
    } catch (error: any) {
        console.error('[SupportChat] Failed to create chat:', error);
    } finally {
        isLoading.value = false;
    }
};

// Создание гостевого чата
const createGuestChat = async () => {
    if (!guestName.value || !guestEmail.value) return;
    await getOrCreateChat();
};

// Переключение окна чата
const toggleChat = async () => {
    if (isMinimized.value) {
        isMinimized.value = false;
        isChatOpen.value = true;
        await nextTick();
        if (chat.value && messagesContainer.value) {
            scrollToBottom();
        }
        return;
    }

    if (isChatOpen.value) {
        minimizeChat();
        return;
    }

    await openChat();
};

// Сворачивание чата
const minimizeChat = () => {
    isMinimized.value = true;
    showChatHistory.value = false;
};

// Открытие чата
const openChat = async () => {
    // Если чат уже создан, просто открываем существующий чат
    if (chat.value) {
        isChatOpen.value = true;
        isMinimized.value = false;
        showChoiceScreen.value = false;
        showChatHistory.value = false;
        await nextTick();
        scrollToBottom();
        updateUnreadCount();

        // Загружаем историю для авторизованных пользователей, если её ещё нет
        if (authStore.isAuthenticated && chatHistory.value.length === 0) {
            await loadChatHistory();
        }
        return;
    }

    isChatOpen.value = true;
    isMinimized.value = false;
    showChatHistory.value = false;

    // Для авторизованных пользователей сразу загружаем историю чатов
    if (authStore.isAuthenticated && chatHistory.value.length === 0) {
        await loadChatHistory();
    }

    // Показываем экран выбора, если есть валидная Telegram ссылка и чат ещё не создан
    if (
        telegramLink.value &&
        telegramLink.value.trim() !== '' &&
        telegramLink.value.startsWith('http')
    ) {
        showChoiceScreen.value = true;
    } else {
        // Если нет Telegram ссылки, сразу открываем встроенный чат
        showChoiceScreen.value = false;
        if (authStore.isAuthenticated) {
            await getOrCreateChat();
        }
    }
};

// Выбор встроенного чата
const chooseInlineChat = async () => {
    showChoiceScreen.value = false;
    if (authStore.isAuthenticated) {
        await getOrCreateChat();
    }
    // Для гостей форма уже будет показана автоматически
};

// Переход в Telegram
const goToTelegram = () => {
    window.open(telegramLink.value, '_blank');
    closeChat();
};

// Закрытие чата
const closeChat = () => {
    sendStopTypingEvent();
    isChatOpen.value = false;
    isMinimized.value = false;
    showChoiceScreen.value = false;
    showChatHistory.value = false;
    stopPolling();
    adminIsTyping.value = false;
};

// Начать новый диалог
const startNewChat = async () => {
    if (isLoading.value) return;

    chat.value = null;
    messages.value = [];
    newMessage.value = '';
    showChatHistory.value = false;

    await getOrCreateChat();
};

// Отправка сообщения
const sendMessage = async () => {
    const messageText = newMessage.value.trim();
    if (
        (!messageText && selectedFiles.value.length === 0) ||
        !chat.value ||
        isSending.value ||
        chat.value.status === 'closed'
    )
        return;

    isSending.value = true;
    const tempMessage = messageText;
    const tempFiles = [...selectedFiles.value];
    newMessage.value = '';
    selectedFiles.value = [];
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }

    // Создаем временный ID для оптимистичного обновления
    const tempId = `temp_${Date.now()}_${Math.random()}`;
    const optimisticMessage: any = {
        id: tempId,
        message: tempMessage,
        sender_type: authStore.isAuthenticated ? 'user' : 'guest',
        user_id: authStore.user?.id || null,
        user: authStore.isAuthenticated
            ? {
                  id: authStore.user?.id,
                  name: authStore.user?.name,
                  email: authStore.user?.email
              }
            : {
                  name: guestName.value,
                  email: guestEmail.value
              },
        created_at: new Date().toISOString(),
        is_read: false,
        attachments: tempFiles.map((file, index) => ({
            id: `temp_${index}`,
            file_name: file.name,
            file_path: URL.createObjectURL(file),
            file_size: file.size,
            mime_type: file.type
        })),
        is_optimistic: true // Флаг для идентификации временного сообщения
    };

    // Оптимистично добавляем сообщение сразу
    messages.value.push(optimisticMessage);
    await nextTick();
    scrollToBottom();
    sendStopTypingEvent();

    try {
        const formData = new FormData();
        formData.append('message', tempMessage || '');

        // Добавляем файлы
        tempFiles.forEach(file => {
            formData.append('attachments[]', file);
        });

        if (!authStore.isAuthenticated) {
            formData.append('email', guestEmail.value);
        }

        const response = await axios.post(`/support-chat/${chat.value.id}/messages`, formData, {
            timeout: 30000, // Увеличиваем таймаут для загрузки файлов
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });

        if (response.data.success && response.data.message) {
            // Заменяем оптимистическое сообщение на реальное
            const optimisticIndex = messages.value.findIndex((m: any) => m.id === tempId);
            if (optimisticIndex !== -1) {
                messages.value[optimisticIndex] = response.data.message;
            } else {
                // Если не нашли оптимистическое, проверяем на дубликат
                const existingIndex = messages.value.findIndex(
                    (m: any) => m.id === response.data.message.id
                );
                if (existingIndex === -1) {
                    messages.value.push(response.data.message);
                }
            }

            await nextTick();
            scrollToBottom();
            updateUnreadCount();
        } else {
            // Удаляем оптимистическое сообщение при ошибке
            const optimisticIndex = messages.value.findIndex((m: any) => m.id === tempId);
            if (optimisticIndex !== -1) {
                messages.value.splice(optimisticIndex, 1);
            }
            newMessage.value = tempMessage;
            selectedFiles.value = tempFiles;
        }
    } catch (error: any) {
        console.error('[SupportChat] Failed to send message:', error);
        // Удаляем оптимистическое сообщение при ошибке
        const optimisticIndex = messages.value.findIndex((m: any) => m.id === tempId);
        if (optimisticIndex !== -1) {
            messages.value.splice(optimisticIndex, 1);
        }
        newMessage.value = tempMessage;
        selectedFiles.value = tempFiles;
    } finally {
        isSending.value = false;
    }
};

// Загрузка сообщений
const loadMessages = async () => {
    if (!chat.value) return;

    try {
        const params: any = {};
        if (!authStore.isAuthenticated) {
            params.email = guestEmail.value;
        }

        const response = await axios.get(`/support-chat/${chat.value.id}/messages`, {
            params,
            timeout: 8000
        });

        if (response.data.success && response.data.messages) {
            const oldMessageIds = new Set(messages.value.map((m: any) => m.id));

            // Сохраняем оптимистические сообщения (временные)
            const optimisticMessages = messages.value.filter((m: any) => m.is_optimistic);

            // Объединяем сообщения: берем все из сервера и добавляем оптимистические, которых еще нет
            const serverMessageIds = new Set(response.data.messages.map((m: any) => m.id));
            const mergedMessages = [...response.data.messages];

            // Добавляем оптимистические сообщения, которых еще нет на сервере
            optimisticMessages.forEach((optMsg: any) => {
                if (!serverMessageIds.has(optMsg.id)) {
                    mergedMessages.push(optMsg);
                }
            });

            // Сортируем по дате создания
            mergedMessages.sort((a: any, b: any) => {
                return new Date(a.created_at).getTime() - new Date(b.created_at).getTime();
            });

            messages.value = mergedMessages;

            if (response.data.chat && chat.value) {
                chat.value.status = response.data.chat.status;
            }

            // Проверяем новые сообщения для уведомлений
            const newMessages = response.data.messages.filter((m: any) => !oldMessageIds.has(m.id));
            if (newMessages.length > 0) {
                const newMessagesFromAdmin = newMessages.filter(
                    (m: any) =>
                        m.sender_type === 'admin' &&
                        (!authStore.isAuthenticated || m.user_id !== authStore.user?.id)
                );

                // Звук и браузерное уведомление для новых сообщений от админов
                if (newMessagesFromAdmin.length > 0) {
                    const lastMessage = newMessagesFromAdmin[newMessagesFromAdmin.length - 1];
                    if (!isChatOpen.value || isMinimized.value) {
                        playNotificationSound();
                        showBrowserNotification(
                            t('supportChat.title'),
                            `${lastMessage.user?.name || t('supportChat.admin')}: ${lastMessage.message.substring(0, 100)}${lastMessage.message.length > 100 ? '...' : ''}`
                        );
                    }
                }

                await nextTick();
                scrollToBottom();
            }
            updateUnreadCount();
        }
    } catch (error: any) {
        if (error.code !== 'ECONNABORTED' && error.response?.status !== 403) {
            console.error('[SupportChat] Failed to load messages:', error);
        }
    }
};

// Отправка события "печатает" с throttle
const sendTypingEvent = async () => {
    if (!chat.value || !newMessage.value.trim()) {
        sendStopTypingEvent();
        return;
    }

    // Throttle: отправляем событие не чаще чем раз в 2 секунды
    if (typingThrottleTimeout) {
        return;
    }

    if (!isTyping.value) {
        isTyping.value = true;
    }

    try {
        await axios.post(`/support-chat/${chat.value.id}/typing`, {}, { timeout: 3000 });
    } catch {
        // Игнорируем ошибки
    }

    // Устанавливаем throttle на 2 секунды
    typingThrottleTimeout = window.setTimeout(() => {
        typingThrottleTimeout = null;
    }, 2000);

    // Автоматически останавливаем через 3 секунды бездействия
    if (typingTimeout) {
        clearTimeout(typingTimeout);
    }
    typingTimeout = window.setTimeout(() => {
        sendStopTypingEvent();
    }, 3000);
};

// Остановка события "печатает"
const sendStopTypingEvent = async () => {
    if (!chat.value || !isTyping.value) return;

    isTyping.value = false;

    // Очищаем throttle timeout
    if (typingThrottleTimeout) {
        clearTimeout(typingThrottleTimeout);
        typingThrottleTimeout = null;
    }

    try {
        await axios.post(`/support-chat/${chat.value.id}/typing/stop`, {}, { timeout: 3000 });
    } catch {
        // Игнорируем ошибки
    }
};

// Обработка ввода текста для индикатора печати
const handleTyping = () => {
    if (chat.value && chat.value.status !== 'closed') {
        sendTypingEvent();
    }
};

// Проверка статуса печати администратора
const checkAdminTyping = async () => {
    if (!chat.value || !isChatOpen.value) return;

    try {
        const response = await axios.get(`/support-chat/${chat.value.id}/typing/status`, {
            timeout: 3000
        });
        if (response.data.success) {
            adminIsTyping.value = response.data.is_typing || false;
        }
    } catch {
        // Игнорируем ошибки
    }
};

// Polling для обновления сообщений и статуса печати
const startPolling = () => {
    stopPolling();
    pollInterval = window.setInterval(() => {
        if (chat.value && isChatOpen.value) {
            loadMessages();
            checkAdminTyping();
        }
    }, 3000);
};

const stopPolling = () => {
    if (pollInterval) {
        clearInterval(pollInterval);
        pollInterval = null;
    }
    if (typingTimeout) {
        clearTimeout(typingTimeout);
        typingTimeout = null;
    }
    if (typingThrottleTimeout) {
        clearTimeout(typingThrottleTimeout);
        typingThrottleTimeout = null;
    }
    sendStopTypingEvent();
};

// Обновление счетчика непрочитанных сообщений
const updateUnreadCount = async () => {
    if (!chat.value) {
        unreadCount.value = 0;
        previousUnreadCountForSound.value = 0;
        return;
    }

    try {
        const unread = messages.value.filter((msg: any) => {
            if (authStore.isAuthenticated) {
                return !msg.is_read && msg.user_id !== authStore.user?.id;
            } else {
                return !msg.is_read && msg.sender_type === 'admin';
            }
        });

        const currentCount = unread.length;

        // Звук при появлении нового непрочитанного сообщения (только если чат закрыт или свернут)
        if (
            currentCount > previousUnreadCountForSound.value &&
            previousUnreadCountForSound.value >= 0
        ) {
            if (!isChatOpen.value || isMinimized.value) {
                playNotificationSound();

                const latestUnread = unread[unread.length - 1];
                if (latestUnread && Notification.permission === 'granted') {
                    showBrowserNotification(
                        t('supportChat.title'),
                        `${latestUnread.user?.name || t('supportChat.admin')}: ${latestUnread.message.substring(0, 100)}${latestUnread.message.length > 100 ? '...' : ''}`
                    );
                }
            }
        }

        unreadCount.value = currentCount;
        previousUnreadCountForSound.value = currentCount;
    } catch (error) {
        console.error('[SupportChat] Failed to update unread count:', error);
    }
};

// Прокрутка вниз
const scrollToBottom = () => {
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
};

// Обработка выбора файлов
const triggerFileInput = () => {
    if (fileInputRef.value) {
        fileInputRef.value.click();
    }
};

const handleFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files) {
        const files = Array.from(target.files);
        // Ограничиваем до 5 файлов и 10MB каждый
        const validFiles = files
            .filter(file => {
                if (file.size > 10 * 1024 * 1024) {
                    // 10MB
                    alert(`Файл "${file.name}" слишком большой. Максимальный размер: 10MB`);
                    return false;
                }
                return true;
            })
            .slice(0, 5); // Максимум 5 файлов

        selectedFiles.value = [...selectedFiles.value, ...validFiles].slice(0, 5);
    }
};

const removeFile = (index: number) => {
    selectedFiles.value.splice(index, 1);
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

// Отправка рейтинга качества поддержки
const submitRating = async () => {
    if (!chat.value || !selectedRating.value) return;

    try {
        isSending.value = true;
        const data: any = {
            rating: selectedRating.value,
            rating_comment: ratingComment.value.trim() || null
        };

        if (!authStore.isAuthenticated) {
            data.email = guestEmail.value;
        }

        const response = await axios.post(`/support-chat/${chat.value.id}/rating`, data, {
            timeout: 5000
        });

        if (response.data.success && response.data.chat) {
            chat.value = response.data.chat;
            showRatingForm.value = false;
        }
    } catch (error) {
        console.error('[SupportChat] Failed to submit rating:', error);
    } finally {
        isSending.value = false;
    }
};

// Получить имя отправителя
const getMessageSender = (message: any): string => {
    if (message.sender_type === 'admin') {
        return message.user?.name || t('supportChat.admin');
    }
    if (message.sender_type === 'guest') {
        return chat.value?.guest_name || t('supportChat.guest');
    }
    return message.user?.name || authStore.user?.name || t('supportChat.you');
};

// Форматирование времени
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

// Загрузка истории чатов
const loadChatHistory = async () => {
    if (!authStore.isAuthenticated || isLoadingChatHistory.value) return;

    isLoadingChatHistory.value = true;
    try {
        const response = await axios.get('/support-chats', { timeout: 10000 });

        if (response.data.success && response.data.chats) {
            chatHistory.value = response.data.chats;
        }
    } catch (error: any) {
        console.error('[SupportChat] Failed to load chat history:', error);
        chatHistory.value = [];
    } finally {
        isLoadingChatHistory.value = false;
    }
};

// Переключение истории чатов
const toggleChatHistory = async () => {
    if (!authStore.isAuthenticated) return;

    if (showChatHistory.value) {
        showChatHistory.value = false;
        if (chat.value) {
            await loadMessages();
        }
        return;
    }

    showChatHistory.value = true;

    if (chatHistory.value.length === 0) {
        await loadChatHistory();
    }

    await nextTick();
    if (chatHistoryList.value) {
        chatHistoryList.value.scrollTop = 0;
    }
};

// Выбор чата из истории
const selectChatFromHistory = async (historyChat: any) => {
    if (chat.value?.id === historyChat.id) {
        showChatHistory.value = false;
        return;
    }

    isLoading.value = true;
    try {
        const response = await axios.get(`/support-chat/${historyChat.id}/messages`, {
            timeout: 10000
        });

        if (response.data.success) {
            chat.value = {
                ...historyChat,
                ...(response.data.chat || {}),
                user: historyChat.user,
                assignedAdmin: historyChat.assignedAdmin
            };
            messages.value = response.data.messages || [];

            await nextTick();
            scrollToBottom();
            updateUnreadCount();

            showChatHistory.value = false;

            if (chat.value.status !== 'closed') {
                startPolling();
            } else {
                stopPolling();
            }
        }
    } catch (error: any) {
        console.error('[SupportChat] Failed to load chat from history:', error);
        if (error.response?.status === 403) {
            await loadChatHistory();
        }
    } finally {
        isLoading.value = false;
    }
};

// Форматирование даты для истории чатов
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

const newLine = () => {
    // Shift+Enter добавляет новую строку
};

onMounted(() => {
    loadSettings();
});

onUnmounted(() => {
    stopPolling();
});

watch(isChatOpen, newVal => {
    if (!newVal) {
        stopPolling();
    } else if (chat.value) {
        startPolling();
        updateUnreadCount();
    }
});

watch(
    messages,
    () => {
        updateUnreadCount();
    },
    { deep: true }
);

watch(adminIsTyping, newVal => {
    if (newVal) {
        nextTick(() => {
            scrollToBottom();
        });
    }
});
</script>

<style scoped>
.support-chat-widget {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 9999;
}

/* Полупрозрачное окно чата */
.support-chat-window {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
}

.dark .support-chat-window {
    background: rgba(17, 24, 39, 0.85);
    border-color: rgba(255, 255, 255, 0.1);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
}

.support-chat-button {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    color: white;
    position: relative;
    animation: support-chat-pulse 2.5s ease-in-out infinite;
}

.support-chat-button:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
    animation-play-state: paused;
}

.support-chat-icon {
    width: 28px;
    height: 28px;
}

.support-chat-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #ef4444;
    color: white;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: bold;
}

.support-chat-window {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 380px;
    max-width: calc(100vw - 40px);
    height: 600px;
    max-height: calc(100vh - 40px);
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 16px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    z-index: 9999;
}

.dark .support-chat-window {
    background: rgba(17, 24, 39, 0.85);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border-color: rgba(255, 255, 255, 0.1);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    color: #f3f4f6;
}

.support-chat-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    background: rgba(102, 126, 234, 0.8);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    color: white;
}

.dark .support-chat-header {
    border-bottom-color: #374151;
}

.support-chat-header-info {
    flex: 1;
}

.support-chat-title {
    font-size: 18px;
    font-weight: 600;
    margin: 0 0 4px 0;
}

.support-chat-status {
    font-size: 12px;
    opacity: 0.9;
    margin: 0;
}

.support-chat-header-actions {
    display: flex;
    gap: 8px;
    align-items: center;
}

.support-chat-history-button,
.support-chat-minimize,
.support-chat-close {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    border-radius: 8px;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: white;
    transition: all 0.2s;
}

.support-chat-history-button:hover,
.support-chat-minimize:hover,
.support-chat-close:hover {
    background: rgba(255, 255, 255, 0.3);
}

.support-chat-history-button.active {
    background: rgba(255, 255, 255, 0.3);
}

/* Экран выбора */
.support-chat-choice-screen {
    padding: 24px;
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}

.choice-content {
    width: 100%;
    max-width: 320px;
}

.choice-title {
    font-size: 20px;
    font-weight: 600;
    margin: 0 0 8px 0;
    text-align: center;
    color: #1f2937;
}

.dark .choice-title {
    color: #f3f4f6;
}

.choice-description {
    font-size: 14px;
    color: #6b7280;
    text-align: center;
    margin: 0 0 24px 0;
}

.dark .choice-description {
    color: #9ca3af;
}

.choice-buttons {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.choice-button {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    border: 2px solid rgba(229, 231, 235, 0.5);
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(5px);
    cursor: pointer;
    transition: all 0.2s;
    text-align: left;
}

.dark .choice-button {
    background: rgba(17, 24, 39, 0.8);
    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(5px);
    border-color: rgba(55, 65, 81, 0.5);
    color: #f3f4f6;
}

.choice-button:hover {
    border-color: #667eea;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
}

.choice-button.choice-inline:hover {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
}

.choice-button.choice-telegram:hover {
    background: rgba(37, 150, 190, 0.1);
    border-color: #2596be;
}

.dark .choice-button.choice-inline:hover {
    background: rgba(102, 126, 234, 0.2);
}

.dark .choice-button.choice-telegram:hover {
    background: rgba(37, 150, 190, 0.2);
}

.choice-icon {
    width: 32px;
    height: 32px;
    flex-shrink: 0;
    color: #667eea;
}

.choice-button.choice-telegram .choice-icon {
    color: #2596be;
}

.choice-button-text {
    display: flex;
    flex-direction: column;
    gap: 4px;
    flex: 1;
}

.choice-button-text strong {
    font-size: 16px;
    font-weight: 600;
    color: #1f2937;
}

.dark .choice-button-text strong {
    color: #f3f4f6;
}

.choice-button-text small {
    font-size: 13px;
    color: #6b7280;
}

.dark .choice-button-text small {
    color: #9ca3af;
}

/* Форма для гостей */
.support-chat-guest-form {
    padding: 24px;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-group label {
    font-size: 14px;
    font-weight: 500;
    color: #374151;
}

.dark .form-group label {
    color: #d1d5db;
}

.form-input {
    padding: 12px;
    border: 1px solid rgba(209, 213, 219, 0.5);
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.2s;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(5px);
    color: #1f2937;
}

.dark .form-input {
    background: rgba(17, 24, 39, 0.9);
    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(5px);
    border-color: rgba(55, 65, 81, 0.5);
    color: #f3f4f6;
}

.form-input:focus {
    outline: none;
    border-color: #667eea;
}

.btn-primary {
    padding: 12px 24px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: opacity 0.2s;
}

.btn-primary:hover:not(:disabled) {
    opacity: 0.9;
}

.btn-primary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Сообщения */
.support-chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.support-chat-empty {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #6b7280;
    text-align: center;
}

.dark .support-chat-empty {
    color: #9ca3af;
}

.support-chat-message {
    display: flex;
    flex-direction: column;
    max-width: 80%;
}

.message-user {
    align-self: flex-end;
}

.message-admin {
    align-self: flex-start;
}

.message-content {
    padding: 12px 16px;
    border-radius: 12px;
    background: rgba(243, 244, 246, 0.8);
    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(5px);
    border: 1px solid rgba(0, 0, 0, 0.1);
}

.dark .message-content {
    background: rgba(55, 65, 81, 0.8);
    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(5px);
    border-color: rgba(255, 255, 255, 0.1);
}

.message-user .message-content {
    background: rgba(102, 126, 234, 0.8);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    color: white;
    border-color: rgba(255, 255, 255, 0.2);
}

.message-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 4px;
    font-size: 12px;
    opacity: 0.8;
}

.message-text {
    font-size: 14px;
    line-height: 1.5;
    word-wrap: break-word;
}

.support-chat-closed-info {
    padding: 16px;
    border-top: 1px solid rgba(229, 231, 235, 0.5);
    background: rgba(249, 250, 251, 0.7);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.dark .support-chat-closed-info {
    border-top-color: rgba(55, 65, 81, 0.5);
    background: rgba(17, 24, 39, 0.7);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

.closed-text {
    font-size: 14px;
    color: #4b5563;
}

.dark .closed-text {
    color: #d1d5db;
}

.new-chat-button {
    align-self: flex-start;
}

.support-chat-input {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 16px;
    border-top: 1px solid rgba(229, 231, 235, 0.5);
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

.dark .support-chat-input {
    border-top-color: rgba(55, 65, 81, 0.5);
    background: rgba(17, 24, 39, 0.7);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

.message-input {
    flex: 1;
    padding: 12px;
    border: 1px solid rgba(209, 213, 219, 0.5);
    border-radius: 8px;
    font-size: 14px;
    resize: none;
    font-family: inherit;
    transition: border-color 0.2s;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(5px);
    color: #1f2937;
}

.dark .message-input {
    background: rgba(17, 24, 39, 0.9);
    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(5px);
    border-color: rgba(55, 65, 81, 0.5);
    color: #f3f4f6;
}

.message-input:focus {
    outline: none;
    border-color: #667eea;
}

.send-button {
    width: 44px;
    height: 44px;
    border-radius: 8px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: opacity 0.2s;
    flex-shrink: 0;
}

.send-button:hover:not(:disabled) {
    opacity: 0.9;
}

.send-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* История чатов */
.support-chat-history-view {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.chat-history-header-inline {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 16px 20px;
    border-bottom: 1px solid rgba(229, 231, 235, 0.5);
    position: relative;
    background: transparent;
}

.dark .chat-history-header-inline {
    border-bottom-color: rgba(55, 65, 81, 0.5);
}

.chat-history-header-inline h4 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: #1f2937;
}

.dark .chat-history-header-inline h4 {
    color: #f3f4f6;
}

.chat-history-list-inline {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    scroll-behavior: smooth;
}

.chat-history-loading,
.chat-history-empty {
    padding: 32px 16px;
    text-align: center;
    color: #6b7280;
    font-size: 14px;
}

.dark .chat-history-loading,
.dark .chat-history-empty {
    color: #9ca3af;
}

.chat-history-item {
    width: 100%;
    padding: 16px;
    border: 1px solid rgba(229, 231, 235, 0.5);
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(5px);
    cursor: pointer;
    text-align: left;
    transition: all 0.2s;
}

.dark .chat-history-item {
    background: rgba(17, 24, 39, 0.8);
    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(5px);
    border-color: rgba(55, 65, 81, 0.5);
}

.chat-history-item:hover {
    border-color: #667eea;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.2);
}

.chat-history-item.active {
    border-color: #667eea;
    background: rgba(102, 126, 234, 0.1);
}

.dark .chat-history-item.active {
    background: rgba(102, 126, 234, 0.2);
}

.chat-history-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.chat-history-item-date {
    font-size: 12px;
    color: #6b7280;
}

.dark .chat-history-item-date {
    color: #9ca3af;
}

.chat-history-item-status {
    font-size: 11px;
    padding: 2px 8px;
    border-radius: 12px;
    font-weight: 500;
}

.chat-history-item-status.status-open {
    background: #d1fae5;
    color: #065f46;
}

.dark .chat-history-item-status.status-open {
    background: #065f46;
    color: #d1fae5;
}

.chat-history-item-status.status-closed {
    background: #e5e7eb;
    color: #374151;
}

.dark .chat-history-item-status.status-closed {
    background: #374151;
    color: #e5e7eb;
}

.chat-history-item-status.status-pending {
    background: #fef3c7;
    color: #92400e;
}

.dark .chat-history-item-status.status-pending {
    background: #92400e;
    color: #fef3c7;
}

.chat-history-item-preview {
    font-size: 13px;
    color: #4b5563;
    line-height: 1.4;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.dark .chat-history-item-preview {
    color: #d1d5db;
}

.chat-history-footer-inline {
    padding: 16px 20px;
    border-top: 1px solid #e5e7eb;
}

.dark .chat-history-footer-inline {
    border-top-color: #374151;
}

.new-chat-from-history-button {
    width: 100%;
}

.slide-up-enter-active,
.slide-up-leave-active {
    transition: all 0.3s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
    opacity: 0;
    transform: translateY(20px) scale(0.95);
}

/* Стили для вложений */
.message-input-wrapper {
    position: relative;
    display: flex;
    align-items: flex-end;
    width: 100%;
}

.message-input-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    justify-content: flex-end;
    flex-shrink: 0;
}

.attach-button {
    padding: 8px 12px;
    background: #f3f4f6;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    cursor: pointer;
    color: #6b7280;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.attach-button:hover:not(:disabled) {
    background: #e5e7eb;
    color: #374151;
}

.dark .attach-button {
    background: #374151;
    border-color: #4b5563;
    color: #9ca3af;
}

.dark .attach-button:hover:not(:disabled) {
    background: #4b5563;
    color: #d1d5db;
}

.attach-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.send-button {
    padding: 8px 16px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 8px;
    cursor: pointer;
    color: white;
    transition: opacity 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.send-button:hover:not(:disabled) {
    opacity: 0.9;
}

.send-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.attachments-preview {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    padding: 8px;
    background: #f9fafb;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    width: 100%;
    box-sizing: border-box;
}

.dark .attachments-preview {
    background: #1f2937;
    border-color: #374151;
}

.attachment-preview-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 12px;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    font-size: 13px;
    flex: 1 1 auto;
    min-width: 0;
}

.dark .attachment-preview-item {
    background: #111827;
    border-color: #374151;
}

.attachment-preview-name {
    color: #374151;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    flex: 1;
    min-width: 0;
}

.dark .attachment-preview-name {
    color: #d1d5db;
}

.attachment-preview-size {
    color: #6b7280;
    font-size: 12px;
}

.dark .attachment-preview-size {
    color: #9ca3af;
}

.attachment-preview-remove {
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #ef4444;
    color: white;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    font-size: 16px;
    line-height: 1;
    transition: opacity 0.2s;
}

.attachment-preview-remove:hover {
    opacity: 0.8;
}

.message-attachments {
    margin-top: 8px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.attachment-item {
    display: inline-block;
}

.attachment-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 6px;
    text-decoration: none;
    color: inherit;
    transition: background 0.2s;
}

.message-user .attachment-link {
    background: rgba(0, 0, 0, 0.05);
}

.dark .message-user .attachment-link {
    background: rgba(255, 255, 255, 0.1);
}

.attachment-link:hover {
    background: rgba(255, 255, 255, 0.2);
}

.message-user .attachment-link:hover {
    background: rgba(0, 0, 0, 0.1);
}

.dark .message-user .attachment-link:hover {
    background: rgba(255, 255, 255, 0.15);
}

.attachment-image {
    max-width: 200px;
    max-height: 200px;
    border-radius: 6px;
    cursor: pointer;
    display: block;
}

.attachment-file {
    font-size: 14px;
}

.attachment-size {
    font-size: 12px;
    opacity: 0.7;
}

/* Форма оценки качества поддержки */
.rating-prompt {
    text-align: center;
    padding: 20px;
}

.rating-text {
    margin: 16px 0;
    font-size: 15px;
    color: #6b7280;
}

.dark .rating-text {
    color: #9ca3af;
}

.rating-button {
    margin-top: 12px;
}

.rating-form {
    padding: 20px;
    text-align: center;
}

.rating-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 16px;
    color: #1f2937;
}

.dark .rating-title {
    color: #f3f4f6;
}

.rating-stars {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-bottom: 16px;
}

.star-button {
    background: transparent;
    border: none;
    cursor: pointer;
    padding: 4px;
    color: #d1d5db;
    transition: all 0.2s;
}

.star-button:hover {
    transform: scale(1.1);
}

.star-button.active {
    color: #fbbf24;
}

.rating-comment {
    width: 100%;
    padding: 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    resize: none;
    margin-bottom: 16px;
    background: white;
    color: #1f2937;
}

.dark .rating-comment {
    background: #111827;
    border-color: #374151;
    color: #f3f4f6;
}

.rating-buttons {
    display: flex;
    gap: 12px;
    justify-content: center;
}

.btn-secondary {
    padding: 10px 20px;
    background: #e5e7eb;
    color: #374151;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    transition: opacity 0.2s;
}

.dark .btn-secondary {
    background: #374151;
    color: #d1d5db;
}

.btn-secondary:hover:not(:disabled) {
    opacity: 0.8;
}

.btn-secondary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.rating-thanks {
    text-align: center;
    padding: 20px;
}

.thanks-text {
    font-size: 16px;
    font-weight: 500;
    margin-bottom: 16px;
    color: #1f2937;
}

.dark .thanks-text {
    color: #f3f4f6;
}

.rating-display {
    margin-bottom: 16px;
}

.rating-stars-display {
    display: flex;
    justify-content: center;
    gap: 4px;
    margin-bottom: 8px;
}

.rating-comment-text {
    font-size: 14px;
    color: #6b7280;
    font-style: italic;
    margin-top: 8px;
}

.dark .rating-comment-text {
    color: #9ca3af;
}

.text-warning {
    color: #fbbf24;
}

.text-muted {
    color: #9ca3af;
}

/* Typing indicator styles */
.typing-message {
    display: flex;
    align-items: center;
    min-height: 20px;
}

.typing-indicator {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 0;
}

.typing-indicator span {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background-color: currentColor;
    opacity: 0.6;
    animation: typing-bounce 1.4s infinite ease-in-out;
}

.typing-indicator span:nth-child(1) {
    animation-delay: 0s;
}

.typing-indicator span:nth-child(2) {
    animation-delay: 0.2s;
}

.typing-indicator span:nth-child(3) {
    animation-delay: 0.4s;
}

@keyframes typing-bounce {
    0%,
    60%,
    100% {
        transform: translateY(0);
        opacity: 0.6;
    }
    30% {
        transform: translateY(-5px);
        opacity: 1;
    }
}

/* Keyframe animation for support chat button pulse */
@keyframes support-chat-pulse {
    0%,
    100% {
        transform: scale(1);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }
    50% {
        transform: scale(1.05);
        box-shadow: 0 4px 16px rgba(102, 126, 234, 0.6);
    }
}

@media (max-width: 640px) {
    .support-chat-window {
        width: calc(100vw - 20px);
        height: calc(100vh - 20px);
        bottom: 10px;
        right: 10px;
        border-radius: 12px;
    }

    .support-chat-button {
        width: 56px;
        height: 56px;
        bottom: 60px;
        right: 0;
    }

    .support-chat-input {
        padding: 12px;
        gap: 8px;
    }

    .message-input-wrapper {
        width: 100%;
    }

    .message-input {
        font-size: 16px; /* Prevents zoom on iOS */
    }

    .message-input-actions {
        width: 100%;
        justify-content: space-between;
    }

    .attach-button,
    .send-button {
        min-width: 44px;
        height: 44px;
        padding: 8px;
    }

    .attachments-preview {
        padding: 6px;
        gap: 6px;
    }

    .attachment-preview-item {
        padding: 6px 10px;
        font-size: 12px;
        flex: 1 1 100%;
        max-width: 100%;
    }

    .attachment-preview-name {
        max-width: calc(100% - 80px);
    }
}
</style>
