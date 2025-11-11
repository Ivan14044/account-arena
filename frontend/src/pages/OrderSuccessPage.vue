<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 dark:from-gray-900 dark:to-gray-800 py-12 px-4">
        <div class="max-w-4xl mx-auto">
            <!-- Успешное сообщение -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-green-100 dark:bg-green-900/30 rounded-full mb-4">
                    <svg class="w-10 h-10 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                    {{ $t('order_success.title') || 'Заказ оформлен успешно!' }}
                </h1>
                <p class="text-gray-600 dark:text-gray-300 mb-4">
                    {{ $t('order_success.subtitle') || 'Спасибо за покупку! Ваши товары доступны для скачивания ниже.' }}
                </p>
                <div v-if="!loading && recentPurchases.length > 0" class="inline-flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                    Куплено товаров: {{ recentPurchases.length }}
                </div>
            </div>

            <!-- Загрузка -->
            <div v-if="loading" class="flex justify-center py-12">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            </div>

            <!-- Список купленных товаров -->
            <div v-else-if="recentPurchases.length > 0" class="space-y-4">
                <div 
                    v-for="purchase in recentPurchases" 
                    :key="purchase.id"
                    class="purchase-card"
                >
                    <!-- Заголовок товара -->
                    <div class="flex items-center gap-4 mb-4">
                        <img 
                            v-if="purchase.product.image_url" 
                            :src="purchase.product.image_url" 
                            :alt="purchase.product.title"
                            class="w-16 h-16 rounded-lg object-contain"
                        />
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                {{ purchase.product.title }}
                            </h3>
                            <p class="text-sm font-medium text-blue-600 dark:text-blue-400">
                                Заказ: #{{ purchase.order_number || purchase.id }}
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Количество: {{ purchase.quantity }} шт. × ${{ purchase.price }} = ${{ purchase.total_amount }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                Куплено: {{ formatDate(purchase.purchased_at) }}
                            </p>
                        </div>
                    </div>

                    <!-- Данные аккаунтов -->
                    <div v-if="purchase.account_data && purchase.account_data.length > 0" class="space-y-3">
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <h4 class="font-semibold text-gray-900 dark:text-white">
                                    Ваши данные для доступа:
                                </h4>
                            </div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                {{ isPurchaseExpanded(purchase.id) ? purchase.account_data.length : Math.min(5, purchase.account_data.length) }} 
                                из {{ purchase.account_data.length }} аккаунтов
                            </span>
                        </div>
                        
                        <div 
                            v-for="(accountItem, index) in getVisibleAccounts(purchase)" 
                            :key="index"
                            class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 border border-gray-200 dark:border-gray-600"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1">
                                    <div class="font-mono text-sm text-gray-900 dark:text-white whitespace-pre-wrap break-all">
                                        {{ formatAccountData(accountItem) }}
                                    </div>
                                </div>
                                <button
                                    @click="copyToClipboard(formatAccountData(accountItem))"
                                    class="shrink-0 p-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors duration-200"
                                    title="Копировать"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                </button>
                                <button
                                    @click="downloadSingleAccount(purchase, accountItem, index)"
                                    class="shrink-0 p-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors duration-200"
                                    title="Скачать"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Кнопка "Показать все" если аккаунтов больше 5 -->
                        <button
                            v-if="purchase.account_data.length > 5"
                            @click="toggleExpandPurchase(purchase.id)"
                            class="w-full mt-3 px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg font-medium transition-all duration-200 flex items-center justify-center gap-2"
                        >
                            <svg 
                                class="w-5 h-5 transition-transform"
                                :class="{ 'rotate-180': isPurchaseExpanded(purchase.id) }"
                                fill="none" 
                                stroke="currentColor" 
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                            <span v-if="isPurchaseExpanded(purchase.id)">
                                Скрыть {{ purchase.account_data.length - 5 }} аккаунтов
                            </span>
                            <span v-else>
                                Показать все {{ purchase.account_data.length }} аккаунтов (ещё {{ purchase.account_data.length - 5 }})
                            </span>
                        </button>
                        
                        <!-- Кнопка скачать все -->
                        <button
                            v-if="purchase.account_data.length > 1"
                            @click="downloadAllAccounts(purchase)"
                            class="w-full mt-3 px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-lg font-medium transition-all duration-300 flex items-center justify-center gap-2"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                            Скачать все аккаунты ({{ purchase.account_data.length }} шт.)
                        </button>
                    </div>
                    
                    <!-- Если нет данных -->
                    <div v-else class="text-center py-6 text-gray-500 dark:text-gray-400">
                        <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p>Нет данных для этого товара</p>
                    </div>
                </div>
            </div>

            <!-- Пустое состояние -->
            <div v-else class="text-center py-12">
                <div class="empty-state-card">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                    </svg>
                    <p class="text-gray-600 dark:text-gray-400 text-lg mb-4">
                        Покупки загружаются...
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-500 mb-4">
                        Если покупки не появились, нажмите кнопку ниже
                    </p>
                    
                    <button
                        @click="fetchPurchases"
                        class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-all duration-300 inline-flex items-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Обновить
                    </button>
                </div>
            </div>

            <!-- Кнопки навигации -->
            <div class="mt-8 flex gap-4 justify-center">
                <router-link
                    to="/profile"
                    class="px-6 py-3 bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-lg font-medium hover:shadow-lg transition-all duration-300 border border-gray-200 dark:border-gray-700"
                >
                    Перейти в профиль
                </router-link>
                <router-link
                    to="/"
                    class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-all duration-300"
                >
                    Вернуться на главную
                </router-link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'vue-toastification';
import axios from '@/bootstrap'; // Используем настроенный axios из bootstrap

const router = useRouter();
const toast = useToast();

const purchases = ref([]);
const loading = ref(true);
const expandedPurchases = ref(new Set()); // Отслеживаем раскрытые покупки

// Вычисляемое свойство для покупок (убран фильтр времени)
const recentPurchases = computed(() => {
    // Возвращаем все покупки, отсортированные по дате (новые первые)
    return purchases.value;
});

// Проверяем, раскрыта ли покупка полностью
const isPurchaseExpanded = (purchaseId) => {
    return expandedPurchases.value.has(purchaseId);
};

// Переключаем состояние раскрытия покупки
const toggleExpandPurchase = (purchaseId) => {
    if (expandedPurchases.value.has(purchaseId)) {
        expandedPurchases.value.delete(purchaseId);
    } else {
        expandedPurchases.value.add(purchaseId);
    }
    // Принудительно обновляем реактивность
    expandedPurchases.value = new Set(expandedPurchases.value);
};

// Получаем список аккаунтов для отображения (первые 5 или все)
const getVisibleAccounts = (purchase) => {
    const maxVisible = 5;
    if (isPurchaseExpanded(purchase.id) || purchase.account_data.length <= maxVisible) {
        return purchase.account_data;
    }
    return purchase.account_data.slice(0, maxVisible);
};

// Загрузка покупок при монтировании
onMounted(async () => {
    await fetchPurchases();
    
    // Автоматически перезагружаем через 2 секунды, если покупок нет
    setTimeout(() => {
        if (purchases.value.length === 0) {
            fetchPurchases();
        }
    }, 2000);
});

const fetchPurchases = async () => {
    try {
        loading.value = true;
        
        // ИСПРАВЛЕНИЕ: Используем authStore вместо прямого доступа к localStorage
        const { useAuthStore } = await import('@/stores/auth');
        const authStore = useAuthStore();
        const token = authStore.token;
        
        console.log('🔍 Fetching purchases...', {
            url: '/purchases',
            hasToken: !!token,
            tokenStart: token ? token.substring(0, 20) + '...' : 'нет',
            authStoreUser: authStore.user?.email
        });
        
        if (!token) {
            toast.error('Вы не авторизованы. Войдите в систему.');
            await router.push('/login');
            return;
        }
        
        const response = await axios.get('/purchases', {
            headers: {
                Authorization: `Bearer ${token}`
            }
        });
        
        console.log('✅ Response received:', {
            status: response.status,
            success: response.data.success,
            purchasesCount: response.data.purchases?.length || 0,
            data: response.data
        });
        
        if (response.data.success) {
            purchases.value = response.data.purchases;
            console.log('✅ Purchases set:', purchases.value.length);
        } else {
            console.warn('⚠️ Response success=false');
        }
    } catch (error) {
        console.error('❌ Failed to fetch purchases:', {
            message: error.message,
            response: error.response?.data,
            status: error.response?.status
        });
        toast.error('Ошибка при загрузке покупок: ' + (error.response?.data?.message || error.message));
    } finally {
        loading.value = false;
    }
};

const formatAccountData = (accountItem) => {
    if (typeof accountItem === 'string') {
        return accountItem;
    }
    if (typeof accountItem === 'object') {
        return Object.entries(accountItem)
            .map(([key, value]) => `${key}: ${value}`)
            .join('\n');
    }
    return String(accountItem);
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleString('ru-RU');
};

const copyToClipboard = async (text) => {
    try {
        await navigator.clipboard.writeText(text);
        toast.success('Скопировано в буфер обмена!');
    } catch (error) {
        console.error('Failed to copy:', error);
        toast.error('Ошибка при копировании');
    }
};

const downloadAsText = (content, filename) => {
    const blob = new Blob([content], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
    toast.success('Файл скачан!');
};

const downloadSingleAccount = (purchase, accountItem, index) => {
    const orderNumber = purchase.order_number || `ID${purchase.id}`;
    const header = `======================================
ЗАКАЗ: ${orderNumber}
ТОВАР: ${purchase.product.title || 'Unknown'}
ДАТА: ${formatDate(purchase.purchased_at)}
АККАУНТ: ${index + 1}
======================================\n\n`;
    
    const content = formatAccountData(accountItem);
    const filename = `ORDER_${orderNumber}_${index + 1}.txt`;
    downloadAsText(header + content, filename);
};

const downloadAllAccounts = (purchase) => {
    const orderNumber = purchase.order_number || `ID${purchase.id}`;
    
    // Заголовок с информацией о заказе
    const header = `======================================
ЗАКАЗ: ${orderNumber}
ТОВАР: ${purchase.product.title || 'Unknown'}
ДАТА: ${formatDate(purchase.purchased_at)}
КОЛИЧЕСТВО: ${purchase.account_data.length} шт.
======================================\n\n`;
    
    const allData = purchase.account_data
        .map((item, index) => `=== Аккаунт ${index + 1} ===\n${formatAccountData(item)}`)
        .join('\n\n');
    
    downloadAsText(header + allData, `ORDER_${orderNumber}_${purchase.product.title}.txt`);
};

</script>

<style scoped>
/* Purchase Card Styles - Улучшенный контраст */
.purchase-card {
    background: #f9fafb;
    border: 1px solid #d1d5db;
    border-radius: 16px;
    padding: 24px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.dark .purchase-card {
    background: #1f2937;
    border-color: #4b5563;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.purchase-card:hover {
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    transform: translateY(-2px);
    border-color: #9ca3af;
}

.dark .purchase-card:hover {
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
    border-color: #6b7280;
}

/* Empty State Card Styles */
.empty-state-card {
    background: #f9fafb;
    border: 1px solid #d1d5db;
    border-radius: 16px;
    padding: 32px;
    max-width: 28rem;
    margin: 0 auto;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.dark .empty-state-card {
    background: #1f2937;
    border-color: #4b5563;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}
</style>

