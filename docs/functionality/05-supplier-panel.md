# 05 — Supplier (Seller) Panel

Functional inventory of the **Supplier Panel** of Account Arena: the Blade-rendered (AdminLTE) supplier dashboard, all supplier-facing features, the public "Become a Supplier" landing page, and the admin-side settings that govern suppliers (commission, hold period, payout rules).

All file paths are absolute. Line references use `path:line`. Russian UI strings are quoted verbatim; English glosses are provided.

---

## 0. Architecture Overview

- **Backend:** Laravel. Supplier controllers live in `backend/app/Http/Controllers/Supplier/`. All views are Blade templates under `backend/resources/views/supplier/` and extend the **jeroennoten/laravel-adminlte** package layout (`@extends('adminlte::page')`; login extends `adminlte::auth.login`). There is **no custom master Blade** — the sidebar/navbar are config-driven in `backend/config/adminlte.php`.
- **Frontend (Vue SPA):** Only the public marketing page `frontend/src/pages/BecomeSupplierPage.vue` belongs to this domain. The supplier panel itself is **not** a Vue app — it is server-rendered Blade.
- **Auth model:** There is **no separate guard**. Suppliers, customers, and admins are all rows in the single `users` table and use the default `web` session guard. Role is determined by boolean flags `is_supplier` and `is_admin` on `User`.
- **Routes:** All supplier routes are under the `supplier` URL prefix / `supplier.` route-name prefix in `backend/routes/web.php:170-206`.
- **Money model:** A supplier's earnings flow through `supplier_earnings` rows (status `held` → `available` → `withdrawn`, or `reversed`). The denormalized `users.supplier_balance` is a cached "available" total kept in sync on dashboard/withdrawal page loads.

### Role gates (`backend/app/Providers/AuthServiceProvider.php:24-37`)
- `admin-only` → `user.is_admin === true`
- `supplier-only` → `user.is_supplier === true && !user.is_admin` (a supplier who is also an admin is treated as admin, **not** supplier, for sidebar/menu gating)
- `main-admin` → `is_admin === true && id === 1`

---

## 1. Supplier Authentication

### 1.1 Login page (GET)
- **Route:** `GET /supplier/login` → `supplier.login` (`backend/routes/web.php:175`)
- **Controller:** `Supplier\AuthController::showLoginForm()` (`backend/app/Http/Controllers/Supplier/AuthController.php:11-24`)
- **View:** `backend/resources/views/supplier/login.blade.php` (extends `adminlte::auth.login`)
- **What it does:** Renders the login form. If a user is already authenticated **and** `is_supplier`, redirects to `supplier.dashboard`. (Note: if authenticated but **not** a supplier, the code does *not* log them out — the logout line is commented out at `AuthController.php:20` — it just shows the form.)
- **Inputs:** none (GET).
- **Outputs:** login view, or redirect to dashboard.

### 1.2 Login submit (POST)
- **Route:** `POST /supplier/login` → `supplier.login.post` (`backend/routes/web.php:176`)
- **Controller:** `Supplier\AuthController::login()` (`AuthController.php:26-49`)
- **Form Request:** `Supplier\LoginRequest` (`backend/app/Http/Requests/Supplier/LoginRequest.php`) — rules: `email` required+email, `password` required. `authorize()` returns `true`.
- **Inputs:** `email`, `password`, `remember` (checkbox).
- **Logic:**
  1. `Auth::attempt($credentials, remember)` against the default user table.
  2. On success: `session()->regenerate()`.
  3. **Role check:** if `!$user->is_supplier` → `Auth::logout()` and return error "У вас нет доступа к кабинету поставщика. Обратитесь к администратору." (You don't have access to the supplier panel. Contact the administrator.)
  4. Otherwise redirect to `intended()` → `supplier.dashboard`.
- **Outputs:** redirect to dashboard, or `back()->withErrors()`.
- **Edge cases:**
  - Wrong credentials → "Неверный email или пароль." (Invalid email or password.) with `onlyInput('email')`.
  - Valid customer (non-supplier) credentials → authenticated then immediately logged out with the access-denied error.
  - Same credentials as the main site (note in view: "Войдите, используя учетные данные от сайта" — log in with your site credentials). Becoming a supplier does **not** create a new account; it flips a flag on the existing user (see §2).

### 1.3 Logout
- **Route:** `GET|POST /supplier/logout` → `supplier.logout` (`backend/routes/web.php:181`, inside the protected group)
- **Controller:** `Supplier\AuthController::logout()` (`AuthController.php:51-59`)
- **Logic:** `Auth::logout()`, invalidate session, regenerate CSRF token, redirect to `supplier.login`.

### 1.4 Route protection — `SupplierMiddleware`
- **File:** `backend/app/Http/Middleware/SupplierMiddleware.php` (alias `supplier.auth`)
- **Applied via:** `Route::middleware(['auth', 'supplier.auth'])` (`backend/routes/web.php:179`)
- **Logic:**
  - If not authenticated → store intended URL in session, redirect to `supplier.login` with "Пожалуйста, войдите в систему." (Please log in.)
  - If authenticated but `!is_supplier` → `auth()->logout()` and redirect to login with the access-denied error.
- **Note:** The middleware checks `is_supplier` only; it does **not** exclude admins. Menu/sidebar gating (`supplier-only` gate) *does* exclude admins, but the route middleware would let an `is_supplier && is_admin` user reach supplier endpoints. Standard supplier accounts are `is_supplier=true, is_admin=false`.

**How supplier login differs from user/admin:** Same guard and `users` table, but a dedicated controller/route set under `/supplier`, plus a post-attempt `is_supplier` assertion and a role-enforcing middleware. Admins log in at `/admin`; the storefront SPA uses its own API/session login. No multi-guard configuration is involved.

---

## 2. Becoming a Supplier

There is **no self-service application that flips the flag**. The flow is marketing → contact support → admin manually grants supplier status.

### 2.1 Public landing page "Стать поставщиком" (Become a Supplier)
- **Route (SPA SSR):** `GET /become-supplier` → `spa.become-supplier` (`backend/routes/web.php:251`), served by `Seo\SpaController::index`. Alias `GET /suppliers` → `spa.suppliers` (`web.php:252`); legacy `GET /seo/suppliers` 301-redirects to `/become-supplier` (`web.php:223-225`).
- **Component:** `frontend/src/pages/BecomeSupplierPage.vue`
- **What it does:** A pure marketing page. Sections: welcome banner, supplier stats (hard-coded "500+ / 50K+ / 4.8 / 50+"), 4-step process, digital-goods categories, restricted items, partner benefits, payout methods, FAQ accordion.
- **Content source:** `useSiteContentStore().becomeSupplier(locale)` with i18n fallbacks (admin-editable site content, locale-aware).
- **Primary CTA action (`becomeSupportRedirect`, `BecomeSupplierPage.vue:584-589`):** redirects the browser to the Telegram support link from `optionStore.getOption('support_chat_telegram_link', 'https://t.me/support')`. **There is no application form submission** — the user is funneled to manual Telegram contact.
- **SEO:** `useSeo()` sets title "Стать поставщиком" and description.

### 2.2 Granting supplier status (admin side)
- **Where:** `Admin\UserController` user-edit/update. Validation includes `is_supplier` (boolean), `supplier_balance`, `supplier_commission` (0–100), `supplier_hold_hours` (1–8760) (`backend/app/Http/Controllers/Admin/UserController.php:67-70`).
- **Note:** `supplier_balance` is validated but **deliberately excluded** from the bulk update to avoid accidental overwrite (`UserController.php:73-74`); it is changed only via financial operations.
- **Effect:** Once an admin sets `is_supplier=true` (and typically a commission and hold period) on an existing user, that user can log in at `/supplier/login`.

---

## 3. Supplier Dashboard

- **Route:** `GET /supplier` (and `/supplier/`) → `supplier.dashboard` (`backend/routes/web.php:180`)
- **Controller:** `Supplier\DashboardController::index()` (`backend/app/Http/Controllers/Supplier/DashboardController.php:12-117`)
- **View:** `backend/resources/views/supplier/dashboard.blade.php`

### 3.1 Balance sync on load (side effect)
Before computing metrics, `syncSupplierBalance()` (`DashboardController.php:123-185`) runs inside a DB transaction:
- Finds `SupplierEarning` rows with `status='held'`, `available_at` not null and `<= now()`, locked `forUpdate`.
- Flips them to `status='available'`, sets `processed_at=now()`.
- Increments `users.supplier_balance` by the released sum (guards: sum must be `> 0`; new balance must not go negative; failures are logged, never break the page).
- The same routine exists in `WithdrawalController` (§7.1). This is how "held" funds become "available" — lazily, whenever the supplier opens the dashboard or withdrawals page (there is **no cron** for the release; only the rating recalculation has a command).

### 3.2 Metrics computed (`compact(...)` at `DashboardController.php:99-116`)
| Metric | Source / rule |
|---|---|
| `totalProducts` / `activeProducts` | count of `ServiceAccount where supplier_id=me`; active = `is_active=true` |
| `totalStock` | sum of `getAvailableStock()` over all products |
| `soldCount` | sum of `used` over all products |
| `lowStockProducts[]` | active products that `isLowStock()` (available < 10) and available > 0 |
| `totalRevenue` (30d) | sum of `amount` of completed `Transaction`s on the supplier's `service_account`s in last 30 days |
| `totalOrders` (30d) | count of those transactions |
| `averageCheck` | `totalRevenue / totalOrders` (0 if no orders) |
| `last7Days[]` | per-day revenue for last 7 days for the sales chart |
| `topProducts` | top 5 products by `used > 0` (id, title, sold, revenue=`used*price`, stock) |
| `unreadNotifications` / `unreadCount` | latest 5 unread `SupplierNotification`s + total unread count |
| `rating` | `supplier_rating ?? 100.00` |
| `ratingLevel` | `User::getRatingLevel()` (badge/icon/stars/class) |
| `ratingDetails` | `User::getRatingDetails()` (90-day sales/valid/invalid breakdown) |

### 3.3 View highlights
- Rating card with 5-star display, level badge, and 90-day valid/invalid breakdown + a valid-percent progress bar (`dashboard.blade.php:23-98`).
- Small-box stats for 30-day revenue/orders/avg-check/active-products (`:101-147`).
- Chart.js sales line chart (rendered only when `totalOrders > 0`) (`:152-172`).
- **Balance card** shows `supplier_balance` and `supplier_commission`%, but the "Вывести средства" (Withdraw funds) button here is **disabled** (`dashboard.blade.php:194`) — withdrawals are initiated only from the Withdrawals page.
- Unread-notifications card (only if `unreadCount > 0`), top-5 products table, low-stock table, and quick-action buttons.
- **Edge cases:** New supplier with no sales → rating shown as 100%, empty chart state, "Все товары в наличии" (all in stock). `Transaction` model is queried defensively (`?? collect()`).

---

## 4. Product Management

Supplier products are rows in `service_accounts` (model `ServiceAccount`, `backend/app/Models/ServiceAccount.php`) with `supplier_id` set. Admin products have `supplier_id = null`.

- **Resource routes:** `Route::resource('products', SupplierProductController::class)->except(['show'])` (`backend/routes/web.php:182`) → index, create, store, edit, update, destroy. Plus:
  - `GET /supplier/products/{product}/export` → `supplier.products.export` (`web.php:183`)
  - `POST /supplier/products/upload-image` → `supplier.products.upload-image` (`web.php:184`)
- **Controller:** `Supplier\ProductController` (`backend/app/Http/Controllers/Supplier/ProductController.php`)

### 4.1 List products
- **Route:** `GET /supplier/products` → `supplier.products.index`
- **Controller:** `index()` (`ProductController.php:14-20`) — `auth()->user()->supplierProducts()` (only own products), eager-loads category, newest first.
- **View:** `products/index.blade.php` — DataTable showing ID, title (+category), price (USD), available stock (`count(accounts_data) - used`), sold (`used`), active status badge, **moderation badge**, actions. Export button shown only when available > 0; delete via per-row modal (DELETE).

### 4.2 Create product
- **Route:** `GET /supplier/products/create` → `supplier.products.create`; `POST /supplier/products` → `supplier.products.store`
- **Controllers:** `create()` (`:22-27`), `store()` (`:29-99`)
- **Create view** loads only **product** parent categories (`Category::productCategories()->parentCategories()`); subcategories AJAX-loaded from `/api/categories/{id}/subcategories`.
- **Inputs (validated by `getRules()`, `:418-437`):** `category_id` (nullable, exists), `subcategory_id` (nullable, exists), `is_active` (required boolean), `price` (required numeric, **min 0.01 USD**), `title` (required, max 255), `description`, EN/UK translations (`title_en/uk`, `description_en/uk`), `additional_description(_en/_uk)`, `image` (image, jpeg/png/jpg/gif/webp, max 2 MB), `bulk_accounts` (nullable string).
- **Business rules on store:**
  - Validates category type is `TYPE_PRODUCT`; if a subcategory is chosen it must be a product subcategory and its id becomes the effective `category_id` (`:34-48`).
  - Uploaded image stored to `products` disk `public`; `image_url` set via `Storage::url()`.
  - **Moderation gating (critical):** every supplier-created product is forced to `moderation_status='pending'` and `is_active=false` so it is hidden until an admin approves (`:68-69`).
  - **SEO fields stripped:** `getAllowedFieldsForSupplier()` (`:445-470`) removes `meta_title*`, `meta_description*`, `seo_text*`, `instruction*` — suppliers cannot set SEO/instruction fields.
  - `used=0`, `supplier_id=auth()->id()`, `accounts_data=[]` for single creation.
  - On creation an admin notification is dispatched via `NotifierService::sendFromTemplate('supplier_product_created', …)` (best-effort, wrapped in try/catch) (`:77-95`).
  - **SKU & slug** auto-generated in `ServiceAccount::boot()` (`ServiceAccount.php:40-68`): SKU format `PRD-{6}-{4}`, unique slug from title, `sort_order` appended to end.
- **Output:** redirect to products index with "Товар успешно создан и отправлен на модерацию…" (Product created and sent for moderation; available after admin approval).

### 4.3 Bulk stock upload / import
- **Mechanism:** the `bulk_accounts` textarea — **one account per line** (e.g. `login:password`). Handled by `storeBulkAccounts()` (`ProductController.php:101-193`) when `bulk_accounts` is non-empty on store.
- **Logic:** splits lines, trims, drops empties → array stored in `accounts_data`; rejects if no valid lines ("Добавьте хотя бы один аккаунт"). Same moderation gating (`pending` + inactive) and SEO stripping apply. Admin notification includes `accounts_count`.
- **Validation for bulk:** `bulk_accounts` required string, `title` required, `price` min 0.01, `is_active` required boolean, optional `image` (`:103-110`).
- **Adding stock on edit:** in `update()` (`:259-275`), new `bulk_accounts` lines are **appended** to the existing `accounts_data` (`array_merge`), not replaced.
- **Stock semantics:** available = `count(accounts_data) - used`. The `used` pointer advances on purchase/export; sold accounts are **not removed** from the array (the index just moves forward). For manual-delivery products (`delivery_type='manual'`), stock is `999` if active else `0` (`ServiceAccount::getAvailableStock()`, `:285-320`).
- **JS helpers** in create view: remove-duplicates and shuffle-lines buttons.

### 4.4 Edit / update product
- **Route:** `GET /supplier/products/{product}/edit` → `supplier.products.edit`; `PUT /supplier/products/{product}` → `supplier.products.update`
- **Controllers:** `edit()` (`:195-223`), `update()` (`:225-283`)
- **Ownership check:** both abort `403` "У вас нет доступа к этому товару." if `product.supplier_id !== auth()->id()` (`:198-200`, `:228-230`).
- **Edit view** pre-resolves parent/subcategory from `product.category_id` (subcategory vs parent) and shows stock breakdown (total/sold/available).
- **Update rules:** same `getRules()`. `is_active` taken from `request->boolean('is_active', false)`. SEO fields stripped. New bulk accounts appended.
- **Edge cases / notes:** Editing does **not** reset `moderation_status` back to pending (only create forces pending), so a supplier can edit an already-approved product without re-moderation. The edit view contains an inline Eloquent `Category::where('parent_id', …)` query (logic in the view).
- **Output:** redirect to index with "Товар успешно обновлен." (Product updated.)

### 4.5 Delete product
- **Route:** `DELETE /supplier/products/{product}` → `supplier.products.destroy`
- **Controller:** `destroy()` (`:285-295`) — ownership-checked (`403` otherwise), hard `delete()`, redirect with "Товар успешно удален." (Product deleted.)

### 4.6 Export accounts (stock pull / fulfillment)
- **Route:** `GET /supplier/products/{product}/export` → `supplier.products.export`
- **Controller:** `export()` (`:301-387`)
- **What it does:** Lets the supplier download a `.txt` of *unsold* accounts and **advances the `used` counter** as if they were consumed (so exported accounts won't be sold to customers).
- **Concurrency safety:** runs inside `DB::transaction` with `lockForUpdate()` on the product to avoid a race with a simultaneous purchase. Re-checks ownership after lock.
- **Inputs:** optional `count` query param. If provided, exports `clamp(count, 1, available)`; otherwise exports all remaining.
- **Logic:** slices `accounts_data` from index `used` for `exportCount` items; prepends UTF-8 BOM; filename `product_{id}_{Y-m-d}.txt`; increments `used` by `exportCount` and saves; flashes "Выгружено N товаров. Осталось: M".
- **Outputs:** `text/plain` attachment download.
- **Edge cases:** empty `accounts_data` → "Нет товаров для выгрузки"; nothing available → "Нет доступных товаров для выгрузки"; assignment failure logged → generic error.

### 4.7 Image upload (rich-text editor)
- **Route:** `POST /supplier/products/upload-image` → `supplier.products.upload-image`
- **Controller:** `uploadImage()` (`:392-416`) — validates `upload` as image (max 5 MB), stores under `products/descriptions`, returns CKEditor-style JSON `{url, uploaded:1, fileName}`; on failure `{uploaded:0, error.message}`.

### 4.8 Moderation status & storefront visibility gating
- **Field:** `moderation_status` on `service_accounts` with values `pending` / `approved` / `rejected` (+ `moderation_comment`, `moderated_at`, `moderated_by`). Scopes: `scopePendingModeration/Approved/Rejected` (`ServiceAccount.php:331-352`). Helpers: `requiresModeration()` (true iff `supplier_id` not null), `isApproved()` (admin products always approved; supplier products require `moderation_status='approved'`) (`:354-372`).
- **Storefront gating (where it matters):** the public API `Api\AccountController` (list, single, similar) filters with:
  ```php
  ->where('is_active', true)
  ->where(fn($q) => $q->where('moderation_status','approved')
                      ->orWhereNull('supplier_id'))
  ```
  (`backend/app/Http/Controllers/Api/AccountController.php:31-34, 89-93, 164-168`). So a supplier product is visible to buyers **only when both** `is_active=true` **and** `moderation_status='approved'`. Admin products (null supplier) bypass moderation.
- **Admin approve/reject:** `Admin\ProductModerationController`:
  - `approve()` (`:82+`) — only if currently `pending`; locks row, sets `moderation_status='approved'`, **`is_active=true`** (activates), `moderated_at/by`; creates a `SupplierNotification` and best-effort `NotifierService` message.
  - `reject()` (`:192+`) — requires `moderation_comment` (max 1000); sets `moderation_status='rejected'`, **`is_active=false`**, stores the comment; notifies the supplier (the comment is surfaced to them in the products list and notifications).
- **Supplier view:** the products index shows a moderation badge — "Ожидает модерации" (pending/warning), "Одобрен" (approved/success), "Отклонен" (rejected/danger, with the admin comment as tooltip).

---

## 5. Orders (read-only)

- **Route:** `GET /supplier/orders` → `supplier.orders.index` (`backend/routes/web.php:185`)
- **Controller:** `Supplier\OrderController::index()` (`backend/app/Http/Controllers/Supplier/OrderController.php:12-69`)
- **View:** `orders/index.blade.php`
- **Which orders the supplier sees:** `Transaction`s where `service_account_id` is not null, the related `ServiceAccount.supplier_id = me`, and `status='completed'`. So a supplier sees **only completed sales of their own products** — never other suppliers' orders, never pending/failed transactions.
- **Filters (GET):** `product_id`, `date_from`, `date_to`, `search` (matches buyer name/email or product title via `whereHas`).
- **Outputs:** paginated 20/page (`withQueryString`), plus a product dropdown for filtering, `totalOrders` and `totalRevenue` info-boxes.
- **Columns:** ID, date, product title, buyer (name + email), amount, payment method.
- **Edge cases:** deleted product/user → "—". `totalOrders`/`totalRevenue` are computed from the *filtered* query (count/sum after filters). This is purely read-only — no actions on orders.

---

## 6. Discounts (supplier-set)

Discounts are not a separate table — they are the `discount_percent` / `discount_start_date` / `discount_end_date` columns **on the product itself** (`ServiceAccount`).

- **Resource routes:** `Route::resource('discounts', DiscountController::class)->except(['show'])` (`backend/routes/web.php:186`) → index, create, store, edit, update, destroy.
- **Controller:** `Supplier\DiscountController` (`backend/app/Http/Controllers/Supplier/DiscountController.php`)

### 6.1 List
- `index()` (`:11-21`): own products where `discount_percent > 0`, ordered by percent desc. View shows price, percent, discounted price (`getCurrentPrice()`), validity period (or "Бессрочно" = indefinite), and active/inactive status via `hasActiveDiscount()`.

### 6.2 Create
- `create()` (`:23-32`) lists own products (id/title/price). `store()` (`:34-55`).
- **Inputs:** `product_id` (required, exists), `discount_percent` (required numeric, **min 1, max 99**), `discount_start_date` (nullable date), `discount_end_date` (nullable date, `after_or_equal:discount_start_date`).
- **Ownership:** product re-fetched scoped to `supplier_id=auth()->id()` via `firstOrFail()` (`:43-45`) — cannot discount someone else's product.
- **Effect:** updates the three discount columns on the product. Output redirect "Скидка успешно добавлена!".

### 6.3 Edit / Update / Delete
- Route-model binding is on `{discount}` = a `ServiceAccount`. Each of `edit/update/destroy` checks `discount.supplier_id === auth()->id()` and aborts `403` otherwise.
- `update()` (`:73-90`): re-validates percent (min 1, max 99) and dates; updates columns.
- `destroy()` (`:92-107`): "deleting" a discount = setting `discount_percent=0` and clearing both dates (the product is not deleted).

### 6.4 Pricing interaction (important)
For supplier products, the customer-facing price is computed by `ServiceAccount::getPriceWithCommission()` (`ServiceAccount.php:205-264`):
- Base: `final = supplier_price / (1 - commission/100)` (commission is grossed up onto the buyer).
- Then the discount percent is applied **on top of the commission-adjusted price** (`:258-261`).
- `getCurrentPrice()` routes supplier products through this; admin products apply discount directly to `price`.
- `hasActiveDiscount()` (`:269-280`) requires `discount_percent > 0` and `now` within the optional start/end window.
- **Edge case:** `0%` is disallowed (min 1) because it is meaningless; a commission of 100% (division by zero) is guarded and falls back to base price with a logged warning.

---

## 7. Withdrawals (payout)

- **Controller:** `Supplier\WithdrawalController` (`backend/app/Http/Controllers/Supplier/WithdrawalController.php`)
- **Routes** (`backend/routes/web.php:188-194`):
  - `GET /supplier/withdrawals` → `withdrawals.index`
  - `GET /supplier/withdrawals/create` → `withdrawals.create`
  - `POST /supplier/withdrawals` → `withdrawals.store`
  - `POST /supplier/withdrawals/{withdrawal}/cancel` → `withdrawals.cancel`
  - `GET /supplier/withdrawals/payment-details` → `withdrawals.payment-details`
  - `POST /supplier/withdrawals/payment-details` → `withdrawals.payment-details.update`

### 7.1 Balance model: available vs held
Earnings live in `supplier_earnings` (model `SupplierEarning`, `backend/app/Models/SupplierEarning.php`) with statuses `held`, `available`, `withdrawn`, `reversed`.
- **Available amount** (`WithdrawalController.php:24-32`): sum of earnings that are `status='available'` **OR** (`status='held'` AND `available_at <= now`). Equivalent to `SupplierEarning::scopeAvailable` (`SupplierEarning.php:54-62`).
- **Held amount** (`:34-39`): `status='held'` AND (`available_at` is null OR `available_at > now`) — still inside the hold window.
- `index()` calls `syncSupplierBalance()` first (same logic as the dashboard, `:229-291`) to release matured held earnings into `supplier_balance`.
- **View** (`withdrawals/index.blade.php`): "Доступно к выводу" (available to withdraw), "В холде" (in hold, with note "Средства, ожидающие окончания холд-периода"), commission box, payment-details summary, request-history table, and a create button enabled only when available > 0.

### 7.2 How earnings are created and held (purchase flow)
- **Where:** `ProductPurchaseService` (`backend/app/Services/ProductPurchaseService.php:253-331`).
- On a completed purchase of a supplier product:
  - `supplierSharePercent = clamp(100 - supplier_commission, 0, 100)`; `supplierAmount = round(total * share/100, 2)` (`:270-279`).
  - `holdHours = supplier_hold_hours ?? 6`; `available_at = now + holdHours` (`:311-312`).
  - Creates `SupplierEarning { supplier_id, purchase_id, transaction_id, amount, status='held', available_at }` (`:314-321`), guarded against duplicates (one earning per purchase/transaction/supplier) and zero/negative amounts. The supplier is `lockForUpdate()`-ed.
- **Reversal on refund:** `SupplierEarning::reverse()` / `partialReverse()` (`SupplierEarning.php:70-165`) flip an earning to `reversed` (cannot reverse `withdrawn` funds; partial reverse splits the row). This is how a dispute refund debits the supplier (see §9).

### 7.3 Create / store a withdrawal request
- **`create()`** (`:81-119`): recomputes available/held; **redirects with error** if the supplier has no payment details set ("Сначала укажите реквизиты для вывода средств.") or if `availableAmount <= 0` ("Нет доступных средств для вывода. Дождитесь окончания холда…").
- **`store()`** (`:124-191`) — runs inside `DB::transaction`:
  - Locks the user (`lockForUpdate`) and recomputes available earnings with locks.
  - Subtracts the sum of existing `pending` withdrawal requests so multiple requests can't exceed available: `maxAvailable = round(available - pendingWithdrawals, 2)` (`:147-152`).
  - If `maxAvailable <= 0` → error redirect.
  - **Inputs validated:** `amount` (required numeric, **min 1, max `maxAvailable`**), `payment_method` (`in:trc20,card_uah`).
  - Verifies the chosen method's wallet/card is actually set; resolves `payment_details` snapshot from the user's `trc20_wallet` or `card_number_uah`.
  - Creates `WithdrawalRequest { supplier_id, amount, payment_method, payment_details, status='pending' }`.
  - Output: "Запрос на вывод средств успешно создан!".
- **Note:** Creating a request does **not** immediately deduct `supplier_balance`; the deduction happens when an admin marks it paid (§7.6). Double-spend is prevented at request time by subtracting pending requests.

### 7.4 Cancel a withdrawal request
- **`cancel()`** (`:196-223`): ownership-checked (`403`). Only `status='pending'` can be cancelled ("Можно отменить только запросы со статусом 'В обработке'."). Logs the cancellation and sets `status='rejected'` (no dedicated `cancelled` status exists; user-cancelled and admin-rejected share `rejected`). Output: "Запрос на вывод средств отменен."

### 7.5 Payment details
- **`editPaymentDetails()`** (`:55-59`) → `withdrawals/edit-payment-details.blade.php`.
- **`updatePaymentDetails()`** (`:64-76`): validates `trc20_wallet` (nullable string ≤255) and `card_number_uah` (nullable string ≤255); updates the `User` (both fields are in `User::$fillable`). Output: "Реквизиты успешно обновлены!". The view advises supplying at least one method.

### 7.6 Admin side of withdrawals (lifecycle)
`Admin\WithdrawalRequestController` (`backend/app/Http/Controllers/Admin/WithdrawalRequestController.php`) drives the status machine `pending → approved → paid` (or `rejected`):
- **approve()** (`:70+`): only `pending`; syncs balance, re-checks available funds (guards "insufficient funds"), sets `status='approved'`, notifies supplier (`type='withdrawal_approved'`).
- **markAsPaid()** (`:165+`): only `approved`; syncs balance; throws if `supplier_balance < amount` ("Недостаточно средств на балансе поставщика…"); **`decrement('supplier_balance', amount)`**; marks corresponding `available` earnings as `withdrawn` (splitting if partial); sets `status='paid'`, `processed_at`; notifies (`withdrawal_paid`).
- **reject()** (`:283+`): cannot reject a `paid` request; sets `status='rejected'` with `admin_comment`; notifies (`withdrawal_rejected`).
- **Model:** `WithdrawalRequest` (`backend/app/Models/WithdrawalRequest.php`) — `SoftDeletes`, casts `amount` decimal:2, scopes pending/approved/paid/rejected. Statuses observed: `pending`, `approved`, `paid`, `rejected`.

### 7.7 Withdrawal edge cases
- No payment details → cannot open create page.
- Available ≤ 0 (all funds still in hold, or already requested) → create blocked.
- Cannot request more than `available − pending`.
- Cancel only while pending; once approved/paid it cannot be cancelled by the supplier.
- Balance is only physically decremented at the `paid` step (with a final sufficiency guard).

---

## 8. Supplier Notifications (in-panel)

- **Model:** `SupplierNotification` (`backend/app/Models/SupplierNotification.php`) — `user_id`, `type`, `title`, `message`, `data` (array cast), `is_read` (bool), `read_at`. Scopes `unread`, `forUser`, `byType`; `markAsRead()`. Linked to user via `User::supplierNotifications()` hasMany (`User.php:68-71`).
- **Controller:** `Supplier\NotificationController` (`backend/app/Http/Controllers/Supplier/NotificationController.php`)
- **Routes** (`backend/routes/web.php:196-200`):
  - `GET /supplier/notifications` → `notifications.index` (`index()`, paginated 20, newest first)
  - `POST /supplier/notifications/{id}/mark-read` → `notifications.mark-read` (`markAsRead()`, scoped to `user_id=auth()->id()` via `firstOrFail`)
  - `POST /supplier/notifications/mark-all-read` → `notifications.mark-all-read` (`markAllAsRead()`, bulk update unread → read with `read_at=now`)
  - `GET /supplier/notifications/unread-count` → `notifications.unread-count` (`getUnreadCount()`, returns JSON `{count}`)
- **Navbar badge:** the AdminLTE navbar bell polls `/supplier/notifications/unread-count` (`config/adminlte.php` `navbar_custom`, gated `supplier-only`) to render the unread count.
- **Producers of notifications:** product approval/rejection (`Admin\ProductModerationController`), and withdrawal approved/paid/rejected (`Admin\WithdrawalRequestController`). View renders type-specific icons (`sale`, `low_stock`, `withdrawal`, default bell) and a "Новое" badge for unread.
- **Edge cases:** marking another user's notification read → `firstOrFail()` 404. There is **no** sidebar link for notifications — only the navbar bell and dashboard card.

---

## 9. Disputes (read access)

- **Model:** `ProductDispute` (referenced via `User::supplierDisputes()` and `ProductDispute::forSupplier()` scope).
- **Controller:** `Supplier\DisputeController` (`backend/app/Http/Controllers/Supplier/DisputeController.php`) — **read-only**; the supplier cannot act on a dispute, only view.
- **Routes** (`backend/routes/web.php:202-204`):
  - `GET /supplier/disputes` → `disputes.index`
  - `GET /supplier/disputes/{dispute}` → `disputes.show`
- **index()** (`:14-50`): lists `ProductDispute::forSupplier(me)` (own products only), eager-loads user/serviceAccount/transaction/resolver. Filters: `status`, `date_from`, `date_to`. Paginated 20. Provides stats: total, new, in_review, resolved, rejected, and `total_refunded` (sum of `refund_amount` where `admin_decision='refund'`).
- **show()** (`:55-67`): ownership-checked — aborts `403` "У вас нет доступа к этой претензии" if `dispute.supplier_id !== me`. Read-only detail view.
- **Business meaning surfaced in views:** disputes are buyer claims against the supplier's product; resolution is decided by an admin (`admin_decision` = refund / replacement / rejected). **A refund debits the supplier's balance** (the disputes views state "сумма будет списана с вашего баланса"); the `disputes/show` view shows `refund_amount` as a red "-$…" with "Списано с вашего баланса". Frequent disputes lower the rating and can restrict functionality (warning shown in the recommendations card).
- **Link to earnings:** a refund triggers `SupplierEarning::reverse()/partialReverse()` (§7.2) to claw back the held/available earning.

---

## 10. Supplier Rating

- **Stored field:** `users.supplier_rating` (decimal:2, default conceptually 100) + `rating_updated_at`.
- **Calculation:** `User::calculateSupplierRating()` (`backend/app/Models/User.php:165-211`):
  - Window: **last 90 days**.
  - `totalSales` = count of `Transaction`s on the supplier's products with status in `['completed','success']`.
  - **Newcomer rule:** if `totalSales < 10` → rating forced to **100.00** (and saved).
  - Otherwise: pull resolved disputes in window; `refunds` = decisions `refund`, `replacements` = decisions `replacement`; `invalidSales = refunds + replacements`; `validSales = totalSales - invalidSales`; `rating = validSales / totalSales * 100`, clamped to `[0,100]`, rounded to 2dp. Saved with `rating_updated_at`.
  - **Rating = percentage of "valid" (non-refunded, non-replaced) sales.**
- **Breakdown for UI:** `getRatingDetails()` (`:276-326`) returns total/valid/invalid sales, refunds, replacements, rejected disputes, and valid/invalid percentages (used on the dashboard rating card). Returns 100% defaults when there are no sales.
- **Level/badge:** `getRatingLevel()` (`:216-271`) maps rating to tiers:
  | Rating | Level | Name | Stars | Badge |
  |---|---|---|---|---|
  | ≥95 | excellent | Отличный | 5 | "Топ продавец" |
  | ≥85 | good | Хороший | 4 | "Надежный" |
  | ≥70 | normal | Нормальный | 3 | — |
  | ≥50 | low | Низкий | 2 | "Требует улучшения" |
  | <50 | critical | Критичный | 1 | "Риск блокировки" |
- **Batch recalculation command:** `suppliers:recalculate-ratings` (`backend/app/Console/Commands/RecalculateSupplierRatings.php`) iterates all `is_supplier=true, is_blocked=false` users, calls `calculateSupplierRating()` on each, and prints changed ratings with a progress bar. (No schedule is shown in this domain; it is a manually/cron-invokable artisan command.)

---

## 11. Commission, Hold & Earnings Settings (admin-controlled)

These per-supplier settings are stored on the `users` row and configured by admins; the supplier cannot change them.

### Per-supplier fields (on `User`, casts at `backend/app/Models/User.php:42-55`)
| Field | Cast | Meaning |
|---|---|---|
| `is_supplier` | boolean | grants supplier-panel access |
| `supplier_balance` | decimal:2 | cached available balance (synced from earnings) |
| `supplier_commission` | decimal:2 | platform commission % taken from each sale |
| `supplier_hold_hours` | integer | hold period (hours) before an earning becomes available |
| `supplier_rating` / `rating_updated_at` | decimal:2 / datetime | rating (see §10) |
| `trc20_wallet`, `card_number_uah` | string | payout details (supplier-editable) |

- **Set via:** `Admin\UserController` update — validation `is_supplier` boolean, `supplier_balance` numeric ≥0, `supplier_commission` numeric 0–100, `supplier_hold_hours` integer 1–8760 (`UserController.php:67-70`). `supplier_balance` is intentionally excluded from the mass update (`:73-74`).

### Commission economics
- **Buyer-facing price:** `final = supplier_price / (1 - commission/100)` — commission is grossed up onto the buyer so the supplier nets their stated price (`ServiceAccount::getPriceWithCommission()`, `:205-264`).
- **Supplier earning per sale:** `supplierAmount = total * (100 - commission)/100` of the transaction total (`ProductPurchaseService.php:270-279`). Commission `null` defaults to 0%.
- Both create/edit product views and the withdrawals view surface the current `supplier_commission` to the supplier, noting it is deducted automatically.

### Hold rule
- Each earning is created `held` with `available_at = now + supplier_hold_hours` (default **6** hours if unset) (`ProductPurchaseService.php:311-312`). Funds become withdrawable only after `available_at` passes and a balance sync runs (dashboard/withdrawals page load).

### Admin global supplier settings page
- **Controller:** `Admin\SupplierController` (`backend/app/Http/Controllers/Admin/SupplierController.php`)
- **Routes** (`backend/routes/web.php:129-132`):
  - `GET /admin/suppliers` → `suppliers.index` — list of `is_supplier` users with search; statistics: total, active (not blocked), `total_balance` (sum of `supplier_balance`).
  - `GET /admin/suppliers/{supplier}` → `suppliers.show` — 404 if not a supplier; loads `supplierProducts` and `withdrawalRequests`.
  - `GET /admin/suppliers-settings` → `suppliers.settings` — edit form.
  - `POST /admin/suppliers-settings` → `suppliers.settings.update` — `updateSettings()`: the **only** global supplier setting is `telegram_support_link` (validated `required|url|max:255`), stored via `Option::set`. This link is the support contact shown on the withdrawals pages (`Option::get('telegram_support_link', 'https://t.me/support')`).
- **Note:** Per-supplier commission/hold/balance are edited on the **user** edit screen (`Admin\UserController`), not on this settings page. This settings page is global and currently holds only the Telegram support link.

---

## 12. Route Summary (supplier prefix)

| Method | URI | Name | Controller@method |
|---|---|---|---|
| GET | /supplier/login | supplier.login | Supplier\AuthController@showLoginForm |
| POST | /supplier/login | supplier.login.post | Supplier\AuthController@login |
| GET\|POST | /supplier/logout | supplier.logout | Supplier\AuthController@logout |
| GET | /supplier | supplier.dashboard | Supplier\DashboardController@index |
| GET | /supplier/products | supplier.products.index | Supplier\ProductController@index |
| GET | /supplier/products/create | supplier.products.create | …@create |
| POST | /supplier/products | supplier.products.store | …@store |
| GET | /supplier/products/{product}/edit | supplier.products.edit | …@edit |
| PUT/PATCH | /supplier/products/{product} | supplier.products.update | …@update |
| DELETE | /supplier/products/{product} | supplier.products.destroy | …@destroy |
| GET | /supplier/products/{product}/export | supplier.products.export | …@export |
| POST | /supplier/products/upload-image | supplier.products.upload-image | …@uploadImage |
| GET | /supplier/orders | supplier.orders.index | Supplier\OrderController@index |
| GET | /supplier/discounts | supplier.discounts.index | Supplier\DiscountController@index |
| GET | /supplier/discounts/create | supplier.discounts.create | …@create |
| POST | /supplier/discounts | supplier.discounts.store | …@store |
| GET | /supplier/discounts/{discount}/edit | supplier.discounts.edit | …@edit |
| PUT/PATCH | /supplier/discounts/{discount} | supplier.discounts.update | …@update |
| DELETE | /supplier/discounts/{discount} | supplier.discounts.destroy | …@destroy |
| GET | /supplier/withdrawals | supplier.withdrawals.index | Supplier\WithdrawalController@index |
| GET | /supplier/withdrawals/create | supplier.withdrawals.create | …@create |
| POST | /supplier/withdrawals | supplier.withdrawals.store | …@store |
| POST | /supplier/withdrawals/{withdrawal}/cancel | supplier.withdrawals.cancel | …@cancel |
| GET | /supplier/withdrawals/payment-details | supplier.withdrawals.payment-details | …@editPaymentDetails |
| POST | /supplier/withdrawals/payment-details | supplier.withdrawals.payment-details.update | …@updatePaymentDetails |
| GET | /supplier/notifications | supplier.notifications.index | Supplier\NotificationController@index |
| POST | /supplier/notifications/{id}/mark-read | supplier.notifications.mark-read | …@markAsRead |
| POST | /supplier/notifications/mark-all-read | supplier.notifications.mark-all-read | …@markAllAsRead |
| GET | /supplier/notifications/unread-count | supplier.notifications.unread-count | …@getUnreadCount |
| GET | /supplier/disputes | supplier.disputes.index | Supplier\DisputeController@index |
| GET | /supplier/disputes/{dispute} | supplier.disputes.show | …@show |

All protected routes (everything except login GET/POST) run through `['auth', 'supplier.auth']` (`backend/routes/web.php:179`).

---

## 13. Notable Findings / Gotchas

- **No separate auth guard.** Suppliers reuse the `web` guard and the shared `users` table; role is just `is_supplier`. Login is the same credentials as the main site.
- **Become-a-supplier is not self-service.** The Vue page funnels users to Telegram support; an admin manually flips `is_supplier`.
- **Moderation only forces pending on create, not on edit.** A supplier can edit an approved product without re-triggering moderation (`ProductController::update` does not reset `moderation_status`).
- **Visibility requires BOTH `is_active=true` AND `moderation_status='approved'`** for supplier products on the storefront API.
- **Held → available release is lazy**, happening on dashboard/withdrawal page loads (`syncSupplierBalance`), not via a scheduled job. A supplier who never opens those pages won't see matured funds released until they do.
- **User-cancelled withdrawals reuse the `rejected` status** (no dedicated `cancelled` state) — code comments note this is intentional pending a future migration.
- **`supplier_balance` is decremented only at the admin `paid` step**, with a final sufficiency check; request creation guards double-spend by subtracting pending requests.
- **SEO/instruction fields are stripped from all supplier product writes** (`getAllowedFieldsForSupplier`).
- **The dashboard balance "Withdraw" button is disabled**; withdrawals must start from the Withdrawals page.
- **`edit.blade.php` contains an inline Eloquent query** for subcategories (view-layer DB access).
