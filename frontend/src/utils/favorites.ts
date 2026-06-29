/**
 * SSOT for the product-favorites localStorage list.
 *
 * The same key + load/save try-catch block was duplicated across AccountSection,
 * SimilarProducts and AccountDetail. Keep the storage key and (de)serialization here
 * so every component reads/writes the same shape.
 */

const FAVORITES_STORAGE_KEY = 'product_favorites';

/** Load the set of favorite product ids from localStorage (empty set on miss/error). */
export const loadFavorites = (): Set<number> => {
    try {
        const stored = localStorage.getItem(FAVORITES_STORAGE_KEY);
        if (stored) {
            return new Set(JSON.parse(stored));
        }
    } catch (error) {
        console.error('Error loading favorites:', error);
    }
    return new Set();
};

/** Persist the set of favorite product ids to localStorage. */
export const saveFavorites = (favs: Set<number>): void => {
    try {
        localStorage.setItem(FAVORITES_STORAGE_KEY, JSON.stringify([...favs]));
    } catch (error) {
        console.error('Error saving favorites:', error);
    }
};
