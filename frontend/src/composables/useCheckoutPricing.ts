import { computed } from 'vue';
import { useProductCartStore } from '@/stores/productCart';
import { useAuthStore } from '@/stores/auth';
import { usePromoStore } from '@/stores/promo';

/**
 * Checkout pricing pipeline (extracted from CheckoutPage.vue).
 *
 * A pure reactive chain: cart subtotal → personal discount → promo discount → final total.
 * Personal discount applies first (authenticated users only, respecting expiry), then the
 * promo discount applies to the already-discounted subtotal. No side effects.
 */
export function useCheckoutPricing() {
    const productCartStore = useProductCartStore();
    const authStore = useAuthStore();
    const promo = usePromoStore();

    const subtotalPaid = computed(() => productCartStore.totalAmount);

    // Personal discount (only for authenticated users, ignored once expired)
    const personalDiscountPercent = computed(() => {
        if (!authStore.isAuthenticated || !authStore.user) {
            return 0;
        }

        const discount = authStore.user.personal_discount || 0;
        const expiresAt = authStore.user.personal_discount_expires_at;

        if (discount <= 0) {
            return 0;
        }

        if (expiresAt) {
            const expiryDate = new Date(expiresAt);
            if (new Date() > expiryDate) {
                return 0;
            }
        }

        return Number(discount);
    });

    const personalDiscountAmount = computed(() => {
        if (personalDiscountPercent.value <= 0) {
            return 0;
        }
        return (subtotalPaid.value * personalDiscountPercent.value) / 100;
    });

    // Apply personal discount first, then promo discount on top
    const subtotalAfterPersonalDiscount = computed(() => {
        return Math.max(0, subtotalPaid.value - personalDiscountAmount.value);
    });

    const promoDiscountPercent = computed(() =>
        promo.result?.type === 'discount' ? Number(promo.result.discount_percent || 0) : 0
    );
    const promoDiscountAmount = computed(
        () => (subtotalAfterPersonalDiscount.value * promoDiscountPercent.value) / 100
    );
    const finalTotal = computed(() =>
        Math.max(0, subtotalAfterPersonalDiscount.value - promoDiscountAmount.value)
    );
    const isZeroTotalWithServices = computed(
        () => finalTotal.value === 0 && productCartStore.items.length > 0
    );

    return {
        subtotalPaid,
        personalDiscountPercent,
        personalDiscountAmount,
        subtotalAfterPersonalDiscount,
        promoDiscountPercent,
        promoDiscountAmount,
        finalTotal,
        isZeroTotalWithServices
    };
}
