# Admin Panel — Functional Inventory

The Admin Panel is the Blade-rendered backoffice for Account Arena. It is server-rendered
on the **AdminLTE** layout (`@extends('adminlte::page')`, shared CSS in
`backend/resources/views/admin/layouts/modern-styles.blade.php`) and lives entirely under the
`/admin` route prefix with the `admin.` route-name prefix.

All paths below are relative to the project root
(`/Users/gospodin/Desktop/Account Arena`). Controllers live in
`backend/app/Http/Controllers/Admin/`. Routes are defined in `backend/routes/web.php`
(lines 39–168).

---

## 0. Access Control, Roles & Cross-Cutting Behavior

### Middleware stack (aliases registered in `backend/app/Http/Kernel.php`)
- `admin.auth` → `App\Http\Middleware\AdminAuth` (`backend/app/Http/Middleware/AdminAuth.php`):
  Requires `auth()->user()->is_admin`. If the admin is `is_blocked`, they are logged out and
  redirected to `admin.login`. Non-admins are logged out and redirected to login.
- `admin.main` → `App\Http\Middleware\IsMainAdmin` (`backend/app/Http/Middleware/IsMainAdmin.php`):
  Requires `is_admin && is_main_admin && !is_blocked`; otherwise redirect to `admin.login`.
- `audit.admin` → `App\Http\Middleware\AuditAdminActions`
  (`backend/app/Http/Middleware/AuditAdminActions.php`): see Audit Logs section.

### Route grouping (`backend/routes/web.php:39`)
- `/admin/login` (GET/POST) and `/admin/logout` (GET/POST) are outside the audit group.
  Logout uses only `admin.auth` (no `audit.admin`) to avoid a 405 on logout.
- Everything else is wrapped in `['admin.auth', 'audit.admin']` (lines 53–167).
- Two nested `admin.main`-only sub-groups: **Admins management** (lines 123–126) and
  **Manual Delivery index/show/process** (lines 104–108).

### Roles: Main Admin vs Regular Admin
- **Main Admin** (`is_main_admin = true`): may manage staff admins, delete/bulk-delete
  promocodes, and access Manual Delivery processing screens. The main admin record is
  protected — it cannot be edited, blocked, or deleted through the admin-management UI.
- **Regular Admin** (`is_admin = true, is_main_admin = false`): full access to all other
  sections, but main-admin-gated actions redirect with an error.
- Note: most controllers gate "main admin only" actions by an **in-controller check**
  (`if (!auth()->user()->is_main_admin) ...`) in addition to (or instead of) the `admin.main`
  route middleware — e.g. `AdminController` re-checks on every action, `UserController` and
  `PromocodeController` check inline.

### Login (cross-reference; not in this domain's controllers)
- `LoginController` handles `admin.login`/`admin.logout`. The login form, credential check,
  and session handling are part of the Auth domain.

---

## 1. Dashboard & Analytics

**Manages:** Top-level KPIs and date-range sales analytics.

**Controller:** `DashboardController` (`backend/app/Http/Controllers/Admin/DashboardController.php`)

| Action | path:line | Route |
|---|---|---|
| `index(Request)` | `DashboardController.php:15` | `GET /admin` → `admin.dashboard` |

**Behavior & business rules:**
- **Period selector** (`?period=`): `today` (default), `yesterday`, `week` (start of week),
  `month` (start of month), `year` (start of year), or `custom` (with `start_date`/`end_date`
  parsed via Carbon). Lines 17–46.
- **Caching:** the whole stat payload is cached 10 minutes (`Cache::remember`, 600s) under
  `admin_dashboard_{period}` (custom periods append an md5 of the date range). Line 49–50.
- **Stock KPIs** via a single `selectRaw` aggregate over active `ServiceAccount`s where
  `moderation_status='approved' OR supplier_id IS NULL` (line 52–68): `total_products`,
  `available_products` (manual delivery counts as 1; automatic = `JSON_LENGTH(accounts_data) - used`,
  floored at 0), `total_value` (available × price, automatic only).
- **Period KPIs** from `Purchase`: purchases-in-period, sold-in-period (status `completed`),
  revenue-in-period (`SUM(total_amount)` of completed), average order value, total non-admin users.
- **Charts** (private helpers): `getSalesChartData(30)` (line 127) — 30-day revenue line with
  per-day tooltip data (orders, items, avg check, new vs returning buyers, computed from each
  buyer's MIN(created_at) first-purchase date); `getCategoryChartData()` (line 236) — completed
  sales grouped by localized category name (joins `category_translations` on current locale),
  auto-generated HSL colors; `getTopProducts(5)` (line 273) — top 5 products by completed sale
  count with revenue, falls back to "Удаленный товар" for deleted products.

**View:** `admin/dashboard.blade.php`.

---

## 2. User Management

**Manages:** End-user (non-admin) accounts: CRUD, blocking, manual balance adjustment,
personal discounts, and supplier-flag/commission fields.

**Controller:** `UserController` (`backend/app/Http/Controllers/Admin/UserController.php`),
constructor-injects `App\Services\UserService`.

| Action | path:line | Route |
|---|---|---|
| `index` | `UserController.php:19` | `GET users` → `admin.users.index` |
| `create` | `:28` | `GET users/create` |
| `store` | `:33` | `POST users` |
| `edit` | `:45` | `GET users/{user}/edit` |
| `update` | `:53` | `PUT/PATCH users/{user}` |
| `destroy` | `:86` | `DELETE users/{user}` |
| `block` | `:107` | `POST users/{user}/block` → `admin.users.block` |
| `updateBalance` | `:120` | `POST users/{user}/update-balance` → `admin.users.update-balance` |

Routes registered as `Route::resource('users')->except(['show'])` plus the two custom POSTs
(`web.php:56–58`).

**Key inputs/validation:**
- `store`: `email` (required, unique), `password` (required, min 6). Delegated to `UserService::createUser`.
- `update`: `name`, `email` (unique-ignoring-self), `is_blocked`, optional `password` (min 6, confirmed),
  `personal_discount` (0–100), `personal_discount_expires_at` (date), `is_supplier` (bool),
  `supplier_balance`, `supplier_commission` (0–100), `supplier_hold_hours` (1–8760). Delegated to
  `UserService::updateUser`. The `?save` param keeps you on the edit page vs. returning to the index.

**Business rules / edge cases:**
- `index` lists only `is_admin = false` users, paginated 20.
- `edit` also loads purchase history via `UserService::getPurchaseHistory`.
- `update` blocks editing another admin unless current user is main admin (or it's the same user).
- `destroy` blocks deleting admins (unless main admin), self-deletion, and main-admin deletion.
- **Manual balance adjustment** (`updateBalance`, line 120) — one of the most safety-critical flows:
  - Validates `operation` ∈ {`add`,`subtract`,`set`}, `amount` (numeric, ≥ 0), optional `comment` (≤500).
  - Wraps everything in a `DB::transaction` with `lockForUpdate()` on the user to prevent race conditions.
  - Uses `App\Services\BalanceService`: `add` → `topUp(TYPE_TOPUP_ADMIN)`; `subtract` →
    `deduct(TYPE_DEDUCTION)` (throws "Недостаточно средств" on overdraw); `set` → computes the
    delta and tops-up or deducts via `TYPE_ADJUSTMENT` (negative target rejected).
  - Records to `\Log::info` **and** writes an `AuditLog` entry with action `update_balance`,
    model `User`, capturing operation/amount/old_balance/new_balance/comment (line 231).
  - On exception, logs the error and redirects back with the error message.

---

## 3. Admin / Staff Management & Roles  (Main Admin only)

**Manages:** Creation/editing/blocking/deletion of staff administrator accounts.

**Controller:** `AdminController` (`backend/app/Http/Controllers/Admin/AdminController.php`)

| Action | path:line | Route |
|---|---|---|
| `index` | `AdminController.php:13` | `GET admins` → `admin.admins.index` |
| `create` | `:33` | `GET admins/create` |
| `store` | `:42` | `POST admins` |
| `edit` | `:62` | `GET admins/{admin}/edit` |
| `update` | `:76` | `PUT/PATCH admins/{admin}` |
| `destroy` | `:104` | `DELETE admins/{admin}` |
| `block` | `:123` | `POST admins/{admin}/block` → `admin.admins.block` |

Routes are inside the `admin.main` middleware group (`web.php:123–126`), **and** every action
re-checks `is_main_admin` internally and redirects to the dashboard with an error if not.

**Key inputs/validation (`getRules`, line 140):** `name` (required), `email` (required, unique-ignore-self),
`is_blocked` (required boolean), `password` (required+confirmed+min6 on create; nullable on update).

**Business rules / edge cases:**
- `index` lists only `is_admin = true AND is_main_admin = false`, with statistics (total/active/blocked).
- New admins are created with `is_admin = true` (no `is_main_admin`).
- **Main admin protection:** `edit`, `update`, `destroy`, and `block` all refuse to touch a record
  where `is_main_admin = true` ("Редактирование/удаление/блокировку главного администратора запрещено").
- `destroy` also blocks self-deletion.
- `block` toggles `is_blocked`.

**Profile** — `ProfileController` (`backend/app/Http/Controllers/Admin/ProfileController.php`):
`index` (`:11`) and `store` (`:18`) at `GET/POST profile` (`web.php:166`). The logged-in admin
edits their own email (unique-ignore-self) and optional password (min6, confirmed).

**Views:** `admin/admins/{index,create,edit}.blade.php`, `admin/profile.blade.php`.

---

## 4. Service Accounts (Products / Inventory)

**Manages:** The core product catalog. Each `ServiceAccount` is a product whose stock is a JSON
array `accounts_data` of account/credential lines; `used` tracks how many have been sold/exported.
Supports automatic vs manual delivery, multi-language fields, discounts, images, admin notes,
and sorting.

**Controller:** `ServiceAccountController` (`backend/app/Http/Controllers/Admin/ServiceAccountController.php`)

| Action | path:line | Route |
|---|---|---|
| `index` | `ServiceAccountController.php:16` | `GET service-accounts` |
| `create` | `:83` | `GET service-accounts/create` |
| `store` | `:88` | `POST service-accounts` |
| `edit` | `:219` | `GET service-accounts/{serviceAccount}/edit` |
| `update` | `:242` | `PUT/PATCH service-accounts/{serviceAccount}` |
| `destroy` | `:485` | `DELETE service-accounts/{serviceAccount}` |
| `export` | `:353` | `GET service-accounts/{serviceAccount}/export` |
| `import` | `:430` | `POST service-accounts/{serviceAccount}/import` |
| `bulkAction` | `:495` | `POST service-accounts/bulk-action` |
| `uploadImage` | `:694` | `POST service-accounts/upload-image` (CKEditor) |
| `updateSortOrder` | `:723` | `POST service-accounts/update-sort-order` |
| `applySortOrder` | `:745` | `POST service-accounts/apply-sort-order` |
| `updateNotes` | `:787` | `POST service-accounts/{serviceAccount}/update-notes` |

Routes: `Route::resource('service-accounts')->except(['show'])` + custom routes (`web.php:80–93`).

**`index` (line 16):** Paginates 20, eager-loads `category.translations` + `supplier`, deliberately
**omits the heavy `accounts_data`** column (memory optimization) and instead derives quantity via
`JSON_LENGTH(accounts_data)`. Orders by `sort_order ASC, id DESC`. Builds a category tree
(parent + sub) with per-category product counts (including counts rolled up from subcategories),
a "no category" count, and the list of suppliers that own products (for filtering).

**`store` (line 88) & `storeBulkAccounts` (line 119):**
- Validation via `getRules()` (line 649): `category_id`/`subcategory_id` (exist in categories),
  `is_active` (required bool), `price` (required, **min 0.01**), `title` (required), localized
  title/description/additional_description/meta_title/meta_description in ru/en/uk, `image`
  (image, jpeg/png/jpg/gif/webp, max 2MB), `accounts_data` (string), `discount_percent` (0–99),
  `discount_start_date`/`discount_end_date` (date, end ≥ start), `delivery_type`
  (`automatic`|`manual`), `manual_delivery_instructions` (≤5000), `admin_notes` (≤5000).
- If `subcategory_id` is present it overrides `category_id`.
- If `bulk_accounts` is non-empty, the request branches to `storeBulkAccounts`, which creates a
  **single product holding all the pasted account lines** (split on newlines, trimmed, blanks
  dropped), validates the category type is `TYPE_PRODUCT` and that a chosen subcategory is really
  a subcategory, handles image upload, and persists all localized + discount + suffix +
  delivery fields. SKU is auto-generated in the model.
- Plain single create initializes `accounts_data = []`, `used = 0`.

**`update` (line 242):** Critically preserves already-sold stock. It slices the first `used`
elements of existing `accounts_data` as "sold accounts", then appends only **new, non-duplicate**
lines (de-duped against sold and against each other), reporting added vs filtered-duplicate counts.
Validates category/subcategory types; handles image upload, account-suffix fields, discount fields,
and `admin_notes`. `?save` keeps you on edit.

**`export` (line 353):** Wrapped in `DB::transaction` with `lockForUpdate()` to avoid a
race with concurrent purchases. Exports remaining accounts (or `?count=` capped to available),
**increments `used`** (same pointer logic as a purchase — does not delete), returns a UTF-8
(BOM-prefixed) `.txt` download named `product_{id}_{date}.txt`, and flashes a remaining-count message.

**`import` (line 430):** Validates `import_data` (required string), splits/trims lines, **de-duplicates**
against existing stock and within the batch, appends unique lines to `accounts_data`, reports how many
were added and how many duplicates were skipped.

**`bulkAction` (line 495):** AJAX/JSON. Validates `action` ∈
{`activate`,`deactivate`,`change_price`,`change_category`,`change_delivery_type`,`delete`} and
`ids[]` (each must exist). Runs inside `DB::transaction`:
- `change_price` with `action_type` ∈ {`increase`,`decrease`,`set`} — percentage increase/decrease
  (decrease floored at 0.01) or absolute set; `set` requires value ≥ 0.01, others 0–1000.
- `change_category` validates the target is a product-type category.
- `change_delivery_type` ∈ {`automatic`,`manual`}.
- After success, clears the client product cache (`clearServiceAccountCache`, line 806 — forgets
  `active_accounts_list`, `_v2`, `_v3`), logs the action, returns JSON with count.

**`uploadImage` (line 694):** CKEditor image upload endpoint; validates image ≤5MB, stores under
`products/descriptions`, returns CKEditor-format JSON (`{url, uploaded, fileName}`).

**`updateSortOrder` (line 723):** Persists drag-drop ordering (`items[].id` + `items[].sort_order`),
clears cache. **`applySortOrder` (line 745):** bulk-applies a sort (`sort_by` ∈ {id,price,created_at},
`direction` asc/desc) and rewrites every `sort_order` 1..N inside a transaction.

**`updateNotes` (line 787):** AJAX; saves `admin_notes` only, returns JSON.

**Edge cases:** minimum price 0.01 enforced everywhere; duplicate-account filtering on
create/update/import; sold accounts are never lost on edit; export and purchase share the
`used`-pointer model; client-facing caches are invalidated on bulk/sort changes.

**Views:** `admin/service-accounts/{index,create,edit}.blade.php`.

---

## 5. Product Moderation (Supplier Products)

**Manages:** Approving/rejecting products submitted by suppliers (those with
`moderation_status = pending`).

**Controller:** `ProductModerationController` (`backend/app/Http/Controllers/Admin/ProductModerationController.php`)

| Action | path:line | Route |
|---|---|---|
| `index` | `ProductModerationController.php:17` | `GET product-moderation` → `admin.product-moderation.index` |
| `show` | `:30` | `GET product-moderation/{product}` |
| `approve` | `:82` | `POST product-moderation/{product}/approve` |
| `reject` | `:192` | `POST product-moderation/{product}/reject` |

Routes at `web.php:86–89`.

**Behavior & business rules:**
- `index` lists `ServiceAccount::pendingModeration()` with supplier+category, newest first.
- `show` rejects products that don't `requiresModeration()`, then validates **every** account
  line for a delimiter (`:`, `;`, or `|`), producing valid/invalid counts and up to 10 error
  messages (DOS/fraud protection), and previews the first 50 lines.
- `approve`: refuses if not `pending`. Inside a `DB::transaction` with `lockForUpdate()`,
  re-checks status (guards against another admin processing it concurrently), then runs
  **critical-field validation** (non-empty title, price ≥ 0.01, category set, non-empty
  `accounts_data` array) before setting `moderation_status='approved'`, `is_active=true`,
  `moderated_at`, `moderated_by`. After commit, notifies the supplier via
  `SupplierNotification` and `NotifierService::sendFromTemplate('supplier_product_approved')`
  (notifications are outside the transaction; failures are logged, not fatal).
- `reject`: requires `moderation_comment` (≤1000). Same lock/re-check pattern; sets
  `moderation_status='rejected'`, `is_active=false`, stores the comment, and notifies the
  supplier with the rejection reason.

**Views:** `admin/product-moderation/{index,show}.blade.php`.

---

## 6. Promocodes & Usages

**Controller:** `PromocodeController` (`backend/app/Http/Controllers/Admin/PromocodeController.php`),
injects `App\Services\PromocodeService`.

| Action | path:line | Route |
|---|---|---|
| `index` | `PromocodeController.php:21` | `GET promocodes` |
| `create` | `:35` | `GET promocodes/create` |
| `store` | `:40` | `POST promocodes` |
| `edit` | `:76` | `GET promocodes/{promocode}/edit` |
| `update` | `:81` | `PUT/PATCH promocodes/{promocode}` |
| `destroy` | `:104` | `DELETE promocodes/{promocode}` |
| `bulkDestroy` | `:117` | `DELETE promocodes-bulk` → `admin.promocodes.bulk-destroy` |

Routes: `Route::resource('promocodes')->except(['show'])` + bulk + usages (`web.php:60–62`).

**Key inputs/validation (`getRules`, line 132):** `quantity` (1–1000), `code`
(required when quantity=1, unique), `type` (must be `discount`), `prefix` (≤64; required for bulk
via an `after()` validator), `batch_id` (unique), `percent_discount` (0–100), `usage_limit`
(0..1e8, 0 = unlimited), `per_user_limit` (0..1e8), `is_active` (bool), `starts_at`/`expires_at`
(dates, expires ≥ starts).

**Business rules / edge cases:**
- `index` shows statistics (total, active, total usages, avg discount), paginated 20.
- `store`: quantity ≤ 1 creates a single code; quantity > 1 delegates to
  `PromocodeService::bulkCreate` (batch creation using prefix).
- **`destroy` and `bulkDestroy` are Main-Admin-only** (inline `is_main_admin` check; bulkDestroy
  returns HTTP 403 JSON otherwise). bulkDestroy takes `ids[]`.

**Promocode Usages** — `PromocodeUsageController` (`backend/app/Http/Controllers/Admin/PromocodeUsageController.php`):
`index` (`:10`) at `GET promocode-usages` → `admin.promocode-usages.index` (`web.php:61`). Lists all
`PromocodeUsage` with promocode+user, plus stats (total, unique users, most-used code).

**Views:** `admin/promocodes/{index,create,edit,usages}.blade.php`.

---

## 7. Vouchers

**Manages:** Balance-top-up voucher codes that users can redeem.

**Controller:** `VoucherController` (`backend/app/Http/Controllers/Admin/VoucherController.php`)

| Action | path:line | Route |
|---|---|---|
| `index` | `VoucherController.php:12` | `GET vouchers` |
| `create` | `:26` | `GET vouchers/create` |
| `store` | `:31` | `POST vouchers` |
| `show` | `:116` | `GET vouchers/{voucher}` |
| `edit` | `:122` | `GET vouchers/{voucher}/edit` |
| `update` | `:127` | `PUT/PATCH vouchers/{voucher}` |
| `destroy` | `:142` | `DELETE vouchers/{voucher}` |

Route: full `Route::resource('vouchers')` (includes `show`) (`web.php:94`).

**Key inputs/validation:** `store` — `amount` (≥0.01), `currency` (size 3), optional `code`
(unique), optional `note`, `quantity` (1–500). `update` — same plus `code` required + `is_active`.

**Business rules / edge cases:**
- `index` stats: total, active (active & unused), used (`used_at` set), total amount.
- **Bulk creation with collision handling:** `store` runs inside `DB::transaction`, generating
  codes via `Voucher::generateCode()`, checking each batch (including soft-deleted via `withTrashed`)
  for collisions, retrying up to 3 times; throws (and rolls back) if it can't create the full
  requested quantity, returning the partial-count error to the form.

**Views:** `admin/vouchers/{index,create,edit,show}.blade.php`.

---

## 8. Purchases Management

**Manages:** Read-only listing + detail of all product purchases, with deletion limited to
non-completed orders.

**Controller:** `PurchaseController` (`backend/app/Http/Controllers/Admin/PurchaseController.php`)

| Action | path:line | Route |
|---|---|---|
| `index` | `PurchaseController.php:16` | `GET purchases` |
| `show` | `:106` | `GET purchases/{purchase}` |
| `destroy` | `:116` | `DELETE purchases/{purchase}` |

Route: `Route::resource('purchases')->only(['index','show','destroy'])` (`web.php:97`).

**`index` filters:** `user_id`, `product_id` (service_account_id), `status`, `date_from`/`date_to`
(by `created_at` date), free-text `search` (order_number / guest_email / user email or name),
`buyer_type` (`registered` = has user_id, `guest` = null user_id + guest_email). Paginated 50,
`withQueryString()`. Stats (total / today / this month / total completed revenue) cached 5 min under
`admin_purchases_stats`.

**Edge case:** `destroy` refuses to delete a `completed` purchase ("Сначала измените статус").

**Views:** `admin/purchases/{index,show}.blade.php`.

---

## 9. Purchase Rules (config)

**Manages:** The localized "purchase rules" text shown to buyers, with an on/off toggle.

**Controller:** `PurchaseRulesController` (`backend/app/Http/Controllers/Admin/PurchaseRulesController.php`)

| Action | path:line | Route |
|---|---|---|
| `index` | `PurchaseRulesController.php:14` | `GET purchase-rules` → `admin.purchase-rules.index` |
| `store` | `:27` | `POST purchase-rules` → `admin.purchase-rules.store` |

Routes at `web.php:111–112`. Stored as `Option` rows `purchase_rules_{ru,en,uk}` (≤10000 chars each)
and a `purchase_rules_enabled` flag (saved as 1/0). **View:** `admin/purchase-rules/index.blade.php`.

---

## 10. Manual Delivery (cross-reference — Main Admin only)

**Manages:** Manual fulfilment of orders for products with `delivery_type = manual`.
(Primary domain elsewhere; included for completeness.)

**Controller:** `ManualDeliveryController` (`backend/app/Http/Controllers/Admin/ManualDeliveryController.php`)

| Action | path:line | Route | Access |
|---|---|---|---|
| `getPendingCount` | `:209` | `GET manual-delivery/count` | all admins (badge) |
| `statistics` | `:198` | `GET manual-delivery/statistics` | all admins |
| `index` | `:24` | `GET manual-delivery` | **admin.main** |
| `show` | `:116` | `GET manual-delivery/{purchase}` | **admin.main** |
| `process` | `:132` | `POST manual-delivery/{purchase}/process` | **admin.main** |

Routes at `web.php:99–108`. The badge-count and statistics endpoints are open to all admins;
the actual processing screens are `admin.main`-gated.

**`index`** filters processing/completed manual-delivery orders by status, date range, customer
email/id, order number, with sorting. **`process`** validates `account_data[]` (count must equal
`purchase->quantity`), trims (deliberately **no** `strip_tags`, to avoid corrupting passwords),
runs inside a `DB::transaction` with `lockForUpdate()` guarding against double-processing, delegates
to `ManualDeliveryService::processPurchase`, and stores optional `admin_notes`/`processing_notes`.
**Views:** `admin/manual-delivery/{index,show}.blade.php`.

---

## 11. Banners

**Manages:** Home-page promotional banners with positions, ordering, scheduling, and images.

**Controller:** `BannerController` (`backend/app/Http/Controllers/Admin/BannerController.php`)

| Action | path:line | Route |
|---|---|---|
| `index` | `BannerController.php:15` | `GET banners` |
| `create` | `:40` | `GET banners/create` |
| `store` | `:61` | `POST banners` |
| `edit` | `:95` | `GET banners/{banner}/edit` |
| `update` | `:118` | `PUT/PATCH banners/{banner}` |
| `destroy` | `:158` | `DELETE banners/{banner}` |

Route: `Route::resource('banners')->except(['show'])` (`web.php:73`).

**Key inputs/validation:** `title` (+`title_en`/`title_uk`), `image` (required on create, optional
on update; jpeg/png/jpg/gif/webp ≤5MB), `link` (url), `position`, `order` (1–4 normally, **1 only**
for `home_top_wide`), `is_active` (bool), `open_new_tab` (bool), `start_date`/`end_date`
(end ≥ start). Positions come from `Banner::getPositions()`.

**Business rules / edge cases:**
- `index` filters by `is_active`, paginated 20, with stats (total / currently-active via
  `isCurrentlyActive()` / inactive). Note: stats call `Banner::all()` then filter in PHP.
- create/edit surface which `home_top` slots (1–4) and the single `home_top_wide` slot are taken.
- Images are stored on the `public` disk; old image files are deleted on update/destroy.

**Views:** `admin/banners/{index,create,edit}.blade.php`.

---

## 12. CMS — Pages, Contents, Site Content, Articles, Article Categories

### 12.1 Pages
**Controller:** `PageController` (`backend/app/Http/Controllers/Admin/PageController.php`).
Resource except `show` (`web.php:63`).

| Action | path:line | Route |
|---|---|---|
| `index` | `:11` | `GET pages` (stats: total/active/inactive) |
| `create` | `:24` | `GET pages/create` |
| `store` | `:29` | `POST pages` |
| `edit` | `:58` | `GET pages/{page}/edit` |
| `update` | `:68` | `PUT/PATCH pages/{page}` |
| `destroy` | `:90` | `DELETE pages/{page}` |

- `slug` is normalized (alphanumerics/`/`/`-`, collapsed dashes, trimmed) before validation.
- Validation: `name` (required), `slug` (required, unique-ignore-self), `is_active` (bool), and
  per-language `title`/`content` (nullable) for each locale in `config('langs')`.
- **HTML is sanitized** before saving translations via `sanitizeHtml()` (line 100): `strip_tags`
  with a whitelist of formatting tags + removal of `on*` handlers and `javascript:` (XSS defense).
- Translations stored via `$page->saveTranslation()`.

### 12.2 Contents
**Controller:** `ContentController` (`backend/app/Http/Controllers/Admin/ContentController.php`).
Resource except `show` (`web.php:66`).

| Action | path:line | Route |
|---|---|---|
| `index` | `:12` | `GET contents` (stats: total/system/custom) |
| `create` | `:27` | `GET contents/create` |
| `store` | `:32` | `POST contents` |
| `edit` | `:41` | `GET contents/{content}/edit` |
| `update` | `:67` | `PUT/PATCH contents/{content}` |
| `destroy` | `:95` | `DELETE contents/{content}` |

- Content blocks support repeatable per-locale dynamic fields keyed `code.field.index`.
- Validation: `name`, `code` (unique-ignore-self), `fields` (array), `fields_file.*.*.*`
  (image files: jpeg/png/jpg/gif/svg/webp/ico ≤10MB). Uploaded files are stored to `contents/`
  on the public disk and their URLs merged back into the field values before
  `saveTranslation($validated, $content->code)`.
- Views: `admin/contents/{index,create,edit}.blade.php` + `_language_tab_content.blade.php`.

### 12.3 Site Content (homepage / become-supplier copy)
**Controller:** `SiteContentController` (`backend/app/Http/Controllers/Admin/SiteContentController.php`)

| Action | path:line | Route |
|---|---|---|
| `index` | `:11` | `GET site-content` → `admin.site-content.index` |
| `store` | `:18` | `POST site-content` → `admin.site-content.store` |

Routes at `web.php:67–68` (not a resource). A single `store` handles several tabbed forms keyed by
`?form=`:
- `site_content` — `currency` plus a very large set of nullable per-language (`_ru/_en/_uk`)
  strings: hero, about, promote title + 6 promote blocks (access/pricing/refund/activation/
  support/payment), steps, and the entire **Become Supplier** landing (welcome banner, stats,
  4 process steps, digital-goods categories, restricted items, 4 partner benefits, payout methods,
  4-question FAQ) — see `getRules()` lines 48–290.
- `header_menu` / `footer_menu` — arrays persisted as JSON into `Option`.
- Each saved key becomes an `Option` row; empties are skipped except `'0'`/`0`. Redirects back
  with the active tab preserved.

### 12.4 Articles
**Controller:** `ArticleController` (`backend/app/Http/Controllers/Admin/ArticleController.php`).
Resource except `show` (`web.php:69`).

| Action | path:line | Route |
|---|---|---|
| `index` | `:13` | `GET articles` (stats: total/published/draft) |
| `create` | `:29` | `GET articles/create` |
| `store` | `:35` | `POST articles` |
| `edit` | `:70` | `GET articles/{article}/edit` |
| `update` | `:89` | `PUT/PATCH articles/{article}` |
| `destroy` | `:129` | `DELETE articles/{article}` |

- Validation: `is_active` (bool, drives `status` published/draft), `img` (image ≤10MB) or
  `img_text` URL fallback, `categories[]` (existing category ids), and per-language `title`,
  `content`, `short`, `meta_title`, `meta_description`.
- `content` and `short` are XSS-sanitized (same whitelist as Pages). Category links via
  `categories()->sync()`. `destroy` detaches categories first.

### 12.5 Article Categories
**Controller:** `ArticleCategoryController` (`backend/app/Http/Controllers/Admin/ArticleCategoryController.php`),
injects `CategoryService`. Resource except `show` (`web.php:72`).

| Action | path:line | Route |
|---|---|---|
| `index` | `:19` | `GET article-categories` |
| `create` | `:26` | `GET article-categories/create` |
| `store` | `:31` | `POST article-categories` |
| `edit` | `:65` | `GET article-categories/{article_category}/edit` |
| `update` | `:44` | `PUT/PATCH article-categories/{article_category}` |
| `destroy` | `:86` | `DELETE article-categories/{article_category}` |

- Scoped to `Category::TYPE_ARTICLE` (every lookup `firstOrFail`s on type). Per-language
  `name`/`meta_title`/`meta_description`/`text`; at least one `name` locale required.
  Persistence/deletion delegated to `CategoryService` (`saveCategory`, `deleteCategory`).

---

## 13. Product Categories & Subcategories

> There is **no** `Admin\CategoryController`; product categories are split into two controllers
> (parent categories vs subcategories), and article categories have their own controller (§12.5).
> All use the shared `App\Services\CategoryService` and the `Category` model with a `type`
> discriminator (`TYPE_PRODUCT` / `TYPE_ARTICLE`) and `parent_id` for the parent/child hierarchy.

### 13.1 Product Categories (parents)
**Controller:** `ProductCategoryController` (`backend/app/Http/Controllers/Admin/ProductCategoryController.php`).
Resource except `show` (`web.php:70`).

| Action | path:line | Route |
|---|---|---|
| `index` | `:21` | `GET product-categories` |
| `create` | `:49` | `GET product-categories/create` |
| `store` | `:54` | `POST product-categories` |
| `edit` | `:110` | `GET product-categories/{product_category}/edit` |
| `update` | `:76` | `PUT/PATCH product-categories/{product_category}` |
| `destroy` | `:132` | `DELETE product-categories/{product_category}` |

- `index` lists `productCategories()->parentCategories()` with children + product counts, computes
  `total_products_count` (own + subcategory products), and stats (categories / subcategories /
  total products).
- Lookups enforce `type = TYPE_PRODUCT` and `parent_id IS NULL` (parents only).
- Validation: per-language `name`/`meta_title`/`meta_description`/`text` (≥1 `name` locale),
  `image` (jpeg/png/jpg/gif/webp ≤2MB). Old category image is deleted on replacement.
- `destroy` delegates to `CategoryService::deleteCategory`, which returns a
  success/message structure (e.g. refuses deletion when products/children depend on it).

### 13.2 Product Subcategories
**Controller:** `ProductSubcategoryController` (`backend/app/Http/Controllers/Admin/ProductSubcategoryController.php`).
Resource except `show` (`web.php:71`).

| Action | path:line | Route |
|---|---|---|
| `index` | `:18` | `GET product-subcategories` |
| `create` | `:29` | `GET product-subcategories/create` |
| `store` | `:40` | `POST product-subcategories` |
| `edit` | `:100` | `GET product-subcategories/{product_subcategory}/edit` |
| `update` | `:67` | `PUT/PATCH product-subcategories/{product_subcategory}` |
| `destroy` | `:129` | `DELETE product-subcategories/{product_subcategory}` |

- Lookups enforce `type = TYPE_PRODUCT` and `parent_id NOT NULL`.
- `parent_id` is **required** and validated to be an existing **parent** product category
  (`whereNull('parent_id')`); reassigning the parent is allowed on update. Subcategories have no images.
  Per-language fields as in §13.1.

---

## 14. Email Templates + SMTP test

**Controller:** `EmailTemplateController` (`backend/app/Http/Controllers/Admin/EmailTemplateController.php`).
Full resource (`web.php:74`) + custom `send-test`.

| Action | path:line | Route |
|---|---|---|
| `index` | `:16` | `GET email-templates` |
| `create` | `:25` | `GET email-templates/create` |
| `store` | `:30` | `POST email-templates` |
| `show` | `:51` | `GET email-templates/{email_template}` (HTML preview) |
| `edit` | `:137` | `GET email-templates/{email_template}/edit` |
| `update` | `:147` | `PUT/PATCH email-templates/{email_template}` |
| `destroy` | (resource) | `DELETE email-templates/{email_template}` |
| `sendTest` | `:189` | `POST email-templates/{email_template}/send-test` → `admin.email-templates.send-test` |

- Templates carry a `code` (unique, immutable after create) + `name`, and per-language `title`
  + `message` translations. Body HTML is XSS-sanitized (`sanitizeTemplateData`, line 167) with a
  formatting-tag whitelist and `on*`/`javascript:` removal.
- `show` renders a live preview using `EmailService::renderTemplate` with code-specific sample
  params (`getPreviewParams`, line 97 — e.g. amount, products_count, total_amount, guest_email);
  `reset_password` uses a dedicated Blade view. Falls back ru→en, 404s if no usable translation.
- `sendTest` validates `test_email`, renders subject/body for the chosen `locale` (fallback en),
  configures mail from saved Options (`EmailService::configureMailFromOptions`), sends, and on
  SMTP failure returns friendly guidance for auth (535) / connection errors.

**Views:** `admin/email-templates/{index,create,edit}.blade.php`; previews via `emails.base` /
`emails.reset-password`.

---

## 15. Notification Templates & Sending Notifications

### 15.1 Notification Templates
**Controller:** `NotificationTemplateController` (`backend/app/Http/Controllers/Admin/NotificationTemplateController.php`).
Resource `only(['index','create','store','edit','update','destroy'])` (`web.php:64`).

| Action | path:line | Route |
|---|---|---|
| `index` | `:11` | `GET notification-templates` (`?type=system|custom`) |
| `create` | `:29` | `GET notification-templates/create` |
| `store` | `:34` | `POST notification-templates` |
| `edit` | `:59` | `GET notification-templates/{...}/edit` |
| `update` | `:69` | `PUT/PATCH notification-templates/{...}` |
| `destroy` | `:88` | `DELETE notification-templates/{...}` |

- `is_mass` distinguishes **system** (`0`) from **custom/mass** (`1`) templates; `index` filters by
  it and shows counts. Code is unique + immutable; per-language `title`/`message`; XSS-sanitized.
  store/update run inside `DB::transaction`. **`destroy` 404s on non-mass (system) templates** —
  system templates cannot be deleted.

### 15.2 Sending Notifications (mass)
**Controller:** `NotificationController` (`backend/app/Http/Controllers/Admin/NotificationController.php`).
Resource `only(['index','create','store','destroy'])` (`web.php:65`).

| Action | path:line | Route |
|---|---|---|
| `index` | `:14` | `GET notifications` (stats: total/read/unread) |
| `create` | `:29` | `GET notifications/create` |
| `store` | `:37` | `POST notifications` |
| `destroy` | `:68` | `DELETE notifications/{notification}` |

- `store` requires `target` + per-language `title`/`message` (required), creates an auto-named
  mass `NotificationTemplate` (`is_mass=1`, random code), saves translations, then **fans out a
  `Notification` row to every target user**. Current `getTargetUsers()` (line 89) resolves to
  `User::all()` for all filters (sends to all users).

---

## 16. Settings (SMTP, Telegram, integrations, feature flags)

**Manages:** Global site options (stored in the `Option` model) and the current admin's
notification preferences.

**Controller:** `SettingController` (`backend/app/Http/Controllers/Admin/SettingController.php`).
Resource `only(['index','store'])` + custom routes (`web.php:76–78`).

| Action | path:line | Route |
|---|---|---|
| `index` | `:18` | `GET settings` → `admin.settings.index` |
| `store` | `:35` | `POST settings` |
| `testSmtp` | `:151` | `POST settings/test-smtp` → `admin.settings.test-smtp` |
| `getNotificationSettings` | `:240` | `GET settings/notification-check` → `admin.settings.notification-check` |

**Tabbed forms** (keyed by `?form=`, validated by `getRules($form)` line 252):
- `cookie` — `cookie_countries[]`.
- `smtp` — from address/name, host, port, encryption (`tls`/`ssl` or empty for none),
  username, password (required), `smtp_verify_peer`.
- `support_chat` — enable flag, telegram link (url), greeting flag, plus per-locale greeting
  messages; clears `support_chat_settings_{locale}` cache.
- `notification_settings` — the current admin's `AdminNotificationSetting` toggles
  (registration / product_purchase / dispute_created / payment / topup / support_chat /
  manual_delivery / low_stock / sound).
- `telegram` — `telegram_client_enabled` + `telegram_bot_token`. On save, validates the token via
  the Telegram `getMe` API; on success stores bot username/id, derives the support link, and sets
  the webhook (`TelegramBotService::setWebhook`). Invalid token → redirect back with error on the
  telegram tab.
- `pixel` — `facebook_pixel_id`.
- `disputes` — `dispute_auto_close_enabled` + `dispute_auto_close_hours` (1–720).

**Business rules / edge cases:**
- `store` runs in a `DB::transaction`; checkbox-style keys are explicitly persisted as true/false
  even when unchecked; SMTP encryption empty-string is saved explicitly (not skipped).
- **Sensitive options are encrypted at rest** by the `Option` model
  (`backend/app/Models/Option.php`): `smtp_password`, `telegram_bot_token`,
  `cryptomus_payment_key`, `cryptomus_payout_key`, `monobank_token` are `encrypt()`ed on `set` and
  `decrypt()`ed on `get` (with graceful fallback + warning for legacy plaintext). `Option::get`
  caches all options for 3600s under `site_options_all`, invalidated on any `set`.
- `testSmtp` validates the SMTP form, builds a temporary `test` mailer config, and sends a
  `emails.test` message to the from-address; returns JSON success or a friendly error
  (timeout / auth-failed / host-unreachable).
- `getNotificationSettings` returns the admin's manual_delivery/sound/dispute/support toggles as JSON
  (used by the front-end polling for notification behavior).

**View:** `admin/settings/index.blade.php`.

---

## 17. Audit Logs / Activity Logs

**Manages:** Read-only audit trail of admin actions.

**Controller:** `AuditLogController` (`backend/app/Http/Controllers/Admin/AuditLogController.php`):
`index` (`:15`) at `GET activity-logs` → `admin.activity-logs.index`
(`Route::resource(...)->only(['index'])`, `web.php:79`).

**Filters:** `user_id` (actor), `action` (exact), `model_type` (LIKE), `model_id`. Paginated 20,
`withQueryString()`. Also supplies the distinct actor list and distinct action list for the filter
dropdowns. **View:** `admin/audit-logs/index.blade.php`.

### What gets logged
Two sources both write to the `AuditLog` model (`backend/app/Models/AuditLog.php`, static
`AuditLog::log(action, modelType, modelId, userId, changes, ip, userAgent)`; `changes` cast to array):

1. **Automatic middleware** — `AuditAdminActions` (`backend/app/Http/Middleware/AuditAdminActions.php`),
   applied to the whole admin group:
   - Only mutating methods (`POST`/`PUT`/`PATCH`/`DELETE`) that returned a 2xx/3xx response.
   - **Skips** `admin/login` and `admin/logout`.
   - Action derived from HTTP verb (`POST→create`, `PUT/PATCH→update`, `DELETE→delete`).
   - Model type/id parsed from the URL (regex on `admin/{resource}/{id}`), with a name map
     (users/admins→User, service-accounts→ServiceAccount, etc.; unknown → ucfirst).
   - `changes` = the full request payload **except** `_token`, `_method`, `password`,
     `password_confirmation`, `current_password` (passwords never logged).
   - Stores IP and user-agent.

2. **Explicit application logging** — e.g. `UserController::updateBalance` writes a dedicated
   `update_balance` entry (operation/amount/old+new balance/comment).

---

## 18. Admin Notifications + Settings

### 18.1 Admin Notifications (bell dropdown + list)
**Controller:** `AdminNotificationController` (`backend/app/Http/Controllers/Admin/AdminNotificationController.php`)

| Action | path:line | Route |
|---|---|---|
| `get` | `:10` | `GET admin_notifications/get` → `admin.admin_notifications.get` |
| `read` | `:68` | `GET admin_notifications/read/{id}` |
| `readAll` | `:76` | `POST admin_notifications/read-all` |
| `index` | `:61` | `GET admin_notifications` (paginated 50) |
| `destroy` | `:85` | `DELETE admin_notifications/{id}` |

Routes at `web.php:115–120` (resource limited to index/destroy with `{id}` parameter binding).

- `get` is the AJAX poller for the header bell: returns the latest 5 notifications rendered into
  the `admin/admin_notifications/dropdown.blade.php` partial, the unread count, the current admin's
  `sound_enabled` flag, and a `has_new` boolean (drives the notification sound).
- `read` marks one read; `readAll` marks all read. Titles/messages are run through the
  `AdminNotification` model's `formatted_title`/`formatted_message` accessors
  (`backend/app/Models/AdminNotification.php`), which translate legacy `notifier.*` keys and strip
  leftover placeholders for clean display.

### 18.2 Admin Notification Settings
**Controller:** `AdminNotificationSettingsController`
(`backend/app/Http/Controllers/Admin/AdminNotificationSettingsController.php`): `index` (`:14`) and
`update` (`:24`) under `admin.notification-settings.*`.

> Note: these routes are **not** wired in the admin group in `web.php` (the admin's notification
> toggles are actually edited via the Settings page §16 `notification_settings` form). The
> per-admin record is `AdminNotificationSetting` (`backend/app/Models/AdminNotificationSetting.php`),
> with one row per admin (`getOrCreateForUser`, defaults everything true) and boolean toggles:
> registration / product_purchase / dispute_created / payment / topup / support_chat /
> manual_delivery / low_stock / sound. `isEnabled($type)` maps event types to fields and defaults
> to enabled.

---

## 19. Supplier Management & Settings

**Controller:** `SupplierController` (`backend/app/Http/Controllers/Admin/SupplierController.php`)

| Action | path:line | Route |
|---|---|---|
| `index` | `:15` | `GET suppliers` → `admin.suppliers.index` |
| `show` | `:43` | `GET suppliers/{supplier}` |
| `settings` | `:57` | `GET suppliers-settings` → `admin.suppliers.settings` |
| `updateSettings` | `:67` | `POST suppliers-settings` → `admin.suppliers.settings.update` |

Routes at `web.php:129–132`.

- `index` lists `User::where('is_supplier', true)`, searchable by name/email, paginated 20, with
  stats (total / active / summed `supplier_balance`).
- `show` 404s for non-suppliers; loads `supplierProducts` and `withdrawalRequests`.
- `settings`/`updateSettings` manage a single global `Option` `telegram_support_link` (required url).

**Views:** `admin/suppliers/{index,show,settings}.blade.php`.

---

## 20. Withdrawal Request Processing

**Manages:** Supplier payout requests — approve, mark paid, reject — with balance accounting.

**Controller:** `WithdrawalRequestController` (`backend/app/Http/Controllers/Admin/WithdrawalRequestController.php`)

| Action | path:line | Route |
|---|---|---|
| `index` | `:18` | `GET withdrawal-requests` → `admin.withdrawal-requests.index` |
| `show` | `:61` | `GET withdrawal-requests/{withdrawalRequest}` |
| `approve` | `:70` | `POST withdrawal-requests/{withdrawalRequest}/approve` |
| `reject` | `:283` | `POST withdrawal-requests/{withdrawalRequest}/reject` |
| `markAsPaid` | `:165` | `POST withdrawal-requests/{withdrawalRequest}/mark-paid` |

Routes at `web.php:135–139`.

- `index` filters by status / supplier_id / date range, paginated 20, with stats
  (total / pending / paid / total paid amount).
- **`approve`** (status must be `pending`): in a `DB::transaction` with `lockForUpdate()` on the
  supplier, syncs supplier balance via `BalanceService::syncSupplierBalance`, computes truly
  available funds (available + matured held earnings, minus other pending withdrawals), and rejects
  if insufficient. Sets `status='approved'`, `processed_at`, notifies the supplier.
- **`markAsPaid`** (status must be `approved`): in a transaction, re-syncs balance, verifies
  `supplier_balance ≥ amount`, decrements `supplier_balance`, then walks `SupplierEarning` rows
  FIFO (oldest available first), marking them `withdrawn` (splitting a row when only partially
  consumed). Sets `status='paid'` + optional `admin_comment`, notifies supplier.
- **`reject`** (cannot reject an already-`paid` request): requires `admin_comment`, sets
  `status='rejected'`, notifies supplier with the reason.
- All supplier notifications use `SupplierNotification` and are wrapped in try/catch (logged, not fatal).

**Views:** `admin/withdrawal-requests/{index,show}.blade.php`.

---

## 21. Other admin sections present in routes/views (brief)

These belong to adjacent domains but are reachable from the admin panel and share its layout/middleware:

- **Product Disputes** — `ProductDisputeController` (`web.php:142–149`): list, new-count badge, show,
  mark-in-review, resolve-refund, resolve-replacement, reject, replacement-products lookup.
  Views: `admin/disputes/{index,show}.blade.php`.
- **Support Chats** — `SupportChatController` (`web.php:152–163`): list, unread-count, messages,
  show, send-message, assign, status, typing indicators (throttled 60/min), notes add/delete.
  Views: `admin/support-chats/{index,show}.blade.php`.

---

## Appendix: Route → Controller quick map (admin group)

| Route name prefix | Controller | Notes |
|---|---|---|
| `admin.dashboard` | DashboardController | analytics |
| `admin.users.*` + block + update-balance | UserController | balance via BalanceService + AuditLog |
| `admin.admins.*` + block | AdminController | **admin.main** group + inline checks |
| `admin.profile.*` | ProfileController | self email/password |
| `admin.service-accounts.*` + export/import/bulk-action/upload-image/(update|apply)-sort-order/update-notes | ServiceAccountController | inventory |
| `admin.product-moderation.*` | ProductModerationController | approve/reject supplier products |
| `admin.promocodes.*` + bulk-destroy | PromocodeController | destroy/bulk = main-admin only |
| `admin.promocode-usages.index` | PromocodeUsageController | read-only |
| `admin.vouchers.*` | VoucherController | full resource (incl. show) |
| `admin.purchases.*` | PurchaseController | index/show/destroy only |
| `admin.purchase-rules.*` | PurchaseRulesController | Option-backed |
| `admin.manual-delivery.*` | ManualDeliveryController | index/show/process **admin.main**; count/statistics all admins |
| `admin.banners.*` | BannerController | positions/scheduling |
| `admin.pages.*` | PageController | XSS-sanitized HTML |
| `admin.contents.*` | ContentController | dynamic repeatable fields + file uploads |
| `admin.site-content.*` | SiteContentController | tabbed Option forms + menus |
| `admin.articles.*` | ArticleController | published/draft + categories |
| `admin.article-categories.*` | ArticleCategoryController | TYPE_ARTICLE via CategoryService |
| `admin.product-categories.*` | ProductCategoryController | parents, TYPE_PRODUCT |
| `admin.product-subcategories.*` | ProductSubcategoryController | children, requires parent |
| `admin.email-templates.*` + send-test | EmailTemplateController | preview + SMTP test send |
| `admin.notification-templates.*` | NotificationTemplateController | system vs mass; system undeletable |
| `admin.notifications.*` | NotificationController | mass fan-out to all users |
| `admin.settings.*` + test-smtp + notification-check | SettingController | Option (encrypted secrets), Telegram webhook |
| `admin.activity-logs.index` | AuditLogController | audit trail (read-only) |
| `admin.admin_notifications.*` | AdminNotificationController | bell dropdown poller |
| `admin.suppliers.*` + suppliers-settings | SupplierController | supplier directory + telegram link |
| `admin.withdrawal-requests.*` | WithdrawalRequestController | payout approve/pay/reject |
| `admin.disputes.*` | ProductDisputeController | (adjacent domain) |
| `admin.support-chats.*` | SupportChatController | (adjacent domain) |
