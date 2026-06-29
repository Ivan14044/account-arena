<template>
    <div>
        <div class="text-center mb-14">
            <span class="aa-eyebrow mb-5">{{ t('faq.eyebrow') }}</span>
            <h2
                class="text-[32px] md:text-[48px] lg:text-[64px] font-medium text-gray-900 dark:text-white mt-3 leading-none"
                v-html="t('faq.heading')"
            ></h2>
            <p class="max-w-2xl mx-auto mt-5 text-base md:text-lg text-gray-500 dark:text-gray-400">
                {{ t('faq.subtitle') }}
            </p>
        </div>

        <div class="faq-wrap">
            <div
                v-for="(item, index) in faqItems"
                :key="index"
                class="faq-item"
                :class="{ open: openIndex === index }"
            >
                <button
                    type="button"
                    class="faq-q"
                    :aria-expanded="openIndex === index"
                    @click="toggle(index)"
                >
                    <span class="faq-q-index">{{ String(index + 1).padStart(2, '0') }}</span>
                    <span class="faq-q-text">{{ item.question }}</span>
                    <span class="faq-q-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 9l6 6 6-6" />
                        </svg>
                    </span>
                </button>
                <transition name="faq-acc">
                    <div v-show="openIndex === index" class="faq-a">
                        <p>{{ item.answer }}</p>
                    </div>
                </transition>
            </div>
        </div>

        <div class="faq-cta">
            <div class="faq-cta-text">
                <h3>{{ t('faq.still_have_questions') }}</h3>
                <p>{{ t('faq.contact_support_text') }}</p>
            </div>
            <a
                href="https://t.me/account_arena_support"
                target="_blank"
                rel="noopener noreferrer"
                class="faq-cta-btn"
            >
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.18-.357.295-.6.295l.213-3.054 5.56-5.022c.24-.213-.054-.334-.373-.121l-6.869 4.326-2.96-.924c-.64-.203-.658-.64.135-.954l11.566-4.458c.538-.196 1.006.128.832.941z" />
                </svg>
                {{ t('faq.contact_support') }}
            </a>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t, tm, rt } = useI18n();
const openIndex = ref<number | null>(0);

const toggle = (index: number) => {
    openIndex.value = openIndex.value === index ? null : index;
};

interface FaqItem { question: string; answer: string }

const faqItems = computed<FaqItem[]>(() => {
    const raw = tm('faq.items') as unknown;
    if (!Array.isArray(raw)) return [];
    return raw.map((entry: any) => ({
        question: rt(entry.question),
        answer: rt(entry.answer)
    }));
});
</script>

<style scoped>
.faq-wrap {
    max-width: 920px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.faq-item {
    border-radius: 16px;
    background: var(--aa-surface);
    border: 1px solid var(--aa-border);
    box-shadow: var(--aa-shadow-sm);
    overflow: hidden;
    transition: border-color 0.35s ease, box-shadow 0.35s ease, transform 0.35s ease;
}

.faq-item:hover {
    border-color: var(--aa-gold-line);
}

.faq-item.open {
    border-color: var(--aa-gold-line);
    box-shadow: var(--aa-shadow-md);
}

.faq-q {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 22px 24px;
    background: transparent;
    border: none;
    cursor: pointer;
    text-align: left;
}

.faq-q-index {
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.04em;
    color: var(--aa-gold-strong);
    flex-shrink: 0;
    font-variant-numeric: tabular-nums;
    padding-top: 2px;
}

.faq-q-text {
    flex: 1;
    font-size: 1.0625rem;
    font-weight: 600;
    letter-spacing: -0.01em;
    color: var(--aa-ink);
    line-height: 1.4;
}

.faq-q-icon {
    flex-shrink: 0;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: var(--aa-ink-soft);
    border: 1px solid var(--aa-border);
    background: var(--aa-surface-soft);
    transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1),
                color 0.3s ease, border-color 0.3s ease, background-color 0.3s ease;
}

.faq-q-icon svg {
    width: 16px;
    height: 16px;
}

.faq-item.open .faq-q-icon {
    transform: rotate(180deg);
    color: var(--aa-gold-strong);
    border-color: var(--aa-gold-line);
    background: var(--aa-gold-soft);
}

.faq-a {
    padding: 0 24px 24px 64px;
    color: var(--aa-ink-soft);
    font-size: 0.975rem;
    line-height: 1.65;
}

.faq-a p { margin: 0; }

.faq-acc-enter-active,
.faq-acc-leave-active {
    transition: max-height 0.4s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.3s ease;
    max-height: 320px;
    overflow: hidden;
}

.faq-acc-enter-from,
.faq-acc-leave-to {
    max-height: 0;
    opacity: 0;
}

/* CTA */
.faq-cta {
    max-width: 920px;
    margin: 44px auto 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 28px;
    flex-wrap: wrap;
    padding: 34px 38px;
    border-radius: 22px;
    background: var(--aa-obsidian);
    border: 1px solid var(--aa-gold-line);
    box-shadow: var(--aa-shadow-md);
    position: relative;
    overflow: hidden;
}

.faq-cta::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        radial-gradient(120% 140% at 100% 0%, rgba(216, 183, 101, 0.16) 0%, transparent 55%);
    pointer-events: none;
}

.faq-cta-text { position: relative; }

.faq-cta-text h3 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #fff;
    margin: 0 0 6px;
    letter-spacing: -0.01em;
}

.faq-cta-text p {
    font-size: 1rem;
    color: rgba(255, 255, 255, 0.72);
    margin: 0;
    max-width: 520px;
}

.faq-cta-btn {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 9px;
    padding: 14px 30px;
    border-radius: 12px;
    font-weight: 700;
    font-size: 0.975rem;
    letter-spacing: 0.01em;
    text-decoration: none;
    color: #14161c;
    background: var(--aa-gold-sheen, linear-gradient(120deg, #d8b765 0%, #f1dca0 50%, #c8a151 100%));
    box-shadow: 0 10px 26px rgba(176, 141, 63, 0.34);
    transition: transform 0.3s ease, box-shadow 0.3s ease, filter 0.3s ease;
    flex-shrink: 0;
}

.faq-cta-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 16px 34px rgba(176, 141, 63, 0.44);
    filter: saturate(1.05);
}

@media (max-width: 640px) {
    .faq-a { padding-left: 24px; }
    .faq-cta { padding: 26px; flex-direction: column; align-items: flex-start; }
    .faq-cta-btn { width: 100%; justify-content: center; }
}
</style>
