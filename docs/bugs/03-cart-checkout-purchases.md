# Bug Audit — Cart, Checkout, Purchases & Delivery

Adversarial security & correctness audit of the financially-critical product cart / checkout / purchase / delivery domain of **Account Arena**.

Scope audited: `CartController`, `GuestCartController`, `Api\PurchaseController`, `ProductPurchaseService`, `AssignServiceAccount`, `ManualDeliveryService`, `Admin\ManualDeliveryController`, `Purchase`/`PurchaseStatusHistory`/`ServiceAccount` models, `PurchasePolicy`, `ProcessWaitingStockOrders`, `NotifyOverdueManualOrders`, the Mono/Cryptomus purchase webhooks, `BalanceService::deduct`, `PromocodeValidationService`, `CartStoreRequest`, `productCart.ts`, `CheckoutPage.vue`, and the relevant migrations.

---

## Summary table

| # | Severity | Category | Confidence | Title | Location |
|---|----------|----------|-----------|-------|----------|
| 1 | HIGH | Broken access control / IDOR | High | Guest purchase credentials & cancel exposed to anyone who knows the guest email | `Api/PurchaseController.php:25-53,130-254,287-322`, `PurchasePolicy.php:22-37` |
| 2 | HIGH | Double-delivery / double-spend (race) | High | Webhook duplicate guard is unprotected check-then-act; no unique index on `purchases.transaction_id` | `MonoController.php:558-569,729-740`, `CryptomusController.php` (parallels), migration `...create_purchases_table.php:18` |
| 3 | HIGH | Financial / supplier over-credit & wrong revenue | High | Discount (personal + promo) never re-applied at delivery: `Purchase.total_amount`, `Transaction.amount` and `SupplierEarning` recorded at full undiscounted price | `ProductPurchaseService.php:54,189-221,279`, `MonoController.php:599-619`, `CryptomusController.php:723-740` |
| 4 | MEDIUM | Promocode abuse | High | `per_user_limit` enforced only at validate() time, never re-checked under lock at record time; no unique constraint; guests entirely unlimited | `ProductPurchaseService.php:520-572`, `CartController.php:152-186`, `PromocodeValidationService.php:51-70`, migration `...create_promocode_usages_table.php` |
| 5 | MEDIUM | Cancel/refund abuse | High | User can cancel a `processing` (paid) manual order but no refund is ever issued — money is kept | `ManualDeliveryService.php:211-294` (TODO line 289), `Api/PurchaseController.php:211-254` |
| 6 | LOW | Input validation / hardening | High | `quantity` has no upper bound; manual products report stock `999`, allowing absurd line quantities & totals | `CartStoreRequest.php:17`, `GuestCartController.php:28`, `ServiceAccount.php:285-291` |
| 7 | LOW | Atomicity / accounting drift | Medium | Cryptomus authed webhook path skips stock + price re-validation that the Mono path performs | `CryptomusController.php:808-829` |

---

## Bug 1 — Guest purchase data (credentials) readable / cancellable by anyone who knows the email

- **SEVERITY:** HIGH
- **Category:** Broken access control / IDOR / sensitive data exposure
- **Confidence:** High
- **Location:** `backend/app/Http/Controllers/Api/PurchaseController.php:25-53` (index), `:130-139` (show), `:181-190` (download), `:211-220` (cancel), `:287-296` (status-history); `backend/app/Policies/PurchasePolicy.php:22-37`.

**Description.** For guest purchases, authorization is satisfied purely by passing a `guest_email` query parameter that matches the purchase's stored `guest_email`. There is no session binding, signed token, or proof of email ownership. The purchase `id` is a sequential auto-increment integer (guessable), and `account_data` contains the actual delivered credentials.

**Code path / why.** `PurchasePolicy::view()` returns `true` when `!$purchase->user_id && $purchase->guest_email` and `strtolower(trim($guestEmail)) === strtolower($purchase->guest_email)` (`PurchasePolicy.php:30-34`). `show`/`download`/`getStatusHistory`/`cancel` all gate on this same `view` ability. The code even ships with the missing control commented out:
```php
// PurchaseController.php:50-51
// ВАЖНО: Дополнительная проверка для гостей (например, из сессии)
// if (session('guest_email') !== $guestEmail) { abort(403); }
```
An attacker who knows (or guesses) a buyer's email can enumerate `GET /purchases/{id}?guest_email=victim@example.com` / `/download` and exfiltrate purchased account credentials, or `POST /purchases/{id}/cancel` to grief the victim's processing orders.

**Impact.** Disclosure of sold credentials (the core product the customer paid for) and unauthorized cancellation of others' orders. Email addresses are low-entropy secrets; this is effectively unauthenticated access to paid digital goods.

**Suggested fix.** Bind guest access to something the requester must prove possession of: a per-purchase opaque access token embedded in the order/confirmation link (stored on the Purchase, compared in constant time), or a signed URL. At minimum, require a server-issued guest session token tied to the email at checkout. Do not authorize on email equality alone.

---

## Bug 2 — Duplicate-webhook guard is an unprotected check-then-act (double delivery / double stock consumption)

- **SEVERITY:** HIGH
- **Category:** Concurrency / double-delivery / double-spend
- **Confidence:** High
- **Location:** `backend/app/Http/Controllers/MonoController.php:558-569` (auth) and `:729-740` (guest); `backend/app/Http/Controllers/CryptomusController.php` (parallel guards); migration `backend/database/migrations/2025_11_04_063700_create_purchases_table.php:18`.

**Description.** Each purchase webhook prevents duplicates by doing `Purchase::where('transaction_id', $transaction->id)->first()` and bailing if one exists. This SELECT is **not** inside a transaction, takes **no lock**, and there is **no unique constraint** on `purchases.transaction_id` (the migration declares only a nullable FK: `$table->foreignId('transaction_id')->nullable()->constrained()->onDelete('set null')`).

**Code path / why.** Payment providers explicitly may deliver webhooks more than once / out of order (the code comments acknowledge "Webhooks can arrive out of order"). The webhook route is `throttle:100,1` — it does not serialize. Two near-simultaneous deliveries of the same `success` webhook both run the duplicate SELECT, both see "no existing purchase," both proceed to `createMultiplePurchases`. The critical section that is supposed to be protected (the existence check → create) spans across two independent `DB::transaction` calls (the inner one in `createMultiplePurchases`), so the row lock on `ServiceAccount` inside `createMultiplePurchases` does **not** cover the duplicate check. Result: two full sets of Purchases for one payment, `used` incremented twice (or accounts `accounts_data[used+i]` handed out twice from the same product), and two `SupplierEarning` rows.

**Impact.** A single payment can deliver two copies of the goods (drains automatic stock at no cost, delivers double credentials), double-counts supplier earnings, and corrupts inventory accounting. Triggerable by normal provider retry behavior, not just an attacker.

**Suggested fix.** Add a `unique` index on `purchases.transaction_id` (and rely on the DB to reject the second insert), OR wrap the duplicate check + creation in a single transaction with `Transaction::lockForUpdate()` on the transaction row before checking. Idempotency must be enforced at the storage layer, not by an un-locked read.

---

## Bug 3 — Discount is never re-applied at delivery: purchases, transactions and supplier earnings recorded at full undiscounted price

- **SEVERITY:** HIGH
- **Category:** Financial correctness / accounting / supplier over-payment
- **Confidence:** High
- **Location:** `backend/app/Services/ProductPurchaseService.php:54` (line total = full price × qty), `:189-197` (Transaction amount = `$total`), `:210-221` (Purchase.total_amount = `$total`), `:279` (SupplierEarning amount = `round($total * share)`); discounting that is *not* propagated: `CartController.php:62-72`, `MonoController.php:328-337,599-619`, `CryptomusController.php:723-740`.

**Description.** The amount actually **charged** to the buyer is the aggregate after personal discount + promo discount (`CartController.php:64-72`; `MonoController.php:330-337`). But the per-line `total` that flows into `createProductPurchase` is the **undiscounted** `price × quantity` computed in `prepareProductsData` (`ProductPurchaseService.php:54`). At delivery, that undiscounted `total` is written to `Transaction.amount`, `Purchase.total_amount`, and — most damagingly — drives the `SupplierEarning` payout (`:279`). The card/crypto webhooks make this worse: they recompute `total = getCurrentPrice() * quantity` and **never** apply the discount at all.

**Code path / why.** Example: buyer applies a 50%-off promo on a 100 USD supplier item. Charged 50 USD. At delivery, `SupplierEarning.amount = round(100 * (100-commission)/100)` — computed on 100, not 50. The platform collected 50 but holds a supplier liability based on 100. With a small commission the supplier is credited *more than the platform received*, producing a negative margin / loss. `Purchase.total_amount` and `Transaction.amount` (used for refunds, disputes, reporting, average-order analytics) are likewise overstated versus cash actually taken.

**Impact.** Direct monetary loss on every discounted supplier sale (supplier over-credited), and systematically inflated revenue/transaction records that mis-drive refunds and disputes. Easily reached through normal promo usage.

**Suggested fix.** Propagate the effective discounted per-line amount into `createProductPurchase`/`createMultiplePurchases` (allocate the order-level discount across lines), and base `Transaction.amount`, `Purchase.total_amount`, and `SupplierEarning` on the actually-charged figure. In the webhooks, re-apply the stored discount/promo to the recomputed line totals rather than charging-then-recording full price.

---

## Bug 4 — Promocode `per_user_limit` not enforced at charge time; no unique constraint; unlimited for guests

- **SEVERITY:** MEDIUM
- **Category:** Promocode / voucher abuse
- **Confidence:** High
- **Location:** `backend/app/Services/ProductPurchaseService.php:520-572` (`recordPromocodeUsage`), `backend/app/Http/Controllers/CartController.php:152-186` (inline balance-flow recording), `backend/app/Services/PromocodeValidationService.php:51-70`, migration `backend/database/migrations/2025_10_07_000004_create_promocode_usages_table.php`.

**Description.** `per_user_limit` is checked only inside `validate()` via a plain COUNT (`PromocodeValidationService.php:54-66`), which runs at quote/validation time — **before** payment and with **no lock**. At the authoritative recording step (`recordPromocodeUsage`, and the inline block in `CartController`), only the global `usage_limit` is re-checked under `lockForUpdate`; `per_user_limit` is never re-evaluated. The `promocode_usages` table has no unique index on `(promocode_id, user_id)` or on `order_id` (only `index()`).

**Code path / why.** A user at their `per_user_limit` (or firing concurrent checkouts) passes the early COUNT check (TOCTOU window), pays, and the recorder happily inserts another `PromocodeUsage` because it only guards the global counter. For **guests** the per-user check is skipped entirely by design (`PromocodeValidationService.php:68-69` comment), so a guest promo with `per_user_limit` is reusable without bound. Even the `order_id` dedupe (`recordPromocodeUsage:527-535`) is an un-unique check-then-act and could double-insert under concurrent identical webhooks (see Bug 2).

**Impact.** Per-user-limited promo codes can be redeemed more times than intended (revenue leakage), especially via concurrency or guest checkout.

**Suggested fix.** Re-check `per_user_limit` inside the locked recording transaction (count existing usages for the user under the promocode lock). Add a unique constraint on `promocode_usages.order_id` and, where applicable, a `(promocode_id, user_id)` guard. For guests, scope the per-user limit by normalized email.

---

## Bug 5 — User cancellation of a paid `processing` order issues no refund

- **SEVERITY:** MEDIUM
- **Category:** Cancel / refund correctness
- **Confidence:** High
- **Location:** `backend/app/Services/ManualDeliveryService.php:211-294` (refund TODO at `:289`); entry `backend/app/Http/Controllers/Api/PurchaseController.php:211-254`.

**Description.** A buyer can cancel their own manual-delivery order while it is `processing` (i.e., already paid, awaiting fulfillment). `cancelProcessingOrder` sets status to `cancelled`, writes history, notifies admins — but performs **no** balance credit / transaction reversal. The code explicitly leaves it unimplemented: `// TODO: Возврат средств через Transaction (если требуется)`.

**Code path / why.** Money was taken at checkout/webhook; cancellation only flips a status. The buyer loses their money with no goods and no automatic refund. (Conversely there is no refund-after-delivery exploit here, because cancel is correctly restricted to `processing` only — `:214-216` and `PurchasePolicy::cancel`.) The harm is to the **customer**, and to support load / chargeback risk.

**Impact.** Customers who self-cancel a pending paid order are silently not refunded → disputes, chargebacks, reputational and potential legal/financial exposure. Inconsistent with the user-facing "cancel" affordance.

**Suggested fix.** On cancellation of a paid `processing` order, credit the user's balance (or initiate provider refund) within the same transaction, recording a reversing `Transaction`/`BalanceTransaction`. If refunds are intentionally manual, the UI/endpoint must not present self-cancel as final without a clear money-back path.

---

## Bug 6 — `quantity` unbounded above; manual products report stock 999

- **SEVERITY:** LOW
- **Category:** Input validation / hardening
- **Confidence:** High
- **Location:** `backend/app/Http/Requests/Cart/CartStoreRequest.php:17`, `backend/app/Http/Controllers/GuestCartController.php:28`, `MonoController.php:281`/`createGuestPayment`, `backend/app/Models/ServiceAccount.php:285-291`.

**Description.** `quantity` is validated only as `integer|min:1` — no maximum. For manual-delivery products, `getAvailableStock()` returns a hardcoded `999` whenever `is_active`, so `prepareProductsData`'s `available < quantity` check passes for any quantity up to 999, and line totals scale to `price × 999` per item with many distinct manual line items.

**Code path / why.** Not a free-goods exploit (the balance/charge checks still apply and credentials aren't auto-assigned for manual), but it permits creation of very large/expensive orders and large `account_data` arrays the admin must hand-fill, and large `price * quantity` float math. Combined with float arithmetic in `prepareProductsData` (`$price * $quantity`), large quantities increase rounding/precision exposure.

**Impact.** Resource/abuse surface (oversized manual orders, admin burden, large computed totals) and minor precision risk. Low exploitability for direct theft.

**Suggested fix.** Add a sane `max` to `products.*.quantity` (e.g., per-product configurable cap), and cap manual-delivery purchasable quantity explicitly instead of relying on the `999` sentinel.

---

## Bug 7 — Cryptomus authed-user webhook skips the stock/price re-validation Mono performs

- **SEVERITY:** LOW
- **Category:** Atomicity / accounting consistency
- **Confidence:** Medium
- **Location:** `backend/app/Http/Controllers/CryptomusController.php:808-829` (`prepareProductsForPurchase`) vs. `MonoController.php:578-619`.

**Description.** The Mono authed-user webhook re-locks each product, re-checks `getAvailableStock()`, and recomputes `getCurrentPrice()` before building purchase data. The Cryptomus authed path's `prepareProductsForPurchase` does a bare `ServiceAccount::find()` and trusts the stored metadata `price`/`total` with no stock or price re-check at this layer.

**Code path / why.** The authoritative stock re-check under `lockForUpdate` still happens inside `createMultiplePurchases` (`ProductPurchaseService.php:385-404`), so true oversell is still prevented and the transaction rolls back if short. The gap is that Cryptomus records the **stale stored price** rather than the current price, so price changes between checkout and confirmation are not reflected (and the warning-logging parity with Mono is absent). Because metadata is server-stored, this is not client-tamperable.

**Impact.** Minor pricing/accounting drift and inconsistent behavior between providers; no direct theft vector.

**Suggested fix.** Mirror the Mono webhook: re-fetch under lock and recompute `getCurrentPrice()` in the Cryptomus authed path (and ideally consolidate both providers onto one shared revalidation routine).

---

## Ruled out (investigated, not vulnerable)

- **Client price tampering.** The cart sends only `{id, quantity}` and `promocode`; price/total are always recomputed server-side via `getCurrentPrice()` in `prepareProductsData` and again in the webhooks. `CartStoreRequest` / guest validation accept no price field. Not exploitable.
- **Buying with insufficient balance (balance flow).** The pre-check at `CartController.php:76` is advisory, but `BalanceService::deduct` re-checks `$oldBalance < $amount` **inside** a `DB::transaction` after `User::lockForUpdate()` (`BalanceService.php:172-185`) and rejects negative balances. Concurrent spends on the same user serialize on the user row lock. Safe.
- **Automatic-delivery stock oversell / concurrent drain of the same `accounts_data`.** `createMultiplePurchases` always re-fetches the product with `ServiceAccount::lockForUpdate()` and re-checks `getAvailableStock() < quantity` after the lock (`ProductPurchaseService.php:385-404`), and `createProductPurchase` increments `used` under that same row lock. Two buyers cannot both take the last unit. Safe (the one exception is the cross-transaction duplicate-webhook path — see Bug 2).
- **Atomicity of balance checkout (money taken but no product / vice versa).** `BalanceService::deduct` (nested txn) and `createMultiplePurchases` (nested txn) run inside the outer `DB::beginTransaction()` in `CartController`, with explicit rollback when `purchases` is empty or count mismatches (`:108-134`). Nested transactions use savepoints; the outer commit governs. Atomic.
- **Negative / zero quantity.** Blocked by `integer|min:1` validation in all entry points. (Upper bound is the only gap — Bug 6.)
- **IDOR on authenticated `/purchases/{id}`.** For logged-in users, `PurchasePolicy::view` requires `purchase->user_id === user->id`; `delete` is hard-false; `cancel` is processing-only. Authed ownership is enforced correctly. (The guest path is the problem — Bug 1.)
- **Status-machine violations / double manual processing.** `ManualDeliveryService::processPurchase` and the admin controller both lock the purchase (`lockForUpdate`) and re-check `status === processing` post-lock (`ManualDeliveryService.php:64-74`, `ManualDeliveryController.php:158-162`); out-of-stock-at-processing converts to a non-throwing waiting hold. Re-processing a completed order is blocked. Safe.
- **Waiting-stock cron.** `ProcessWaitingStockOrders` only clears the `is_waiting_stock` flag and notifies a human; it never auto-delivers or consumes stock, so no oversell. The `used` increment remains gated by the locked `processPurchase`. Safe.
- **`AssignServiceAccount` legacy path.** Uses `DB::transaction` + `lockForUpdate` + `increment('used')`; not wired into the product cart/checkout flow. Not a live cart vector.
- **Promocode `usage_limit` (global) over-redemption.** Re-checked under `Promocode::lockForUpdate()` at record time (`ProductPurchaseService.php:538-561`, `CartController.php:172-184`). The per-user limit is the actual gap (Bug 4).
