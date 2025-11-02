<template>
    <div>
        <div v-if="isOpen" class="fixed inset-0 bg-[#1617196e] z-40" @click="$emit('close')"></div>

        <Transition appear name="slide-x">
            <div v-if="isOpen" class="fixed top-0 left-0 h-full w-64 bg-gray-900 z-50">
                <div class="flex justify-between items-center p-4 border-b border-gray-700">
                    <div
                        class="flex items-center sm:gap-2 w-[32px]"
                        @click="handleClick({ is_target: 0, link: '/' })"
                    >
                        <img
                            :src="logo"
                            alt="Logo"
                            class="!w-[29px] sm:w-8 sm:h-8 object-contain rounded-full spin-slow-reverse"
                        />
                        <span class="text-white text-xl font-bold pl-3"> SubCloudy </span>
                    </div>
                    <button class="text-white" @click="$emit('close')">
                        <svg
                            width="24"
                            height="24"
                            viewBox="0 0 24 24"
                            fill="none"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path
                                d="M18 6L6 18M6 6L18 18"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </button>
                </div>

                <!-- Меню -->
                <nav class="p-4">
                    <ul class="flex flex-col gap-2">
                        <li
                            v-for="(item, index) in menuItems"
                            :key="index"
                            class="text-white cursor-pointer hover:text-gray-300 transition-colors font-lg border !border-gray-800 px-4 py-3 rounded-md"
                            @click="handleClick(item)"
                        >
                            {{ item.title }}
                        </li>
                    </ul>
                </nav>
            </div>
        </Transition>
    </div>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router';
import logo from '@/assets/logo.webp';

const router = useRouter();

defineProps<{
    isOpen: boolean;
    menuItems: any[];
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const handleClick = (item: any) => {
    if (item.is_target) {
        window.open(item.link, '_blank');
    } else {
        router.push(item.link);
    }
    emit('close');
};
</script>

<style scoped>
.slide-x-enter-from,
.slide-x-leave-to {
    transform: translateX(-100%);
}
.slide-x-enter-to,
.slide-x-leave-from {
    transform: translateX(0%);
}
.slide-x-enter-active,
.slide-x-leave-active {
    transition: transform 300ms ease-in-out;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
.fade-enter-active,
.fade-leave-active {
    transition: opacity 200ms ease;
}
</style>

<!-- Duplicate transition classes globally to avoid scoping issues on transition hooks -->
<style>
.slide-x-enter-from,
.slide-x-leave-to {
    transform: translateX(-100%);
}
.slide-x-enter-to,
.slide-x-leave-from {
    transform: translateX(0%);
}
.slide-x-enter-active,
.slide-x-leave-active {
    transition: transform 300ms ease-in-out;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
.fade-enter-active,
.fade-leave-active {
    transition: opacity 200ms ease;
}
</style>
