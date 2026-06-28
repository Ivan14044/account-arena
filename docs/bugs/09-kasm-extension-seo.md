# 09 — KASM / Browser-Launch, Extension, Telegram, SEO/SSR, CLI — Security & Correctness Audit

Adversarial audit of the browser-launch proxy, browser extension auth, Telegram bot webhook, SEO/SSR injection, SEO middleware, and maintenance CLI commands. Scope and entrypoints per `docs/functionality/09-kasm-extension-seo.md`. Paths absolute; references are `path:line`.

**No source was modified.**

---

## Summary table

| # | Severity | Confidence | Title | Location |
|---|----------|-----------|-------|----------|
| 1 | **Critical** | High | Browser-control endpoints fully unauthenticated — anyone can stop/list ALL users' sessions and launch sessions | `backend/routes/api.php:135-159`, `BrowserController.php` |
| 2 | **High** | High | No purchase/ownership check before launching an account profile; arbitrary `profile_id` IDOR | `BrowserController.php:19-49` |
| 3 | **High** | High | Telegram webhook has no signature/secret verification — anyone can POST forged updates (impersonation, fake support chats, attribution to victims) | `TelegramWebhookController.php:14-72` |
| 4 | **High** | High | Stored XSS via unescaped product `description` injected into SSR HTML (`generateProductContent`) | `Seo/SpaController.php:1037` (+ `:706/:1185` pages, `:322/:1111` articles) |
| 5 | **High** | High | Stored XSS in legacy Blade SSR: `{!! $seoText !!}` outputs raw article content | `resources/views/seo/article.blade.php:66` |
| 6 | **Medium** | High | JSON-LD `</script>` breakout in SSR schema (SpaController + all `seo.*` Blade views) | `SpaController.php:939`; `seo/*.blade.php:6` |
| 7 | **Medium** | High | `sc_auth` extension token cookie delivered unencrypted; token never invalidated/rotated, long TTL | `EncryptCookies.php:14-16`, `AuthController.php:22-39` |
| 8 | **Medium** | Med | `TrustProxies::$proxies` is `null` → trusts X-Forwarded-* from ANY source (IP spoofing, host/proto poisoning) | `TrustProxies.php:15` |
| 9 | **Medium** | Med | `getApiUser` pairing accepts any valid Sanctum token (no `extension` ability scoping) for `sc_pair` injection | `Controller.php:14-27`, `BrowserController.php:40-43` |
| 10 | **Low** | High | SSRF-adjacent: `app_url` (client-controlled) forwarded to internal browser service; weak URL validation | `BrowserController.php:17-49` |
| 11 | **Low** | Med | `sanitizeHtml` (admin/article/page) bypassable: unquoted event handlers and `<svg>`/`srcdoc` not stripped | `Controller.php:33-55`, `Admin/ArticleController.php:140-156` |
| 12 | **Low** | High | `InjectSpaMetaTags` middleware `<title>` injected WITHOUT escaping | `InjectSpaMetaTags.php:246` |
| 13 | **Info** | High | `useServiceLauncher` client-side "anti-leak/devtools" hardening is security theater (trivially bypassed) | `frontend/src/composables/useServiceLauncher.ts:69-97` |

Counts: **1 Critical, 4 High, 4 Medium, 3 Low, 1 Info** (13 findings).

---

## BUG-01 — Browser-control endpoints are completely unauthenticated (Critical)

**Location:** `backend/routes/api.php:135-159`; `backend/app/Http/Controllers/Api/BrowserController.php`

**Confidence:** High (confirmed by route placement)

The four `/browser/*` routes are declared **outside** every auth middleware group (the `auth:sanctum` groups end at `api.php:122`; these routes sit at 135-159 with no middleware, not even `throttle`):

```php
Route::get('/browser/new', [BrowserController::class, 'new']);            // 135
Route::post('/browser/stop', function (Request $request) { ... });         // 137
Route::post('/browser/stop_all', function (Request $request) { ... });     // 145
Route::get('/browser/list', function () { ... });                          // 153
```

`/browser/list` proxies `GET {browser_api.url}/list` and returns the raw upstream body — i.e. it enumerates **every active streamed session of every user** (pids/ports/profiles). `/browser/stop` and `/browser/stop_all` proxy the client-supplied body straight to the upstream `/stop` and `/stop_all`. Because the body is forwarded verbatim (`$request->all()`), an unauthenticated attacker can:

- `POST /browser/stop {"pid": <any pid from /list>}` → kill any other user's live session.
- `POST /browser/stop_all {"clean": true}` → terminate (and "clean") **all** sessions platform-wide — a one-request DoS against everyone's launched accounts.
- `GET /browser/list` → reconnaissance of all active sessions.

There is no per-user filtering at the Laravel layer; the upstream service is treated as a trusted enumerable/mutable global pool, and Laravel exposes it to the open internet with zero auth.

**Impact:** Unauthenticated, cross-tenant session termination / mass DoS / enumeration. Highest-severity item in this domain.

**Fix direction:** Put all `/browser/*` routes behind `auth:sanctum`; scope stop/list operations to sessions owned by the authenticated user (track session→user mapping server-side, e.g. via the `sc_pair`/profile association), and never forward a raw `pid`/`port` the user does not own.

---

## BUG-02 — No purchase/ownership check before launching a profile; `profile_id` IDOR (High)

**Location:** `backend/app/Http/Controllers/Api/BrowserController.php:19-49`

**Confidence:** High

`new()` selects the profile to launch as follows:

```php
if ($request->has('profile')) {
    $profile = $request->profile;            // 20 — caller-supplied, trusted verbatim
} else {
    $account = ServiceAccount::where('is_active', true)
        ->where(fn($q) => $q->whereNull('expiring_at')->orWhere('expiring_at','>',now()))
        ->orderBy('id','asc')->first();      // 23-28 — FIRST active account in the table
    $profile = $account->profile_id ?? null; // 29
}
```

There is **no check that the requesting user purchased the account** whose `profile_id` is launched. Two distinct problems:

1. **Arbitrary `profile_id` injection (IDOR):** any caller can pass `?profile=<another account's profile_id>` and the controller forwards it to the upstream `/new` (line 47). The upstream profile holds the account's real credentials (cookies/session). So any user — authenticated or not (see BUG-01) — can launch a streamed browser already logged into an account they never bought, simply by guessing/learning a `profile_id`.
2. **Default auto-pick leaks the first account:** with no `profile` param, the controller launches `profile_id` of the lowest-`id` active `ServiceAccount` — i.e. it hands the first in-stock account's live session to whoever calls the endpoint, again with no purchase gate.

Combined with BUG-01 (unauthenticated), this means the "credentials never leave the server" design goal is defeated: a buyer is not required at all, and ownership is never verified.

**Impact:** Account-takeover-grade credential access without purchase; cross-user IDOR on `profile_id`.

**Fix direction:** Require auth; resolve `profile_id` server-side from a `Purchase`/order record owned by the authenticated user; reject any client-supplied `profile` that is not tied to that user's purchase.

---

## BUG-03 — Telegram webhook accepts forged updates (no signature/secret) (High)

**Location:** `backend/app/Http/Controllers/TelegramWebhookController.php:14-72`; route `backend/routes/api.php:166`

**Confidence:** High

`POST /telegram/webhook` runs no signature check. Telegram's only anti-forgery mechanism for webhooks is (a) the secret-bot-token URL path or (b) the `X-Telegram-Bot-Api-Secret-Token` header set via `setWebhook(..., secret_token=...)`. Neither is used: the route is a fixed, guessable path `/telegram/webhook`, and `setWebhook` is called with only `['url' => $webhookUrl]` (`TelegramBotService.php:292`) — no `secret_token`. The handler trusts `$request->all()` entirely.

Because `processIncomingMessage` attributes a chat to a real user via `User::where('telegram_id', $from['id'])` (`TelegramBotService.php:376`), an attacker who knows or guesses a victim's `telegram_id` can:

- Forge inbound updates that create/append `SupportChat`/`SupportMessage` rows **attributed to the victim's user account** (`sender_type = SENDER_USER`), polluting support history and impersonating the victim to support agents.
- Create arbitrary guest chats / spam, and trigger attachment downloads (`getFile` → server-side fetch of attacker-chosen Telegram file paths, written to the public disk — see note below).
- Trigger `/start` greeting sends to arbitrary `chat_id`s (outbound message amplification via the bot).

The handler always returns `{ok:true}` and swallows exceptions, so abuse is silent.

> Note: `telegram_id` itself cannot be spoofed at *login* (the OAuth callback is correctly HMAC-verified, `SocialAuthController::validateTelegramData:209` uses `hash_hmac` + `hash_equals`). The webhook is the unverified path.

**Impact:** Unauthenticated support-chat injection, user impersonation in support context, outbound-message and server-fetch amplification.

**Fix direction:** Configure `setWebhook` with a random `secret_token` and verify the `X-Telegram-Bot-Api-Secret-Token` header in `handle()` (reject mismatches before processing); optionally also IP-allowlist Telegram CIDRs.

---

## BUG-04 — Stored XSS via unescaped product `description` in SSR injection (High)

**Location:** `backend/app/Http/Controllers/Seo/SpaController.php:1037` (product); same pattern at `:706`+`:1185` (dynamic Page content), `:322`+`:1111` (article content)

**Confidence:** High

`injectMetaTags` escapes title/description/OG/canonical with `htmlspecialchars`, but the **bot-visible HTML body** (`html_content`) is built by helpers that interpolate raw DB content. The clearest sink:

```php
// generateProductContent(), SpaController.php:1036-1038
if ($description) {
    $html .= '<div itemprop="description" style="line-height: 1.6;">' . $description . '</div>';
}
```

`$description` is `$rawDesc = $this->getLocalizedField($product, 'description', $locale)` (`:164`, passed at `:223`) — the raw `ServiceAccount.description` field, injected with **no escaping and no sanitization**. The result is written into `<div id="app">…</div>` and returned as `text/html` for every visitor of `/products/{id}` (and `/account/{id}` via the alias regex `(account|products)/(.+)` at `:69`).

Critically, the product `description` is **never run through `sanitizeHtml`** on any write path:
- Admin `ServiceAccountController::store/update` validates `description` as `nullable|string` and saves it raw (no `sanitizeHtml` call; grep shows `sanitizeHtml` is only wired into `Admin/ArticleController` and `Admin/PageController`).
- Supplier `ProductController::store/storeBulkAccounts` (`:107,:144`) also stores `description` raw (`nullable|string`). Supplier products require admin moderation/activation (`is_active=false`, `moderation_status=pending`), so the supplier vector requires approval — but an approving admin publishes the payload, and the admin self-authored path needs no approval at all.

So a payload like `<img src=x onerror=alert(document.cookie)>` (or a token-exfil script) stored in any product's `description` executes in every visitor's browser at SSR time, before Vue hydrates. The CSP (`SecurityHeaders.php:61`) allows `'unsafe-inline'` for `script-src`, so inline-handler XSS is **not** blocked by CSP.

The dynamic-Page path (`generatePageContent`, `:1185`: `'<div class="description"...>' . $description . '</div>'` where `$description` = `Page::translate('content')`) and article path (`generateArticleContent`, `:1111`: `'<div itemprop="articleBody"...>' . $content . '</div>'`) inject raw content too; those are admin-`sanitizeHtml`-filtered at write, but see BUG-11 for that filter's gaps.

**Impact:** Stored XSS executed for all visitors/crawlers of product pages; CSP does not mitigate (unsafe-inline). Session/token theft, account takeover.

**Fix direction:** Run `description`/`content` through a real HTML sanitizer (HTMLPurifier) before injecting, or `htmlspecialchars` it if plain text is acceptable; apply `sanitizeHtml` on the product write path as is already done for articles/pages.

---

## BUG-05 — Stored XSS in legacy `seo.article` Blade view (High)

**Location:** `backend/resources/views/seo/article.blade.php:66` (`{!! $seoText !!}`), driven by `Seo/ArticleController.php`

**Confidence:** High (verified by sub-audit of the Blade views)

The dedicated SSR Article view outputs article content fully unescaped:

```blade
{!! $seoText !!}   {{-- $seoText = $article->translate('content', $locale), raw --}}
```

No `e()`, no sanitizer at the view layer. While admin article writes pass through `sanitizeHtml`, that filter is insufficient (BUG-11) — it keeps `<img>`/`<svg>`-class tags and only strips *quoted* `on*=` handlers, so `<img src=x onerror=alert(1)>` survives sanitization and then renders raw here. `service-page.blade.php:21` (`{!! $content !!}` = `Page::translate('content')`) is the same class (admin-only authorship).

These views back the named `seo.*` routes; even though `/seo/*` URLs now 301-redirect to the SPA, the controllers/views remain reachable wherever the named routes are still invoked and are a live XSS sink.

**Impact:** Stored XSS via article/page body content; compounds BUG-11.

**Fix direction:** Sanitize at render with a strict allowlist (HTMLPurifier) rather than relying on the weak write-time filter; never `{!! !!}` DB content without purification.

---

## BUG-06 — JSON-LD `</script>` breakout in SSR schema (Medium)

**Location:** `backend/app/Http/Controllers/Seo/SpaController.php:939`; `backend/resources/views/seo/*.blade.php:6` (product/category/article/articles/service-page)

**Confidence:** High

Both injectors emit JSON-LD with `JSON_UNESCAPED_SLASHES` and **without** `JSON_HEX_TAG`:

```php
// SpaController.php:939
$headTags[] = '<script type="application/ld+json">'
    . json_encode($metaTags['schema'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    . '</script>';
```

The schema arrays embed DB-controlled values un-encoded for HTML context: product `name`/`description`/`sku` (`:200-208`), article `headline`/`description` (`:272-273`), category breadcrumb `name` (`:424`). A stored value containing the literal string `</script><script>alert(1)</script>` is emitted verbatim inside the `<script>` element and terminates the JSON-LD block, executing attacker script. `JSON_UNESCAPED_SLASHES` makes `</script>` pass through unescaped; the breakout works because `JSON_HEX_TAG`/`JSON_HEX_AMP`/`JSON_HEX_APOS`/`JSON_HEX_QUOT` are not set. The Blade `seo.*` views have the identical `{!! json_encode(..., JSON_UNESCAPED_SLASHES ...) !!}` pattern at line 6 of each.

**Impact:** Second independent stored-XSS channel through product/category/article titles, descriptions and SKUs. Same controllability as BUG-04/05.

**Fix direction:** `json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)` (drop `JSON_UNESCAPED_SLASHES`) for every JSON-LD `<script>` block.

---

## BUG-07 — `sc_auth` extension token: unencrypted cookie, no rotation/revocation, long TTL (Medium)

**Location:** `backend/app/Http/Middleware/EncryptCookies.php:14-16`; `backend/app/Http/Controllers/Auth/AuthController.php:22-39,93,151`; Sanctum `expiration => 1440`

**Confidence:** High

`sc_auth` is in the `EncryptCookies::$except` list, so the **raw Sanctum PAT plaintext** is stored in the browser cookie (by design, so the extension can read it). Consequences:

- The cookie value is a directly-usable bearer credential. Any XSS that can read cookies, or any cookie-leak path, yields a full extension-scoped token. (It is `httpOnly`, which blocks `document.cookie` reads — but the extension itself and any non-httpOnly leak/logging path expose it; and `ExtensionAuth.php:19` logs cookie *names* on failure, while other branches log token prefixes `:69`.)
- Every login/register **mints a brand-new** `createToken('extension', ['extension'])` (`AuthController.php:93,151`) and never deletes prior extension tokens. Old tokens are never revoked, so tokens accumulate and a leaked one stays valid until it independently expires.
- TTL: cookie is 7 days (`buildAuthCookie` default `60*24*7`), Sanctum `expiration` is 1440 min (24h). The mismatch means the cookie advertises a 7-day token while Sanctum may expire it at 24h — but there is no server-side per-token expiry stored on the extension token, and logout (`AuthController::logout`) is not shown to revoke the extension token specifically.

**Impact:** Long-lived, accumulating, plaintext-transported bearer tokens with weak lifecycle; elevates the blast radius of any XSS (BUG-04/05/06) or cookie leak.

**Fix direction:** Revoke prior `extension` tokens on new issuance; set an explicit `expires_at` on the extension PAT; ensure logout deletes it; keep `Secure`/`SameSite` strict in prod (currently `SameSite=none` is required for the cross-site extension but widens CSRF surface — pair with the ability check).

---

## BUG-08 — `TrustProxies::$proxies` defaults to `null` → all X-Forwarded-* trusted (Medium)

**Location:** `backend/app/Http/Middleware/TrustProxies.php:15`

**Confidence:** Medium (depends on edge topology)

```php
protected $proxies;   // never assigned → null → trust ALL proxies
```

With `$proxies = null` and `$headers` enabling `X_FORWARDED_FOR|HOST|PORT|PROTO|AWS_ELB`, Laravel trusts forwarding headers from **any** client. If the app is reachable directly (not exclusively behind a locked-down LB), an attacker can:

- Spoof `X-Forwarded-For` → defeats IP-based logic: `RateLimiter` keys/`$request->ip()` (e.g. `AuthController::forgotPassword:64` clears the limiter per spoofable IP), and the cookie-consent geolocation (`CF-Connecting-IP`/IP → GeoLite2).
- Spoof `X-Forwarded-Host`/`X-Forwarded-Proto` → host/proto poisoning feeding `url()`/`request()->getHost()` used throughout SEO (canonical/hreflang generation, `EnsureCanonicalUrl` redirect target `:42`), enabling cache-poisoning of canonical/hreflang and potential redirect-host manipulation.

**Impact:** IP-spoofing (rate-limit bypass, geo bypass), host-header poisoning of SEO canonical/redirect URLs.

**Fix direction:** Set `$proxies` to the known LB/CDN CIDR(s) (or `'*'` only if the only ingress is a trusted proxy that strips client-supplied forwarding headers).

---

## BUG-09 — Pairing token not ability-scoped; any Sanctum token sets `sc_pair` (Medium)

**Location:** `backend/app/Http/Controllers/Controller.php:14-27`; `BrowserController.php:40-43`

**Confidence:** Medium

`getApiUser` resolves a user from ANY valid Sanctum PAT via `PersonalAccessToken::findToken($token)` with **no `->can('extension')` ability check** (contrast `ExtensionAuth.php:44`). `/browser/new` then appends `#sc_pair=sc_u_{user->id}` to the streamed-browser URL based on this unscoped token. Combined with the route being unauthenticated (BUG-01), the bearer is optional and unvalidated for scope: a low-privilege/SPA token (or a token of a different user, if obtained) can pair a launched streamed session to an arbitrary `user->id` derived solely from token ownership. The `sc_pair` fragment is the association the remote browser/extension uses, so mis-scoping it can cross-link sessions to the wrong user identity.

**Impact:** Session-pairing performed with an over-broad token class; weakens the user↔session binding the extension relies on.

**Fix direction:** Require and validate the `extension` ability in `getApiUser` for pairing, or derive pairing from the authenticated route user only.

---

## BUG-10 — `app_url` forwarded to internal browser service; weak validation (Low / SSRF-adjacent)

**Location:** `backend/app/Http/Controllers/Api/BrowserController.php:17-49`

**Confidence:** Low (impact bounded by upstream behavior)

`app_url` is client-controlled and only normalized (`https://` prepended, `FILTER_VALIDATE_URL` fallback to `google.com`). It is then passed as `app` to `GET {browser_api.url}/new` (`:45-49`). `FILTER_VALIDATE_URL` accepts `http://169.254.169.254/…`, `http://localhost/…`, `file://`-less but arbitrary internal hosts. The actual fetch is performed by the **upstream** browser service inside its own network, so whether this reaches cloud metadata/internal services depends on that service's egress controls — but Laravel imposes no allowlist on the destination the remote browser will navigate to. The Laravel→upstream call itself targets a fixed `BROWSER_API_URL`, so the SSRF surface is "what URL the streamed browser opens," driven entirely by the attacker.

**Impact:** Attacker fully controls the URL the server-side streamed browser navigates to (no scheme/host allowlist); potential internal-network reconnaissance via the browser node.

**Fix direction:** Allowlist permitted destination hosts/schemes for `app_url`; block RFC1918/link-local/loopback targets at the proxy.

---

## BUG-11 — `sanitizeHtml` allowlist is bypassable (Low)

**Location:** `backend/app/Http/Controllers/Controller.php:33-55`; `backend/app/Http/Controllers/Admin/ArticleController.php:140-156`; `Admin/PageController.php`

**Confidence:** Medium

The shared sanitizer keeps dangerous tags and uses regex-only attribute stripping:

- Allowlist includes `<img>`, `<a>`, `<div>`, `<span>`, `<figure>`, `<table>` (and base `Controller` adds the same), so `<img src=x onerror=...>` tags survive `strip_tags`.
- Attribute filter `preg_replace('/on\w+\s*=\s*".*?"/i', '', ...)` (and the `'...'` variant) strips only **quoted** handlers. `<img src=x onerror=alert(1)>` (unquoted) is **not** matched and passes through. The base-Controller variant `preg_replace('/on\w+\s*=/i', 'data-removed=', ...)` is stronger but still does not neutralize `<svg>`/`<math>` payloads or `srcdoc`/`<a href="data:…">` vectors, and `<svg>` is not in the allowlist for the base controller but `<img>` is.
- `javascript:` removal is naive (`/javascript\s*:/i`) and can be evaded with entities/whitespace tricks (`java\tscript:`, HTML entities).

These payloads then render via `{!! ... !!}` (BUG-05) or raw injection (BUG-04) without further escaping.

**Impact:** Admin-authored (and approved-supplier) content can carry XSS past the filter; mainly an insider/compromised-admin or moderation-bypass vector, hence Low.

**Fix direction:** Replace regex sanitization with HTMLPurifier; drop `<img>` `on*`/`srcdoc` and restrict `href`/`src` schemes via a real parser.

---

## BUG-12 — `InjectSpaMetaTags` injects `<title>` without escaping (Low)

**Location:** `backend/app/Http/Middleware/InjectSpaMetaTags.php:246`

**Confidence:** High

In the alternative/older meta middleware, every meta tag is `htmlspecialchars`-escaped EXCEPT the title:

```php
$tags[] = "<title>{$metaTags['title']}</title>";   // 246 — no htmlspecialchars
```

`title` = `($metaTitle ?: $title) . ' - ' . config('app.name')` where `$metaTitle`/`$title` are DB-controlled product/article fields (`:121,:136`). A `</title><script>…` payload in a product/article title breaks out of the title element. Whether this middleware is active depends on it being registered on HTML routes (it overlaps `SpaController`); the live web routes use `SpaController` (which DOES escape its title at `:892`), so exposure is conditional on this middleware being in the stack — hence Low, but it is a real unescaped sink.

**Impact:** Conditional reflected/stored XSS via title element breakout if this middleware runs.

**Fix direction:** `htmlspecialchars($metaTags['title'], ENT_QUOTES, 'UTF-8')` like every other tag in the same method.

---

## BUG-13 — Client-side launcher "hardening" is security theater (Info)

**Location:** `frontend/src/composables/useServiceLauncher.ts:69-97`

**Confidence:** High

`launchService` injects a CSP `<meta>`, a `document.cookie` getter/setter stub, a 1s devtools-detection loop that navigates to `about:blank`, and key/copy/contextmenu blockers — all **inside the opened streamed-browser window from the parent page**. This only works when the popup is same-origin (the code explicitly catches and ignores cross-origin failures, `:93-96`), and even then every control is trivially bypassable by the end user (disable JS, attach devtools before load, read network traffic, etc.). It provides no real protection of the streamed credentials and may give a false sense of security. The genuine protection (credentials staying server-side) lives in the upstream profile model, which BUG-01/02 undermine.

**Impact:** None positive; misleading defense. Do not rely on it for credential protection.

---

## Ruled out (looked safe / not exploitable as feared)

- **Kasm client package (`KasmClient.php`, `kasm.php`):** credentials read from env config and merged server-side into the payload; `base_url` is config-driven (not client-controlled). The package is **not wired into the live launch path** (no controller calls it). No injection. Only minor robustness gaps (no timeout, null-on-failure) — non-security. Safe.
- **`SpaController` title/description/OG/canonical:** all `htmlspecialchars`-escaped (`:892,899,908,914`). Hreflang built off the canonical (post-`hreflang_fix.patch`), not raw `fullUrl()`. The `lang` param is whitelisted to `ru|en|uk` (`:39`). Canonical/hreflang values are server-built `url()` strings, not reflected user input → no meta-injection via these. Safe (the XSS is in the un-escaped `html_content`/JSON-LD body, BUG-04/06).
- **SPA catch-all slug regex** `^(?!api|admin|auth|supplier|storage|img|js|css|seo|sitemap\.xml|robots\.txt).*$` (`web.php:261`): negative-lookahead correctly prevents the catch-all from shadowing `api`/`admin`/etc. Unmatched slugs resolve to a DB `Page` lookup or a 404 with `noindex` — no route/path bypass to admin/api. Safe.
- **301 redirect routes** (`web.php:210-245`): all redirect to **hardcoded** relative paths (`/products/`, `/categories/`, `/become-supplier`, `admin.login`) with the `{id}`/`{slug}` only appended as a path segment, not used as the redirect host/scheme. No open redirect. Safe.
- **`SitemapController`:** all URLs `htmlspecialchars(..., ENT_XML1)` before `<loc>`/`href`; freq/priority/lastmod are server constants; 24h cache; correct content-type + `nosniff`. No injection/SSRF. Safe.
- **`EnsureCanonicalUrl`:** redirect URL built from `getHost()`/`getScheme()`/`getRequestUri()`; host-poisoning risk is via TrustProxies (BUG-08), not this middleware's logic. The redirect target host is the (possibly spoofed) request host — flagged under BUG-08, not double-counted.
- **`SecurityHeaders` CSP:** present and reasonably scoped, BUT `script-src` includes `'unsafe-inline'` and `'unsafe-eval'`, so it does **not** mitigate the inline-handler XSS in BUG-04/12 or the JSON-LD breakout in BUG-06. Noted as a mitigating-control gap rather than a standalone bug. `frame-ancestors 'self'` + `X-Frame-Options: SAMEORIGIN` give consistent clickjacking protection. HSTS gated on prod+https. Acceptable but `unsafe-inline` is the weak link.
- **`TrustHosts`:** trusts only subdomains of the app URL (`allSubdomainsOfApplicationUrl`). It is also **commented out** of the global stack (`Kernel.php:17`), so it is inactive; host validation effectively relies on the web server. Not a finding by itself (interacts with BUG-08).
- **Telegram OAuth login (`SocialAuthController::validateTelegramData`):** correct HMAC-SHA256 with `hash_equals` + 24h freshness. `telegram_id` cannot be forged at login. Safe (the unverified path is the webhook, BUG-03).
- **`TelegramBotService` attachment download:** server fetches `https://api.telegram.org/file/bot{token}/{file_path}` where `file_path` comes from Telegram's `getFile` response. Host is fixed to `api.telegram.org`; only the path varies. Stored on the `public` disk under a `uniqid()`-prefixed name. Not arbitrary-host SSRF; filename uses original `file_name` but path is namespaced and prefixed — low traversal risk (Telegram-supplied `file_path`/`file_name`). Flagged only as part of BUG-03's amplification, not separately.
- **Extension `saveSettings`:** validates `settings` as `required|array`; stored to `extension_settings` (a JSON column) and only echoed back to the same user / consumed as `uiLanguage`. No reflected sink with escaping bypass found. `authStatus` returns the user behind `ext.auth`. Safe.
- **CLI commands** (`SeoGenerateSlugs`, `NormalizeImageUrls`, `FixCategoryImageUrls`, `FixServiceAccountsData`, `DiagnoseServiceAccounts`): all use Eloquent (no raw SQL/shell), no external input (no args), no destructive truncation/deletion. `Str::slug` + uniqueness loop is safe. `normalize`↔`fix-image-urls` are mutually inverse (documented operational footgun, not a security bug). `diagnose` is read-only. Idempotency/no-chunking are perf notes only. Safe — no injection or destructive effect.
- **`ExtensionAuth` PAT branch:** correctly enforces `$pat->can('extension')` → 403 otherwise (`:44`). The JWT branch is commented out (no bypass). The session-guard fallback (`:63`) only triggers on an existing authenticated session. Token sanitization is cosmetic but not exploitable. Safe (the weakness is cookie lifecycle, BUG-07, not the middleware check).
