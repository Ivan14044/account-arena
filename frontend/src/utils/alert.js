import { useI18n } from 'vue-i18n';
import Swal from 'sweetalert2';

export function useAlert() {
    const { t } = useI18n();

    const baseOptions = {
        customClass: {
            popup: 'bg-white dark:!bg-gray-800 rounded-lg shadow-lg',
            title: 'text-xl font-semibold text-gray-800 dark:text-white',
            htmlContainer: 'text-gray-600 dark:text-gray-300',
            confirmButton:
                'bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg mr-2 transition',
            cancelButton:
                'bg-gray-300 hover:bg-gray-400 text-gray-800 hover:!text-gray-900 px-4 py-2 rounded-lg transition'
        },
        scrollbarPadding: false,
        buttonsStyling: false
    };

    const showAlert = ({ title, text, icon = 'info', confirmText } = {}) => {
        return Swal.fire({
            ...baseOptions,
            title: title || t('alert.title'),
            text: text || t('alert.text'),
            icon,
            confirmButtonText: confirmText || t('alert.ok')
        });
    };

    const showConfirm = ({ title, text, icon = 'warning', confirmText, cancelText } = {}) => {
        return Swal.fire({
            ...baseOptions,
            title: title || t('confirm.title'),
            text: text || t('confirm.text'),
            icon,
            showCancelButton: true,
            confirmButtonText: confirmText || t('confirm.yes'),
            cancelButtonText: cancelText || t('confirm.cancel')
        });
    };

    return { showAlert, showConfirm };
}
