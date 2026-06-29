/**
 * Centralized money/price formatting (SSOT).
 *
 * Before this module the same `Intl.NumberFormat('ru-RU', { style: 'currency', … })`
 * block was copy-pasted across 6+ components, each maintaining its own formatter cache.
 * Keep the cache here so call sites stay allocation-free while sharing one rule.
 */

// Cache formatters per currency — constructing Intl.NumberFormat is comparatively expensive.
const priceFormatters = new Map<string, Intl.NumberFormat>();

/**
 * Return the cached Intl.NumberFormat for a currency.
 * Exposed for hot loops that format many values and want to hoist the lookup out of the loop.
 */
export const getPriceFormatter = (currency: string): Intl.NumberFormat => {
    if (!priceFormatters.has(currency)) {
        priceFormatters.set(
            currency,
            new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency,
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }),
        );
    }
    return priceFormatters.get(currency)!;
};

/**
 * Format a numeric amount as a currency string (e.g. 12 → "12,00 $").
 * Accepts strings (parsed via parseFloat) so balance fields coming raw from the API work.
 */
export const formatPrice = (
    value: number | string | null | undefined,
    currency: string = 'USD',
): string => {
    const amount = typeof value === 'string' ? parseFloat(value) : Number(value);
    return getPriceFormatter(currency || 'USD').format(Number.isFinite(amount) ? amount : 0);
};

/**
 * The price a product should sell at: discounted `current_price` when present,
 * otherwise the base `price`. Replaces the `current_price || price` idiom.
 */
export const effectivePrice = (
    product: { current_price?: number | null; price?: number | null } | null | undefined,
): number => product?.current_price || product?.price || 0;
