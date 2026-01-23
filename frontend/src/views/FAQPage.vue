<template>
    <div class="faq-page">
        <div class="faq-container">
            <Breadcrumbs :crumbs="[{ name: $t('faq.title'), path: '/faq' }]" />
            <!-- Header -->
            <div class="faq-header">
                <h1 class="faq-title">{{ $t('faq.title') }}</h1>
                <p class="faq-subtitle">{{ $t('faq.subtitle') }}</p>
            </div>

            <!-- FAQ Items -->
            <div class="faq-list">
                <div
                    v-for="(item, index) in faqItems"
                    :key="index"
                    class="faq-item"
                    :class="{ active: activeIndex === index }"
                >
                    <button
                        class="faq-question"
                        @click="toggleItem(index)"
                        :aria-expanded="activeIndex === index"
                    >
                        <span class="question-text">{{ item.question }}</span>
                        <svg
                            class="chevron-icon"
                            :class="{ rotated: activeIndex === index }"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 9l-7 7-7-7"
                            />
                        </svg>
                    </button>
                    <transition name="accordion">
                        <div v-if="activeIndex === index" class="faq-answer">
                            <p>{{ item.answer }}</p>
                        </div>
                    </transition>
                </div>
            </div>

            <!-- Contact Support CTA -->
            <div class="faq-cta">
                <h3>{{ $t('faq.still_have_questions') }}</h3>
                <p>{{ $t('faq.contact_support_text') }}</p>
                <a
                    href="https://t.me/account_arena_support"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="btn-primary"
                >
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.18-.357.295-.6.295-.002 0-.003 0-.005 0l.213-3.054 5.56-5.022c.24-.213-.054-.334-.373-.121l-6.869 4.326-2.96-.924c-.64-.203-.658-.64.135-.954l11.566-4.458c.538-.196 1.006.128.832.941z"
                        />
                    </svg>
                    {{ $t('faq.contact_support') }}
                </a>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import Breadcrumbs from '@/components/Breadcrumbs.vue';

const { locale } = useI18n();
const activeIndex = ref<number | null>(null);

const toggleItem = (index: number) => {
    activeIndex.value = activeIndex.value === index ? null : index;
};

const faqItems = computed(() => {
    if (locale.value === 'ru') {
        return [
            {
                question: 'Как купить аккаунт?',
                answer:
                    'Выберите нужный товар, нажмите кнопку "Купить", выберите удобный способ оплаты и следуйте инструкциям. Данные от аккаунта придут моментально после оплаты.'
            },
            {
                question: 'Есть ли гарантия на аккаунты?',
                answer:
                    'Да, мы предоставляем гарантию на валидность аккаунтов на момент покупки. Подробные условия гарантии указаны на странице "Условия замены".'
            },
            {
                question: 'Какие способы оплаты доступны?',
                answer:
                    'Мы принимаем оплату через криптовалюту, банковские карты и электронные кошельки. Все платежи защищены и проходят через безопасные платежные шлюзы.'
            },
            {
                question: 'Как быстро я получу аккаунт после оплаты?',
                answer:
                    'Для товаров с автоматической выдачей - моментально после подтверждения оплаты. Для товаров с ручной выдачей - в течение 1-24 часов в зависимости от времени суток.'
            },
            {
                question: 'Что делать, если аккаунт не работает?',
                answer:
                    'Свяжитесь с нашей службой поддержки через Telegram или форму обратной связи. Мы проверим проблему и предоставим замену согласно условиям гарантии.'
            },
            {
                question: 'Можно ли вернуть деньги?',
                answer:
                    'Возврат средств возможен только в случае, если товар не соответствует описанию или не был предоставлен. Подробности в разделе "Оплата и возврат".'
            }
        ];
    } else if (locale.value === 'uk') {
        return [
            {
                question: 'Як купити акаунт?',
                answer:
                    'Оберіть потрібний товар, натисніть кнопку "Купити", оберіть зручний спосіб оплати та дотримуйтесь інструкцій. Дані від акаунту прийдуть миттєво після оплати.'
            },
            {
                question: 'Чи є гарантія на акаунти?',
                answer:
                    'Так, ми надаємо гарантію на валідність акаунтів на момент покупки. Детальні умови гарантії вказані на сторінці "Умови заміни".'
            }
        ];
    } else {
        // English
        return [
            {
                question: 'How to buy an account?',
                answer:
                    'Choose the desired product, click "Buy", select a convenient payment method and follow the instructions. Account data will arrive instantly after payment.'
            },
            {
                question: 'Is there a warranty on accounts?',
                answer:
                    'Yes, we provide a warranty for account validity at the time of purchase. Detailed warranty conditions are listed on the "Replacement Conditions" page.'
            }
        ];
    }
});
</script>

<style scoped>
.faq-page {
    min-height: 100vh;
    padding: 60px 20px;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
}

.dark .faq-page {
    background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%);
}

.faq-container {
    max-width: 900px;
    margin: 0 auto;
}

.faq-header {
    text-align: center;
    margin-bottom: 48px;
}

.faq-title {
    font-size: 3rem;
    font-weight: 800;
    background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 16px;
}

.faq-subtitle {
    font-size: 1.25rem;
    color: #64748b;
    max-width: 600px;
    margin: 0 auto;
}

.dark .faq-subtitle {
    color: #94a3b8;
}

.faq-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
    margin-bottom: 48px;
}

.faq-item {
    background: rgba(255, 255, 255, 0.95);
    border: 1px solid rgba(226, 232, 240, 0.6);
    border-radius: 16px;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.dark .faq-item {
    background: rgba(30, 41, 59, 0.95);
    border-color: rgba(255, 255, 255, 0.08);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
}

.faq-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 24px rgba(108, 92, 231, 0.15);
    border-color: rgba(108, 92, 231, 0.3);
}

.dark .faq-item:hover {
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.4);
    border-color: rgba(108, 92, 231, 0.4);
}

.faq-item.active {
    border-color: rgba(108, 92, 231, 0.5);
}

.faq-question {
    width: 100%;
    padding: 24px;
    background: transparent;
    border: none;
    text-align: left;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    transition: background-color 0.2s ease;
}

.faq-question:hover {
    background: rgba(108, 92, 231, 0.05);
}

.dark .faq-question:hover {
    background: rgba(108, 92, 231, 0.1);
}

.question-text {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1f2937;
    flex: 1;
}

.dark .question-text {
    color: #f1f5f9;
}

.chevron-icon {
    width: 24px;
    height: 24px;
    color: #6c5ce7;
    transition: transform 0.3s ease;
    flex-shrink: 0;
}

.chevron-icon.rotated {
    transform: rotate(180deg);
}

.faq-answer {
    padding: 0 24px 24px 24px;
    color: #64748b;
    line-height: 1.7;
    font-size: 1rem;
}

.dark .faq-answer {
    color: #94a3b8;
}

.faq-answer p {
    margin: 0;
}

.accordion-enter-active,
.accordion-leave-active {
    transition: all 0.3s ease;
    max-height: 500px;
    overflow: hidden;
}

.accordion-enter-from,
.accordion-leave-to {
    max-height: 0;
    opacity: 0;
}

.faq-cta {
    background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);
    border-radius: 20px;
    padding: 48px;
    text-align: center;
    color: white;
    box-shadow: 0 20px 40px rgba(108, 92, 231, 0.3);
}

.faq-cta h3 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 12px;
}

.faq-cta p {
    font-size: 1.125rem;
    opacity: 0.9;
    margin-bottom: 24px;
}

.btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 14px 32px;
    background: white;
    color: #6c5ce7;
    border-radius: 12px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
}

@media (max-width: 768px) {
    .faq-title {
        font-size: 2rem;
    }

    .faq-subtitle {
        font-size: 1rem;
    }

    .faq-question {
        padding: 16px;
    }

    .question-text {
        font-size: 1rem;
    }

    .faq-answer {
        padding: 0 16px 16px 16px;
        font-size: 0.9375rem;
    }

    .faq-cta {
        padding: 32px 24px;
    }

    .faq-cta h3 {
        font-size: 1.5rem;
    }

    .faq-cta p {
        font-size: 1rem;
    }
}
</style>
