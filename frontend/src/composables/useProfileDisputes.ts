import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useToast } from 'vue-toastification';
import axios from '@/bootstrap';
import { PurchaseStatus } from '@/constants/purchaseStatus';

interface ProfileDisputesDeps {
    /** Refresh the purchases list after a dispute is created (lives in useUserPurchases). */
    refreshPurchases: () => Promise<void>;
}

/**
 * Disputes (claims) feature for ProfilePage: list, create-modal form, file/link screenshot,
 * submit and status presentation. Extracted verbatim from ProfilePage.vue.
 */
export function useProfileDisputes({ refreshPurchases }: ProfileDisputesDeps) {
    const { t } = useI18n();
    const toast = useToast();

    const disputes = ref<any[]>([]);
    const loadingDisputes = ref(false);
    const expandedDisputes = ref<Set<number>>(new Set());

    // Dispute modal
    const showDisputeModal = ref(false);
    const selectedPurchase = ref<any>(null);
    const screenshotMethod = ref<'file' | 'link'>('file');
    const screenshotFile = ref<File | null>(null);
    const screenshotPreview = ref<string | null>(null);
    const screenshotLinkError = ref(false);
    const isSubmittingDispute = ref(false);

    const disputeForm = ref({
        reason: '',
        description: '',
        screenshot_link: ''
    });

    const toggleDisputeDetails = (disputeId: number) => {
        if (expandedDisputes.value.has(disputeId)) {
            expandedDisputes.value.delete(disputeId);
        } else {
            expandedDisputes.value.add(disputeId);
        }
        expandedDisputes.value = new Set(expandedDisputes.value);
    };

    const canCreateDispute = (purchase: any): boolean => {
        // Только для completed транзакций (не для processing и других статусов)
        if (purchase.status !== PurchaseStatus.COMPLETED) return false;

        // Проверяем наличие transaction_id
        if (!purchase.transaction_id) return false;

        // Не старше 30 дней
        const daysSince = Math.floor(
            (new Date().getTime() - new Date(purchase.created_at).getTime()) / (1000 * 60 * 60 * 24)
        );
        if (daysSince > 30) return false;

        // Только если есть данные аккаунтов (это покупка товара, а не подписка)
        if (!purchase.account_data || purchase.account_data.length === 0) return false;

        // Проверяем, нет ли уже претензии на эту покупку
        if (purchase.has_dispute) return false;

        return true;
    };

    const openDisputeModal = (purchase: any) => {
        selectedPurchase.value = purchase;
        showDisputeModal.value = true;
    };

    const getDisputeStatusText = (status: string): string => {
        const statuses: Record<string, string> = {
            new: t('profile.purchases.disputes.status.new'),
            in_review: t('profile.purchases.disputes.status.in_review'),
            resolved: t('profile.purchases.disputes.status.resolved'),
            rejected: t('profile.purchases.disputes.status.rejected')
        };
        return statuses[status] || status;
    };

    const getDisputeStatusClass = (status: string): string => {
        const classes: Record<string, string> = {
            new: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
            in_review: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
            resolved: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
            rejected: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
        };
        return classes[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400';
    };

    const closeDisputeModal = () => {
        showDisputeModal.value = false;
        selectedPurchase.value = null;
        disputeForm.value = {
            reason: '',
            description: '',
            screenshot_link: ''
        };
        screenshotMethod.value = 'file';
        screenshotFile.value = null;
        screenshotPreview.value = null;
        screenshotLinkError.value = false;
    };

    const handleFileUpload = (event: Event) => {
        const target = event.target as HTMLInputElement;
        const file = target.files?.[0];

        if (file) {
            // Проверка размера (5MB)
            if (file.size > 5 * 1024 * 1024) {
                toast.error(t('profile.purchases.disputes.file_too_large'));
                return;
            }

            // Проверка типа
            const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                toast.error(t('profile.purchases.disputes.unsupported_format'));
                return;
            }

            screenshotFile.value = file;

            // Создать preview
            const reader = new FileReader();
            reader.onload = e => {
                screenshotPreview.value = e.target?.result as string;
            };
            reader.readAsDataURL(file);
        }
    };

    const submitDispute = async () => {
        if (!selectedPurchase.value) return;

        // Проверка наличия transaction_id
        if (!selectedPurchase.value.transaction_id) {
            toast.error(t('profile.purchases.disputes.transaction_not_found'));
            return;
        }

        // Проверка наличия скриншота
        if (screenshotMethod.value === 'file' && !screenshotFile.value) {
            toast.error(t('profile.purchases.disputes.please_attach_screenshot'));
            return;
        }

        if (screenshotMethod.value === 'link' && !disputeForm.value.screenshot_link) {
            toast.error(t('profile.purchases.disputes.please_provide_link'));
            return;
        }

        isSubmittingDispute.value = true;

        try {
            // ИСПРАВЛЕНО: Используем transaction_id вместо purchase.id
            const formData = new FormData();
            formData.append('transaction_id', selectedPurchase.value.transaction_id.toString());
            formData.append('reason', disputeForm.value.reason);
            formData.append('description', disputeForm.value.description);

            if (screenshotMethod.value === 'file' && screenshotFile.value) {
                formData.append('screenshot_file', screenshotFile.value);
            } else if (screenshotMethod.value === 'link') {
                formData.append('screenshot_link', disputeForm.value.screenshot_link);
            }

            const response = await axios.post('/disputes', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            });

            if (response.data.success) {
                toast.success(t('profile.purchases.disputes.success'));
                closeDisputeModal();
                // Обновляем список покупок и претензий
                await refreshPurchases();
                await fetchDisputes();
            }
        } catch (error: any) {
            const message = error.response?.data?.message || t('profile.purchases.disputes.error');
            toast.error(message);
        } finally {
            isSubmittingDispute.value = false;
        }
    };

    const fetchDisputes = async () => {
        loadingDisputes.value = true;
        try {
            const { data } = await axios.get('/disputes');

            if (data.disputes && Array.isArray(data.disputes.data)) {
                disputes.value = data.disputes.data;
            } else if (Array.isArray(data.disputes)) {
                disputes.value = data.disputes;
            } else {
                disputes.value = [];
            }
        } catch (error) {
            console.error('Error fetching disputes:', error);
            disputes.value = [];
        } finally {
            loadingDisputes.value = false;
        }
    };

    const getDecisionColor = (decision: string): string => {
        const colors: Record<string, string> = {
            refund: 'text-green-600 dark:text-green-400',
            replacement: 'text-blue-600 dark:text-blue-400',
            rejected: 'text-red-600 dark:text-red-400'
        };
        return colors[decision] || 'text-gray-600 dark:text-gray-400';
    };

    return {
        disputes,
        loadingDisputes,
        expandedDisputes,
        showDisputeModal,
        selectedPurchase,
        screenshotMethod,
        screenshotPreview,
        screenshotLinkError,
        isSubmittingDispute,
        disputeForm,
        toggleDisputeDetails,
        canCreateDispute,
        openDisputeModal,
        getDisputeStatusText,
        getDisputeStatusClass,
        closeDisputeModal,
        handleFileUpload,
        submitDispute,
        fetchDisputes,
        getDecisionColor
    };
}
