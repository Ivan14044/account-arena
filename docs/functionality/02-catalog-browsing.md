# 02 — Product Catalog & Buyer-facing Browsing

Functional inventory for **Account Arena** (Laravel API backend + Vue 3 / Pinia frontend).
Scope: categories & subcategories, product (account) listings/details/similar, public CMS-read
content shown on the storefront, banners, static pages, site-content / option key-values,
articles, and the FAQ / Contacts / Guarantees pages.

> Note on naming: the marketplace sells "accounts" but the codebase models them as **products**
> via the `ServiceAccount` model / `service_accounts` table. "Product" and "account" are used
> interchangeably throughout. All public catalog/browse routes are unauthenticated and rate-limited
> to **300 req/min** (`throttle:300,1`), defined in `backend/routes/api.php:41`. The single exception
> is `GET /contents/{code}` which sits outside that group (`backend/routes/api.php:133`, no throttle).

Languages supported (`backend/config/langs.php:3`): **`ru`** (base / default), **`en`**, **`uk`**.
Currency is a single global option (`currency`, default shown as `USD` on the frontend).

---

## 1. Product Listing (catalog)

**What it does:** Returns the full list of active, visible products for the storefront catalog.
There is no server-side pagination/filter/sort on this endpoint — the **entire** active catalog is
returned in one cached payload and all filtering/sorting/pagination happens client-side.

- **Endpoint:** `GET /accounts` — `backend/routes/api.php:42`
- **Controller:** `AccountController@index` — `backend/app/Http/Controllers/Api/AccountController.php:15`
- **Frontend store:** `useAccountsStore.fetchAll` — `frontend/src/stores/accounts.ts:77`
- **Frontend consumers:** `AccountSection.vue` (grid + client filters), `CatalogSection.vue`
  (filter UI + counts), preloaded in `App.vue` background load (`frontend/src/components/App.vue:157`).

**Inputs:** None (no query params read by the controller).

**Outputs (per item, `AccountController.php:51-76`):**
`id`, `slug`, `sku`, `title`, `title_en`, `title_uk`, `description`, `description_en`,
`description_uk`, `price`, `discount_percent`, `current_price`, `has_discount`, `image_url`,
`category` (`{id, name, slug}` — `name` is the admin/RU name via `admin_name` accessor),
`quantity` (available stock), `total_quantity`, `sold`, `delivery_type`, `created_at` (ISO 8601).

**Business rules:**
- **Visibility / moderation filter** (`AccountController.php:30-34`): only rows where
  `is_active = true` AND `title` not null AND `price` not null AND
  (`moderation_status = 'approved'` OR `supplier_id IS NULL`). Admin products (`supplier_id` null)
  bypass moderation entirely; supplier products must be `approved`.
- **Sorting** (`AccountController.php:35-36`): `sort_order ASC`, then `id DESC` (manual ordering first,
  newest as tie-break).
- **Stock computation** (`AccountController.php:40-49`):
  - Manual-delivery products (`delivery_type = 'manual'`, `requiresManualDelivery()`): `quantity = 999`
    if active else `0`; `total_quantity` and `sold` forced to `0` (they are meaningless for manual).
    `999` is the "unlimited / in stock" sentinel.
  - Automatic-delivery products: `total_quantity = JSON_LENGTH(accounts_data)` (computed in SQL via
    `selectRaw`), `sold = used`, `quantity = max(0, total - used)`.
- **Pricing display** (`current_price = getPriceWithCommission()`, model
  `ServiceAccount.php:205`):
  - Admin products: base `price`, minus active discount if any.
  - Supplier products: `price / (1 - commission/100)` (commission baked into buyer price so supplier
    nets their listed price), then active discount applied on top. Division-by-zero guarded
    (`ServiceAccount.php:245`).
  - `has_discount = hasActiveDiscount()` (`ServiceAccount.php:269`): true only when
    `discount_percent > 0` AND now ≥ `discount_start_date` (or null) AND now ≤ `discount_end_date`
    (or null).
- **Caching:** `Cache::remember('active_accounts_list_v4', 300, ...)` — 5-minute cache
  (`AccountController.php:17`). Comment notes it is invalidated on product changes.
- **Image URL** (`ServiceAccount::getImageUrlAttribute`, `ServiceAccount.php:14`): pass-through for
  `http(s)` URLs; otherwise resolved through `Storage::disk('public')->url()` (strips leading
  `storage/`). Unlike the Category accessor, it does **not** verify file existence.

**Edge cases:**
- On API error the store sets `loaded = true` and `list = []` so the UI is not stuck loading, then
  re-throws (`accounts.ts:92-110`).
- `transform()` coerces `price` to a number and drops items with non-finite `id`
  (`accounts.ts:43-90`).
- Cache key is versioned (`_v4`); deploying a payload-shape change requires bumping the version.

### 1a. Client-side filtering / search / sort / pagination

All of this runs in the browser over the single `/accounts` payload.

- **Filter UI:** `CatalogSection.vue` emits a `filter-change` event with
  `{ categoryId, subcategoryId, hideOutOfStock, showFavoritesOnly, searchQuery }`
  (`CatalogSection.vue:261`). `MainPage.vue:182` holds the filter state and passes it to
  `AccountSection`.
- **Filtering logic:** `AccountSection.vue` `filteredAccounts` computed
  (`AccountSection.vue:198-265`):
  - **Category:** if a subcategory is selected, match `account.category.id === subcategoryId`
    exactly. Else if a parent category is selected, match against a `Set` of `{categoryId + its
    subcategory ids}` (so a parent category includes all its subcategories). Category→subcategory id
    sets are memoized in `categoryCache`.
  - **Hide out of stock:** removes `quantity <= 0`.
  - **Favorites only:** keeps ids present in the `product_favorites` localStorage Set.
  - **Search:** lowercased, split on whitespace; **every** word must appear in title OR description
    OR sku (AND-of-words substring match). Search input is debounced 300 ms
    (`CatalogSection.vue:274`).
- **Sorting:** none client-side; preserves server order (`sort_order`, `id DESC`).
- **Pagination ("Show more"):** `itemsPerPage = 12`, incremented page count via a "Show more"
  button; `displayedAccounts` slices `filteredAccounts` (`AccountSection.vue:121,267-278`). Page
  resets to 1 whenever any filter changes (`AccountSection.vue:280-291`).
- **Localization of card text:** `useProductTitle` composable
  (`frontend/src/composables/useProductTitle.ts`) picks `field` (ru), `field_en` (en), or
  `field_uk` (uk), each falling back to base `field` (ru) when the localized value is empty
  (`useProductTitle.ts:22-36`). Descriptions additionally rewrite external links to add
  `rel="nofollow noopener noreferrer" target="_blank"` (`useProductTitle.ts:60-100`).
- **Counts per category/subcategory:** precomputed in `CatalogSection.vue` `productCounts`
  (`CatalogSection.vue:116-148`); "All categories" pseudo-button (id `0`) shows total list length.
- **Performance:** results are heavily memoized (`accountsCache`, `enrichedDisplayedAccounts`,
  `v-memo` on cards) keyed by locale/currency/list length.

---

## 2. Product Detail

**What it does:** Returns full detail (incl. SEO meta and additional descriptions) for one product
by `id`, `sku`, or `slug`; increments a view counter.

- **Endpoint:** `GET /accounts/{account}` — `backend/routes/api.php:43`
- **Controller:** `AccountController@show` — `backend/app/Http/Controllers/Api/AccountController.php:83`
- **Frontend store:** `useAccountsStore.fetchById` — `frontend/src/stores/accounts.ts:113`
- **Frontend page:** `AccountDetail.vue` — `frontend/src/pages/account/AccountDetail.vue`
  (routes `/account/:id` and `/products/:id`, `frontend/src/router.js:47-53`).

**Inputs:** path param `{account}` = `id` OR `sku` OR `slug` (matched via OR clause,
`AccountController.php:93-97`).

**Outputs (`AccountController.php:120-154`):** everything in the list payload plus
`additional_description(_en/_uk)`, `meta_title(_en/_uk)`, `meta_description(_en/_uk)`,
`show_only_telegram`, `views`. `category` here is `{id, name}` only (no `slug`).
`current_price = getPriceWithCommission()`, `has_discount`, stock fields as in the list.

**Business rules:**
- **Visibility:** same as list — `is_active` AND (`approved` OR admin product)
  (`AccountController.php:87-92`). Non-matching → `firstOrFail()` → **404**.
- **View counter:** `$account->increment('views')` on every successful fetch
  (`AccountController.php:101`) — not deduplicated, so refreshes inflate it.
- **Stock:** uses `getAvailableStock()` (`ServiceAccount.php:285`); manual → 999/0,
  automatic → `count(accounts_data) - used`. `total_quantity`/`sold` zeroed for manual.
- **Not cached** (unlike list/similar) because of the view increment.

**Frontend behavior (`AccountDetail.vue`):**
- Loads via `fetchById(idOrSku)`; on null → redirect `/404` (`AccountDetail.vue:824-828`).
- **Slug canonicalization:** if the product has a `slug` and the current path isn't
  `/products/{slug}`, it `router.replace`s to the slug URL for SEO (`AccountDetail.vue:831-837`).
- Re-fetches on `route.params.id` change (`AccountDetail.vue:854-871`).
- **Displayed price:** uses raw `account.price` in the big price tag (`formatPrice(account.price)`,
  `AccountDetail.vue:221`) — note this shows base price, while cards use `current_price`.
- Description & additional description are localized + HTML-rendered via `v-html`
  (`getProductDescription`/`getAdditionalDescription`, `AccountDetail.vue:564-570`).
- Stats shown: `views`, `sold` (`AccountDetail.vue:416,432`).
- Stock indicator, quantity selector (1..`quantity`), Add-to-cart / Buy-now (Buy-now clears cart →
  `/checkout`), favorites (localStorage `product_favorites`), SKU copy-to-clipboard.
- **SEO:** `useSeo` meta, `useStructuredData` emits `Product` + `BreadcrumbList` JSON-LD
  (`AccountDetail.vue:594-668`), `useHreflang` for alternate languages. Product schema availability
  = `InStock`/`OutOfStock` from `quantity`.

**Edge cases:**
- `show_only_telegram` is returned but not consumed in the read path reviewed (affects checkout/
  display elsewhere).
- A product reachable by numeric `id` but possessing a `slug` will trigger a client redirect; direct
  `id`/`sku` deep links still resolve server-side.

---

## 3. Similar Products

**What it does:** Returns up to 6 related products for a given product, used in the "Similar"
section of the detail page.

- **Endpoint:** `GET /accounts/{account}/similar` — `backend/routes/api.php:44`
- **Controller:** `AccountController@similar` — `backend/app/Http/Controllers/Api/AccountController.php:162`
- **Model logic:** `ServiceAccount::getSimilarProducts(int $limit = 6)` — `ServiceAccount.php:423`
- **Frontend:** `useAccountsStore.fetchSimilar` (`accounts.ts:152`) →
  `SimilarProducts.vue` (`frontend/src/components/products/SimilarProducts.vue`).

**Inputs:** path param `{account}` = `id` OR `sku` (note: `slug` is **not** accepted here, unlike
`show`) — `AccountController.php:170-173`.

**Outputs:** array of list-shaped items (`AccountController.php:199-222`): `id`, `sku`,
`title(_en/_uk)`, `description(_en/_uk)`, `price`, `discount_percent`, `current_price`,
`has_discount`, `image_url`, `category {id,name}`, `quantity`, `total_quantity`, `sold`,
`delivery_type`, `created_at`.

**Business rules — selection algorithm (`ServiceAccount.php:428-478`):** fill up to `limit`,
short-circuiting as soon as full, over only `is_active = true` products excluding self:
1. **Same category** first — `where category_id = self.category_id`, `inRandomOrder()`, up to limit.
2. **Similar price (±30%)** — if still short, `price BETWEEN self.price*0.7 AND self.price*1.3`,
   excluding already-picked ids.
3. **Most recent** — if still short, newest by `created_at DESC`, excluding already-picked ids.
- The candidate query selects `JSON_LENGTH(accounts_data)` so stock can be computed
  (`ServiceAccount.php:440`).
- **Caching:** `similar_products_v2_{id}_{limit}` for 1 hour (`ServiceAccount.php:425-426`).
- The base product itself is loaded with the same visibility filter as `show`
  (`approved` OR admin), `firstOrFail()` → 404 if missing (`AccountController.php:164-174`).

**Important nuance:** `getSimilarProducts` does **NOT** apply the moderation/visibility filter to the
*candidates* — it only filters by `is_active`, `id != self`, non-null `title`/`price`. So a
**pending or rejected supplier product can appear in the Similar list** even though it would be
hidden from the main catalog and its own detail page. (Discrepancy worth flagging.)

**Edge cases:**
- `inRandomOrder()` makes step-1 results non-deterministic within the 1-hour cache window.
- An `extractKeywords()` helper exists (`ServiceAccount.php:489`) but is **unused** (dead code).
- `SimilarProducts.vue` renders nothing when the array is empty (`v-if="products.length > 0"`).

---

## 4. Categories & Subcategories (tree)

**What it does:** Provides the category tree (parents with nested children) used by the catalog
filter UI and article category pages. Categories are polymorphic by `type`: `product` or `article`.

- **Endpoints:**
  - `GET /categories` — `backend/routes/api.php:47` → `CategoryController@index`
    (`backend/app/Http/Controllers/Api/CategoryController.php:19`)
  - `GET /categories/{categoryId}/subcategories` — `backend/routes/api.php:48` →
    `CategoryController@getSubcategories` (`CategoryController.php:30`)
- **Service:** `CategoryService` — `backend/app/Services/CategoryService.php`
- **Resource:** `CategoryResource` — `backend/app/Http/Resources/CategoryResource.php`
- **Model:** `Category` (`backend/app/Models/Category.php`), `CategoryTranslation`
  (`CategoryTranslation.php`)
- **Frontend stores:** `useProductCategoriesStore` (`frontend/src/stores/productCategories.ts`),
  article categories via `useArticlesStore.fetchCategories` (`articles.ts:281`).

**Inputs:**
- `/categories`: optional `?type=product|article` (`CategoryController.php:21`). When `product`:
  only parent product categories (`type='product'` AND `parent_id IS NULL`) with children eager
  loaded. When `article`: `type='article'`. When omitted: **all** categories (no type/parent
  filter) — used by the articles store (`articles.ts:284`).
- `/categories/{id}/subcategories`: path param `categoryId`.

**Outputs (`CategoryResource.toArray`, `CategoryResource.php:15-68`):** `id`, `slug`, `type`,
`image_url`, `name` (localized to current app locale, fallback to first available locale),
`translations` (`{locale: {code: value}}`), and `subcategories` (array of `{id, slug, name,
translations}`) when `children` relation is loaded and non-empty (else `[]`).
Returned as a Laravel resource **collection** → wrapped in `{ "data": [...] }`.

**Business rules:**
- **Localization** (`CategoryResource.php:17-30`): `name` resolved from
  `translations[currentLocale]['name']`, falling back to the first locale present. The frontend
  store also re-derives names per locale via `getCategoryName` (`productCategories.ts:68`).
- **Subcategory query** (`CategoryService::getSubcategories`, `CategoryService.php:96`): children
  with `parent_id = parentId` AND `type = 'product'` (article subcategories are not returned here).
- **Category image URL** (`Category::getImageUrlAttribute`, `Category.php:106`): like the product
  accessor but **verifies file existence** and returns `null` if the file is missing (so broken
  images are suppressed).
- **Caching** (`CategoryService.php:80,98`): `categories_tree_{type|all}` and
  `subcategories_of_{parentId}` for 1 hour each.
- **Admin name accessor** (`Category::getAdminNameAttribute`, `Category.php:199`): RU `name`
  translation (fallback any) — this is what product payloads expose as `category.name`.

**Edge cases:**
- Frontend tolerates both `response.data.data` and `response.data` shapes
  (`productCategories.ts:39`).
- `getSubcategories` endpoint exists but the product catalog UI derives subcategories from the
  nested `subcategories` already present in the `/categories?type=product` response
  (`CatalogSection.vue:182-189`), so the dedicated endpoint is effectively redundant for the main
  catalog.
- Category `type` was added by migration `2025_12_01_000000_add_type_to_categories_table.php`
  (defaults existing categories to `article`).

---

## 5. Articles — Listing & Details

**What it does:** Public blog/articles with category association, pagination, and per-category
filtering. Multilanguage via translation rows.

- **Endpoints:**
  - `GET /articles` — `backend/routes/api.php:45` → `ArticleController@index`
    (`backend/app/Http/Controllers/Api/ArticleController.php:11`)
  - `GET /articles/{article}` — `backend/routes/api.php:46` → `ArticleController@show`
    (`ArticleController.php:65`)
- **Model:** `Article` (`backend/app/Models/Article.php`), `ArticleTranslation`
  (`ArticleTranslation.php`); translation fields = `title, content, meta_title, meta_description,
  short` (`Article.php:14`).
- **Frontend store:** `useArticlesStore` (`frontend/src/stores/articles.ts`)
- **Frontend pages/components:** `ArticlesAll.vue` (list + pagination,
  `frontend/src/pages/articles/ArticlesAll.vue`), `ArticleDetails.vue`
  (`frontend/src/pages/articles/ArticleDetails.vue`), `ArticleSection.vue` (homepage teaser,
  `frontend/src/components/home/ArticleSection.vue`), `ArticleCard.vue`.

**Listing inputs (`ArticleController.php:13-15`):** `limit` (default 10, **capped at 100**),
`offset` (default 0, floored at 0), `category_id` (optional; must be a positive int to apply).

**Listing outputs (`ArticleController.php:57-61`):** `{ success: true, total, items: [...] }`.
Each item = `article->toArray()` plus `translations` grouped as `{locale: {code: value}}`
(`ArticleController.php:35-39`) and `categories` (each with its own grouped `translations`).
`total` is the unpaginated count for the current filter.

**Listing rules:**
- Only `status = 'published'` (`ArticleController.php:22`); ordered `id DESC`.
- `category_id` filter via `whereHas('categories', ...)` when a valid positive int
  (`ArticleController.php:24-26`).
- **Caching:** `articles_list_{limit}_{offset}_{categoryId}` for 1 hour
  (`ArticleController.php:17-19`).

**Detail inputs/outputs (`ArticleController.php:65-99`):** route-model-bound `{article}`. Returns
404 JSON if not `published` (`ArticleController.php:67-68`). Payload = `article->toArray()` +
grouped `translations` + `categories` (with grouped translations, `pivot` stripped). Cached as
`article_show_{id}` for 1 hour.

**Frontend behavior:**
- **List pagination** (`ArticlesAll.vue`): page derived from route (`/articles/page/:page` or
  `/categories/:id/page/:page`); `limit` default 12 (`ArticlesAll.vue:96`). Computes `offset`,
  builds a numbered pager with `…` ellipses (`ArticlesAll.vue:318-331`). Legacy `?offset=&limit=`
  query params are redirected to clean path-based URLs (`ArticlesAll.vue:188-205`). Pages are
  cached in-store (`paginatedCache`, `articles.ts:201-262`) to avoid re-fetch/flicker.
- **Category pages:** `/categories/:id` reuses `ArticlesAll.vue` (`router.js:72-80`). `:id` may be
  numeric id or a slug resolved against loaded categories (`ArticlesAll.vue:109-140`). Category
  title and intro `text` are taken from the category's translations for the current locale
  (`ArticlesAll.vue:154-174`).
- **Localization:** `getLocalizedArticles` / `getLocalizedPaginatedArticles` pick the translation
  for the current locale, derive `title`, `excerpt` (first 140 chars of content), `short`
  (`articles.ts:37-81`).
- **Homepage teaser** (`ArticleSection.vue`): fetches `limit=6`, shows 3–4 responsively, links to
  `/articles`.
- **Detail** (`ArticleDetails.vue`): non-numeric id → `/404` (`ArticleDetails.vue:81-83`); picks the
  current-locale translation; renders `content` via `v-html`; breadcrumbs; `Article` +
  `BreadcrumbList` JSON-LD; hreflang.

**Edge cases:**
- `ArticleSection.vue` template uses `v-if` on the root `<template>` (`ArticleSection.vue:1`) which
  is effectively a no-op — the section always renders its shell even with zero articles.
- 1-hour caches mean newly published/edited articles can lag on the storefront.

---

## 6. Banners

**What it does:** Manages promotional banners by position (homepage top, wide), with date windows
and ordering. Rendered in the hero area.

- **Endpoints:**
  - `GET /banners?position=...` — `backend/routes/api.php:53` → `BannerController@index`
    (`backend/app/Http/Controllers/Api/BannerController.php:14`)
  - `GET /banners/all` — `backend/routes/api.php:54` → `BannerController@all`
    (`BannerController.php:32`)
- **Model:** `Banner` (`backend/app/Models/Banner.php`)
- **Frontend store:** `useBannersStore` (`frontend/src/stores/banners.ts`)
- **Frontend:** `HeroSection.vue` (`frontend/src/components/home/HeroSection.vue`); preloaded in
  `App.vue` (`App.vue:158`).

**Inputs:** `/banners`: `position` (default `home_top`, `BannerController.php:16`). `/banners/all`:
none.

**Outputs:** rows with `id, title, title_en, title_uk, image_url, link, position, open_new_tab,
order` (`BannerController.php:23`). `/banners` returns a flat array ordered by `order`;
`/banners/all` returns the same fields **grouped by `position`** (`BannerController.php:42`).

**Business rules:**
- **Active scope** (`Banner::scopeActive`, `Banner.php:60`): `is_active = true` AND
  (`start_date` null OR ≤ now) AND (`end_date` null OR ≥ now) — i.e. within the live date window.
- **Position scope** (`Banner.php:76`): exact `position` match. Known positions
  (`Banner::getPositions`, `Banner.php:106`): `home_top_wide` (1 wide banner), `home_top` (4
  banners).
- **Ordering:** `/banners` by `order`; `/banners/all` by `position` then `order`.
- **Caching:** `banners_pos_{position}` and `banners_all` for 1 hour
  (`BannerController.php:17,34`).
- **Image URL:** `Banner::getImageUrlAttribute` (`Banner.php:34`) — pass-through http(s) or Storage
  url (no existence check).

**Frontend behavior (`HeroSection.vue`):**
- Reads `home_top` (4-slot grid) and `home_top_wide` (single) from the store.
- **Slot mapping** (`HeroSection.vue:187-215`): banners placed by their `order` into 4 fixed slots;
  empty slots render i18n **placeholder** tiles (`adBanners.banner1..4`). The wide banner uses slot
  0 of `home_top_wide` or a placeholder.
- Localized title via `getBannerTitle` (en/uk/base fallback, `HeroSection.vue:135`). Clicks honor
  `open_new_tab` (`HeroSection.vue:148`). Images preloaded with `<link rel=preload>` for LCP
  (`banners.ts:125-141`).

**Edge cases:**
- Store has both bulk (`fetchAll` → `/banners/all`) and per-position (`fetchBanners` → `/banners`)
  loaders with in-flight de-duplication via polling intervals (`banners.ts:36-120`). `App.vue`
  uses `fetchAll`; `HeroSection` falls back to `fetchBanners` if a position wasn't loaded.

---

## 7. Static Pages (dynamic CMS pages)

**What it does:** Slug-addressable CMS pages (e.g. policy/info pages) with localized title/content.
Used for the catch-all dynamic route.

- **Endpoint:** `GET /pages` — `backend/routes/api.php:49` → `PageController@index`
  (`backend/app/Http/Controllers/PageController.php:9`)
- **Model:** `Page` (`backend/app/Models/Page.php`, uses `HasTranslations` trait),
  `PageTranslation` (`PageTranslation.php`). Translation fields = `meta_title, meta_description,
  title, content` (`Page.php:19`).
- **Frontend store:** `usePageStore` (`frontend/src/stores/pages.js`)
- **Frontend page:** `ContentPage.vue` (`frontend/src/pages/ContentPage.vue`), routed via the
  catch-all `/:slug(.*)*` (`router.js:121-126`).

**Inputs:** none. Returns **all** active pages at once.

**Outputs (`PageController.php:11-22`):** an object **keyed by page `slug`**; each value is
`{locale: {code: value}}` (translations grouped by locale → field map).

**Business rules:**
- Only `is_active = true` pages (`PageController.php:13`).
- **Routing/lookup** (`router.js:229-243`): on a dynamic route, the router loads `/pages` once,
  strips slashes from the path to get the slug, and if `pages[slug]` exists calls
  `pageStore.setPage(pages[slug])` and proceeds; otherwise redirects to `/404`.
- **Render** (`ContentPage.vue`): shows `page[locale].title` and `page[locale].content` (via
  `v-html`); `useSeo` derives meta from the localized title/content
  (`ContentPage.vue:24-38`).

**Edge cases:**
- `ContentPage.vue` indexes `pageStore.page[locale]` directly — a page missing a translation for
  the active locale would render blank for that field (no explicit locale fallback here).
- The catch-all route is last in the table, so it only matches paths not claimed by earlier routes.

---

## 8. Content Blocks (`/contents/{code}`) — repeatable CMS structures

**What it does:** Returns a structured, repeatable content block (e.g. homepage reviews) defined by
a config schema, grouped by locale. Used for arrays of items with multiple fields each.

- **Endpoint:** `GET /contents/{code}` — `backend/routes/api.php:133` (outside the throttle group;
  **no rate limit**)
- **Controller:** `ContentController@show` — `backend/app/Http/Controllers/Api/ContentController.php:10`
- **Model:** `Content` (`backend/app/Models/Content.php`), `ContentTranslation`
  (`ContentTranslation.php`)
- **Schema config:** `backend/config/contents.php` (e.g. `homepage_reviews` with fields
  `rating, name, logo, photo, text`).
- **Frontend store:** `useContentsStore` (`frontend/src/stores/contents.ts`); consumed by
  `ReviewSection.vue` (homepage reviews).

**Inputs:** path param `{code}` (e.g. `homepage_reviews`); store also passes a `lang` query param
(`contents.ts:18`) though the controller does not read it (it returns all locales).

**Outputs (`ContentController.php:41-44`):** `{ code, items: { locale: [ {field: value, ...}, ... ] } }`.
Translation rows are keyed `code.field.index`; the controller regroups them per locale into an
indexed array of objects, back-filling any schema fields missing from a row with `null`
(`ContentController.php:14-39`).

**Business rules:**
- `Content::where('code', $code)->firstOrFail()` → **404** if the code doesn't exist.
- Field set comes from `config("contents.{$code}.fields")` (`ContentController.php:14`); unknown
  fields in translations that don't fit the `code.field.index` 3-part shape are skipped
  (`ContentController.php:23-27`).
- **No caching** on this endpoint.

**Edge cases:**
- An empty/unknown but existing `Content` row returns `items: {}` (empty group). Store sets
  `itemsByCode[code] = []` on error (`contents.ts:24`).

---

## 9. Site Content (`/site-content`) — homepage marketing copy

**What it does:** Aggregates many individual `Option` key-values into a single nested object of
localized marketing copy for the homepage (hero, about, promote, steps) and the Become-Supplier
page.

- **Endpoint:** `GET /site-content` — `backend/routes/api.php:55` → `SiteContentController@index`
  (`backend/app/Http/Controllers/Api/SiteContentController.php:13`)
- **Backing store:** `Option` model (`backend/app/Models/Option.php`), read via `Option::get`.
- **Frontend store:** `useSiteContentStore` (`frontend/src/stores/siteContent.ts`)
- **Frontend:** `MainPage.vue` (loads on mount), `HeroSection.vue`, `PromoteSection.vue`,
  `StepsSection.vue`, `AboutSection.vue`, `BecomeSupplierPage.vue`.

**Inputs:** none.

**Outputs (`SiteContentController.php:15-422`):** a fixed nested object with top-level sections
`hero`, `about`, `promote`, `steps`, `becomeSupplier`, each containing `ru`/`en`/`uk` sub-objects
built from many named options (e.g. `hero_title_ru`, `promote_access_title_en`,
`become_supplier_faq_answer1_uk`, …). Some list-type values are split on `\n` into arrays
(restricted items, payout methods, `SiteContentController.php:189,214`).

**Business rules:**
- Each leaf is `Option::get('<name>_<locale>')`, so missing options yield `null` leaves.
- **Frontend fallback:** store getters return the requested locale, falling back to `ru`, then to
  i18n locale files when the whole payload is null (`siteContent.ts:98-157`; e.g.
  `MainPage.vue:209` `promoteTitle = content.title || t('promote.title')`).
- **No backend cache** here directly, but `Option::get` is itself backed by a 1-hour
  `site_options_all` cache (`Option.php:48`).

**Edge cases:**
- This endpoint is "read of CMS-managed copy"; it is large/verbose by design (one option per
  field per language).

---

## 10. Public Options (`/options`) — whitelisted key-values

**What it does:** Exposes a **whitelisted** subset of site options (safe, non-secret) to the
frontend (currency, telegram bot id, header/footer menus, support chat link).

- **Endpoint:** `GET /options` — `backend/routes/api.php:50` → `OptionController@index`
  (`backend/app/Http/Controllers/OptionController.php:35`)
- **Model:** `Option` (`backend/app/Models/Option.php`)
- **Frontend store:** `useOptionStore` (`frontend/src/stores/options.js`); loaded as critical data
  in `App.vue` (`App.vue:135`).

**Inputs:** none.

**Outputs:** flat object of the whitelisted keys → values, with missing keys set to `null`
(`OptionController.php:42-49`). **Whitelist** (`OptionController.php:17-28`): `currency`,
`telegram_bot_id`, `header_menu`, `footer_menu`, `support_chat_telegram_link`.

**Business rules:**
- **Security:** only whitelisted names are returned; secrets (SMTP/Telegram/Cryptomus/Monobank
  keys) are excluded and are encrypted at rest in the `Option` model
  (`Option.php:17-23,55-62`).
- **Caching:** `site_options` for 1 hour (`OptionController.php:37`).
- Frontend `getOption(key, default)` reads from the object (`options.js:10-18`); e.g.
  `ProductCard`/`AccountDetail` read `currency` for price formatting.

**Edge cases:**
- Store silently swallows fetch errors (`options.js:29`); consumers then fall back to defaults
  (e.g. `'USD'`).

---

## 11. Purchase Rules (`/purchase-rules`)

**What it does:** Returns the optional, multilingual "purchase rules" text block shown at checkout
(read-only CMS content).

- **Endpoint:** `GET /purchase-rules` — `backend/routes/api.php:56` →
  `OptionController@getPurchaseRules` (`backend/app/Http/Controllers/OptionController.php:58`)
- **Backing store:** `Option` key-values.

**Inputs:** none.

**Outputs:** `{ enabled: bool, rules: { ru, en, uk } }`. When disabled, `rules` are empty strings
(`OptionController.php:67-86`).

**Business rules:**
- `enabled` from `Option::get('purchase_rules_enabled')` coerced via `FILTER_VALIDATE_BOOLEAN`
  (`OptionController.php:61-64`); when false, returns empty rules and `enabled: false`.
- When enabled, returns `purchase_rules_ru/en/uk`.
- **Caching:** `purchase_rules` for 1 hour (`OptionController.php:60`).

---

## 12. Support Chat Settings (`/support-chat-settings`)

**What it does:** Returns public support-chat configuration (enabled flag, Telegram link, optional
greeting message localized). Read-only config consumed by the support widget.

- **Endpoint:** `GET /support-chat-settings` — `backend/routes/api.php:57` →
  `OptionController@getSupportChatSettings` (`backend/app/Http/Controllers/OptionController.php:95`)

**Inputs:** locale via `X-Locale` header or `?locale=` query or app locale (validated against
`config('langs')`, `OptionController.php:99-103`).

**Outputs:** `{ enabled, telegram_link, greeting_enabled, greeting_message }`
(`OptionController.php:118-123`).

**Business rules:**
- Booleans coerced via `FILTER_VALIDATE_BOOLEAN`; `telegram_link` defaults to
  `https://t.me/support`; greeting message localized with **ru fallback** when the locale's message
  is empty (`OptionController.php:113-116`).
- **Caching:** `support_chat_settings_{locale}` for 1 hour (`OptionController.php:105`).
- On any exception, returns safe defaults with `enabled: false` (`OptionController.php:127-139`).

---

## 13. FAQ Page (`/faq`)

**What it does:** Static, accordion-style FAQ. **Content is hardcoded in the component per locale**
(not API-driven).

- **Route:** `/faq` → `frontend/src/views/FAQPage.vue` (`router.js:104-106`)
- **Source of content:** `faqItems` computed, hardcoded arrays per locale
  (`FAQPage.vue:82-144`) — `ru` has 6 Q/A, `uk` and `en` have 2 each.

**Business rules / behavior:** single-open accordion (`activeIndex`), breadcrumbs, and a
"contact support" CTA linking to a hardcoded Telegram handle
`https://t.me/account_arena_support` (`FAQPage.vue:53`). No backend, no moderation, no caching.

**Edge cases:** `uk`/`en` FAQ lists are much shorter than `ru` (content parity gap).

---

## 14. Contacts Page (`/contacts`)

**What it does:** Static contacts page with hardcoded support channels and a **frontend-only**
contact form.

- **Route:** `/contacts` → `frontend/src/views/ContactsPage.vue` (`router.js:116-118`)

**Business rules / behavior:** Telegram (`@account_arena_support`), email
(`support@account-arena.com`), and legal info are hardcoded / from i18n strings. The form
(`name`, `email`, `message`) does **not** submit anywhere — `handleSubmit` only shows a success
toast and resets the form (`ContactsPage.vue:91-94`). Breadcrumbs present.

**Edge cases:** No API call, no persistence, no validation beyond HTML `required`.

---

## 15. Guarantees Page (`/guarantees`)

**What it does:** Static guarantees/benefits page driven entirely by i18n strings.

- **Route:** `/guarantees` → `frontend/src/views/GuaranteesPage.vue` (`router.js:107-110`);
  `/guarantee` redirects to `/guarantees` (`router.js:111-114`).

**Business rules / behavior:** 5 guarantee cards (delivery, quality, payment, support,
replacement) with Lucide icons and i18n title/description (`GuaranteesPage.vue:48-76`), plus a
safety CTA block. No backend, no caching.

---

## 16. Homepage assembly (`MainPage.vue`)

**What it does:** Composes the storefront homepage from the above building blocks.

- **Route:** `/` → `MainPage.vue` (`router.js:15`).
- **Sections (in order):** `HeroSection` (banners + hero copy), `StepsSection`, `CatalogSection`
  + `AccountSection` (the product catalog with filters), `ArticleSection`, `AboutSection`,
  `PromoteSection`, `SubscribeSection` (`MainPage.vue:1-122`). `ReviewSection` (consumes
  `/contents/homepage_reviews`) is part of the home set as well.
- Holds the catalog `filters` state and relays `filter-change` from `CatalogSection` to
  `AccountSection` (`MainPage.vue:182-198`).
- Loads `/site-content` on mount (`MainPage.vue:200-203`); emits Organization + WebSite JSON-LD and
  SEO/hreflang.

---

## Cross-cutting notes

- **Multilanguage model.** Two patterns coexist:
  1. **Inline localized columns** on `service_accounts` (`title`, `title_en`, `title_uk`, etc.) —
     resolved client-side by `useProductTitle` with ru-base fallback.
  2. **Translation tables** (`category_translations`, `article_traslations` [sic],
     `content_translations`, `page_translations`) — grouped by locale in API responses; localized
     `name`/`title` chosen server-side (categories) or client-side (articles/pages) with fallback.
  Banners use inline `title/title_en/title_uk` resolved client-side.

- **Visibility / moderation summary.** Catalog list and product detail enforce
  `is_active = true` AND (`moderation_status = 'approved'` OR `supplier_id IS NULL`). Articles
  require `status = 'published'`. Pages/banners require their `is_active`/active-window flags.
  **Exception:** the Similar-products candidate query only filters on `is_active`, so non-approved
  supplier products can leak into the "Similar" carousel.

- **Stock semantics.** `999` = "in stock / unlimited" sentinel for manual-delivery products;
  automatic-delivery stock = `JSON_LENGTH(accounts_data) - used`. Frontend treats `quantity >= 999`
  as "В наличии" and disables the quantity `+` cap for such items
  (`ProductCard.vue:180,346`).

- **Pricing display.** `current_price` (commission + discount baked in) is the canonical buyer
  price used by cards and cart. The product **detail** hero, however, displays raw `price`
  (`AccountDetail.vue:221`) — a known inconsistency between list cards and the detail page.

- **Caching (server).** 5 min: `/accounts`. 1 hour: similar products, categories tree,
  subcategories, articles list, article show, banners, options, purchase rules, support-chat
  settings, and the underlying `site_options_all`. `/accounts/{id}` (detail) and
  `/contents/{code}` are **not** cached. The `/accounts` cache key is versioned (`_v4`).

- **Caching (client).** Pinia stores guard re-fetching via `loaded`/`isLoaded` flags and
  per-key caches (accounts `byId`, articles `paginatedCache`/`articleById`, banners
  `bannersByPosition`, categories list). Most stores fail soft (empty data) on error.

- **Rate limiting.** All public browse endpoints share `throttle:300,1` except `/contents/{code}`,
  which is unthrottled.
