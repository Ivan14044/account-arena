import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useToast } from 'vue-toastification';
import axios from '@/bootstrap';
import { useAuthStore } from '@/stores/auth';
import { useProductTitle } from '@/composables/useProductTitle';

interface OrderActionsDeps {
    /** Refresh the purchases list after an order is cancelled. */
    refreshPurchases: () => Promise<void>;
}

/**
 * Order-level actions for ProfilePage: cancel a processing order, view its status history,
 * and contact a manager via the support-chat widget. Extracted verbatim from ProfilePage.vue.
 */
export function useOrderActions({ refreshPurchases }: OrderActionsDeps) {
    const { t } = useI18n();
    const toast = useToast();
    const authStore = useAuthStore();
    const { getProductTitle } = useProductTitle();

    // Cancel order modal
    const showCancelModal = ref(false);
    const cancellationReason = ref('');
    const selectedPurchaseForCancel = ref<any>(null);

    // Status history modal
    const showStatusHistoryModal = ref(false);
    const fullStatusHistory = ref<any[]>([]);
    const loadingStatusHistory = ref(false);
    const selectedPurchaseForHistory = ref<any>(null);

    const openCancelModal = (purchase: any) => {
        selectedPurchaseForCancel.value = purchase;
        cancellationReason.value = '';
        showCancelModal.value = true;
    };

    const closeCancelModal = () => {
        showCancelModal.value = false;
        selectedPurchaseForCancel.value = null;
        cancellationReason.value = '';
    };

    const openStatusHistoryModal = async (purchase: any) => {
        selectedPurchaseForHistory.value = purchase;
        showStatusHistoryModal.value = true;
        loadingStatusHistory.value = true;
        fullStatusHistory.value = [];

        try {
            const token = authStore.token;
            if (!token) {
                toast.error(t('profile.purchases.not_authorized'));
                return;
            }

            const response = await axios.get(`/purchases/${purchase.id}/status-history`);

            if (response.data.success && response.data.status_history) {
                fullStatusHistory.value = response.data.status_history;
            } else {
                toast.error(t('profile.purchases.status_history_error'));
            }
        } catch (error: any) {
            console.error('Error fetching status history:', error);
            toast.error(
                error.response?.data?.message || t('profile.purchases.status_history_error')
            );
        } finally {
            loadingStatusHistory.value = false;
        }
    };

    const closeStatusHistoryModal = () => {
        showStatusHistoryModal.value = false;
        selectedPurchaseForHistory.value = null;
        fullStatusHistory.value = [];
        loadingStatusHistory.value = false;
    };

    const cancelProcessingOrder = async () => {
        if (!selectedPurchaseForCancel.value) {
            return;
        }

        const purchase = selectedPurchaseForCancel.value;

        // Валидация причины отмены
        const reason = cancellationReason.value.trim();
        if (reason.length < 10) {
            toast.error(t('profile.purchases.cancel_reason_min_length'));
            return;
        }
        if (reason.length > 500) {
            toast.error(t('profile.purchases.cancel_reason_max_length'));
            return;
        }

        try {
            const token = authStore.token;

            if (!token) {
                toast.error(t('profile.purchases.not_authorized'));
                return;
            }

            const config: any = {};

            // Для гостевых покупок добавляем email в query параметры
            if (!purchase.user_id && purchase.guest_email) {
                config.params = { guest_email: purchase.guest_email };
            }

            const response = await axios.post(
                `/purchases/${purchase.id}/cancel`,
                {
                    cancellation_reason: reason
                },
                config
            );

            if (response.data.success) {
                toast.success(t('profile.purchases.order_cancelled'));
                closeCancelModal();
                // Обновляем список покупок
                await refreshPurchases();
            }
        } catch (error: any) {
            toast.error(error.response?.data?.error || t('profile.purchases.cancel_order_error'));
        }
    };

    const contactManagerAboutOrder = async (purchase: any) => {
        try {
            const productTitle = getProductTitle(
                purchase.service_name || purchase.product?.title || {}
            );
            const orderNumber = purchase.order_number || purchase.id;

            // Формируем сообщение с контекстом заказа
            const initialMessage = t('profile.purchases.contact_manager_message', {
                order_number: orderNumber,
                product_title: productTitle
            });

            // Проверяем, что чат поддержки доступен
            const chatWidget = document.querySelector('.support-chat-widget');
            if (!chatWidget) {
                toast.info(t('profile.purchases.chat_not_available'), {
                    timeout: 5000
                });
                return;
            }

            // Открываем чат поддержки через событие
            const event = new CustomEvent('openSupportChat', {
                detail: {
                    initialMessage: initialMessage
                }
            });
            window.dispatchEvent(event);

            // Проверяем, что событие обработалось (fallback через 1 секунду)
            let eventHandled = false;
            const checkInterval = setTimeout(() => {
                if (!eventHandled) {
                    toast.info(t('profile.purchases.contact_manager_hint'), {
                        timeout: 5000
                    });
                }
            }, 1000);

            // Слушаем событие об успешном открытии чата (если есть)
            const handleChatOpened = () => {
                eventHandled = true;
                clearTimeout(checkInterval);
                window.removeEventListener('supportChatOpened', handleChatOpened);
            };
            window.addEventListener('supportChatOpened', handleChatOpened);

            // Очищаем слушатель через 2 секунды
            setTimeout(() => {
                window.removeEventListener('supportChatOpened', handleChatOpened);
                clearTimeout(checkInterval);
            }, 2000);
        } catch (error: any) {
            console.error('Error opening support chat:', error);
            toast.error(t('profile.purchases.contact_manager_error'));
        }
    };

    return {
        showCancelModal,
        cancellationReason,
        showStatusHistoryModal,
        fullStatusHistory,
        loadingStatusHistory,
        openCancelModal,
        closeCancelModal,
        openStatusHistoryModal,
        closeStatusHistoryModal,
        cancelProcessingOrder,
        contactManagerAboutOrder
    };
}
