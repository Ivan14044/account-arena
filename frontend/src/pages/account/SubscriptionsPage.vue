<template>
    <div class="w-full lg:w-1/2 mx-auto min-h-[calc(100vh-388px)] px-4 py-16 sm:px-6 lg:px-8">
        <!-- Header Section -->
        <div class="text-center mb-6">
            <h1 class="text-4xl font-light text-gray-900 dark:text-white mt-3">
                {{ $t('subscriptions.title') }}
            </h1>

            <div class="flex justify-center mt-6 space-x-4">
                <button
                    :class="[
                        'px-4 py-2 rounded-lg font-medium',
                        activeTab === 'active'
                            ? 'bg-blue-500 dark:bg-blue-900 text-white cursor-default'
                            : 'bg-gray-300 dark:bg-gray-400 dark:text-gray-800 text-gray-800 hover:!bg-gray-200'
                    ]"
                    @click="activeTab = 'active'"
                >
                    {{ $t('subscriptions.active_tab') }}
                </button>
                <button
                    :class="[
                        'px-4 py-2 rounded-lg font-medium',
                        activeTab === 'inactive'
                            ? 'bg-blue-500 dark:bg-blue-900 text-white cursor-default'
                            : 'bg-gray-300 dark:bg-gray-400 dark:text-gray-800 text-gray-800 hover:!bg-gray-200'
                    ]"
                    @click="activeTab = 'inactive'"
                >
                    {{ $t('subscriptions.inactive_tab') }}
                </button>
            </div>
        </div>

        <div v-if="authStore.user?.subscriptions?.length" class="space-y-6">
            <div
                v-for="subscription in filteredSubscriptions"
                :key="subscription.id"
                class="space-y-2 mb-2"
            >
                <div class="d-flex align-center gap-3 flex-wrap service-item">
                    <div class="flex-1 min-w-[200px]">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3
                                    class="text-xl font-medium text-gray-900 dark:!text-white"
                                    :class="{
                                        'mb-1': getServiceSubtitle(subscription.service_id) === null
                                    }"
                                    v-text="
                                        $t('subscriptions.title_sub', {
                                            name: getServiceName(subscription.service_id)
                                        })
                                    "
                                ></h3>
                                <h4
                                    v-if="getServiceSubtitle(subscription.service_id)"
                                    class="text-sm text-gray-600 dark:!text-gray-300 font-medium mb-1 mt-0 lh-1"
                                    v-text="getServiceSubtitle(subscription.service_id)"
                                ></h4>
                                <p
                                    class="text-sm text-gray-900 dark:!text-gray-400 leading-relaxed"
                                    v-html="
                                        $t('plans.price_monthly', {
                                            price: getServiceAmount(subscription.service_id),
                                            currency: serviceOption.options.currency.toUpperCase()
                                        })
                                    "
                                ></p>
                                <p class="text-[12px] dark:!text-gray-400">
                                    {{
                                        $t('subscriptions.payment_method', {
                                            method: $t('checkout.' + subscription.payment_method)
                                        })
                                    }}
                                </p>
                                <p class="text-[12px] dark:!text-gray-400">
                                    {{
                                        $t('subscriptions.created_at', {
                                            date: formatDate(subscription.created_at)
                                        })
                                    }}
                                </p>
                                <template
                                    v-if="
                                        subscription.payment_method === 'credit_card' &&
                                        subscription.status === 'active'
                                    "
                                >
                                    <p
                                        v-if="subscription.is_auto_renew"
                                        class="text-[12px] dark:!text-gray-400"
                                    >
                                        {{
                                            $t('subscriptions.next_payment_at', {
                                                date: formatDate(subscription.next_payment_at)
                                            })
                                        }}
                                    </p>
                                    <p
                                        v-else
                                        class="text-[12px] dark:!text-gray-400"
                                        v-html="
                                            $t('subscriptions.end_at', {
                                                date: formatDate(subscription.next_payment_at)
                                            })
                                        "
                                    ></p>
                                </template>
                            </div>
                            <img
                                :src="getServiceLogo(subscription.service_id)"
                                :alt="getServiceName(subscription.service_id)"
                                class="w-[100px] h-[100px] object-contain object-center service-logo"
                            />
                        </div>
                        <div
                            v-if="subscription.status === 'active'"
                            class="flex gap-2 mt-2 items-stretch h-8"
                        >
                            <div
                                :class="[
                                    subscription.status !== 'active' ||
                                    subscription.payment_method !== 'credit_card'
                                        ? 'w-full'
                                        : 'w-1/2'
                                ]"
                            >
                                <span
                                    class="inline-block w-full h-full text-xs font-medium px-2 py-1 rounded-lg text-center flex items-center justify-center bg-green-400 text-gray-800"
                                >
                                    {{ $t('subscriptions.status_' + subscription.status) }}
                                </span>
                            </div>

                            <div
                                v-if="
                                    subscription.status === 'active' &&
                                    subscription.payment_method === 'credit_card'
                                "
                                class="w-1/2"
                            >
                                <button
                                    type="button"
                                    class="w-full h-full bg-black text-white rounded-lg hover:bg-gray-800 text-sm"
                                    @click="
                                        toggleAutoRenew(subscription.id, subscription.is_auto_renew)
                                    "
                                >
                                    {{
                                        subscription.is_auto_renew
                                            ? $t('subscriptions.cancel')
                                            : $t('subscriptions.renew')
                                    }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <p
            v-if="!filteredSubscriptions.length"
            class="text-center text-gray-500 dark:text-gray-300"
        >
            {{
                activeTab === 'active'
                    ? $t('subscriptions.empty_active')
                    : $t('subscriptions.empty_inactive')
            }}
        </p>
    </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '@/stores/auth';
import { useToast } from 'vue-toastification';
import { useI18n } from 'vue-i18n';
import { ref, computed } from 'vue';
import { useServiceStore } from '@/stores/services';
import { useOptionStore } from '@/stores/options';
import { useAlert } from '@/utils/alert';

const authStore = useAuthStore();
const toast = useToast();
const { locale, t } = useI18n();
const { showConfirm } = useAlert();

const activeTab = ref<'active' | 'inactive'>('active');
const serviceStore = useServiceStore();
const serviceOption = useOptionStore();

function getServiceName(serviceId) {
    const service = serviceStore.services.find(s => s.id === serviceId);
    return service?.translations?.[locale.value]?.name ?? `#${serviceId}`;
}

function getServiceSubtitle(serviceId) {
    const service = serviceStore.services.find(s => s.id === serviceId);
    return service?.translations?.[locale.value]?.subtitle ?? null;
}

function getServiceLogo(id) {
    const service = serviceStore.services.find(s => s.id === id);
    return service?.logo ?? '';
}

function getServiceAmount(id) {
    const service = serviceStore.services.find(s => s.id === id);
    return service?.amount.toFixed(2) ?? '';
}

const filteredSubscriptions = computed(() => {
    if (!authStore.user?.subscriptions?.length) return [];

    return authStore.user.subscriptions.filter(sub => {
        return activeTab.value === 'active' ? sub.status === 'active' : sub.status !== 'active';
    });
});

const formatDate = function (dateString) {
    const date = new Date(dateString);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0'); // Месяцы с 0
    const year = date.getFullYear();

    return `${day}.${month}.${year}`;
};

const toggleAutoRenew = async function (id, isAutoRenew) {
    const isCancelling = isAutoRenew === 1;

    const res = await showConfirm({
        title: isCancelling
            ? t('subscriptions.toggle_confirm_title_cancel')
            : t('subscriptions.toggle_confirm_title_renew'),
        text: isCancelling
            ? t('subscriptions.toggle_confirm_text_cancel')
            : t('subscriptions.toggle_confirm_text_renew'),
        confirmText: t('subscriptions.toggle_confirm_yes'),
        cancelText: t('subscriptions.toggle_confirm_no')
    });

    if (res.isConfirmed) {
        try {
            await authStore.toggleAutoRenew(id);
            await authStore.fetchUser();
            toast.success(t('subscriptions.toggled_success'));
        } catch (e) {
            console.log(e);
            toast.error(t('subscriptions.toggled_error'));
        }
    }
};
</script>

<style scoped>
.service-logo {
    height: 100px;
    padding: 10px;
    width: 124px;
}

.service-item {
    border-bottom: 1px solid gray;
    padding-bottom: 10px;
}
</style>
