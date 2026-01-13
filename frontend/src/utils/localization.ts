/**
 * Centralized localization helper for dynamic content (titles, names, descriptions)
 */

export const getLocalizedValue = (
    data: any, 
    field: string = 'title', 
    locale: string = 'uk'
): string => {
    if (!data) return '';

    // If data is a plain string, return it
    if (typeof data === 'string') return data;

    // Handle standard translation structure: { title: 'RU', title_en: 'EN', title_uk: 'UK' }
    if (locale === 'ru') {
        return data[field] || data[`${field}_ru`] || data[`${field}_en`] || '';
    }
    
    if (locale === 'en') {
        return data[`${field}_en`] || data[field] || data[`${field}_uk`] || '';
    }
    
    if (locale === 'uk') {
        return data[`${field}_uk`] || data[field] || data[`${field}_en`] || '';
    }

    // Handle nested translations object (from CategoryResource)
    if (data.translations && typeof data.translations === 'object') {
        const translations = data.translations[locale] || 
                           data.translations['uk'] || 
                           data.translations['ru'] || 
                           data.translations['en'] || 
                           Object.values(data.translations)[0];
        
        if (translations && typeof translations === 'object') {
            return translations[field] || '';
        }
    }

    return data[field] || '';
};

/**
 * Specifically for ServiceAccount/Product titles
 */
export const getProductTitle = (product: any, locale: string = 'uk'): string => {
    // Some API responses return title as an object { title, title_en, title_uk }
    if (product.title && typeof product.title === 'object') {
        return getLocalizedValue(product.title, 'title', locale);
    }
    
    return getLocalizedValue(product, 'title', locale);
};

/**
 * Specifically for Category names
 */
export const getCategoryName = (category: any, locale: string = 'uk'): string => {
    // CategoryResource returns 'name' field
    if (category.name && typeof category.name === 'string') {
        return category.name;
    }
    
    return getLocalizedValue(category, 'name', locale);
};
