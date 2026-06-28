# Security & Correctness Audit — Payments, Balance, Transactions, Promocodes, Vouchers, Withdrawals, Supplier Earnings

**Scope:** Financially-critical money-handling domain of Account Arena (Laravel API).
**Auditor:** Adversarial security & correctness review.
**Date:** 2026-06-28.
**Method:** For each webhook, traced exactly what is signed, what is compared, and whether the credited amount is re-derived server-side or trusted. For each balance/earning mutation, located the exact critical section and checked atomicity, idempotency, and over/under-payment.

> Note: `backend/vendor/` is not installed in the audited tree, so `FunnyDev\Cryptomus\CryptomusSdk` internals (`read_result`, `create_payment`) could not be inspected directly. Findings about the Cryptomus SDK are reasoned from its call sites and noted with reduced confidence where they depend on SDK internals.

---

## Summary Table

| # | Severity | Confidence | Title | Primary File |
|---|----------|-----------|-------|--------------|
| 1 | **Critical** | High | Supplier earning over-pays supplier — commission applied to already-marked-up buyer price (double benefit / platform loses its margin) | `ProductPurchaseService.php:279` |
| 2 | **Critical** | High | `markAsPaid` / `partialReverse` create duplicate `SupplierEarning` rows that violate the new unique index → payout transaction throws and rolls back | `Admin/WithdrawalRequestController.php:225`, `SupplierEarning.php:139` |
| 3 | **High** | High | Promocode-usage idempotency is check-then-insert with **no unique constraint** on `order_id` → concurrent webhook retries double-count usage and bypass `usage_limit` | `ProductPurchaseService.php:520`, migration `...create_promocode_usages_table.php` |
| 4 | **High** | Medium | Top-up double-credit window: idempotency limited to last 24h + status flipped to `completed` before crediting; webhook replay after 24h (or with a fresh order_id reuse) re-credits balance | `BalanceService.php:291`, `MonoController.php:438`, `CryptomusController.php:479` |
| 5 | **High** | Medium-High | Cryptomus signature re-encodes a re-decoded body (`json_encode` of `json_decode`) — canonicalization mismatch can reject valid webhooks or, combined with type coercion, weaken the compare | `VerifyWebhookSignature.php:55` |
| 6 | **Medium** | High | `markAsPaid` FIFO only consumes `status='available'` earnings but the approval/balance math also counts matured `held` rows → `supplier_balance` and earning ledger desync; `remainingAmount` can silently remain > 0 | `Admin/WithdrawalRequestController.php:190–241` |
| 7 | **Medium** | High | Guests bypass per-user promocode limit entirely (no email/IP throttle) → unlimited reuse of percentage discounts on guest orders | `PromocodeValidationService.php:54–70`, `Promocode.php:60` |
| 8 | **Medium** | Medium | Monobank webhook does not verify the paid `amount`/`ccy` against the created invoice; currency confusion possible if Option currency differs from invoice ccy | `MonoController.php:402–456` |
| 9 | **Low** | High | `findDuplicateTransaction` mutates a shared query builder across `invoice_id`/`order_id` branches; `JSON_EXTRACT(...) = ?` comparison is fragile | `BalanceService.php:291` |
| 10 | **Low** | Medium | `genMonoPubKey.php` treats the raw `/pubkey` response as the key; Mono returns a JSON envelope — provisioning may store a wrong key (fails closed, but operationally risky) | `genMonoPubKey.php:50` |
| 11 | **Low** | High | `CryptomusMiddleware` IP allowlist is dead/unused and trusts spoofable `X-Forwarded-For`/`X-Real-Ip`; not wired to the route but a footgun if ever enabled | `CryptomusMiddleware.php:13` |
| 12 | **Low** | High | Webhook verification can be globally disabled via `verify_webhooks_enabled=false`; if misconfigured in prod, **any** unauthenticated request can credit balances | `VerifyWebhookSignature.php:21` |

---

## BUG 1 — Supplier earning over-pays the supplier (commission applied to the marked-up buyer price)

**Severity:** Critical · **Confidence:** High
**Files:** `backend/app/Services/ProductPurchaseService.php:264–341` (earning calc at `:279`); `backend/app/Models/ServiceAccount.php:205–248` (`getPriceWithCommission`).

### What the code intends (per its own comment)
`ServiceAccount::getPriceWithCommission()` documents the model:

```
// поставщик указал 10 USD, комиссия 10%
// - Поставщик получает: 10 * 0.9 = 9 USD
// - Покупатель платит: 10 / 0.9 = 11.11 USD
```

So with `supplier_price = 10`, `commission = 10%`:
- Buyer is charged `final_price = supplier_price / (1 - c/100) = 10 / 0.9 = 11.11`.
- Supplier is *supposed* to receive `9.00` per the doc comment (and the platform keeps `11.11 - 9 = 2.11`).

### What the code actually does
The earning is computed in `createProductPurchase`:

```php
$supplierSharePercent = max(0, min(100, 100 - $supplierCommission)); // = 90
$supplierAmount = round($total * ($supplierSharePercent / 100.0), 2); // $total is the BUYER total (11.11)
```

`$total` here is the line total **actually charged to the buyer**, which already contains the markup (`11.11`). So:

```
supplierAmount = 11.11 * 0.90 = 10.00
```

The supplier receives **10.00**, not the documented **9.00**, and the platform keeps only `11.11 - 10.00 = 1.11` instead of `2.11`. The commission percentage is being applied to a base that already had the inverse-commission markup baked in, so the two operations do **not** cancel and the supplier is systematically over-credited (and the platform's effective commission is roughly halved).

Worked check across commissions (supplier_price = 100):

| commission c | buyer pays = 100/(1-c) | supplier *should* get (100·(1-c)) | code pays = buyerPays·(1-c) | platform margin (intended) | platform margin (actual) |
|---|---|---|---|---|---|
| 10% | 111.11 | 90.00 | 100.00 | 21.11 | 11.11 |
| 25% | 133.33 | 75.00 | 100.00 | 58.33 | 33.33 |
| 50% | 200.00 | 50.00 | 100.00 | 150.00 | 100.00 |

In every case the code pays the supplier ≈ the original `supplier_price` (so the markup is silently handed to the supplier) and the platform earns roughly **half** of its intended commission.

### Why it's exploitable / impactful
This is a pure money leak from platform to supplier on every supplier-product sale. It is deterministic, not a race — it fires on the normal happy path of every Cryptomus/Mono/balance purchase of a supplier product.

### Fix direction
The earning base must be the **supplier's own price**, not the buyer total. Either compute `supplierAmount` from `product->price` (the supplier-set price) directly, or derive it as `buyerTotal * (1 - c/100)^2`-style cancellation — but the cleanest is `supplierAmount = supplier_price * quantity` (supplier keeps their price, platform keeps the markup), consistent with the model's documented example. Confirm the intended commercial model with product owners before changing.

---

## BUG 2 — Withdrawal payout creates duplicate SupplierEarning rows that violate the unique index → transaction rolls back

**Severity:** Critical · **Confidence:** High
**Files:** `backend/app/Http/Controllers/Admin/WithdrawalRequestController.php:222–240` (markAsPaid split); `backend/app/Models/SupplierEarning.php:138–146` (partialReverse split); unique index `database/migrations/2026_01_05_151711_add_unique_index_to_supplier_earnings_table.php`.

### The conflict
A unique index was added:

```php
$table->unique(['purchase_id', 'transaction_id', 'supplier_id'], 'unique_purchase_transaction_supplier');
```

But `markAsPaid` partial-withdraw logic creates a **new** earning row carrying the **same** `purchase_id`, `transaction_id`, `supplier_id` as the row being split:

```php
\App\Models\SupplierEarning::create([
    'supplier_id'    => $earning->supplier_id,
    'purchase_id'    => $earning->purchase_id,      // same
    'transaction_id' => $earning->transaction_id,   // same
    'amount'         => $earning->amount - $amountToDeduct,
    'status'         => 'available',
    'available_at'   => now(),
]);
```

`SupplierEarning::partialReverse()` (`:139`) does exactly the same when splitting a remainder.

Because `(purchase_id, transaction_id, supplier_id)` is now UNIQUE, the second row insertion throws `QueryException` (Integrity constraint violation). In `markAsPaid` this happens **inside** the `DB::transaction` after `supplier_balance` was already decremented and earlier earnings marked `withdrawn` — so the whole transaction rolls back and the payout cannot complete whenever a withdrawal amount does not exactly line up with whole earning rows (the common case). The admin sees a 500 / generic failure and the supplier cannot be paid out partially.

### Impact
- Partial withdrawals (any amount not equal to the sum of an integer number of earning rows) are **bricked**: the payout always rolls back.
- `partialReverse` (refund path) likewise throws and aborts the refund/reversal.
- This is a correctness/availability break in the money-out path with potential for inconsistent state if any caller swallows the exception outside the transaction.

### Fix direction
Don't reuse the natural key for split rows. Either: (a) deduct in place without creating a sibling row (adjust `amount` on the original + mark a portion withdrawn via a separate "withdrawn ledger" table), or (b) drop the 3-column unique index in favor of an idempotency key that is genuinely unique per accrual event, or (c) include a discriminator column in the unique index. Reconcile the index with the split-row logic.

---

## BUG 3 — Promocode usage idempotency is check-then-insert with no unique constraint → double counting / per-order reuse under concurrency

**Severity:** High · **Confidence:** High
**Files:** `backend/app/Services/ProductPurchaseService.php:520–572` (`recordPromocodeUsage`); `backend/app/Http/Controllers/CartController.php:152–186`; migration `database/migrations/2025_10_07_000004_create_promocode_usages_table.php`.

### The mechanism
Idempotency for promocode usage is claimed to be "per order" via `PromocodeUsage.order_id`. But the schema declares only:

```php
$table->string('order_id')->nullable()->index();   // plain index, NOT unique
```

and the guard is a non-atomic check-then-insert:

```php
$existingUsage = PromocodeUsage::where('order_id', (string)$orderId)->first(); // SELECT
if ($existingUsage) return;
// ... lockForUpdate() is taken on the PROMOCODE row, not on order_id ...
PromocodeUsage::create([...]);                                                 // INSERT
```

The `lockForUpdate()` is on the `promocodes` row, which does **not** serialize two transactions keyed on the same `order_id` against each other for the existence check (both can read "not found" before either inserts). Payment providers (Cryptomus and Monobank) explicitly retry webhooks and warn that they arrive out of order — two concurrent retries for the same order can both pass the `first()` check and both `increment('usage_count')`.

### Impact
- `usage_count` over-counts → a limited promocode is exhausted faster than intended, OR (worse) under the per-user gate it inflates a user's apparent usage.
- For unlimited codes the increment is unconditional (`:563`), compounding the over-count.
- Combined with BUG 4's retry surface, a promocode's `usage_limit` can be exceeded because the limit is enforced in app code (`if usage_count < usage_limit`) rather than by a DB constraint.

### Fix direction
Add a UNIQUE constraint on `promocode_usages.order_id` (and rely on insert-or-ignore / catch duplicate) so the database — not a TOCTOU app check — guarantees one usage row per order. Same applies to the CartController duplicate of this logic.

---

## BUG 4 — Top-up double-credit: 24h idempotency window + status flipped before credit

**Severity:** High · **Confidence:** Medium
**Files:** `backend/app/Services/BalanceService.php:69–80, 291–313`; `backend/app/Http/Controllers/MonoController.php:436–456`; `backend/app/Http/Controllers/CryptomusController.php:477–496`.

### The mechanism
Top-up idempotency depends **entirely** on `BalanceService::findDuplicateTransaction`, which only looks back **24 hours**:

```php
->where('created_at', '>', now()->subHours(24))
->where('status', self::STATUS_COMPLETED)
->whereRaw("JSON_EXTRACT(metadata,'$.invoice_id') = ?", [$invoiceId]);
```

For top-ups the webhook handlers do **not** use the `Purchase`-exists short-circuit (that guard at `MonoController.php:124` / `CryptomusController.php:420` only covers `payment_type ∈ {user, guest}`). So a top-up webhook delivered/replayed by the provider **more than 24h** after the original credit will not be deduplicated and credits the balance a second time. Providers can legitimately re-deliver on long backoffs, manual re-sends, or reconciliation jobs.

Aggravating factors:
- `transaction->status` is set to `'completed'` **before** `topUp()` is called (`MonoController.php:438`, `CryptomusController.php:479`). The transaction-status flag is therefore not usable as a second idempotency signal, and there is no `BalanceTransaction`/DB unique key on `invoice_id`/`order_id`.
- The dedup `SELECT` is not row-locked and runs **outside** the `DB::transaction` that performs the credit (`BalanceService::topUp` checks duplicates at `:69` before opening the transaction at `:83`), so two simultaneous webhook deliveries within the window can both pass the check and both credit (classic check-then-act race).

### Impact
Balance can be credited twice for a single payment (lost money) via (a) provider re-delivery after the 24h window, or (b) two near-simultaneous deliveries racing the unlocked dedup check.

### Fix direction
Enforce idempotency at the database layer: a UNIQUE constraint on the top-up's `invoice_id`/`order_id` (e.g., a dedicated `idempotency_key` column on `balance_transactions` or `transactions`), inserted inside the same locked transaction as the credit. Remove the 24h time bound for the uniqueness guarantee.

---

## BUG 5 — Cryptomus signature verified over a re-encoded (round-tripped) payload

**Severity:** High · **Confidence:** Medium-High
**File:** `backend/app/Http/Middleware/VerifyWebhookSignature.php:55–81`.

### The mechanism
```php
$data = json_decode($rawData, true);
...
unset($data['sign']);
$expected = md5(base64_encode(json_encode($data, JSON_UNESCAPED_UNICODE)) . $paymentKey);
return hash_equals($expected, $signature);
```

The signature is recomputed from `json_encode(json_decode($rawData))` — i.e., PHP re-serializes the decoded body rather than signing over a canonical/raw representation. Cryptomus computes its `sign` from **its own** JSON serialization. PHP's `json_encode` will differ from the sender's bytes whenever:
- numeric values are reformatted (e.g., `"amount":"10.00"` vs `10`, trailing-zero/float normalization),
- key ordering differs from PHP's associative-array order after decode,
- slashes/Unicode escaping differ (only `JSON_UNESCAPED_UNICODE` is set; `JSON_UNESCAPED_SLASHES` is **not**, so `/` in URLs becomes `\/`),
- nested objects vs arrays are reshaped by `json_decode(..., true)`.

### Impact
Primarily a **fail-open-to-fail-closed correctness risk**: legitimate webhooks whose canonical form doesn't byte-match PHP's re-encode are rejected (payments silently not credited; reconciliation burden). It is the documented Cryptomus quirk, and many integrations get it subtly wrong. The compare itself uses `hash_equals` (good, constant-time). The security downside is secondary but real: because verification correctness hinges on exactly reproducing the provider's serialization, any drift pushes operators toward disabling verification (see BUG 12) or loosening the check. Confirm against live Cryptomus payloads that the round-trip is byte-identical; if not, this is a hard outage, and the "fix" of relaxing it would be a signature bypass.

### Fix direction
Follow Cryptomus's documented signing exactly (sign over the raw received body with `sign` removed using the provider's serialization rules — typically `JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES` and preserving original key order). Add tests with captured real payloads.

---

## BUG 6 — `markAsPaid` FIFO consumes only `available` earnings while the balance math counts matured `held` rows

**Severity:** Medium · **Confidence:** High
**File:** `backend/app/Http/Controllers/Admin/WithdrawalRequestController.php:84–118` (approve) and `:183–241` (markAsPaid).

### The mechanism
- Approval availability (`:92–108`) and supplier "available" displays count `status='available'` **OR** (`status='held'` AND `available_at <= now`).
- `markAsPaid` first calls `syncSupplierBalance` (moves matured `held` → `available` and increments `supplier_balance`), then decrements `supplier_balance` by the full amount, then runs a FIFO loop that **only** selects `status='available'` rows to mark `withdrawn` (`:200–205`).

If, between sync and the FIFO loop (or due to a sync failure that is swallowed — `syncSupplierBalance` catches and logs in the supplier-side variant), some matured earnings remain `held`, then:
- `supplier_balance` is decremented correctly, but the earning ledger marks fewer rows `withdrawn` than the cash paid out (`remainingAmount` ends > 0 and is silently ignored).
- The earning ledger and `supplier_balance` **desync**: a later `syncSupplierBalance` may move those still-`held` rows into `supplier_balance` again, effectively re-crediting amounts that were already paid out.

### Impact
Over-payment / double-availability of supplier funds across withdrawal + later sync cycles; ledger no longer reconciles with `supplier_balance`. There is no assertion that `remainingAmount == 0` after the FIFO loop.

### Fix direction
Make `markAsPaid` consume the exact same earning set the availability calc trusts (including matured `held` rows), and assert `remainingAmount == 0` (throw to roll back otherwise). Treat a `syncSupplierBalance` failure as fatal to the payout rather than logging and continuing.

---

## BUG 7 — Guests bypass per-user promocode limit (unlimited reuse)

**Severity:** Medium · **Confidence:** High
**Files:** `backend/app/Services/PromocodeValidationService.php:54–70`; `backend/app/Models/Promocode.php:60–72`; guest payment creation `CryptomusController.php:202`, `MonoController.php:218`.

### The mechanism
`PromocodeValidationService::validate` only checks `per_user_limit` when `$userId` is non-null:

```php
if ($userId && (int)($promocode->per_user_limit ?? 0) > 0) { ... }
// гостей (userId = null) per_user_limit не проверяется
```

`Promocode::canUserUse(null)` likewise `return true`. Guest checkout always passes `userId = null`. There is **no** email/IP-based throttle anywhere. The only backstop is the global `usage_limit` (`exhausted`), which is often 0/unlimited for marketing codes.

### Impact
A single promotional percentage code can be applied to an unlimited number of guest orders by the same person (different email each time, or even the same email — `recordPromocodeUsage` keys on `order_id`, which is unique per order). Direct revenue loss on discount abuse. The code itself acknowledges this in comments.

### Fix direction
Enforce a guest throttle on `per_user_limit`/`usage` by `guest_email` (and/or IP) for promocodes, or disallow `per_user_limit` codes on guest checkout.

---

## BUG 8 — Monobank top-up does not verify webhook `amount`/`ccy` against the created invoice

**Severity:** Medium · **Confidence:** Medium
**File:** `backend/app/Http/Controllers/MonoController.php:402–456`.

### The mechanism
The Mono top-up handler credits the balance from the **webhook body** amount:

```php
$amountDecimal = round((float)($amount / 100), 2);   // $amount from webhook body (minor units)
$balanceService->topUp(user: $user, amount: $amountDecimal, type: TYPE_TOPUP_CARD, ...);
```

It never compares `$amountDecimal` to `transaction->metadata['amount']` (the amount the invoice was created for), nor checks the webhook `ccy` against the invoice currency. The body is ECDSA-signed by Mono (so it's authentic *from Mono*), which limits attacker control — but:
- There is no defense-in-depth assertion that the paid amount equals the requested amount (partial-pay/over-pay scenarios, or a future hold/capture mismatch, credit whatever the webhook says).
- **Currency confusion:** `MonoPaymentService::createInvoice` builds the invoice `ccy` from `Option::get('currency')` mapped to ISO-4217, but the top-up credits a *scalar* balance amount with no currency awareness. If the platform currency Option and the actual invoice currency ever diverge (or fallback-to-UAH at `MonoPaymentService.php:40` kicks in for an unrecognized currency), a balance denominated in one currency is credited a number paid in another (e.g., 100 UAH paid → 100 "USD" balance). The contrast with Cryptomus (which credits the server-stored `metadata['amount']`, not the provider-reported paid amount) shows the two providers are inconsistent here.

### Impact
Mis-crediting on amount/currency mismatch; loss of an obvious reconciliation invariant. Lower severity because the body is signed, but the missing cross-check is a real correctness gap.

### Fix direction
Assert `webhook amount (in minor units) == round(metadata['amount']*100)` and webhook `ccy == invoice ccy` before crediting; reject/log mismatches. Persist and credit using the invoice's stored amount, like the Cryptomus path.

---

## BUG 9 — `findDuplicateTransaction` mutates a shared builder; fragile JSON comparison

**Severity:** Low · **Confidence:** High
**File:** `backend/app/Services/BalanceService.php:291–313`.

```php
$query = BalanceTransaction::where(...);
if (isset($metadata['invoice_id'])) {
    $query->whereRaw("JSON_EXTRACT(metadata,'$.invoice_id') = ?", [$metadata['invoice_id']]);
    $result = $query->first();
    if ($result) return $result;
}
if (isset($metadata['order_id'])) {
    $query->whereRaw("JSON_EXTRACT(metadata,'$.order_id') = ?", [$metadata['order_id']]);  // ANDed onto same builder
    return $query->first();
}
```

Issues:
- If a future caller passes **both** keys, the `order_id` clause is `AND`-ed onto the already-`invoice_id`-filtered builder, so the order_id branch can only ever match a row that has *both* — likely always empty, silently defeating dedup.
- `JSON_EXTRACT(...) = ?` compares a JSON-typed value against a bound scalar; depending on MySQL version/`sql_mode`, `JSON_EXTRACT` may return a quoted JSON string (`"abc"`), which won't equal the plain bound `abc`. `JSON_UNQUOTE(JSON_EXTRACT(...))` (i.e. `->>`) is the safe form. A silent non-match here means **dedup fails open → double credit** (feeds BUG 4).

### Fix direction
Use separate query builders per key, and `JSON_UNQUOTE(JSON_EXTRACT(...))` / `->>` for the comparison. Better: replace JSON-probe dedup with a real unique column.

---

## BUG 10 — `genMonoPubKey.php` stores raw response as the public key

**Severity:** Low · **Confidence:** Medium
**File:** `backend/genMonoPubKey.php:50–60`.

The script does `$publicKey = trim($response)` and prints it for `.env`. Per Mono's `/api/merchant/pubkey` docs the response is a JSON object (`{"key":"<base64>"}`), not a bare key string. If so, `MONOBANK_PUBLIC_KEY` ends up as `{"key":"..."}`, `base64_decode` + `openssl_pkey_get_public` fail, and **all** Mono webhooks are rejected (`verifyMonobankSignature` returns false → 403). Fails closed (no security bypass), but is an operational landmine and may pressure operators toward disabling verification (BUG 12).

### Fix direction
Parse the JSON envelope and extract the `key` field before printing.

---

## BUG 11 — `CryptomusMiddleware` IP allowlist trusts spoofable headers (dead code, latent footgun)

**Severity:** Low · **Confidence:** High
**File:** `backend/app/Http/Middleware/CryptomusMiddleware.php:11–26`.

The middleware allowlists a single IP but reads it from `X-Forwarded-For` / `X-Real-Ip` first, both fully attacker-controllable unless a trusted proxy strips them. `in_array($ip, ['91.227.144.54'])` on `X-Forwarded-For` also fails to handle the comma-separated multi-hop form. It is **not** wired to the webhook routes (they use `verify.webhook`), so currently inert — but if anyone swaps it in as the guard, it's a trivial bypass (just send `X-Forwarded-For: 91.227.144.54`).

### Fix direction
Delete it, or if kept, validate against `Request::ip()` only behind a configured trusted-proxy set; never trust raw forwarded headers for an allowlist.

---

## BUG 12 — Global kill-switch can disable all webhook signature verification

**Severity:** Low (config-dependent; Critical if misconfigured) · **Confidence:** High
**File:** `backend/app/Http/Middleware/VerifyWebhookSignature.php:21–27`.

```php
if (!config('app.verify_webhooks_enabled', true)) { return $next($request); }
```

When `verify_webhooks_enabled=false`, **both** webhook endpoints accept any unauthenticated POST. Since the webhooks credit balances and deliver products, a single env misconfiguration in production turns `/api/cryptomus/webhook` and `/api/mono/webhook` into open "give me balance / give me goods" endpoints (attacker crafts a body with a known/guessable `order_id`/`invoice_id` of a pending transaction → top-up credited, purchase delivered). Default is safe (`true`), but the kill-switch has no environment guard.

### Fix direction
Refuse to honor the disable flag when `APP_ENV=production` (or remove it). Log loudly and alert when verification is off.

---

## Ruled Out / Verified Safe

- **Negative top-up / deduction amounts:** `BalanceService::topUp`/`deduct` throw `InvalidArgumentException` on `amount <= 0` (`:57`, `:162`). Admin voucher creation validates `amount >= 0.01`. ✔
- **Overdraft / negative balance on debit:** `deduct` re-reads balance under `User::lockForUpdate()` inside the transaction and throws if `balance < amount`, plus a second negative-result guard (`:178–199`). Authoritative check is correctly inside the lock. ✔
- **Balance debit lost-update race:** `deduct` and `topUp` both lock the user row (`lockForUpdate`) before read-modify-write; the CartController balance checkout wraps `deduct` + purchase creation in one `DB::beginTransaction`. The pre-check at `CartController.php:76` is advisory only; the real check is under lock. ✔
- **Voucher double-activation race:** `VoucherController::activate` takes `Voucher::where(code)->lockForUpdate()` inside `DB::transaction`, re-checks `isUsed()` after the lock, and the whole credit rolls back on failure. ✔ (Note: voucher amount is trusted from the admin-created row, which is correct.)
- **Purchase webhook price/stock tampering:** Both providers re-lock each `ServiceAccount` (`lockForUpdate`), re-check stock, and recompute totals from `getCurrentPrice()` at webhook time — the client/metadata price snapshot is **not** trusted for charging. ✔
- **Purchase double-delivery idempotency:** Existence of a `Purchase` for `transaction_id` short-circuits both Cryptomus and Mono purchase handlers ("Already processed"). ✔ (Top-ups are the gap — see BUG 4.)
- **Supplier earning accrual idempotency:** Unique index `(purchase_id, transaction_id, supplier_id)` + pre-check prevents duplicate accrual rows on webhook replay. ✔ (But the same index breaks the split logic — see BUG 2.)
- **Discount > 100% / free order:** Combined personal + promo discount is capped at 99% and total floored to `max(round(total,2), 0.01)` in CartController/Mono/Cryptomus authenticated flows. Buyer always pays ≥ 0.01. ✔ (Guest flow applies a single integer-percent promo, also floored to 0.01.)
- **Percentage discount > 100 on a single code:** Discount comes from `percent_discount` cast to int and is summed under the 99% cap; no path multiplies beyond it. ✔ (Guest path lacks the cap but uses a single code and floors to 0.01, so total stays ≥ 0.01.)
- **IDOR on withdrawals:** `Supplier\WithdrawalController::cancel` checks `withdrawal->supplier_id !== auth()->id()` → 403. Admin withdrawal actions are under the admin route group. ✔
- **Withdrawal over-available / double-approve:** `store` recomputes available minus pending withdrawals under lock; `approve` requires `status==='pending'` and re-checks funds; `markAsPaid` requires `status==='approved'`; `reject` forbids `paid`. State machine guards are present (the desync in BUG 6 is the residual issue). ✔ for the basic abuse cases.
- **Monobank signature compare:** ECDSA `openssl_verify(rawBody, sig, pubkey, SHA256)` over the **raw** request content (not a re-encode) and requires `=== 1`; missing header/key → reject. Verifies over the correct payload. ✔ (Provisioning caveat is BUG 10.)
- **Transactions list authorization:** `Api\TransactionController::index` scopes to `$user->transactions()`; unauthenticated → 401. No IDOR. ✔
- **Promocode per-user limit (authenticated):** Soft check in validate + authoritative recording under promocode-row lock. The residual issue is the order_id idempotency (BUG 3) and guests (BUG 7), not the authenticated per-user gate itself. ✔ for authenticated single-threaded use.

---

## Top Priorities (recommended order)
1. **BUG 1** — fix supplier earning base (ongoing money leak on every supplier sale).
2. **BUG 2** — reconcile the supplier-earning unique index with the split-row payout/reversal logic (partial withdrawals currently broken / can corrupt state).
3. **BUG 4 + BUG 3** — replace 24h/TOCTOU idempotency with DB unique constraints on top-up keys and `promocode_usages.order_id` (double-credit / double-count).
