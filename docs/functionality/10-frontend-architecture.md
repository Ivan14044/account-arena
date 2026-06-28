# Frontend Architecture — Cross-Cutting Concerns

Functional inventory of the **Account Arena** Vue 3 SPA shell: app bootstrap, routing, global state, layout, i18n, theming, global UX, shared composables/utilities/directives, and build/config. Page-specific business logic (checkout, catalog, profile, articles, etc.) is documented separately; this document covers the application shell and everything cross-cutting.

> Stack: **Vue 3.5** (Composition API + `<script setup>`), **Vue Router 4**, **Pinia 3** (+ persistedstate), **Vuetify 3** (icons/components), **vue-i18n 11** (legacy:false), **Tailwind CSS 3** (class dark mode), **Axios 1.11**, **Vite 5**. Mixed `.ts`/`.js` sources. Code comments are predominantly in Russian.

---

## 1. App Bootstrap & Plugin Registration

### 1.1 HTML entry — `frontend/index.html`
- `<html lang="uk">`; title `Account Arena`; favicon; MDI font CSS loaded directly from `node_modules`.
- **Google Tag Manager** snippet injected inline (container `GTM-TGGFZ32Q`) plus the `<noscript>` iframe fallback (`index.html:12-20, 118-119`).
- **Inline anti-FOUC theme bootstrap** (`index.html:105-113`): before the app loads, reads `localStorage['theme']`; if `'dark'` (or unset and `prefers-color-scheme: dark`) toggles `html.dark`. This prevents a light flash before Vue/`useTheme` runs.
- **App preloader** (`#app-preloader`, `index.html:121-125`): full-screen overlay with a pulsing logo (`/img/logo_trans.webp`), shown until `App.vue` removes it. `#app` starts `opacity:0` and fades in via `.app-loaded`; `body.loading` disables scroll. Dark-aware via `html.dark #app-preloader`.
- Fixed `<header>` height 60px, `z-index:100` reserved in inline CSS; mounts `<div id="app">` and loads `/src/main.js` as a module.

### 1.2 JS entry — `frontend/src/main.js`
Order of registration (`main.js:32-46`):
1. `import './bootstrap'` — configures the global Axios instance **first** (side-effect import, see §11.3).
2. `createApp(App)` then `app.use(...)` in order: **i18n → router → vuetify → pinia → Toast → Vue3Lottie**.
3. `app.directive('intersect', IntersectDirective)` — global scroll-reveal directive (§9).
4. `app.mount('#app')`.

Other bootstrap behavior:
- **Performance optimizations** (`initPerformanceOptimizations`, §8.3) run on `DOMContentLoaded` or immediately if DOM ready (`main.js:16-22`).
- **Manual scroll restoration**: `window.history.scrollRestoration = 'manual'` so Vue Router controls scroll (`main.js:25-27`).
- **Pinia persistence**: `pinia.use(piniaPluginPersistedstate)` (`main.js:29-30`).
- **vue-toastification** options: `position: TOP_RIGHT`, `timeout: 5000`, fade transition (`main.js:37-41`).
- The initial HTML preloader is **not** hidden here — `App.vue` hides it after critical data loads.

### 1.3 Plugin configs
- **Vuetify** — `frontend/src/plugins/vuetify.js`: `createVuetify` with MDI icon set as default (`defaultSet: 'mdi'`, aliases). No custom Vuetify theme defined (theming is Tailwind-class-driven, not Vuetify-theme-driven).
- **i18n** — `frontend/src/i18n/index.js` (see §5).
- **Pinia + persist** — registered in `main.js`; per-store opt-in (see §3).
- **Toast** — `vue-toastification` (global, plus a standalone interface created in `bootstrap.js` for use outside components).
- **Lottie** — `vue3-lottie` is globally registered but **currently unused** by any component in the tree (loaders animate a static WebP via CSS; no `*.json` lottie asset is imported anywhere in `src/`).

---

## 2. Routing — `frontend/src/router.js`

History mode: `createWebHistory()` (HTML5, clean URLs; requires server fallback to `index.html`). Critical pages (`MainPage`, `LoginPage`, `RegisterPage`, `AuthCallback`, `NotFound`) are **statically imported**; everything else is **lazy-loaded** via dynamic `import()`.

### 2.1 Complete Route Table

| Path | Name | Component | Load | Meta | Notes |
|---|---|---|---|---|---|
| `/` | — | `pages/MainPage.vue` | static | — | Home |
| `/login` | — | `components/auth/LoginPage.vue` | static | `requiresGuest` | |
| `/register` | — | `components/auth/RegisterPage.vue` | static | `requiresGuest` | |
| `/forgot-password` | — | `components/auth/ForgotPasswordPage.vue` | lazy | `requiresGuest` | |
| `/reset-password/:token` | — | `components/auth/ResetPasswordPage.vue` | lazy | `requiresGuest`, `props:true` | token passed as prop |
| `/auth/callback` | — | `components/auth/AuthCallback.vue` | static | — | OAuth/social token landing (§2.4) |
| `/profile` | — | `pages/account/ProfilePage.vue` | lazy | `requiresAuth` | |
| `/account/:id` | — | `pages/account/AccountDetail.vue` | lazy | — | product/account detail |
| `/products/:id` | — | `pages/account/AccountDetail.vue` | lazy | — | alias of account detail |
| `/balance/topup` | — | `pages/BalanceTopUpPage.vue` | lazy | `requiresAuth` | |
| `/articles` | — | `pages/articles/ArticlesAll.vue` | lazy | `isArticlesList` | |
| `/articles/page/:page` | — | `pages/articles/ArticlesAll.vue` | lazy | `isArticlesList` | paginated |
| `/categories/:id` | — | `pages/articles/ArticlesAll.vue` | lazy | `isArticlesList` | category-filtered |
| `/categories/:id/page/:page` | — | `pages/articles/ArticlesAll.vue` | lazy | `isArticlesList` | |
| `/articles/:id` | — | `pages/articles/ArticleDetails.vue` | lazy | — | |
| `/checkout` | — | `pages/CheckoutPage.vue` | lazy | — | **guests allowed** (auth guard removed intentionally) |
| `/order-success` | — | `pages/OrderSuccessPage.vue` | lazy | `requiresAuth` | |
| `/become-supplier` | — | `pages/BecomeSupplierPage.vue` | lazy | — | |
| `/faq` | — | `views/FAQPage.vue` | lazy | — | |
| `/guarantees` | — | `views/GuaranteesPage.vue` | lazy | — | |
| `/guarantee` | — | *(redirect)* | — | — | → `/guarantees` |
| `/contacts` | — | `views/ContactsPage.vue` | lazy | — | |
| `/:slug(.*)*` | `dynamic` | `pages/ContentPage.vue` | lazy | `isDynamic` | **catch-all** CMS pages (§2.3) |
| `/404` | — | `pages/NotFound.vue` | static | — | |

> Note: only two named routes exist (`dynamic`, plus the redirect target). No route-level `layout` or `roles` meta is used — layout is global (see §4) and there is no role-based routing (auth is binary: authenticated vs guest).

### 2.2 Navigation guard — `router.beforeEach` (`router.js:170-246`)
Stores are imported dynamically inside the guard to avoid circular deps (they are already in the main bundle via `App.vue`).
1. **Loading spinner**: if navigating between *different* paths (and not the first load), `loadingStore.start()` (`:182-184`).
2. **Hydrate user**: if `authStore.token` exists but `authStore.user` is null, `await authStore.fetchUser()`; on failure → `authStore.logout()` (`:187-194`).
3. **`requiresAuth`**: if not authenticated → redirect to `/login?redirect=<fullPath>` (`:197-204`).
4. **`requiresGuest`**: if authenticated → redirect to `/` (unless already on `/`) (`:207-215`).
5. **Articles entry-point tracking**: when first entering an `isArticlesList` route from a non-list route, saves the origin in `sessionStorage['articlesEntryFrom']` (used by `BackLink`, §4.6). Wrapped in try/catch for Safari private mode (`:218-227`).
6. **`isDynamic` resolution** (`:229-243`): strips slashes from the path to a slug, lazy-loads `usePageStore`, fetches pages if empty, and if the slug exists sets the current page and proceeds; otherwise redirects to `/404`.

### 2.3 `router.afterEach` (`router.js:249-275`)
Stops the loading spinner after the transition, deferred via `setTimeout(150)` + double `requestAnimationFrame` to ensure content rendered. **Exception**: if `loadingStore.message` contains "Подготовка/Preparing/Підготовка" (order-fulfillment text), the spinner is **not** auto-hidden — `OrderSuccessPage` keeps it until the purchased product is delivered.

### 2.4 `scrollBehavior` (`router.js:138-167`)
1. Back/forward → restore `savedPosition` (and flags `sessionStorage['articlesUsedSavedPosition']`).
2. `isArticlesList` routes → returns `false` (the list component defers scroll until data loads).
3. Hash anchors → scroll to element (`behavior:'auto'`).
4. Default → scroll to top after a double `requestAnimationFrame` (Firefox layout-readiness quirk).

### 2.5 OAuth / social auth callback
`/auth/callback` (`components/auth/AuthCallback.vue`) reads `?token=` from the URL, calls `authStore.setToken(token)` + `fetchUser()`, then routes to `/`; on missing token → `/login?error=callback`. The social login buttons live in `components/auth/SocialAuthButtons.vue` (a Telegram login widget type exists in `types/telegram.d.ts`). The provider redirect lands the user on `/auth/callback` with a token.

---

## 3. Global State — Pinia Stores (`frontend/src/stores/`)

All stores import the **pre-configured Axios instance** from `../bootstrap` (base URL + interceptors centralized). Only two stores persist to `localStorage`. Token handling is **inconsistent**: `auth` sets a global `axios.defaults` Authorization header, but `auth` (several actions) and `notifications` (all actions) also attach explicit per-request `Bearer` headers read directly from `localStorage['token']`, while the rest rely on the axios default.

| Store (id) | Export | File | Persisted | Purpose |
|---|---|---|---|---|
| `auth` | `useAuthStore` | `auth.ts` | manual (`localStorage` keys `token`,`user`,`user-language`) | Authentication & session (§3.1) |
| `loading` | `useLoadingStore` | `loading.ts` | no | Global ref-counted loading spinner (§3.2) |
| `header` | `useHeaderStore` | `header.ts` | no | Header UI flags (§3.3) |
| `browserSessions` | `useBrowserSessionsStore` | `browserSessions.ts` | no | Remote browser session control (§3.4) |
| `accounts` | `useAccountsStore` | `accounts.ts` | no | Product/account catalog cache |
| `articles` | `useArticlesStore` | `articles.ts` | no | Articles + categories + pagination cache |
| `banners` | `useBannersStore` | `banners.ts` | no | Promo banners by position (+ image preload) |
| `contents` | `useContentsStore` | `contents.ts` | no | Misc content blocks keyed by code |
| `notifications` | `useNotificationStore` | `notifications.js` | no | User notifications (read/unread, polling) |
| `options` | `useOptionStore` | `options.js` | no | Site options/settings (menus, currency, pixel id) |
| `pages` | `usePageStore` | `pages.js` | no | Dynamic CMS pages + current page |
| `productCart` | `useProductCartStore` | `productCart.ts` | **yes** (`product_cart`) | Shopping cart (§3.5) |
| `productCategories` | `useProductCategoriesStore` | `productCategories.ts` | no | Product category tree |
| `promo` | `usePromoStore` | `promo.ts` | **yes** (`promo`) | Promo-code validation result (§3.5) |
| `siteContent` | `useSiteContentStore` | `siteContent.ts` | no | Homepage section content (hero/about/steps…) |

### 3.1 `auth.ts` — `useAuthStore` (Options API store)
- **State**: `user` (object|null, hydrated by an IIFE that validates `localStorage['user']`+`token` and clears both if invalid, `auth.ts:8-32`), `token` (from `localStorage['token']` or `''`), `errors` (validation bag), `userLoaded` (bool).
- **Getters**: `isAuthenticated` = non-empty token **and** user object with `.email` (`:40-44`); `hasSession` = token present only.
- **Actions / endpoints**:
  - `init()` — sets `axios.defaults Authorization: Bearer <token>` from localStorage; if a token exists, `fetchUser()`, then `userLoaded=true` (`:49-67`). Called once from `App.vue onMounted`.
  - `register()` → `POST /register` (+ `lang`); persists token+user, sets default auth header.
  - `login()` → `POST /login`; validates token/user, persists, sets header, applies `user.lang` to i18n + `user-language`; handles HTTP 429 with a custom message.
  - `forgotPassword()` → `POST /forgot-password`; `resetPassword()` → `POST /reset-password`.
  - `logout()` → `GET /logout`; `finally` clears token/user (state+localStorage) and deletes the default auth header.
  - `fetchUser()` → `GET /user`; on error calls `logout()`.
  - `cancelSubscription()` → `POST /cancel-subscription`; `toggleAutoRenew()` → `POST /toggle-auto-renew`.
  - `update()` → `POST /user`; only shows spinner for non-`lang`-only updates; captures 422 errors.
  - `setToken()` / `setUser()` — set state + localStorage (+ header for token).
- **No 2FA, no email-verification flow** in this store; social login arrives via `/auth/callback` (§2.5).

### 3.2 `loading.ts` — `useLoadingStore`
- **State**: `isLoading` (bool), `activeRequests` (ref count), `message` (string|null).
- **Actions**: `start(message?)` increments count + sets loading/message; `stop()` decrements (clamped ≥0) and clears when zero; `reset()` forces all to default. Drives `NavigationLoader`/`FullPageLoader` and the router spinner.

### 3.3 `header.ts` — `useHeaderStore`
- **State only**: `isReady` (header intro-animation complete), `showMenu` (mobile menu open), `printedText` (typewriter buffer for the "Account Arena" header animation). No getters/actions.

### 3.4 `browserSessions.ts` — `useBrowserSessionsStore`
- Tracks one remote browser session: `url`, `pid`, `port`.
- **Actions/endpoints**: `startSession(serviceId)` → `GET /browser/new` (params `service_id`, `uiLanguage` from `authStore.user.extension_settings.uiLanguage`); `stopSession(pid)` / `stopSessionByPort(port)` → `POST /browser/stop`; `stopAllSessions(clean?)` → `POST /browser/stop_all`; `listSessions()` → `GET /browser/list`. (Used to launch automated browser sessions for purchased accounts.)

### 3.5 Persisted stores
- **`productCart`** (`productCart.ts`): `persist: { key: 'product_cart', storage: localStorage }`. State `items: CartItem[]`; getters `itemCount`, `totalQuantity`, `totalAmount`, `hasProduct/getProduct/getProductQuantity`; actions add/remove/update/increase/decrease/clear (all clamp to `max_quantity`). Also redundantly writes to `localStorage['product_cart']` manually. No API calls.
- **`promo`** (`promo.ts`): `persist: true` (whole store, default key `promo`). State `code`, `loading`, `error`, `result` (discount | free_access), `lastAppliedAt`; `apply()` → `POST /promocodes/validate`; `clear()` resets.

### 3.6 Other store endpoints (one-liners)
- `accounts.fetchAll()` → `GET /accounts`; `fetchById()` → `GET /accounts/{id}`; `fetchSimilar()` → `GET /accounts/{id}/similar`.
- `articles.fetchArticles()`/`fetchArticlesPage()` → `GET /articles`; `fetchArticleById()` → `GET /articles/{id}`; `fetchCategories()` → `GET /categories`.
- `banners.fetchAll()` → `GET /banners/all`; `fetchBanners(position)` → `GET /banners?position=`; preloads images via `Image` + `<link rel=preload>`.
- `contents.fetchContent(code, lang)` → `GET /contents/{code}?lang=`.
- `notifications.fetchData()`/`fetchChunk()` → `GET /notifications`; `markNotificationsAsRead()` → `POST /notifications/read`; `markAllAsRead()` → `POST /notifications/read-all` (all pass explicit Bearer header from localStorage).
- `options.fetchData()` → `GET /options` (guarded by `isLoaded`); getter `getOption(key, default)`.
- `pages.fetchData()` → `GET /pages`; `setPage()` sets current.
- `productCategories.fetchAll()` → `GET /categories?type=product` (leftover `console.log` debug present).
- `siteContent.loadContent()` → `GET /site-content`; falls back to local locale files on error.

---

## 4. Layout System

There is **no per-route layout switching** — `App.vue` hardcodes a single layout via a computed:

```
const layoutComponent = computed(() => DefaultLayout)   // App.vue:87
```

`EmptyLayout.vue` exists (template is just `<router-view/>`) as an alternative shell but is **not currently wired into routing**. `DefaultLayout` handles guest pages internally by hiding chrome.

### 4.1 `App.vue` (`frontend/src/components/App.vue`)
- Renders `<component :is="layoutComponent">` + global `NavigationLoader` + `SupportChatWidget`, plus a hidden global SVG `#header-glass-distortion` filter (the "liquid glass" displacement map used by header/menus).
- **`onMounted` orchestration** (`:118-187`): `loadingStore.start()`, `authStore.init()`, then loads **critical** data in parallel (`pages`, `options`, plus `notifications` if authenticated) via `Promise.allSettled`; then fires **background** data (`productCategories`, `accounts`, `banners`) without `await`; preloads logo + current-locale flag; then `loadingStore.stop()`, `isLoading=false`, and `hideAppPreloader()` (fades out the HTML preloader, adds `#app.app-loaded`, removes `body.loading`, removes the node after 400ms).
- Listens for a global `window 'app:hide-loader'` event to force-hide the preloader.

### 4.2 `DefaultLayout.vue` (`components/layout/DefaultLayout.vue`)
- The primary shell: `AnimatedBackdrop` → `MainHeader` → `<main><router-view/></main>` → `MainFooter`, plus globally-mounted `ScrollToTop`, `CookieBanner`, `FacebookPixel`.
- **Header and footer are hidden on guest routes**: `v-if="!route.meta.requiresGuest"` — login/register/forgot/reset render bare.
- Uses `useTheme()` (passes `isDark` into the backdrop). Flex column, `min-height:100vh`, gray-100 / dark gray-900 background.

### 4.3 `MainHeader.vue`
- Floating centered rounded-full "liquid glass" pill (`max-w-7xl`, blur + tint + shine layers, SVG `#header-glass-distortion`). Holds logo, `LanguageSelector`, `MainMenu` (rendered twice: desktop `hidden lg:block`, mobile `block lg:hidden`), `ThemeSwitcher`, `ServiceCart`, `NotificationBell`, `UserMenu`.
- **Intro animation**: pill starts collapsed `w-[64px]` and expands to full width (`isReady`, CSS `transition: width 1.5s`); a typewriter effect prints "Account Arena" into `headerStore.printedText`.
- Uses `useLoadingStore`, `useHeaderStore`, `useScroll({ throttleMs: 100 })`.

### 4.4 Footer
- `MainFooter.vue` — dark footer (`#161719`, rounded top), brand/address/support email/copyright (all i18n), Visa/Mastercard image; composes `FooterMenu`. Pinned via `mt-auto`.
- `FooterMenu.vue` — links sourced from **`useOptionStore`** option `footer_menu` (double `JSON.parse`, keyed by locale); `is_blank` items open new tab, else `router.push`.

### 4.5 Navigation (desktop vs mobile)
- `MainMenu.vue` (`components/MainMenu.vue`) — desktop horizontal list (`hidden lg:flex`) + burger button (`flex lg:hidden`) that opens `MobileMenu`. Items from **`useOptionStore`** option `header_menu` (locale-keyed JSON, `ru` fallback). Click modes: `is_blank` (new tab), `is_scroll` (smooth-scroll anchor, or navigate home then scroll), normal `router.push` (with `window.location.href` fallback).
- `MobileMenu.vue` (`components/MobileMenu.vue`) — slide-in left drawer `Teleport`ed to `body` (overlay `z-[9998]`, panel `z-[9999]`). Controlled by `isOpen` prop; closes on overlay click / X / `Escape`; locks `body` overflow while open; items passed in via prop from `MainMenu`. Same three click modes.

### 4.6 Header controls & decorative
- `UserMenu.vue` — account dropdown; authenticated shows name + balance (`Intl.NumberFormat('ru-RU', currency)` from options) with Top-up/Profile/Logout; logout clears cart + resets notifications + routes `/`; unauthenticated shows a Login button. Closes on outside `mousedown`.
- `ServiceCart.vue` — cart button with item-count badge (`9+` cap) + total amount (desktop only), routes to `/checkout`. Uses `productCart` + `options`.
- `NotificationBell.vue` — bell + unread badge + glass dropdown; **polls every 10s** (`fetchData(3)`), optimistic mark-as-read, plays `/sounds/notification.mp3` on new notifications. Uses `useNotificationStore` + `useAuthStore`.
- `AnimatedBackdrop.vue` — fixed full-viewport `pointer-events-none` SVG of four animated blurred gradient blobs; `opacity-50` dark / `opacity-30` light; respects `prefers-reduced-motion`.
- `BackLink.vue` — smart back button: restores `sessionStorage['articlesEntryFrom']` for `isArticlesList` routes; else `router.back()` if history exists; else `router.push('/')`.

**Shared layout patterns**: liquid-glass styling (`backdrop-filter: blur()` + SVG distortion) recurs across header/UserMenu/LanguageSelector/NotificationBell/BackLink; all outside-click dropdowns ignore clicks inside `.support-modal-container`; currency formatting is consistently `Intl.NumberFormat('ru-RU', { style:'currency', currency })` from `useOptionStore`.

---

## 5. Internationalization (i18n)

### 5.1 Config — `frontend/src/i18n/index.js`
- `createI18n({ legacy: false, globalInjection: true, fallbackLocale: 'en', warnHtmlMessage: false })` (Composition API mode).
- **Supported languages**: `en`, `uk` (Ukrainian), `ru` (Russian). Locale files statically imported and registered as messages.
- **Initial locale selection** (`index.js:17-22`): `localStorage['user-language']` if present, else `getBrowserLocale()` — first segment of `navigator.language`, restricted to the three supported codes, **defaulting to `uk`**.

### 5.2 Locale files — `frontend/src/i18n/locales/{en,uk,ru}.json`
- One JSON file per language (≈39–55 KB each). Flat top-level namespaces shared across all three: `common, auth, services, perMonth, plans, profile, checkout, subscriptions, hero, adBanners, about, promote, steps, footer, reviews, subscribe, notifications, cookie, alert, plugin, confirm, balance_topup, cart, loader, articles, savings, account, catalog, order_success, supportChat, products, becomeSupplier, meta, faq, guarantees, contacts`. (`uk` additionally includes a `subscribe`/`account` ordering difference — namespaces are otherwise parallel.)

### 5.3 Locale selection / persistence at runtime
- `LanguageSelector.vue` (`components/layout/LanguageSelector.vue`): static language list `en/uk/ru` with flag SVGs (`/img/lang/<code>.svg`). On change: sets `locale.value`, persists `localStorage['user-language']`, and if authenticated calls `authStore.update({ lang })` to sync server-side.
- **Axios locale header**: the request interceptor in `bootstrap.js` always sends `X-Locale` from `localStorage['user-language']` (default `'uk'`) on every request (§11.3).
- **hreflang**: handled by the `useHreflang` composable (§8.1), not by the selector component.
- Data-store localization: many stores/composables resolve `field_uk`/`field_en`/base by current locale with fallback (see `utils/localization.ts`, `useProductTitle`, `useBanners` — three slightly different implementations of the same pattern).

---

## 6. Theming (Light / Dark)

Tailwind **class-based** dark mode (`darkMode: 'class'` in `tailwind.config.js`); the `.dark` class on `<html>` is the single source of truth.

- **Anti-FOUC**: inline script in `index.html` (§1.1) applies `.dark` before the bundle runs.
- **`useTheme.ts` composable** (`composables/useTheme.ts`) — the canonical theme manager (module-level singleton `isDark` ref shared app-wide):
  - `applyTheme(dark)` toggles `documentElement.classList('dark')`, briefly adds `.disable-transitions` (double `rAF`) to avoid flicker, and persists to **`localStorage['theme']`** (`'dark'`/`'light'`, deferred via `setTimeout`).
  - On mount reads `localStorage['theme']`, else falls back to `window.matchMedia('(prefers-color-scheme: dark)')`.
  - Listens to the cross-tab `storage` event to sync theme across tabs.
- **`ThemeSwitcher.vue`** is the UI only — imports `useTheme`, renders a glass pill light/dark radio toggle (sun/moon, animated CSS indicator), calls `applyTheme`. No Lottie. Consumers of `useTheme`: `ThemeSwitcher`, `DefaultLayout`, `AccountDetail`.

---

## 7. Global UX (loaders, toasts, banners, scroll, breadcrumbs, images, pixel)

### 7.1 Loaders
- **`NavigationLoader.vue`** — mounted once in `App.vue`; full-screen translucent overlay (`z-[9998]`, backdrop-blur) gated on `loadingStore.isLoading`, showing a pulsing **static** `/img/logo_trans.webp` + optional `loadingStore.message`. CSS keyframes, no Lottie.
- **`FullPageLoader.vue`** — top-most `z-[9999]` loader (optional dark/opaque overlay), pulsing logo, gated on `loadingStore.isLoading`. Props `overlay`, legacy `isLoading`; emits `callHideLoader`.
- **`BoxLoader.vue`** — local/inline overlay (`absolute inset-0`, `z-[9999]`) for a single card/box, spinning Vite-imported `@/assets/logo.webp`; prop `expandPadding`. Used by `CheckoutPage` for promo-code loading. No store.
- The HTML `#app-preloader` (§1.1) is the very first loader, removed by `App.vue`.

### 7.2 Toasts & alerts
- **Toasts**: `vue-toastification` (global, top-right, 5s). The response interceptor in `bootstrap.js` raises toasts for 403/422/5xx/network errors (§11.3).
- **Alerts/confirms**: `utils/alert.js` → `useAlert()` wraps **sweetalert2** (`showAlert`, `showConfirm`); `buttonsStyling:false` with Tailwind `customClass` (dark-mode aware), default labels from i18n (`alert.*`, `confirm.*`).

### 7.3 Cookie banner
- `CookieBanner.vue` — fixed bottom consent banner (link to `/cookie-policy`, Accept button). Consent stored in **`localStorage['cookies_accepted'] = 'true'`**. On mount, if not accepted, calls `GET /cookie/check` and only shows the banner if the API returns `show_cookie_banner` (server-side geo/regulation gating).

### 7.4 Scroll-to-top & breadcrumbs
- `ScrollToTop.vue` — fixed bottom-right button, visible when `scrollY > 300` (via `useScroll`, throttled 100ms), `window.scrollTo({top:0, behavior:'smooth'})`.
- `Breadcrumbs.vue` — presentational nav fed by a **`crumbs` prop** (`{name,path}[]`) from the parent page (not from route meta/store); leading Home link; also injects inline **JSON-LD `BreadcrumbList`** (absolute URLs from `window.location.origin`).

### 7.5 Images
- `ImageWithFallback.vue` — `<img>` wrapper that swaps to an inline base64 SVG placeholder when `src` is empty/`'null'`/`'undefined'` or on runtime `@error`; preserves original URL in `data-original-url`; `inheritAttrs:false` (extra attrs bind to inner img). No built-in lazy loading.
- Lazy loading is provided separately via the `useLazyImage` composable and the `v-intersect` directive (§8/§9).

### 7.6 Analytics — Facebook/Meta Pixel + GTM
- **GTM** is hardcoded in `index.html` (container `GTM-TGGFZ32Q`).
- **`FacebookPixel.vue`** — headless component mounted once in `DefaultLayout`. Pixel ID comes from **`optionStore.options.facebook_pixel_id`** (admin-configured, fetched from backend; no-ops if absent). Injects the Meta `fbevents.js` snippet, calls `fbq('init', id)` + `fbq('track','PageView')`, and re-fires `PageView` on every `route.path` change (SPA navigation). Only `PageView` is tracked.

---

## 8. Shared Composables (`frontend/src/composables/`)

| Composable | Returns / API | What it does |
|---|---|---|
| `useTheme()` | `{ isDark, toggleTheme, applyTheme }` | Singleton dark-mode manager; `localStorage['theme']`, `html.dark`, system fallback, cross-tab sync (§6). |
| `useScroll(opts)` | `{ scrollY, isScrolled, handleScroll }` | Single throttled (lodash-es, default 100ms) passive `scroll` listener; auto-cleanup. |
| `useIntersectionObserver(ref, opts)` | `{ isVisible, hasBeenVisible }` | Per-element IntersectionObserver wrapper; `once` unobserves; non-IO fallback. |
| `useLazyImage(ref, opts)` | `{ isLoaded, isInView }` | IO-based lazy image: copies `data-src`→`src` on view (`rootMargin:'50px'`). |
| `useHreflang(path?)` | `{ injectHreflang, clear }` | Injects `<link rel=alternate hreflang>` for ru/en/uk + x-default against `https://account-arena.com`; re-injects on route/locale change. |
| `useSeo(opts)` | `{ title, description, ogImage, canonical, updateTitle, updateMeta }` | Manages `document.title` (appends " - Account Arena"), description, robots noindex, OpenGraph, Twitter Card, canonical; reactive + route-driven. |
| `useStructuredData(fn)` | `{ injectStructuredData, clear }` | Injects/refreshes JSON-LD `<script type="application/ld+json">` (single or array); deep-watch. |
| `useServiceLauncher()` | `{ launchService, closeAllWindows, activeWindows }` | Opens external service URLs in hardened popups (`noopener`, CSP/no-referrer meta, anti-devtools/anti-copy script — same-origin only); polls for close. |
| `useArticleDetail()` | `{ article }` | Reads route `:id`, fetches via `useArticlesStore`, returns locale-merged article. |
| `useBanners(position)` | `{ banners, loading, error, hasBanners, getBannerTitle, loadBanners, loadAllBanners, handleBannerClick }` | Fetches banners (`GET /banners`, `/banners/all`) via the bootstrap axios; localizes titles. |
| `useProductTitle()` | `{ getProductTitle, getProductDescription, getAdditionalDescription, getLocalizedField }` | Locale-aware product fields; sanitizes description HTML (`rel="nofollow noopener noreferrer"`, auto-link, `\n`→`<br>`). |

### 8.3 Utilities (`frontend/src/utils/`)
- `alert.js` — sweetalert2 `useAlert()` (§7.2).
- `localization.ts` — pure `getLocalizedValue`/`getProductTitle`/`getCategoryName` helpers (per-locale fallback chains, handles nested `translations`).
- `performance.ts` — `initPerformanceOptimizations()` (called in `main.js`): adds `.no-backdrop-filter` when `backdrop-filter` unsupported, and `.reduced-motion` when `prefers-reduced-motion: reduce` — both drive CSS optimizations.
- `pluralize.ts` — `pluralizeDays(count, locale)` with correct Slavic plural forms (ru/uk) + en.
- `scrollToElement.ts` — `scrollToElement(selector, opts)`; `scrollIntoView` with `getBoundingClientRect` + `window.scrollTo` fallback.

---

## 9. Directives (`frontend/src/directives/`)

- **`intersect.ts`** → registered globally as `v-intersect` (`main.js:43`). Scroll-reveal directive backed by a **single shared lazy IntersectionObserver** + `WeakMap` of per-element callbacks (perf optimization vs one observer per element). On mount sets hidden classes (`opacity-0 translate-y-8 transition-all duration-1000`); on intersection adds the animation class (default `animate-fade-in-up`). Binding object: `{ class, once, threshold, rootMargin }` (`once` default true). Includes small-screen threshold tuning, an iOS-Safari double-trigger guard, an immediate `rAF` check, and a 500ms retry for zero-height elements. The `animate-fade-in-up` keyframe is defined in `tailwind.config.js`.

---

## 10. Types & Assets

- **`frontend/src/types/`**: `article.ts` (`Article`, `Category`, `Translation`, `CategoryTranslation` interfaces), `telegram.d.ts` (`window.Telegram.Login` widget typing for social auth). Plus `frontend/src/vite-env.d.ts`.
- **`frontend/src/assets/`**:
  - `app.css` (global styles), `logo.webp` (Vite-imported logo).
  - `img/` — small emoji/icon PNGs (`eyes`, `fire`, `pencil`, `warning`).
  - `payment-icons/` — Apple Pay, Google Pay, Visa, Mastercard, Bitcoin, Ethereum, Tether logos.
  - `Dark.json`, `Light.json`, `Universal.json`, `animation.json` — large (~820 KB each) Lottie JSON assets that are **present but not imported/used anywhere** in `src/` (legacy/orphaned).
- **`frontend/public/`**: `favicon.ico`, `robots.txt`, `vite.svg`; `fonts/` (the **SFT Schrifted Sans** trial font family, the Tailwind `font-sans` default); `img/` (hero/bot/emoji images, `logo_trans.webp` used by loaders, `predloader.mp4`/`logo.mp4`, and `img/lang/{en,uk,ru}.svg` flags).

---

## 11. Build & Configuration

### 11.1 Vite — `frontend/vite.config.js`
- Plugin: `@vitejs/plugin-vue`. **Alias** `@` → `src`.
- **Dev server**: port `3000`, `host:true`, `strictPort:true`; **proxy** `/storage` and `/api` → `http://localhost:8000` (`changeOrigin`, `secure:false`).
- **Build**: `outDir:'dist'`, `sourcemap:false`, `minify:'esbuild'`, `chunkSizeWarningLimit:1000`, `base:'/'`.
- **Manual chunk splitting** (`rollupOptions.output.manualChunks`): `vendor-core` (vue/vue-router/pinia), `vendor-vuetify`, `vendor-axios`, `vendor-i18n` (vue-i18n/@intlify), `vendor-heavy` (swiper/lottie/chart), `vendor-utils` (lodash), `vendor` (rest); plus app chunks `stores` (`/stores/`) and `pages-heavy` (ProfilePage/CheckoutPage).

### 11.2 Tailwind — `frontend/tailwind.config.js`
- `darkMode: 'class'`; content globs `index.html` + `src/**/*.{vue,js,ts,jsx,tsx}`.
- Custom keyframe/animation `fade-in-up` (used by `v-intersect`); custom colors `midnight-alpha`, `indigo-soft`(-alpha); `fontFamily.sans`/`sft` → **SFT Schrifted Sans** with system fallbacks. PostCSS via `postcss.config.js`. No plugins enabled in config.

### 11.3 Axios setup — `frontend/src/bootstrap.js`
- **Base URL**: `import.meta.env.VITE_API_BASE || VITE_API_URL || 'http://localhost:8000/api'` (both env names supported for compat; `.env.example` ships `VITE_API_URL`).
- `axios.defaults.withCredentials = true` (cookie/session support, CSRF-friendly) and `X-Requested-With: XMLHttpRequest` on every request.
- **Request interceptor**: sets `X-Locale` header from `localStorage['user-language']` (default `'uk'`) unless already present (handles both AxiosHeaders and plain-object header shapes).
- **Response interceptor** (raises `vue-toastification` toasts): 401 → console warn only (guest access allowed); 403 → "Доступ запрещен"; 422 → server message or generic validation message; ≥500 → server-error toast; no response → network-error toast. Errors re-rejected.
- **Auth token / CSRF notes**: there is **no Authorization interceptor** — the bearer token is applied via `axios.defaults.headers.common['Authorization']` set in `authStore.init()/login()/setToken()` and `delete`d on logout, supplemented by per-request `Bearer` headers in `auth`/`notifications` actions. No explicit CSRF-token interceptor; CSRF relies on `withCredentials` + cookies (Laravel Sanctum-style backend).

### 11.4 TypeScript / lint / format
- `tsconfig.json`: strict, ES2020, bundler resolution, `resolveJsonModule`, `allowJs`, path alias `@/*`→`src/*` (mirrors Vite). Type-check via `vue-tsc --noEmit`.
- ESLint flat config (`eslint.config.js`) + Prettier (`.prettierrc`); scripts: `dev`, `build`, `preview`, `lint`, `lint:fix`, `format`, `type-check`.

---

## 12. Notable Findings / Cross-Cutting Risks

- **Single hardcoded layout**: `App.vue` always uses `DefaultLayout`; `EmptyLayout` is dead code. Guest-page chrome suppression is done inside `DefaultLayout` via `route.meta.requiresGuest`, not via a layout system.
- **Inconsistent token transport**: global `axios.defaults` Authorization header **and** ad-hoc per-request `Bearer` headers (read straight from `localStorage`) coexist across `auth`/`notifications` stores.
- **Orphaned Lottie assets**: `vue3-lottie` is registered and four ~820 KB `*.json` animations ship in `assets/`, but nothing imports them; all loaders use a static WebP + CSS.
- **Duplicated localization logic**: `utils/localization.ts`, `useProductTitle`, and `useBanners` each re-implement the "pick `_uk`/`_en`/base by locale" pattern.
- **Leftover debug logging** in `productCategories.fetchAll`.
- **No role-based access control** in routing — auth is binary (`requiresAuth`/`requiresGuest`).
- **Redundant cart persistence**: `productCart` persists via the Pinia plugin *and* manual `localStorage` writes to the same key.
