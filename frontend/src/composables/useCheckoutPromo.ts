import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useAlert } from '@/utils/alert';
import { useProductCartStore } from '@/stores/productCart';
import { usePromoStore } from '@/stores/promo';

/**
 * Promo-code input + apply/clear handlers (extracted from CheckoutPage.vue).
 *
 * Owns the `inputCode` ref so consumers share one source of truth (the checkout payment
 * flow reads/clears the same ref). `onApply` is debounced 500ms and surfaces promo errors /
 * the empty-cart-discount notice via alerts, mirroring the original inline logic.
 */
export function useCheckoutPromo() {
    const { t } = useI18n();
    const { showAlert } = useAlert();
    const productCartStore = useProductCartStore();
    const promo = usePromoStore();

    const inputCode = ref('');
    let applyTimer: number | null = null;

    const isApplied = computed(
        () => !!promo.code && !!promo.result && !promo.error && promo.code === inputCode.value
    );

    async function onApply() {
        if (applyTimer) {
            clearTimeout(applyTimer as unknown as number);
        }
        applyTimer = window.setTimeout(async () => {
            await promo.apply(inputCode.value);
            if (promo.error) {
                await showAlert({
                    title: t('alert.title'),
                    text: promo.error,
                    icon: 'error',
                    confirmText: t('alert.ok')
                });
                return;
            }
            if (promo.result?.type === 'free_access') {
                // Free access for products might need different handling
                // This functionality may need to be implemented if needed
            } else if (promo.result?.type === 'discount' && productCartStore.items.length === 0) {
                await showAlert({
                    title: t('alert.title'),
                    text: t('checkout.promocode_discount_empty_cart'),
                    icon: 'success',
                    confirmText: t('alert.ok')
                });
            }
        }, 500);
    }

    function onClear() {
        promo.clear();
        inputCode.value = '';
    }

    function onPrimaryPromoClick() {
        if (isApplied.value) {
            onClear();
        } else {
            onApply();
        }
    }

    return {
        inputCode,
        isApplied,
        onApply,
        onClear,
        onPrimaryPromoClick
    };
}
