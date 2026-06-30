import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { POSITION, useToast } from 'vue-toastification';
import axios from '@/bootstrap';
import { useAuthStore } from '@/stores/auth';

/**
 * Voucher activation (extracted from ProfilePage.vue).
 * Activates a code, refreshes the user balance, and surfaces success/error inline + via toast.
 */
export function useVoucher() {
    const { t } = useI18n();
    const toast = useToast();
    const authStore = useAuthStore();

    const voucherCode = ref('');
    const voucherLoading = ref(false);
    const voucherError = ref('');
    const voucherSuccess = ref('');

    const activateVoucher = async () => {
        if (!voucherCode.value.trim()) {
            return;
        }

        voucherLoading.value = true;
        voucherError.value = '';
        voucherSuccess.value = '';

        try {
            const response = await axios.post('/vouchers/activate', {
                code: voucherCode.value.trim().toUpperCase()
            });

            voucherSuccess.value = response.data.message;
            voucherCode.value = '';

            // Обновляем баланс пользователя
            await authStore.fetchUser();

            // Показываем toast уведомление
            toast.success(response.data.message, {
                position: POSITION.TOP_RIGHT,
                timeout: 5000
            });
        } catch (error: any) {
            if (error.response?.data?.errors?.code) {
                voucherError.value = error.response.data.errors.code[0];
            } else if (error.response?.data?.message) {
                voucherError.value = error.response.data.message;
            } else {
                voucherError.value = t('profile.voucher.error');
            }

            // Очищаем ошибку через 5 секунд
            setTimeout(() => {
                voucherError.value = '';
            }, 5000);
        } finally {
            voucherLoading.value = false;
        }
    };

    return {
        voucherCode,
        voucherLoading,
        voucherError,
        voucherSuccess,
        activateVoucher
    };
}
