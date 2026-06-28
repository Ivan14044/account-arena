# 07 — Product Disputes

Functional inventory of the **Product Dispute** subsystem of Account Arena: buyer-opened claims over purchased products, across buyer (API), admin (web/blade), and supplier (web/blade) surfaces, plus the automated auto-close job.

A "dispute" (Russian: *претензия*) is always attached to **one `Transaction`** (and therefore one purchase / one `ServiceAccount` product). The owning entity is either a **supplier** (`supplier_id` set) or the **platform administrator** (`supplier_id = NULL`).

---

## 1. Data model & schema

### 1.1 `product_disputes` table

Migration: `backend/database/migrations/2025_11_03_112558_create_product_disputes_table.php`
- `id`
- `transaction_id` → `transactions.id`, `onDelete('cascade')` (`:16`)
- `user_id` → `users.id`, `onDelete('cascade')` — the buyer (`:17`)
- `supplier_id` → `users.id` — originally NOT nullable (`:18`); **made nullable** later (see below). NULL = admin-owned product.
- `service_account_id` → `service_accounts.id`, nullable, `onDelete('set null')` (`:19`)
- `reason` — enum: `invalid_account`, `wrong_data`, `not_working`, `already_used`, `banned`, `other` (`:22-29`)
- `customer_description` — text, required (`:32`)
- `admin_decision` — enum nullable: `refund`, `replacement`, `rejected` (`:35`)
- `admin_comment` — text nullable (`:38`)
- `refund_amount` — decimal(10,2) nullable (`:41`)
- `status` — enum: `new`, `in_review`, `resolved`, `rejected`, default `new` (`:44`)
- `resolved_at` — timestamp nullable (`:47`)
- `resolved_by` → `users.id`, nullable, `onDelete('set null')` — admin who resolved (`:50`)
- `timestamps`
- Indexes: `[user_id, status]`, `[supplier_id, status]`, `[status, created_at]` (`:55-57`)

Migration: `backend/database/migrations/2025_11_03_172455_add_screenshot_to_product_disputes_table.php`
- `screenshot_url` — string(500) nullable (`:15`)
- `screenshot_type` — string(20) nullable: `'upload'` or `'link'` (`:16`)

Migration: `backend/database/migrations/2025_11_17_150333_make_supplier_id_nullable_in_product_disputes_table.php`
- Drops the FK, makes `supplier_id` nullable, re-adds FK with `onDelete('set null')` (`:18-25`). **No-op on SQLite** (`:14`). This is what enables admin-owned (NULL supplier) disputes.

### 1.2 `ProductDispute` model

File: `backend/app/Models/ProductDispute.php`

- **Status constants** (`:38-41`): `STATUS_NEW='new'`, `STATUS_IN_REVIEW='in_review'`, `STATUS_RESOLVED='resolved'`, `STATUS_REJECTED='rejected'`.
- **Reason constants** (`:44-49`): `REASON_INVALID_ACCOUNT`, `REASON_WRONG_DATA`, `REASON_NOT_WORKING`, `REASON_ALREADY_USED`, `REASON_BANNED`, `REASON_OTHER`.
- **Decision constants** (`:52-54`): `DECISION_REFUND='refund'`, `DECISION_REPLACEMENT='replacement'`, `DECISION_REJECTED='rejected'`.
- **Casts** (`:32-35`): `refund_amount` → `decimal:2`, `resolved_at` → `datetime`.

Relationships:
- `transaction()` belongsTo `Transaction` (`:59`)
- `user()` belongsTo `User` (the buyer) (`:67`)
- `supplier()` belongsTo `User` via `supplier_id`, **with a default** `name='Администратор', email='admin', is_supplier=false` so admin-owned disputes still render a "supplier" label (`:75-82`)
- `serviceAccount()` belongsTo `ServiceAccount` (`:95`)
- `resolver()` belongsTo `User` via `resolved_by` (`:103`)
- `isAdminProduct(): bool` → `supplier_id === null` (`:87-90`)

Query scopes: `new()`, `inReview()`, `resolved()`, `rejected()`, `forSupplier($supplierId)` (`:111-146`).

Label helpers (all use Laravel `__()` translation; see note in §8.1):
- `getReasonText()` (`:349`), `getStatusText()` (`:379`), `getDecisionText()` (`:336`), `getStatusBadgeClass()` (`:365`).

Related models / fields:
- `Transaction::dispute()` — `hasOne(ProductDispute::class)` (`backend/app/Models/Transaction.php:35-37`). Used for the "already disputed" check.
- `Purchase` fillables include `service_account_id`, `transaction_id` (`backend/app/Models/Purchase.php:17-18`). Used as fallback to resolve the product when the transaction lacks `service_account_id`.

---

## 2. Buyer-facing features (API)

Controller: `backend/app/Http/Controllers/Api/ProductDisputeController.php`
Routes (all under `auth:sanctum`, prefix `/api`): `backend/routes/api.php:88-92`

| Feature | Method + Path | Controller method |
|---|---|---|
| List my disputes | `GET /disputes` | `index` (`:18`) |
| Open a dispute | `POST /disputes` | `store` (`:80`) |
| Dispute detail | `GET /disputes/{id}` | `show` (`:214`) |
| Eligibility check | `GET /transactions/{transactionId}/can-dispute` | `canDispute` (`:268`) |

Frontend: all buyer dispute UI lives in a single Vue page **`frontend/src/pages/account/ProfilePage.vue`** (no separate store/component). It posts `POST /disputes` as `multipart/form-data` (`~:2619`), fetches `GET /disputes` (`~:2644`), and has a client-side `canCreateDispute(purchase)` mirror of eligibility (`~:2320`). Status/reason/decision labels are localized client-side under `profile.purchases.disputes.*` i18n keys.

### 2.1 Eligibility — `canDispute`

- **Endpoint:** `GET /transactions/{transactionId}/can-dispute`
- **File:** `Api/ProductDisputeController.php:268-311`
- **Input:** path `transactionId`; authenticated user.
- **Logic:** loads the transaction scoped to `user_id = current user` (`:270-273`). If not found → `{can_dispute:false, reason:'Транзакция не найдена'}` (`:275-280`).
- **Checks array** (`:283-288`):
  - `exists` — a dispute already exists for this transaction (`transaction->dispute()->exists()`).
  - `has_service_account` — transaction has a `service_account_id`.
  - `not_expired` — `created_at` is within 30 days (`diffInDays(now()) <= 30`).
  - `status_ok` — transaction `status` ∈ `['completed','success', null]`.
- **`can_dispute` = !exists && has_service_account && not_expired && status_ok** (`:290-293`).
- **Output:** `{ can_dispute: bool, reason: string|null, checks: {...} }`. The first failing check sets a human reason (`:295-304`).
- **Edge cases / notes:**
  - `not_expired` here is checked only against `transaction->service_account_id` for `has_service_account`. Note the **inconsistency vs `store`**: `store` falls back to the `Purchase` record when the transaction has no `service_account_id`, but `canDispute` does **not** — so a purchase whose product link lives only on the `Purchase` row will report `can_dispute=false` here yet could succeed in `store`.
  - A `null` transaction status is treated as eligible.

### 2.2 Open a dispute — `store`

- **Endpoint:** `POST /disputes`
- **File:** `Api/ProductDisputeController.php:80-209`
- **Validation:** FormRequest `backend/app/Http/Requests/Dispute/CreateDisputeRequest.php`
  - `transaction_id` — required, exists in `transactions` (`:12`)
  - `reason` — required, `in:invalid_account,wrong_data,not_working,already_used,banned,other` (`:13`)
  - `description` — required string, 3–1000 chars (`:14`)
  - `screenshot_file` — nullable image, mimes `jpeg,png,jpg,webp`, max 5120 KB (5 MB) (`:15`)
  - `screenshot_link` — nullable URL, max 500 (`:16`)
- **Extra business validation in controller:**
  1. At least one screenshot (file OR link) is required — else 422 "Необходимо прикрепить скриншот" (`:85-90`).
  2. Transaction must belong to the user → 403 "Транзакция не найдена" (`:95-100`).
  3. No existing dispute for this transaction → 422 "Претензия на эту покупку уже создана" (`:103-108`).
  4. Transaction not older than 30 days (`diffInDays > 30`) → 422 "Срок подачи претензии истек" (`:111-116`).
  5. Must resolve a `service_account_id`: from the transaction, else from the linked `Purchase` row (`:118-127`); if none → 422 "Эта покупка не поддерживает претензии" (`:130-135`).
  6. Product must exist; if `serviceAccount` can't be loaded → 422 "Товар не найден" (`:143-150`).
- **Supplier resolution:** `supplier_id` is taken from the resolved `ServiceAccount`. **NULL supplier_id ⇒ admin-owned product** (`:151-156`).
- **Screenshot handling** (`:158-173`):
  - File upload → stored at `disputes/screenshots/dispute_{time}_{userId}.{ext}` on the `public` disk; `screenshot_url='/storage/...'`, `screenshot_type='upload'`.
  - Link → `screenshot_url = link`, `screenshot_type='link'`.
- **Creation** (`:176-187`): status `STATUS_NEW`; `refund_amount` pre-seeded to `transaction->amount`.
- **Side effects:**
  - `Cache::forget('disputes_new_count')` to refresh the admin badge (`:190`).
  - Admin notification via `NotifierService::send('dispute_created', ...)` (`:194-199`) — see §7.1.
- **Output:** `201` `{ success:true, data:{ message, dispute:{id,status,created_at} } }` (wrapped by `ApiResponse::success`, `:201-208`).
- **Edge cases:**
  - A guest purchase (no `user_id` match) is rejected at step 2.
  - The 30-day window is enforced both in `canDispute` and again in `store` (defense in depth).
  - There is **no validation that the transaction status is completed** inside `store` (unlike `canDispute`, which checks `status_ok`). So a dispute could be opened on a transaction with an unexpected status if the client skips `canDispute`.

### 2.3 List — `index`

- **Endpoint:** `GET /disputes`
- **File:** `Api/ProductDisputeController.php:18-75`
- **Input:** locale via `X-Locale` header / `?locale` / app locale (validated against `config('langs')`, `:21-24`); pagination 20/page.
- **Output:** per-dispute object including `id`, `transaction_id`, `product_title` (object with `title/title_en/title_uk` for client-side localization), `amount` (from transaction), `reason` + `reason_text`, `customer_description`, `screenshot_url`, `screenshot_type`, `status` + `status_text`, `admin_decision` + `admin_decision_text`, `admin_comment`, `refund_amount`, `created_at`, `resolved_at`; plus `pagination` block (`:39-74`).
- Eager-loads transaction's and dispute's `serviceAccount` (selected columns only).

### 2.4 Detail — `show`

- **Endpoint:** `GET /disputes/{id}`
- **File:** `Api/ProductDisputeController.php:214-263`
- Scoped to the user's own disputes via `$request->user()->disputes()->findOrFail($id)` (returns 404 for other users' disputes).
- Same fields as `index`, plus `product_login` (the product's `login`) (`:246`).

---

## 3. Status & state machine

States: `new` → `in_review` → (`resolved` | `rejected`). `resolved` carries one of two decisions: `refund` or `replacement`.

```
                 buyer opens (store)
                        │
                        ▼
                     [ new ]
            ┌───────────┼───────────────────────────┐
   admin markInReview   │  admin resolveRefund /     │ admin resolveRefund/
   (only from new)      │  resolveReplacement / reject│ resolveReplacement / reject
                        ▼  (allowed directly from new)▼  (allowed from in_review)
                  [ in_review ] ───────────────────►  [ resolved ]  (decision = refund | replacement)
                        │                              [ rejected ]  (decision = rejected)
                        └──────────────────────────►
```

Transition rules:
- **new → in_review:** `markInReview`, only if current status is `new` (`Admin/ProductDisputeController.php:220-232`). Otherwise "Невозможно изменить статов".
- **new/in_review → resolved/rejected:** all three resolution actions are allowed from either `new` or `in_review`. They guard against acting on an already-`resolved`/`rejected` dispute (`resolveRefund :98-101`, `resolveReplacement :131-134`, `reject :199-202`). The blade only renders the action forms when status is `new` or `in_review` (`show.blade.php:152`).
- **resolved/rejected:** terminal. No transition back. `refund_amount` and `admin_decision`/`admin_comment`/`resolved_at`/`resolved_by` are finalized.
- **Auto-close** (§6) can move a stale `new` dispute → `resolved`(refund) automatically.

---

## 4. Admin features (web / AdminLTE blade)

Controller: `backend/app/Http/Controllers/Admin/ProductDisputeController.php`
Routes (admin group, web.php `:142-149`):

| Feature | Method + Route name | Controller |
|---|---|---|
| List | `GET admin/disputes` → `admin.disputes.index` | `index` (`:16`) |
| New-count badge | `GET admin/disputes/new-count` → `...new-count` | `getNewCount` (`:258`) |
| Detail | `GET admin/disputes/{dispute}` → `...show` | `show` (`:80`) |
| Mark in review | `PATCH admin/disputes/{dispute}/mark-in-review` | `markInReview` (`:220`) |
| Resolve: refund | `POST admin/disputes/{dispute}/resolve-refund` | `resolveRefund` (`:90`) |
| Resolve: replacement | `POST admin/disputes/{dispute}/resolve-replacement` | `resolveReplacement` (`:123`) |
| Reject | `POST admin/disputes/{dispute}/reject` | `reject` (`:192`) |
| Replacement product options | `GET admin/disputes/{dispute}/replacement-products` | `getReplacementProducts` (`:237`) |

Blade views: `backend/resources/views/admin/disputes/index.blade.php`, `show.blade.php`.

### 4.1 List — `index`

- **File:** `Admin/ProductDisputeController.php:16-75`
- **Filters (query params):** `status`; `owner` (`admin` → `whereNull(supplier_id)`, `suppliers` → `whereNotNull`) (`:26-34`); `supplier_id`; `date_from`/`date_to` on `created_at`; `search` (matches dispute `id`, or user `email`/`name`) (`:51-60`).
- **Stats block** (`:64-72`): counts of `new`, `in_review`, `resolved`, `rejected`, plus `admin_products` (NULL supplier) and `supplier_products` (non-NULL).
- Eager-loads `user, supplier, serviceAccount, transaction.purchase`; paginates 20 with `withQueryString()`.

### 4.2 Detail — `show`

- **File:** `Admin/ProductDisputeController.php:80-85`; view `admin/disputes/show.blade.php`.
- Loads `user, supplier, serviceAccount, transaction.purchase, resolver`.
- Blade shows full claim info, screenshot (inline `<img>` + open link, with upload/link badge `:101-105`), supplier vs "Товар администратора" badge (`:53-61`), linked purchase order number, and resolution metadata once resolved.
- **Action panel** rendered only for `new`/`in_review` (`:152`): "Взять на рассмотрение" (only when `new`, `:154`), refund form, replacement form, reject form. Otherwise a read-only resolved/rejected status card (`:273-291`).
- The replacement `<select>` is populated by an AJAX call to `replacement-products` on page load (`:296-323`).

### 4.3 Resolve with refund — `resolveRefund`

- **File:** `Admin/ProductDisputeController.php:90-118`; core logic in `ProductDispute::resolveWithRefund` (`Models/ProductDispute.php:151-243`).
- **Validation:** `admin_comment` required string ≤1000; `deduct_from_supplier` boolean (`:92-95`).
- **Guard:** if already `resolved`/`rejected` → back with error (`:98-101`).
- Sets `refund_amount = transaction->amount` (`:104`) then calls `resolveWithRefund(auth()->id(), $comment)`.
- **`resolveWithRefund` (inside a DB transaction, with row lock):**
  1. Re-loads the dispute `lockForUpdate()` and re-checks status inside the transaction → throws "Dispute already resolved or rejected" if terminal (prevents double-processing / race) (`:155-160`).
  2. Throws "Transaction already refunded" if `transaction->status === 'refunded'` (`:163-165`).
  3. **Refund to buyer balance** via `BalanceService::topUp($user, refund_amount, TYPE_REFUND, [dispute_id, transaction_id, admin_id, comment])` (`:167-189`). `TYPE_REFUND='refund'` (`Services/BalanceService.php:32`); creates a `BalanceTransaction` of subtype `balance_refund` with description "Возврат средств на баланс" (`BalanceService.php:333,355`). Money returns to the buyer's **site balance**, not to the original payment method.
  4. **Supplier earnings clawback** — only if `supplier_id` set and supplier exists (`:192`): finds the non-`reversed` `SupplierEarning` for that `transaction_id`+`supplier_id`. If its status is `withdrawn` or `available`, decrements `supplier_balance` by the earning's `amount` (the commission-adjusted amount, not the full purchase price); if balance insufficient → throws "Insufficient supplier balance for refund" (`:204-210`). Then `supplierEarning->reverse('Product dispute refund')` (`:212`). If **no** earning record exists → throws "Supplier earning record not found…" (manual resolution required), to avoid clawing back the full price incl. service commission (`:213-221`).
  5. Sets `transaction->status = 'refunded'` (`:225`).
  6. Updates dispute: status `resolved`, decision `refund`, comment, `resolved_at`, `resolved_by` (`:228-234`).
  7. Notifications + supplier rating recompute (`:237-241`): `notifySupplier()` + `supplier->calculateSupplierRating()` if supplier-owned; always `notifyCustomer()`.
- **Output:** redirect to `admin.disputes.index` with success "Средства возвращены покупателю", or back with error message (`:113-117`).
- **Edge cases / notes:**
  - **`deduct_from_supplier` is effectively ignored.** The blade checkbox (default checked, `show.blade.php:194-201`) is validated but never read in `resolveRefund`; `resolveWithRefund` always reverses supplier earnings when supplier-owned. Unchecking it has no effect.
  - `SupplierEarning::reverse()` itself refuses to reverse a `withdrawn` earning (returns false + logs) (`Models/SupplierEarning.php:82-91`) — but the controller path already decrements `supplier_balance` for `withdrawn`/`available` before calling reverse, so a withdrawn earning is clawed from the live balance.
  - For admin-owned products (NULL supplier) no clawback occurs; the platform absorbs the refund.

### 4.4 Resolve with replacement — `resolveReplacement`

- **File:** `Admin/ProductDisputeController.php:123-187`; helper `ProductDispute::resolveWithReplacement` (`Models/ProductDispute.php:248-271`).
- **Validation:** `admin_comment` required ≤1000; `replacement_account_id` required, `exists:service_accounts,id` (`:125-128`).
- **Guard:** already `resolved`/`rejected` → error (`:131-134`).
- Wrapped in `DB::transaction` (`:138`):
  1. `resolveWithReplacement(...)` updates the dispute to `resolved`, decision `replacement`, comment, `resolved_at`, `resolved_by`; notifies supplier+recomputes rating (if supplier-owned) and notifies customer (`Models/ProductDispute.php:250-270`).
  2. `lockForUpdate()` the chosen replacement `ServiceAccount` (`:142`).
  3. Stock check: `getAvailableStock() <= 0` → throws "Товар для замены недоступен" (`:145-148`). (`getAvailableStock` returns 999 for manual-delivery active products, else counts `accounts_data` — `Models/ServiceAccount.php:285-292`.)
  4. Self-replacement guard: replacement `id` must differ from `dispute->service_account_id` → throws "Нельзя заменить товар на тот же самый" (`:151-153`).
  5. Issues the new product to the buyer via `ProductPurchaseService::createProductPurchase($replacementAccount, qty=1, currentPrice, currentPrice, userId=dispute->user_id, guestEmail=null, paymentMethod='replacement')` (`:156-167`). The `'replacement'` payment method denotes a $0/no-charge fulfillment. Failures are logged and rethrown as "Ошибка при выдаче товара для замены" (`:168-176`).
- **Side effects:** `Cache::forget('disputes_new_count')` (`:180`).
- **Output:** redirect to index with "Товар заменен", or back with error.
- **Edge cases:**
  - Atomicity: the entire thing is one DB transaction, so a failed stock/self-check/issuance rolls back the dispute status change too.
  - `getReplacementProducts` already filters to same `service_id` + same `supplier_id` + `used=0`, but the controller still revalidates stock and self-equality at resolve time (defense against stale select options / concurrent sale).
  - No supplier-earnings clawback on replacement — the supplier keeps the original earning but loses a second unit of stock; rating still recomputes (replacement counts against rating, see §5.2).

### 4.5 Reject — `reject`

- **File:** `Admin/ProductDisputeController.php:192-215`; helper `ProductDispute::reject` (`Models/ProductDispute.php:276-293`).
- **Validation:** `admin_comment` required ≤1000.
- **Guard:** already terminal → error.
- Updates dispute: status `rejected`, decision `rejected`, comment, `resolved_at`, `resolved_by` (`:278-284`). Notifies customer; recomputes supplier rating if supplier-owned (`:286-292`).
- **Note:** `reject()` is **not** wrapped in its own DB transaction (unlike refund/replacement), but it only performs a single update + notification.
- **Output:** redirect to index "Претензия отклонена".

### 4.6 Replacement product options — `getReplacementProducts`

- **File:** `Admin/ProductDisputeController.php:237-253`
- **Endpoint:** `GET admin/disputes/{dispute}/replacement-products` (JSON; called by AJAX in `show.blade.php:300`).
- Derives `service_id` from the dispute's `serviceAccount`; if none → `{products:[]}` (`:239-243`).
- Returns up to 50 `ServiceAccount`s with the **same `service_id`**, `used=0`, and **same `supplier_id`** as the dispute (`:245-250`), selecting `id, title, sku, service_id`.
- **Edge case:** admin-owned disputes have `supplier_id=NULL`, so this returns admin-owned products with `supplier_id` literally NULL — works for admin products, and prevents cross-supplier replacement.

### 4.7 New-count badge — `getNewCount`

- **File:** `Admin/ProductDisputeController.php:258-265`
- **Endpoint:** `GET admin/disputes/new-count` (JSON `{count}`).
- Count of `status = new` disputes, cached under key **`disputes_new_count` for 30 seconds** (`:260-264`).
- **Cache invalidation:** `Cache::forget('disputes_new_count')` is called on dispute creation (`Api/ProductDisputeController.php:190`), `markInReview` (`:226`), `resolveRefund` (`:111`), `resolveReplacement` (`:180`), `reject` (`:208`) — but NOT explicitly in the auto-close command (it calls `resolveWithRefund` on the model, which does not clear this cache; the count self-heals after ≤30 s TTL).

---

## 5. Supplier features (web / blade) & supplier impact

Controller: `backend/app/Http/Controllers/Supplier/DisputeController.php`
Routes (supplier group, web.php `:203-204`):

| Feature | Method + Route name | Controller |
|---|---|---|
| List | `GET supplier/disputes` → `supplier.disputes.index` | `index` (`:14`) |
| Detail | `GET supplier/disputes/{dispute}` → `supplier.disputes.show` | `show` (`:55`) |

Blade views: `backend/resources/views/supplier/disputes/index.blade.php`, `show.blade.php`.

### 5.1 Visibility — read only

- `index` (`:14-50`): lists disputes scoped via `forSupplier($supplier->id)`. Filters: `status`, `date_from`/`date_to`. Stats: `total`, `new`, `in_review`, `resolved`, `rejected`, and `total_refunded` = sum of `refund_amount` where `admin_decision='refund'` (`:38-47`).
- `show` (`:55-67`): **authorization** — aborts 403 "У вас нет доступа к этой претензии" if `dispute->supplier_id !== supplier->id` (`:60-62`). Loads `user, serviceAccount, transaction, resolver`.
- The supplier has **no actions** — they cannot mark-in-review, resolve, or reject. All resolution authority is the admin's. Suppliers only observe outcomes.

### 5.2 Impact on the supplier

- **Earnings clawback** (refund only): see §4.3 step 4 — the supplier's `SupplierEarning` for the transaction is reversed and, if already available/withdrawn, deducted from `supplier_balance`. Replacement and rejection do **not** claw back earnings.
- **Rating recompute** — `User::calculateSupplierRating()` (`backend/app/Models/User.php:165-211`) is invoked after refund, replacement, and rejection (when supplier-owned):
  - Looks at the last **90 days** of supplier sales (transactions with status `completed`/`success`).
  - If `<10` sales in the window → rating fixed at **100% (newcomer)** (`:182-185`).
  - Otherwise: counts **resolved** disputes (`refund` + `replacement`) in the window as "invalid sales"; `rating = validSales / totalSales * 100`, clamped 0–100 (`:188-202`). **Rejected disputes do not lower the rating** (only `resolved` refunds/replacements count against it), even though `reject()` triggers a recompute.
  - Writes `supplier_rating` and `rating_updated_at`.
- **Supplier notification** is created on refund and replacement (not on reject — `notifySupplier()` is only called in those two paths). See §7.2.

---

## 6. Auto-close (silent disputes)

Command: `backend/app/Console/Commands/AutoCloseDisputes.php` — signature `disputes:auto-close`.
Scheduled **hourly**: `backend/app/Console/Kernel.php:33` (`$schedule->command('disputes:auto-close')->hourly()`).

- **Feature flag:** `Option::get('dispute_auto_close_enabled', false)` — disabled by default; logs and exits if off (`:32-36`).
- **Window:** `Option::get('dispute_auto_close_hours', 24)` hours (min coerced to 24 if `<1`); threshold = `now()->subHours($hours)` (`:38-43`).
- **Behavior (Seller-silence scenario):** finds disputes with `status = new` and `created_at < threshold` (`:49-51`). For each, calls `resolveWithRefund(systemAdminId=1, comment)` where the comment is "Автоматическое решение: Продавец не ответил в течение {hours} часов." (`:55-67`). I.e. **silent seller ⇒ automatic refund in the buyer's favor**, using admin user ID **1** as the resolver.
- Errors per-dispute are caught and logged; the loop continues (`:70-73`).
- **Buyer-silence scenario is intentionally NOT implemented** — the code comments note there is no `waiting_buyer` status, so it skips auto-rejecting to avoid unfair rejections (`:76-79`).
- **Edge cases:**
  - Only `new` disputes auto-close; once an admin moves a dispute to `in_review`, it is exempt from auto-close.
  - Goes through the same `resolveWithRefund` path, so the same supplier-earnings clawback, balance refund, rating recompute, and notifications all fire automatically.
  - Hardcoded resolver ID `1` — if no admin with ID 1 exists, `resolved_by` FK still records 1 (no existence check); auto-close assumes a system admin at ID 1.
  - Does not clear `disputes_new_count` cache (self-heals within 30 s).

---

## 7. Notifications triggered by dispute events

(Cross-reference the Notifications domain doc for delivery details.)

### 7.1 On dispute creation → admins

- `NotifierService::send('dispute_created', 'Новая претензия на товар', "...email created dispute #id... reason...", 'warning')` (`Api/ProductDisputeController.php:194-199`).
- `NotifierService::send` (`Services/NotifierService.php:17`) fans out to all admins (`is_admin`/`is_main_admin`) whose `AdminNotificationSetting` has the matching toggle enabled. The toggle is **`dispute_created_enabled`** (default `true`), mapped from type `dispute_created` (`Models/AdminNotificationSetting.php:105`, `:56`).

### 7.2 On resolution → supplier (refund & replacement only)

- `ProductDispute::notifySupplier()` creates a `SupplierNotification` row of type `product_dispute`, title "Претензия на товар", message "Претензия #id … решена. Решение: {decisionText}", with `data:{dispute_id, decision}` (`Models/ProductDispute.php:298-310`). Model: `backend/app/Models/SupplierNotification.php`.
- Called from `resolveWithRefund` (`:238`) and `resolveWithReplacement` (`:262`). **Not** called from `reject()`.

### 7.3 On resolution → buyer (all three outcomes)

- `ProductDispute::notifyCustomer()` (`Models/ProductDispute.php:315-331`) uses `NotificationTemplateService::sendToUser($user, 'dispute_resolved', { dispute_id, decision, comment })`.
- Template `dispute_resolved` seeded in `backend/database/seeders/NotificationTemplateSeeder.php:68-95` (ru/en/uk: "Your dispute #:dispute_id has been reviewed… Decision: :decision. :comment"). `is_mass=0`.
- Called from refund, replacement, and reject paths (`:241`, `:269`, `:287`).
- **Note:** the admin-side "dispute_resolved" notification setting column was **removed** (`backend/database/migrations/2025_11_18_032736_remove_dispute_resolved_enabled_from_admin_notification_settings_table.php`) — admins are notified only on creation, not on resolution.

---

## 8. Cross-cutting notes, quirks & edge cases

### 8.1 Locale handling quirk (labels)

`ProductDispute::getReasonText()`, `getStatusText()`, `getDecisionText()` are **defined with no `$locale` parameter** (`Models/ProductDispute.php:336,349,379`) and rely on Laravel's `__()` (current app locale). However, the **API controller calls them with a `$locale` argument** (e.g. `getReasonText($locale)`, `Api/ProductDisputeController.php:54,59,61,249,254,256`). PHP silently ignores the extra argument, so **the passed locale has no effect** — labels render in the request's app locale, not necessarily the `X-Locale`-requested one. The frontend mitigates this by localizing from the raw `reason`/`status`/`admin_decision` codes and the multi-language `product_title` object client-side.

### 8.2 Double-processing protection

`resolveWithRefund` uses a `lockForUpdate()` re-read + in-transaction status re-check (`:155-165`), and `resolveReplacement` wraps everything (including stock lock) in a DB transaction. This makes concurrent admin clicks / auto-close races safe for refund and replacement. `reject` has only the controller-level status guard (no row lock), but it is a single idempotent-ish update.

### 8.3 Admin vs supplier ownership

- `supplier_id = NULL` ⇒ admin-owned product. The `supplier()` relation supplies a default "Администратор" pseudo-user so blades render cleanly.
- Admin-owned disputes: no earnings clawback, no supplier notification, no supplier rating change; the platform bears the refund.
- The admin list `owner` filter (`admin` / `suppliers`) partitions on `supplier_id` NULL-ness.

### 8.4 Refund destination

Refunds always land on the buyer's **internal site balance** (`BalanceService::topUp` with `TYPE_REFUND`), never reversing the original payment instrument. `transaction->status` becomes `refunded`.

### 8.5 Time window

The 30-day filing window is computed with `created_at->diffInDays(now())` (`> 30` rejects in `store`; `<= 30` passes in `canDispute`). `diffInDays` is a whole-day absolute difference, so the boundary is "≤30 full days".

---

## 9. File index (quick reference)

| Concern | File |
|---|---|
| Buyer API | `backend/app/Http/Controllers/Api/ProductDisputeController.php` |
| Buyer request validation | `backend/app/Http/Requests/Dispute/CreateDisputeRequest.php` |
| Admin web controller | `backend/app/Http/Controllers/Admin/ProductDisputeController.php` |
| Supplier web controller | `backend/app/Http/Controllers/Supplier/DisputeController.php` |
| Model + state transitions + notifications | `backend/app/Models/ProductDispute.php` |
| Auto-close job | `backend/app/Console/Commands/AutoCloseDisputes.php` (scheduled in `app/Console/Kernel.php:33`) |
| Refund money flow | `backend/app/Services/BalanceService.php` (`topUp`, `TYPE_REFUND`) |
| Earnings clawback | `backend/app/Models/SupplierEarning.php` (`reverse`) |
| Supplier rating | `backend/app/Models/User.php` (`calculateSupplierRating` `:165`) |
| Replacement issuance | `backend/app/Services/ProductPurchaseService.php` (`createProductPurchase`) |
| Stock check | `backend/app/Models/ServiceAccount.php` (`getAvailableStock` `:285`) |
| Buyer notify template | `backend/database/seeders/NotificationTemplateSeeder.php` (`dispute_resolved`) |
| Admin notify | `backend/app/Services/NotifierService.php`, `AdminNotificationSetting.php` (`dispute_created_enabled`) |
| Supplier notify | `backend/app/Models/SupplierNotification.php` |
| Admin blade UI | `backend/resources/views/admin/disputes/{index,show}.blade.php` |
| Supplier blade UI | `backend/resources/views/supplier/disputes/{index,show}.blade.php` |
| Buyer UI (Vue) | `frontend/src/pages/account/ProfilePage.vue` |
| Routes | `backend/routes/api.php:88-92`, `backend/routes/web.php:142-149` (admin), `:203-204` (supplier) |
| Migrations | `2025_11_03_112558_create_product_disputes_table.php`, `2025_11_03_172455_add_screenshot...`, `2025_11_17_150333_make_supplier_id_nullable...`, `2025_11_18_032736_remove_dispute_resolved_enabled...` |
