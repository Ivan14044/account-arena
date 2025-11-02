<template>
    <div :class="[
        'absolute inset-0 z-[9999] flex items-center justify-center bg-black/40 rounded-3xl pointer-events-none',
        expandPadding ? 'm-[-1.5rem]' : ''
    ]">
        <img :src="logo" alt="Loading..." class="w-16 h-16 object-contain spin-slow-reverse" />
    </div>
</template>

<script setup lang="ts">
import logo from '@/assets/logo.webp';

const { expandPadding = false } = defineProps<{ expandPadding?: boolean }>();
</script>
