# 04 — Payments, Balance, Transactions, Withdrawals, Vouchers, Promocodes, Supplier Earnings

Functional inventory for the money-handling domain of **Account Arena** (Laravel API + Vue SPA).

This document is exhaustive at the feature level: each entry lists name, behavior, route/method, implementing file(s) with `path:line`, inputs, outputs, business rules, and edge cases. All paths are absolute.

---

## 0. Cross-cutting concepts

### 0.1 Two ledgers: `BalanceTransaction` vs `Transaction`

The system keeps **two parallel ledgers**:

- **`BalanceTransaction`** (`backend/app/Models/BalanceTransaction.php`) — the authoritative internal-balance ledger. Every credit/debit of `User.balance` writes exactly one row recording `amount` (signed: positive for credit, negative for debit), `balance_before`, `balance_after`, `type`, `status`, `description`, `metadata`.
- **`Transaction`** (`backend/app/Models/Transaction.php`) — the older/general "transactions" table. It is used for two distinct purposes:
  1. **Payment-intent tracking** for external providers (Cryptomus / Monobank): a `pending` row is created at payment creation and flipped to `completed` in the webhook. Provider lookup keys (`order_id`, `invoice_id`) live in its `metadata` JSON.
  2. **Compatibility mirror**: `BalanceService` writes a second `Transaction` row alongside every `BalanceTransaction` (see `BalanceService::topUp`/`deduct`), so the legacy `GET /transactions` list stays populated.

> Consequence: a single balance top-up produces **1 `BalanceTransaction` + at least 2 `Transaction` rows** (the original pending payment-intent that gets flipped to completed, plus the compatibility mirror created by `BalanceService`). This is a known design quirk worth keeping in mind when reconciling.

### 0.2 `User.balance` is the source of truth for spendable balance

The actual spendable customer balance is the scalar column `User.balance`. `BalanceTransaction` is an append-only history/audit trail; it is **not** summed to compute balance. `BalanceService` mutates `User.balance` directly under a row lock and records the before/after in the ledger row.

Supplier money is a **separate** scalar: `User.supplier_balance` (withdrawable), fed from `SupplierEarning` rows.

### 0.3 Authentication helper

Most payment endpoints resolve the user via `Controller::getApiUser()` — `backend/app/Http/Controllers/Controller.php:14`. It reads the Bearer token, looks it up via `Laravel\Sanctum\PersonalAccessToken::findToken`, and returns the tokenable user or `false`. Routes are additionally protected by `auth:sanctum` middleware and rate limits (see §8).

### 0.4 Currency

The active currency is a global option: `Option::get('currency', 'USD')`. Monobank needs ISO-4217 numeric codes; `MonoPaymentService::getCurrencyCode()` maps `UAH→980, USD→840, EUR→978, GBP→826, PLN→985` (fallback 980/UAH).

---

## 1. Internal Balance System

### 1.1 Get current balance

- **What**: Returns the authenticated user's spendable balance, currency, and a formatted string.
- **Route**: `GET /api/balance/` — `backend/routes/api.php:107`
- **Impl**: `BalanceController::getBalance` — `backend/app/Http/Controllers/BalanceController.php:39`; reads via `BalanceService::getBalance` — `backend/app/Services/BalanceService.php:267`
- **Inputs**: Bearer token only.
- **Outputs**: `{ success, data: { balance, currency, formatted } }` (e.g. `"12.50 USD"`).
- **Business rules**: `getBalance` returns `round(User.balance ?? 0, 2)`.
- **Edge cases**: Unauthenticated → 401 `Требуется авторизация`. Null balance treated as 0.

### 1.2 Balance history (ledger)

- **What**: Lists the user's `BalanceTransaction` rows, newest first, with optional type/status filters.
- **Route**: `GET /api/balance/history` — `backend/routes/api.php:108`
- **Impl**: `BalanceController::getHistory` — `backend/app/Http/Controllers/BalanceController.php:66`; validation `backend/app/Http/Requests/Balance/BalanceHistoryRequest.php`
- **Inputs** (query): `limit` (1–100, default 50), `type` (one of `topup_card,topup_crypto,topup_admin,topup_voucher,deduction,refund,purchase,adjustment`), `status` (`pending,completed,failed,cancelled`).
- **Outputs**: `{ history: [{ id, type, type_name, amount, formatted_amount, balance_before, balance_after, status, status_name, description, created_at, metadata }], total }`.
- **Business rules**: Queries `BalanceTransaction` directly (not via service). `type_name`/`status_name`/`formatted_amount` are model accessors (`BalanceTransaction.php:108–147`). `formatted_amount` prefixes `+` for non-negative amounts.
- **Edge cases**: Unauthenticated → 401. `total` is the count of the (limited) returned page, not the full history count.

### 1.3 Check sufficient funds

- **What**: Tells the client whether the user's balance covers a given amount and computes any shortage.
- **Route**: `POST /api/balance/check-funds` — `backend/routes/api.php:109`
- **Impl**: `BalanceController::checkSufficientFunds` — `backend/app/Http/Controllers/BalanceController.php:125`; `BalanceService::hasSufficientFunds` — `backend/app/Services/BalanceService.php:279`; validation `backend/app/Http/Requests/Balance/CheckFundsRequest.php` (`amount` required numeric ≥ 0.01)
- **Inputs**: `{ amount }`.
- **Outputs**: `{ has_sufficient_funds, current_balance, required_amount, shortage }`. `shortage = 0` when funds suffice, else `round(amount - balance, 2)`.
- **Business rules**: `amount` is rounded to 2 dp before comparison. `hasSufficientFunds` = `balance >= amount`.
- **Edge cases**: This is advisory only — the real authoritative check happens again inside the locked DB transaction at purchase time (`BalanceService::deduct`).

### 1.4 Balance statistics (last 30 days)

- **What**: Aggregates completed balance activity over the last 30 days.
- **Route**: `GET /api/balance/statistics` — `backend/routes/api.php:110`
- **Impl**: `BalanceController::getStatistics` — `backend/app/Http/Controllers/BalanceController.php:157`
- **Inputs**: Bearer token only.
- **Outputs**: `{ period: '30_days', statistics: { current_balance, total_top_ups, total_deductions, total_refunds, transactions_count, top_ups_count, deductions_count }, currency }`.
- **Business rules**: Considers only `status='completed'` `BalanceTransaction` rows from the last 30 days. Top-ups = sum of `topup_card|topup_crypto|topup_admin|topup_voucher`. Deductions = `abs(sum(deduction|purchase))`. Refunds = sum of `refund`.
- **Edge cases**: Unauthenticated → 401.

### 1.5 Credit balance (top-up primitive)

- **What**: Atomically increases `User.balance` and writes ledger rows. Used by all top-up flows (card, crypto, voucher, admin).
- **Impl**: `BalanceService::topUp(User, float $amount, string $type, array $metadata = [])` — `backend/app/Services/BalanceService.php:54`
- **Inputs**: user, positive amount, type constant, metadata (commonly `invoice_id` or `order_id`).
- **Outputs**: the created `BalanceTransaction`, or the **existing** one if a duplicate was detected (idempotency), or throws on invalid input.
- **Business rules**:
  - Throws `InvalidArgumentException` if `amount <= 0`.
  - Rounds amount to 2 dp.
  - **Idempotency**: if metadata has `invoice_id` or `order_id`, calls `findDuplicateTransaction` (`BalanceService.php:291`) — searches the **last 24h** of completed `BalanceTransaction` rows by matching `JSON_EXTRACT(metadata,'$.invoice_id')` / `$.order_id`. On match returns the existing row (no double credit).
  - Runs inside `DB::transaction` with `User::lockForUpdate()` to prevent race conditions.
  - Guards against a resulting negative balance.
  - Creates the `BalanceTransaction` (amount positive) **and** a mirror `Transaction` (status `completed`, `payment_method` mapped via `mapTypeToPaymentMethod`, `BalanceService.php:347`).
  - Notifies via `Log::info`.
- **Edge cases**: Duplicate detection is time-bounded to 24h — a webhook retried after >24h could double-credit (mitigated upstream by webhook-level `Purchase`/transaction-status checks for purchases, but top-ups rely on this 24h window).

### 1.6 Debit balance (deduction primitive)

- **What**: Atomically decreases `User.balance` (purchases, admin adjustments).
- **Impl**: `BalanceService::deduct(User, float $amount, string $type = TYPE_DEDUCTION, array $metadata = [])` — `backend/app/Services/BalanceService.php:159`
- **Inputs**: user, positive amount, type, metadata.
- **Outputs**: created `BalanceTransaction` (amount stored **negative**), or throws.
- **Business rules**:
  - Throws `InvalidArgumentException` if `amount <= 0`.
  - Inside `DB::transaction` + `User::lockForUpdate()`: re-reads balance, throws `Недостаточно средств на балансе` if `balance < amount` (authoritative funds check), guards against negative result, saves, writes `BalanceTransaction` with `amount = -amount` plus mirror `Transaction`.
- **Edge cases**: `deduct` has **no** idempotency guard (unlike `topUp`); callers must wrap in their own atomic flow (CartController does — see §1.7).

### 1.7 Pay for purchase with internal balance (checkout via balance)

- **What**: Buyer pays for cart items using their internal balance.
- **Route**: `POST /api/cart` — `backend/routes/api.php:83` (auth:sanctum)
- **Impl**: `CartController::store` — `backend/app/Http/Controllers/CartController.php` (balance branch begins ~line 40; deduction at `:92`)
- **Inputs**: `{ products: [{id, quantity}], promocode? }`, payment method `balance`.
- **Outputs**: success with purchases / order number, or 422 on insufficient balance / validation.
- **Business rules**:
  - Computes total via `ProductPurchaseService::prepareProductsData`.
  - Applies **personal discount** (`User::getActivePersonalDiscount`, `User.php:351`) + **promo discount**, capped: `min(99, personal% + promo%)` so the buyer always pays ≥ 1% (`CartController.php:62–69`).
  - Total floored to `max(round(total,2), 0.01)`.
  - Pre-check `balance < total` → 422 (advisory).
  - Wraps deduction + purchase creation in a single `DB::beginTransaction`; rolls back if no purchases created or count mismatch.
  - `deduct(..., TYPE_PURCHASE, {products_count, payment_method:'balance'})` then `createMultiplePurchases(..., 'balance')`.
  - Records promocode usage in a separate idempotent transaction keyed by `order_id = 'balance_'.orderNumber` (`CartController.php:152–169`).
- **Edge cases**: Authoritative balance check happens inside `deduct` under lock (the pre-check can be stale). Mismatch between created-purchase count and product count forces rollback.

---

## 2. Cryptomus Crypto Payments

SDK: `FunnyDev\Cryptomus\CryptomusSdk`. Config `backend/config/cryptomus.php` (`merchant_uuid`, `payment_key`, `payout_key`).

### 2.1 Create payment — authenticated product purchase

- **Route**: `POST /api/cryptomus/create-payment` — `backend/routes/api.php:116` (auth:sanctum, `throttle:10,1`)
- **Impl**: `CryptomusController::createPayment` — `backend/app/Http/Controllers/CryptomusController.php:30`
- **Inputs**: `{ products: [{id (exists:service_accounts), quantity≥1}], promocode? }` + Bearer token.
- **Outputs**: `{ success, url }` (Cryptomus hosted-payment URL) or 422/500.
- **Business rules**:
  - `ProductPurchaseService::prepareProductsData` validates stock/prices.
  - Promo validated via `PromocodeValidationService::validate(code, user->id)`; only `type==='discount'` reduces total.
  - Discounts combined and capped at 99% (personal + promo), total floored to ≥ 0.01.
  - `orderId = 'order_'.userId.'_'.time()`.
  - Calls `sdk->create_payment(orderId, total, currency, '', '', return=/checkout, callback=/api/cryptomus/webhook, success=/order-success)`.
  - On success creates a **pending** `Transaction` with `metadata.order_id`, `payment_type='user'`, `user_id`, `products_data` (id/qty/price/total snapshot), optional `promocode`.
- **Edge cases**: SDK throws → 500. Stock/price snapshot stored in metadata is **re-validated at webhook time** (price/stock may change).

### 2.2 Create guest payment

- **Route**: `POST /api/guest/cryptomus/create-payment` — `backend/routes/api.php:71` (public, `throttle:?` via public group)
- **Impl**: `CryptomusController::createGuestPayment` — `backend/app/Http/Controllers/CryptomusController.php:156`
- **Inputs**: `{ guest_email (email), products[], promocode? }`.
- **Outputs**: `{ success, url }`.
- **Business rules**:
  - Per-item stock check + current price (`getAvailableStock`, `getCurrentPrice`).
  - Promo validated with `userId = null` (**no per-user limit** for guests — see §5.4).
  - Guest promo discount is integer percent applied to total.
  - `orderId = 'guest_order_'.time().'_'.md5(email)`.
  - Pending `Transaction` with `user_id=null`, `guest_email`, `payment_type='guest'`, `products_data`, optional `promocode`.
- **Edge cases**: Email lowercased/trimmed. No authentication.

### 2.3 Create top-up payment (crypto)

- **Route**: `POST /api/cryptomus/topup` — `backend/routes/api.php:121` (auth:sanctum, `throttle:10,1`)
- **Impl**: `CryptomusController::createTopUpPayment` — `backend/app/Http/Controllers/CryptomusController.php:271`
- **Inputs**: `{ amount: numeric 1..100000 }` + token.
- **Outputs**: `{ success, url, order_id }`.
- **Business rules**:
  - Amount rounded to 2dp; min 1.
  - `orderId = 'topup_crypto_'.userId.'_'.time().'_'.bin2hex(random_bytes(4))` (collision-resistant).
  - Pending `Transaction` with `payment_type='topup'`, `user_id`, `amount` in metadata.
- **Edge cases**: Unauthorized → 401 (logged with IP). SDK failure → 422/500.

### 2.4 Cryptomus webhook

- **Route**: `POST /api/cryptomus/webhook` — `backend/routes/api.php:126`. Middleware: `verify.webhook:cryptomus` + `throttle:100,1`.
- **Impl**: `CryptomusController::webhook` — `backend/app/Http/Controllers/CryptomusController.php:368`
- **Signature verification**: `VerifyWebhookSignature::verifyCryptomusSignature` — `backend/app/Http/Middleware/VerifyWebhookSignature.php:55`. The `sign` field is in the **body**. Expected = `md5(base64_encode(json_encode($data_without_sign, JSON_UNESCAPED_UNICODE)) . payment_key)`, compared with `hash_equals`. Missing key/sign → reject 403. (Verification can be globally disabled via `config('app.verify_webhooks_enabled')`.)
- **Note**: There is **also** a separate `CryptomusMiddleware` (`backend/app/Http/Middleware/CryptomusMiddleware.php`) that IP-allowlists `91.227.144.54` (X-Forwarded-For / X-Real-Ip / `ip()`) and aborts 403 otherwise — an alternative/legacy guard, not the one wired onto this route (the route uses `verify.webhook`).
- **Flow**:
  1. Parse JSON; missing/invalid JSON or missing `order_id` → return 200 (success envelope) to stop retries.
  2. `sdk->read_result($data)`; if not status-true → log "not completed", return 200.
  3. Look up `Transaction` by `JSON_EXTRACT(metadata,'$.order_id')`. Not found / no metadata / missing `payment_type` → 200.
  4. **Idempotency for purchases**: for `payment_type ∈ {user, guest}`, if a `Purchase` already exists for `transaction_id`, mark transaction `completed` (if not already) and return "Already processed".
  5. Route by `payment_type`: `topup → handleTopUpWebhook`, `guest → handleGuestWebhook`, `user → handleUserPurchaseWebhook`, else `handleUnknownPaymentType`.
- **`handleTopUpWebhook`** (`:455`): validates `user_id`/amount, sets transaction `completed`, then `BalanceService::topUp(..., TYPE_TOPUP_CRYPTO, {order_id, payment_method:'cryptomus', cryptocurrency, network, ...})`. Idempotency via `BalanceService` order_id dedup. Admin notified via `NotifierService::send('balance_topup', ...)`.
- **`handleUserPurchaseWebhook`** (`:536`) / **`handleGuestWebhook`** (`:663`): re-lock each product (`lockForUpdate`), re-check stock (skip item if insufficient), use **current price** (logs if price drifted >0.01), then create purchases (`ProductPurchaseService::createMultiplePurchases` for users / `GuestCartController::createGuestPurchases` for guests), passing `promocode` + `orderId` for atomic usage recording. Sends buyer email + admin notification.
- **Outputs**: Always returns a **200 success envelope** (even on most internal failures) to prevent Cryptomus retries; failures are logged for manual reconciliation.
- **Edge cases**: Duplicate webhooks deduped by existing `Purchase`/`order_id`. If all products fail validation → logs and returns 200 (no purchase). Transaction status is flipped to `completed` **before** purchase creation inside the handlers; a later failure leaves transaction `completed` but no `Purchase` (logged).

---

## 3. Monobank Payments

Service: `MonoPaymentService` (`backend/app/Services/MonoPaymentService.php`). Config `backend/config/monobank.php` (`token`, `public_key`).

### 3.1 Invoice creation primitive

- **Impl**: `MonoPaymentService::createInvoice(float $amount, string $webhookUrl, array $options)` — `MonoPaymentService.php:61`
- **Behavior**: Builds the Monobank `invoice/create` request:
  - `amount` in **minor units**: `(int)round($amount*100)`.
  - `ccy` from `getCurrencyCode()` (Option currency → ISO code) unless overridden.
  - `webHookUrl` (capital W per Mono docs), `redirectUrl`/`successUrl`/`failUrl`, `validity` (default 86400 s = 24h), `paymentType` (`debit`|`hold`, default `debit`), `reference` (default `uniqid('order_',true)`), optional `merchantPaymInfo`, `qrId`, `code`, `agentFeePercent`, `tipsEmployeeId`, `displayType=iframe`.
  - POST to `https://api.monobank.ua/api/merchant/invoice/create` with header `X-Token: <token>` (`makeRequest`, `:163`).
- **Outputs**: array (`invoiceId`, `pageUrl`, …) or `false` on failure/missing token.

### 3.2 Create payment — authenticated product purchase

- **Route**: `POST /api/mono/create-payment` — `backend/routes/api.php:117` (auth:sanctum, `throttle:10,1`)
- **Impl**: `MonoController::createPayment` — `backend/app/Http/Controllers/MonoController.php:277`
- **Inputs/Outputs/Rules**: Same shape as Cryptomus user purchase (§2.1): prepare products, validate promo (`user->id`), combine personal+promo discount capped at 99%, floor to ≥0.01. Creates invoice (`successUrl=/order-success`, `failUrl=/checkout?error=payment_failed`). On success persists pending `Transaction` with `metadata.invoice_id`, `payment_type='user'`, `user_id`, `products_data`, `promocode`.
- **Edge cases**: Invoice creation returns false → 422.

### 3.3 Create guest payment

- **Route**: `POST /api/guest/mono/create-payment` — `backend/routes/api.php:70` (public)
- **Impl**: `MonoController::createGuestPayment` — `backend/app/Http/Controllers/MonoController.php:170`
- Mirrors §2.2 but via Mono invoice. Pending `Transaction` with `payment_type='guest'`, `guest_email`, `products_data`, `promocode`.

### 3.4 Create top-up payment (card)

- **Route**: `POST /api/mono/topup` — `backend/routes/api.php:120` (auth:sanctum, `throttle:10,1`)
- **Impl**: `MonoController::createTopUpPayment` — `backend/app/Http/Controllers/MonoController.php:861`
- **Inputs**: `{ amount: numeric 1..100000 }`.
- **Outputs**: `{ success, url, invoice_id }`.
- **Rules**: Amount rounded; min 1. Invoice `successUrl=/profile?topup=success`, `failUrl=/profile?topup=failed`. Pending `Transaction` `payment_type='topup'`, `user_id`, `amount`.

### 3.5 Monobank webhook

- **Route**: `POST /api/mono/webhook` — `backend/routes/api.php:130`. Middleware: `verify.webhook:monobank` + `throttle:100,1`.
- **Impl**: `MonoController::webhook` — `backend/app/Http/Controllers/MonoController.php:42`
- **Signature verification**: `VerifyWebhookSignature::verifyMonobankSignature` — `VerifyWebhookSignature.php:86`. Reads header `X-Sign`, base64-decodes it and the configured `monobank.public_key` (Base64 ECDSA public key), then `openssl_verify(body, sig, pubkey, OPENSSL_ALGO_SHA256)`. Result must equal `1`. Missing header/key or bad key → reject.
- **Public key provisioning**: `backend/genMonoPubKey.php` — standalone CLI script. `GET https://api.monobank.ua/api/merchant/pubkey` with `X-Token`, prints the Base64 ECDSA key to paste into `.env` as `MONOBANK_PUBLIC_KEY`. Key is cached; refresh only when verification breaks.
- **Webhook body fields used**: `invoiceId`, `status`, `amount` (minor units), `modifiedDate` (ISO 8601 → parsed to Unix timestamp; out-of-order webhooks anticipated).
- **Flow**:
  1. Missing `invoiceId` → 200.
  2. `status !== 'success'` → 200 (only successful payments processed).
  3. Look up `Transaction` by `JSON_EXTRACT(metadata,'$.invoice_id')`. Not found / missing `payment_type` → 200.
  4. Reconstruct `paymentMetadata` from transaction metadata.
  5. For `user`/`guest`: if `Purchase` exists for the transaction → mark completed and return "Already processed".
  6. Route: `topup → handleTopUpWebhook` (`:402`), `guest → handleGuestWebhook` (`:706`), `user → handleUserPurchaseWebhook` (`:523`).
- **Top-up handler**: converts `amount/100` to decimal, sets transaction `completed`, `BalanceService::topUp(..., TYPE_TOPUP_CARD, {invoice_id, payment_method:'monobank', modified_date, ...})`. Idempotency via `BalanceService` invoice_id dedup (returns null → "Already processed"). Admin notified.
- **Purchase handlers**: same re-lock / re-stock / current-price logic as Cryptomus; create purchases with `promocode` + `invoiceId` as the idempotency `order_id`.
- **Outputs**: Always 200 to suppress retries; errors logged.
- **Edge cases**: `modifiedDate` parse failure is logged but non-fatal. Status is set `completed` inside handlers (atomic with purchase) per the in-code comments; on handler failure transaction stays `pending` for manual review (for purchases), but top-up handler sets `completed` before crediting.

---

## 4. Balance Top-Up Flow (both providers) — UI

- **Page**: `frontend/src/pages/BalanceTopUpPage.vue`
- **What**: Lets an authenticated user pick an amount (presets `[5,10,25,50,100,200]` or custom, min 1) and a method (`card` or `crypto`), then redirects to the provider.
- **Behavior** (`handleTopUp`, `BalanceTopUpPage.vue:339`):
  - `card → POST /mono/topup`, `crypto → POST /cryptomus/topup`, body `{ amount }`, `Authorization: Bearer <token>`.
  - On `{ url }` → `window.location.href = url` (redirect to provider).
- **Rules/Edge cases**: Requires auth (redirects to `/login?redirect=/balance/topup` if not). Refreshes user on mount to show live balance. Min amount enforced client-side (1) and server-side (1..100000). Balance is credited only later, via webhook.

### 4.1 Checkout payment-method picker

- **Component**: `frontend/src/components/checkout/PaymentList.vue`
- Renders three options: **card**, **crypto**, **balance**. The `balance` option is hidden when `hideBalance` prop is set (guests). Emits `update:modelValue` with `'card' | 'crypto' | 'balance'`. This is the selector that routes checkout to §2/§3 (card/crypto) or §1.7 (balance).

---

## 5. Promocodes

Models: `Promocode` (`backend/app/Models/Promocode.php`), `PromocodeUsage` (`backend/app/Models/PromocodeUsage.php`).
Promocode fields: `code, type, prefix, batch_id, percent_discount, usage_limit, per_user_limit, usage_count, starts_at, expires_at, is_active`. Type `discount` is the only supported type now (`free_access`/services removed).

### 5.1 Validate promocode (API)

- **Route**: `POST /api/promocodes/validate` — `backend/routes/api.php:52` (public group)
- **Impl**: `Api\PromocodeController::validateCode` — `backend/app/Http/Controllers/Api/PromocodeController.php:11` → `PromocodeValidationService::validate(code, userId?)` — `backend/app/Services/PromocodeValidationService.php:9`
- **Inputs**: `{ code }`; user id auto-derived from authenticated user if present.
- **Outputs**: On success 200 `{ ok:true, status:'active', type, code, promocode_id, discount_percent }`. On failure 422 `{ ok:false, status, message }`.
- **Validation rules** (`PromocodeValidationService::validate`):
  - Empty code → `invalid` / `code_required`.
  - Not found → `not_found`.
  - `paused` if `!is_active`.
  - `expired` if `expires_at < now`.
  - `exhausted` if `usage_limit > 0 && usage_count >= usage_limit`.
  - `scheduled` if `starts_at > now`.
  - Order of precedence: paused → expired → exhausted → scheduled.
  - **Per-user limit**: if `userId` and `per_user_limit > 0`, counts `promocode_usages` for that user; `>= per_user_limit` → `per_user_limit`.
  - For guests (`userId = null`) per-user limit is **not** checked.
  - Returns `discount_percent = (int)percent_discount` for `type==='discount'`.

### 5.2 How a promocode is applied at checkout

- A validated `discount` promocode contributes `discount_percent` to the order. In authenticated flows (`CartController`, `MonoController::createPayment`, `CryptomusController::createPayment`) it is **summed with the user's personal discount** and the combined percentage is capped at **99%** (so the buyer always pays ≥ 1%). See `CryptomusController.php:78–85`, `MonoController.php:330–337`, `CartController.php:64–69`.
- For guests the discount is applied as a plain integer-percent reduction of the total (no personal discount).

### 5.3 Recording usage (atomic, idempotent)

- **Impl**: `ProductPurchaseService::recordPromocodeUsage(code, userId?, orderId?)` — `backend/app/Services/ProductPurchaseService.php:520`, invoked from `createMultiplePurchases` (`:425`).
- **Rules**:
  - If `orderId` already has a `PromocodeUsage` row → skip (prevents double counting on webhook replays).
  - Locks the promocode (`lockForUpdate`), inserts a `PromocodeUsage` (`promocode_id, user_id, order_id`).
  - Increments `usage_count` (guarded against exceeding `usage_limit` when a limit is set; always increments for unlimited codes for statistics).
- The `orderId` is the provider order/invoice id (`order_id`/`invoiceId`) or `'balance_'.orderNumber` for balance purchases — making usage idempotent per order.

### 5.4 Per-user-limit enforcement model

- Soft pre-check happens in `PromocodeValidationService::validate` and `Promocode::canUserUse` (`Promocode.php:60`, default limit 1 when unset).
- **Authoritative** enforcement is at write time under lock in `recordPromocodeUsage`.
- **Edge case (documented in code)**: guests bypass per-user limits entirely — a guest can reuse a promocode multiple times unless restricted elsewhere by email/IP (not implemented). `PromocodeValidationService.php:68`.

### 5.5 Bulk creation & alternative validation/apply (admin/service)

- `PromocodeService::bulkCreate` — `backend/app/Services/PromocodeService.php:15`: generates `quantity` codes (`prefix + 8-char [A-Z0-9]`), dedupes within batch and against existing codes (single query), bulk `insert`. Tags rows with `batch_id` (UUID) and per-row `usage_limit`, `per_user_limit` (default 1), dates, `is_active`.
- `PromocodeService::validatePromocode` (`:73`) and `applyPromocode` (`:95`) — an alternate validate/apply pair using `Promocode::canBeUsed`/`canUserUse`; `applyPromocode` locks the row, increments `usage_count`, and writes a `promocode_usages` row inside a transaction (race-safe).

### 5.6 Frontend promo store

- `frontend/src/stores/promo.ts`: Pinia store. `apply(code)` → `POST /promocodes/validate`; on `type==='discount'` stores `{ type, discount_percent }`; handles `free_access` shape defensively (legacy). Surfaces `status`/`message` errors. Persisted across reloads.

---

## 6. Vouchers

Model: `Voucher` (`backend/app/Models/Voucher.php`) — fields `code, amount, currency, user_id, used_at, expires_at, is_active, note`; soft-deletes. `isUsed()` = `used_at !== null`; `canBeUsed()` = active & unused; `isExpired()` hardcoded `false` (vouchers are perpetual). `generateCode()` produces a 12-char `[0-9A-Z]` code.

### 6.1 Activate voucher

- **Route**: `POST /api/vouchers/activate` — `backend/routes/api.php:86` (auth:sanctum, `throttle:120,1`)
- **Impl**: `VoucherController::activate` — `backend/app/Http/Controllers/VoucherController.php:16`; validation `backend/app/Http/Requests/Voucher/ActivateVoucherRequest.php` (`code` required string).
- **Inputs**: `{ code }` + token.
- **Outputs**: `{ success, data: { message, voucher:{code,amount,currency}, balance:{old,new,added} } }`. Failures throw `ValidationException` on the `code` field.
- **Business rules** (all inside `DB::transaction`):
  - `Voucher::where(code)->lockForUpdate()` (race-safe; code uppercased/trimmed).
  - Not found → "Ваучер с таким кодом не найден".
  - `!is_active` → "Ваучер деактивирован администратором".
  - `isUsed()` → "Ваучер уже был использован".
  - `expires_at` past (if set) → "Срок действия ваучера истек" (note: `isExpired()` always returns false, but this explicit `expires_at` check still applies).
  - Marks `user_id` + `used_at = now()`, saves.
  - Credits balance via `BalanceService::topUp(user, amount, TYPE_TOPUP_VOUCHER, {voucher_id, voucher_code})` → produces a `BalanceTransaction` + mirror `Transaction`.
- **Edge cases**: A failure during `topUp` rolls back the whole transaction (voucher not consumed) and surfaces a generic error. Lock prevents concurrent double-activation.

---

## 7. Supplier Earnings (accrual, hold, release, commission)

Model: `SupplierEarning` (`backend/app/Models/SupplierEarning.php`) — `supplier_id, purchase_id, transaction_id, amount, status, available_at, processed_at`. Statuses observed: `held`, `available`, `withdrawn`, `reversed`.

### 7.1 Commission & price markup

- **Buyer-facing price**: `ServiceAccount::getPriceWithCommission()` — `backend/app/Models/ServiceAccount.php:205`. For supplier products: `final_price = supplier_price / (1 - commission/100)`. Example: supplier sets 10 USD, commission 10% → buyer pays `10/0.9 = 11.11`, supplier keeps `10*0.9 = 9`. Admin products (no `supplier_id`) use plain price (minus any active discount). Guards against `commission_multiplier <= 0`.
- `supplier_commission` is a per-user percent (`User.php` cast `decimal:2`), null → 0.

### 7.2 Earning accrual (per sale)

- **Impl**: `ProductPurchaseService::createMultiplePurchases` → earning block at `backend/app/Services/ProductPurchaseService.php:264–341`.
- **Rules**:
  - Only when the product has a `supplier_id` and that user `is_supplier`.
  - Locks supplier (`lockForUpdate`) to avoid race conditions.
  - `supplierSharePercent = clamp(100 - supplier_commission, 0..100)`.
  - `supplierAmount = round(total * supplierSharePercent/100, 2)` — i.e. the supplier receives their share of the **line total actually charged**.
  - Skips creating an earning if `supplierAmount <= 0` (logged) or if a `SupplierEarning` already exists for the same `(purchase_id, transaction_id, supplier_id)` (idempotency).
  - `holdHours = supplier.supplier_hold_hours ?? 6`; `available_at = now()->addHours(holdHours)`; `status = 'held'`.
- **Outputs**: a `held` `SupplierEarning` row.
- **Edge cases**: Earning-creation failures are caught and logged but **do not** roll back the purchase (intentional — needs manual reconciliation).

### 7.3 Holding / release schedule

- **Hold**: earnings start `held` with a future `available_at` (default +6h, per-supplier override `supplier_hold_hours`).
- **Release command**: `php artisan suppliers:release-earnings` — `backend/app/Console/Commands/ReleaseSupplierEarnings.php`.
  - Finds suppliers with `SupplierEarning::readyToRelease()` (status `held` & `available_at <= now`), distinct.
  - For each supplier calls `BalanceService::syncSupplierBalance($supplier)` — `backend/app/Services/BalanceService.php:369`.
- **`syncSupplierBalance`**: inside a locked transaction, selects `held` earnings with `available_at <= now`, sums them, sets those rows to `status='available'` + `processed_at=now()`, and `increment('supplier_balance', total)` on the supplier. Returns the amount released.
- **Scopes**: `SupplierEarning::scopeReadyToRelease` (`:46`), `scopeAvailable` (`:54`).

### 7.4 Reversal (on refund)

- `SupplierEarning::reverse(reason?)` — `:70`: sets `status='reversed'`, `processed_at=now()`. Refuses if already `reversed` (warn) or `withdrawn` (error — funds already paid out).
- `SupplierEarning::partialReverse(amount, reason?)` — `:119`: if full amount → `reverse()`; else splits — creates a new earning row for the remainder (same status/available_at) and marks the current one `reversed` for the reversed amount. Validates `0 < amount <= this.amount`.

### 7.5 Supplier balance & relation to withdrawals

- `User.supplier_balance` accumulates released earnings; it is the withdrawable pool. Withdrawal approval/payout decrements it (see `Admin\WithdrawalRequestController`, `Supplier\WithdrawalController`). Several read paths also lazily compute "available" by combining `status='available'` and `held & available_at<=now` (`scopeAvailable`).

---

## 8. Transactions Ledger (legacy/general)

### 8.1 List transactions

- **Route**: `GET /api/transactions` — `backend/routes/api.php:79` (auth:sanctum, `throttle:120,1`)
- **Impl**: `Api\TransactionController::index` — `backend/app/Http/Controllers/Api/TransactionController.php:10`
- **Inputs** (query): `status`, `date_from`, `date_to`, `payment_method`, `per_page` (default 20).
- **Outputs**: `{ data: [{id, amount, currency, payment_method, status, service_name, created_at}], meta: {current_page,last_page,per_page,total} }`.
- **Rules**: Returns the authenticated user's `Transaction` rows (`user->transactions()`) eager-loading `subscription.service` and `serviceAccount`. Old rows without status default to `completed`.
- **Edge cases**: Unauthenticated → 401. This lists the `Transaction` table (payment-intents + balance mirrors), **not** the `BalanceTransaction` ledger (that is §1.2).

### 8.2 Transaction model — types & statuses

- **`Transaction`** statuses seen in code: `pending` (payment created, awaiting webhook), `completed` (paid / balance op). `payment_method` values include `cryptomus`, `monobank`, plus `BalanceService`-mapped methods: `balance_topup_card`, `balance_topup_crypto`, `admin_balance_topup`, `voucher_balance_topup`, `balance_deduction`, `balance_refund`, `balance`, `admin_balance_adjustment` (`BalanceService::mapTypeToPaymentMethod`, `:347`). `metadata` carries `order_id`/`invoice_id`, `payment_type` (`user|guest|topup`), `products_data`, `promocode`, `balance_transaction_id`, etc.
- **`BalanceTransaction`** types: `topup_card, topup_crypto, topup_admin, topup_voucher, deduction, refund, purchase, adjustment`. Statuses: `pending, completed, failed, cancelled` (in practice service writes `completed`).

---

## 9. Withdrawal Requests (data model & states)

> Full withdrawal lifecycle is documented elsewhere; here only the model and its states.

- **Model**: `WithdrawalRequest` (`backend/app/Models/WithdrawalRequest.php`). Soft-deletes.
- **Fields**: `supplier_id, amount (decimal:2), payment_method, payment_details, status, admin_comment, processed_at (datetime)`.
- **Relation**: `supplier()` → `User` (FK `supplier_id`).
- **Statuses** (`status` string + scopes): `pending` → `approved` → `paid`, or `rejected`.
  - `scopePending` / `scopeApproved` / `scopePaid` / `scopeRejected` (`:39–66`).
- **State transitions (where they live, for reference)**:
  - Created `pending` by supplier (`Supplier\WithdrawalController`, line ~185).
  - Supplier may cancel/reject own `pending` request (→ `rejected`, line ~220).
  - Admin approves `pending` → `approved` (requires balance sync; `Admin\WithdrawalRequestController:121`).
  - Admin marks `approved` → `paid` (checks `supplier_balance >= amount`, decrements `supplier_balance`; `:171–245`).
  - Admin rejects (cannot reject already-`paid`; `:289–294`).
- **Edge cases**: Payout requires sufficient `supplier_balance` (which depends on released `SupplierEarning`s — §7.3); insufficient → exception. Reversed/withdrawn earnings interact with reversal guards (§7.4).

---

## 10. Webhook security summary

| Provider | Guard | Mechanism | File |
|---|---|---|---|
| Cryptomus | `verify.webhook:cryptomus` | body `sign` = `md5(base64(json(data)) + payment_key)`, `hash_equals` | `VerifyWebhookSignature.php:55` |
| Cryptomus (alt) | `CryptomusMiddleware` | IP allowlist `91.227.144.54` | `CryptomusMiddleware.php` |
| Monobank | `verify.webhook:monobank` | `X-Sign` header, ECDSA `openssl_verify(body, sig, pubkey, SHA256)` | `VerifyWebhookSignature.php:86` |

- Both webhook routes also carry `throttle:100,1`.
- Verification can be globally bypassed by `config('app.verify_webhooks_enabled') === false` (logs a warning) — should be `true` in production.
- Both webhooks return a 200 success envelope on virtually all internal failures to avoid provider retries; reconciliation relies on logs and the pending/completed transaction + `Purchase`/`SupplierEarning` existence checks for idempotency.

## 11. Idempotency mechanisms (consolidated)

- **Top-ups**: `BalanceService::findDuplicateTransaction` matches `invoice_id`/`order_id` in completed `BalanceTransaction`s within the **last 24h**.
- **Purchases (webhooks)**: existence of a `Purchase` for `transaction_id` short-circuits as "Already processed" (Cryptomus & Mono).
- **Promocode usage**: `PromocodeUsage.order_id` uniqueness per order; recorded under lock.
- **Supplier earnings**: unique `(purchase_id, transaction_id, supplier_id)` check before creating.
- **Vouchers / promocode apply / balance ops**: row-level `lockForUpdate` inside DB transactions.

## 12. Rate limiting (route groups)

- Payment creation & top-up endpoints: `throttle:10,1` (`api.php:115`).
- Webhooks: `throttle:100,1` (`api.php:125,129`).
- Authenticated general (vouchers activate, transactions list): `throttle:120,1` (`api.php:75`).
- Public group (promocode validate, guest payment creation): public middleware group.
