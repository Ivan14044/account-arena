<template>
    <p class="text-center mb-10 text-lg leading-6 text-gray-700 dark:text-gray-300">
        {{ $t('steps.description') }}
    </p>

    <div
        class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8 mx-auto max-w-[1440px] justify-items-center md:justify-items-stretch"
    >
        <!-- 1 -->
        <div
            class="step p-8 rounded-2xl w-full max-w-[460px] md:justify-self-end xl:justify-self-auto relative group bg-gradient-to-br from-purple-500/20 via-indigo-500/20 to-blue-500/20 dark:from-indigo-900/30 dark:via-purple-900/30 dark:to-blue-900/30 border border-indigo-400/30 dark:border-gray-700/40 backdrop-blur-md shadow-lg"
        >
            <div class="flex items-center gap-4 mb-4">
                <div class="w-9 h-9 flex items-center justify-center rounded-full text-white font-bold bg-pink-600 dark:bg-pink-700 shadow-md">
                    01
                </div>
                <img
                    :src="eyesIcon"
                    alt="eyes emoji"
                    class="shadow dark:shadow-black w-12 bg-white/80 p-2 position-absolute right-4 top-4 rounded-full"
                />
                <p class="text-lg font-semibold leading-none text-gray-900 dark:text-white">
                    {{ $t('steps.step1.title') }}
                </p>
            </div>
            <p class="text-gray-900 dark:text-gray-100 text-base font-medium leading-5 mb-1">
                {{ $t('steps.step1.description1') }}
            </p>
            <p class="text-gray-700 dark:text-gray-300 text-base leading-5 mb-3">
                {{ $t('steps.step1.subdescription1') }}
            </p>
            <p class="text-gray-900 dark:text-gray-100 text-base font-medium leading-5 mb-1">
                {{ $t('steps.step1.description2') }}
            </p>
            <p class="text-gray-700 dark:text-gray-300 text-base leading-5 mb-3">
                {{ $t('steps.step1.subdescription2') }}
            </p>
            <p class="text-gray-900 dark:text-gray-100 text-base font-medium leading-5 mb-1">
                {{ $t('steps.step1.description3') }}
            </p>
            <p class="text-gray-700 dark:text-gray-300 text-base leading-5">
                {{ $t('steps.step1.subdescription3') }}
            </p>
        </div>

        <!-- 2 -->
        <div
            class="step p-8 rounded-2xl w-full max-w-[460px] relative group bg-gradient-to-br from-purple-500/20 via-indigo-500/20 to-blue-500/20 dark:from-indigo-900/30 dark:via-purple-900/30 dark:to-blue-900/30 border border-indigo-400/30 dark:border-gray-700/40 backdrop-blur-md shadow-lg"
        >
            <div class="flex items-center gap-4 mb-4">
                <div class="w-9 h-9 flex items-center justify-center rounded-full text-white font-bold bg-green-600 dark:bg-green-700 shadow-md">
                    02
                </div>
                <img
                    :src="pencilIcon"
                    alt="eyes emoji"
                    class="shadow dark:shadow-black w-12 bg-white/80 p-2 position-absolute right-4 top-4 rounded-full"
                />
                <p class="text-lg font-semibold leading-none text-gray-900 dark:text-white">
                    {{ $t('steps.step2.title') }}
                </p>
            </div>
            <p class="text-gray-800 dark:text-gray-200 text-base leading-5 mb-1">
                {{ $t('steps.step2.description1') }}
            </p>
            <p class="text-gray-800 dark:text-gray-200 text-base leading-5 mb-1">
                {{ $t('steps.step2.description2') }}
            </p>
            <p class="text-gray-800 dark:text-gray-200 text-base leading-5 mb-1">
                {{ $t('steps.step2.description3') }}
            </p>
            <p class="text-gray-800 dark:text-gray-200 text-base leading-5 mb-1">
                {{ $t('steps.step2.description4') }}
            </p>
            <p class="text-gray-800 dark:text-gray-200 text-base leading-5">
                {{ $t('steps.step2.description5') }}
            </p>
        </div>

        <!-- 3 -->
        <div
            class="step p-8 rounded-2xl w-full max-w-[460px] md:col-span-2 md:justify-self-center xl:col-span-1 xl:justify-self-auto relative group bg-gradient-to-br from-purple-500/20 via-indigo-500/20 to-blue-500/20 dark:from-indigo-900/30 dark:via-purple-900/30 dark:to-blue-900/30 border border-indigo-400/30 dark:border-gray-700/40 backdrop-blur-md shadow-lg"
        >
            <div class="flex items-center gap-4 mb-4">
                <div class="w-9 h-9 flex items-center justify-center rounded-full text-white font-bold bg-blue-600 dark:bg-blue-700 shadow-md">
                    03
                </div>
                <img
                    :src="fireIcon"
                    alt="eyes emoji"
                    class="shadow dark:shadow-black w-12 bg-white/80 p-2 position-absolute right-4 top-4 rounded-full"
                />
                <p class="text-lg font-semibold leading-none text-gray-900 dark:text-white">
                    {{ $t('steps.step3.title') }}
                </p>
            </div>
            <p class="text-gray-800 dark:text-gray-200 text-base leading-5 mb-1">
                {{ $t('steps.step3.description1') }}
            </p>
            <p class="text-gray-800 dark:text-gray-200 text-base leading-5 mb-1">
                {{ $t('steps.step3.description2') }}
            </p>
            <p class="text-gray-800 dark:text-gray-200 text-base leading-5 mb-1">
                {{ $t('steps.step3.description3') }}
            </p>
            <p class="text-gray-800 dark:text-gray-200 text-base leading-5 mb-1">
                {{ $t('steps.step3.description4') }}
            </p>
            <p class="text-gray-800 dark:text-gray-200 text-base leading-5">
                {{ $t('steps.step3.description5') }}
            </p>
        </div>
    </div>
</template>

<script setup lang="ts">
import eyesIcon from '@/assets/img/eyes.png';
import pencilIcon from '@/assets/img/pencil.png';
import fireIcon from '@/assets/img/fire.png';
</script>

<style scoped>
/* Base styling for each step card */
.step {
    position: relative;
    text-align: left;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

/* Subtle lift and shadow on hover */
.step:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 25px rgba(0, 0, 0, 0.15);
}

/* Stronger shadow in dark mode on hover */
:global(.dark) .step:hover {
    box-shadow: 0 12px 25px rgba(0, 0, 0, 0.4);
}
</style>
