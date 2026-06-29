<template>
    <div class="flex items-stretch gap-0">
        <input
            v-model.trim="code"
            :placeholder="$t('checkout.promocode_placeholder')"
            class="flex-1 h-11 px-3 border rounded-l-lg rounded-r-none"
            :class="
                isApplied
                    ? '!border-green-400 !bg-green-400/10'
                    : '!border-gray-700 dark:!border-gray-300'
            "
            :disabled="isApplied"
        />
        <button
            class="h-11 w-12 grid place-items-center border border-l-0 rounded-r-lg rounded-l-none transition-all text-white disabled:opacity-50"
            :class="
                isApplied
                    ? 'border-red-500 bg-red-500 hover:bg-red-600 dark:border-red-700 dark:bg-red-900 dark:hover:bg-red-800'
                    : 'border-blue-500 bg-blue-500 hover:bg-blue-600 dark:border-blue-700 dark:bg-blue-900 dark:hover:bg-blue-800'
            "
            :disabled="loading || (!isApplied && !code)"
            :aria-label="
                isApplied
                    ? $t('checkout.promocode_clear_aria')
                    : $t('checkout.promocode_apply_aria')
            "
            @click.prevent="$emit('primaryClick')"
        >
            <X v-if="isApplied" class="w-5 h-5" />
            <Check v-else class="w-5 h-5" />
        </button>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Check, X } from 'lucide-vue-next';

/**
 * Promo-code input + apply/clear button (extracted from CheckoutPage.vue, where the
 * identical block was rendered twice). Pure presentation: state lives in the parent.
 */
const props = defineProps<{
    modelValue: string;
    isApplied: boolean;
    loading: boolean;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
    primaryClick: [];
}>();

// Writable proxy so `v-model.trim` keeps trimming, with state owned by the parent.
const code = computed({
    get: () => props.modelValue,
    set: value => emit('update:modelValue', value)
});
</script>
