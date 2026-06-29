import { useI18n } from 'vue-i18n';
import { useToast } from 'vue-toastification';
import { useProductTitle } from '@/composables/useProductTitle';

interface AccountDownloadDeps {
    /** Date formatter from the host page, so download headers match the on-screen format. */
    formatDate: (dateString: string) => string;
}

/**
 * Delivered-account rendering, clipboard copy and .txt download (extracted from ProfilePage.vue).
 * Self-contained I/O helpers — no reactive page state involved.
 */
export function useAccountDownload({ formatDate }: AccountDownloadDeps) {
    const { t } = useI18n();
    const toast = useToast();
    const { getProductTitle } = useProductTitle();

    const formatAccountData = (accountItem: any): string => {
        if (typeof accountItem === 'string') {
            return accountItem;
        }
        if (typeof accountItem === 'object' && accountItem !== null) {
            return Object.entries(accountItem)
                .map(([key, value]) => `${key}: ${value}`)
                .join('\n');
        }
        return String(accountItem);
    };

    const copyToClipboard = async (text: string) => {
        try {
            await navigator.clipboard.writeText(text);
            toast.success(t('profile.purchases.copy_success'));
        } catch (error) {
            console.error('Failed to copy:', error);
            toast.error(t('profile.purchases.copy_error'));
        }
    };

    const downloadAsText = (content: string, filename: string) => {
        const blob = new Blob([content], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
        toast.success(t('profile.purchases.download_success'));
    };

    const downloadSingleAccount = (purchase: any, accountItem: any, index: number) => {
        const orderNumber = purchase.order_number || `ID${purchase.id}`;
        const productTitle = purchase.service_name
            ? getProductTitle(purchase.service_name)
            : t('profile.purchases.unknown');
        const header = `======================================
${t('profile.purchases.download_labels.order')}: ${orderNumber}
${t('profile.purchases.download_labels.product')}: ${productTitle}
${t('profile.purchases.download_labels.date')}: ${formatDate(purchase.created_at)}
${t('profile.purchases.download_labels.account')}: ${index + 1}
======================================\n\n`;

        const content = formatAccountData(accountItem);
        const filename = `ORDER_${orderNumber}_${index + 1}.txt`;
        downloadAsText(header + content, filename);
    };

    const downloadAllAccounts = (purchase: any) => {
        const orderNumber = purchase.order_number || `ID${purchase.id}`;
        const productTitle = purchase.service_name
            ? getProductTitle(purchase.service_name)
            : t('profile.purchases.unknown');

        // Заголовок с информацией о заказе
        const header = `======================================
${t('profile.purchases.download_labels.order')}: ${orderNumber}
${t('profile.purchases.download_labels.product')}: ${productTitle}
${t('profile.purchases.download_labels.date')}: ${formatDate(purchase.created_at)}
${t('profile.purchases.download_labels.quantity')}: ${purchase.account_data.length} ${t('profile.purchases.quantity_unit')}
======================================\n\n`;

        const allData = purchase.account_data
            .map(
                (item: any, index: number) =>
                    `=== ${t('profile.purchases.account')} ${index + 1} ===\n${formatAccountData(item)}`
            )
            .join('\n\n');

        const filename = `ORDER_${orderNumber}_${productTitle || t('profile.purchases.purchase')}.txt`;
        downloadAsText(header + allData, filename);
    };

    return {
        formatAccountData,
        copyToClipboard,
        downloadSingleAccount,
        downloadAllAccounts
    };
}
