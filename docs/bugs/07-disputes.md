# 07 — Product Disputes — Security & Correctness Audit

Adversarial audit of the Product Dispute subsystem (buyer API, admin web, supplier web, auto-close job, money path). Scope per `docs/functionality/07-disputes.md`.

Method: traced the refund money path (`resolveWithRefund` → `BalanceService::topUp` → buyer balance; supplier clawback via `SupplierEarning::reverse` + `supplier_balance` decrement) and every ownership / state-machine check. Each finding below is labelled with a confidence level and a concrete reproduction.

---

## Summary table

| # | Title | Severity | Confidence | Money? |
|---|---|---|---|---|
| 1 | Refund issued but delivered credentials never clawed back (refund + keep product) | High | High | Yes |
| 2 | Duplicate disputes on one purchase (TOCTOU, no unique constraint) | Medium | High | Indirect |
| 3 | `store` does not validate transaction status — dispute on pending/failed/already-refunded purchase | Medium | High | Yes |
| 4 | `refund_amount` trusts transaction amount but refunds to **balance** regardless of original pay method / partial value | Medium | Medium | Yes |
| 5 | Auto-close hardcodes `resolved_by = 1` with no existence/role check; refunds with stale seeded `refund_amount` | Medium | High | Yes |
| 6 | `notifySupplier()` null-deref when `serviceAccount` was set-null (product deleted) | Low | High | No |
| 7 | Buyer-controlled `screenshot_link` rendered into admin/supplier `href` (open-redirect / scheme abuse) | Low | Medium | No |
| 8 | `canDispute` vs `store` eligibility divergence (status + service-account fallback) exploitable | Low | High | No |
| 9 | `deduct_from_supplier` toggle validated but ignored — supplier always clawed back | Low | High | Yes (supplier) |
| 10 | Replacement keeps original supplier earning AND burns a second unit, with no buyer charge | Low | High | Yes (supplier/platform) |

---

## BUG-01 — Refund issued, delivered account/credentials never revoked (refund-and-keep)

- **Severity:** High
- **Confidence:** High
- **Files:** `app/Models/ProductDispute.php:151-243` (`resolveWithRefund`), `app/Http/Controllers/Admin/ProductDisputeController.php:90-118`, `app/Console/Commands/AutoCloseDisputes.php:55-74`

**What happens:** When a refund is granted, `resolveWithRefund`:
1. credits the buyer's site balance (`BalanceService::topUp`, `:170`),
2. reverses the supplier earning (`:212`),
3. flips `transaction->status = 'refunded'` (`:225`),
4. updates the dispute record.

There is **no step that revokes the product the buyer already received** — the `Purchase` row, its `account_data` (the actual login/password/credential payload), and the `ServiceAccount`'s consumed stock slot are all left untouched. The buyer keeps full, working access to the delivered account *and* gets 100% of the money back on balance. For digital-goods disputes (`invalid_account`, `banned`, `not_working`) the seller may genuinely be at fault, but the platform has no mechanism to confirm the credential is actually dead — a buyer can dispute a perfectly working account, receive a full refund, and continue using it.

**Why it matters / money path:** This is the classic digital-marketplace "refund + keep" abuse. Combined with auto-close (BUG-05), a buyer can buy → open a dispute with any reason + a screenshot → wait out the silence window → receive an automatic full refund while retaining the account. Net loss is borne by the supplier (clawback) or the platform (admin-owned products).

**Repro:**
1. Buy an auto-delivered account; receive credentials in `Purchase.account_data`.
2. Open a dispute (`POST /disputes`), attach any screenshot.
3. Admin/auto-close resolves with refund.
4. Balance is credited; credentials in the purchase record still work — nothing is invalidated.

**Fix direction:** On refund, mark the `Purchase` as `refunded`/revoked, scrub or flag the `account_data`, and (where the delivery model allows) rotate/disable the underlying account. At minimum record that the credential is forfeit so re-sale/abuse can be detected.

---

## BUG-02 — Duplicate disputes on one transaction (TOCTOU; no unique constraint)

- **Severity:** Medium
- **Confidence:** High
- **Files:** `app/Http/Controllers/Api/ProductDisputeController.php:103-108` + `:176-187`; migration `database/migrations/2025_11_03_112558_create_product_disputes_table.php` (no unique index on `transaction_id`)

**What happens:** `store` guards duplicates with a non-atomic read-then-write:
```php
if ($transaction->dispute()->exists()) { ... 422 ... }   // :103
...
$dispute = ProductDispute::create([...]);                // :176
```
There is **no unique constraint** on `product_disputes.transaction_id` (confirmed in the create migration — only plain indexes on `[user_id,status]`, `[supplier_id,status]`, `[status,created_at]`) and **no row lock** on the transaction. Two concurrent `POST /disputes` for the same `transaction_id` both pass `exists()` (both see zero rows) and both insert. `Transaction::dispute()` is a `hasOne` (`Transaction.php:35-37`), so the system now has an inconsistent state: two dispute rows for one purchase, while `dispute()` arbitrarily returns one.

**Why it matters:** Direct double-refund is *mitigated* by the `transaction->status === 'refunded'` check inside `resolveWithRefund` (`:163-165`) — the second resolution on the same (now-refunded) transaction throws. So this is **not** a clean double-refund. But the duplicate rows still cause: (a) two admin work-items / two supplier notifications, (b) `total_refunded` and rating stats double-count, (c) operational confusion, and (d) if the two disputes are resolved via *different* paths (one refund, one replacement) the replacement path (BUG-10) has **no transaction-status guard**, so a buyer could get a **refund on dispute A and a free replacement product on dispute B** for the same purchase.

**Repro:** Fire two simultaneous `POST /disputes` with the same `transaction_id` (e.g. via a script). Both return 201. Resolve one as refund, the other as replacement → money back + free product.

**Fix direction:** Add a DB unique constraint on `transaction_id`, or `lockForUpdate` the transaction inside `store` before the `exists()` check, and add the `transaction.status === 'refunded'` guard to the replacement path too.

---

## BUG-03 — `store` never checks transaction status (dispute on pending/failed/already-refunded purchase)

- **Severity:** Medium
- **Confidence:** High
- **Files:** `app/Http/Controllers/Api/ProductDisputeController.php:80-209`; contrast `canDispute` `:283-293`

**What happens:** `canDispute` checks `status_ok = status ∈ {completed, success, null}` (`:287`). `store` performs **no such check** — it validates ownership, duplicate, 30-day window, and service-account resolution, but never inspects `transaction->status`. `canDispute` is a client-side advisory only; nothing forces the client to call it. A buyer can therefore `POST /disputes` directly against a transaction in *any* status, including:
- `pending`/`failed` (purchase never completed) — opens a dispute on something they may not have paid for;
- `refunded` — opens a **second** dispute on an already-refunded transaction.

**Money path:** The `refunded` case is the dangerous one. `store` seeds `refund_amount = transaction->amount` and creates a NEW dispute on an already-refunded transaction. When an admin resolves it with refund, `resolveWithRefund`'s `transaction->status === 'refunded'` check (`:163`) does fire and blocks the second balance credit — *good*. **But** the admin can instead resolve it as **replacement** (`resolveWithReplacement` has no refunded-status guard), handing the buyer a free product on a transaction that was already fully refunded. So: refund the purchase, then dispute the refunded transaction, then get a free replacement.

**Repro:** Refund a purchase (or get it auto-closed). Then `POST /disputes` on the same `transaction_id` — passes (no status check, and if BUG-02 timing isn't even needed because the original dispute may be resolvable). Admin resolves as replacement → free product.

**Fix direction:** Mirror `canDispute`'s `status_ok` check in `store`; explicitly reject `status === 'refunded'` and non-completed statuses.

---

## BUG-04 — Refund amount/destination integrity: full transaction amount → site balance regardless of pay method or partial consumption

- **Severity:** Medium
- **Confidence:** Medium
- **Files:** `app/Models/ProductDispute.php:186` (seed), `:104` (admin re-seed), `:170-180` (`topUp` to balance)

**What happens:** `refund_amount` is always `transaction->amount` (the full purchase price), and refunds always land on the buyer's **internal site balance** via `BalanceService::topUp(TYPE_REFUND)` — never back to the original card/crypto instrument. Two correctness concerns:

1. **Quantity / partial:** If a transaction covered a multi-quantity purchase (qty > 1) or the buyer only disputes one of several delivered accounts, the buyer still receives a refund of the *entire* `transaction->amount`. There is no per-unit proration; `refund_amount` = whole transaction.
2. **Refund-to-balance laundering vector:** Card/crypto top-ups have anti-fraud and chargeback semantics; converting a card purchase into *withdrawable site balance* via a dispute can be used to move funds, especially combined with BUG-01 (keep the product) and BUG-05 (automatic). The `BalanceService::topUp` idempotency guard (`findDuplicateTransaction`, `:69-80`) is **not engaged** for refunds because the refund metadata passes `dispute_id`/`transaction_id` but **no `invoice_id` or `order_id`** — so the only protection against repeated credits is the dispute/transaction status guards, not `BalanceService` itself.

**Why "Medium / Medium":** The per-purchase refunded-status guard prevents trivially crediting the same transaction twice through the refund path; the concern is the policy correctness (full-amount, to-balance, no proration) and the laundering surface, not a single-call double-credit.

**Fix direction:** Prorate `refund_amount` for partial/qty disputes; consider refunding to original instrument or marking balance from refunds as non-withdrawable; pass an idempotency key (e.g. `order_id = "dispute_{id}"`) into `topUp` so `BalanceService` itself enforces single-credit.

---

## BUG-05 — Auto-close: hardcoded resolver ID 1, refunds with stale seeded amount, runs `resolveWithRefund` for admin-owned (no clawback) silently

- **Severity:** Medium
- **Confidence:** High
- **Files:** `app/Console/Commands/AutoCloseDisputes.php:55-74`, scheduled hourly (`app/Console/Kernel.php:33`)

**What happens:** For every `new` dispute older than the window, auto-close calls `resolveWithRefund($systemAdminId = 1, $comment)` (`:64-66`).

1. **Hardcoded resolver `1`** with no existence/role check. `resolved_by` FK is `onDelete('set null')`, so if user 1 doesn't exist or isn't an admin, the value is still written (or nulled) — there is no guarantee user 1 is a privileged/system account. If user 1 happens to be a *regular registered user* (common when seed order differs), every auto-refund is attributed to and "performed by" a non-admin.
2. **Stale `refund_amount`:** Auto-close does **not** re-seed `refund_amount`; it relies on the value stored at `store` time (`transaction->amount` at creation). If the transaction amount changed (or was always wrong), the refund uses the stale figure. The admin path re-seeds (`:104`); auto-close does not.
3. **Refunds without human review:** Combined with BUG-01 (keep product) and BUG-03 (no status check), the automatic path is the most abusable — a buyer only needs the seller to stay silent for `dispute_auto_close_hours` to get a guaranteed full refund while keeping the goods. The job is feature-flagged off by default (`dispute_auto_close_enabled`, `:32`), which limits exposure, but when enabled it is fully automatic.
4. **Admin-owned disputes auto-refund with platform absorbing the loss** and no audit beyond the comment string.

**Fix direction:** Resolve the system-admin id from config/role lookup and validate it; re-seed `refund_amount` from the live transaction at close time; gate auto-close behind the same status/eligibility checks as `store`.

---

## BUG-06 — `notifySupplier()` null-deref when product was deleted (`service_account_id` set-null)

- **Severity:** Low
- **Confidence:** High
- **Files:** `app/Models/ProductDispute.php:298-310` (`notifySupplier`), `:304` (`$this->serviceAccount->title`)

**What happens:** `service_account_id` is `onDelete('set null')` (create migration `:19`). If the underlying `ServiceAccount` is deleted after a supplier-owned dispute is opened, `serviceAccount` becomes `null`. `notifySupplier()` builds its message with `$this->serviceAccount->title` (`:304`) **without a null guard**. On refund/replacement resolution of such a dispute, this throws "Attempt to read property 'title' on null".

**Impact:** In `resolveWithRefund`, `notifySupplier()` is called *inside* the DB transaction (`:238`), so the throw **rolls back the entire refund** — the buyer's balance credit, the supplier clawback, and the status change all revert, yet `BalanceService::topUp` may have already logged a "success". The dispute is left stuck `new` and un-resolvable for a deleted product. In auto-close this surfaces as a per-dispute caught error, permanently blocking that dispute from closing.

**Fix direction:** Null-guard `serviceAccount` in `notifySupplier()` (fall back to a generic title); resolution should not hard-depend on the product still existing.

---

## BUG-07 — Buyer-controlled `screenshot_link` rendered into admin/supplier `href` and `onclick`

- **Severity:** Low
- **Confidence:** Medium
- **Files:** `app/Http/Requests/Dispute/CreateDisputeRequest.php:16` (`url` rule only), `resources/views/admin/disputes/show.blade.php:98,108,112`, `resources/views/supplier/disputes/show.blade.php:70,80,84`

**What happens:** When `screenshot_type === 'link'`, `screenshot_url` is the raw buyer-supplied string, validated **only** by Laravel's `url` rule (max 500). It is then emitted into:
- `<a href="{{ $dispute->screenshot_url }}" target="_blank">` (no `rel="noopener noreferrer"`),
- `<img src="{{ $dispute->screenshot_url }}">`,
- `onclick="window.open('{{ $dispute->screenshot_url }}', '_blank')"`.

`{{ }}` HTML-escapes, and the single-quote escaping (`&#039;`) prevents breaking out of the JS string literal in `onclick`, so this is **not** a clean stored-XSS. The residual issues are: (a) `target="_blank"` without `noopener` exposes a reverse-tabnabbing vector against admins/suppliers; (b) the buyer controls a URL that staff are induced to click (phishing / SSRF-via-admin-browser / tracking); (c) Laravel's `url` rule accepts non-`http(s)` schemes that `FILTER_VALIDATE_URL` allows, so the `href` may carry unexpected schemes. The buyer fully chooses where a privileged user's browser navigates.

**Fix direction:** Restrict `screenshot_link` to `http/https` and an allowlist of image hosts (or proxy/re-host it); add `rel="noopener noreferrer"`; drop the inline `onclick`.

---

## BUG-08 — `canDispute` vs `store` eligibility divergence

- **Severity:** Low
- **Confidence:** High
- **Files:** `app/Http/Controllers/Api/ProductDisputeController.php:283-293` (`canDispute`) vs `:118-156` (`store`)

**What happens:** Two inconsistencies between the advisory check and the enforcing endpoint:
1. **Service-account resolution:** `canDispute` only checks `transaction->service_account_id` (`:285`); `store` falls back to the linked `Purchase.service_account_id` (`:122-127`). So a purchase whose product link lives only on the `Purchase` row reports `can_dispute=false` yet **succeeds** in `store`.
2. **Status:** `canDispute` enforces `status_ok` (`:287`); `store` does not (see BUG-03).

`canDispute` is therefore not a trustworthy gate — it is strictly advisory and both *under*-reports (case 1) and *over*-reports (case 2 — it would say not-ok, but `store` ignores status anyway, so the divergence is asymmetric). Clients relying on it for UI logic can be bypassed by calling `store` directly.

**Fix direction:** Extract a single shared eligibility method used by both endpoints.

---

## BUG-09 — `deduct_from_supplier` validated but ignored

- **Severity:** Low
- **Confidence:** High
- **Files:** `app/Http/Controllers/Admin/ProductDisputeController.php:94` (validates `deduct_from_supplier`), `resolveWithRefund` `:191-222` (never reads it)

**What happens:** The refund form validates a `deduct_from_supplier` boolean and the blade renders the checkbox default-checked (`show.blade.php:194-201`), but `resolveWithRefund` **always** reverses the supplier earning and decrements `supplier_balance` when supplier-owned, regardless of the flag. Unchecking it has no effect — an admin who intends *not* to charge the supplier (e.g. platform-fault refund) still claws back from the supplier. This is a correctness/trust bug that silently overrides admin intent and unfairly debits suppliers.

**Fix direction:** Either honor the flag in `resolveWithRefund` (skip clawback when false) or remove the control.

---

## BUG-10 — Replacement: supplier keeps original earning, loses a second unit; no charge, no transaction-status guard

- **Severity:** Low
- **Confidence:** High
- **Files:** `app/Http/Controllers/Admin/ProductDisputeController.php:123-187`, `ProductDispute::resolveWithReplacement` `:248-271`, `ProductPurchaseService::createProductPurchase`

**What happens:** Replacement issues a brand-new product to the buyer at `payment_method='replacement'` (no charge) via `createProductPurchase`, which **consumes a second stock unit** and **creates a fresh SupplierEarning for the replacement product's supplier** (`ProductPurchaseService:264-304`). Meanwhile the *original* purchase's supplier earning is **not** reversed (replacement does no clawback). Consequences:

1. The replacement path has **no `transaction->status === 'refunded'` guard** and no row-lock on the dispute (unlike `resolveWithRefund`). Combined with BUG-02/BUG-03, a buyer can obtain a refund on one dispute and a free replacement on a duplicate/second dispute for the same purchase.
2. The replacement product is chosen by `replacement_account_id` (`required|exists:service_accounts,id`). The controller re-validates same-`service_id`/same-`supplier_id`/stock at resolve time only via `getReplacementProducts` filtering for the *select options*; but `resolveReplacement` itself only checks `getAvailableStock() > 0` and `id !== dispute->service_account_id` (`:145-153`) — it does **not** re-verify the chosen account shares the original's `service_id`/`supplier_id`. An admin (or a forged form post, since it's a plain `exists` rule) can pick an **arbitrary, more expensive, cross-service, or cross-supplier** account as the "replacement," handing the buyer any product for free and burning that unrelated supplier's stock. The earning created lands on whichever supplier owns the chosen account.
3. For manual-delivery products `getAvailableStock` returns a sentinel `999` (`ServiceAccount.php:285-292`), so the stock guard is effectively bypassed for manual products.

**Fix direction:** In `resolveReplacement`, re-assert the replacement account's `service_id` and `supplier_id` match the disputed product server-side; add the refunded/terminal-status guard and dispute row-lock as in the refund path.

---

## Ruled out (checked, not vulnerable)

- **Stored XSS of `customer_description` / `admin_comment` in blades:** Rendered with `{{ }}` (HTML-escaped) in both admin and supplier `show.blade.php` (`:90,135` admin; `:62,115` supplier). No `{!! !!}` on dispute text. Not exploitable. (Residual `href`/`onclick` concern captured separately in BUG-07.)
- **SVG/HTML file-upload XSS:** `screenshot_file` validated with `image` + `mimes:jpeg,png,jpg,webp` (`CreateDisputeRequest:15`). The `image` rule rejects SVG; mime allowlist excludes svg/html. Stored on `public` disk, served statically (not executed). Not exploitable.
- **Path traversal in upload filename:** Filename is constructed server-side as `dispute_{time}_{userId}.{ext}` via `storeAs` (`store:165-166`); the client filename is not used for the path. No traversal.
- **IDOR opening a dispute on another user's purchase:** `store` enforces `$transaction->user_id !== $request->user()->id → 403` (`:95-100`), and `transaction_id` is loaded by id but ownership-checked. Guest purchases (no matching `user_id`) are rejected. Not vulnerable. *(Note: the 403 message "Транзакция не найдена" leaks existence vs ownership only via status code, negligible.)*
- **IDOR viewing another user's dispute:** `index`/`show` scope via `$request->user()->disputes()` (`:26-37`, `:222-232`) → 404 for others. Supplier `show` aborts 403 if `supplier_id` mismatch (`Supplier/DisputeController.php:60-62`). Not vulnerable.
- **Double-refund by re-resolving the same dispute:** Blocked by `lockForUpdate` re-read + in-transaction status re-check (`resolveWithRefund:155-165`) and the `transaction.status==='refunded'` guard. The terminal-status controller guards (`:98-101`, `:131-134`, `:199-202`) add defense-in-depth. Effective for the same dispute. (Cross-dispute gaps captured in BUG-02/03/10.)
- **Resolve-after-auto-close double refund:** Auto-close goes through the same `resolveWithRefund` (sets transaction `refunded` + dispute `resolved`); a subsequent admin resolve hits the terminal-status guard and the refunded-transaction guard. Not a double refund.
- **Supplier earnings double-reverse:** `resolveWithRefund` queries `where('status','!=','reversed')` (`:196`) and `SupplierEarning::reverse()` refuses already-`reversed`/`withdrawn` rows (`:73-91`). No double-reverse of the same earning. *(The pre-reverse `supplier_balance` decrement for `withdrawn`/`available` is documented behavior, not a double-reverse.)*
- **Supplier clawback null-deref on admin-owned (NULL supplier):** Clawback branch is guarded by `if ($dispute->supplier_id && $dispute->supplier)` (`:192`); admin-owned (NULL) skips it cleanly. Not vulnerable. *(The unrelated `serviceAccount` null-deref is BUG-06.)*
- **Bypassing the 30-day window:** Enforced in both `canDispute` (`:286`) and `store` (`:111`). `diffInDays` is absolute whole-day; boundary is ≤30. No bypass found.
- **Mass-assignment of `status`/`admin_decision`/`refund_amount` via `store`:** `store` builds the create array explicitly from `$validated` + server-derived fields; the FormRequest only allows `transaction_id/reason/description/screenshot_*`. Buyer cannot inject `status`, `refund_amount`, `supplier_id`, or `resolved_by`. Not vulnerable.
