# Cart, Checkout, Purchases & Order Delivery

Functional inventory for the **Account Arena** marketplace covering the shopping cart, checkout, purchase lifecycle, and order delivery (automatic stock delivery + manual delivery).

> Scope note: Account Arena sells digital **products** (a.k.a. "service accounts" / stock items). The cart/checkout described here is the **product** flow. Subscriptions were removed from the codebase (see `Console/Kernel.php` commented-out lines). Comments in source are in Russian; this document summarizes them in English.

---

## 0. High-level model

| Concept | Where | Notes |
|---|---|---|
| Product / stock item | `App\Models\ServiceAccount` | A sellable item. Holds inventory in `accounts_data` (JSON array of credential strings), a `used` counter, a `delivery_type` (`automatic`/`manual`), and pricing. |
| Purchase | `App\Models\Purchase` | One row per product line bought. Holds `account_data` (delivered credentials), `status`, `order_number`, links to `transaction` and `serviceAccount`. Soft-deletable. |
| Transaction | `App\Models\Transaction` | Payment record created alongside each Purchase. |
| Status history | `App\Models\PurchaseStatusHistory` | Append-only audit log of status transitions for each Purchase. |
| SupplierEarning | `App\Models\SupplierEarning` | When a purchased product belongs to a supplier, a "held" earning is recorded (out of this doc's core scope, but created during purchase). |

**Two delivery types** (`ServiceAccount::DELIVERY_AUTOMATIC = 'automatic'`, `DELIVERY_MANUAL = 'manual'`):
- **Automatic** — credentials are pulled from `accounts_data` instantly at purchase time; purchase is created as `completed`.
- **Manual** — no credentials assigned at purchase; purchase is created as `processing` and a human admin later fills in `account_data` via the admin Manual Delivery panel.

---

## 1. Frontend cart (client-side only)

### 1.1 Product cart store
- **Name:** Product cart (Pinia store)
- **What it does:** Holds cart items entirely client-side; persisted to `localStorage` (key `product_cart`). There is **no server-side cart**; the cart is materialized into a purchase only at checkout/payment.
- **File:** `frontend/src/stores/productCart.ts`
- **State:** `items: CartItem[]` where `CartItem` = `{ id, title, title_uk?, title_en?, price, quantity, image_url?, max_quantity }` (`productCart.ts:3`).
- **Getters:** `itemCount` (unique items), `totalQuantity` (sum of quantities), `totalAmount` (sum `price*quantity`), `hasProduct(id)`, `getProduct(id)`, `getProductQuantity(id)` (`productCart.ts:19-45`).
- **Actions:**
  - `addItem(product, quantity=1)` — adds or increments; **clamps to `product.quantity`** (the available stock at add-time, stored as `max_quantity`) (`productCart.ts:49-87`).
  - `removeItem(id)`, `updateQuantity(id, qty)` (clamped to `[1, max_quantity]`), `increaseQuantity`/`decreaseQuantity` (bounded by `max_quantity` and `1`), `clearCart` (`productCart.ts:90-126`).
  - `saveToLocalStorage`/`loadFromLocalStorage` plus the `persist` plugin config (`productCart.ts:128-154`).
- **Business rules / edge cases:**
  - Max quantity is captured once when the item is added; it does **not** auto-refresh against live stock. Real stock is re-validated server-side at checkout/webhook (see §3/§4), so a stale `max_quantity` can only over-promise UI-side, never over-deliver.
  - No price recheck client-side; server recomputes price at purchase time (`getCurrentPrice()`).

### 1.2 Cart widget (header)
- **Name:** ServiceCart header widget
- **What it does:** Shows total amount (desktop) and a badge with unique item count (`9+` cap). Clicking routes to `/checkout`.
- **File:** `frontend/src/components/layout/ServiceCart.vue` (`totalItems` `:40`, `totalAmount` `:43`, click→`/checkout` `:4`).

### 1.3 Checkout page (price calc + payment selection)
- **Name:** Checkout page
- **File:** `frontend/src/pages/CheckoutPage.vue`
- **What it does:** Lists cart items with quantity controls, computes discounts, collects promocode, selects payment method, collects guest email, enforces purchase-rules agreement, and dispatches the payment.
- **Price calculation (client preview):** (`CheckoutPage.vue:512-564`)
  - `subtotalPaid` = `productCartStore.totalAmount`.
  - `personalDiscountPercent` — from `authStore.user.personal_discount` (0 if unauthenticated, expired, or ≤0).
  - `personalDiscountAmount` = `subtotal * personal% / 100`; applied **first**.
  - `promoDiscountPercent` — from `promo.result.discount_percent` when promo type is `discount`.
  - `promoDiscountAmount` = `(subtotalAfterPersonalDiscount) * promo% / 100`; applied **after** personal discount.
  - `finalTotal` = `max(0, subtotalAfterPersonalDiscount - promoDiscountAmount)`.
  - `isZeroTotalWithServices` = `finalTotal === 0 && items.length > 0` → triggers the "free" path.
- **Promocode UX:** `usePromoStore` apply/clear with 500ms debounce (`onApply` `:910`, `onPrimaryPromoClick` `:944`). Applying a `discount` promo with an empty cart shows an info alert; `free_access` type is acknowledged but not implemented for products.
- **Payment methods** (`PaymentList.vue`): `card` (Mono), `crypto` (Cryptomus), `balance`. **Balance is hidden for guests** (`:hide-balance="!authStore.isAuthenticated"`, `CheckoutPage.vue:209`).
- **Guest email field:** Shown only when unauthenticated and cart non-empty; required + regex-validated before submit (`CheckoutPage.vue:219-237`, `:684-702`).
- **Purchase rules:** If enabled (`GET /purchase-rules`), the user must tick an agreement checkbox; otherwise checkout shakes the rules card and aborts (`:644-675`, `loadPurchaseRules` `:583`).
- **Dispatch (`handleSubmit` `:713`):**
  - zero-total → `buyFree()` (products free path is a stub; clears promo, refetches user, routes home).
  - `card` → `processMonoPayment()` → `POST /guest/mono/create-payment` (guest) or `POST /mono/create-payment` (auth). Redirects to the returned `data.url`.
  - `crypto` → `processCryptoPayment()` → `/guest/cryptomus/create-payment` or `/cryptomus/create-payment`. Redirects to `data.url`.
  - `balance` → `processBalancePayment()` → `POST /cart` with `payment_method: 'balance'`; on success clears cart, refetches user, routes to `/order-success`. Client pre-checks `user.balance >= finalTotal`.
- **Return handling:** On mount, if URL `?success=true` (Mono/Cryptomus redirect-back), it clears the cart + promo, shows a preparing-product loader, and routes to `/order-success` (`:629-641`).
- **Edge cases:** Double-submit guard (`isProcessingCheckout`, 500ms) `:650`; preloader intentionally stays visible until `/order-success` confirms delivery; insufficient-balance and validation errors surfaced via toasts.

### 1.4 Order success page
- **Name:** Order success / delivered-items page
- **File:** `frontend/src/pages/OrderSuccessPage.vue`
- **What it does:** After payment, fetches `GET /purchases` and renders delivered credentials per purchase; polls while a webhook may still be landing or while items are still `processing`.
- **Polling:**
  - **Order polling:** every 5s, up to `maxPollingAttempts = 12` (≈60s) until purchases appear; then shows a "waiting for payment confirmation" panel with a manual Refresh (`startPolling` `:709`).
  - **Status polling:** every 10s while any purchase is `processing` (manual delivery), stops when none remain (`startStatusPolling` `:741`).
  - Smart in-place diff updates (`updatePurchasesSmart` `:808`) and a toast when a `processing`→`completed` transition is detected (not on first load) (`:944-975`).
- **Per-purchase UI:**
  - `processing` items show a 3-stage progress bar (accepted → processing → ready), elapsed-time timer (`getProcessingDuration` `:1097`), and a "Contact manager" button that opens support chat (`contactManagerAboutOrder` `:1115`).
  - `completed` items show `account_data` (first 5, expandable), admin `processing_notes`, copy-to-clipboard, single + bulk download (client-side `Blob` download with a formatted header) (`downloadSingleAccount` `:1050`, `downloadAllAccounts` `:1066`).
- **Edge case:** Requires an auth token (`authStore.token`); redirects to `/login` if missing (`:897`). This page is therefore effectively for authenticated buyers (guests track orders via guest-email flows elsewhere).

---

## 2. Adding to cart — backend validation entry points

There is **no `POST /cart` "add" endpoint**; the cart lives in the browser. The two server endpoints below validate a full set of products and either charge immediately (balance) or hand off to a payment provider (guest). Both delegate stock/price validation to `ProductPurchaseService::prepareProductsData()`.

### 2.1 Shared product validation
- **Name:** `prepareProductsData`
- **File:** `backend/app/Services/ProductPurchaseService.php:24-71`
- **Inputs:** `array $productsRequest` of `{ id, quantity }`.
- **What it does (per item):** loads `ServiceAccount`; if missing → `Product not found`. Reads `getAvailableStock()`; if `available < quantity` → `Insufficient stock for {title}. Available: X, requested: Y`. Computes unit `price = getCurrentPrice()`, line `total = price*quantity`, accumulates `productsTotal`.
- **Output:** `['success'=>bool, 'data'=>[{product, quantity, price, total}], 'message'=>?, 'total'=>float]`.
- **Edge case:** Stock here is a **pre-check**; the authoritative check happens again under a row lock in `createMultiplePurchases` (§3.2).

---

## 3. Checkout flow & purchase creation

### 3.1 Balance checkout (authenticated only)
- **Name:** Cart store / balance purchase
- **Route:** `POST /cart` — `backend/routes/api.php:83` (inside `auth:sanctum` + `throttle:120,1`).
- **File:** `backend/app/Http/Controllers/CartController.php:17-260`
- **Validation:** `CartStoreRequest` (`Http/Requests/Cart/CartStoreRequest.php`): `products[]` of `{ id: exists:service_accounts,id, quantity: >=1 }`, `payment_method ∈ {credit_card, crypto, admin_bypass, free, balance}`, `promocode` nullable (required_if `payment_method=free`).
- **What it does (balance branch only — `payment_method === 'balance'`):**
  1. Empty-cart guard (`CartController.php:22`).
  2. Resolves user from bearer token via `getApiUser()` (`Controller.php:14`).
  3. Validates promocode via `PromocodeValidationService::validate()` (`:31`).
  4. `prepareProductsData()` → `productsTotal` (`:41`).
  5. **Discount stacking:** `personalDiscount = user->getActivePersonalDiscount()` + promo `discount_percent`; **capped at 99%** so final ≥ 1% (`:64-69`). Final amount `round(.,2)` then `max(.,0.01)` (`:72`).
  6. **Balance check:** if `user->balance < totalAmount` → 422 "Insufficient balance" (`:76`).
  7. **Atomic block** (`DB::beginTransaction`): deduct balance via `BalanceService::deduct(... TYPE_PURCHASE ...)` (creates BalanceTransaction + Transaction), then `createMultiplePurchases(productsData, user->id, null, 'balance')` (`:85-137`).
  8. **Integrity guards:** rolls back if no purchases created, or if `count(purchases) !== count(productsData)` (partial failure) → 500 (`:109-134`). Commit only when all succeed.
  9. **Promo usage** recorded in a separate `DB::transaction` with duplicate-`order_id` guard and `lockForUpdate` on the promocode (`:153-186`).
  10. **Post-response side effects:** in-app notification (`NotificationTemplateService::sendToUser ... 'purchase'`), admin notifier (`product_purchase`), and emails (`product_purchase_confirmation`, `payment_confirmation`) deferred via `register_shutdown_function` so the HTTP response is not blocked (`:200-250`).
- **Output:** `ApiResponse::success(['message'=>'Payment completed successfully'])`.
- **Business rules:** Only `balance` is accepted here; any other method → 422 "Only balance payment method is supported for products" (`:256`). Card/crypto for authed users go through their own provider controllers (`/mono/create-payment`, `/cryptomus/create-payment`) and the webhooks in §4.
- **Edge cases:** Full rollback on any throwable mid-transaction (balance restored). Race-safe because purchase creation locks each product row.

### 3.2 Multi-item purchase creation (core engine)
- **Name:** `createMultiplePurchases`
- **File:** `backend/app/Services/ProductPurchaseService.php:362-468`
- **Inputs:** `productsData[]`, `?userId`, `?guestEmail`, `paymentMethod='balance'`, `?promocode`, `?orderId`.
- **What it does:** Within one `DB::transaction`, for each item:
  - Resolves product id from `product->id` or `product_id`; throws if missing.
  - **Always re-locks the product:** `ServiceAccount::lockForUpdate()->find($productId)` — does not trust the passed object (`:385`).
  - **Re-checks stock after lock:** `getAvailableStock() < quantity` → throws `Insufficient stock for product {id}` (race protection) (`:394-404`).
  - Calls `createProductPurchase()` (§3.3) and collects the `Purchase`.
  - After the loop, records promo usage atomically via `recordPromocodeUsage()` (inside the same transaction) (`:424`).
- **Post-transaction (outside, deliberately):** invalidates `manual_delivery_pending_count` cache for manual items; sends admin + user "order created" notifications for manual items; runs `checkLowStockAndNotify()` per unique product (`:429-465`).
- **Output:** array of `Purchase` models.
- **Business rules / edge cases:**
  - Notifications/cache moved out of the transaction so they fire only after commit (counter/AJAX consistency).
  - `recordPromocodeUsage()` (`:520`): duplicate-`order_id` guard, locks promocode, increments `usage_count` (respects `usage_limit`; unlimited codes still increment for stats).
  - `checkLowStockAndNotify()` (`:473`): for automatic products only, sends a `low_stock` notifier when `available <= 5`, throttled to once/hour per product via cache key `low_stock_notified_{id}`.

### 3.3 Single purchase + delivery assignment
- **Name:** `createProductPurchase`
- **File:** `backend/app/Services/ProductPurchaseService.php:85-348`
- **Inputs:** `ServiceAccount $product`, `quantity`, `price`, `total`, `?userId`, `?guestEmail`, `paymentMethod`.
- **What it does (in its own `DB::transaction`):**
  - Re-locks the product if not freshly created (`:100`).
  - Normalizes `accounts_data` to an array (decodes JSON string if needed) (`:105-116`).
  - Determines `deliveryType`; `requiresManualDelivery = deliveryType === 'manual'` (`:130`).
  - **Automatic delivery branch** (`!requiresManualDelivery`, `:137-186`):
    - Optionally appends a per-locale suffix (`account_suffix_text_{ru|en|uk}`) when `account_suffix_enabled` (`:140-153`).
    - Selects credentials `accounts_data[used + i]` for `i in [0,quantity)`; throws "Insufficient accounts in product" if an index is missing (`:146-160`).
    - Sanity checks: non-empty and exact-count of assigned accounts (`:162-181`).
    - **Increments `product->used += quantity`** and saves — this is how automatic stock is consumed (`:184`).
  - **Manual delivery branch:** assigns **no** accounts; `used` is **not** incremented here (consumed later at admin processing, §6.1) (`:133-135`).
  - Creates the `Transaction` (`status='completed'`, `payment_method`, `service_account_id`, amount=`total`) (`:189`).
  - **Initial status:** `processing` for manual, `completed` for automatic (`:200`). `account_data` = assigned accounts (auto) or `[]` (manual) (`:205`).
  - Creates the `Purchase` with `order_number = Purchase::generateOrderNumber()` (`:210`).
  - Writes the first `PurchaseStatusHistory` row (old=null) with a system reason (`:225-231`).
  - Records a held `SupplierEarning` if the product has a `supplier_id` (locks supplier, computes share = `total * (100 - commission)/100`, `available_at = now + supplier_hold_hours||6`, dedupe guard) (`:264-341`). Failure is logged but does not abort the purchase.
- **Output:** `['transaction'=>Transaction, 'purchase'=>Purchase]`.
- **Edge cases:** All wrapped in a transaction; throwing rolls back the row-`used` increment and the purchase. Status-history write failure is caught and logged (does not break the purchase).

### 3.4 Order number generation
- **Name:** `generateOrderNumber`
- **File:** `backend/app/Models/Purchase.php:76-87`
- **Format:** `ORD-YYYYMMDD-NNNNN` (5-digit random, zero-padded), regenerated until unique. Edge case: collision-retry loop, theoretically unbounded under extreme volume per day.

---

## 4. Card / crypto checkout (guest + authed) via payment webhooks

Card and crypto purchases are **not** finalized in the cart controllers. The provider controllers create a payment session, then the provider webhook creates purchases on confirmation.

### 4.1 Guest order preparation
- **Name:** Guest cart store
- **Route:** `POST /guest/cart` — `backend/routes/api.php:69` (public group, `throttle:60,1`).
- **File:** `backend/app/Http/Controllers/GuestCartController.php:22-90`
- **Validation:** `guest_email` required|email; `products[]` `{id: exists, quantity>=1}`; `payment_method ∈ {card, crypto}` (no balance for guests); `promocode` nullable.
- **What it does:** Validates promocode (with `user_id=null`), runs `prepareProductsData`, applies **only** a promo `discount` percentage (no personal discount for guests), and returns `order_data` (email, products, products_data, total, promocode, promo_data) + `total_amount` + `currency` for the frontend to initiate payment. **No purchase is created yet.**
- **Output:** `{ success, order_data, total_amount, currency }`.
- **Note:** In practice the checkout page calls `/guest/mono/create-payment` or `/guest/cryptomus/create-payment` directly (this `store` is a validation/quote helper).

### 4.2 Guest purchase materialization
- **Name:** `createGuestPurchases` (static)
- **File:** `backend/app/Http/Controllers/GuestCartController.php:97-111`
- **What it does:** Thin wrapper → `ProductPurchaseService::createMultiplePurchases(productsData, userId=null, guestEmail, 'guest_purchase', promocode, orderId)`.
- **Called from:** `MonoController.php:799`, `CryptomusController.php:752` (guest webhooks).

### 4.3 Webhook purchase creation (Mono example; Cryptomus parallels it)
- **Files:** `backend/app/Http/Controllers/MonoController.php` (auth `handleUserWebhook` ~`:560-692`, guest `handleGuestWebhook` `:706-819`); analogous in `CryptomusController.php` (`:630`, `:752`).
- **What it does (per webhook):**
  - **Duplicate guard:** if a `Purchase` already exists for the `transaction_id`, returns "Already processed" (idempotency for repeated webhooks) (`MonoController.php:730-740`).
  - Marks the `Transaction` `completed` before creating purchases (`:746`).
  - **Re-validates each product under `lockForUpdate`:** skips missing products, skips items with `available < quantity`, and **recomputes price** with `getCurrentPrice()` (logs a warning if price changed since checkout) (`:751-787`).
  - If nothing valid remains → 400 "No valid products".
  - Auth path → `createMultiplePurchases(..., user->id, null, 'credit_card', promocode, invoiceId)`; guest path → `createGuestPurchases(guestEmail, validatedProductsData, promocode, invoiceId)`.
  - Sends confirmation emails/notifications.
  - **Always returns 200** even on internal failure, to prevent provider retries storming (`:690`).
- **Business rules / edge cases:**
  - Price is authoritative at webhook time, not checkout time (protects against stale client price).
  - Stock is re-validated a third time under lock; out-of-stock items are silently skipped (the buyer paid but an item may not be delivered — surfaced via logs, not auto-refunded here).
  - `promocode` + `invoiceId`/`orderId` passed through for atomic, dedup-safe usage recording.

---

## 5. Purchase lifecycle, statuses & state machine

### 5.1 Statuses (all of them)
Defined in `backend/app/Models/Purchase.php:92-97`:

| Const | Value | `getStatusText()` (RU) | Badge class | Meaning |
|---|---|---|---|---|
| `STATUS_PENDING` | `pending` | "В обработке" | `warning` | Defined but not used by the product flow (legacy/reserved). |
| `STATUS_PROCESSING` | `processing` | "В работе" | `primary` | Manual-delivery order awaiting admin fulfillment (also the "waiting stock" holding state). |
| `STATUS_COMPLETED` | `completed` | "Завершено" | `success` | Delivered — `account_data` populated. Terminal (happy path). |
| `STATUS_FAILED` | `failed` | "Ошибка" | `danger` | Defined; not set by the core cart/delivery flow. |
| `STATUS_CANCELLED` | `cancelled` | "Отменено" | `secondary` | User cancelled a `processing` order. Terminal. |
| `STATUS_REFUNDED` | `refunded` | "Возврат" | `info` | Set by the dispute/refund subsystem (outside this doc's core files). Terminal. |

**Helper predicates** (`Purchase.php:134-174`): `isProcessing()`, `isCompleted()`, `requiresManualProcessing()` (processing AND product is manual), `isWaitingStock()` (reads `is_waiting_stock` flag), and scope `pendingManualProcessing()` (status=processing AND `serviceAccount.delivery_type='manual'`).

### 5.2 Effective state machine

```
                 (automatic delivery)
Purchase created ───────────────────────────────► completed   [terminal]
       │            account_data filled instantly
       │
       │ (manual delivery)
       └──► processing ──► [admin processes] ──► completed     [terminal]
              │  ▲
              │  │ (stock missing at processing time)
              │  └── stays processing, is_waiting_stock=true ── stock returns ──► is_waiting_stock=false (still processing)
              │
              └──► cancelled  (user cancels their own processing order)   [terminal]
```

- There is **no** automatic transition out of `processing` except admin action; `cancelled`/`completed` are reached explicitly.
- `is_waiting_stock` is an **orthogonal boolean flag** on a `processing` purchase, not a separate status. While true, the order is excluded from overdue notifications (§7.2) and is re-evaluated by the waiting-stock cron (§7.1).

### 5.3 Status history (audit log)
- **Name:** Purchase status history
- **File:** `backend/app/Models/PurchaseStatusHistory.php` (table `purchase_status_history`)
- **Fields:** `purchase_id`, `old_status`, `new_status`, `changed_by` (User id or null=system), `reason`, `metadata` (JSON cast).
- **Writer:** static `createHistory(purchase, newStatus, oldStatus?, changedBy?, reason?, metadata?)` (`:54-74`). Default metadata captures `account_data_count`, `quantity`, `total_amount`.
- **Transitions recorded:**
  - Creation (system) — `ProductPurchaseService::createProductPurchase` (`ProductPurchaseService.php:225`).
  - Processing→Completed (admin) — `ManualDeliveryService::processPurchase` (`ManualDeliveryService.php:138`).
  - Stock-missing holding event — same status (processing), reason "Товар отсутствует, заказ переведен в ожидание", metadata flags `is_waiting_stock` (`ManualDeliveryService.php:90`).
  - Processing→Cancelled (user) — `ManualDeliveryService::cancelProcessingOrder` (`ManualDeliveryService.php:247`).
- **Read endpoints:** embedded (last 5) in `GET /purchases`; full list via `GET /purchases/{id}/status-history` (§8.5).

---

## 6. Automatic delivery (stock items) — concurrency

Covered mechanically in §3.3 (auto branch). Key concurrency/inventory points:

- **Stock definition** (`ServiceAccount::getAvailableStock()`, `ServiceAccount.php:285-320`):
  - Manual products: returns `999` if `is_active` else `0` (treated as effectively unlimited; `is_active` is the gate).
  - Automatic products: `max(0, totalQty - used)` where `totalQty` = `count(accounts_data)` (or pre-computed `total_qty_from_json` from a SQL `JSON_LENGTH`).
- **Consumption:** automatic delivery increments `used` (`ProductPurchaseService.php:184`); manual delivery increments `used` only at admin processing (`ManualDeliveryService.php:117-119`).
- **Locking / race protection (three layers):**
  1. UI clamp on `max_quantity` (advisory only).
  2. Pre-check in `prepareProductsData` (advisory).
  3. **Authoritative:** `ServiceAccount::lockForUpdate()` + post-lock stock recheck inside `createMultiplePurchases` and again in `createProductPurchase`; webhooks also lock per product. Two concurrent buyers cannot both consume the last unit — the second throws `Insufficient stock` and the transaction rolls back.
- **`AssignServiceAccount` service** (`backend/app/Services/AssignServiceAccount.php`): a **separate, legacy/subscription-era** assignment path (`assignToUser(serviceId, user)`). It picks an active, non-expiring `ServiceAccount` by `service_id`, using `DB::transaction` + `lockForUpdate` + `orderBy('used','asc')`, inserts into `user_service_accounts`, and `increment('used')`. It is **not** invoked by the current product cart/checkout flow (no callers in the cart/purchase path) but demonstrates the same lock-then-increment concurrency pattern; documented here for completeness.

---

## 7. Out-of-stock "waiting" handling & scheduled jobs

### 7.1 Process waiting-stock orders
- **Name:** `process:waiting-stock-orders`
- **File:** `backend/app/Console/Commands/ProcessWaitingStockOrders.php`
- **Schedule:** every 30 minutes (`Console/Kernel.php:30`).
- **What it does:** Finds purchases with `status=processing AND is_waiting_stock=true`. For each, reloads the product; if `getAvailableStock() >= quantity`, it **notifies the manager** (`stock_available_for_order` notifier) and **clears `is_waiting_stock`** (the order stays `processing` for the admin to fulfill manually) and invalidates `manual_delivery_pending_count` cache. Otherwise it remains waiting.
- **Outputs:** console counts (`processed`, `still waiting`).
- **Edge case:** It does **not** auto-deliver; it only flips the flag and pings a human. Missing product is logged and skipped.

### 7.2 Notify overdue manual orders
- **Name:** `notify:overdue-manual-orders`
- **File:** `backend/app/Console/Commands/NotifyOverdueManualOrders.php`
- **Schedule:** hourly (`Console/Kernel.php:27`).
- **What it does:** Finds `processing` orders with `is_waiting_stock=false`, `created_at < now-24h`, and (`last_reminder_at` null OR `< now-24h`). Sends a `overdue_manual_order` notifier whose priority escalates by age (`info` ≥1d, `warning` ≥2d, `error` ≥3d with a "КРИТИЧНО" suffix), then stamps `last_reminder_at = now`.
- **Outputs:** console counts.
- **Edge cases:** Waiting-stock orders are intentionally **excluded** (manager can't fulfill them anyway). Re-notifies at most once per 24h per order.

### 7.3 Test purchase generator
- **Name:** `test:create-purchase {user_id} {--amount} {--product-title}`
- **File:** `backend/app/Console/Commands/CreateTestPurchase.php`
- **What it does:** Dev/QA helper. Creates an admin-owned `ServiceAccount` with sample `accounts_data`, a `completed` `Transaction` of type `purchase`, and increments `used`. **Note:** it creates a `Transaction` (not a `Purchase` row) — intended for testing the disputes/refund UI, not the purchase-delivery UI.

---

## 8. Purchases API (read, download, cancel, stats, history)

All under `auth:sanctum` + `throttle:120,1` (`api.php:95-100`). Controllers also support **guest access** via a `guest_email` query param + `PurchasePolicy` checks. Auth is resolved in `index` via `$request->user()`.

### 8.1 List purchases — `GET /purchases`
- **File:** `backend/app/Http/Controllers/Api/PurchaseController.php:16-128`; request `Http/Requests/Purchase/PurchaseIndexRequest.php` (validates `date_from`, `date_to`, `status`).
- **Inputs:** auth user OR `guest_email` query; filters `date_from`, `date_to`, `status`; `per_page` (default 50).
- **What it does:** Eager-loads `serviceAccount` (trimmed cols), `transaction` (+ `dispute`), and last-5 `statusHistory` (with `changedBy`). Scopes to `user_id` (auth) or `whereNull(user_id) AND guest_email=...` (guest). Returns a mapped array incl. `account_data`, `status`, dispute summary, and embedded `status_history`.
- **Output:** `ApiResponse::success({ purchases, meta:{current_page,last_page,per_page,total} })`.
- **Edge case:** Without user and without `guest_email` → 401; invalid guest email format → 422. Guest ownership relies on knowing the email (a commented-out TODO notes a stronger session check is not enforced).

### 8.2 Show one — `GET /purchases/{id}`
- **File:** `PurchaseController.php:130-179`. Loads full relations; authorizes via `Gate::allows('view', [purchase, guestEmail])` → 403 if denied. Returns full purchase incl. product description and full `status_history`.

### 8.3 Download — `GET /purchases/{id}/download`
- **File:** `PurchaseController.php:181-205`. Authorizes via `download` ability (= `view`). Concatenates `account_data` lines into a `.txt` (`purchase_{order_number}_{date}.txt`) with no-cache headers. If `account_data` empty → body "Нет данных для скачивания". (Frontend also offers a purely client-side download from already-fetched data.)

### 8.4 Cancel — `POST /purchases/{id}/cancel`
- **File:** `PurchaseController.php:211-254`.
- **Inputs:** `cancellation_reason` required string, 10–500 chars; auth user or `guest_email`.
- **What it does:** Authorizes via `view` ability, builds a temp `User` for guests (email-bearing), then calls `ManualDeliveryService::cancelProcessingOrder()`.
- **Output:** `{ success, message, purchase }` or 400 with the exception message.
- **Business rules (in the service, `ManualDeliveryService.php:211-294`):** only `processing` orders can be cancelled; ownership enforced (auth via `user_id`, guest via case-insensitive `guest_email` match). Sets status `cancelled`, writes status history, invalidates the pending-count cache, and notifies admins (`manual_delivery_cancelled`). **Refund is a documented TODO** — cancellation does **not** currently refund money (see §9).
- **Edge cases:** Cancelling a `completed` or already-`cancelled` order throws "Order cannot be cancelled". `PurchasePolicy::cancel()` additionally encodes the processing-only rule.

### 8.5 Status history — `GET /purchases/{id}/status-history`
- **File:** `PurchaseController.php:287-322`. Authorizes via `view`. Returns the **full** history (no limit), including `metadata`, ordered newest-first.

### 8.6 Processing stats — `GET /purchases/stats/processing`
- **File:** `PurchaseController.php:260-281`. **Auth-only** (401 for guests). Returns `average_processing_time_hours` from `ManualDeliveryService::getAverageProcessingTime()` (`ManualDeliveryService.php:340-357`): mean of `created_at → processed_at` (hours) across all `completed`, admin-processed purchases; `null` if none.

### 8.7 Authorization policy
- **File:** `backend/app/Policies/PurchasePolicy.php`. `view`/`download`: owner (auth) or matching `guest_email`. `update`: owner only. `delete`: **always false** (users can't delete). `cancel`: processing-only + `view`.

---

## 9. Refunds (pointer)

- **Cart/checkout core does not implement refunds.** `ManualDeliveryService::cancelProcessingOrder()` contains an explicit `TODO: Возврат средств через Transaction` (`ManualDeliveryService.php:289`) — user cancellation changes status only.
- The `refunded` purchase status and actual money movement live in the **disputes/refund subsystem** (e.g. `ProductDisputeController`, admin dispute handling, `disputes:auto-close` cron in `Console/Kernel.php:33`), which is outside this domain's files. `CreateTestPurchase` exists specifically to seed data for that flow.

---

## 10. Manual delivery — admin processing

### 10.1 Service: process a manual order
- **Name:** `ManualDeliveryService::processPurchase`
- **File:** `backend/app/Services/ManualDeliveryService.php:31-201`
- **Inputs:** `Purchase`, `User $admin`, `array $accountData`, `?notes`.
- **Pre-validation (before transaction):** status must be `processing`; product must be manual-delivery; `count(accountData) === purchase->quantity`; no empty account string (`:38-59`).
- **What it does (in `DB::transaction`):**
  - **Locks the purchase** (`lockForUpdate`) and re-checks status post-lock; clears any prior `processing_error` (`:62-74`).
  - **Locks the product** and checks `getAvailableStock() >= quantity`. If short → sets `is_waiting_stock=true`, writes a status-history "waiting" event, invalidates pending-count cache, and **returns early still `processing`** (does not throw) (`:77-104`).
  - If stock OK: clears `is_waiting_stock`, **increments `product->used += quantity`** (`:107-119`).
  - Updates the purchase: `status=completed`, `account_data=accountData`, `processed_by=admin->id`, `processed_at=now`, `processing_notes=notes`, `is_waiting_stock=false` (`:125-132`).
  - Writes status-history `processing→completed` (reason "Заказ обработан менеджером", metadata with account count) (`:138-149`).
- **Post-transaction notifications:** completed → `notifyUserAboutDelivery`; waiting → `notifyUserAboutOutOfStock` + `notifyAdminAboutOutOfStock` (`:171-179`).
- **Error path:** on any throwable, records `processing_error` on the purchase, notifies the user of the error (`manual_delivery_processing_error`), logs, and rethrows (`:180-200`).
- **Outputs:** the fresh `Purchase`.
- **Edge cases:** Idempotency/double-process protection via row lock + post-lock status recheck; out-of-stock at processing converts to a non-throwing "waiting" hold; notifications never break the transaction (wrapped in try/catch).

### 10.2 Admin controller
- **File:** `backend/app/Http/Controllers/Admin/ManualDeliveryController.php`
- **Routes** (`routes/web.php`, admin group; statistics/count for all admins, the rest gated by `admin.main`):
  - `GET admin/manual-delivery/count` → `getPendingCount` (`web.php:100`): count of `processing` manual orders (badge). 
  - `GET admin/manual-delivery/statistics` → `statistics` (`web.php:101`).
  - `GET admin/manual-delivery` → `index` (`web.php:105`): paginated (50) list with rich filters — status (`all`/`processing`/`completed`), delivery_type, date range, customer email/id, order_number, sort (`created_at`/`total_amount`/`quantity`/`processing_time`). Always restricted to manual-delivery products.
  - `GET admin/manual-delivery/{purchase}` → `show` (`web.php:106`): redirects away if `!requiresManualProcessing()`.
  - `POST admin/manual-delivery/{purchase}/process` → `process` (`web.php:107`).
- **`process` specifics** (`ManualDeliveryController.php:132-193`):
  - Validates `account_data[]` (each required non-empty string), optional `processing_notes`/`admin_notes` (≤1000). Enforces `count(account_data) === purchase->quantity`.
  - **Does not strip tags** (preserves passwords/configs) — only `trim()` (`:149-152`).
  - Wraps in `DB::transaction` + `lockForUpdate` + status recheck ("Заказ уже был обработан" if not processing), calls `processPurchase`, and saves `admin_notes` separately (`:156-176`).
  - Redirects with success/error flash.
- **Statistics** (`ManualDeliveryService::getStatistics()`, `:319-333`): `pending`, `processed_today`, `processed_this_week`, `average_processing_time`.

### 10.3 Admin purchases resource (auxiliary)
- **Routes:** `Route::resource('purchases')->only(['index','show','destroy'])` (`web.php:97`) → `Admin\PurchaseController`. Provides admin browsing and `destroy` (soft delete — `Purchase` uses `SoftDeletes`). Distinct from the buyer-facing API in §8.

---

## 11. Notifications & cache map (delivery side effects)

| Event | Channel(s) | Where |
|---|---|---|
| Manual order created | admin `admin_manual_delivery_new_order`; user `manual_delivery_order_created` | `ManualDeliveryService::notifyAdminAboutNewOrder` / `notifyUserAboutOrderCreated`, fired from `createMultiplePurchases` post-commit |
| Manual order delivered | user `manual_delivery_completed` (in-app + email/guest email) | `notifyUserAboutDelivery` |
| Out of stock at processing | user `manual_delivery_out_of_stock`; admin notifier (priority `error`) | `notifyUserAboutOutOfStock` / `notifyAdminAboutOutOfStock` |
| Stock returned for waiting order | admin `stock_available_for_order` | `ProcessWaitingStockOrders` |
| Overdue manual order | admin `overdue_manual_order` (escalating priority) | `NotifyOverdueManualOrders` |
| Manual order cancelled by user | admin `manual_delivery_cancelled` | `cancelProcessingOrder` |
| Processing error | user `manual_delivery_processing_error` | `notifyUserAboutProcessingError` |
| Low stock (automatic) | admin `low_stock` (≤5, throttled 1/h) | `checkLowStockAndNotify` |
| Purchase confirmation (balance/card) | user in-app `purchase`; emails `product_purchase_confirmation`, `payment_confirmation`; admin `product_purchase` | `CartController`, Mono/Cryptomus webhooks |

**Cache key `manual_delivery_pending_count`** is invalidated on: manual purchase creation, processing, out-of-stock hold, waiting-stock flag clear, and cancellation — keeping the admin badge counter accurate.

**Observers:** No `PurchaseObserver` exists. The only delivery-relevant observer is `ServiceAccountObserver` (`backend/app/Observers/ServiceAccountObserver.php`), which clears `active_accounts_list*` product-listing caches on ServiceAccount create/update/delete/restore — relevant because editing stock/products refreshes catalog caches. Registered in `AppServiceProvider`/`EventServiceProvider`.

---

## 12. Key file index

| Concern | Path |
|---|---|
| Balance checkout | `backend/app/Http/Controllers/CartController.php` |
| Guest quote / materialize | `backend/app/Http/Controllers/GuestCartController.php` |
| Buyer purchases API | `backend/app/Http/Controllers/Api/PurchaseController.php` |
| Purchase model + statuses | `backend/app/Models/Purchase.php` |
| Status history model | `backend/app/Models/PurchaseStatusHistory.php` |
| Product/stock model | `backend/app/Models/ServiceAccount.php` |
| Purchase engine + auto delivery | `backend/app/Services/ProductPurchaseService.php` |
| Legacy assignment service | `backend/app/Services/AssignServiceAccount.php` |
| Manual delivery service | `backend/app/Services/ManualDeliveryService.php` |
| Admin manual delivery | `backend/app/Http/Controllers/Admin/ManualDeliveryController.php` |
| Waiting-stock cron | `backend/app/Console/Commands/ProcessWaitingStockOrders.php` |
| Overdue cron | `backend/app/Console/Commands/NotifyOverdueManualOrders.php` |
| Test purchase seeder | `backend/app/Console/Commands/CreateTestPurchase.php` |
| Authorization | `backend/app/Policies/PurchasePolicy.php` |
| Routes (API) | `backend/routes/api.php` |
| Routes (admin web) | `backend/routes/web.php` |
| Cron schedule | `backend/app/Console/Kernel.php` |
| Frontend cart store | `frontend/src/stores/productCart.ts` |
| Checkout page | `frontend/src/pages/CheckoutPage.vue` |
| Order success page | `frontend/src/pages/OrderSuccessPage.vue` |
| Payment selector | `frontend/src/components/checkout/PaymentList.vue` |
| Header cart widget | `frontend/src/components/layout/ServiceCart.vue` |
| Card/crypto webhooks | `backend/app/Http/Controllers/MonoController.php`, `CryptomusController.php` |
