<template>
    <div class="w-full lg:w-3/4 xl:w-2/3 mx-auto px-4 py-16 sm:px-6 lg:px-8">
        <!-- Header Section -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-light text-gray-900 dark:text-white mt-3">
                {{ $t('profile.title') }}
            </h1>
        </div>

        <!-- Balance Card -->
        <div class="balance-card mb-8">
            <div class="balance-content">
                <div class="balance-icon">
                    <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <div class="balance-info">
                    <p class="balance-label">{{ $t('profile.balance') }}</p>
                    <p class="balance-amount">{{ formatBalance(authStore.user?.balance || 0) }}</p>
                </div>
            </div>
        </div>

        <form class="space-y-6" @submit.prevent="handleSubmit">
            <div class="space-y-2">
                <label class="text-sm text-gray-700 dark:text-gray-300" for="name">{{
                    $t('profile.name')
                }}</label>
                <div class="relative">
                    <User
                        class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-400 w-5 h-5"
                    />
                    <input
                        id="name"
                        v-model="name"
                        type="text"
                        class="w-full pl-10 pr-4 py-2 border rounded-lg dark:!border-gray-500 dark:text-gray-300"
                        :placeholder="$t('profile.namePlaceholder')"
                        required
                    />
                </div>
                <p v-if="errors.name" class="text-red-500 text-sm">
                    {{ errors.name[0] }}
                </p>
            </div>

            <div class="space-y-2">
                <label class="text-sm text-gray-700 dark:text-gray-300" for="email">{{
                    $t('profile.email')
                }}</label>
                <div class="relative">
                    <Mail
                        class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-400 w-5 h-5"
                    />
                    <input
                        id="email"
                        v-model="email"
                        type="email"
                        class="w-full pl-10 pr-4 py-2 border dark:!border-gray-500 rounded-lg dark:text-gray-300"
                        :placeholder="$t('profile.emailPlaceholder')"
                        required
                    />
                </div>
                <p v-if="errors.email" class="text-red-500 text-sm">
                    {{ errors.email[0] }}
                </p>
            </div>

            <div class="space-y-2">
                <label class="text-sm text-gray-700 dark:text-gray-300" for="password">{{
                    $t('profile.password')
                }}</label>
                <div class="relative">
                    <Lock
                        class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-400 w-5 h-5"
                    />
                    <input
                        id="password"
                        v-model="password"
                        type="password"
                        autocomplete="off"
                        class="w-full pl-10 pr-4 py-2 border dark:!border-gray-500 rounded-lg dark:text-gray-300"
                        :placeholder="$t('profile.passwordPlaceholder')"
                    />
                </div>
                <p v-if="errors.password" class="text-red-500 text-sm">
                    {{ errors.password[0] }}
                </p>
            </div>

            <div class="space-y-2">
                <label
                    class="text-sm text-gray-700 dark:text-gray-300"
                    for="password_confirmation"
                    >{{ $t('profile.confirmPassword') }}</label
                >
                <div class="relative">
                    <Lock
                        class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-400 w-5 h-5"
                    />
                    <input
                        id="password_confirmation"
                        v-model="password_confirmation"
                        type="password"
                        autocomplete="off"
                        class="w-full pl-10 pr-4 py-2 border dark:!border-gray-500 rounded-lg dark:text-gray-300"
                        :placeholder="$t('profile.confirmPasswordPlaceholder')"
                    />
                </div>
            </div>

            <button
                type="submit"
                class="w-full text-white py-2 rounded-lg bg-blue-500 dark:bg-blue-900 hover:bg-blue-600 dark:hover:bg-blue-800"
            >
                {{ $t('profile.saveButton') }}
            </button>
        </form>

        <!-- Purchases Section -->
        <div class="purchases-section mt-12">
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-6">
                {{ $t('profile.purchases.title') }}
            </h2>
            
            <div v-if="loadingPurchases" class="text-center py-8">
                <p class="text-gray-500 dark:text-gray-400">{{ $t('profile.purchases.loading') }}</p>
            </div>

            <div v-else-if="purchases.length === 0" class="text-center py-8">
                <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <p class="text-gray-500 dark:text-gray-400 text-lg">{{ $t('profile.purchases.no_purchases') }}</p>
            </div>

            <div v-else class="space-y-4">
                <div
                    v-for="purchase in purchases"
                    :key="purchase.id"
                    class="purchase-card"
                >
                    <div class="purchase-header">
                        <div class="purchase-service">
                            <span class="purchase-id">#{{ purchase.id }}</span>
                            <span v-if="purchase.service_name" class="purchase-service-name">{{ purchase.service_name }}</span>
                            <span v-else class="purchase-service-name text-gray-400">{{ $t('profile.purchases.no_service') }}</span>
                        </div>
                        <div class="purchase-amount">
                            {{ formatAmount(purchase.amount, purchase.currency) }}
                        </div>
                    </div>
                    <div class="purchase-footer">
                        <span class="purchase-method">
                            {{ formatPaymentMethod(purchase.payment_method) }}
                        </span>
                        <span class="purchase-date">
                            {{ formatDate(purchase.created_at) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Lock, Mail, User } from 'lucide-vue-next';
import { useAuthStore } from '../../stores/auth';
import { useToast } from 'vue-toastification';
import { useI18n } from 'vue-i18n';
import axios from '@/bootstrap';

const toast = useToast();
const authStore = useAuthStore();
const email = ref(authStore.user.email ?? '');
const name = ref(authStore.user.name ?? '');
const password = ref('');
const password_confirmation = ref('');
type FormErrors = Record<string, string[]>;
const errors = ref<FormErrors>({});
const { t } = useI18n();

// Purchases
const purchases = ref<any[]>([]);
const loadingPurchases = ref(false);

const formatBalance = (balance: number) => {
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2,
    }).format(balance);
};

const formatAmount = (amount: number, currency: string = 'USD') => {
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: currency || 'USD',
        minimumFractionDigits: 2,
    }).format(amount);
};

const formatPaymentMethod = (method: string) => {
    const methods: Record<string, string> = {
        'credit_card': t('profile.purchases.methods.card'),
        'crypto': t('profile.purchases.methods.crypto'),
        'free': t('profile.purchases.methods.free'),
        'admin_bypass': t('profile.purchases.methods.admin'),
    };
    return methods[method] || method;
};

const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('ru-RU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
};

const fetchPurchases = async () => {
    loadingPurchases.value = true;
    try {
        const { data } = await axios.get('/transactions');
        purchases.value = Array.isArray(data) ? data : [];
    } catch (error) {
        console.error('Error fetching purchases:', error);
        purchases.value = [];
    } finally {
        loadingPurchases.value = false;
    }
};

const handleSubmit = async () => {
    const payload: any = {
        name: name.value,
        email: email.value,
        password: password.value,
        password_confirmation: password_confirmation.value,
    };

    const success = await authStore.update(payload);
    errors.value = authStore.errors;

    if (success) {
        toast.success(t('profile.success'));
        password.value = '';
        password_confirmation.value = '';
    }
};

onMounted(() => {
    fetchPurchases();
});
</script>

<style scoped>
.balance-card {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.15) 0%, rgba(118, 75, 162, 0.15) 100%);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(102, 126, 234, 0.2);
    border-radius: 20px;
    padding: 32px;
    box-shadow: 0 8px 30px rgba(108, 92, 231, 0.15);
}

.dark .balance-card {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.2) 0%, rgba(118, 75, 162, 0.2) 100%);
    border-color: rgba(102, 126, 234, 0.3);
}

.balance-content {
    display: flex;
    align-items: center;
    gap: 20px;
}

.balance-icon {
    background: rgba(102, 126, 234, 0.2);
    border-radius: 16px;
    padding: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.dark .balance-icon {
    background: rgba(102, 126, 234, 0.3);
}

.balance-info {
    flex: 1;
}

.balance-label {
    font-size: 14px;
    font-weight: 500;
    color: #6b7280;
    margin-bottom: 4px;
    font-family: 'SFT Schrifted Sans', sans-serif;
}

.dark .balance-label {
    color: #9ca3af;
}

.balance-amount {
    font-size: 32px;
    font-weight: 700;
    color: #667eea;
    font-family: 'SFT Schrifted Sans', sans-serif;
}

.dark .balance-amount {
    color: #a29bfe;
}

.purchases-section {
    margin-top: 48px;
}

.purchase-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    padding: 20px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.dark .purchase-card {
    background: #1f2937;
    border-color: #374151;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.purchase-card:hover {
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.dark .purchase-card:hover {
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3);
}

.purchase-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.purchase-service {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.purchase-id {
    font-size: 12px;
    color: #9ca3af;
    font-weight: 500;
}

.purchase-service-name {
    font-size: 16px;
    font-weight: 600;
    color: #1f2937;
}

.dark .purchase-service-name {
    color: #ffffff;
}

.purchase-amount {
    font-size: 20px;
    font-weight: 700;
    color: #6c5ce7;
}

.dark .purchase-amount {
    color: #a29bfe;
}

.purchase-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 12px;
    border-top: 1px solid #f3f4f6;
}

.dark .purchase-footer {
    border-color: #374151;
}

.purchase-method {
    font-size: 13px;
    color: #6b7280;
    font-weight: 500;
}

.dark .purchase-method {
    color: #9ca3af;
}

.purchase-date {
    font-size: 13px;
    color: #9ca3af;
}

.dark .purchase-date {
    color: #6b7280;
}

@media (max-width: 640px) {
    .balance-card {
        padding: 24px;
    }
    
    .balance-amount {
        font-size: 28px;
    }
    
    .purchase-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .purchase-amount {
        font-size: 18px;
    }
}
</style>
