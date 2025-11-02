<template>
  <div class="max-w-5xl mx-auto px-4 py-10">
    <div v-if="!account" class="text-center text-gray-500">Загрузка...</div>
    <div v-else class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
      <div class="flex flex-col md:flex-row gap-6">
        <div class="md:w-1/3">
          <img
            v-if="account.image_url"
            :src="account.image_url"
            :alt="account.title"
            class="w-full h-auto rounded-lg object-cover"
          />
          <div v-else class="w-full h-48 bg-gray-100 dark:bg-gray-700 rounded-lg" />
        </div>
        <div class="md:w-2/3">
          <h1 class="text-2xl font-semibold text-gray-900 dark:text-white mb-2">{{ account.title }}</h1>
          <div v-if="account.description" class="prose dark:prose-invert max-w-none mb-4" v-html="account.description" />

          <div class="flex items-center gap-6 mt-4">
            <div class="text-3xl font-bold text-green-600 dark:text-green-400">
              {{ formatPrice(account.price) }}
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400">
              В наличии: {{ account.quantity ?? 0 }} шт.
            </div>
          </div>

          <div class="mt-6 flex gap-3">
            <button
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50"
              :disabled="!account.quantity || account.quantity === 0"
            >
              Купить
            </button>
            <button class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-lg" @click="$router.back()">
              Назад
            </button>
          </div>
        </div>
      </div>

      <div v-if="account.additional_description" class="prose dark:prose-invert max-w-none mt-8" v-html="account.additional_description" />
    </div>
  </div>
  
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { useAccountsStore, type AccountItem } from '@/stores/accounts';

const route = useRoute();
const accountsStore = useAccountsStore();
const account = ref<AccountItem | null>(null);

function formatPrice(value: number): string {
  const num = Number(value ?? 0);
  try {
    return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' }).format(num);
  } catch (_) {
    return `$${num.toFixed(2)}`;
  }
}

onMounted(async () => {
  const id = Number(route.params.id);
  if (!Number.isFinite(id)) return;
  account.value = await accountsStore.fetchById(id).catch(() => null);
});
</script>

<style scoped>
</style>


