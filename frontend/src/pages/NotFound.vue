<template>
    <div class="max-w-7xl mx-auto px-4 py-16 sm:px-6 lg:px-8">
        <div class="flex flex-col items-center justify-center min-h-[600px]">
            <div class="mx-auto text-center text-6xl font-light text-gray-900 dark:text-white">
                404
            </div>
            <div class="mx-auto text-center text-2xl font-light text-gray-900 dark:text-gray-300">
                Page not found
            </div>
            <div class="mx-auto text-center mt-10">
                <button
                    class="bg-blue-500 dark:bg-blue-900 hover:bg-blue-600 dark:hover:bg-blue-800 text-center text-sm font-light text-white rounded-md px-4 py-2"
                    @click="goBack"
                >
                    Go back to home
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router';

const router = useRouter();

function goBack() {
    router.push('/');
}
</script>
