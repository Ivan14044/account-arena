import { ref } from 'vue';
import axios from '@/bootstrap';
import { useAuthStore } from '@/stores/auth';
import { PurchaseStatus } from '@/constants/purchaseStatus';

/**
 * User purchases data layer (extracted from ProfilePage.vue).
 *
 * Owns the purchases list, loading/expanded state, date/status filters and the
 * processing-time stat. `fetchPurchases` re-applies the current filters server-side
 * and, when any order is still processing, refreshes the average processing-time stat.
 */
export function useUserPurchases() {
    const authStore = useAuthStore();

    const purchases = ref<any[]>([]);
    const filteredPurchases = ref<any[]>([]);
    const loadingPurchases = ref(false);
    const expandedPurchases = ref<Set<number>>(new Set());
    const averageProcessingTime = ref<number | null>(null);

    const filters = ref({
        date_from: '',
        date_to: '',
        status: ''
    });

    // Average processing-time stat for orders still in `processing`
    const fetchProcessingStats = async () => {
        try {
            const token = authStore.token;
            if (!token) {
                return;
            }

            const { data } = await axios.get('/purchases/stats/processing');

            if (
                data.success &&
                data.average_processing_time_hours !== null &&
                data.average_processing_time_hours !== undefined
            ) {
                averageProcessingTime.value = data.average_processing_time_hours;
            } else {
                averageProcessingTime.value = null;
            }
        } catch (error) {
            console.error('Error fetching processing stats:', error);
            averageProcessingTime.value = null;
        }
    };

    const fetchPurchases = async () => {
        loadingPurchases.value = true;
        try {
            const params: any = {};

            if (filters.value.date_from) {
                params.date_from = filters.value.date_from;
            }
            if (filters.value.date_to) {
                params.date_to = filters.value.date_to;
            }
            if (filters.value.status) {
                params.status = filters.value.status;
            }

            const { data } = await axios.get('/purchases', { params });

            // API возвращает { success: true, purchases: [...] }
            if (data.success && Array.isArray(data.purchases)) {
                purchases.value = data.purchases;
                filteredPurchases.value = data.purchases;

                // Загружаем статистику обработки, если есть заказы в processing
                const hasProcessingOrders = purchases.value.some(
                    p => p.status === PurchaseStatus.PROCESSING
                );
                if (hasProcessingOrders) {
                    await fetchProcessingStats();
                }
            } else {
                purchases.value = [];
                filteredPurchases.value = [];
            }
        } catch (error) {
            console.error('Error fetching purchases:', error);
            purchases.value = [];
            filteredPurchases.value = [];
        } finally {
            loadingPurchases.value = false;
        }
    };

    const applyFilters = () => {
        fetchPurchases();
    };

    const resetFilters = () => {
        filters.value = {
            date_from: '',
            date_to: '',
            status: ''
        };
        fetchPurchases();
    };

    const togglePurchaseDetails = (purchaseId: number) => {
        if (expandedPurchases.value.has(purchaseId)) {
            expandedPurchases.value.delete(purchaseId);
        } else {
            expandedPurchases.value.add(purchaseId);
        }
        // Принудительно обновляем реактивность
        expandedPurchases.value = new Set(expandedPurchases.value);
    };

    return {
        purchases,
        filteredPurchases,
        loadingPurchases,
        expandedPurchases,
        averageProcessingTime,
        filters,
        fetchPurchases,
        applyFilters,
        resetFilters,
        togglePurchaseDetails
    };
}
