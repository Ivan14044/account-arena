<template>
    <div class="max-w-3xl mx-auto px-4 min-h-[500px] flex flex-col justify-center items-center">
        <h2
            class="text-[32px] md:text-[48px] lg:text-[64px] font-medium text-gray-900 dark:text-white mt-3 text-center mb-4"
        >
            {{ $t('subscribe.title') }}
        </h2>
        <p class="text-gray-500 dark:text-gray-400 mb-10 text-center text-lg leading-6">
            {{ $t('subscribe.description') }}
        </p>
        <div class="w-full relative">
            <form @submit.prevent="handleSubmit">
                <input
                    id="email"
                    v-model="email"
                    type="email"
                    class="w-full h-16 bg-white dark:!bg-gray-800 border-solid border-black dark:border-gray-500 border-[1px] rounded-[14px] px-4 font-medium text-[20px] text-gray-900 dark:!text-white"
                    :class="{
                        'border-red-500': (emailError || requiredError) && emailTouched
                    }"
                    placeholder="Email"
                    name="email"
                    required
                    autocomplete="email"
                    @blur="validateEmail"
                />
                <button
                    class="bg-[#000] w-full max-w-[200px] flex items-center justify-center bg-blue-500 dark:bg-blue-900 hover:bg-blue-600 dark:hover:bg-blue-800 text-white py-2 rounded-[10px] absolute right-[6px] top-[6px] h-[52px] text-[20px] font-medium"
                    type="submit"
                >
                    {{ $t('subscribe.button') }}
                </button>
                <span v-if="emailError && emailTouched" class="text-red-500 text-sm">
                    {{ $t('subscribe.error') }}
                </span>
                <span v-if="requiredError && emailTouched" class="text-red-500 text-sm">
                    {{ $t('subscribe.required') }}
                </span>
                <span v-if="subscribeSuccess" class="text-green-500 text-sm">
                    {{ $t('subscribe.success') }}
                </span>
            </form>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';

const email = ref('');
const emailError = ref(false);
const requiredError = ref(false);
const emailTouched = ref(false);
const subscribeSuccess = ref(false);

const validateEmail = () => {
    emailTouched.value = true;
    requiredError.value = email.value.trim() === '';

    if (!requiredError.value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        emailError.value = !emailRegex.test(email.value);
    } else {
        emailError.value = false;
    }
};

const handleSubmit = () => {
    validateEmail();

    if (!emailError.value && !requiredError.value) {
        // Здесь можно добавить логику отправки формы
        subscribeSuccess.value = true;
        email.value = '';
        emailTouched.value = false;

        // Сбросить сообщение об успехе через 5 секунд
        setTimeout(() => {
            subscribeSuccess.value = false;
        }, 5000);
    }
};
</script>
