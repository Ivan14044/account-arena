<template>
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-8 xl:gap-6 px-4 mb-20">
        <ServiceCard
            v-for="service in serviceStore.services"
            :key="service.id"
            :service="service"
            :is-added="cartStore.hasService(service.id)"
        />
    </div>
</template>

<script setup lang="ts">
import { useServiceStore } from '@/stores/services';
import { useCartStore } from '@/stores/cart';
import ServiceCard from '@/components/services/ServiceCard.vue';

const serviceStore = useServiceStore();
const cartStore = useCartStore();
</script>
