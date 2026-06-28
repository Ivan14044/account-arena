# Account Arena — План ремедиации (фаза рефакторинга/фиксов)

> Контекст: окружение без PHP/Composer/Node — собрать/прогнать тесты нечем.
> Поэтому правки разделены на **(A) применённые сейчас** (проверенно-безопасные,
> backward-compatible) и **(B) требующие рантайма** (могут сломать рабочие потоки,
> нужна верификация на поднятом стенде перед деплоем).
>
> Все баги и их `path:line` — в `docs/bugs/00-overview.md` и `docs/bugs/01..10`.

---

## A. Применено на ветке `refactor/security-hardening`

| ID | Фикс | Файлы | Риск | Примечание |
|---|---|---|---|---|
| C2 | `/browser/*` переведены под `auth:sanctum` + `throttle:60,1` (были полностью открыты) | `routes/api.php` | низкий | фронт ходит через авторизованный axios |
| C3 | `BrowserController::new` проверяет владение профилем (только из своих `completed`-покупок) | `app/Http/Controllers/Api/BrowserController.php` | низкий | fail-closed: 403/404 при отсутствии покупки |
| C7 | `admin.main` на `settings` (index/store/test-smtp) и `site-content` | `routes/web.php` | низкий | обычные админы теряют доступ к секретам/SSRF (так и задумано) |
| H4 | Проверка `secret_token` Telegram-вебхука + регистрация секрета в `setWebhook` | `TelegramWebhookController.php`, `TelegramBotService.php`, `config/services.php` | нулевой | включается только при заданном `TELEGRAM_WEBHOOK_SECRET` |
| M11 | `/contents/{code}` обёрнут в `throttle:300,1` | `routes/api.php` | нулевой | был вне throttle (DoS) |
| §7 | Исправлен ложный doc-комментарий о «переплате поставщику» | `app/Models/ServiceAccount.php` | нулевой | переплаты НЕТ; ошибка была только в комментарии |

### Действия для деплоя A
- **H4:** задать `TELEGRAM_WEBHOOK_SECRET` в `.env` и **повторно вызвать setWebhook** (иначе старый вебхук без секрета продолжит работать — обратная совместимость сохранена; новый секрет начнёт требоваться только после re-setWebhook).
- **C3:** проверить на стенде, что фронт реально шлёт `profile` = `service_accounts.profile_id` купленного товара и статус покупки `completed`.

---

## B. Требуют рантайма / координации (НЕ применять вслепую)

### B1. CSRF (C1) — НЕ просто убрать `'*'`
`VerifyCsrfToken::$except = ['*']` отключает CSRF для всех web-роутов (админка/кабинет — сессионные → классическая мишень).
**Почему нельзя слепо:** в layout админки **нет `<meta name="csrf-token">`**, а админка активно шлёт AJAX (bulk-действия, чат, споры, sort-order). Включение CSRF без проводки токена даст массовые **419** на этих AJAX.
**Правильный фикс (по шагам):**
1. `$except` оставить только для внешних POST без токена: `auth/telegram/callback` (web). API/вебхуки и так в `api`-группе (CSRF к ним не применяется).
2. Добавить `<meta name="csrf-token" content="{{ csrf_token() }}">` в `admin/layouts/*` и `supplier/layouts/*`.
3. Глобально слать заголовок: `$.ajaxSetup({headers:{'X-CSRF-TOKEN': metaToken}})` / `fetch` обёртка / axios `X-CSRF-TOKEN`.
4. Прогнать каждую AJAX-операцию админки и кабинета.

### B2. CORS (C8)
`allowed_origins:['*']` + `supports_credentials:true`. Заменить `*` на явный whitelist доменов фронта. Проверить, что SPA-домен(ы) перечислены, иначе сломается прод-витрина. (Браузеры и так блокируют credentialed-чтение при `*`, но мисконфиг убрать.)

### B3. Идемпотентность вебхуков / двойная выдача (C6) — НЕ unique на `transaction_id`
⚠️ Мульти-товарный заказ создаёт НЕСКОЛЬКО `purchases` с одним `transaction_id` → `unique(transaction_id)` сломает легитимные заказы.
**Правильный фикс:** в `MonoController`/`CryptomusController` оборачивать доставку в `DB::transaction` + `Transaction::lockForUpdate()` и идемпотентный флаг (например, переход статуса `processing→completed`/`delivered_at`), повторный вебхук видит «уже доставлено» и выходит. Тот же приём — для top-up (H10), убрав 24-часовое окно `findDuplicateTransaction`.

### B4. Лимиты промокодов/споры (H7, H15) — композитные ключи
- `promocode_usages`: уникальность на `(promocode_id, order_id)` (а не `order_id` индекс), запись под локом; для гостей — лимит по email/IP fallback.
- `product_disputes`: уникальность на `(transaction_id, service_account_id)` (НЕ только `transaction_id` — мульти-товар), + проверка статуса транзакции в `store` (сейчас её нет, в отличие от `canDispute`).
Все — миграции с предварительной чисткой дублей; обязательно проверить на копии прод-данных.

### B5. Сквозная санитизация XSS (C5, H2, H3, H6, M6, M7)
1. Бэкенд: санитайзер HTML при ЗАПИСИ всех rich-text полей (описания товаров, контент, статьи, сообщения чата, имена файлов) — например `mews/purifier` (HTMLPurifier). Заменить самописные regex-санитайзеры.
2. Фронт: добавить `dompurify`, оборачивать ВСЕ `v-html` (`AccountDetail.vue:461,493`, `ProductCard.vue:119`, статьи, контент, уведомления, баннеры).
3. Админ-чат `show.blade.php`: live-поллер — экранировать (`textContent`/escape), не `innerHTML` с `${message.message}`/`${file_name}`.
4. SSR (`SpaController::generateProductContent`, `seo/article.blade.php`): экранировать/санитизировать; убрать `'unsafe-inline'` из CSP.
Нужен `npm install` + сборка фронта.

### B6. Social-login takeover (C4)
Не принимать `email` из Telegram-payload для линковки; линковать только по провайдер-идентификатору (`telegram_id`/Google `sub`); требовать verified-email у Google; добавить OAuth `state`-nonce. Не принимать `?token=` из произвольного URL (`AuthCallback.vue`). Требует прогона OAuth/Telegram-флоу.

### B7. Логика возвратов/споров/модерации
- **H11 refund-and-keep:** при refund отзывать выданные креды/ротация/блок доступа к аккаунту.
- **H5 reset moderation on edit:** значимые правки товара → `pending` + `is_active=false`.
- **M9 replacement:** сверять, что заменяющий товар того же `service_id`/`supplier_id` и не дороже.
- **M10 auto-close:** убрать hardcoded `resolved_by=1`, брать актуальный `refund_amount`, защитить `notifySupplier()` от null (удалённый товар не должен откатывать refund).
- **M12 `deduct_from_supplier`:** учитывать флаг (сейчас игнорируется).

### B8. Email-инфраструктура (C9)
Добавить отсутствующий `app/Mail/BaseMail.php` + view `emails.base`, либо переписать `EmailService::send` на существующий mailable. Иначе все письма зарегистрированным юзерам молча падают.

### B9. Кэш каталога (M1, M2)
Инвалидаторы чистят мёртвые `_v1/_v2/_v3`, а читается `active_accounts_list_v4` / `similar_products_v2_*`. Привести ключи в соответствие (или централизовать в одном хелпере). `getSimilarProducts` — добавить фильтр `moderation_status=approved OR supplier_id IS NULL`.

### B10. Прочее
- `extension`-токен (`sc_auth`): проверять ability в middleware, cookie httpOnly+secure, срок жизни/ротация (H13).
- Audit-log: не писать секреты/`account_data`, логировать только на 2xx (M3).
- Open redirect логина (M4): валидировать `redirect` (same-origin).
- JSON-LD: `JSON_HEX_TAG` (M5).
- User enumeration (Low): не использовать `exists:users,email` в forgot/reset; не сбрасывать throttle.
- Dead code: `SupportMessageReaction`, `extractKeywords()`, `EmptyLayout`, осиротевшие Lottie-ассеты — удалить.
- **НЕ трогать** `TrustProxies` (H14 — ложная находка: `$proxies=null` = доверять никаким = безопасно; для работы за nginx нужно, наоборот, указать доверенные прокси).

---

## C. Ложные/переоценённые находки (зафиксировано, чтобы не «чинить» зря)
- **Переплата поставщику (отчёт 04 BUG-01)** — ложь; код платит поставщику его базовую цену. Исправлен только комментарий.
- **TrustProxies «доверяет всем» (H14)** — инверсия; `null` = безопасно.
- **CORS (C8)** — реальный мисконфиг, но эксплуатируемость ниже заявленной из-за поведения браузеров.

---

*Порядок работ B: B1→B2 (конфиг) → B8 (email) → B5 (XSS) → B3/B4 (финансы) → B6/B7 (логика). Каждый пункт — отдельная ветка/PR с прогоном на стенде.*
