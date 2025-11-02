<template>
    <div class="flex flex-col gap-6 w-full">
        <div
            v-for="item in cartStore.items"
            :key="item.id"
            class="service-card transition-all duration-300 glass-card rounded-3xl p-4 sm:p-6 relative"
        >
            <button
                type="button"
                class="absolute top-4 right-4 w-9 h-9 rounded-full glass-button flex items-center justify-center transition-colors duration-200 hover:bg-red-500 text-dark dark:text-white hover:text-white"
                :disabled="isPromoFreeLocked(item.id)"
                :class="isPromoFreeLocked(item.id) ? 'opacity-50 cursor-not-allowed hover:bg-transparent hover:text-current' : ''"
                @click="!isPromoFreeLocked(item.id) && removeFromCart(item.id)"
            >
                <Trash class="w-4" />
            </button>

            <div class="flex items-start gap-4 text-dark dark:text-white">
                <img
                    :src="item.logo"
                    :alt="`${getTranslation(item, 'name')} Logo`"
                    class="w-16 h-16 sm:w-20 sm:h-20 flex-shrink-0 object-contain"
                />

                <div class="flex-1 space-y-2">
                    <h3 class="text-lg sm:text-xl">
                        {{ getTranslation(item, 'name') }}
                    </h3>
                    <p class="text-sm">
                        {{ getTranslation(item, 'subtitle') }}
                    </p>
                    <p
                        class="text-sm mt-1"
                        v-html="getTranslation(item, 'short_description_checkout')"
                    ></p>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 text-dark dark:text-white sm:grid-cols-2 gap-4">
                <div
                    class="plan-card rounded-lg p-3 relative border-2"
                    :class="[
                        planClass(item.id, 'trial'),
                        cartStore.isFree(item.id)
                            ? 'opacity-50 pointer-events-none cursor-not-allowed'
                            : 'cursor-pointer'
                    ]"
                    @click="!cartStore.isFree(item.id) && cartStore.setSubscriptionType(item.id, 'trial')"
                >
                    <div class="flex justify-between items-start xl:items-center">
                        <div class="xl:flex xl:items-center xl:gap-2">
                            <div class="text-lg">Trial</div>
                            <div class="text-xs px-2 py-1 bg-orange-400 !text-gray-950 rounded-lg">
                                {{ $t('checkout.trial_badge') }}
                            </div>
                        </div>
                        <div class="text-right xl:flex xl:items-center">
                            <div class="text-lg font-bold">
                                {{ cartStore.isFree(item.id) ? formatter.format(0) : formatter.format(item.trial_amount) }}
                            </div>
                            <div class="text-xs text-dark dark:text-white xl:ml-2">
                                <span class="hidden xl:!inline-flex mr-1"> / </span>
                                {{ pluralizeDays(optionStore.options.trial_days, locale) }}
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="plan-card cursor-pointer rounded-lg p-3 relative border-2"
                    :class="planClass(item.id, 'premium')"
                    @click="!cartStore.isFree(item.id) && cartStore.setSubscriptionType(item.id, 'premium')"
                >
                    <div class="flex justify-between items-start xl:items-center">
                        <div class="xl:flex xl:items-center xl:gap-2">
                            <div class="text-lg">Premium</div>
                            <div class="text-xs px-2 py-1 bg-green-400 !text-gray-950 rounded-lg">
                                {{ $t('checkout.premium_badge') }}
                            </div>
                        </div>
                        <div class="text-right xl:flex xl:items-center">
                            <div class="text-lg font-bold">
                                {{ cartStore.isFree(item.id) ? formatter.format(0) : formatter.format(item.amount) }}
                            </div>
                            <div class="text-xs text-dark dark:text-white xl:ml-2">
                                <span class="hidden xl:!inline-flex mr-1"> / </span
                                >{{ $t('perMonth') }}
                            </div>
                        </div>
                    </div>
                    <div v-if="cartStore.isFree(item.id)" class="absolute -top-3 -left-3 bg-green-500 text-white text-xs px-2 py-1 rounded-md shadow">
                        {{ $t('checkout.free_via_promocode', { days: cartStore.promoFreeDays[item.id] || 0 }) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useCartStore } from '@/stores/cart';
import { usePromoStore } from '@/stores/promo';
import { useOptionStore } from '@/stores/options';
import { useI18n } from 'vue-i18n';
import { useAlert } from '@/utils/alert';
import { Trash } from 'lucide-vue-next';
import { pluralizeDays } from '@/utils/pluralize';

const cartStore = useCartStore();
const promoStore = usePromoStore();
const optionStore = useOptionStore();
const { locale, t } = useI18n();
const { showConfirm } = useAlert();

const formatter = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: optionStore.options.currency,
    minimumFractionDigits: 0,
    maximumFractionDigits: 2
});

const getTranslation = (service: any, key: string) => {
    return service.translations[locale.value]?.[key] ?? service.translations['en']?.[key] ?? null;
};

const planClass = (itemId: string, plan: 'trial' | 'premium') => {
    const isSelected = cartStore.getSubscriptionType(itemId) === plan;
    if (plan === 'trial') {
        return [
            'plan-card cursor-pointer rounded-lg p-3 relative border-2',
            isSelected
                ? 'border-orange-400 bg-orange-400/10'
                : 'border-transparent glass-card hover:border-orange-400/50'
        ];
    } else {
        return [
            'plan-card cursor-pointer rounded-lg p-3 relative border-2',
            isSelected
                ? 'border-green-400 bg-green-400/10'
                : 'border-transparent glass-card hover:border-green-400/50'
        ];
    }
};

const removeFromCart = async (id: number) => {
    const res = await showConfirm({
        title: t('cart.confirm.remove_single.title'),
        text: t('cart.confirm.remove_single.text'),
        confirmText: t('cart.confirm.remove_single.confirm'),
        cancelText: t('cart.confirm.remove_single.cancel')
    });

    if (res.isConfirmed) {
        cartStore.removeFromCart(id);
    }
};

const isPromoFreeLocked = (id: number) => {
    return (
        cartStore.isFree(id) &&
        !!promoStore.code &&
        !!promoStore.result &&
        promoStore.result.type === 'free_access'
    );
};
</script>

<style scoped>
.service-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
}

@media (max-width: 1024px) {
    .service-card {
        padding: 16px;
    }
}
</style>
