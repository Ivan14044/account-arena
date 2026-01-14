<template>
    <div class="catalog-section">
        <!-- Header -->
        <div class="catalog-header">
            <h3 class="catalog-title">{{ $t('catalog.title') }}</h3>
            <p class="catalog-subtitle">{{ $t('catalog.subtitle') }}</p>
        </div>

        <!-- Category Buttons -->
        <div class="category-buttons-wrapper">
            <div class="category-buttons">
                <button
                    v-for="category in categories"
                    :key="category.id"
                    :class="['category-btn', { active: selectedCategoryId === category.id }]"
                    @click="selectCategory(category.id)"
                >
                    <img
                        v-if="category.image_url && category.id !== 0"
                        :src="category.image_url"
                        :alt="category.name || 'Category'"
                        class="category-icon"
                        loading="lazy"
                        @error="handleImageError($event)"
                    />
                    <span class="category-name">{{ category.name }}</span>
                    <span v-if="getCategoryProductCount(category.id) > 0" class="category-count">
                        {{ getCategoryProductCount(category.id) }}
                    </span>
                </button>
            </div>
        </div>

        <!-- Subcategories -->
        <div v-if="selectedSubcategories.length > 0" class="subcategories-wrapper">
            <div class="subcategories">
                <button
                    v-for="subcategory in selectedSubcategories"
                    :key="subcategory.id"
                    :class="[
                        'subcategory-btn',
                        { active: selectedSubcategoryId === subcategory.id }
                    ]"
                    @click="selectSubcategory(subcategory.id)"
                >
                    <span class="subcategory-name">{{ getSubcategoryName(subcategory) }}</span>
                    <span
                        v-if="getSubcategoryProductCount(subcategory.id) > 0"
                        class="subcategory-count"
                    >
                        {{ getSubcategoryProductCount(subcategory.id) }}
                    </span>
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-wrapper">
            <div class="filters-left">
                <label class="toggle-wrapper">
                    <input v-model="hideOutOfStock" type="checkbox" class="toggle-input" />
                    <span class="toggle-slider"></span>
                    <span class="toggle-label">{{ $t('catalog.hide_out_of_stock') }}</span>
                </label>

                <label class="toggle-wrapper">
                    <input v-model="showFavoritesOnly" type="checkbox" class="toggle-input" />
                    <span class="toggle-slider"></span>
                    <span class="toggle-label">{{ $t('catalog.show_favorites_only') }}</span>
                </label>
            </div>

            <div class="search-wrapper">
                <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                    />
                </svg>
                <input
                    v-model="searchQuery"
                    type="text"
                    :placeholder="$t('catalog.search_placeholder')"
                    class="search-input"
                />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { debounce } from 'lodash-es';
import { useProductCategoriesStore, type ProductCategory } from '@/stores/productCategories';
import { useAccountsStore } from '@/stores/accounts';
import { useI18n } from 'vue-i18n';

const categoriesStore = useProductCategoriesStore();
const accountsStore = useAccountsStore();
const { locale } = useI18n();

const selectedCategoryId = ref<number | null>(null);
const selectedSubcategoryId = ref<number | null>(null);
const hideOutOfStock = ref(false);
const showFavoritesOnly = ref(false);
const searchQuery = ref('');
const debouncedSearchQuery = ref('');

// Кэш для подсчетов товаров (критическая оптимизация производительности)
const categoriesCache = ref<Map<string, ProductCategory[]>>(new Map());

// КРИТИЧЕСКАЯ ОПТИМИЗАЦИЯ: Предвычисляем все счетчики товаров один раз
// Это избавляет от множественных filter операций при каждом вызове
const productCounts = computed(() => {
    const directCounts = new Map<number, number>();
    
    // 1. Считаем товары напрямую в каждой категории/подкатегории (O(N) по товарам)
    accountsStore.list.forEach(account => {
        const catId = account.category?.id;
        if (catId) {
            directCounts.set(catId, (directCounts.get(catId) || 0) + 1);
        }
    });
    
    const categoryCounts = new Map<number, number>();
    const subcategoryCounts = new Map<number, number>();
    
    // 2. Рассчитываем итоговые счетчики (O(M) по категориям)
    categoriesStore.list.forEach(category => {
        // Прямые товары в родительской категории
        let totalForCategory = directCounts.get(category.id) || 0;
        
        // Товары в подкатегориях
        if (category.subcategories && category.subcategories.length > 0) {
            category.subcategories.forEach(sub => {
                const subCount = directCounts.get(sub.id) || 0;
                subcategoryCounts.set(sub.id, subCount);
                totalForCategory += subCount;
            });
        }
        
        categoryCounts.set(category.id, totalForCategory);
    });
    
    return { categoryCounts, subcategoryCounts };
});

// Мемоизированные категории (кэшируются по локали)
const categories = computed(() => {
    const currentLocale = locale.value;
    
    // Проверяем кэш
    if (categoriesCache.value.has(currentLocale)) {
        return categoriesCache.value.get(currentLocale)!;
    }
    
    // Создаем категории один раз для текущей локали
    const allCategories: ProductCategory[] = [
        {
            id: 0,
            type: 'product',
            name:
                currentLocale === 'ru'
                    ? 'Все категории'
                    : currentLocale === 'en'
                      ? 'All Categories'
                      : 'Всі категорії'
        },
        ...categoriesStore.list.map(cat => ({
            ...cat,
            name: categoriesStore.getCategoryName(cat, currentLocale)
        }))
    ];
    
    // Сохраняем в кэш
    categoriesCache.value.set(currentLocale, allCategories);
    return allCategories;
});

const selectedSubcategories = computed(() => {
    if (!selectedCategoryId.value || selectedCategoryId.value === 0) {
        return [];
    }

    const category = categoriesStore.list.find(cat => cat.id === selectedCategoryId.value);
    return category?.subcategories || [];
});

const getSubcategoryName = (subcategory: any): string => {
    if (
        subcategory.translations &&
        subcategory.translations[locale.value] &&
        subcategory.translations[locale.value]['name']
    ) {
        return subcategory.translations[locale.value]['name'];
    }
    return subcategory.name || '';
};

// КРИТИЧЕСКАЯ ОПТИМИЗАЦИЯ: Используем предвычисленные счетчики вместо filter операций
const getCategoryProductCount = (categoryId: number): number => {
    if (categoryId === 0) {
        // Для "Все категории" возвращаем общее количество всех товаров
        return accountsStore.list.length;
    }
    return productCounts.value.categoryCounts.get(categoryId) || 0;
};

// КРИТИЧЕСКАЯ ОПТИМИЗАЦИЯ: Используем предвычисленные счетчики вместо filter операций
const getSubcategoryProductCount = (subcategoryId: number): number => {
    return productCounts.value.subcategoryCounts.get(subcategoryId) || 0;
};

const selectCategory = (categoryId: number | null) => {
    selectedCategoryId.value = categoryId === 0 ? null : categoryId;
    selectedSubcategoryId.value = null; // Сбрасываем выбранную подкатегорию при смене категории

    emit('filter-change', {
        categoryId: selectedCategoryId.value,
        subcategoryId: null,
        hideOutOfStock: hideOutOfStock.value,
        showFavoritesOnly: showFavoritesOnly.value,
        searchQuery: debouncedSearchQuery.value || searchQuery.value
    });
};

const selectSubcategory = (subcategoryId: number | null) => {
    selectedSubcategoryId.value = subcategoryId;

    emit('filter-change', {
        categoryId: selectedCategoryId.value,
        subcategoryId: selectedSubcategoryId.value,
        hideOutOfStock: hideOutOfStock.value,
        showFavoritesOnly: showFavoritesOnly.value,
        searchQuery: debouncedSearchQuery.value || searchQuery.value
    });
};

// Обработчик ошибок загрузки изображений категорий
const handleImageError = (event: Event) => {
    const img = event.target as HTMLImageElement;
    if (img) {
        // Пытаемся загрузить fallback изображение
        const fallbackSrc = '/img/placeholder-category.png';
        if (img.src !== fallbackSrc && !img.src.includes('placeholder')) {
            // Если это не fallback, пытаемся загрузить его
            img.src = fallbackSrc;
            img.onerror = () => {
                // Если fallback тоже не загрузился, скрываем изображение
                img.style.display = 'none';
            };
        } else {
            // Если fallback не загрузился, скрываем изображение
            img.style.display = 'none';
        }
    }
};

const emit = defineEmits<{
    'filter-change': [
        filters: {
            categoryId: number | null;
            subcategoryId: number | null;
            hideOutOfStock: boolean;
            showFavoritesOnly: boolean;
            searchQuery: string;
        }
    ];
}>();

// Debounce функция для поиска (300ms задержка)
const updateSearchQuery = debounce((value: string) => {
    debouncedSearchQuery.value = value;
    emit('filter-change', {
        categoryId: selectedCategoryId.value,
        subcategoryId: selectedSubcategoryId.value,
        hideOutOfStock: hideOutOfStock.value,
        showFavoritesOnly: showFavoritesOnly.value,
        searchQuery: debouncedSearchQuery.value
    });
}, 300);

// Watch для поиска с debounce
watch(searchQuery, (newValue) => {
    updateSearchQuery(newValue);
});

// Watch для остальных фильтров (без debounce, но с flush: post)
watch([hideOutOfStock, showFavoritesOnly], () => {
    emit('filter-change', {
        categoryId: selectedCategoryId.value,
        subcategoryId: selectedSubcategoryId.value,
        hideOutOfStock: hideOutOfStock.value,
        showFavoritesOnly: showFavoritesOnly.value,
        searchQuery: debouncedSearchQuery.value || searchQuery.value
    });
}, { flush: 'post' });

// Очистка кэша при изменении списка товаров или категорий
watch(() => accountsStore.list.length, () => {
    // Кэш counts пересчитывается автоматически через computed productCounts
});

watch(() => categoriesStore.list.length, () => {
    categoriesCache.value.clear();
});

watch(() => locale.value, () => {
    categoriesCache.value.clear();
});

onMounted(async () => {
    await categoriesStore.fetchAll();
    // Убеждаемся, что товары загружены для подсчета
    if (!accountsStore.loaded) {
        await accountsStore.fetchAll();
    }
});
</script>

<style scoped>
.catalog-section {
    /* Упрощаем для производительности - убираем блюр */
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: none; /* Убираем блюр для FPS */
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 20px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06); /* Упрощенная тень */
    padding: 32px;
    margin-bottom: 32px;
    /* GPU acceleration для backdrop-filter */
    transform: translateZ(0);
    isolation: isolate;
    contain: layout style paint;
}

.dark .catalog-section {
    background: rgba(31, 41, 55, 0.6);
    border-color: rgba(75, 85, 99, 0.3);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.catalog-header {
    margin-bottom: 28px;
    text-align: center;
}

.catalog-title {
    font-size: 28px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 8px;
    font-family: 'SFT Schrifted Sans', sans-serif;
}

.dark .catalog-title {
    color: #ffffff;
}

.catalog-subtitle {
    font-size: 16px;
    color: #6b7280;
    margin: 0;
    font-weight: 400;
}

.dark .catalog-subtitle {
    color: #9ca3af;
}

/* Category Buttons */
.category-buttons-wrapper {
    margin-bottom: 28px;
}

.category-buttons {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    padding-bottom: 8px;
}

.category-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 22px;
    background: rgba(255, 255, 255, 0.85);
    /* Упрощаем backdrop-filter для повышения FPS - оставляем только на активных или убираем совсем */
    backdrop-filter: none; 
    border: 1px solid rgba(226, 232, 240, 0.8);
    border-radius: 14px;
    cursor: pointer;
    /* Оптимизация: только конкретные свойства */
    transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), 
                background-color 0.2s ease, 
                border-color 0.2s ease, 
                box-shadow 0.2s ease, 
                color 0.2s ease;
    font-size: 15px;
    font-weight: 600;
    color: #1f2937;
    white-space: nowrap;
    font-family: 'SFT Schrifted Sans', sans-serif;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    flex-shrink: 0;
    min-width: fit-content;
    position: relative;
    overflow: hidden;
    /* GPU acceleration */
    transform: translateZ(0);
    backface-visibility: hidden;
    isolation: isolate;
}

.category-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(108, 92, 231, 0.1), transparent);
    transition: left 0.5s ease;
}

.category-btn:hover::before {
    left: 100%;
}

.dark .category-btn {
    background: rgba(55, 65, 81, 0.8);
    border-color: rgba(75, 85, 99, 0.6);
    color: #f3f4f6;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

.category-btn:hover {
    will-change: transform;
    transform: translateY(-2px) translateZ(0);
    box-shadow: 0 6px 20px rgba(108, 92, 231, 0.15);
    border-color: rgba(108, 92, 231, 0.3);
    background: rgba(255, 255, 255, 0.95);
}

/* Убираем will-change после hover для экономии памяти */
.category-btn:not(:hover) {
    will-change: auto;
}

.dark .category-btn:hover {
    background: rgba(55, 65, 81, 0.95);
    box-shadow: 0 6px 20px rgba(108, 92, 231, 0.25);
    border-color: rgba(108, 92, 231, 0.4);
}

.category-btn.active {
    background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);
    color: #ffffff;
    border-color: #6c5ce7;
    box-shadow: 0 6px 24px rgba(108, 92, 231, 0.35);
    transform: translateY(-2px) translateZ(0);
}

.category-btn.active::before {
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
}

.dark .category-btn.active {
    background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);
    box-shadow: 0 6px 24px rgba(108, 92, 231, 0.45);
}

.category-icon {
    width: 26px;
    height: 26px;
    border-radius: 8px;
    object-fit: cover;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
    flex-shrink: 0;
}

.category-btn:hover .category-icon {
    transform: scale(1.1);
}

.category-btn.active .category-icon {
    box-shadow: 0 2px 8px rgba(255, 255, 255, 0.3);
}

.category-name {
    font-weight: 500;
}

.category-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 26px;
    height: 26px;
    padding: 0 9px;
    background: linear-gradient(
        135deg,
        rgba(108, 92, 231, 0.15) 0%,
        rgba(162, 155, 254, 0.15) 100%
    );
    backdrop-filter: none; /* Убираем блюр с мелких элементов */
    color: #6c5ce7;
    border: 1px solid rgba(108, 92, 231, 0.2);
    border-radius: 13px;
    font-size: 11px;
    font-weight: 700;
    margin-left: 8px;
    letter-spacing: 0.3px;
    box-shadow: 0 1px 3px rgba(108, 92, 231, 0.1); /* Упрощенная тень */
    transition: transform 0.2s ease;
    will-change: transform;
    transform: translateZ(0);
}

.dark .category-count {
    background: linear-gradient(135deg, rgba(162, 155, 254, 0.2) 0%, rgba(139, 92, 231, 0.2) 100%);
    border-color: rgba(162, 155, 254, 0.3);
    color: #a29bfe;
    box-shadow: 0 2px 6px rgba(162, 155, 254, 0.2);
}

.category-btn:hover .category-count {
    transform: scale(1.05);
    box-shadow: 0 3px 8px rgba(108, 92, 231, 0.25);
}

.category-btn.active .category-count {
    background: rgba(255, 255, 255, 0.25);
    backdrop-filter: blur(10px);
    color: #ffffff;
    border-color: rgba(255, 255, 255, 0.3);
    box-shadow: 0 2px 8px rgba(255, 255, 255, 0.2);
}

/* Filters */
.filters-wrapper {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 24px;
    flex-wrap: wrap;
}

.filters-left {
    display: flex;
    gap: 24px;
    flex-wrap: wrap;
}

.toggle-wrapper {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    user-select: none;
}

.toggle-input {
    display: none;
}

.toggle-slider {
    position: relative;
    width: 48px;
    height: 26px;
    background: #d1d5db;
    border-radius: 13px;
    transition: background-color 0.3s ease, box-shadow 0.3s ease;
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
}

.dark .toggle-slider {
    background: #4b5563;
}

.toggle-slider::before {
    content: '';
    position: absolute;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #ffffff;
    top: 3px;
    left: 3px;
    transition: transform 0.3s ease, left 0.3s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.toggle-input:checked + .toggle-slider {
    background: #6c5ce7;
    box-shadow: 0 0 0 3px rgba(108, 92, 231, 0.2);
}

.toggle-input:checked + .toggle-slider::before {
    transform: translateX(22px);
}

.toggle-label {
    font-size: 14px;
    font-weight: 500;
    color: #374151;
    font-family: 'SFT Schrifted Sans', sans-serif;
}

.dark .toggle-label {
    color: #e5e7eb;
}

/* Search */
.search-wrapper {
    position: relative;
    flex: 1;
    max-width: 300px;
}

.search-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    width: 20px;
    height: 20px;
    color: #9ca3af;
    pointer-events: none;
}

.search-input {
    width: 100%;
    padding: 12px 16px 12px 42px;
    background: #f9fafb;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    font-size: 14px;
    color: #1f2937;
    transition: border-color 0.3s ease, box-shadow 0.3s ease, background-color 0.3s ease;
    font-family: 'SFT Schrifted Sans', sans-serif;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.dark .search-input {
    background: #374151;
    border-color: #4b5563;
    color: #ffffff;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.search-input:focus {
    outline: none;
    border-color: #6c5ce7;
    box-shadow: 0 0 0 3px rgba(108, 92, 231, 0.1);
}

.search-input::placeholder {
    color: #9ca3af;
}

@media (max-width: 768px) {
    .catalog-section {
        padding: 20px;
    }

    .filters-wrapper {
        flex-direction: column;
        align-items: stretch;
    }

    .filters-left {
        flex-direction: column;
        gap: 16px;
    }

    .search-wrapper {
        max-width: 100%;
    }

    .category-buttons {
        flex-wrap: wrap;
    }
}

/* Subcategories */
.subcategories-wrapper {
    margin-top: 20px;
    margin-bottom: 20px;
    padding-top: 20px;
    border-top: 1px solid rgba(0, 0, 0, 0.1);
}

.dark .subcategories-wrapper {
    border-top-color: rgba(255, 255, 255, 0.1);
}

.subcategories {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.subcategory-btn {
    padding: 8px 16px;
    background: #f3f4f6;
    border: 2px solid transparent;
    border-radius: 8px;
    cursor: pointer;
    /* Оптимизация: только transform для GPU acceleration */
    transition: transform 0.2s ease, opacity 0.2s ease, box-shadow 0.2s ease;
    font-size: 14px;
    font-weight: 500;
    color: #374151;
    white-space: nowrap;
    font-family: 'SFT Schrifted Sans', sans-serif;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
    /* GPU acceleration */
    transform: translateZ(0);
}

.dark .subcategory-btn {
    background: #4b5563;
    color: #e5e7eb;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.2);
}

.subcategory-btn:hover {
    will-change: transform;
    transform: translateY(-1px) translateZ(0);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.dark .subcategory-btn:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

.subcategory-btn:not(:hover) {
    will-change: auto;
}

.dark .subcategory-btn:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

.subcategory-btn.active {
    background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);
    color: #ffffff;
    border-color: #6c5ce7;
    box-shadow: 0 2px 12px rgba(108, 92, 231, 0.3);
}

.dark .subcategory-btn.active {
    background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);
    box-shadow: 0 2px 12px rgba(108, 92, 231, 0.4);
}

.subcategory-name {
    font-weight: 500;
}

.subcategory-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 22px;
    height: 22px;
    padding: 0 7px;
    background: linear-gradient(
        135deg,
        rgba(108, 92, 231, 0.12) 0%,
        rgba(162, 155, 254, 0.12) 100%
    );
    backdrop-filter: none;
    color: #6c5ce7;
    border: 1px solid rgba(108, 92, 231, 0.2);
    border-radius: 11px;
    font-size: 10px;
    font-weight: 700;
    margin-left: 7px;
    letter-spacing: 0.2px;
    box-shadow: 0 1px 4px rgba(108, 92, 231, 0.15);
    transition: background-color 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease, color 0.3s ease;
}

.dark .subcategory-count {
    background: linear-gradient(135deg, rgba(162, 155, 254, 0.2) 0%, rgba(139, 92, 231, 0.2) 100%);
    border-color: rgba(162, 155, 254, 0.3);
    color: #a29bfe;
    box-shadow: 0 1px 4px rgba(162, 155, 254, 0.2);
}

.subcategory-btn:hover .subcategory-count {
    transform: scale(1.05);
    box-shadow: 0 2px 6px rgba(108, 92, 231, 0.25);
}

.subcategory-btn.active .subcategory-count {
    background: rgba(255, 255, 255, 0.25);
    backdrop-filter: blur(10px);
    color: #ffffff;
    border-color: rgba(255, 255, 255, 0.3);
    box-shadow: 0 1px 6px rgba(255, 255, 255, 0.2);
}

.subcategory-name {
    font-weight: 500;
}
</style>
