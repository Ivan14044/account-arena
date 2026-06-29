import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import axios from '@/bootstrap';

/**
 * Purchase-rules agreement gate (extracted from CheckoutPage.vue).
 *
 * Loads the per-locale rules text from the API, exposes the text for the current locale
 * (with ru/en fallback) and the agreement / modal UI state. `loadRules()` must be called
 * by the consumer (e.g. onMounted) — kept explicit to preserve the original timing.
 */
export function usePurchaseRules() {
    const { locale } = useI18n();

    const purchaseRulesEnabled = ref(false);
    const purchaseRulesText = ref<Record<string, string>>({});
    const agreedToRules = ref(false);
    const showRulesModal = ref(false);

    // Rules text for the current locale, falling back to ru → en
    const currentRulesText = computed(() => {
        const currentLocale = locale.value;
        return (
            purchaseRulesText.value[currentLocale] ||
            purchaseRulesText.value.ru ||
            purchaseRulesText.value.en ||
            ''
        );
    });

    const loadRules = async () => {
        try {
            const response = await axios.get('/purchase-rules');

            if (response.data.enabled && response.data.rules) {
                purchaseRulesText.value = response.data.rules || {};

                const currentLocale = locale.value;
                const hasText =
                    purchaseRulesText.value[currentLocale] ||
                    purchaseRulesText.value.ru ||
                    purchaseRulesText.value.en;

                // Enable only if there's non-empty text
                if (hasText && hasText.trim()) {
                    purchaseRulesEnabled.value = true;
                }
            }
        } catch (error) {
            console.error('Error loading purchase rules:', error);
        }
    };

    return {
        purchaseRulesEnabled,
        agreedToRules,
        showRulesModal,
        currentRulesText,
        loadRules
    };
}
