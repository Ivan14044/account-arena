<template>
    <router-view />
</template>

<script setup lang="ts">
defineProps<{
    isLoading: boolean;
}>();
</script>
