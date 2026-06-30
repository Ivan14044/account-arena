import { useI18n } from 'vue-i18n';
import { formatPrice } from '@/utils/money';
import { PurchaseStatus, purchaseStatusClass } from '@/constants/purchaseStatus';

/**
 * Display formatters for ProfilePage (balance/amount/payment-method/status/date/duration).
 * Pure presentation helpers — no reactive state. Extracted verbatim from ProfilePage.vue.
 */
export function useProfileFormatters() {
    const { t } = useI18n();

    const formatBalance = (balance: number | string) => formatPrice(balance, 'USD');

    const formatAmount = (amount: number, currency: string = 'USD') =>
        formatPrice(amount, currency || 'USD');

    const formatPaymentMethod = (method: string) => {
        const methods: Record<string, string> = {
            credit_card: t('profile.purchases.methods.card'),
            crypto: t('profile.purchases.methods.crypto'),
            free: t('profile.purchases.methods.free'),
            admin_bypass: t('profile.purchases.methods.admin'),
            balance: t('profile.purchases.methods.balance'),
            balance_deduction: t('profile.purchases.methods.balance')
        };
        return methods[method] || method;
    };

    const formatStatus = (status: string) => {
        const statuses: Record<string, string> = {
            completed: t('profile.purchases.statuses.completed'),
            pending: t('profile.purchases.statuses.pending'),
            processing: t('profile.purchases.statuses.processing'),
            failed: t('profile.purchases.statuses.failed'),
            cancelled: t('profile.purchases.statuses.cancelled'),
            refunded: t('profile.purchases.statuses.refunded')
        };
        return statuses[status] || status;
    };

    const getStatusClass = (status: string) => purchaseStatusClass(status);

    // Определение текущего этапа прогресс-бара
    const getProgressStage = (purchase: any): number => {
        if (purchase.status === PurchaseStatus.COMPLETED) return 3;
        if (purchase.status === PurchaseStatus.PROCESSING) return 2;
        return 1;
    };

    const formatDate = (dateString: string) => {
        const date = new Date(dateString);
        return new Intl.DateTimeFormat('ru-RU', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        }).format(date);
    };

    // Расчет времени обработки заказа
    const getProcessingDuration = (purchase: any) => {
        const start = new Date(purchase.created_at);
        const now = new Date();
        const diffMs = now.getTime() - start.getTime();
        const hours = Math.floor(diffMs / (1000 * 60 * 60));
        const minutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));

        if (hours >= 24) {
            const days = Math.floor(hours / 24);
            const remainingHours = hours % 24;
            return t('profile.purchases.processing_duration_days', {
                days,
                hours: remainingHours
            });
        }
        return t('profile.purchases.processing_duration_hours', { hours, minutes });
    };

    return {
        formatBalance,
        formatAmount,
        formatPaymentMethod,
        formatStatus,
        getStatusClass,
        getProgressStage,
        formatDate,
        getProcessingDuration
    };
}
