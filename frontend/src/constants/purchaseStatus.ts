/**
 * SSOT for purchase status values and their presentation.
 *
 * The status strings mirror the backend `Purchase` model constants. Keeping them
 * named here removes the scattered magic-string comparisons across ProfilePage /
 * OrderSuccessPage and gives the status→CSS-class map a single home.
 */

export const PurchaseStatus = {
    COMPLETED: 'completed',
    PENDING: 'pending',
    PROCESSING: 'processing',
    FAILED: 'failed',
    CANCELLED: 'cancelled',
    REFUNDED: 'refunded',
} as const;

export type PurchaseStatusValue = (typeof PurchaseStatus)[keyof typeof PurchaseStatus];

const STATUS_CLASSES: Record<string, string> = {
    [PurchaseStatus.COMPLETED]: 'status-completed',
    [PurchaseStatus.PENDING]: 'status-pending',
    [PurchaseStatus.PROCESSING]: 'status-processing',
    [PurchaseStatus.FAILED]: 'status-failed',
    [PurchaseStatus.CANCELLED]: 'status-cancelled',
    [PurchaseStatus.REFUNDED]: 'status-refunded',
};

/** CSS class for a purchase status badge. Falls back to the completed style (unchanged behaviour). */
export const purchaseStatusClass = (status: string): string =>
    STATUS_CLASSES[status] || 'status-completed';
