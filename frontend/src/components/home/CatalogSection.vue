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
                        v-if="category.image_url"
                        :src="category.image_url"
                        :alt="category.name"
                        class="category-icon"
                    />
                    <span class="category-name">{{ category.name }}</span>
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
import { useProductCategoriesStore, type ProductCategory } from '@/stores/productCategories';
import { useI18n } from 'vue-i18n';

const categoriesStore = useProductCategoriesStore();
const { locale } = useI18n();

const selectedCategoryId = ref<number | null>(null);
const selectedSubcategoryId = ref<number | null>(null);
const hideOutOfStock = ref(false);
const showFavoritesOnly = ref(false);
const searchQuery = ref('');

const categories = computed(() => {
    // Add "Все категории" as first option
    const allCategories: ProductCategory[] = [
        {
            id: 0,
            type: 'product',
            name:
                locale.value === 'ru'
                    ? 'Все категории'
                    : locale.value === 'en'
                      ? 'All Categories'
                      : 'Всі категорії'
        },
        ...categoriesStore.list.map(cat => ({
            ...cat,
            name: categoriesStore.getCategoryName(cat, locale.value)
        }))
    ];
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

const selectCategory = (categoryId: number | null) => {
    selectedCategoryId.value = categoryId === 0 ? null : categoryId;
    selectedSubcategoryId.value = null; // Сбрасываем выбранную подкатегорию при смене категории

    emit('filter-change', {
        categoryId: selectedCategoryId.value,
        subcategoryId: null,
        hideOutOfStock: hideOutOfStock.value,
        showFavoritesOnly: showFavoritesOnly.value,
        searchQuery: searchQuery.value
    });
};

const selectSubcategory = (subcategoryId: number | null) => {
    selectedSubcategoryId.value = subcategoryId;

    emit('filter-change', {
        categoryId: selectedCategoryId.value,
        subcategoryId: selectedSubcategoryId.value,
        hideOutOfStock: hideOutOfStock.value,
        showFavoritesOnly: showFavoritesOnly.value,
        searchQuery: searchQuery.value
    });
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

watch([hideOutOfStock, showFavoritesOnly, searchQuery], () => {
    emit('filter-change', {
        categoryId: selectedCategoryId.value,
        subcategoryId: selectedSubcategoryId.value,
        hideOutOfStock: hideOutOfStock.value,
        showFavoritesOnly: showFavoritesOnly.value,
        searchQuery: searchQuery.value
    });
});

onMounted(async () => {
    await categoriesStore.fetchAll();
});
</script>

<style scoped>
.catalog-section {
    background: rgba(255, 255, 255, 0.6);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    padding: 32px;
    margin-bottom: 32px;
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
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.category-buttons {
    display: flex;
    gap: 12px;
    flex-wrap: nowrap;
    padding-bottom: 8px;
}

.category-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 20px;
    background: #f9fafb;
    border: 2px solid transparent;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 15px;
    font-weight: 500;
    color: #374151;
    white-space: nowrap;
    font-family: 'SFT Schrifted Sans', sans-serif;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.dark .category-btn {
    background: #374151;
    color: #e5e7eb;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.category-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.dark .category-btn:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.category-btn.active {
    background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);
    color: #ffffff;
    border-color: #6c5ce7;
    box-shadow: 0 4px 16px rgba(108, 92, 231, 0.3);
}

.dark .category-btn.active {
    background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);
    box-shadow: 0 4px 16px rgba(108, 92, 231, 0.4);
}

.category-icon {
    width: 24px;
    height: 24px;
    border-radius: 6px;
    object-fit: cover;
}

.category-name {
    font-weight: 500;
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
    transition: all 0.3s ease;
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
    transition: all 0.3s ease;
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
    transition: all 0.3s ease;
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
    transition: all 0.3s ease;
    font-size: 14px;
    font-weight: 500;
    color: #374151;
    white-space: nowrap;
    font-family: 'SFT Schrifted Sans', sans-serif;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
}

.dark .subcategory-btn {
    background: #4b5563;
    color: #e5e7eb;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.2);
}

.subcategory-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
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
</style>
