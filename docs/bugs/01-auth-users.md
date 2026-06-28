# Security & Correctness Audit: Authentication, Users, Profile & Access Control

Target: **Account Arena** (Laravel backend + Vue 3 / Pinia frontend)
Domain: Authentication, Users, Profile, Access Control
Auditor pass: adversarial code review of the actual code paths (no code modified).

Findings are ordered by severity. Each is traced through the real code and assigned a confidence level.

---

### [Critical] Social-login account takeover via attacker-controlled email (Google + Telegram link-by-email)

- **Category:** Authentication bypass / Account takeover
- **Confidence:** High
- **Location:** `backend/app/Http/Controllers/Auth/SocialAuthController.php:56-66` (Google), `:129-158` (Telegram)

**Description**
Both social-login flows link a social identity to an existing local account **purely by matching the email address**, with no verification that the local account actually belongs to the social identity, and no requirement that the email is verified on the social side.

**Code path / why it's a bug**
- Google (`handleGoogleCallback`, lines 56-66): `User::where('email', $googleUser->getEmail())->first()`. If a local user exists with that email, the code attaches `google_id`/`provider='google'` to it (when `google_id` is empty) and then `Auth::login($user)` + mints a full-ability `auth_token`. Google's `email_verified` claim is never checked. With a Google Workspace domain or any IdP where an attacker can set an arbitrary unverified email on a Google account, the attacker logs in **as the victim local user**.
- Telegram (`handleTelegramCallback`, lines 129-153): the `email` field comes straight from the **client-supplied** widget payload (`$telegramData['email']`). The HMAC in `validateTelegramData` only proves the payload came from the Telegram bot widget for *that Telegram account* — it does **not** prove ownership of the email. If `email` matches an existing user, lines 137-142 link the attacker's `telegram_id` onto the victim account and return a token for it. The Telegram login widget does not even normally send `email`, so this field is effectively attacker-injected.

For Telegram the takeover is unconditional and requires only a valid Telegram account: sign in via Telegram with `email = victim@example.com` in the POST body; the backend binds your `telegram_id` to the victim and issues you a valid bearer token.

**Impact**
Full account takeover of any local user (including admins/suppliers, since role columns are on the same `users` row) by anyone who can present a social payload carrying the victim's email. The minted `auth_token` has `['*']` abilities and inherits all of the victim's roles, balance, and data.

**Suggested fix**
- Never auto-link a social identity to a pre-existing local account by email alone. Either (a) require the email to be verified by the provider (`email_verified`) **and** require an explicit "link account" step where the already-authenticated local user confirms the link, or (b) only ever match/create by the provider's stable id (`google_id`/`telegram_id`).
- For Telegram, ignore any client-supplied `email` entirely; derive identity solely from `telegram_id`.
- For Google, check the verified-email claim (`$googleUser->user['email_verified'] === true`) before any linking.

---

### [Critical] CSRF protection disabled for the entire application (`$except = ['*']`)

- **Category:** CSRF
- **Confidence:** High
- **Location:** `backend/app/Http/Middleware/VerifyCsrfToken.php:14-16`

**Description**
`VerifyCsrfToken::$except = ['*']` exempts **every** URI from CSRF verification. The `web` middleware group still lists `VerifyCsrfToken`, but it now no-ops for all paths.

**Code path / why it's a bug**
All session-authenticated, server-rendered surfaces run through the `web` group (`Kernel.php:33-41`): the admin panel (`/admin/*`), supplier panel (`/supplier/*`), admin login (`POST /admin/login`), and the Google OAuth callback. Because CSRF is globally disabled, none of these state-changing POST endpoints validate the CSRF token. Session auth (cookies) is automatically attached by the browser on cross-site form posts, so a malicious page can drive any admin/supplier action while the victim is logged in.

This is far more dangerous than the typical "exclude the stateless API" pattern, because the admin/supplier panels are **session-cookie authenticated** and thus the canonical CSRF targets.

**Impact**
Cross-site request forgery against every authenticated session endpoint — e.g. an admin visiting an attacker page can be forced to block/unblock users, change roles, adjust balances, approve withdrawals, etc., via `UserService::updateUser` and other admin controllers, with no token check.

**Suggested fix**
Remove the global `'*'` exemption. Exclude only the genuinely stateless, token-authenticated endpoints (and only if they cannot be reached with cookies). The API routes are already token-only and live under the `api` group (no `VerifyCsrfToken`), so the `web` group should keep full CSRF protection: set `$except = []` (or only the specific webhook/callback paths that require it).

---

### [Critical] Wildcard CORS with credentials (`allowed_origins: ['*']` + `supports_credentials: true`)

- **Category:** Insecure CORS / cross-origin credential theft
- **Confidence:** High
- **Location:** `backend/config/cors.php:18-26`

**Description**
CORS is configured with `paths: ['*']`, `allowed_origins: ['*']`, `allowed_headers: ['*']`, and `supports_credentials: true`.

**Code path / why it's a bug**
`supports_credentials: true` combined with `allowed_origins: ['*']` is an explicitly invalid/maximally-permissive combination. Laravel's CORS layer, when credentials are supported, reflects the request `Origin` back in `Access-Control-Allow-Origin` and adds `Access-Control-Allow-Credentials: true`. This means **any** website can make credentialed cross-origin requests to the API and read the responses. Although the SPA uses a bearer token in `localStorage` (not a cookie) for the main API, the `sc_auth` extension cookie and the admin/supplier session cookies are sent on credentialed cross-origin requests, and the responses (including JSON containing the full user object, tokens issued by social callbacks, balances, etc.) are readable cross-origin.

**Impact**
Any origin can invoke the API with the victim's cookies and read the responses, enabling cross-origin data exfiltration and, combined with the disabled CSRF protection above, cross-origin state changes against session-authenticated panels.

**Suggested fix**
Replace `allowed_origins: ['*']` with an explicit allow-list of the SPA/admin origins (e.g. `https://account-arena.com`, the extension origin). Keep `supports_credentials: true` only with a concrete origin list; never pair it with `*`. Scope `paths` to `api/*` and the auth callbacks rather than `*`.

---

### [High] Password-reset & forgot-password leak account existence; reset throttle deliberately cleared

- **Category:** User enumeration / missing rate limiting
- **Confidence:** High
- **Location:** `backend/app/Http/Requests/Auth/ForgotPasswordRequest.php:12`, `ResetPasswordRequest.php:13`, `AuthController.php:60-71`

**Description**
`forgot-password` and `reset-password` both validate `email` with `exists:users,email`. An unknown email returns a 422 validation error ("The selected email is invalid"), while a known email proceeds — a direct account-existence oracle. Worse, `forgotPassword` then **explicitly clears** the password broker's per-IP throttle: `RateLimiter::clear("password.reset:".$request->ip())` (line 64).

**Code path / why it's a bug**
- The `exists` rule makes the two endpoints distinguishable for known vs unknown emails (enumeration). `config/auth.php:98` sets the broker `throttle` to 60s, which would normally rate-limit reset requests per user/IP, but line 64 wipes that key on every call before `Password::sendResetLink`. The only remaining limiter is the route-level `throttle:60,1` keyed by **IP** (`routes/api.php:29`), which permits 60 attempts/min from one IP and is trivially bypassed by rotating IPs.
- Combined: an attacker can enumerate the entire user base (60 emails/min/IP, parallelizable across IPs) and spam reset emails to any user with no broker-level cooldown.

**Impact**
Reliable enumeration of registered emails (useful for targeted credential-stuffing / the social-takeover bug above) and password-reset email flooding of arbitrary users.

**Suggested fix**
Remove the `exists:users,email` rule and always return a generic 200 ("if that email exists, a link was sent"). Remove the `RateLimiter::clear(...)` call so the broker throttle applies. Add an email-keyed rate limiter in addition to the IP-keyed route throttle.

---

### [High] API login rate limiting is IP-only and too loose — credential stuffing / brute force

- **Category:** Missing rate limiting / brute force
- **Confidence:** High
- **Location:** `backend/routes/api.php:29-34`, `app/Http/Controllers/Auth/AuthController.php:114-126`

**Description**
`POST /login` is protected only by `throttle:60,1` keyed by IP (default keying). There is no per-account lockout, no exponential backoff, and no `Auth::attempt`-style throttling. 60 password guesses per minute per IP against a single account, multiplied across a botnet/proxy pool, is a viable online brute-force / credential-stuffing rate, especially given the weak password policy (min 6 chars, see below).

**Code path / why it's a bug**
`login()` manually does `User::where('email')->first()` + `Hash::check` with no failure counter tied to the email. The only limiter is the shared route group `throttle:60,1` (IP-keyed). The frontend even has dedicated handling for HTTP 429 (`auth.ts:137`), implying throttling is expected to matter — but the threshold is high and per-IP only.

**Impact**
Practical online password-guessing against any known account (enumeration above provides the account list).

**Suggested fix**
Add a per-email (or email+IP) throttle with a much lower threshold (e.g. 5/min, 10/15min) and progressive lockout; consider Laravel's `Illuminate\Foundation\Auth\ThrottlesLogins` or a dedicated `RateLimiter::for('login', by: email.'|'.ip)`.

---

### [High] `sc_auth` extension cookie is unencrypted and `auth:sanctum` ignores token abilities

- **Category:** Token/session handling / privilege scoping
- **Confidence:** High (cookie), Medium (ability-scope gap)
- **Location:** `backend/app/Http/Middleware/EncryptCookies.php:14-16`, `app/Http/Controllers/Auth/AuthController.php:91-93,149-151`, `routes/api.php:75-112`

**Description**
Two related token-scoping weaknesses:
1. `EncryptCookies::$except` includes `sc_auth`, so the extension's Sanctum PAT is stored in the cookie **in plaintext** (id|token). That's by design for the extension to read it, but it means the raw, reusable bearer token rides in a cookie that is also sent automatically (and, under the wildcard CORS + disabled CSRF above, is exposed cross-origin).
2. The SPA `auth_token` is created with **no abilities** (`createToken('auth_token')`), so it gets `['*']` — full access. The `extension` token is scoped to `['extension']`. However, the protected API group uses bare `auth:sanctum` (`routes/api.php:75`) with **no `->can('...')` ability gate**. Sanctum's guard authenticates any valid token regardless of abilities, so the narrowly-scoped `extension` cookie token is accepted on **every** authenticated API route (logout, profile update, balance, disputes, payments) — the `['extension']` scoping provides no actual restriction outside the one `ext.auth` route that explicitly calls `$pat->can('extension')`.

**Code path / why it's a bug**
`ExtensionAuth.php:44` correctly enforces `$pat->can('extension')`, proving the intent was to confine the extension token to extension endpoints. But the main API never checks abilities, so a leaked `extension` cookie token is a full-power credential against `POST /user`, `/balance/*`, `/cryptomus/create-payment`, etc. The plaintext, auto-sent cookie is the most exposed of the two tokens, yet it is effectively unrestricted.

**Impact**
A captured `sc_auth` cookie (via the CORS/CSRF issues, XSS, or a misconfigured subdomain — note production uses `domain=.account-arena.com`, sharing the cookie across all subdomains) grants full account API access, not just the intended extension surface.

**Suggested fix**
Add explicit ability gates to ability-sensitive routes (`auth:sanctum` + `abilities:...` or `->can()` checks), so the SPA token and extension token have distinct reach. Consider encrypting the cookie and having the extension authenticate via header instead, or shorten the extension token lifetime and scope it tightly. Avoid the `.account-arena.com` domain-wide cookie unless every subdomain is trusted.

---

### [High] Self-service profile update allows email change with no re-auth / no verification (account-integrity + enables takeover)

- **Category:** Broken access control / weak account integrity
- **Confidence:** High
- **Location:** `backend/app/Http/Controllers/Auth/AuthController.php:175-228`

**Description**
`POST /api/user` lets the authenticated user change `email` and `password` with only a bearer token — no current-password confirmation, no email-verification step, and no notification. `email` is validated `unique` (ignoring self) but is set directly and saved.

**Code path / why it's a bug**
`update()` resolves the user via `getApiUser()` (any valid token, including the over-scoped extension token above), then applies `email`/`password` directly (lines 197-205). Because there is no re-authentication, a stolen/over-scoped token (see prior finding) can silently rewrite the account's email and password, fully evicting the legitimate owner. There is also no `email_verified_at` reset on email change (email verification is unused per the inventory), so the new email is trusted immediately — and an attacker-set email then becomes a link target for the social-login takeover bug.

**Impact**
A token holder can permanently seize the account (change email + password) without knowing the current password; chains with the social-login email-link takeover.

**Suggested fix**
Require current-password confirmation for email/password changes; send a confirmation email to the old address and require verification of the new address before it becomes active/usable for login or social linking; notify the user of the change.

---

### [Medium] Telegram HMAC check is vulnerable to replay and uses a brittle data-check string

- **Category:** Social-login replay / signature handling
- **Confidence:** Medium
- **Location:** `backend/app/Http/Controllers/Auth/SocialAuthController.php:176-211`, route `web.php:276` (`GET|POST`)

**Description**
`validateTelegramData` accepts auth data up to **24 hours** old (`time() - auth_date > 86400`) and performs no nonce/one-time-use check, so a captured valid Telegram payload can be replayed for a full day to obtain fresh tokens. The route also allows `GET`, meaning a valid signed payload can be captured from a URL/referer/logs and replayed. Additionally, the data-check string is built by `sort()`-ing `key=value` strings rather than following Telegram's spec (sort keys, join with `\n`); including extra/attacker-added fields (like the injected `email`) changes the signed string but is still controlled by the client, and the loose construction makes the scheme fragile.

**Code path / why it's a bug**
Telegram's recommended freshness window is short (commonly ~30-60s); 86400s is a very wide replay window. With `GET` allowed, the signed `hash` plus fields can appear in server logs, browser history, or referers and be resubmitted within 24h. No `auth_date`-binding to a session nonce exists.

**Impact**
Replay of a captured Telegram auth payload for up to 24h yields a valid session/token for that Telegram identity (and, combined with the email-injection takeover, potentially a victim account).

**Suggested fix**
Shorten the freshness window to ~30-60s, restrict the route to `POST` only, and (ideally) bind to a server-issued nonce/state. Build the data-check string per Telegram spec (sort keys, exclude `hash`, join with `\n`). Reject unexpected fields such as `email`.

---

### [Medium] Google OAuth callback has no `state` / CSRF protection (login CSRF / code injection)

- **Category:** OAuth CSRF (login CSRF)
- **Confidence:** Medium
- **Location:** `backend/app/Http/Controllers/Auth/SocialAuthController.php:19-47`, `web.php:271-273`

**Description**
The Google flow relies on Socialite's stateful `state` parameter, but the `web` group's CSRF protection is globally disabled (finding above) and, more importantly, Socialite's `state` validation depends on the session being intact across redirect/callback. The redirect endpoints (`/auth/google`, `/auth/google/reauth`) are not behind any explicit `state`-enforcing guard, and the callback (`handleGoogleCallback`) does not itself validate a `state`/nonce beyond whatever Socialite does internally. If session state is not reliably carried (popup flow, cross-site cookies under `SameSite` constraints), the callback can be driven with an attacker-supplied `code`, logging the victim's browser into the **attacker's** Google account (classic login CSRF) — or, in reverse, replaying a victim's code.

**Code path / why it's a bug**
`redirectToGoogleWithPrompt()` issues the redirect without an app-level `state` binding to the initiating session; the callback view immediately `postMessage`s the resulting token to `window.location.origin`. There is no app-level anti-forgery token tying the popup open to its callback.

**Impact**
Login-CSRF: a victim can be silently signed into an attacker-controlled Google identity (then e.g. lured into entering data, linking payment, etc.), or session-fixation-style confusion in the popup handshake.

**Suggested fix**
Ensure Socialite stateful mode is used with a verifiable `state` that is bound to the initiating session/popup and validated on callback; do not rely on the globally-disabled CSRF layer. Reject callbacks whose `state` does not match.

---

### [Medium] Weak password policy (min 6) and inconsistent enforcement

- **Category:** Weak credential policy
- **Confidence:** High
- **Location:** `backend/app/Http/Requests/Auth/RegisterRequest.php:14`, `ResetPasswordRequest.php:14`, `AuthController.php:186`

**Description**
Registration, reset, and profile-update accept passwords with `min:6` and no complexity/breach checks. The frontend strength meter and the legacy web `RegisterController` assume 8. Six characters with no complexity, combined with the loose login throttling above, materially lowers brute-force cost.

**Impact**
Weak passwords are accepted system-wide, increasing success rate of the credential-stuffing/brute-force vector.

**Suggested fix**
Use Laravel's `Password::min(8)->mixedCase()->numbers()->uncompromised()` rule across register/reset/update; align the backend with the frontend's stated policy.

---

### [Medium] Mass-assignment exposure: `email`/social ids fillable; takeover via profile linking surface

- **Category:** Mass assignment
- **Confidence:** Medium
- **Location:** `backend/app/Models/User.php:20-35`

**Description**
The privilege/status columns (`is_admin`, `is_main_admin`, `is_supplier`, `is_blocked`, `is_pending`, `balance`, `personal_discount`, `supplier_commission`) are correctly **not** in `$fillable`, which is good. However, `google_id`, `telegram_id`, `telegram_username`, `provider`, and `email` **are** fillable. Any controller that does `User::create($request->all())` or `$user->update($request->all())` on user input would let a caller set/overwrite these identity-linking fields. The social controllers already pass attacker-controlled `telegram_id`/`email` into `User::create`/`update` (see Critical findings). While role escalation via mass assignment is blocked, **identity-linking** fields are not protected, which is the lever the social-takeover bugs pull.

**Impact**
Combined with the social flows, attacker-controlled identity fields can be written to arbitrary accounts. No direct `is_admin` escalation via fillable was found (correctly guarded).

**Suggested fix**
Keep identity-linking writes out of mass-assignment from untrusted input; set `google_id`/`telegram_id`/`provider` only through vetted, server-derived values after ownership verification.

---

### [Low] Login attempt logging records emails (and the flow logs balance) in plaintext logs

- **Category:** Sensitive-data logging
- **Confidence:** High
- **Location:** `backend/app/Http/Controllers/Auth/AuthController.php:117-164`

**Description**
Every login attempt logs the submitted email (including failed attempts and blocked/pending attempts), and successful logins log `user_id`, `email`, and `balance`. This writes PII and account state to application logs on every request.

**Impact**
Log files become a PII store and an enumeration aid (failed-attempt emails). Anyone with log access (or via a log-exposure bug) gets an email/account inventory.

**Suggested fix**
Reduce auth logging to non-PII identifiers or hashed emails; drop `balance` from auth logs; ensure logs are access-controlled and rotated.

---

### [Low] Logout revokes all tokens across all devices (availability / UX correctness)

- **Category:** Session handling correctness
- **Confidence:** High
- **Location:** `backend/app/Http/Controllers/Auth/AuthController.php:246-252`

**Description**
`logout()` calls `$request->user()->tokens()->delete()`, deleting **every** Sanctum token for the user (SPA + extension + any social-minted tokens). Logging out on one device silently kills the browser extension session and all other devices.

**Impact**
Not a vulnerability but a correctness/UX defect noted in the inventory; can also mask token-theft cleanup expectations (you cannot revoke a single compromised device).

**Suggested fix**
Delete only the current access token (`$request->user()->currentAccessToken()->delete()`), and expose a separate "log out everywhere" action.

---

## Summary

| Severity | Count |
|----------|-------|
| Critical | 3 |
| High     | 4 |
| Medium   | 4 |
| Low      | 2 |
| **Total**| **13** |

**Top 3 most serious**
1. **Social-login account takeover via attacker-controlled email** (Critical) — Telegram `email` injection (and unverified Google email) links a social identity onto any victim account by email match and returns a full token.
2. **CSRF globally disabled** (`VerifyCsrfToken::$except = ['*']`) (Critical) — every session-authenticated admin/supplier endpoint is CSRF-forgeable.
3. **Wildcard CORS with credentials** (`allowed_origins:['*']` + `supports_credentials:true`) (Critical) — any origin can make credentialed requests and read responses.

---

## Ruled-out / non-issues (with reasoning)

- **Mass-assignment role escalation** — `is_admin`, `is_main_admin`, `is_supplier`, `is_blocked`, `is_pending`, `balance`, `personal_discount`, `supplier_commission` are all **absent** from `User::$fillable` (`User.php:20-35`); `UserService::updateUser` writes them via `forceFill` only on the admin path. No user→admin escalation via the self-service `/api/user` update was found. (Identity-linking fields remain fillable — captured separately as Medium.)
- **`getStatus()` role logic** — correct precedence (blocked → pending → active); `AdminAuth`/`IsMainAdmin` correctly require `is_admin`/`is_main_admin` plus `!is_blocked`. No logic inversion found.
- **Blocked/pending bypass on API login** — `AuthController::login` checks `is_blocked` then `is_pending` before minting tokens; not bypassable through that path. (Note: Google callback does not check `is_pending`, and neither social path checks `is_pending` — minor inconsistency, but pending is an onboarding gate, not a security boundary, so not escalated.)
- **Password-reset token storage** — standard Laravel broker (`password_reset_tokens`, hashed, 60-min expiry per `auth.php:97`); `Password::reset` regenerates `remember_token`. No custom-token weakness found.
- **`SetLocale` host/locale injection** — locale is normalized and validated against `config('langs')` allow-list (`SetLocale.php:24-27`); invalid values fall back to default. No locale/host injection into queries or file paths observed in this middleware.
- **`getApiUser()` resolving an invalid token** — returns `false`/`null` and callers 401; `PersonalAccessToken::findToken` validates the token hash. The real issue is the missing **ability** check (captured in the extension-token finding), not token forgery.
- **`hash_equals` usage in Telegram HMAC** — timing-safe comparison is correctly used; the weakness is the replay window and email injection, not the comparison itself.
