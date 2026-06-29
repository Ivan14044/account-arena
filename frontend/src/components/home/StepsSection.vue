<template>
    <p class="steps-lead">
        {{ stepsDescription }}
    </p>

    <div class="steps-grid">
        <!-- 1 -->
        <article class="step-card">
            <div class="step-head">
                <span class="step-num">01</span>
                <span class="step-icon">
                    <img :src="eyesIcon" :alt="$t('steps.step1.title')" />
                </span>
            </div>
            <h3 class="step-title">{{ $t('steps.step1.title') }}</h3>
            <div class="step-rule"></div>
            <dl class="step-body">
                <dt>{{ $t('steps.step1.description1') }}</dt>
                <dd>{{ $t('steps.step1.subdescription1') }}</dd>
                <dt>{{ $t('steps.step1.description2') }}</dt>
                <dd>{{ $t('steps.step1.subdescription2') }}</dd>
                <dt>{{ $t('steps.step1.description3') }}</dt>
                <dd>{{ $t('steps.step1.subdescription3') }}</dd>
            </dl>
        </article>

        <!-- 2 -->
        <article class="step-card">
            <div class="step-head">
                <span class="step-num">02</span>
                <span class="step-icon">
                    <img :src="pencilIcon" :alt="$t('steps.step2.title')" />
                </span>
            </div>
            <h3 class="step-title">{{ $t('steps.step2.title') }}</h3>
            <div class="step-rule"></div>
            <ul class="step-list">
                <li>{{ $t('steps.step2.description1') }}</li>
                <li>{{ $t('steps.step2.description2') }}</li>
                <li>{{ $t('steps.step2.description3') }}</li>
                <li>{{ $t('steps.step2.description4') }}</li>
                <li>{{ $t('steps.step2.description5') }}</li>
            </ul>
        </article>

        <!-- 3 -->
        <article class="step-card">
            <div class="step-head">
                <span class="step-num">03</span>
                <span class="step-icon">
                    <img :src="fireIcon" :alt="$t('steps.step3.title')" />
                </span>
            </div>
            <h3 class="step-title">{{ $t('steps.step3.title') }}</h3>
            <div class="step-rule"></div>
            <ul class="step-list">
                <li>{{ $t('steps.step3.description1') }}</li>
                <li>{{ $t('steps.step3.description2') }}</li>
                <li>{{ $t('steps.step3.description3') }}</li>
                <li>{{ $t('steps.step3.description4') }}</li>
                <li>{{ $t('steps.step3.description5') }}</li>
            </ul>
        </article>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useSiteContentStore } from '@/stores/siteContent';
import eyesIcon from '@/assets/img/eyes.png';
import pencilIcon from '@/assets/img/pencil.png';
import fireIcon from '@/assets/img/fire.png';

const { t, locale } = useI18n();
const siteContentStore = useSiteContentStore();

const stepsContent = computed(() => siteContentStore.steps(locale.value));

const stepsDescription = computed(() => {
    return stepsContent.value?.description || t('steps.description');
});
</script>

<style scoped>
.steps-lead {
    text-align: center;
    max-width: 640px;
    margin: 0 auto 3.5rem;
    font-size: 1.0625rem;
    line-height: 1.6;
    color: var(--aa-ink-soft);
}

.steps-grid {
    display: grid;
    grid-template-columns: repeat(1, minmax(0, 1fr));
    gap: 28px;
    max-width: 1240px;
    margin: 0 auto;
}

@media (min-width: 768px) {
    .steps-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .steps-grid .step-card:last-child { grid-column: 1 / -1; }
}

@media (min-width: 1280px) {
    .steps-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .steps-grid .step-card:last-child { grid-column: auto; }
}

.step-card {
    position: relative;
    padding: 34px 32px 36px;
    border-radius: 20px;
    background: var(--aa-surface);
    border: 1px solid var(--aa-border);
    box-shadow: var(--aa-shadow-sm);
    overflow: hidden;
    transition: transform 0.45s cubic-bezier(0.16, 1, 0.3, 1),
                box-shadow 0.45s ease, border-color 0.45s ease;
}

/* Тонкая золотая линия по верхней грани */
.step-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 2px;
    background: var(--aa-gold-gradient);
    opacity: 0.55;
    transition: opacity 0.45s ease;
}

.step-card:hover {
    transform: translateY(-6px);
    box-shadow: var(--aa-shadow-md);
    border-color: var(--aa-gold-line);
}

.step-card:hover::before { opacity: 1; }

.step-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 18px;
}

.step-num {
    font-size: 56px;
    font-weight: 800;
    line-height: 0.9;
    letter-spacing: -0.03em;
    -webkit-text-fill-color: transparent;
    -webkit-background-clip: text;
    background-clip: text;
    background-image: var(--aa-gold-gradient);
    font-family: 'SFT Schrifted Sans', sans-serif;
}

.step-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: var(--aa-surface-soft);
    border: 1px solid var(--aa-gold-line);
    box-shadow: inset 0 0 0 4px var(--aa-surface);
    flex-shrink: 0;
}

.step-icon img {
    width: 26px;
    height: 26px;
    object-fit: contain;
}

.step-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--aa-ink);
    letter-spacing: -0.01em;
    margin: 0 0 16px;
}

.step-rule {
    width: 40px;
    height: 2px;
    background: var(--aa-gold-line);
    border-radius: 2px;
    margin-bottom: 18px;
}

.step-body {
    margin: 0;
}

.step-body dt {
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--aa-ink);
    margin-bottom: 2px;
}

.step-body dt:not(:first-child) {
    margin-top: 14px;
}

.step-body dd {
    margin: 0;
    font-size: 0.9rem;
    line-height: 1.55;
    color: var(--aa-ink-soft);
}

.step-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.step-list li {
    position: relative;
    padding-left: 22px;
    font-size: 0.9375rem;
    line-height: 1.5;
    color: var(--aa-ink-soft);
}

.step-list li::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0.55em;
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--aa-gold-gradient);
}
</style>
