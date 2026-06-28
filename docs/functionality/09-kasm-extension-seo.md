# 09 — Account Launching (KASM), Browser Extension, Telegram Bot, SEO/SSR & CLI Commands

Functional inventory for the "Account Arena" marketplace covering: remote streamed-browser account launching, the browser extension, the Telegram bot, server-side SEO/SSR rendering, and maintenance/CLI artisan commands.

All paths are absolute. Line references use `path:line`.

---

## 1. KASM / Remote Streamed-Browser Account Launching

The platform lets a buyer "launch" a purchased account inside a remote, streamed browser so the credentials are never exposed to the buyer's local machine. There are **two distinct layers**:

1. A reusable Laravel package `arthur-salenko/kasm-client` that wraps the **Kasm Workspaces** public API (user/session lifecycle).
2. A separate, currently-active **"browser_api" proxy** (`BrowserController` + inline routes) that talks to an internal browser-streaming service at `BROWSER_API_URL` (default `https://workspace.account-arena.com/api/`). This is what the frontend actually calls today.

> Note: The two are not wired together in the read code. The Kasm package exposes a `Kasm` facade and singleton but no controller in this domain calls it directly; the live launch path goes through `BrowserController`/the inline `/browser/*` routes and the `browser_api` config. Both are documented below.

### 1.1 Kasm client package (`arthur-salenko/kasm-client`)

- **What it does:** Thin Laravel HTTP wrapper over the Kasm Workspaces public REST API. Every call is a JSON `POST` to `{base_url}{endpoint}` with `api_key` + `api_key_secret` automatically merged into the payload.
- **Implementing files:**
  - `backend/packages/ArthurSalenko/kasm-client/src/KasmClient.php` — client.
  - `backend/packages/ArthurSalenko/kasm-client/src/KasmServiceProvider.php` — registers singleton `kasm-client` and merges config (`KasmServiceProvider.php:13`).
  - `backend/packages/ArthurSalenko/kasm-client/src/Facades/Kasm.php` — `Kasm` facade, accessor `kasm-client`.
  - `backend/packages/ArthurSalenko/kasm-client/config/kasm.php` — config keys.
  - `backend/packages/ArthurSalenko/kasm-client/composer.json` — auto-discovery provider + `Kasm` alias.
- **Config / credentials** (`config/kasm.php:3`): `kasm.base_url` (`KASM_BASE_URL`, default `https://your-kasm-url`), `kasm.api_key` (`KASM_API_KEY`), `kasm.api_secret` (`KASM_API_SECRET`). These are **separate** from `config/services.php` (which has no KASM keys).
- **Auth model** (`KasmClient.php:27-43`): every request merges `['api_key' => ..., 'api_key_secret' => ...]` into the body. Returns decoded JSON on `2xx`, otherwise `null` (failures are swallowed silently — no exception).
- **Public methods** (each is a POST):
  - `createUser(array $payload)` → `/api/public/create_user` (`KasmClient.php:51`).
  - `requestKasm(array $payload)` → `/api/public/request_kasm` — start a session (`KasmClient.php:62`).
  - `getKasmStatus(array $payload)` → `/api/public/get_kasm_status` (`KasmClient.php:73`).
  - `destroyKasm(array $payload)` → `/api/public/destroy_kasm` — stop a session (`KasmClient.php:84`).
  - `getImages()` → `/api/public/get_images` — list available workspace images (`KasmClient.php:94`).
- **Inputs:** caller-supplied payload arrays (e.g. `user_id`, `image_id`, `kasm_id`). **Outputs:** decoded Kasm JSON or `null`.
- **Edge cases / business rules:**
  - No timeout is set on the underlying `Http::post` (default Guzzle behavior).
  - Non-2xx responses return `null` with no logging — callers must null-check.
  - `php ^7.4 || ^8.0`, `illuminate/support ^8–11` (composer.json:6-9).

### 1.2 Live browser-launch proxy (`browser_api`)

This is the path the SPA actually uses. The Laravel backend acts as a thin authenticated proxy in front of the internal browser-streaming microservice.

- **Config:** `config/services.php:46` → `services.browser_api.url` from `BROWSER_API_URL` (default `https://workspace.account-arena.com/api/`).

#### 1.2.1 Start a session — `GET /browser/new`
- **Route:** `backend/routes/api.php:135` → `BrowserController@new`.
- **Implementing file:** `backend/app/Http/Controllers/Api/BrowserController.php:13-53`.
- **What it does:** Picks a target app URL and a profile, optionally pairs the session to the logged-in user, then GETs the upstream `{browser_api.url}/new` and pipes the raw JSON back to the client.
- **Inputs (query params):**
  - `app_url` (optional) — destination URL to open; defaults to `https://google.com` (`BrowserController.php:16`).
  - `profile` (optional) — explicit browser profile id. If absent, the controller auto-selects: the first active, non-expired `ServiceAccount` ordered by `id ASC`, using its `profile_id` (`BrowserController.php:19-30`).
  - `uiLanguage` (optional) — forwarded as `lang`, defaults `en` (`BrowserController.php:48`).
  - `service_id` — sent by the frontend store but **not read** by the controller (services "no longer supported", `BrowserController.php:16`).
  - Bearer token (optional) — used only to derive a pairing fragment.
- **Session/credential injection (the core mechanic):**
  - URL normalization: prepends `https://` if scheme missing; falls back to `https://google.com` if not a valid URL (`BrowserController.php:32-38`).
  - **User pairing:** if a valid Sanctum bearer token resolves to a user (`getApiUser`, `Controller.php:14-27`), the controller appends `#sc_pair=sc_u_{userId}` to the app URL (`BrowserController.php:40-43`). This fragment is what the remote browser/extension uses to associate the streamed session with the buyer.
  - Upstream call: `Http::timeout(60)->get($base.'/new', ['app' => $appUrl, 'profile' => $profile, 'lang' => ...])` (`BrowserController.php:45-49`).
- **Outputs:** Raw upstream body + status, forced `Content-Type: application/json` (`BrowserController.php:51-52`). Frontend expects `{ pid, port, url }` (`browserSessions.ts:5-9`).
- **Business rules / edge cases:**
  - The account credentials themselves are **not** passed by Laravel — they live in the upstream profile referenced by `profile_id`; Laravel only forwards the profile id + pairing fragment.
  - Route is **unauthenticated** (outside any auth middleware group). The bearer token is optional; without it, no `sc_pair` is added and a default/auto profile is used.
  - If no active account exists, `profile` is `null` (`BrowserController.php:29`).

#### 1.2.2 Stop a session — `POST /browser/stop`
- **Route + impl:** inline closure `backend/routes/api.php:137-143`.
- **What it does:** Proxies the entire request body to `{browser_api.url}/stop` as JSON (`Http::timeout(60)->asJson()->post`).
- **Inputs:** `{ pid }` or `{ port }` (frontend sends one of these — `browserSessions.ts:45,60`). **Outputs:** raw upstream body/status as JSON.

#### 1.2.3 Stop all sessions — `POST /browser/stop_all`
- **Route + impl:** inline closure `backend/routes/api.php:145-151`.
- **What it does:** Proxies the body to `{browser_api.url}/stop_all`.
- **Inputs:** optional `{ clean: true }` (frontend `browserSessions.ts:73`). **Outputs:** raw upstream JSON.

#### 1.2.4 List sessions — `GET /browser/list`
- **Route + impl:** inline closure `backend/routes/api.php:153-159`.
- **What it does:** GETs `{browser_api.url}/list` and returns the raw body. No inputs.

#### 1.2.5 Frontend clients
- **Primary store:** `frontend/src/stores/browserSessions.ts` (Pinia). `startSession(serviceId)` → `GET /browser/new` with `service_id` + `uiLanguage` (pulled from `user.extension_settings.uiLanguage`, default `en`, `browserSessions.ts:22-29`). `stopSession(pid)`, `stopSessionByPort(port)`, `stopAllSessions(clean)`, `listSessions()` map 1:1 to the routes above. Keeps local `{ pid, port, url }` state.
- **Popup launcher (separate concern):** `frontend/src/composables/useServiceLauncher.ts` — opens the streamed-browser `url` in a hardened `window.open` popup (`launchService(url, serviceName)`, lines 11-101). Hardening applied after `load`:
  - Injects a permissive CSP meta + `referrer: no-referrer` (lines 53-66).
  - Injects an anti-leak/anti-devtools script that: stubs `document.cookie` getter/setter to empty (lines 71-75), nulls `window.cookieStore`, runs a 1s devtools-detection loop that navigates to `about:blank` if devtools open (lines 76-81), and blocks copy/cut/contextmenu plus `Ctrl+C/V/U/I` and `F12` (lines 82-89).
  - `noopener`/`noreferrer` features + `newWindow.opener = null` (lines 27-34). Polls every 1s to clean up closed windows (lines 40-47); `closeAllWindows` on unmount (lines 103-110).
  - Cross-origin failures are caught and ignored (lines 93-96), so the hardening only applies when the popup is same-origin.

---

## 2. Browser Extension

A companion browser extension authenticates against the marketplace via a cookie-borne Sanctum token and stores per-user settings server-side.

### 2.1 Auth model — `ExtensionAuth` middleware
- **Alias:** `ext.auth` → `App\Http\Middleware\ExtensionAuth` (`backend/app/Http/Kernel.php:77`).
- **File:** `backend/app/Http/Middleware/ExtensionAuth.php`.
- **How it authenticates:**
  1. Reads token from the **`sc_auth` cookie** (`ExtensionAuth.php:17`). If empty → `401 Unauthorized (no cookie)` (logs the available cookie names).
  2. Sanitizes: `urldecode` + trims whitespace/quotes (`ExtensionAuth.php:23-24`). Optional `X-EXT-TOKEN` header fallback for diagnostics (`ExtensionAuth.php:28`).
  3. Detects format: PAT if it contains `|`; JWT via regex (`ExtensionAuth.php:32-33`).
  4. If PAT: `PersonalAccessToken::findToken($token)` (`ExtensionAuth.php:41`). The token **must have the `extension` ability** — `$pat->can('extension')` else `403 Forbidden` (`ExtensionAuth.php:44-46`). Resolves `tokenable` user.
  5. Fallbacks: session guard if `auth()->check()` (`ExtensionAuth.php:63-66`). JWT branch is stubbed/commented out (`ExtensionAuth.php:59-60`).
  6. No user → `401 Unauthorized (bad token)`. On success, `auth()->setUser($user)` (`ExtensionAuth.php:73`).
- **Where `sc_auth` is issued:** `AuthController::buildAuthCookie` (`backend/app/Http/Controllers/Auth/AuthController.php:22-39`) creates an httpOnly cookie (7-day TTL) holding the **extension-scoped** Sanctum token. Cookie is `Secure`, `SameSite=none`, domain `.account-arena.com` in prod; `Secure=false`, `SameSite=lax`, null domain on localhost. The token is minted with the `extension` ability: `createToken('extension', ['extension'])` during both register (`AuthController.php:93`) and login (`AuthController.php:151`), then attached via `->withCookie(...)` (`AuthController.php:108,169`).
- **Cookie encryption:** `sc_auth` is in the `EncryptCookies` `$except` list (`backend/app/Http/Middleware/EncryptCookies.php:15`), so it is delivered/read **unencrypted** (the extension can read the raw PAT).

### 2.2 Save extension settings — `POST /extension/settings`
- **Route:** `backend/routes/api.php:162` (inside `ext.auth` group) → `ExtensionController@saveSettings`.
- **File:** `backend/app/Http/Controllers/ExtensionController.php:9-19`; request validation `backend/app/Http/Requests/Extension/SaveSettingsRequest.php`.
- **Inputs:** `{ settings: <array> }` — validated as `required|array` (`SaveSettingsRequest.php:9-14`). Arbitrary key/values (e.g. `uiLanguage`, `keyboardLanguages`) are accepted; no per-key schema.
- **What it does:** Persists the whole `settings` array to `user.extension_settings` and saves (`ExtensionController.php:14-17`).
- **Outputs:** `ApiResponse::success(['ok' => true])`.
- **Business rules:** Stored settings are later consumed by the SPA (e.g. `extension_settings.uiLanguage` feeds `/browser/new`, `browserSessions.ts:23`).

### 2.3 Auth status — `GET /extension/auth`
- **Route:** `backend/routes/api.php:163` (inside `ext.auth` group) → `ExtensionController@authStatus`.
- **File:** `backend/app/Http/Controllers/ExtensionController.php:21-36`.
- **What it does:** Confirms the extension's cookie session is valid and returns the user. Because `ext.auth` already gated the request, `$user` is normally present.
- **Outputs:** `{ authorized: true, user: {...} }`. If somehow no user → `{ authorized: false }` (`ExtensionController.php:24-26`). `user.active_services` is forced to `[]` (subscriptions removed, `ExtensionController.php:30`).
- **Edge cases:** Subscriptions/active-services functionality is deprecated; the field is always empty.

---

## 3. Telegram Bot

A Telegram bot doubles as a **support-chat ingress** (and notification egress). Inbound messages create/append `SupportChat`/`SupportMessage` rows; the same service sends agent replies and notifications outbound. Account linking is by `telegram_id`.

### 3.1 Webhook entrypoint — `POST /telegram/webhook`
- **Route:** `backend/routes/api.php:166` → `TelegramWebhookController@handle`.
- **File:** `backend/app/Http/Controllers/TelegramWebhookController.php:14-72`.
- **What it does:**
  1. Reads the raw update array; logs it at debug level (`TelegramWebhookController.php:17-20`).
  2. Instantiates `TelegramBotService`; if `!isEnabled()`, logs a warning and returns `{ ok: true }` (no-op) (`:24-27`).
  3. **`/start` command:** if message text equals/starts with `/start` (incl. `/start@botname`), sends a localized greeting via `sendGreetingMessage($chatId, $languageCode)` and returns early without creating a chat (`:30-49`).
  4. Otherwise calls `processIncomingMessage($update)` to create/append the support chat (`:52`).
  5. Always returns `200 {ok:true}` — even on exception (caught + logged) — so Telegram never retries (`:62-71`).
- **Inputs:** Telegram Update JSON. **Outputs:** `{ok: true}` always.
- **Edge case:** Webhook is unauthenticated (no signature verification middleware); relies on the bot-token-secret URL pattern.

### 3.2 `TelegramBotService`
- **File:** `backend/app/Services/TelegramBotService.php`.
- **Enablement / token:** reads `Option::get('telegram_client_enabled')` and `Option::get('telegram_bot_token')` from the DB options table at construction (`:25-26`); `isEnabled()` requires both set (`:32-35`). API base `https://api.telegram.org/bot{token}/` (`:16`).
- **Webhook management helpers:** `setWebhook(url)` → `setWebhook` (`:286`), `deleteWebhook()` (`:305`), `getWebhookInfo()` (GET) (`:324`).
- **`/start` greeting (`sendGreetingMessage`, :121-171):**
  - Gated by `Option::get('support_chat_greeting_enabled')` (`:128`).
  - Maps Telegram `language_code` (`uk`/`ru`/`en`) to a locale, defaulting `en` (`:136-141`); loads `support_chat_greeting_message_{locale}`, falling back to `_ru` if empty (`:144-149`).
  - Sends via `sendMessage` with `parse_mode: HTML` (`:156-160`).
- **Inbound message handling (`processIncomingMessage`, :339-411):**
  - Validates presence of `chat`, `from`, `chat.id`, `message_id` (`:352`); skips empty messages with no attachments (`:357-361`).
  - **Dedup:** if a `SupportMessage` with the same `telegram_message_id` exists, it just bumps `last_message_at` and returns (`:365-372`).
  - **Account linking:** looks up `User::where('telegram_id', from.id)` — if found, the chat/message are attributed to that user; otherwise treated as a guest (`:375-376`).
  - Display name priority: `FirstName LastName` > `username` > `User {chatId}` (`formatTelegramDisplayName`, :421-440).
  - `findOrCreateSupportChat` (`:450-477`): reuses the latest non-closed Telegram-sourced chat for that `telegram_chat_id`; else creates a new `SupportChat` with `source=telegram`, `status=pending`, `guest_email=tg{chatId}@telegram.local` (`:474`).
  - Creates a `SupportMessage` (sender = user or guest), then processes attachments, then sets chat `status=open` + `last_message_at` (`:383-399`).
- **Attachments (`processAttachments`/`downloadAndSaveAttachment`, :482-550):** for photos picks the largest size; for documents keeps original filename/mime. Calls Telegram `getFile`, downloads from `https://api.telegram.org/file/bot{token}/{path}`, stores under `support/attachments/Y/m/{uniqid}_{name}` on the `public` disk, and creates a `SupportMessageAttachment` row (`:531-543`).
- **Outbound (`sendMessage`, :181-230 / `sendAttachment`, :235-278):** sends HTML text and/or attachments (photos via `sendPhoto`, others via `sendDocument`). Used by the admin support-chat UI to reply to Telegram users and to push notifications. Timeouts: 10s default, 30s for file ops (`:17-18`).
- **Edge cases:** All API failures are logged and return `null`/`false` — never throw to the webhook. If the bot is disabled, every method short-circuits.

### 3.3 Telegram account linking (auth side)
- **File:** `backend/app/Http/Controllers/Auth/SocialAuthController.php:120-167` (`handleTelegramCallback`, route `auth/telegram/callback` in `web.php:276`).
- **What it does:** validates Telegram login data, then: matches an existing user by `telegram_id`; else links by `email` (setting `telegram_id`, `telegram_username`, `provider=telegram`); else creates a new user with a synthesized `{id}@telegram.org` email if none provided (`:129-153`). This `telegram_id` is the key the bot service uses to attribute support chats to a known user.

---

## 4. SEO / SSR

Crawlers hit the Laravel app for HTML routes. Laravel serves the built SPA's `index.html` but **injects per-route meta tags, JSON-LD, hreflang, and bot-visible HTML content** before returning it. There is also a legacy Blade-based SSR controller set (`Seo\*Controller`) that renders dedicated SSR views, but the live web routes point at `SpaController`; the old `/seo/*` URLs are 301-redirected.

### 4.1 Primary SSR injector — `SpaController`
- **File:** `backend/app/Http/Controllers/Seo/SpaController.php`.
- **Routes (all GET, `backend/routes/web.php`):** `/` (`:257`), `/products/{id}` (`:239`, `id` = `.*`), `/articles` (`:248`), `/articles/{id}` (`:247`, numeric), `/categories` (`:250`), `/categories/{id}` (`:249`), service pages `/become-supplier`, `/suppliers`, `/conditions`, `/replace-conditions`, `/payment-refund`, `/contacts` (`:251-256`), and a catch-all `/{slug}` for dynamic pages excluding `api|admin|auth|supplier|storage|img|js|css|seo|sitemap.xml|robots.txt` (`:260-261`).
- **How it works (`index`, :19-62):**
  1. Locates `frontend/dist/index.html` (3 fallback paths, then 404) (`:21-34`).
  2. **Locale detection:** `?lang` → `locale` cookie → `Accept-Language` first 2 chars; whitelist `ru|en|uk`, default `ru` (`:38-41`).
  3. Computes per-route meta (`getMetaTagsForRoute`); if entity not found, returns **HTTP 404 with `noindex, follow`** and a generic 404 title/description (`:45-54`).
  4. Injects tags + content into the HTML (`injectMetaTags`) and returns `text/html; charset=utf-8` (`:56-61`).
- **Route → meta resolver (`getMetaTagsForRoute`, :64-147):** regex-matches `account|products/{id}`, `articles/{id}`, `categories/{slug}` (strips `/page/N` pagination, `:85-87`), `categories` list, home, `articles` list, info pages (`faq/guarantees/cookies/terms/privacy`), service aliases (`suppliers→become-supplier`, `replace-conditions→conditions`, etc.), then dynamic DB `Page`s. Anything unmatched → `status:404 + noindex,follow` (`:141-146`).
- **Per-entity meta (titles/descriptions, OG, canonical, JSON-LD, bot HTML):**
  - **Product** (`getProductMetaTags`, :149-238): finds active `ServiceAccount` by `id|sku|slug`; localized `title`/`description`/`meta_description`; canonical `/{products}/{slug|id}`; OG `type=product`, image fallback `/img/logo_trans.webp`; JSON-LD `@graph` of `Product` (with `Offer`, USD, `InStock`) + `BreadcrumbList`; injects full product HTML for bots.
  - **Article** (`getArticleMetaTags`, :258-339): published `Article` only; JSON-LD `Article` + `BreadcrumbList`; canonical `/articles/{id}`; injects article body HTML.
  - **Category** (`getCategoryMetaTags`, :369-443): by id or slug; auto-generates a localized description if missing/too short (`getCategoryDescription`, :492-520) and de-dups the word "accounts"; `BreadcrumbList`; injects a product grid for bots.
  - **Home** (`getHomeMetaTags`, :522-568): localized title/desc; `Organization` JSON-LD with Telegram `sameAs`/support `contactPoint`; injects categories + top-by-views products + recent articles (`generateHomeContent`, :573-651).
  - **Info pages** (`getInfoPageMetaTags`, :722-842): static localized title/desc; **FAQ page also emits `FAQPage` JSON-LD** (hard-coded Q&A per locale, :768-839).
  - **Service pages** (`getServicePageMetaTags`, :653-692) and **dynamic DB pages** (`getDynamicPageMetaTags`, :694-720).
- **Meta-description hygiene:** `sanitizeMetaDescription` strips URLs + emoji (`:243-256`); `smartTruncate` to 160 chars on word boundaries (`:983-999`).
- **Localization:** `getLocalizedField` uses base field for `ru`, `{field}_{locale}` for `en`/`uk` with fallback (`:974-978`).
- **Injection (`injectMetaTags`, :874-972):**
  - Sets `<html lang>` to the locale (`:877`); strips any pre-existing title/description/OG/twitter/canonical/hreflang/robots tags to avoid dupes (`:880-886`).
  - Emits `<title>`, `robots` (default `index, follow`), `description`, **canonical** (trailing slash removed; appends `?page=N` if present) (`:902-909`), OG + mirrored Twitter tags (`:912-919`).
  - **Hreflang:** builds `ru/en/uk` + `x-default` alternates off the **canonical** URL (not the request URL) for consistency (`:921-935`).
  - **JSON-LD:** schema injected as `<script type="application/ld+json">` (`:937-940`).
  - **Bot-visible content:** `html_content` injected inside `<div id="app">…</div>` so crawlers see text before Vue hydrates and replaces it (`:949-958`).
  - **Hidden H1:** injects `<h1 style="display:none">` after `<body>` except on the home page (home already has a visible H1 in `HeroSection.vue`) to avoid duplicate H1s (`:960-969`).

### 4.2 Legacy Blade SSR controllers (`Seo\*Controller`)
These render dedicated SSR Blade views (`view('seo.*')`) and are not bound to the live `web.php` routes (those use `SpaController`); they back the named routes (`seo.product`, `seo.article`, `seo.category`, etc.) and the old `/seo/*` URLs that now redirect.
- `ProductController@show` — `backend/app/Http/Controllers/Seo/ProductController.php:15-87`: active `ServiceAccount` by id, localized fields, hreflang alternates, `Product` JSON-LD with availability from `getAvailableStock()`, breadcrumbs; `spaUrl=/account/{id}`.
- `ArticleController@index/@show` — `.../Seo/ArticleController.php:16-140`: published articles; `ItemList` + `Article` JSON-LD; image-URL normalization to avoid double `/storage/` (`:201-224`).
- `CategoryController@show` — `.../Seo/CategoryController.php:23-112`: via `CategoryService::getCategoryForPublic`; `CollectionPage` JSON-LD; auto description for short/empty; breadcrumbs.
- `ServicePageController` — `.../Seo/ServicePageController.php`: slug map (`suppliers→become-supplier`, etc., :15-25), DB `Page` with i18n fallbacks (:66-88), `WebPage` + optional `FAQPage` JSON-LD (:139-156, :175-230). Public methods `suppliers()`, `replaceConditions()`, `paymentRefund()`.

### 4.3 Sitemap — `GET /sitemap.xml`
- **Route:** `backend/routes/web.php:235` → `Seo\SitemapController@index` (declared **before** the SPA catch-all so it isn't swallowed).
- **File:** `backend/app/Http/Controllers/Seo/SitemapController.php`.
- **What it does:** Builds `urlset` cached **24h** under `sitemap_xml` (`:20`). Includes: home (priority 1.0, daily), published articles (`/articles/{id}`, 0.8 weekly, lastmod), categories with at least one non-empty `name` translation (`/categories/{slug|id}`, 0.7 weekly) (`:47-63`), active products (`/products/{slug|id}`, 0.8 daily, lastmod) (`:66-77`), `/articles` list, and service pages (`become-supplier`, `conditions`, `payment-refund`, `contacts`) (`:84-93`). Each URL emits `xhtml:link` hreflang alternates for `ru/en/uk` via `?lang=` (`generateUrl`, :108-132).
- **Outputs:** `application/xml; charset=utf-8` + `X-Content-Type-Options: nosniff`.

### 4.4 301 redirects (URL canonicalization)
- **Old `/seo/*` → SPA** (`web.php:210-232`): `/seo/categories/{id}`, `/seo/articles`, `/seo/articles/{id}`, `/seo/products/{id}`, `/seo/suppliers`→`/become-supplier`, `/seo/replace-conditions`→`/conditions`, `/seo/payment-refund` — all `redirect(..., 301)`.
- **`/account/{id}` → `/products/{id}`** 301 (URL unification, `web.php:243-245`).
- **`/login` → `admin.login`** (`web.php:264-266`).

### 4.5 SEO/SSR middleware
- **`EnsureCanonicalUrl`** (`backend/app/Http/Middleware/EnsureCanonicalUrl.php`): for GET requests only, forces HTTPS in production and strips leading `www.`, issuing a **301** to the canonical host (`:18-47`).
- **`InjectSpaMetaTags`** (`backend/app/Http/Middleware/InjectSpaMetaTags.php`): an **alternative/older** meta-injector that runs as middleware on HTML responses for `account/{id}`, `articles/{id}`, `/`, `articles` (`isSpaRoute`, :48-57). It injects title/description/OG/Twitter/canonical (canonical points at `/seo/products/{id}` and `/seo/articles/{id}`, :133,182). Overlaps with `SpaController`; same-route logic differs (e.g. canonical target), a known inconsistency.
- **`SecurityHeaders`** (`backend/app/Http/Middleware/SecurityHeaders.php`): sets `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `X-XSS-Protection`, a full **CSP** (`buildCsp`, :54-72, allowlisting jsDelivr/CKEditor/jQuery/GTM/GA/Cloudflare, Monobank+Cryptomus connect-src), HSTS (prod+https), `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy` disabling geolocation/mic/camera.
- **`AddRequestId`** (`.../AddRequestId.php`): echoes/creates `X-Request-ID` (UUID) on request + response for log correlation.
- **`TrustProxies`** (`.../TrustProxies.php`): trusts X-Forwarded-* incl. AWS ELB header (proxies list is `null` → trust all in current config).
- **`TrustHosts`** (`.../TrustHosts.php`): trusts all subdomains of the app URL.

### 4.6 robots.txt
Two copies exist (served depending on web root):
- `backend/public/robots.txt`: `Allow: /`; disallows `/admin/ /api/ /supplier/ /vendor/ /storage/` and query patterns (`sort/filter/search/utm/ref/token/auth`); `Sitemap: https://account-arena.com/sitemap.xml`.
- `frontend/public/robots.txt`: similar allow/disallow plus **blocks AI crawlers** `GPTBot`, `ClaudeBot`, `CCBot` entirely; same sitemap line.
- The SPA catch-all route explicitly excludes `robots.txt` (and `sitemap.xml`) so the static file is served (`web.php:261`).

### 4.7 Frontend SEO composables & components
- **`useSeo.ts`** (`frontend/src/composables/useSeo.ts`): client-side meta manager. Computes title (appends `- Account Arena` unless already present), description (default fallback), `ogImage` (absolutized to `https://account-arena.com`), canonical (strips trailing slash + query). Writes `<title>`, description, robots (`noindex,nofollow` when `options.noindex`), OG (incl. `og:site_name`), Twitter `summary_large_image`, and canonical link; re-runs on mount, computed changes, and route change (`:163-178`).
- **`useHreflang.ts`**: injects `<link rel="alternate" hreflang>` for `ru/en/uk` + `x-default` against `https://account-arena.com{path}?lang=` (`:40-55`); re-injects on route/locale change; cleans up on unmount.
- **`useStructuredData.ts`**: injects one or more JSON-LD `<script>` tags from a reactive data getter; watches deep + immediate; removes prior scripts on change/unmount.
- **`useProductTitle.ts`**: localized field getter (`ru` = base, `uk`/`en` = `{field}_uk|_en` with fallback). `getProductDescription` also **rewrites links**: adds `rel="nofollow noopener noreferrer"` + `target="_blank"` to external `<a>` and auto-links bare external URLs (`:49-106`).
- **`FacebookPixel.vue`** (`frontend/src/components/FacebookPixel.vue`): reads `facebook_pixel_id` from the option store (renders nothing if unset). Loads the standard `fbevents.js` snippet, `fbq('init', id)` + `fbq('track','PageView')`, and fires a `PageView` on every route change (`:88-92`).
- **`CookieBanner.vue`** (`frontend/src/components/CookieBanner.vue`): on mount, if `localStorage.cookies_accepted` unset, calls `GET /cookie/check`; shows the banner only when the response `show_cookie_banner` is true. Backend `CookieConsentController@check` (`backend/app/Http/Controllers/CookieConsentController.php`) geolocates the IP (Cloudflare `CF-Connecting-IP` → GeoLite2) and returns `show_cookie_banner = country ∈ Option('cookie_countries')`. Accepting stores `cookies_accepted=true` and hides the banner.

### 4.8 `hreflang_fix.patch`
- **File:** `/Users/gospodin/Desktop/Account Arena/hreflang_fix.patch` (repo root, **not** under `backend/`).
- **What it is:** A git patch against `SpaController.php` that changes hreflang generation to use the **canonical URL** (instead of `request()->fullUrl()`) and normalizes the `?`/`&` separator logic, eliminating canonical↔hreflang inconsistency. This change is **already reflected** in the current `SpaController::injectMetaTags` (`:921-935`), so the patch documents/records the applied SEO-audit fix.

---

## 5. Maintenance / CLI (artisan) Commands

### 5.1 Task schedule (`backend/app/Console/Kernel.php`)
Cron registrations (`schedule()`, :13-40). Disabled subscription commands are commented out.

| Command | Frequency | Purpose |
|---|---|---|
| `suppliers:recalculate-ratings` | daily at 03:00 | Recompute supplier ratings |
| `suppliers:release-earnings` | every 5 minutes | Move supplier funds from hold to available balance |
| `notify:overdue-manual-orders` | hourly | Remind managers of overdue manual-delivery orders |
| `process:waiting-stock-orders` | every 30 minutes | Auto-process orders waiting for stock |
| `disputes:auto-close` | hourly | Auto-close inactive disputes |

> The five SEO/maintenance commands documented below (slugs, image-URL fixes, data fixes, diagnose) are **NOT scheduled** — they are run manually/ad-hoc.

### 5.2 In-domain commands (manual, unscheduled)

- **`seo:generate-slugs`** — `backend/app/Console/Commands/SeoGenerateSlugs.php`.
  - Signature `seo:generate-slugs` (`:14`); default stub description "Command description" (`:21`); no args.
  - Backfills NULL `slug` on `Category` (name→admin_name→`category-{id}`) and `ServiceAccount` (title→`product-{id}`), slugifying via `Str::slug` and ensuring uniqueness by appending `-1/-2/...` (excluding self). Idempotent (only NULL slugs touched). Prints each generated slug; "Done!" at end. N+1 uniqueness query per row.

- **`images:normalize-urls`** — `backend/app/Console/Commands/NormalizeImageUrls.php`.
  - Signature `images:normalize-urls` (`:13`); desc "Normalize all image URLs in categories, products, and banners to relative paths." (`:14`).
  - Converts absolute/`storage/`-prefixed `image_url` to **relative paths** across `Category`, `ServiceAccount`, `Banner` (`normalizeUrl`, :73-96). Saves only when value changes; prints per-row + per-type summary; `return 0`. **Inverse of** `categories:fix-image-urls`.

- **`categories:fix-image-urls`** — `backend/app/Console/Commands/FixCategoryImageUrls.php`.
  - Signature `categories:fix-image-urls` (`:16`); desc "Fix category image URLs - convert relative paths to absolute URLs" (`:23`).
  - For `Category` rows with non-empty `image_url` not starting with `http`, converts to absolute via `url()` and saves; skips already-absolute. Prints fixed/skipped summary; `return 0`. **Inverse of** `images:normalize-urls` for categories — running both toggles the data.

- **`service-accounts:fix-data`** — `backend/app/Console/Commands/FixServiceAccountsData.php`.
  - Signature `service-accounts:fix-data` (`:15`); desc "Fix service accounts with NULL or invalid accounts_data field" (`:22`).
  - Loads all `ServiceAccount`; repairs `accounts_data` (NULL or non-array → `[]`) and `used` (NULL → `0`); prints per-row issues + fixed/skipped totals; `return 0`. Idempotent; loads whole table into memory (no chunking).

- **`service-accounts:diagnose`** — `backend/app/Console/Commands/DiagnoseServiceAccounts.php`.
  - Signature `service-accounts:diagnose` (`:15`); desc "Diagnose why service accounts are not showing on frontend" (`:22`).
  - **Read-only.** Prints an 8-column table (ID, Title, Active, Price, Total Qty, Sold, Available, Status) flagging why a product is hidden: `NOT ACTIVE`, `NO TITLE`, `NO PRICE`, `INVALID accounts_data`, `NO STOCK`. `availableCount = max(0, count(accounts_data) - used)`. Encodes the frontend visibility contract (active + titled + priced + in stock). `return 0`.

---

## Cross-cutting notes & observations

- **Browser launch:** the *live* path is the `browser_api` proxy (`BrowserController` + inline `/browser/*` routes), not the Kasm package. Credentials live in upstream profiles (`profile_id`); Laravel forwards only the profile id, target URL, lang, and an optional `#sc_pair=sc_u_{userId}` fragment. `/browser/*` routes are **unauthenticated**.
- **Extension auth** hinges on the unencrypted, `extension`-ability `sc_auth` cookie minted at login/register.
- **Telegram** webhook is a support-chat ingress with no signature verification; account attribution is via `telegram_id` set during Telegram OAuth login.
- **SEO** has two overlapping injectors (`SpaController` and the `InjectSpaMetaTags` middleware) with differing canonical targets — a known inconsistency. `hreflang_fix.patch` (already applied) aligned hreflang with canonical in `SpaController`.
- **The five SEO/maintenance artisan commands are not on cron**; `images:normalize-urls` and `categories:fix-image-urls` are mutually inverse and should not both be run.
