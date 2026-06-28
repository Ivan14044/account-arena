# 08 — Support Chat & Notifications

Functional inventory for two cross-cutting subsystems of **Account Arena**:

1. **Support Chat** — live chat between users/guests/Telegram users and admins.
2. **Notifications** — in-app user notifications, admin notifications (per-type settings), supplier notifications, notification templates, email templates, and the Telegram delivery channel.

All paths are absolute from the repo root `/Users/gospodin/Desktop/Account Arena`. Line references use `path:line`.

---

## PART A — SUPPORT CHAT

### A.0 Architecture & real-time mechanism

- **No WebSockets / broadcasting.** `backend/routes/channels.php` only registers the default `App.Models.User.{id}` private channel (channels.php:16) used by Laravel's `Notifiable`. There is **no support-chat broadcast channel** and no `broadcast()` calls in the chat controllers/models. Real-time behavior is achieved entirely by **HTTP polling**.
- **Frontend polling (user/guest widget)**: `frontend/src/components/SupportChatWidget.vue` polls `GET /support-chat/{id}/messages` every **3 seconds** while the chat window is open (`startPolling`, SupportChatWidget.vue:1107-1114). The same response also carries the admin "is typing" flag, so no separate typing poll is needed (SupportChatWidget.vue:1007-1009).
- **Admin polling**: the admin Blade view polls `GET admin/support-chats/{id}/messages?last_message_id=N` for new messages and `GET .../typing/user-status` for the user typing flag (controller methods `getMessages`, `getUserTypingStatus`).
- **Typing indicators are stored in Cache**, not the DB — see A.8.
- **Two data sources / channels** for a chat: `website` (the Vue widget) and `telegram` (Telegram bot webhook). Differentiated by `SupportChat.source` (`SOURCE_WEBSITE` / `SOURCE_TELEGRAM`, SupportChat.php:40-41).

### A.1 Data model

**`SupportChat`** — `backend/app/Models/SupportChat.php`
- Fillable (SupportChat.php:14-26): `user_id`, `guest_email`, `guest_name`, `status`, `assigned_to`, `last_message_at`, `rating`, `rating_comment`, `rated_at`, `source`, `telegram_chat_id`.
- Casts (SupportChat.php:28-32): `last_message_at`/`rated_at` → datetime, `rating` → integer.
- Status constants (SupportChat.php:35-37): `open`, `closed`, `pending`.
- Source constants (SupportChat.php:40-41): `website`, `telegram`.
- Relations: `user()` (SupportChat.php:46), `assignedAdmin()` (FK `assigned_to`, :54), `messages()` ordered asc (:62), `lastMessage()` via `latestOfMany()` (:70), `notes()` (:78).
- Accessors: `email`/`name` resolve from the user or guest fields (SupportChat.php:94-105).
- Helpers: `isGuest()` (`user_id === null`, :86), `isFromTelegram()` (:142), `getUnreadMessagesCount()` cached 60s (:150), `clearUnreadCountCache()` (:168), `getTotalAttachmentsSize()` sums all attachment bytes in the chat (:177).
- Scopes: `active` (not closed, :110), `notClosed` (open|pending, :118), `fromTelegram` (:126), `fromWebsite` (:134).
- Typing helpers `setTyping`/`isTyping`/`stopTyping` — see A.8.

**`SupportMessage`** — `backend/app/Models/SupportMessage.php`
- Fillable (:14-22): `support_chat_id`, `user_id`, `sender_type`, `message`, `telegram_message_id`, `is_read`, `read_at`.
- Casts: `is_read` → bool, `read_at` → datetime, `telegram_message_id` → int.
- Sender constants (:31-33): `user`, `admin`, `guest`.
- Relations: `chat()`, `user()`, `attachments()`, `reactions()`.
- `markAsRead()` (:70), scopes `unread` (:105) and `fromUserOrGuest` (sender in user|guest, :113).

**`SupportMessageAttachment`** — `backend/app/Models/SupportMessageAttachment.php`
- Fillable: `support_message_id`, `file_name`, `file_path`, `file_url`, `mime_type`, `file_size`.
- `getFullUrlAttribute()` falls back to `Storage::url(file_path)` (:34).
- `isImage()` matches jpeg/png/gif/webp/svg (:42).
- `getFormattedSizeAttribute()` → human-readable size (:51).
- **`deleting` boot hook deletes the physical file from the `public` disk** (:72-82) — deleting an attachment row removes the file.

**`SupportMessageReaction`** — `backend/app/Models/SupportMessageReaction.php`
- Fillable: `support_message_id`, `user_id`, `emoji`, `reaction_type`, `reaction_identifier`. Relations `message()`, `user()`.
- **Note:** the model exists and `SupportMessage::reactions()` is defined, but **no controller/route/UI currently creates or reads reactions** in this codebase. It is scaffolding only — no reaction feature is wired up.

**`SupportChatNote`** — `backend/app/Models/SupportChatNote.php`
- Fillable: `support_chat_id`, `user_id`, `note`. Relations `chat()`, `user()` (the admin author). Internal admin-only notes (see A.7).

### A.2 Get-or-create chat

- **Name:** Get or create a support chat (guest by email or authed user).
- **Route:** `POST /api/support-chat/create` (public group `throttle:300,1`, api.php:60).
- **File:** `backend/app/Http/Controllers/Api/SupportChatController.php:19-127` (`getOrCreateChat`).
- **Auth resolution:** `$request->user('sanctum')` — token optional, so the same endpoint serves authed users and guests.
- **Inputs:**
  - Authed: none required (user resolved from token).
  - Guest: `email` (required, email, max 255) and `name` (required, string, max 255) — validated at :49-52; on failure returns `422` with `errors`.
- **Behavior / business rules:**
  - **Authed**: looks for an existing `notClosed` chat for `user_id` (most recent). If found, returns it loaded with messages (asc) + `user` + `assignedAdmin`. Otherwise creates a new chat with `status = pending` (:43-46).
  - **Guest**: same logic keyed on `guest_email`; new chat stores `guest_email`, `guest_name`, `status = pending` (:79-83).
  - **Automatic greeting**: only for a brand-new chat with zero messages, and only if Option `support_chat_greeting_enabled` is truthy (:91-93). Greeting text is read from Option `support_chat_greeting_message_{locale}` with locale taken from `X-Locale` header / `locale` query / app locale, validated against `config('langs')`, **falling back to `_ru`** (:95-104). A greeting is inserted as a `SupportMessage` with `user_id = null`, `sender_type = admin`, `is_read = false` (:108-114).
- **Output:** `{ success: true, chat: {...with messages, user, assignedAdmin} }`.
- **Edge cases:** A closed chat is never reused — a new `pending` chat is created instead (because `notClosed` excludes `closed`). Guest chats with the same email are reused only while not closed.

### A.3 Get messages (user/guest)

- **Route:** `GET /api/support-chat/{chatId}/messages` (api.php:61).
- **File:** `SupportChatController.php:132-182` (`getMessages`).
- **Inputs:** `chatId` (path). Guests must pass `email` query/body param matching `guest_email`.
- **Access control:** authed user must own the chat (`chat.user_id === user.id`); guest's `email` must equal `chat.guest_email`, else `403` (:138-153).
- **Side effect:** marks all unread **admin** messages as read (`is_read=true, read_at=now`) but only if any exist (:160-170).
- **Output:** `{ success, messages:[...with user, attachments], is_typing: chat.isTyping('admin'), chat:{id,status,last_message_at} }`. The `is_typing` field is what drives the admin typing bubble in the widget.
- **Edge case:** Returns full message list every poll (no incremental `since` param on the public endpoint) — the frontend de-dupes/merges and preserves optimistic messages (SupportChatWidget.vue:978-1000).

### A.4 Send message (user/guest)

- **Route:** `POST /api/support-chat/{chatId}/messages` (api.php:62).
- **File:** `SupportChatController.php:187-336` (`sendMessage`).
- **Inputs (validated :189-193):** `message` (nullable string, max 5000), `attachments` (nullable array, max 5), `attachments.*` file with mimes `jpeg,png,jpg,gif,webp,svg,pdf,doc,docx,xls,xlsx,txt,zip,rar`, max 10240 KB (10 MB) each. Guests also pass `email`.
- **Business rules / validation layers:**
  - **Message OR file required** — empty text with no files → `422` (:203-209).
  - **Per-request total size cap 50 MB** across all attachments (:211-225).
  - **Free disk space guard** — must leave ≥100 MB free, else `422` (:227-234).
  - **Closed chat blocks sending** → `403` "Этот чат закрыт" (:241-246).
  - **Per-chat attachment cap 100 MB** (DOS protection) via `getTotalAttachmentsSize()` → `422` (:248-257).
  - **Access control:** authed must own chat; guest email must match — else `403` (:259-278). Sender type = `user` (authed) or `guest`.
- **Attachment handling:** each file stored to `support-chat/attachments` on the `public` disk; a `SupportMessageAttachment` row is created with `file_path`, `file_url` (`Storage::url`), mime, size (:289-303).
- **Post-send effects:**
  - `chat.last_message_at = now()` (:306-308).
  - **Admin notification** fired for non-admin senders via `NotifierService::send('support_chat', 'Новое сообщение в чате поддержки', "...", 'info')` with a 100-char message preview (:311-325). See B.3.
  - Clears the admin-panel unread counter cache key `support_chats_unread_count` (:328).
- **Output:** `{ success, message: {...with user, attachments} }`.
- **Edge cases:** Note the public send endpoint does **not** forward the message to Telegram (only the admin send path does — A.6). User→Telegram-origin chat replies travel back over polling.

### A.5 Chat rating

- **Name:** Rate a closed support chat (1–5 stars + optional comment).
- **Route:** `POST /api/support-chat/{chatId}/rating` (api.php:66).
- **File:** `SupportChatController.php:341-396` (`addRating`).
- **Inputs (validated :343-346):** `rating` (required int 1–5), `rating_comment` (nullable string max 1000). Guests pass `email`.
- **Business rules:**
  - Access control as above (`403` on mismatch).
  - **Chat must be `closed`**, else `403` "Оценить можно только закрытый чат" (:370-375).
  - **One rating only** — if `chat.rating` already set → `403` "Рейтинг уже поставлен" (:378-383).
  - Persists `rating`, trimmed `rating_comment` (or null), `rated_at = now()` (:385-389).
- **Output:** `{ success, message:'Рейтинг добавлен', chat }`.
- **Frontend:** rating UI shown only when `chat.status === 'closed' && !chat.rating` (SupportChatWidget.vue:382-434); thank-you state after submit (:436-467).

### A.6 List user's chats (history)

- **Name:** Get authenticated user's chat history.
- **Route:** `GET /api/support-chats` (auth:sanctum group, api.php:103).
- **File:** `SupportChatController.php:401-421` (`getChats`).
- **Auth:** authed only — `401` "Необходима авторизация" if no user (guests have no history endpoint).
- **Output:** `{ success, chats:[...] }` with `lastMessage` + `assignedAdmin` eager-loaded, ordered by `COALESCE(last_message_at, created_at) DESC`.
- **Frontend:** history panel `loadChatHistory()` (SupportChatWidget.vue:1291), select a past chat via `selectChatFromHistory` which re-fetches that chat's messages (:1334).

### A.7 Admin chat management

Controller: `backend/app/Http/Controllers/Admin/SupportChatController.php`. All routes live under the admin web group (`routes/web.php:152-163`, name prefix `admin.support-chats.*`).

| Feature | Route + method | Method `path:line` | Notes |
|---|---|---|---|
| List chats (paginated, filters) | `GET admin/support-chats` | `index` :23-65 | Filters: `source` (telegram/website tab), `status`, `assigned_to`. `withCount` of unread user/guest messages as `unread_count`. Orders by `last_message_at`, then `created_at`. Paginates 20. |
| Unread badge count | `GET admin/support-chats/unread-count` | `getUnreadCount` :445-459 | Counts unread user/guest messages across non-closed chats. Returns `{count}`. |
| Poll new messages | `GET admin/support-chats/{id}/messages?last_message_id=N` | `getMessages` :464-541 | Returns only messages with `id > last_message_id`; marks new user/guest messages read; clears cache. JSON-mapped payload. |
| View chat | `GET admin/support-chats/{id}` | `show` :70-122 | Marks user/guest messages read **in a DB transaction**; auto-transitions `pending → open` (:95-99); loads notes + last `limit` (10–200, default 50) messages. |
| Send admin reply | `POST admin/support-chats/{id}/message` | `sendMessage` :127-274 | See details below. |
| Assign admin | `POST admin/support-chats/{id}/assign` | `assign` :337-349 | `admin_id` required & `exists:users`. Sets `assigned_to`. |
| Update status | `POST admin/support-chats/{id}/status` | `updateStatus` :354-395 | `status in open,closed,pending`. On close, inserts a system message (see below). |
| Add note | `POST admin/support-chats/{id}/notes` | `addNote` :400-416 | `note` required max 2000. |
| Delete note | `DELETE admin/support-chats/{id}/notes/{noteId}` | `deleteNote` :421-440 | Only note author or `is_main_admin`. 404 if note not in chat. |
| Admin typing start/stop | `POST .../typing`, `POST .../typing/stop` | `sendTyping`/`stopTyping` :279-308 | `throttle:60,1`. Cache-based (A.8). |
| User typing status | `GET .../typing/user-status` | `getUserTypingStatus` :313-332 | Returns `is_typing = isTyping('user') OR isTyping('guest')`. |

**Assignment / access-control rules (ID-enumeration protection):**
- In `show`, `sendMessage`, `updateStatus`, `getMessages`: if `chat.assigned_to` is set and it is **not** the current admin and the admin is **not** `is_main_admin`, access is denied (redirect with error / `403`). See :79-81, :134-136, :360-362, :471-473.

**Admin `sendMessage` specifics (:127-274):**
- Validation: `message` nullable ≤5000; attachments max 5; `attachments.*` mimes restricted to **`jpeg,png,jpg,webp,pdf` only** (stricter than the user side), 10 MB each (:138-142).
- Per-chat attachment cap **200 MB** (higher than the 100 MB user cap) (:144-157); free-disk guard ≥500 MB (:159-163).
- Message + attachments created in a **DB transaction** (:170-206); attachment files stored under `support-chat/attachments/Y/m` on `public` disk.
- **Telegram forwarding:** if `chat.isFromTelegram()` and `telegram_chat_id` present, calls `TelegramBotService::sendMessage()` with text + attachments (:213-259). Guards: empty content, invalid chat id, and Option `telegram_client_enabled` must be on; failures are logged and surfaced via flash `telegram_send_error` but the message is still saved.
- Stops admin typing indicator (:262), sets `last_message_at = now()` and `status = open` (:265-268), clears unread cache.
- `sender_type = admin`, `user_id = admin.id`, `is_read = false`.

**`updateStatus` close behavior:** when transitioning into `closed` (from non-closed), within a transaction it inserts a system admin message "Диалог закрыт администратором. Если у вас появятся новые вопросы — создайте новый диалог." and bumps `last_message_at` (:376-388).

### A.8 Typing indicators (storage & polling)

- **Storage = Laravel Cache, not DB.** `SupportChat::setTyping($type, $userId)` writes two keys with a **5-second TTL** (SupportChat.php:187-200):
  - per-user key `chat_typing_{chatId}_{type}_{userId}`,
  - group key `chat_typing_group_{chatId}_{type}` (enables O(1) "is anyone of this type typing?" checks).
- `isTyping($type)` = `Cache::has("chat_typing_group_{chatId}_{type}")` (:205-208).
- `stopTyping()` forgets only the per-user key; the **group key is intentionally left to expire** after 5s (acceptable race for performance) (:213-223).
- **User/guest side** (`Api/SupportChatController`): `sendTyping` (:426), `stopTyping` (:450), `getTypingStatus` (:474) → admin typing. Guests use a shared `'guest'` type key (no user id).
- **Admin side**: types `'admin'` (with admin id), checked by users via group key.
- **Frontend throttling**: widget sends a typing event at most every 2s and auto-stops after 3s of inactivity (`sendTypingEvent`, SupportChatWidget.vue:1044-1078).

### A.9 Unread counts (chat)

- **Per-chat (admin):** `SupportChat::getUnreadMessagesCount()` counts unread user/guest messages, cached 60s under `chat_unread_count_{id}` (SupportChat.php:150-163). `index` instead uses a `withCount` subquery aliased `unread_count`.
- **Global admin badge:** `getUnreadCount()` counts unread user/guest messages across non-closed chats (Admin controller :445-459). Cache key `support_chats_unread_count` is invalidated on every user send and admin read (`clearUnreadCountCache`).
- **User/guest widget unread:** computed client-side from loaded messages (unread admin messages / messages not authored by the user), not a server count (SupportChatWidget.vue:1133-1174). Drives the floating-button badge + notification sound + browser Notification.

### A.10 Telegram-as-chat-channel

- **Inbound:** `POST /api/telegram/webhook` → `TelegramWebhookController::handle` (api.php:166) → `TelegramBotService::processIncomingMessage($update)` (TelegramBotService.php:339-411).
  - Validates the update has `chat`, `from`, `chat.id`, `message_id`; skips empty messages without attachments.
  - **Dedup** on `telegram_message_id` (only bumps `last_message_at` if message already imported) (:365-372).
  - Resolves a registered `User` by `telegram_id`; otherwise treats as guest. `findOrCreateSupportChat` looks up an existing non-closed chat by `telegram_chat_id` + `source=telegram`, else creates a new chat with `source = telegram`, `telegram_chat_id`, guest display name (:450-471).
  - Creates a `SupportMessage` (`sender_type` = user if linked account, else guest), imports photo/document attachments, sets chat `status = open`, `last_message_at = now()`.
- **Outbound:** admin replies on a telegram-origin chat are pushed back via `TelegramBotService::sendMessage()` from the admin `sendMessage` flow (A.7).
- These chats appear under the admin "telegram" source tab (`index` filter, A.7).

---

## PART B — NOTIFICATIONS

Account Arena has **four** distinct notification mechanisms plus two delivery channels:

| Mechanism | Audience | Stored in | Surfaced by |
|---|---|---|---|
| In-app user notifications | Registered customers | `notifications` table (`Notification`) | Vue `NotificationBell` (polls API) |
| Admin notifications | Admins / main admins | `admin_notifications` (`AdminNotification`) | Admin Blade bell dropdown |
| Supplier notifications | Suppliers | `supplier_notifications` (`SupplierNotification`) | Supplier Blade pages |
| Email (transactional) | Users + guests | none (queued/sent mail) | SMTP |
| Telegram | Support-chat users | n/a | Bot (chat only — see note) |

### B.1 In-app USER notifications

**Model `Notification`** — `backend/app/Models/Notification.php`
- Fillable: `user_id`, `notification_template_id`, `read_at`, `variables`. Casts: `read_at` → datetime, `variables` → array.
- Relations: `user()`, `template()` (FK `notification_template_id`). Scope `unread` (`whereNull read_at`).
- A notification stores **only a template reference + a variables map**; the human-readable text is rendered at display time from the template translations.

**Creation** — `NotificationTemplateService::sendToUser($user, $templateCode, $variables)` (`backend/app/Services/NotificationTemplateService.php:20-41`):
- Looks up `NotificationTemplate` by `code`; if missing, logs a warning and returns `null` (no row created).
- Creates `Notification` with `user_id`, `notification_template_id`, `variables`. (It does **not** pre-render text; `render()` at :50 exists but is unused here.)
- **Known call sites / types** (from the call-site sweep): `registration` (`AuthController`), `purchase` (Cart/Mono/Cryptomus checkout, var `order_number`), `dispute_resolved` (`ProductDispute`), and manual-delivery lifecycle codes `manual_delivery_completed`, `manual_delivery_out_of_stock`, `manual_delivery_order_created`, `manual_delivery_processing_error` (`ManualDeliveryService`).

**Read API** — `backend/app/Http/Controllers/NotificationController.php` (auth via bearer token resolved by `getApiUser`, `Controller.php:14-27`; routes in the `auth:sanctum` group, api.php:80-82):

| Feature | Route + method | `path:line` | Inputs | Output / rules |
|---|---|---|---|---|
| List notifications | `GET /api/notifications` | `index` :10-62 | `limit` (≤100, default 10), `offset` (≥0) | `{ total, unread, items[] }`. Each item: `{id, template:{id, variables, translations{locale:{title,message}}}, read_at, created_at}`. **Notifications whose template is null are skipped + logged** (:35-38). |
| Mark some read | `POST /api/notifications/read` | `markNotificationsAsRead` :64-79 | `ids[]` (validated by `MarkAsReadRequest`) | Sets `read_at=now()` for the user's matching, still-unread ids. |
| Mark all read | `POST /api/notifications/read-all` | `markAllAsRead` :81-93 | — | Sets `read_at=now()` on all the user's unread notifications. |

- All three return `401 {message:'Invalid token'}` if the bearer token does not resolve to a user.
- **Translations are returned per-locale**; the **frontend** does variable substitution: `getTranslation()` replaces `:var` tokens using `item.template.variables`, choosing `locale` then falling back to `en` (`NotificationBell.vue:358-368`).

**Frontend** (`frontend/src/stores/notifications.js` + `frontend/src/components/layout/NotificationBell.vue`):
- Pinia store holds `items/total/unread/isLoaded`; `fetchData(limit=3)`, `markNotificationsAsRead(ids)`, `markAllAsRead()`, `fetchChunk(limit,offset)` for "load more", `resetStore()`.
- Bell **polls every 10s** (`POLLING_INTERVAL`, NotificationBell.vue:162, 378-389), initial limit 3, plays a sound + bounce animation when `unread` increases (:418-431). Optimistic local read updates with background API sync (:257-328). "Mark all" button appears when `unread > 2`.

### B.2 NotificationTemplate (multilingual user-notification templates)

- **Model** `NotificationTemplate` — `backend/app/Models/NotificationTemplate.php`. Fillable: `code`, `name`, `is_mass`. `TRANSLATION_FIELDS = ['title','message']`. `translations()` hasMany. Uses `HasTranslations` trait.
- **Model** `NotificationTemplateTranslation` — fillable `notification_template_id`, `locale`, `code`, `value` (one row per locale × field).
- **`HasTranslations::saveTranslation()`** (`backend/app/Traits/HasTranslations.php`): deletes existing translations, then upserts a row per `(locale, code)` for each non-empty value across `TRANSLATION_FIELDS`.
- **Variables:** placeholders use the **`:name` colon syntax** in template `value`s; substituted by `NotifierService::renderTemplate` (admin side) or the frontend (user side).
- **Admin CRUD:** `backend/app/Http/Controllers/Admin/NotificationTemplateController.php`, resource routes `admin/notification-templates` (index/create/store/edit/update/destroy, web.php:64). The `notifier.php` lang file (`backend/resources/lang/{ru,en,uk}/notifier.php`) holds legacy/string-based templates and the `types` map (human labels for notification types).

### B.3 ADMIN notifications + per-type settings

**Model `AdminNotification`** — `backend/app/Models/AdminNotification.php`
- Fillable: `type`, `title`, `status`, `message`, `read`. `markAsRead()`, `isRead()`.
- A **single shared row serves all admins** (not per-admin) — created once per event.
- `getFormattedTitleAttribute()` / `getFormattedMessageAttribute()` (:31-266) are heavy legacy normalizers: they detect old English/placeholder titles, force-translate via `notifier.*` keys (always falling back to `ru`), strip leftover `:var` placeholders and empty brackets, and de-duplicate repeated text. Used by the admin dropdown to render clean labels.

**Dispatch — `NotifierService`** (`backend/app/Services/NotifierService.php`):
- **`send($type, $title, $message, $status='danger')`** (:17-44): loads all admins (`is_admin` OR `is_main_admin`); if **at least one** admin has this `type` enabled (via `AdminNotificationSetting`), creates **one** `AdminNotification`. So the per-type setting gates whether the shared row is created at all, not per-recipient visibility.
- **`sendFromTemplate($type, $templateCode, $variables, $status)`** (:49-91): loads a `NotificationTemplate` by code, picks translations for current locale (ru/en/uk, else ru), falls back to ru, renders `:var` placeholders via `renderTemplate` (:96-103), then calls `send()`. Logs + returns if template/translations missing.
- **Admin-notification call sites & types** (from the sweep): `support_chat` (chat — A.4), `balance_topup` (Mono/Cryptomus top-ups), `dispute_created` (`ProductDisputeController`), `manual_delivery_cancelled` / `manual_delivery_error` / `manual_delivery_new_order` (`ManualDeliveryService`), `low_stock` (`ProductPurchaseService`), `stock_auto_fulfilled` / `overdue_manual_order` (console commands), `product_purchase` / `guest_product_purchase` (checkout), `registration` (`AuthController`), and supplier-product moderation types.

**Model `AdminNotificationSetting`** — `backend/app/Models/AdminNotificationSetting.php`
- Per-admin (`user_id`) boolean toggles (all cast bool): `registration_enabled`, `product_purchase_enabled`, `dispute_created_enabled`, `payment_enabled`, `topup_enabled`, `support_chat_enabled`, `manual_delivery_enabled`, `low_stock_enabled`, plus `sound_enabled` (UI sound).
- `getOrCreateForUser($userId)` (:49-86): firstOrCreate with everything defaulting **true**; back-fills NULL `manual_delivery_enabled` / `low_stock_enabled` for legacy rows.
- `isEnabled($type)` (:91-95) maps a notifier `type` to its `*_enabled` column via `getFieldName()` (:100-114); **defaults to enabled** for unmapped/unknown types (e.g. `balance_topup`, `manual_delivery_new_order`, moderation types are not in the map and therefore always pass the gate).

**Admin read API** — `backend/app/Http/Controllers/Admin/AdminNotificationController.php` (routes web.php:114-120, name `admin.admin_notifications.*`):

| Feature | Route + method | `path:line` | Notes |
|---|---|---|---|
| Bell dropdown (poll) | `GET admin_notifications/get` | `get` :10-59 | Latest 5; computes relative time (s/m/h/d); renders Blade dropdown; returns `{label:unreadCount, dropdown, sound_enabled, has_new}` honoring the admin's `sound_enabled` setting. |
| Index (paginated) | resource `index` | `index` :61-66 | 50/page. |
| Mark one read | `GET admin_notifications/read/{id}` | `read` :68-74 | `markAsRead()`, redirect to index. |
| Mark all read | `POST admin_notifications/read-all` | `readAll` :76-83 | bulk `update(['read'=>true])`. |
| Delete | resource `destroy` | `destroy` :85-93 | hard delete. |

- **Per-type settings UI** is handled through the admin settings form (`SettingController`, `form=notification_settings`), not a dedicated controller; there is no standalone route for editing `AdminNotificationSetting`.

### B.4 SUPPLIER notifications

**Model `SupplierNotification`** — `backend/app/Models/SupplierNotification.php`
- Fillable: `user_id`, `type`, `title`, `message`, `data`, `is_read`, `read_at`. Casts: `data` → array, `is_read` → bool, `read_at` → datetime.
- `markAsRead()`; scopes `unread`, `forUser($id)`, `byType($type)`.
- These are **per-supplier rows** with literal title/message text (not template-based) and an optional `data` JSON payload. `User::supplierNotifications()` hasMany (`User.php:68`).

**Controller** — `backend/app/Http/Controllers/Supplier/NotificationController.php` (routes web.php:197-200, name `supplier.notifications.*`):

| Feature | Route + method | Notes |
|---|---|---|
| List | `GET supplier/notifications` (`index`) | own notifications, latest, paginate 20. |
| Mark one read | `POST supplier/notifications/{id}/mark-read` (`markAsRead`) | scoped to `user_id = auth id` (`firstOrFail`). |
| Mark all read | `POST supplier/notifications/mark-all-read` (`markAllAsRead`) | bulk update unread → read. |
| Unread count | `GET supplier/notifications/unread-count` (`getUnreadCount`) | `{count}`. |

- **Creation** is direct `SupplierNotification::create([...])` at the call sites (no dedicated service). Known types from the sweep: `product_dispute` (`ProductDispute`), `withdrawal_approved`/`withdrawal_paid`/`withdrawal_rejected` (`WithdrawalRequestController`), `product_approved`/`product_rejected` (`ProductModerationController`).

### B.5 EMAIL templates & sending

**Models:** `EmailTemplate` (`backend/app/Models/EmailTemplate.php`, fillable `code`,`name`; `TRANSLATION_FIELDS=['title','message']`; uses `HasTranslations`) and `EmailTemplateTranslation` (fillable `email_template_id`,`locale`,`code`,`value`).
- **Variable syntax differs from notifications:** email templates use **`{{name}}` double-brace** placeholders.
- Admin CRUD: `backend/app/Http/Controllers/Admin/EmailTemplateController.php`.

**Service `EmailService`** — `backend/app/Services/EmailService.php`:
- **`send($templateCode, $userId, $params)`** (:19-45): loads the user, sets locale to `user.lang ?? 'en'`, fetches translation, configures SMTP, renders subject/body, and **`Mail::to(...)->queue(new BaseMail($subject,$body))`** (queued). Returns bool; exceptions are `report()`ed and return `false`.
- **`sendToGuest($email, $templateCode, $params)`** (:50-81): uses Option `default_lang` (fallback `en`) for locale; renders and **sends synchronously** via `Mail::send('emails.base', ...)`.
- **`getTemplateTranslation($code, $locale)`** (:83-102): loads the template's translation for `$locale`; **falls back to `en`** if none; returns `null` if still missing (causing the send to throw/return false).
- **`configureMailFromOptions()`** (:104-162): builds a dynamic SMTP mailer from Options (`smtp_host/port/encryption/username/password/verify_peer`, `smtp_from_address/name`). **Decrypts the stored password** (tolerates legacy plaintext), requires host/port/username/password (throws if incomplete), normalizes encryption to `tls|ssl|null`, then sets `mail.default = dynamic`.
- **`renderTemplate($text, $params)`** (:164-175): `{{key}}` → value substitution.
- **Note:** the referenced `App\Mail\BaseMail` class and the `emails.base` view were not found under `app/Mail/` in the current tree (the `Mail` directory is absent), so the actual mailable/template asset lives elsewhere or is pending — worth flagging.
- **Known email call sites / template codes** (sweep): `payment_confirmation`, `product_purchase_confirmation`, `guest_purchase_confirmation`, and manual-delivery codes (`manual_delivery_completed`, `_out_of_stock`, `_order_created`, `_processing_error`) from Cart/Mono/Cryptomus checkout and `ManualDeliveryService`.
- Lang file `email.php` (`resources/lang/{ru,en,uk}/email.php`) holds shared chrome strings only (`button`, `signature`, `team`, `reset_password`) — the per-message subject/body live in the DB `EmailTemplate` translations.

### B.6 Channel summary — what fires where

There is **no single unified dispatcher**; each event explicitly calls the mechanism(s) it needs. A typical checkout fans out to: `NotificationTemplateService::sendToUser('purchase', …)` (in-app user), `NotifierService::sendFromTemplate('product_purchase', 'admin_product_purchase', …)` (admin), and `EmailService::send('payment_confirmation', …)` (email). Telegram is **not** an admin-notification channel — `TelegramBotService` is used solely for the support-chat bot integration (inbound webhook + outbound admin replies), not to push admin/user notifications.

---

## Key files index

Support chat:
- `backend/app/Http/Controllers/Api/SupportChatController.php`
- `backend/app/Http/Controllers/Admin/SupportChatController.php`
- `backend/app/Models/{SupportChat,SupportMessage,SupportMessageAttachment,SupportMessageReaction,SupportChatNote}.php`
- `backend/app/Services/TelegramBotService.php`, `backend/app/Http/Controllers/TelegramWebhookController.php`
- `frontend/src/components/SupportChatWidget.vue`
- Routes: `backend/routes/api.php:60-66,103,166`, `backend/routes/web.php:152-163`
- `backend/routes/channels.php` (no chat broadcast channel)

Notifications:
- `backend/app/Http/Controllers/NotificationController.php`
- `backend/app/Http/Controllers/Admin/{AdminNotificationController,NotificationTemplateController,EmailTemplateController}.php`
- `backend/app/Http/Controllers/Supplier/NotificationController.php`
- `backend/app/Models/{Notification,NotificationTemplate,NotificationTemplateTranslation,AdminNotification,AdminNotificationSetting,SupplierNotification,EmailTemplate,EmailTemplateTranslation}.php`
- `backend/app/Services/{NotifierService,NotificationTemplateService,EmailService}.php`
- `backend/app/Traits/HasTranslations.php`
- `backend/resources/lang/{ru,en,uk}/{notifier,email}.php`
- `frontend/src/stores/notifications.js`, `frontend/src/components/layout/NotificationBell.vue`
- Routes: `backend/routes/api.php:80-82`, `backend/routes/web.php:64-65,114-120,197-200`
