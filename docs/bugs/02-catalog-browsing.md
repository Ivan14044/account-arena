# 02 — Catalog & Buyer-facing Browsing: Bug Report

Adversarial security & correctness audit of the **Product Catalog & Buyer-facing Browsing** domain
(categories, products/ServiceAccount listings & detail, public CMS-read APIs, storefront).

Scope reviewed: `AccountController`, `CategoryController`, `ContentController`, `ArticleController`,
`BannerController`, `SiteContentController`, `OptionController`, `PageController`; models
`ServiceAccount`, `Category`, `Content`, `Article`, `Banner`, `Page`, `Option`; `CategoryService`,
`CategoryResource`; frontend stores (`accounts`, `productCategories`, `articles`, `banners`,
`contents`, `siteContent`); `AccountDetail.vue`, `ProductCard.vue`, `SimilarProducts.vue`,
`useProductTitle.ts`; observers (`ServiceAccountObserver`, `BannerObserver`, `ArticleObserver`,
`CategoryObserver`).

---

## CRITICAL — Stored XSS via unsanitized product/CMS HTML rendered with `v-html` on the storefront

- **Category:** Cross-Site Scripting (stored)
- **Confidence:** High
- **Location:**
  - `frontend/src/pages/account/AccountDetail.vue:461` and `:493` (`v-html="description"`,
    `v-html="additionalDescription"`)
  - `frontend/src/components/products/ProductCard.vue:119` (`v-html="displayDescription"`) — fed by
    `SimilarProducts.vue:5` and the catalog grid
  - `frontend/src/composables/useProductTitle.ts:49-106` (builds the HTML string, no sanitization)
  - `frontend/src/pages/ContentPage.vue:10` (`v-html="pageStore.page[locale].content"`)
  - `frontend/src/pages/articles/ArticleDetails.vue:44` (`v-html="article.content"`)
  - `frontend/src/pages/articles/ArticlesAll.vue:15` (`v-html="categoryTextHtml"`)
  - Source fields set by suppliers: `backend/app/Http/Controllers/Supplier/ProductController.php:105-107,431-433`
    (`'description' => ['nullable','string']`, `additional_description*` likewise — no HTML filtering).
- **Description:** Product `description` / `additional_description` (and CMS page/article/category
  content) are rendered into the DOM via Vue `v-html` with **no sanitization anywhere** in the
  frontend. There is no `DOMPurify`/`sanitize-html`/`xss` dependency in `frontend/package.json`, and
  no server-side HTML stripping in the write path. The only transformation `useProductTitle`
  performs is a regex that adds `rel`/`target` to `<a>` tags — it does **not** remove `<script>`,
  `<img onerror=…>`, `<svg onload=…>`, event-handler attributes, or `javascript:` URLs.
- **Code path / why:** A supplier submits a product with
  `description = '<img src=x onerror="fetch(\'/api/...\',{credentials:\"include\"})...">'`. Backend
  validation (`getRules()`) accepts it as a plain string. When any buyer opens the product detail
  page (`getProductDescription` → `v-html`) or sees the card, the payload executes in the victim's
  origin. **Two distinct trigger paths:**
  1. *Approved supplier products* — normal moderation gate applies, but moderation is a human reading
     text, not a sanitizer; a benign-looking description can hide script, and admin-authored products
     are never moderated at all.
  2. *Unmoderated leak via Similar* (see HIGH bug below): `getSimilarProducts()` does **not** filter
     `moderation_status`, so a **pending/rejected** supplier product's title+description reach
     `ProductCard.vue`'s `v-html` with no approval needed — turning the XSS into a fully
     self-service, unmoderated stored-XSS vector.
- **Impact:** Account takeover of any buyer/admin who views a crafted product or CMS page (session
  theft, CSRF-token exfiltration, balance/withdrawal actions in the victim's session, admin-panel
  pivot if an admin previews). High blast radius because the catalog is the homepage.
- **Suggested fix:** Sanitize all rich-text on output with `DOMPurify` (allowlist tags/attrs) before
  `v-html`, OR sanitize on write server-side (e.g. HTMLPurifier) for `description`,
  `additional_description`, article `content`, page `content`, category `text`. Do not rely on the
  moderation queue as an XSS control. Additionally close the Similar-products moderation gap below.

---

## HIGH — Non-moderated (pending/rejected) supplier products leak into the "Similar products" carousel

- **Category:** Broken access control / information disclosure (moderation bypass)
- **Confidence:** High
- **Location:** `backend/app/Models/ServiceAccount.php:428-478` (`getSimilarProducts`), exposed via
  `backend/app/Http/Controllers/Api/AccountController.php:162-226` (`similar`).
- **Description:** The catalog list (`index`) and detail (`show`) both enforce
  `moderation_status = 'approved' OR supplier_id IS NULL`. The Similar-products candidate query does
  **not** — it filters only `is_active = true`, `id != self`, `whereNotNull('title'/'price')`. So any
  supplier product that an admin happens to flip to `is_active = true` while still `pending` or after
  being `rejected` is served to the public through `/accounts/{id}/similar`.
- **Code path / why:** `(clone $query)->where('category_id', …)` / `whereBetween('price', …)` /
  `orderBy('created_at')` never reference `moderation_status` or `supplier_id`. The mapper in
  `AccountController::similar` then exposes `title`, `description`, `price`, `current_price`,
  `image_url`, `category`, stock — i.e. full product disclosure for hidden inventory, plus it feeds
  the unsanitized `v-html` sink (see CRITICAL).
- **Impact:** Disclosure of unapproved/rejected supplier listings (pricing, copy, images) that should
  be invisible; amplifies the stored-XSS vector by removing the moderation gate; lets a rejected
  supplier still get storefront exposure.
- **Suggested fix:** Add the same visibility predicate used by `index`/`show` to the candidate
  `$query` in `getSimilarProducts`:
  `->where(fn($q) => $q->where('moderation_status','approved')->orWhereNull('supplier_id'))`.

---

## HIGH — Catalog & Similar caches are never invalidated (wrong/stale cache keys) → stale stock, price, and visibility

- **Category:** Cache logic error / stale data (incorrect business state, potential oversell)
- **Confidence:** High
- **Location:**
  - Reader: `backend/app/Http/Controllers/Api/AccountController.php:17` (`active_accounts_list_v4`),
    `backend/app/Models/ServiceAccount.php:426` (`similar_products_v2_{id}_{limit}`).
  - Invalidators that miss them: `backend/app/Observers/ServiceAccountObserver.php:19-21`,
    `backend/app/Http/Controllers/Admin/ServiceAccountController.php:808-810`,
    `backend/app/Observers/CategoryObserver.php:25-27`.
- **Description:** The list endpoint caches under key **`active_accounts_list_v4`** (5 min), and
  similar products under **`similar_products_v2_{id}_{limit}`** (1 h). But every invalidation site
  only forgets the **stale legacy keys** `active_accounts_list`, `active_accounts_list_v2`,
  `active_accounts_list_v3` — and **nothing** ever forgets `active_accounts_list_v4` or any
  `similar_products_v2_*` key. The `ServiceAccountObserver` docblock claims "cache automatically
  cleared on product change," but it clears keys that no longer exist.
- **Code path / why:** On product create/update/delete the observer runs `clearAccountsCache()` →
  forgets `_v1/_v2/_v3`. The live `_v4` entry survives its full 5-minute TTL; similar-product entries
  survive a full hour. So price edits, stock depletion, deactivation, moderation approval/rejection,
  and deletion are not reflected until TTL expiry.
- **Impact:**
  - **Stale stock** → buyers see in-stock items that are sold out (and vice-versa); contributes to
    oversell on automatic-delivery products within the 5-min window.
  - **Stale price/discount** → buyers shown an old `current_price`.
  - **Visibility lag** → a product that was just deactivated/rejected remains listed for up to 5 min
    (list) / 1 h (similar); a similar-product cache can serve a now-deleted/hidden item for an hour.
- **Suggested fix:** Update all invalidation sites to forget the current key
  (`active_accounts_list_v4`) and to flush `similar_products_v2_*` (use cache tags on Redis:
  `Cache::tags(['accounts'])` for both, then `Cache::tags(['accounts'])->flush()` in the observer).
  Remove the dead `_v1/_v2/_v3` forgets or keep them only as cleanup.

---

## MEDIUM — Banner edits to the wide hero never invalidate its cache (wrong position keys)

- **Category:** Cache logic error / stale data
- **Confidence:** High
- **Location:** `backend/app/Observers/BannerObserver.php:14-17` vs valid positions in
  `backend/app/Models/Banner.php:106-112` and reader
  `backend/app/Http/Controllers/Api/BannerController.php:17` (`banners_pos_{position}`).
- **Description:** `BannerController@index` caches per position as `banners_pos_{position}`. The only
  real positions are `home_top` and **`home_top_wide`** (`Banner::getPositions`). The observer
  forgets `banners_pos_home_top`, `banners_pos_home_middle`, `banners_pos_home_bottom`,
  `banners_pos_catalog_top` — i.e. three keys that are never produced, and **omits
  `banners_pos_home_top_wide`**. `banners_all` is cleared correctly.
- **Code path / why:** Editing/disabling the wide hero banner leaves `banners_pos_home_top_wide`
  stale for up to 1 hour (HeroSection falls back to `fetchBanners`/`/banners?position=home_top_wide`
  when the position isn't already loaded).
- **Impact:** A disabled/expired or edited wide hero banner can keep showing (or a new one stay
  hidden) for up to an hour. Cosmetic/operational, but a stale expired-promo banner can be misleading.
- **Suggested fix:** In `clearBannerCache()` forget the actual keys: `banners_pos_home_top` and
  `banners_pos_home_top_wide` (ideally derive from `Banner::getPositions()` keys), drop the
  non-existent middle/bottom/catalog ones.

---

## MEDIUM — `/contents/{code}` is unauthenticated **and** unthrottled (DoS / abuse)

- **Category:** Missing rate limit (DoS)
- **Confidence:** High
- **Location:** `backend/routes/api.php:133` (route declared **outside** the `throttle:300,1`
  group at `api.php:41`); controller `backend/app/Http/Controllers/Api/ContentController.php:10`.
- **Description:** Every other public browse endpoint is inside `throttle:300,1`. `/contents/{code}`
  sits outside it, so it has **no rate limit at all**. The handler also runs `firstOrFail()` plus a
  `with('translations')` load and per-request regrouping on every hit (no caching on this endpoint).
- **Code path / why:** Unbounded request volume hits the DB (`Content::where('code',$code)
  ->with('translations')->firstOrFail()`) with zero throttle and zero cache, on a route that takes a
  user-controlled `{code}`.
- **Impact:** Cheap unauthenticated DoS amplifier (DB load) and unrestricted enumeration of content
  codes. Lower impact than a write endpoint, but it is the one deliberately-unthrottled public route.
- **Suggested fix:** Move the route inside the `throttle:300,1` group (or give it its own throttle),
  and add a short `Cache::remember` like the other CMS-read endpoints.

---

## LOW — Catalog list endpoint has no pagination and returns the entire active catalog in one payload

- **Category:** Unbounded query / DoS / performance
- **Confidence:** Medium
- **Location:** `backend/app/Http/Controllers/Api/AccountController.php:15-81` (no `limit`/paginate;
  `->get()` over all active+approved rows).
- **Description:** By design (`docs/functionality/02-catalog-browsing.md:19-23`) `/accounts` returns
  the **entire** active catalog uncapped; all filtering/sorting/pagination is client-side. Each row
  also carries `description(_en/_uk)` (potentially large HTML). As the catalog grows, payload size
  and the 5-minute cache regeneration cost grow unbounded.
- **Code path / why:** No `LIMIT`; the response is one big JSON array. The `JSON_LENGTH(accounts_data)`
  `selectRaw` runs per row but stock is the only thing it needs (fine), yet full descriptions inflate
  every list item.
- **Impact:** Memory/bandwidth blow-up and slow first paint at scale; the 5-min cache miss becomes an
  expensive thundering-herd regeneration. Not currently exploitable beyond resource cost.
- **Suggested fix:** Introduce server-side pagination (or at least drop long description fields from
  the list payload and fetch them on detail), and consider a hard cap on returned rows.

---

## LOW — `current_price` / `price` serialized as strings (`decimal:2` cast) — client coercion masks a contract smell

- **Category:** Price serialization / type inconsistency
- **Confidence:** Medium
- **Location:** `backend/app/Models/ServiceAccount.php:134` (`'price' => 'decimal:2'`),
  `getPriceWithCommission()` returns a float but `'price'` and `'discount_percent'` serialize as
  strings; consumed in `frontend/src/stores/accounts.ts:45-64` (re-parses with `parseFloat`/`Number`).
- **Description:** `price` and `discount_percent` are emitted as quoted strings (e.g. `"10.00"`) due
  to the `decimal:2` cast, while `current_price` is a float. The frontend defensively coerces, but
  any consumer that does arithmetic on the raw API value (or strict `===` comparisons) will be wrong.
  The known list-vs-detail inconsistency (detail hero shows raw `price`, cards show `current_price` —
  `docs:642`, `AccountDetail.vue:221`) compounds buyer confusion but is a product decision, not a bug
  per se.
- **Impact:** Latent correctness risk for any new client/integration; current frontend is shielded by
  `transform()`. No money is mishandled today.
- **Suggested fix:** Normalize numeric outputs to floats in the controller (cast explicitly), and
  align the detail hero to show `current_price` for consistency with cards/cart.

---

## LOW — Article API responses dump full model via `toArray()` (minor over-exposure)

- **Category:** Field over-exposure
- **Confidence:** Medium
- **Location:** `backend/app/Http/Controllers/Api/ArticleController.php:33,76` (`$article->toArray()`),
  model `backend/app/Models/Article.php:22-29`.
- **Description:** Both list and detail return `$article->toArray()`, exposing every column. For
  articles the table is minimal (`id`, `img`, `status`, `created_at`, `updated_at` — and `updated_at`
  is `$hidden`), so the only "extra" leaked fields are `status` and `img`/`description` — not
  sensitive. Categories are also dumped via `$cat->toArray()` (id/slug/type/image_url/timestamps).
  Flagged because the pattern (serialize entire models rather than an explicit allowlist Resource) is
  the same anti-pattern that would leak secrets on a richer table; here the impact is negligible.
- **Impact:** Negligible today (no secret columns on `articles`/`categories`). Maintenance hazard:
  adding an internal column later would auto-leak it.
- **Suggested fix:** Return an explicit field allowlist (API Resource) instead of `toArray()` for
  articles and categories, mirroring the `CategoryResource` approach.

---

## Summary table

| # | Severity | Title | Confidence | Primary location |
|---|----------|-------|-----------|------------------|
| 1 | CRITICAL | Stored XSS via unsanitized `v-html` (product/CMS/article HTML) | High | `AccountDetail.vue:461,493`, `ProductCard.vue:119`, `useProductTitle.ts` |
| 2 | HIGH | Non-moderated products leak into Similar carousel | High | `ServiceAccount.php:428-478` |
| 3 | HIGH | Catalog/Similar caches never invalidated (wrong `_v4` / `similar_products_v2_*` keys) | High | `ServiceAccountObserver.php:19-21`; `AccountController.php:17` |
| 4 | MEDIUM | Banner observer misses `banners_pos_home_top_wide` | High | `BannerObserver.php:14-17` |
| 5 | MEDIUM | `/contents/{code}` unauthenticated **and** unthrottled (DoS) | High | `routes/api.php:133` |
| 6 | LOW | `/accounts` unbounded (no pagination, full descriptions) | Medium | `AccountController.php:15-81` |
| 7 | LOW | `price`/`current_price` type inconsistency (string vs float) | Medium | `ServiceAccount.php:134` |
| 8 | LOW | Article/category responses dump full models via `toArray()` | Medium | `ArticleController.php:33,76` |

---

## Ruled-out (investigated, not a bug or out of scope)

- **SQL injection in catalog list/detail/similar.** No raw user input is concatenated into SQL. `show`
  builds an OR of `id`/`sku`/`slug` via the query builder (parameterized); `index` reads no params;
  `similar` is parameterized. `selectRaw('JSON_LENGTH(accounts_data) …')` uses no user input.
  `articles?category_id=` is validated with `filter_var(...FILTER_VALIDATE_INT, min_range 1)` before
  use. **No SQLi found.**
- **IDOR on product/category/content.** All catalog reads enforce visibility predicates
  (`is_active` + `approved/admin` for products; `is_active` for pages; `status=published` for
  articles). There is no per-user ownership concept on public catalog data, so id/slug enumeration is
  by-design public browsing, not IDOR. (The one real access-control gap is the Similar moderation
  bypass — reported as #2.)
- **Leaking `accounts_data` / `credentials` / supplier internals in catalog responses.** Confirmed
  **not** leaked: `index`/`show`/`similar` build explicit field arrays and never include
  `accounts_data`, `credentials`, `manual_delivery_instructions`, `admin_notes`,
  `moderation_comment`, `cost`, or `supplier_commission`. `accounts_data` is only touched via
  `JSON_LENGTH(...)` for stock counting. (`Article`/`category` `toArray()` is the only over-exposure,
  reported as #8 — and those tables hold nothing sensitive.)
- **Secret options leaking via `/options` / `/site-content`.** `/options` uses an explicit whitelist
  (`OptionController.php:15-29`); secret option names (`smtp_password`, `telegram_bot_token`,
  `cryptomus_*`, `monobank_token`) are encrypted at rest (`Option.php:17-23`) and never whitelisted.
  `/site-content` reads only fixed `*_<locale>` marketing keys. **No secret leak.**
- **Division-by-zero / negative price in commission & discount math.** `getPriceWithCommission`
  guards `commissionMultiplier <= 0` (`ServiceAccount.php:245`). `discount_percent` is capped at
  `max:99` in supplier and admin write paths (`DiscountController.php:38,81`,
  `ServiceAccountController.php:682`), so `price - discount` can't go negative via validated input.
  Math is correct.
- **View-count race.** `$account->increment('views')` (`AccountController.php:101`) is an atomic
  `UPDATE … SET views = views + 1`, so it is race-safe at the DB level. The documented "refresh
  inflates count / not deduplicated" is a product decision, not a correctness bug.
- **i18n fallback exposing wrong data.** `useProductTitle` and `CategoryResource` fall back base→ru or
  to the first available locale; this surfaces *fallback* content, not another tenant's/hidden data.
  `ContentPage.vue` rendering blank for a missing-locale page is a UX gap, not a data leak.
- **Mass assignment in catalog write paths.** The only write in this domain's read controllers is the
  `views` increment (not mass-assignment). `Article.$fillable` includes `created_at` (timestamp
  mass-assignment) but that is an admin-only write path outside the buyer-facing browsing scope.
- **Article list cache staleness on edit.** `ArticleObserver` relies on `Cache::tags(['articles'])
  ->flush()`; the configured driver is **redis** (`CACHE_DRIVER=redis`), which supports tags, so list
  invalidation works in production. Only degrades on a non-tag driver (file/db) — not the deployed
  config — so not reported as a live bug (contrast with #3, which uses plain `Cache::forget` of wrong
  keys regardless of driver).
