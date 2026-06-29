<template>
    <article
        class="article-card group h-full flex flex-col relative overflow-hidden cursor-pointer"
        role="link"
        tabindex="0"
        :aria-label="title"
        @click="goToArticle"
        @keydown.enter="goToArticle"
        style="contain: content; transform: translateZ(0); backface-visibility: hidden;"
    >
        <div class="article-media">
            <ImageWithFallback :src="imageUrl" :alt="title" class="w-full h-full object-cover" />
            <span v-if="date" class="article-date">{{ formattedDate }}</span>
        </div>

        <div class="article-body">
            <div v-if="categories && categories.length" class="article-cats">
                <router-link
                    v-for="category in categories"
                    :key="category.id"
                    :to="`/categories/${category.id}`"
                    class="article-cat"
                    :aria-label="`Open category ${resolveCategoryName(category)}`"
                    @click.stop
                >
                    {{ resolveCategoryName(category) }}
                </router-link>
            </div>

            <h3 class="article-title">{{ title }}</h3>
            <p v-if="short" class="article-excerpt">{{ short }}</p>

            <div class="article-foot">
                <span class="article-readmore">
                    {{ t('articles.readMore') }}
                    <svg
                        class="article-arrow"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M5 12h14" />
                        <path d="m12 5 7 7-7 7" />
                    </svg>
                </span>
            </div>
        </div>
    </article>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import ImageWithFallback from './ImageWithFallback.vue';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';

const { t, locale } = useI18n();
const router = useRouter();

import type { Category } from '../types/article';

const props = defineProps<{
    title: string;
    excerpt: string;
    categories: Category[];
    imageUrl: string;
    href: string;
    date: string;
    short?: string;
}>();

const formattedDate = computed(() => {
    if (!props.date) return '';
    const d = new Date(props.date);
    if (Number.isNaN(d.getTime())) return '';
    return d.toLocaleDateString(locale.value, { day: '2-digit', month: 'short', year: 'numeric' });
});

function resolveCategoryName(category: Category): string {
    const current = locale.value;
    const translated = category.translations?.[current]?.name;
    return translated ?? category.name;
}

function goToArticle() {
    router.push(props.href);
}
</script>

<style scoped>
.article-card {
    border-radius: 20px;
    background: var(--aa-surface);
    border: 1px solid var(--aa-border);
    box-shadow: var(--aa-shadow-sm);
    transition: transform 0.45s cubic-bezier(0.16, 1, 0.3, 1),
                box-shadow 0.45s ease, border-color 0.45s ease;
}

.article-card:hover {
    transform: translateY(-6px);
    box-shadow: var(--aa-shadow-md);
    border-color: var(--aa-gold-line);
}

.article-media {
    position: relative;
    aspect-ratio: 16 / 10;
    overflow: hidden;
    background: var(--aa-surface-soft);
}

.article-media :deep(img),
.article-media :deep(*) {
    transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}

.article-card:hover .article-media :deep(img) {
    transform: scale(1.05);
}

.article-date {
    position: absolute;
    top: 12px;
    left: 12px;
    z-index: 2;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.02em;
    color: var(--aa-gold-strong);
    background: rgba(20, 22, 28, 0.72);
    border: 1px solid var(--aa-gold-line);
    backdrop-filter: blur(6px);
}

.article-body {
    position: relative;
    display: flex;
    flex-direction: column;
    flex: 1;
    padding: 20px 22px 22px;
}

.article-cats {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 12px;
}

.article-cat {
    display: inline-flex;
    align-items: center;
    padding: 3px 10px;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    border-radius: 999px;
    color: var(--aa-ink-soft);
    background: var(--aa-surface-soft);
    border: 1px solid var(--aa-border);
    transition: color 0.25s ease, border-color 0.25s ease, background-color 0.25s ease;
}

.article-cat:hover {
    color: var(--aa-gold-strong);
    border-color: var(--aa-gold-line);
    background: var(--aa-gold-soft);
}

.article-title {
    font-size: 1.2rem;
    font-weight: 700;
    line-height: 1.32;
    letter-spacing: -0.01em;
    color: var(--aa-ink);
    margin: 0 0 8px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    transition: color 0.25s ease;
}

.article-card:hover .article-title {
    color: var(--aa-gold-strong);
}

.article-excerpt {
    font-size: 0.9rem;
    line-height: 1.55;
    color: var(--aa-ink-soft);
    margin: 0;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.article-foot {
    display: flex;
    align-items: center;
    margin-top: auto;
    padding-top: 16px;
}

.article-readmore {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    font-size: 0.875rem;
    font-weight: 600;
    letter-spacing: 0.02em;
    color: var(--aa-gold-strong);
}

.article-arrow {
    width: 16px;
    height: 16px;
    transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1);
}

.article-card:hover .article-arrow {
    transform: translateX(5px);
}

@media (hover: none) and (pointer: coarse) {
    .article-card:hover {
        transform: none;
    }
}
</style>
