import type { ComputedRef, Ref } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useToast } from 'vue-toastification';
import axios from '@/bootstrap';
import { useProductCartStore } from '@/stores/productCart';
import { useAuthStore } from '@/stores/auth';
import { useLoadingStore } from '@/stores/loading';
import { usePromoStore } from '@/stores/promo';

interface CheckoutPaymentDeps {
    guestEmail: Ref<string>;
    inputCode: Ref<string>;
    finalTotal: ComputedRef<number>;
}

/**
 * Checkout payment flows (extracted from CheckoutPage.vue).
 *
 * Mono and Cryptomus share one redirect-payment implementation (they differed only by
 * provider slug and a guest/user endpoint prefix). Balance and free flows are kept distinct.
 * Behaviour is preserved from the original inline handlers.
 */
export function useCheckoutPayment({ guestEmail, inputCode, finalTotal }: CheckoutPaymentDeps) {
    const router = useRouter();
    const { t } = useI18n();
    const toast = useToast();
    const productCartStore = useProductCartStore();
    const authStore = useAuthStore();
    const loadingStore = useLoadingStore();
    const promo = usePromoStore();

    const productsPayload = () =>
        productCartStore.items.map(item => ({ id: item.id, quantity: item.quantity }));

    const withPromo = <T extends object>(payload: T) => ({
        ...payload,
        ...(promo.code ? { promocode: promo.code } : {})
    });

    // Mono / Cryptomus: build payload (guest vs user), create payment, redirect to gateway.
    const createRedirectPayment = async (provider: 'mono' | 'cryptomus', label: string) => {
        try {
            const isGuest = !authStore.isAuthenticated;

            let endpoint: string;
            let payload: Record<string, unknown>;

            if (isGuest) {
                if (!guestEmail.value || !guestEmail.value.trim()) {
                    toast.error(t('checkout.guest_email_required_short'));
                    return;
                }
                endpoint = `/guest/${provider}/create-payment`;
                payload = withPromo({
                    guest_email: guestEmail.value.trim(),
                    products: productsPayload()
                });
            } else {
                endpoint = `/${provider}/create-payment`;
                payload = withPromo({ products: productsPayload() });
            }

            const { data } = await axios.post(endpoint, payload);
            if (data.url) {
                // Небольшая задержка для отображения сообщения перед перенаправлением
                await new Promise(resolve => setTimeout(resolve, 500));
                window.location.href = data.url;
            } else {
                toast.error(t('checkout.payment_error'));
            }
        } catch (error) {
            console.error(`${label} payment error:`, error);
            const errMsg =
                (error && (error as any).response?.data?.message) || t('checkout.payment_error');
            toast.error(errMsg as string);
        }
    };

    const processMonoPayment = () => createRedirectPayment('mono', 'Mono');
    const processCryptoPayment = () => createRedirectPayment('cryptomus', 'Crypto');

    const buyFree = async () => {
        try {
            // For products, free purchases are handled differently
            // This might need to be implemented if free products are needed
            await authStore.fetchUser();
            promo.clear();
            inputCode.value = '';
            toast.success(t('checkout.free_success'));
            await router.push('/');
        } catch (error) {
            console.error('Free order error:', error);
            const errMsg =
                (error && (error as any).response?.data?.message) || t('checkout.payment_error');
            toast.error(errMsg as string);
        }
    };

    const processBalancePayment = async () => {
        // Показываем сообщение о подготовке заказа
        loadingStore.start(t('checkout.preparing_product'));
        try {
            // Проверяем достаточно ли средств на балансе
            if (authStore.user && authStore.user.balance < finalTotal.value) {
                toast.error(t('checkout.insufficient_balance'));
                loadingStore.stop();
                return;
            }

            // Submit products purchase with balance
            const payload = {
                products: productsPayload(),
                payment_method: 'balance',
                ...(promo.code ? { promocode: promo.code } : {})
            };

            await axios.post('/cart', payload);

            productCartStore.clearCart();
            await authStore.fetchUser();
            promo.clear();
            inputCode.value = '';
            toast.success(t('checkout.balance_success'));

            // НЕ скрываем прелоадер! Переходим с видимым сообщением "Подготовка товара к выдаче"
            // Прелоадер будет скрыт на OrderSuccessPage после загрузки данных
            await router.push('/order-success');
        } catch (error) {
            console.error('Balance payment error:', error);
            const errMsg =
                (error && (error as any).response?.data?.message) ||
                t('checkout.balance_payment_error');
            toast.error(errMsg as string);
            // Только при ошибке скрываем прелоадер
            loadingStore.stop();
        }
        // УБРАН finally блок - прелоадер остается видимым до выдачи товара
    };

    return {
        processMonoPayment,
        processCryptoPayment,
        buyFree,
        processBalancePayment
    };
}
