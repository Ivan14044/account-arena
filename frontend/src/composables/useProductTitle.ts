import { useI18n } from 'vue-i18n';

/**
 * Composable for getting localized product fields (title, description)
 * Returns field value based on current locale:
 * - ru: field (e.g., title, description)
 * - uk: field_uk (e.g., title_uk, description_uk)
 * - en: field_en (e.g., title_en, description_en)
 */
export function useProductTitle() {
    const { locale } = useI18n();

    const getLocalizedField = <T extends string | null | undefined>(
        product: Record<string, any>,
        fieldName: string
    ): T => {
        if (!product) return '' as T;

        const currentLocale = locale.value;
        let fieldValue: string | null | undefined;

        switch (currentLocale) {
            case 'uk':
                // Use title_uk if it exists and is not null/empty, otherwise fallback to title
                fieldValue = product[`${fieldName}_uk`] || product[fieldName] || '';
                break;
            case 'en':
                // Use title_en if it exists and is not null/empty, otherwise fallback to title
                fieldValue = product[`${fieldName}_en`] || product[fieldName] || '';
                break;
            case 'ru':
            default:
                // For Russian, use title (base field)
                fieldValue = product[fieldName] || '';
                break;
        }

        return (fieldValue || '') as T;
    };

    const getProductTitle = (product: {
        title?: string | null;
        title_uk?: string | null;
        title_en?: string | null;
    }): string => {
        return getLocalizedField<string>(product, 'title');
    };

    const getProductDescription = (product: {
        description?: string | null;
        description_uk?: string | null;
        description_en?: string | null;
    }, newLine: boolean = false): string => {
        const description = getLocalizedField<string>(product, 'description')
        return newLine ? description.replaceAll("\n", "<br />") : description.replace(/\s+|\<br\s*\/?\>/gm, ' ');
    };

    return {
        getProductTitle,
        getProductDescription,
        getLocalizedField
    };
}
