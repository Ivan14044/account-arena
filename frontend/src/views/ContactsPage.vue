<template>
    <div class="contacts-page">
        <div class="container mx-auto px-4 py-16 max-w-5xl">
            <Breadcrumbs :crumbs="[{ name: $t('contacts.title'), path: '/contacts' }]" />
            <div class="text-center mb-16">
                <h1 class="text-4xl font-bold mb-4 gradient-text">{{ $t('contacts.title') }}</h1>
                <p class="text-lg text-gray-600 dark:text-gray-400">{{ $t('contacts.subtitle') }}</p>
            </div>

            <div class="grid md:grid-cols-2 gap-12">
                <!-- Contact Info -->
                <div class="space-y-8">
                    <div class="theme-card p-8">
                        <h2 class="text-2xl font-semibold mb-6">{{ $t('contacts.our_contacts') }}</h2>
                        
                        <div class="space-y-6">
                            <div class="flex items-start gap-4">
                                <div class="icon-wrapper">
                                    <MessageCircle class="w-6 h-6 text-blue-500" />
                                </div>
                                <div>
                                    <h3 class="font-medium">{{ $t('contacts.telegram_support') }}</h3>
                                    <a href="https://t.me/account_arena_support" target="_blank" class="text-blue-600 hover:underline">@account_arena_support</a>
                                    <p class="text-sm text-gray-500 mt-1">{{ $t('contacts.support_hours') }}</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-4">
                                <div class="icon-wrapper">
                                    <Mail class="w-6 h-6 text-blue-500" />
                                </div>
                                <div>
                                    <h3 class="font-medium">Email</h3>
                                    <a href="mailto:support@account-arena.com" class="text-blue-600 hover:underline">support@account-arena.com</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="theme-card p-8">
                        <h2 class="text-2xl font-semibold mb-6">{{ $t('contacts.legal_info') }}</h2>
                        <div class="text-gray-700 dark:text-gray-300 space-y-2">
                            <p><strong>{{ $t('contacts.legal_name_label') }}:</strong> {{ $t('contacts.legal_name') }}</p>
                            <p><strong>{{ $t('contacts.address_label') }}:</strong> {{ $t('contacts.address') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Simple Contact Form (Frontend only for now) -->
                <div class="theme-card p-8">
                    <h2 class="text-2xl font-semibold mb-6">{{ $t('contacts.send_message') }}</h2>
                    <form @submit.prevent="handleSubmit" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">{{ $t('contacts.form.name') }}</label>
                            <input v-model="form.name" type="text" class="w-full theme-input" required />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Email</label>
                            <input v-model="form.email" type="email" class="w-full theme-input" required />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">{{ $t('contacts.form.message') }}</label>
                            <textarea v-model="form.message" rows="4" class="w-full theme-input" required></textarea>
                        </div>
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg transition-colors">
                            {{ $t('contacts.form.submit') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { MessageCircle, Mail } from 'lucide-vue-next';
import { useToast } from 'vue-toastification';

const { t } = useI18n();
const toast = useToast();

const form = ref({
    name: '',
    email: '',
    message: ''
});

const handleSubmit = () => {
    toast.success(t('contacts.form.success'));
    form.value = { name: '', email: '', message: '' };
};
</script>

<style scoped>
.icon-wrapper {
    background: rgba(59, 130, 246, 0.1);
    padding: 0.75rem;
    border-radius: 0.75rem;
    flex-shrink: 0;
}

.gradient-text {
    background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.theme-input {
    @apply border border-gray-300 dark:border-gray-600 p-2.5 rounded-lg bg-transparent focus:ring-2 focus:ring-blue-500 outline-none transition-shadow;
}
</style>
