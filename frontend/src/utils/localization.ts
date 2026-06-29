/**
 * SSOT for resolving localized fields on API objects.
 *
 * Two shapes exist in the API:
 *  - flat suffix fields:   { title, title_en, title_uk }
 *  - nested translations:  { name, translations: { uk: { name }, en: { name } } }
 *
 * Both rules were previously copy-pasted (with subtle drift) across composables,
 * stores and components. Keep them here so every call site resolves identically.
 */

/**
 * Resolve a flat suffix-localized field (e.g. `title` / `title_en` / `title_uk`).
 * Rule: for `ru` use the base field; otherwise prefer `field_<locale>` and fall back to base.
 */
export const getLocalizedField = (
    obj: Record<string, any> | null | undefined,
    field: string,
    locale: string,
): string => {
    if (!obj) return '';
    if (locale === 'ru') return obj[field] || '';
    return obj[`${field}_${locale}`] || obj[field] || '';
};

/**
 * Resolve a field from a nested `translations` map (e.g. category/subcategory names).
 * Rule: prefer `translations[locale][field]`, fall back to the base field.
 */
export const getTranslatedField = (
    obj: Record<string, any> | null | undefined,
    field: string,
    locale: string,
): string => {
    if (!obj) return '';
    const translated = obj.translations?.[locale];
    if (translated && translated[field]) return translated[field];
    return obj[field] || '';
};

/**
 * Pick the translation entry for a locale from a `translations` array
 * (the shape used by articles: `[{ locale, title, content, short }, …]`).
 * Returns `undefined` when there is no match — callers apply their own field fallbacks.
 */
export const getArticleTranslation = <T extends { locale: string }>(
    translations: T[] | null | undefined,
    locale: string,
): T | undefined => translations?.find((t) => t.locale === locale);
