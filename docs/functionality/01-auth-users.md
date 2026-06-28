# Functional Inventory: Authentication, Users, Profile & Access Control

Domain documentation for the **Account Arena** marketplace (Laravel backend + Vue 3 / Pinia frontend).

This document covers: registration, credential login, social login (Google + Telegram), logout, password reset, current-user fetch/update, profile editing, user roles, account blocking/status, the Sanctum token model, and email verification.

---

## 0. Overview & Architecture

There are effectively **two parallel auth systems** in the codebase:

1. **SPA / API auth (token-based, Laravel Sanctum)** — used by the public Vue marketplace frontend. Implemented in `App\Http\Controllers\Auth\AuthController` and exposed under `routes/api.php`. This is the primary system for end users (buyers).
2. **Web / session auth (Laravel UI scaffolding)** — used by the server-rendered admin and supplier panels. Implemented by the classic Laravel `LoginController`, `RegisterController`, `ForgotPasswordController`, `ResetPasswordController`, `ConfirmPasswordController`, `VerificationController` (all use Laravel traits) under `routes/web.php`. Most of these scaffolding controllers are stock/unused except `LoginController` (admin) and the social controller.

Token model: **Sanctum Personal Access Tokens (PAT)**. Two tokens are minted per login/registration:
- `auth_token` — the SPA bearer token (returned in JSON, stored in `localStorage`).
- `extension` (scoped `['extension']`) — set in an HTTP-only cookie `sc_auth` for the browser extension.

User model: `App\Models\User` (`backend/app/Models/User.php`), uses `HasApiTokens, HasFactory, Notifiable`.

User status values (`User.php:16-18`): `STATUS_ACTIVE = 'active'`, `STATUS_BLOCKED = 'blocked'`, `STATUS_PENDING = 'pending'`. Derived via `getStatus()` (`User.php:363-372`) from the `is_blocked` / `is_pending` boolean columns.

---

## 1. Registration (SPA / API)

**What it does:** A visitor creates a marketplace account with name, email, password and UI language. On success they are immediately authenticated (token returned) and redirected into the app. A welcome notification is sent to the user and an admin "new user" notification is dispatched.

**Endpoint:** `POST /api/register` — rate-limited `throttle:60,1` (`routes/api.php:29-30`).

**Implementing files:**
- `backend/app/Http/Controllers/Auth/AuthController.php:73-112` (`register`)
- `backend/app/Http/Requests/Auth/RegisterRequest.php:9-18` (validation)
- `backend/app/Http/Requests/ApiRequest.php:12-26` (base request; returns `{ "errors": {...} }` 422 on validation failure)
- Frontend: `frontend/src/components/auth/RegisterPage.vue`, `frontend/src/stores/auth.ts:69-98` (`register` action)

**Inputs / validation (`RegisterRequest`):**
- `name` — required, string, max 255
- `email` — required, string, email, max 255, `unique:users`
- `password` — required, string, **min 6**, `confirmed`
- `password_confirmation` — required
- `lang` — required, one of `en`, `uk`, `ru`

Note: the frontend (`auth.ts:75-76`) injects `lang` automatically from the current i18n locale; the user does not type it. The RegisterPage also shows a client-side password strength meter (4 levels, requires length≥8, letters, numbers) but this is purely cosmetic — backend min length is 6.

**Output (200 JSON):** `{ message: __('auth.user_registered'), token: <spaToken>, user: <User> }` plus a `Set-Cookie: sc_auth=<extToken>` cookie (HTTP-only, 7-day default).

**Business rules / notable logic:**
- Creates user with `password = Hash::make(...)` (also auto-hashed via `'password' => 'hashed'` cast).
- Sets `active_services = []` and `subscriptions = []` on the response object (legacy subscription feature removed).
- `app(NotificationTemplateService::class)->sendToUser($user, 'registration')` — sends welcome notification.
- `NotifierService::sendFromTemplate('registration', 'admin_new_user', ...)` — notifies admins of the new signup.
- Two Sanctum tokens minted (SPA `auth_token` + scoped `extension`).

**Web (admin/supplier) registration:** `RegisterController` (`RegisterController.php`) is stock Laravel UI (`RegistersUsers` trait, `guest` middleware, redirects to `/home`, password min 8 confirmed). It is **not wired into the documented routes** for public use and appears unused for the marketplace.

---

## 2. Login with Credentials (SPA / API)

**What it does:** Authenticates an existing user by email + password, returns a bearer token and the user object, optionally with an extended "remember me" expiry. Blocks login for blocked/pending accounts.

**Endpoint:** `POST /api/login` — rate-limited `throttle:60,1` (`routes/api.php:31`).

**Implementing files:**
- `backend/app/Http/Controllers/Auth/AuthController.php:114-173` (`login`)
- `backend/app/Http/Requests/Auth/LoginRequest.php:9-17`
- Frontend: `frontend/src/components/auth/LoginPage.vue`, `frontend/src/stores/auth.ts:100-157` (`login`)

**Inputs / validation (`LoginRequest`):**
- `email` — required, email
- `password` — required, string
- `remember` — boolean (optional)

**Output (200 JSON):** `{ token: <spaToken>, user: <User> }` + `Set-Cookie: sc_auth=<extToken>`. The user object has `active_services = []` injected.

**Business rules / state checks (in order, `AuthController::login`):**
1. Looks up user by email; if not found **or** `Hash::check` fails → 422 `auth.invalid_credentials` (generic, does not reveal which).
2. If `user.is_blocked` → 422 `auth.account_blocked`.
3. If `user.is_pending` → 422 `auth.account_pending` ("account awaiting confirmation").
4. If `remember` is truthy → `Config::set('sanctum.expiration', 43200)` (30 days, in minutes) for this request's minted tokens. Default Sanctum expiration is `1440` minutes / 24h (`config/sanctum.php:52`).
5. Mints `auth_token` (SPA) + `extension`-scoped token (cookie).

**Edge cases / frontend handling:**
- Validation errors returned as `{ errors: { email: [...] } }` (422). Logout-level errors flow through the same channel.
- Frontend `auth.ts:137-152` specifically handles **HTTP 429** (rate-limit) with a "too many attempts" message.
- On success the frontend stores `token` + `user` in `localStorage`, sets the axios `Authorization` header, and syncs the i18n locale from `user.lang` (`auth.ts:127-131`).
- Extensive `\Log::info/warning` audit logging is emitted on each attempt (`AuthController.php:117-164`).

---

## 3. Admin Login (Web / Session)

**What it does:** Server-rendered login form for the admin panel; only users with `is_admin = 1` may log in. Session-based (not token).

**Endpoints (web):**
- `GET /admin/login` → `LoginController@showLoginForm` (route name `admin.login`, `web.php:43`)
- `POST /admin/login` → `LoginController@login` (`web.php:44`)
- `GET|POST /admin/logout` → `LoginController@logout` (name `admin.logout`, behind `admin.auth`, `web.php:48-49`)
- `GET /login` → redirects to `admin.login` (`web.php:264-266`)

**Implementing file:** `backend/app/Http/Controllers/Auth/LoginController.php` (uses Laravel `AuthenticatesUsers` trait; `$redirectTo = '/admin'`).

**Business rules:**
- `showLoginForm` (`LoginController.php:44-57`): if already authenticated **and** `is_admin` → redirect to `admin.dashboard`; if authenticated but **not** admin → force `logout()` and show form.
- `validateLogin` (`LoginController.php:59-82`): requires the email to belong to a user with `is_admin = 1`, else "User not found or not an admin."; if that admin is `is_blocked` → "Your account has been blocked."
- `logout` (`LoginController.php:90-98`): invalidates session, regenerates CSRF token, redirects to `admin.login`.

**Supplier login (web):** Handled by a separate `App\Http\Controllers\Supplier\AuthController` under `/supplier/*` (`web.php:174-181`), guarded by `auth` + `supplier.auth` middleware. (Outside the listed files but referenced for completeness.)

---

## 4. Social Login — Google OAuth

**What it does:** Lets a user authenticate via Google. The frontend opens a popup to the backend OAuth redirect; the backend handles the Google callback, finds-or-creates the user, links the Google account, mints a Sanctum token, and posts the result back to the opener window which stores the session.

**Endpoints (web, under `auth` prefix, `web.php:269-273`):**
- `GET /auth/google` → `SocialAuthController@redirectToGoogle`
- `GET /auth/google/reauth` → `SocialAuthController@redirectToGoogleWithPrompt` (forces account chooser via `prompt=select_account`) — **this is the one the frontend uses**.
- `GET /auth/google/callback` → `SocialAuthController@handleGoogleCallback`

**Implementing files:**
- `backend/app/Http/Controllers/Auth/SocialAuthController.php:19-115`
- View rendered back to popup: `backend/resources/views/auth/callback.blade.php`
- Frontend: `frontend/src/components/auth/SocialAuthButtons.vue:58-132` (popup + `postMessage` listener), `frontend/src/components/auth/AuthCallback.vue` (route `/auth/callback` token-from-URL handler), `frontend/src/stores/auth.ts:295-304` (`setToken`/`setUser`)

**Flow / logic (`handleGoogleCallback`, lines 43-115):**
1. `Socialite::driver('google')->user()` fetches the Google profile.
2. Find user by email. If found and `google_id` is empty → update `google_id`, `provider='google'`, `avatar`. If not found → create a new user with `provider='google'`, `google_id`, `name`, `email`, `avatar`, and a **random throwaway password** (`Hash::make(rand(100000,999999))`).
3. If `user.is_blocked` → render callback view with `success=false, error='Ваш аккаунт заблокирован'`.
4. `Auth::login($user)` (session) + mint `auth_token` PAT.
5. Render `auth/callback.blade.php` with `success=true, token, user` (only `id, name, email, avatar`).

**Popup ↔ opener handshake (`callback.blade.php` + `SocialAuthButtons.vue`):**
- The blade view runs JS that calls `window.opener.postMessage({ type: 'GOOGLE_AUTH_SUCCESS', data: { token, user } }, origin)` then `window.close()` (or `GOOGLE_AUTH_ERROR` on failure).
- The opener (`SocialAuthButtons.vue:79-114`) validates `event.origin === window.location.origin`, then `authStore.setToken/setUser`, and routes to `route.query.redirect || '/'`.
- An interval (`:120-131`) detects a manually-closed popup and resets the loading state.

**Edge cases:**
- Any exception is caught and rendered as `success=false` with the exception message (`:102-114`).
- `account_data`/`active_services`: `handleGoogleCallback` sets `active_services = $user->activeServices()` before returning (note: a different method than the email-login path which uses `[]`).
- `AuthCallback.vue` is an **alternate** redirect-based handler (`/auth/callback?token=...`) that reads the token from the URL and calls `fetchUser()`; the primary Google flow uses the popup `postMessage` path instead.

---

## 5. Social Login — Telegram

**What it does:** Authenticates via the Telegram Login Widget. The frontend loads the Telegram widget script, obtains signed auth data, and POSTs it to the backend, which validates the HMAC signature, finds-or-creates/links the user, and returns a Sanctum token.

**Endpoint (web):** `GET|POST /auth/telegram/callback` → `SocialAuthController@handleTelegramCallback` (`web.php:276`).

**Implementing files:**
- `backend/app/Http/Controllers/Auth/SocialAuthController.php:120-211` (`handleTelegramCallback` + `validateTelegramData`)
- Frontend: `frontend/src/components/auth/SocialAuthButtons.vue:135-224` (widget load + `fetch('/auth/telegram/callback')`)

**Inputs:** Raw Telegram widget payload (`id`, `auth_date`, `hash`, `first_name`, `last_name?`, `username?`, `photo_url?`, `email?`). Bot id comes from `optionStore.getOption('telegram_bot_id')` on the frontend.

**Signature validation (`validateTelegramData`, lines 176-211):**
- Requires `id`, `auth_date`, `hash` present, else invalid (401).
- Rejects if `auth_date` is older than 86400s (24h).
- Loads `telegram_bot_token` via `Option::get(...)`; if empty → invalid.
- Computes `secretKey = sha256(botToken, raw)`, builds the sorted `key=value\n` data-check string (excluding `hash`), computes `hash_hmac('sha256', ..., secretKey)`, and compares with `hash_equals`. Invalid signature → 401 `{ error: 'Invalid Telegram data' }`.

**Find/create/link logic (lines 129-158):**
1. Find by `telegram_id`. If found → refresh `telegram_username` and `avatar`.
2. If not found: if a `telegram.email` matches an existing user → link (`telegram_id`, `telegram_username`, `provider='telegram'`). Else → create a new user (`provider='telegram'`, `telegram_id`, `telegram_username`, `name = first_name + ' ' + last_name`, `email = telegram.email ?? "{id}@telegram.org"`, random password).

**Output:** `{ token, user }` (200) with `user.active_services` populated. If `is_blocked` → 403 `{ error: 'Account blocked' }`. Any exception → 500 `{ error: <message> }`.

**Note:** Unlike Google, the Telegram path returns JSON directly to a `fetch` call (no popup/blade), and does **not** call `Auth::login()` (pure token auth).

---

## 6. Logout

### SPA / API logout
**Endpoint:** `GET /api/logout` — behind `auth:sanctum`, `throttle:120,1` (`routes/api.php:75-76`).
**Implementing file:** `AuthController.php:246-252`.
**Behavior:** `$request->user()->tokens()->delete()` — **revokes ALL of the user's Sanctum tokens** (SPA + extension + any social tokens). Returns `{ message: __('auth.logged_out') }` and clears the `sc_auth` cookie (sets it empty with `-60` min expiry). Frontend `auth.ts:201-216` clears `localStorage` + axios header regardless of API outcome.

### Web/admin logout
`LoginController::logout` (`LoginController.php:90-98`) — session invalidate + token regenerate, redirect to `admin.login`. (See §3.)

---

## 7. Password Reset Flow

A standard Laravel `Password` broker flow, exposed via the SPA API.

### 7a. Request reset link (forgot password)
**Endpoint:** `POST /api/forgot-password` — `throttle:60,1` (`routes/api.php:32`).
**Implementing files:** `AuthController.php:60-71` (`forgotPassword`), `ForgotPasswordRequest.php`, frontend `ForgotPasswordPage.vue` + `auth.ts:159-178`.
**Input:** `email` — required, email, **`exists:users,email`** (so a non-existent email returns a 422 validation error — this leaks account existence).
**Behavior:**
- `EmailService::configureMailFromOptions()` configures mail settings from DB options.
- `RateLimiter::clear("password.reset:".$request->ip())` — **explicitly clears** Laravel's built-in per-IP throttle for reset links before sending (so the broker's own throttling is bypassed; only the route-level `throttle:60,1` remains).
- `Password::sendResetLink(['email'])` → returns `{ message }` (200) on `RESET_LINK_SENT`, else `{ errors: { message: [...] } }` (400).
- The reset email is built by `App\Notifications\ResetPasswordNotification` (`User::sendPasswordResetNotification`, `User.php:156-159`). It localizes to `user.lang`, uses the `reset_password` email template, and builds the URL `url('/reset-password/'.$token.'?email='.urlencode(email))` — i.e. a **frontend SPA route**.

### 7b. Perform reset
**Endpoint:** `POST /api/reset-password` — `throttle:60,1` (`routes/api.php:33`).
**Implementing files:** `AuthController.php:41-58` (`resetPassword`), `ResetPasswordRequest.php`, frontend `ResetPasswordPage.vue` (route `/reset-password/:token`) + `auth.ts:180-199`.
**Inputs (`ResetPasswordRequest`):**
- `token` — required (the password-reset token)
- `email` — required, email, `exists:users,email`
- `password` — required, string, **min 6**, `confirmed` (so `password_confirmation` must match)
**Behavior:** `Password::reset(...)` re-hashes the password (`Hash::make`), regenerates `remember_token` (`Str::random(60)`), fires `PasswordReset` event. Returns `{ message }` (200) on `PASSWORD_RESET`, else `{ errors: { message: [...] } }` (400).

**Token storage:** Standard Laravel `password_reset_tokens` table (hashed token + expiry, managed by the broker). The frontend passes the raw token from the URL.

**Web scaffolding:** `ForgotPasswordController` (`SendsPasswordResetEmails`) and `ResetPasswordController` (`ResetsPasswords`, redirect `/home`) exist as stock Laravel UI controllers but the active flow is the API controller above.

---

## 8. Current User — Fetch & Update

### 8a. Fetch current user
**Endpoint:** `GET /api/user` — `auth:sanctum`, `throttle:120,1` (`routes/api.php:77`).
**Implementing file:** `AuthController.php:230-244` (`user`), frontend `auth.ts:218-234` (`fetchUser`).
**Behavior:** Resolves the user from the bearer token via `getApiUser()` (`Controller.php:14-27`, manual `PersonalAccessToken::findToken`); returns the full user JSON with `active_services = []` injected. If the token is invalid → 401 `{ message: __('auth.invalid_token') }`. Frontend logs the user out on any failure.

### 8b. Update current user / profile
**Endpoint:** `POST /api/user` — `auth:sanctum`, `throttle:120,1` (`routes/api.php:78`).
**Implementing files:** `AuthController.php:175-228` (`update`), frontend `ProfilePage.vue` (`handleSubmit` at `:2301-2317`) + `auth.ts:270-293` (`update`).
**Inputs / validation (all `sometimes`):**
- `name` — required string (when present)
- `email` — required, email, `unique:users` ignoring the current user's id
- `password` — nullable, string, `confirmed` (needs `password_confirmation`); empty/absent keeps the existing password
- `lang` — one of `en`, `uk`, `ru`
- `browser_session_pid` — nullable integer (stored as `user.session_pid`, null if falsy) — used by the browser extension
- `keyboardLanguages` — array of strings; merged into `user.extension_settings['keyboardLanguages']` (de-duplicated), preserving `uiLanguage`

**Output:** `{ user: <User> }` (200) or `{ errors }` (422).
**Notable logic:** Only keys actually present in the validated payload are applied (`array_key_exists` checks). Password is re-hashed only if a non-empty value is supplied. The frontend ProfilePage sends `{ name, email, password, password_confirmation }`; `auth.ts` also has a lightweight "lang only" path (`:273`) that updates language without showing the global loading spinner.

**Profile page (UI):** `frontend/src/pages/account/ProfilePage.vue` also surfaces (read-only/related, outside this domain) balance, voucher activation, disputes ("Мои претензии"), and purchase history — but the editable account form is just name/email/password.

### 8c. Service-layer user update (admin path)
`App\Services\UserService::updateUser` (`UserService.php:17-83`) is the admin/back-office update routine (not the self-service `/api/user`). Notable rules:
- Hashes `password` if provided, else strips it.
- Maps a single `is_blocked` input value to status: `1 → blocked` (is_blocked=1,is_pending=0), `2 → pending` (is_blocked=0,is_pending=1), `0/other → active` (both 0). This is the canonical place the tri-state status is set.
- `supplier_balance` is updated **atomically** via `lockForUpdate()` + `increment` by the diff (race-condition protection), not overwritten.
- Non-fillable fields (`is_blocked`, `is_pending`, `is_supplier`, `personal_discount`, `personal_discount_expires_at`, `supplier_commission`, `supplier_hold_hours`) are written via `forceFill(...)->save()`.
- `createUser` (`:99-108`) defaults `name` to the email local-part and sets `is_blocked=0, is_pending=0`.

---

## 9. User Roles & How They're Distinguished

Roles are **boolean columns** on the `users` table (no roles/permissions package). Resolution is by direct column checks in middleware/controllers.

| Role | Column(s) | Default | Migration |
|------|-----------|---------|-----------|
| User (buyer) | none (absence of flags) | — | base `create_users_table` |
| Admin | `is_admin` | `false` | `2014_10_12_000000_create_users_table.php` |
| Main admin | `is_main_admin` (requires `is_admin` too) | `false` | `2025_03_22_224155_add_is_main_admin_to_users_table.php` |
| Supplier | `is_supplier` | `false` | `2025_11_02_082342_add_supplier_fields_to_users_table.php` |

**Status columns** (distinct from roles): `is_blocked` (`2025_03_22_181820_add_is_blocked_to_users_table.php`) and `is_pending` (base table). Derived string via `User::getStatus()` → `active` / `blocked` / `pending`.

**Social/identity columns** (`2025_05_16_221528_add_social_auth_fields_to_users_table.php`): `google_id`, `telegram_id`, `telegram_username`, `avatar`, `provider`. (`provider` ∈ `google`/`telegram`/null.)

Supplier role also carries: `supplier_balance`, `supplier_commission` (default 10%), `supplier_rating`, `supplier_hold_hours`, `rating_updated_at` — with rating logic in `User::calculateSupplierRating/getRatingLevel/getRatingDetails` (`User.php:165-326`).

---

## 10. Access Control Middleware

All under `backend/app/Http/Middleware/`.

| Middleware | File:line | Purpose / Rule |
|------------|-----------|----------------|
| `Authenticate` | `Authenticate.php:13-25` | Standard Laravel auth redirect. For JSON requests returns null (→ 401). For `supplier`/`supplier/*` paths redirects to `supplier.login`, otherwise `admin.login`. |
| `AdminAuth` (`admin.auth`) | `AdminAuth.php:18-35` | Requires `auth()->check() && is_admin`. If admin **and** `is_blocked` → logout + redirect `admin.login`. Non-admin authenticated users are logged out. |
| `IsMainAdmin` | `IsMainAdmin.php:18-25` | Requires `is_admin && is_main_admin && !is_blocked`, else redirect `admin.login`. |
| `SupplierMiddleware` (`supplier.auth`) | `SupplierMiddleware.php:16-34` | Requires auth (saves intended URL → `supplier.login`); requires `is_supplier`, else logout + redirect with error. |
| `ExtensionAuth` | `ExtensionAuth.php:14-75` | Authenticates the browser extension. Reads the `sc_auth` **cookie** (fallback `X-EXT-TOKEN` header), sanitizes it, resolves the Sanctum PAT, and **requires the `extension` ability/scope** (`$pat->can('extension')`, else 403). Falls back to session guard. 401 on missing/invalid token. Sets the resolved user via `auth()->setUser()`. |
| `RedirectIfAuthenticated` (`guest`) | `RedirectIfAuthenticated.php:18-29` | If already authenticated on any listed guard → redirect to `RouteServiceProvider::HOME`. |
| `SetLocale` | `SetLocale.php:13-53` | Resolves locale from `?lang` query → `X-Locale` header → `Accept-Language` → `app.locale`; normalizes `ua`→`uk`; validates against `config('langs')`; applies to `App::setLocale` + `Carbon::setLocale`. |

**API auth guard:** Protected API routes use Sanctum's built-in `auth:sanctum` (e.g. the authenticated group `routes/api.php:75`). Some controller methods additionally use the manual `getApiUser()` helper (`Controller.php:14-27`) which resolves the PAT from the bearer token directly.

---

## 11. Session / Token Model (Sanctum)

- **Token type:** Sanctum Personal Access Tokens. Login/registration mint `auth_token` (SPA) and `extension` (scoped `['extension']`).
- **SPA token transport:** JSON `token` field → `localStorage('token')` → axios `Authorization: Bearer` header (`auth.ts`). Persisted user in `localStorage('user')`.
- **Extension token transport:** HTTP-only cookie `sc_auth`. Cookie built by `AuthController::buildAuthCookie` (`:22-39`): path `/`, 7-day default lifetime; for non-local hosts `secure=true`, `SameSite=None`, domain `config('session.domain')` / `.account-arena.com`; for localhost `secure=false`, `SameSite=Lax`, domain null. Validated by `ExtensionAuth` middleware (requires `extension` ability).
- **Expiration:** `config/sanctum.php:52` default `1440` min (24h). `remember=true` on login bumps it to `43200` min (30 days) for that request (`AuthController.php:144-146`).
- **Revocation:** API logout deletes **all** of the user's tokens (`tokens()->delete()`).
- **Token resolution:** Sanctum guard (`auth:sanctum`) for grouped routes; manual `PersonalAccessToken::findToken($bearer)` in `getApiUser()` and `ExtensionAuth`.

---

## 12. Email Verification

- **Schema support exists:** `users.email_verified_at` column (base migration) and `'email_verified_at' => 'datetime'` cast (`User.php:43`).
- **Controller exists but is gated/unused for the SPA:** `VerificationController` (`VerificationController.php`) uses Laravel's `VerifiesEmails` trait with `auth`, `signed`, and `throttle:6,1` middleware, redirect `/home`. However, **no verification routes are wired into the documented `api.php`/`web.php` auth flows**, the `User` model does **not** implement `MustVerifyEmail`, and registration never sends a verification email. Effectively, **email verification is not enforced** in the current marketplace flow.

---

## 13. 2FA / MFA

**Not implemented.** No two-factor / TOTP / OTP code exists in any of the auth controllers, the `User` model, middleware, or the frontend auth components. The only "second factor"-like mechanisms are: the Telegram HMAC signature check and the per-purpose Sanctum token scoping (`extension` ability).

---

## 14. Account Blocking & Status — Summary

`is_blocked` and `is_pending` gate authentication across all entry points:

| Entry point | Blocked behavior | Pending behavior |
|-------------|------------------|------------------|
| API login (`AuthController::login`) | 422 `auth.account_blocked` | 422 `auth.account_pending` |
| Google callback | callback view error "Ваш аккаунт заблокирован" | (not checked) |
| Telegram callback | 403 `Account blocked` | (not checked) |
| Admin web login (`LoginController`) | "Your account has been blocked." | (n/a) |
| `AdminAuth` middleware | logout + redirect | (n/a) |
| `IsMainAdmin` middleware | denied (redirect) | (n/a) |

Status is set canonically through `UserService::updateUser` tri-state mapping (`0=active, 1=blocked, 2=pending`) and read via `User::getStatus()`.

---

## 15. Notable Edge Cases & Gotchas

- **Account-existence leak:** `forgot-password` and `reset-password` both validate `email` with `exists:users,email`, returning 422 for unknown emails — reveals which emails are registered.
- **Reset-link throttle bypass:** `forgotPassword` explicitly clears the broker's per-IP throttle (`RateLimiter::clear`), leaving only the route-level `throttle:60,1`.
- **Inconsistent `active_services`:** email login/register/`/user` set `active_services = []`; the social callbacks set it from `$user->activeServices()` (legacy method).
- **Password min length mismatch:** API register/reset enforce **min 6**; the legacy web `RegisterController` and the UI strength meter assume **8**.
- **Social accounts get a random, unknown password** — such users can only sign in via the same provider or via the password-reset flow.
- **Two distinct Google callback paths** exist: the popup `postMessage` blade flow (primary) and the URL-token `AuthCallback.vue` route (`/auth/callback`).
- **Logout revokes every token**, so logging out on one device kills the extension session and all sessions.
- **Cookie domain** is environment-sensitive (localhost vs `.account-arena.com`) and uses `SameSite=None; Secure` in production for cross-subdomain extension use.
- **Frontend guards** (`router.js:196-215`): `requiresAuth` routes redirect to `/login?redirect=<path>` when not authenticated; `requiresGuest` routes (login/register/forgot/reset) redirect authenticated users to `/`. `isAuthenticated` requires both a non-empty token and a user object with an email (`auth.ts:40-44`).

---

## Appendix: File Reference Index

**Backend controllers**
- `backend/app/Http/Controllers/Auth/AuthController.php` — SPA register/login/logout/user/update/forgot/reset
- `backend/app/Http/Controllers/Auth/SocialAuthController.php` — Google + Telegram
- `backend/app/Http/Controllers/Auth/LoginController.php` — admin web login
- `backend/app/Http/Controllers/Auth/RegisterController.php` — stock web register (unused)
- `backend/app/Http/Controllers/Auth/ForgotPasswordController.php`, `ResetPasswordController.php`, `ConfirmPasswordController.php`, `VerificationController.php` — stock Laravel UI traits
- `backend/app/Http/Controllers/Controller.php` — `getApiUser()` PAT helper

**Backend models/services/requests/notifications**
- `backend/app/Models/User.php`
- `backend/app/Services/UserService.php`
- `backend/app/Http/Requests/ApiRequest.php`, `Auth/{Register,Login,ForgotPassword,ResetPassword}Request.php`
- `backend/app/Notifications/ResetPasswordNotification.php`

**Middleware**
- `backend/app/Http/Middleware/{Authenticate,AdminAuth,SupplierMiddleware,ExtensionAuth,IsMainAdmin,RedirectIfAuthenticated,SetLocale}.php`

**Routes / views / config**
- `backend/routes/api.php` (lines 29-34, 74-78), `backend/routes/web.php` (lines 42-49, 174-181, 264-276)
- `backend/resources/views/auth/callback.blade.php`
- `backend/config/sanctum.php`
- `backend/database/migrations/{2014_10_12_000000_create_users_table, 2025_03_22_181820_add_is_blocked..., 2025_03_22_224155_add_is_main_admin..., 2025_05_16_221528_add_social_auth_fields..., 2025_11_02_082342_add_supplier_fields...}.php`

**Frontend**
- `frontend/src/stores/auth.ts`
- `frontend/src/components/auth/{LoginPage,RegisterPage,ForgotPasswordPage,ResetPasswordPage,AuthCallback,SocialAuthButtons}.vue`
- `frontend/src/pages/account/ProfilePage.vue`
- `frontend/src/router.js` (auth routes + navigation guards)
