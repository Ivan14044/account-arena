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

    const getProductDescription = (
        product: {
            description?: string | null;
            description_uk?: string | null;
            description_en?: string | null;
        },
        newLine: boolean = false
    ): string => {
        let description = getLocalizedField<string>(product, 'description');

        // Обработка ссылок: добавляем rel="nofollow noopener noreferrer" для внешних ссылок
        if (description) {
            const hostname = typeof window !== 'undefined' ? window.location.hostname : 'account-arena.com';
            const urlRegex = /(https?:\/\/[^\s<]+)/g;

            // Если ссылки уже есть в HTML, дополняем атрибуты
            if (description.includes('<a')) {
                description = description.replace(/<a\s+(?:[^>]*?\s+)?href=(["'])(.*?)\1/gi, (match, _quote, url) => {
                    // Если ссылка внешняя (начинается с http и не содержит наш хост)
                    if (url.startsWith('http') && !url.includes(hostname)) {
                        let newMatch = match;
                        if (!newMatch.toLowerCase().includes('rel=')) {
                            newMatch = newMatch.replace(/<a/i, '<a rel="nofollow noopener noreferrer"');
                        } else {
                            // Добавляем недостающие значения rel
                            newMatch = newMatch.replace(/rel=(["'])(.*?)\1/i, (_relMatch, relQuote, relValue) => {
                                const relParts = relValue.split(/\s+/);
                                ['nofollow', 'noopener', 'noreferrer'].forEach((val) => {
                                    if (!relParts.includes(val)) {
                                        relParts.push(val);
                                    }
                                });
                                return `rel=${relQuote}${relParts.join(' ')}${relQuote}`;
                            });
                        }
                        if (!newMatch.toLowerCase().includes('target=')) {
                            newMatch = newMatch.replace(/<a/i, '<a target="_blank"');
                        }
                        return newMatch;
                    }
                    return match;
                });
            } else if (description.match(urlRegex)) {
                // Если ссылки указаны простым текстом, превращаем их в <a>
                description = description.replace(urlRegex, (url) => {
                    const isExternal = url.startsWith('http') && !url.includes(hostname);
                    if (isExternal) {
                        return `<a href="${url}" target="_blank" rel="nofollow noopener noreferrer">${url}</a>`;
                    }
                    return `<a href="${url}">${url}</a>`;
                });
            }
        }

        return newLine
            ? description.replace(/\n/g, '<br />')
            : description.replace(/\s+|<br\s*\/?>/g, ' ');
    };

    const getAdditionalDescription = (
        product: {
            additional_description?: string | null;
            additional_description_uk?: string | null;
            additional_description_en?: string | null;
        },
        newLine: boolean = false
    ): string => {
        let additionalDescription = getLocalizedField<string>(product, 'additional_description');

        if (additionalDescription && newLine) {
            return additionalDescription.replace(/\n/g, '<br />');
        }

        return additionalDescription || '';
    };

    return {
        getProductTitle,
        getProductDescription,
        getAdditionalDescription,
        getLocalizedField
    };
}
