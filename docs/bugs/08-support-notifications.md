# 08 — Support Chat & Notifications — Security & Correctness Audit

Domain: Support Chat + Notifications (in-app / admin / supplier / email / telegram).
Auditor scope: adversarial bug hunt (IDOR, XSS, upload abuse, template/email injection, rate limiting, broadcast auth, webhook spoofing, missing `BaseMail`).
Source root: `/Users/gospodin/Desktop/Account Arena`.

> Method: every chat/notification access path was traced to its exact ownership/authorization check. "Confidence" reflects how certain the finding is given the code read (not exploit difficulty).

---

## Summary table

| # | Severity | Confidence | Title | Location |
|---|----------|-----------|-------|----------|
| 1 | **Critical** | High | Stored XSS in admin chat view — user/guest/Telegram `message` + `file_name` + `guest_name` injected via `innerHTML` | `resources/views/admin/support-chats/show.blade.php:495-510, 866-922` |
| 2 | **Critical** | High | `EmailService::send()` references nonexistent `App\Mail\BaseMail` → all registered-user transactional emails fail | `app/Services/EmailService.php:38` |
| 3 | **High** | High | Stored XSS in user notifications — message rendered with `v-html` after variable substitution (product title / admin comment / template body) | `frontend/src/components/layout/NotificationBell.vue:73, 358-368` |
| 4 | **High** | High | Telegram webhook is unauthenticated (no secret token / no signature) → spoofed chats & impersonation of registered users via `telegram_id` | `routes/api.php:166`, `app/Http/Controllers/TelegramWebhookController.php`, `app/Services/TelegramBotService.php:375-390,450-477` |
| 5 | **High** | High | Guest chat IDOR — access gated only by attacker-supplied `email` against sequential `chatId`; Telegram chats use derivable synthetic email | `app/Http/Controllers/Api/SupportChatController.php:132-182, 187-336, 341-396` |
| 6 | **Medium** | High | Dangerous upload types allowed (SVG, HTML-capable, zip/rar) on user side; files served from public disk with no auth | `app/Http/Controllers/Api/SupportChatController.php:192, 289-303`; `config/filesystems.php:39-44` |
| 7 | **Medium** | High | No effective per-action rate limiting on chat send/typing/create (shared `throttle:300,1` across all public endpoints; Telegram webhook unthrottled) | `routes/api.php:41, 60-66, 166` |
| 8 | **Low** | High | Chat attachments publicly downloadable with no ownership check (public disk + `storage:link`) | `config/filesystems.php:39-44, 73`; `SupportMessageAttachment` |
| 9 | **Low** | Medium | `getApiUser()` ignores token expiration/abilities; resolves any matching token | `app/Http/Controllers/Controller.php:14-27` |
| 10 | **Low** | Medium | Admin `assign` has no `assigned_to` guard and accepts any `users.id` (non-admin can be assigned) | `app/Http/Controllers/Admin/SupportChatController.php:337-349` |

Counts: **2 Critical, 3 High, 2 Medium, 3 Low** = 10 findings.

---

## BUG 1 — Stored XSS in admin support-chat view (user/guest/Telegram content → `innerHTML`)

- **Severity:** Critical
- **Confidence:** High
- **Type:** Stored / DOM XSS → admin-panel account takeover
- **Location:** `backend/resources/views/admin/support-chats/show.blade.php:495-510` and `:866-922` (also attachment blocks `:871-891`).

### What
The admin chat page renders the **initial** message list with Blade `{{ }}` (escaped — safe). But the **polling** JavaScript that appends new incoming messages builds raw HTML via template literals and assigns it to `innerHTML` with **no escaping**:

```js
// :504-506 (appendMessage) and :906-909 / :911-922 (loadNewMessages)
let messageTextHtml = '';
if (message.message && message.message.trim()) {
    messageTextHtml = `<div class="message-text">${message.message}</div>`;
}
messageDiv.innerHTML = `... ${senderNameHtml} ... ${messageTextHtml} ${attachmentsHtml} ...`;
```

`message.message`, `attachment.file_name` (`:874, 878, 887`), and `chat.guest_name` (`:858, 903`) are all attacker-controlled and inserted unescaped.

### Source of attacker input
- `message.message`: any user/guest message (`Api/SupportChatController::sendMessage`, no HTML sanitization — only `trim()` at `:284`) or any Telegram inbound message (`TelegramBotService::processIncomingMessage`, raw `text` at `:387`).
- `attachment.file_name`: `$file->getClientOriginalName()` (`Api/SupportChatController:297`) or the Telegram `document.file_name` (`TelegramBotService:497`) — fully attacker-set.
- `chat.guest_name`: attacker-set at chat creation (`getOrCreateChat` `name` field) or from Telegram `first_name`/`username`.

### Impact
A guest or unauthenticated Telegram sender submits a message such as
`<img src=x onerror="fetch('/admin/...')">` (or names a file `"><img src=x onerror=...>`).
When an admin opens the chat, the 3s polling loop injects the payload into the admin DOM and it executes **in the admin's authenticated session** → CSRF-token theft, account/role escalation, full admin-panel takeover. This is the highest-impact finding because the unsanitized data crosses a privilege boundary (anonymous → admin).

### Fix
Escape all interpolated values before `innerHTML` (HTML-encode `message.message`, `file_name`, `guest_name`), or build nodes with `textContent`/`createElement`. Server-side, also sanitize message text on store.

---

## BUG 2 — Missing `App\Mail\BaseMail` breaks all registered-user emails

- **Severity:** Critical (functional) / High
- **Confidence:** High
- **Type:** Missing class → unhandled exception, silent email loss
- **Location:** `backend/app/Services/EmailService.php:38`.

### What
`EmailService::send()` does:
```php
Mail::to($user->email)->queue(new \App\Mail\BaseMail($subject, $body));
```
There is **no `app/Mail/` directory** in the repo (verified: `ls app/Mail` → "No such file or directory"); class `App\Mail\BaseMail` does not exist. The `emails.base` Blade view *does* exist and `sendToGuest()` (`:69`) uses it directly via `Mail::send('emails.base', ...)` and works.

### Impact
Every transactional email to a **registered user** (`payment_confirmation`, `product_purchase_confirmation`, manual-delivery codes, etc.) throws `Class "App\Mail\BaseMail" not found`. The `try/catch` at `:41-44` swallows it (`report()` + `return false`), so the caller sees a silent failure — users never receive purchase/delivery confirmation emails, with no visible error. Guests still get emails (different code path), making this inconsistent and easy to miss.

### Fix
Create `App\Mail\BaseMail` (a `Mailable` rendering `emails.base` with `$subject`/`$body`), or change `send()` to mirror `sendToGuest()` and use `Mail::to(...)->send('emails.base', ...)`.

---

## BUG 3 — Stored XSS in in-app user notifications (`v-html` + variable substitution)

- **Severity:** High
- **Confidence:** High
- **Type:** Stored XSS against end users
- **Location:** `frontend/src/components/layout/NotificationBell.vue:73` (`v-html="getTranslation(item, 'message')"`) and `:358-368` (substitution).

### What
The notification **title** is rendered escaped (`{{ getTranslation(item,'title') }}`, `:63`) but the **message** is rendered as raw HTML:
```html
<p v-html="getTranslation(item, 'message')"></p>
```
`getTranslation` takes the admin-authored template `message` body and substitutes `:var` placeholders with `item.template.variables` values **without escaping** (`:363-364`), then hands the result to `v-html`.

### Attacker-controlled inputs reaching the sink
1. **Template variables** (server-stored, then echoed into HTML):
   - `manual_delivery_*` → `product_title` = `serviceAccount->title` (product titles are **supplier/admin-controlled**) — `app/Services/ManualDeliveryService.php:373, 418, 491, 573`.
   - `dispute_resolved` → `comment` = admin-entered free text — `app/Models/ProductDispute.php:329`.
2. **The template `message` body itself** is admin-authored (`NotificationTemplate` CRUD) and rendered as raw HTML for every recipient — a stored XSS pivot if any non-trusted admin can edit templates.

A product titled `<img src=x onerror=...>` (or a dispute comment containing markup) is delivered verbatim into `v-html` in the victim user's browser → script execution, session/token theft.

### Fix
Render the notification message with `{{ }}` (text) or sanitize before `v-html`. At minimum HTML-encode substituted variable values inside `getTranslation`. Strip HTML from `product_title`/`comment` server-side.

---

## BUG 4 — Unauthenticated Telegram webhook → spoofed chats & user impersonation

- **Severity:** High
- **Confidence:** High
- **Type:** Webhook spoofing / authentication bypass
- **Location:** `routes/api.php:166` (no middleware), `app/Http/Controllers/TelegramWebhookController.php:14-72`, `app/Services/TelegramBotService.php:375-390, 450-477`.

### What
`POST /api/telegram/webhook` is registered with **no signature, no secret-token, and no IP allowlist** (verified: no `secret`/`x-telegram`/`signature`/`verify` checks anywhere in the controller or service). It is also outside any `verify.webhook` group (unlike Cryptomus/Mono at `:125-131`). The handler trusts the raw request body entirely:
```php
$update = $request->all();
$supportChat = $telegramBotService->processIncomingMessage($update);
```
`processIncomingMessage` reads `from.id` and links the message to a **registered user** by `telegram_id`:
```php
$user = $telegramUserId ? User::where('telegram_id', $telegramUserId)->first() : null;
// ...
'sender_type' => $supportChat->user_id ? SENDER_USER : SENDER_GUEST,
```

### Impact
Anyone can `POST` a forged Telegram update to the public endpoint. They can:
- **Impersonate a registered user**: by setting `from.id` to a victim's `telegram_id`, the injected message is created with `sender_type=user` and `user_id` = victim — appearing to admins as the genuine customer (social-engineering / fraud vector).
- **Create arbitrary fake support chats** with attacker-chosen `guest_name`/`first_name`/`text`, including the XSS payloads from BUG 1.
- **Trigger file downloads** from `api.telegram.org` (the bot will call `getFile`/download for spoofed `file_id`s — though those require a valid bot file id).
- Flood/DoS chat tables (no auth, no throttle — see BUG 7).

### Fix
Set a Telegram `secret_token` on `setWebhook` and verify the `X-Telegram-Bot-Api-Secret-Token` header on every request (reject mismatches before processing). Optionally restrict to Telegram CIDR ranges. Add throttling.

---

## BUG 5 — Guest chat IDOR (email-as-password against sequential chat IDs)

- **Severity:** High
- **Confidence:** High
- **Type:** IDOR / broken access control (guests)
- **Location:** `app/Http/Controllers/Api/SupportChatController.php` — `getMessages:147`, `sendMessage:270`, `addRating:364`, `sendTyping:438`, `stopTyping:462`, `getTypingStatus:485`.

### What
For guest chats every access check is:
```php
if (!$request->has('email') || $chat->guest_email !== $request->input('email')) { return 403; }
```
The "secret" is the guest's **email address**, which the attacker supplies in the same request alongside the path `chatId`. `chatId` is a sequential auto-increment (`$table->id()`, migration `2025_11_14_094630`). So the authorization reduces to *"do you know this chat's guest email?"* — and email is not a secret.

Attack: enumerate `chatId = 1..N`, and for each, supply a guessed/known victim email. If it matches, the attacker can **read the full conversation, send messages as the guest, rate the chat, and read typing status**. Email is widely known (leaks, prior contact, etc.), so this is a practical hijack, not theoretical.

### Telegram-origin amplification
Telegram guest chats are created with a **derivable** synthetic email:
```php
'guest_email' => "tg{$telegramChatId}@telegram.local",   // TelegramBotService:474
```
The `telegram_chat_id` is frequently public/known. An attacker who knows a Telegram chat id can reconstruct the exact `guest_email` and fully hijack that support chat via the public website API (read all messages, impersonate the guest, etc.) without any secret at all. There is also no `source` check, so a website attacker can drive a Telegram-origin chat.

### Fix
Issue an unguessable per-chat guest token (random, returned only at creation, stored hashed) and require it for all guest chat access instead of email matching. For Telegram chats, never accept email-based website access — gate them behind the token. Make email comparison at least case-insensitive/normalized as a secondary measure.

---

## BUG 6 — Dangerous attachment types accepted (SVG / HTML-capable / archives)

- **Severity:** Medium
- **Confidence:** High
- **Type:** Unrestricted file upload / stored-content XSS vector
- **Location:** `app/Http/Controllers/Api/SupportChatController.php:192` (validation) and `:289-303` (store to public disk).

### What
User-side upload mimes:
```php
'attachments.*' => 'file|mimes:jpeg,png,jpg,gif,webp,svg,pdf,doc,docx,xls,xlsx,txt,zip,rar|max:10240',
```
- **`svg`** is allowed. SVG is an active document — `<svg><script>...</script></svg>` executes when opened directly. Files are stored on the **public disk** (`store(..., 'public')`) and exposed via `Storage::url()` → an attacker can upload an SVG and get a same-origin URL that, when an admin opens it (the admin UI links/loads attachments by URL — `show.blade.php:873,885`), runs script in the storage origin. `SupportMessageAttachment::isImage()` (`:44`) even treats `image/svg+xml` as an inline image.
- **`zip`/`rar`** allow archive upload with no scanning (decompression/zip-bomb handling is downstream/manual, but they are accepted and stored).
- `mimes:` validates by guessed extension/mime, which is weaker than content sniffing; combined with the unescaped `file_name` in BUG 1 this compounds.
- Admin side is correctly stricter (`jpeg,png,jpg,webp,pdf` only — `Admin/SupportChatController:141`), showing the user side is the gap.

### Impact
Stored XSS / content-injection via SVG served same-origin; unscanned archive storage. No malware/content validation.

### Fix
Drop `svg` (or sanitize/sandbox it, serve with `Content-Disposition: attachment` and a restrictive CSP / separate origin). Validate by real MIME (content sniff). Consider stripping archive types or scanning them. Force download (never inline-render) for user-supplied attachments.

---

## BUG 7 — No effective per-action rate limiting (spam / DoS on send, typing, create, webhook)

- **Severity:** Medium
- **Confidence:** High
- **Type:** Missing rate limiting → DB/storage/email/queue DoS
- **Location:** `routes/api.php:41` (single shared `throttle:300,1` group) covering `:60-66`; Telegram webhook `:166` (no throttle).

### What
All public support-chat endpoints (create, get messages, **send message**, typing, typing/stop, rating) live in one `throttle:300,1` group **shared with** accounts/articles/categories/banners/guest-cart, etc. There is **no dedicated throttle on `sendMessage`** (the expensive path: writes messages + attachments to disk + fires an admin notification each time — `NotifierService::send` at `:319`). 300 requests/min is generous for message sends, and the limiter is global to the whole public group, so high-volume spam is easy:
- Mass message/attachment writes → DB + storage growth (each chat capped at 100 MB but an attacker can spawn unlimited new guest chats with distinct emails).
- Each user message triggers `NotifierService::send('support_chat', ...)` → admin-notification row spam.
- Typing endpoints write Cache keys on every call.

The admin typing endpoints *do* have `throttle:60,1` (`web.php:159-160`), but the public user/guest `sendTyping`/`stopTyping` have none beyond the shared group. The **Telegram webhook has no throttle at all** and is unauthenticated (BUG 4), enabling unbounded chat/message creation.

### Fix
Add tight per-route throttles (e.g. `throttle:10,1` on `sendMessage`, `throttle:30,1` on typing), key by IP+chat or email. Limit number of guest chats per email/IP. Throttle the Telegram webhook.

---

## BUG 8 — Chat attachments publicly downloadable, no ownership check

- **Severity:** Low
- **Confidence:** High
- **Type:** Broken access control on files
- **Location:** `config/filesystems.php:39-44` (`'public'` disk, `'visibility' => 'public'`) and `:73` (`storage:link`); attachments stored at `support-chat/attachments/...` (`Api/SupportChatController:291`, `Admin/SupportChatController:189`).

### What
Attachments are stored on the public disk and served straight from `/storage/...` via the web server — there is **no controller, signed URL, or auth check** mediating download (no route handles attachment download; only `Storage::url()` is generated). Anyone with the URL can fetch any chat's files regardless of chat ownership.

### Mitigating factor
File paths include `uniqid()` / Laravel hashed names, so URLs are not trivially enumerable. But URLs leak (Referer, link sharing, the unescaped `file_url` in admin HTML, browser history), and there is zero authorization once a URL is known — including other users' private support attachments (ID documents, screenshots, etc.).

### Fix
Serve attachments through an authenticated controller that verifies chat ownership (user/guest token/admin), or use short-lived signed URLs. Store on a private disk.

---

## BUG 9 — `getApiUser()` ignores token expiration/abilities

- **Severity:** Low
- **Confidence:** Medium
- **Type:** Weak token validation
- **Location:** `app/Http/Controllers/Controller.php:14-27` (used by `NotificationController`).

### What
```php
$accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
if (!$accessToken) return false;
return $accessToken->tokenable;
```
This resolves the token by hash but does **not** check `expires_at` or abilities, and does not run through Sanctum's guard (which honors `sanctum.expiration`). The notification endpoints (`/notifications`, `/read`, `/read-all`) are inside an `auth:sanctum` group (`api.php:75-82`), so the guard *also* runs — but the controller then re-resolves the user via this weaker helper. If `sanctum.expiration` is configured, an expired-but-not-deleted token would still pass `getApiUser`. Notification ownership itself is correctly scoped (`$user->notifications()` — no IDOR), so impact is limited to token-lifecycle correctness.

### Fix
Use `$request->user()` (the guard-resolved user) instead of the custom helper, or honor `expires_at`/abilities in `getApiUser`.

---

## BUG 10 — Admin `assign` accepts any user id, no assignment guard

- **Severity:** Low
- **Confidence:** Medium
- **Type:** Authorization / business-logic gap
- **Location:** `app/Http/Controllers/Admin/SupportChatController.php:337-349`.

### What
```php
$request->validate(['admin_id' => 'required|exists:users,id']);
$chat->update(['assigned_to' => $request->admin_id]);
```
Unlike `show`/`sendMessage`/`updateStatus`, `assign` has **no `assigned_to != admin && !is_main_admin` guard**, and `admin_id` is validated as any `users.id` — **not** restricted to `is_admin`/`is_main_admin`. A regular admin can reassign a chat already owned by another admin to themselves (bypassing the ID-enumeration protection that the other methods enforce), or assign it to a non-admin user id. It is inside the admin group so not exposed to customers, but it undermines the documented per-admin access-control model.

### Fix
Add the same assignment guard used elsewhere, and restrict `admin_id` to admins (`exists:users,id` + `where is_admin/is_main_admin`).

---

## Ruled out (checked, not vulnerable)

- **Broadcast channel auth (`channels.php`)** — only `App.Models.User.{id}` is registered, with `(int)$user->id === (int)$id` (`:16-18`). Correct ownership check; no chat broadcast channel exists (polling only). **No issue.**
- **Authenticated-user chat IDOR** — `getMessages`/`sendMessage`/`addRating`/typing all enforce `chat.user_id === user.id` for token users (`:139, 260, 360, 433, 457, 481`). `getChats` scopes to `user_id` (`:412`). Correct.
- **Notification IDOR (user)** — `index`/`markNotificationsAsRead`/`markAllAsRead` all operate on `$user->notifications()` and additionally `whereIn ids` + `whereNull read_at` (`NotificationController:20, 73-76, 88-90`). Cannot read/mark another user's notifications. Correct.
- **Supplier notification IDOR** — `markAsRead` uses `where('user_id', auth()->id())->firstOrFail()`; `index`/`markAllAsRead`/`getUnreadCount` use `auth()->user()->supplierNotifications()` (`Supplier/NotificationController`). Correctly scoped. **No issue.**
- **Admin notifications shared-row "IDOR"** — `AdminNotification` is a single shared row per event (by design); `read/{id}`/`destroy` operate on the shared row inside the `admin.auth` group. Not a per-user resource, so not an IDOR. (`read/{id}` being a `GET` is a minor CSRF-style nit but only flips a shared read flag.)
- **Admin chat note delete** — `deleteNote` verifies `note.support_chat_id === chat.id` (404 otherwise) and `note.user_id === admin.id || is_main_admin` (403 otherwise) (`:428-435`). Correct.
- **Admin chat message/status access** — `show`/`sendMessage`/`updateStatus`/`getMessages` enforce the `assigned_to`/`is_main_admin` guard (`:79, 134, 360, 471`). Correct (except `assign`, BUG 10).
- **SQL injection** — all queries use Eloquent/bindings; `index` filters (`status`, `assigned_to`, `source`) go through `where()` bindings. No raw interpolation. **No issue.**
- **Email header injection** — recipient is `$user->email` (DB) or the guest `email` (validated `email` rule in chat create / guest checkout); subject/body come from DB templates, not request headers. Laravel Mailer encodes headers. No direct user-controlled header injection found.
- **SSTI / placeholder injection in templates** — `renderTemplate` (notifier `:96-103`, email `:164-175`, notification `:50-57`) is plain `str_replace`, not an expression evaluator — no template-engine execution. Variable *values* can still carry HTML (that's BUG 3), but there is no server-side code execution.
- **SMTP credential exposure** — `configureMailFromOptions` logs config **without** the password (`:134-140`) and decrypts the stored password in memory only. No creds leaked to logs/responses. **No issue.**
- **`AdminNotification` formatted-title/message XSS** — output is rendered in the admin Blade dropdown; verified the support-chat views use `{{ }}` for these (no `{!! !!}` in `resources/views/admin/support-chats/`). The heavy normalizer strips `:var` placeholders but does not introduce a sink. (Admin dropdown Blade not in scope file-set but no `{!! !!}` found.)
- **`SupportMessageReaction`** — model exists but no controller/route/UI creates or reads reactions (confirmed: no reaction endpoints). Dead scaffolding; no live attack surface. **No issue (currently).**
- **Attachment `deleting` boot hook** — deletes the physical file from the `public` disk on row delete (`SupportMessageAttachment:76-81`); path comes from DB `file_path` set at upload time (no user-controlled path traversal — `store()` generates the name). No traversal. **No issue.**
- **Decompression bomb** — zip/rar are accepted (BUG 6) but never auto-extracted server-side in this domain; no decompression sink found. Storage growth only.
