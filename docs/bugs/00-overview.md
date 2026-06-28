# Account Arena — Сводный отчёт по багам (багхант-фаза)

> Консолидация находок 10 параллельных адверсариальных аудитов (по подсистемам).
> Детали каждого бага с `path:line` — в файлах `docs/bugs/01..10`.
> Часть громких находок **перепроверена вручную** (отмечено ✅ verified / ⚠️ disputed / 🔎 needs-runtime).
>
> ⚠️ Окружение без PHP/Node — динамическая эксплуатация невозможна, всё подтверждалось чтением кода.

---

## 1. Сводка по серьёзности

| Серьёзность | Кол-во (после дедупликации) | Примеры |
|---|---|---|
| 🔴 Critical | **9** | CSRF выключен глобально; unauth browser-control; soc-login takeover; stored XSS в админ-чате; двойная выдача по вебхуку |
| 🟠 High | **15** | IDOR гостевых покупок; stored XSS на витрине; Telegram-вебхук без подписи; модерация в обход на edit; per-user лимит промокодов |
| 🟡 Medium | **~18** | кэш каталога не инвалидируется; audit-log пишет секреты; open redirect; JSON-LD breakout; withdrawal split-row vs unique index |
| ⚪ Low | **~20** | user enumeration, PII в логах/localStorage, weak password policy, dead code |

Всего ~62 уникальных подтверждённых/высоковероятных дефекта (из ~104 «сырых» находок; остальное — дубли одного корня или ruled-out).

---

## 2. 🔴 Critical (чинить в первую очередь)

### C1. CSRF-защита отключена глобально ✅ verified
- **Где:** `backend/app/Http/Middleware/VerifyCsrfToken.php:14-16` → `$except = ['*']`.
- **Суть:** все URI исключены из CSRF, включая сессионные **админку и кабинет поставщика**.
- **Impact:** админ, зашедший на вредоносную страницу, форсированно блокирует юзеров, меняет роли, правит балансы, одобряет выводы.
- **Fix:** убрать `'*'`; исключать из CSRF только stateless API/вебхуки (они и так на `auth:sanctum`/подписи). Для web-форм оставить CSRF включённым. Источник: 01.

### C2. Browser-control endpoints полностью без авторизации ✅ verified
- **Где:** `backend/routes/api.php:135-159` (`/browser/new`, `/browser/stop`, `/browser/stop_all`, `/browser/list`) — вне всех auth/throttle-групп.
- **Суть:** `GET /browser/list` отдаёт сессии **всех** пользователей; `POST /browser/stop_all` гасит все сессии платформы; `stop` — по pid/port.
- **Impact:** неавторизованный DoS всех активных браузерных сессий + утечка списка.
- **Fix:** обернуть в `auth:sanctum` + throttle; проксирование — только по своим сессиям. Источник: 09.

### C3. Запуск чужого аккаунта без проверки покупки (IDOR) 🔎 needs-runtime
- **Где:** `backend/app/Http/Controllers/Api/BrowserController.php:19-49`.
- **Суть:** контроллер доверяет `?profile=<profile_id>` или сам берёт первый активный аккаунт; **нет проверки, что юзер купил его**.
- **Impact:** запуск стримингового браузера, залогиненного в неоплаченный аккаунт → подрыв всей модели «креды не покидают сервер».
- **Fix:** проверять владение покупкой (`purchases.user_id == auth id` и связь с `service_account_id`/profile) перед запуском. Источник: 09.

### C4. Захват аккаунта через social-login по подставному email ✅ logic-verified
- **Где:** `SocialAuthController` (Google: `:56-66`; Telegram: `:129-158`).
- **Суть:** привязка soc-identity к существующему аккаунту идёт по совпадению email; для Telegram `email` берётся из тела запроса (HMAC подтверждает источник виджета, но не владение email).
- **Impact:** злоумышленник логинится с `email=victim@…`, привязывает свой `telegram_id` к чужой (в т.ч. админской) записи и получает полноценный токен.
- **Fix:** не принимать email из Telegram-payload для линковки; линковать только по проверенному провайдером идентификатору; требовать verified-email у Google. Источник: 01.

### C5. Stored XSS в админ-панели через чат поддержки ✅ logic-verified
- **Где:** `resources/views/admin/support-chats/show.blade.php` (live-поллер: `innerHTML` + неэкранированные `${message.message}`, `${attachment.file_name}`, `${chat.guest_name}`); запись — `Api/SupportChatController.php:284` (без санитизации).
- **Суть:** гость/Telegram-отправитель шлёт `<img src=x onerror=…>`; при открытии чата исполняется в сессии админа.
- **Impact:** аноним → захват админ-сессии (worst-case privilege crossing).
- **Fix:** экранировать на клиенте (textContent/escape), серверная санитизация входящего текста и имён файлов. Источник: 06, 08.

### C6. Двойная выдача товара из-за гонки идемпотентности вебхука ✅ verified (no unique index)
- **Где:** `MonoController.php:558-569,729-740`, аналог в Cryptomus; `purchases.transaction_id` — **без unique** (`database/migrations/2025_11_04_063700_create_purchases_table.php:18`).
- **Суть:** проверка `Purchase::where('transaction_id')->first()` без блокировки, провайдеры повторяют вебхуки → два параллельных прохода создают по полному комплекту покупок, `used` +2, одни и те же креды выданы дважды, earnings задвоены.
- **Impact:** оверселл/раздача одних кредов нескольким, задвоение выплат.
- **Fix:** unique-индекс на `purchases.transaction_id` + обработка в одной транзакции с `lockForUpdate`/`insertOrIgnore`. Источник: 03, 04.

### C7. Settings-вкладка админки без gate `admin.main` ✅ verified (route)
- **Где:** `routes/web.php:76-78` + `Admin/SettingController`.
- **Суть:** любой обычный админ читает расшифрованные SMTP-пароль и Telegram-токен и перезаписывает их (+pixel). `testSmtp` коннектится к произвольному host:port (blind SSRF).
- **Impact:** обход шифрования секретов и роли главного админа, SSRF.
- **Fix:** навесить `admin.main` на settings/site-content; не отдавать секреты в форму в открытом виде. Источник: 06.

### C8. Wildcard CORS + credentials ✅ verified (с нюансом)
- **Где:** `config/cors.php:21,26` → `allowed_origins:['*']`, `supports_credentials:true`.
- **Нюанс:** при `*` браузеры **блокируют** чтение credentialed-ответов (stack-cors отдаёт буквально `*`, не reflect), так что практическая эксплуатация ниже заявленной агентом. Но это всё равно мисконфиг и риск при изменении на reflect.
- **Impact:** ослабление origin-границы; в связке с C1 — расширенная cross-origin поверхность.
- **Fix:** явный whitelist доменов фронта; `supports_credentials` только для них. Источник: 01.

### C9. `EmailService` ссылается на несуществующий `App\Mail\BaseMail` ✅ verified (нет app/Mail)
- **Где:** `app/Services/EmailService.php` (`send()`), директории `app/Mail/` нет.
- **Суть:** все транзакционные письма зарегистрированным юзерам кидают исключение, оно глотается `try/catch` → письма молча теряются (гостевой путь `sendToGuest` отдельный и работает).
- **Impact:** функциональный отказ всех email зарегистрированным (сбросы пароля и т.д.).
- **Fix:** добавить `App\Mail\BaseMail` + view `emails.base` или переписать на существующий mailable. Источник: 08.

---

## 3. 🟠 High (приоритет 2)

### H1. IDOR гостевых покупок — креды по email ✅ verified
`Api/PurchaseController.php` + `PurchasePolicy.php:30-34`. Авторизация гостя = совпадение `guest_email` в query; ID последовательные. Любой, кто знает email, читает `GET /purchases/{id}?guest_email=…`/`/download` (креды) или `/cancel`. Интендед-контроль закомментирован (`PurchaseController.php:50-51`). **Fix:** подписанные ссылки/токен заказа для гостей. Источник: 03.

### H2. Stored XSS на витрине через `v-html` (нет санитайзера) ✅ verified (нет DOMPurify)
Описания товаров/доп.описания, CMS-страницы, статьи, **уведомления** (в шапке, поллинг 10с), баннеры рендерятся `v-html` без санитизации; `package.json` без DOMPurify; `useProductTitle` лишь правит `<a>`. Запись supplier-описаний — `nullable|string`. Токен в `localStorage` → кража токена. **Sinks:** `AccountDetail.vue:461,493`, `ProductCard.vue:119`. **Fix:** DOMPurify на рендере + серверная санитизация при записи. Источник: 02, 05, 06, 09, 10.

### H3. SSR stored XSS (бот-видимый HTML) ✅ logic-verified
`SpaController::generateProductContent` инжектит сырое `description`; `seo/article.blade.php` → `{!! $seoText !!}`. CSP с `'unsafe-inline'` не спасает. **Fix:** экранировать/санитизировать в SSR. Источник: 09.

### H4. Telegram webhook без проверки подписи ✅ verified (route)
`POST /api/telegram/webhook` — без secret_token/подписи (в отличие от Cryptomus/Mono). Доверяет `from.id` → форджит чаты от имени реального `User` (по `telegram_id`), несёт XSS из C5, флудит БД. Усиливает Guest-Chat IDOR (синтетический email `tg{id}@telegram.local`). **Fix:** `secret_token` у `setWebhook` + проверка в хендлере. Источник: 08, 09.

### H5. Обход модерации при редактировании товара ✅ logic-verified
`Supplier/ProductController.php:225-283` — `update()` не сбрасывает `moderation_status`. Поставщик: чистый товар → апрув → правка (заголовок/описание/цена/сток) идёт **в прод без ре-ревью**. В связке с H2 — доставка XSS-пейлоада после апрува. **Fix:** на значимых правках сбрасывать в `pending`+`is_active=false`. Источник: 05.

### H6. Модератор не видит половину контента ✅ logic-verified
`admin/product-moderation/show.blade.php` рендерит только `title`/`description`, но НЕ `additional_description` и переводы (EN/UK) — а они идут `v-html` на витрине. Пейлоад прячется в неревьюемое поле. **Fix:** показывать все рендеримые поля в модерации. Источник: 05.

### H7. Per-user лимит промокода обходится ✅ logic-verified
`ProductPurchaseService.php:520` — проверка только на этапе validate, запись использования без блокировки/без unique на `(promocode_id, user_id/order_id)`; для гостей лимит вообще не действует (`PromocodeValidationService.php:68`). **Fix:** unique-констрейнт + проверка под локом при записи; для гостей — fallback по email. Источник: 03, 04.

### H8. Скидка может не переноситься в card/crypto-выдаче 🔎 needs-runtime / ⚠️ partly-disputed
Агент 03: в вебхук-пути `total` берётся недисконтированным → переплата поставщику/раздув выручки. **Поправка ревью:** в balance-пути (`prepareProductsData`→`getCurrentPrice()`) скидка **применяется корректно**; вопрос только к card/crypto-вебхукам — требуется прогон. Отмечено как нужно подтвердить рантаймом. Источник: 03.

### H9. Withdrawal split-row конфликтует с unique-индексом 🔎 needs-runtime
`Admin/WithdrawalRequestController.php:225`, `SupplierEarning.php:139`: `markAsPaid`/`partialReverse` `create()` остаток с тем же ключом `(purchase_id, transaction_id, supplier_id)` (unique с 2026_01_05) → integrity violation → откат всей выплаты. Частичные выводы могут быть сломаны. **Fix:** не переиспользовать ключ для split-строки (отдельный тип/nullable-дискриминатор). Источник: 04.

### H10. Top-up может задвоиться при ретрае >24ч 🔎 needs-runtime
`findDuplicateTransaction` ограничен 24ч, у топ-апа нет guard'а по существованию Purchase; статус флипается до зачисления. **Fix:** идемпотентность по provider-txn-id без временного окна. Источник: 04.

### H11. Refund-and-keep: возврат без отзыва доступа ✅ logic-verified
`resolveWithRefund` кредитует баланс и реверсит earning, но **не отзывает выданные `account_data`/сток**. Покупатель получает возврат и продолжает пользоваться аккаунтом; усиливается авто-закрытием. **Fix:** при refund инвалидировать креды/ротация/блок доступа. Источник: 07.

### H12. Insecure token + token-injection через URL ✅ logic-verified
`AuthCallback.vue:22-33` принимает `?token=` из URL без `state`/валидации → session-fixation фишинг. Токен и user в `localStorage`. **Fix:** OAuth `state`-nonce, не принимать токен из произвольного URL. Источник: 10.

### H13. `sc_auth` extension-токен игнорирует abilities ✅ logic-verified
`auth:sanctum` не проверяет ability `extension`, токен в plaintext-cookie без ротации/expiry → узко-скоупленный токен имеет полный доступ к API. **Fix:** middleware проверки ability; httpOnly+secure cookie, срок жизни. Источник: 01, 09.

### H14. `TrustProxies::$proxies = null` ⚠️ verify-config
Доверие `X-Forwarded-*` от кого угодно → подмена IP/host (влияет на rate-limit по IP, Cryptomus IP-allowlist, логи). **Fix:** задать конкретные прокси/подсети. Источник: 09.

### H15. Дубликаты споров (TOCTOU) → refund + бесплатный товар ✅ logic-verified
Нет unique на `product_disputes.transaction_id`, `exists()→create()` без атомарности. Спор A→refund, спор B→replacement (у replacement нет guard на `refunded`) = деньги назад И бесплатный товар. **Fix:** unique-констрейнт + проверка статуса транзакции в `store` (сейчас отсутствует). Источник: 07.

---

## 4. 🟡 Medium (выборка ключевых)

- **M1. Кэш каталога/«похожих» никогда не инвалидируется** ✅ verified — инвалидаторы чистят мёртвые `_v1/_v2/_v3`, а читается `active_accounts_list_v4` / `similar_products_v2_*`. Стейл-сток (оверселл), стейл-цена, задержка скрытия снятых/отклонённых до 5мин/1ч. (`ServiceAccountObserver.php:19-21`). Источник: 02.
- **M2. Немодерированные товары утекают в «Похожие»** ✅ verified — `ServiceAccount::getSimilarProducts:428-478` фильтрует только `is_active`. Источник: 02.
- **M3. Audit-log пишет секреты и «успех» на 3xx** — логирует на 2xx/3xx (отказы-редиректы = «успех»), сохраняет `smtp_password`, `telegram_bot_token`, `account_data[]` (выданные креды) в просматриваемую таблицу. Источник: 06.
- **M4. Open redirect на логине** — `LoginPage.vue:168-169` пушит `route.query.redirect` без same-origin проверки. Источник: 10.
- **M5. JSON-LD `</script>` breakout** — нет `JSON_HEX_TAG` при выводе structured data. Источник: 09.
- **M6. Regex-санитайзеры XSS обходятся** (unquoted `on*=`), категорийный `text` — CKEditor-поле без санитизации, массовые уведомления без санитизации. Источник: 06.
- **M7. SVG/ICO загрузка в content/email/service-account uploads** (XSS через SVG). Источник: 06.
- **M8. `assign` чата принимает не-админский user_id**; `supplier_balance` всё ещё правится из формы юзера. Источник: 06.
- **M9. replacement не сверяет `service_id`/`supplier_id`** → бесплатно выдать произвольный/чужой/дороже товар. Источник: 07.
- **M10. auto-close: hardcoded `resolved_by=1`, стейл `refund_amount`, `notifySupplier()` null-deref на удалённом товаре → откат всего refund.** Источник: 07.
- **M11. `/contents/{code}` вне throttle и без кэша** (DoS). Источник: 02.
- **M12. `deduct_from_supplier` валидируется, но игнорируется** (клоубэк всегда). Источник: 06, 07.

---

## 5. ⚪ Low (выборка)

User enumeration через `exists:users,email` + сброс throttle (01); слабая политика пароля (min 6 vs 8) (01); PII (email/balance/personal_discount) в логах и `localStorage` (01, 10); logout рубит все токены на всех устройствах (01); незакрытый logout фронта (cart/promo/token переживают) (10); rating можно накрутить wash-sale + вечный 100% для новичка (05); dead code (`SupportMessageReaction`, `extractKeywords`, `EmptyLayout`, осиротевшие Lottie) — кандидаты на удаление в рефакторинге.

---

## 6. Сквозные темы (корни, бить по которым закрывает много багов)

1. **Нет серверной санитизации HTML** + клиентский `v-html`/админский `innerHTML` без экранирования → стек stored-XSS (C5, H2, H3, H6, M6, M7). Один общий фикс: серверный санитайзер при записи + DOMPurify/экранирование при рендере во всех точках.
2. **Идемпотентность/уникальность в финансах** отсутствует на уровне БД (C6, H7, H9, H10, H15): не хватает unique-индексов (`purchases.transaction_id`, `promocode_usages`, `product_disputes.transaction_id`) и обработки под локом.
3. **Авторизация на «второстепенных» поверхностях** (C2, C3, C7, H1, H4, H13): browser-routes, telegram-webhook, settings, extension-токен, гостевые покупки — единый аудит middleware/policy.
4. **Доверие к клиенту/проксям/входным данным** (C8, H8, H12, H14, M4): CORS, TrustProxies, OAuth state, redirect-параметры.

---

## 7. ⚠️ Поправки к находкам агентов (важно — не тащить ложь дальше)

- **«CRITICAL переплата поставщику» (отчёт 04, BUG-01) — ОТКЛОНЕНО.** Проверено: `getPriceWithCommission` = `price/(1−c)` (наценка сверху для покупателя), earning = `total×(1−c)` = базовая цена поставщика. Поставщик получает ровно свою запрошенную цену — это согласованная модель. Ошибочен лишь **doc-комментарий** (`ServiceAccount.php:202`), утверждающий «поставщик получает 9». Реального денежного утекания нет. → переклассифицировать в Low «исправить комментарий».
- **CORS (C8):** практическая эксплуатируемость ниже заявленной из-за поведения браузеров при `Allow-Origin: *` + credentials. Остаётся мисконфигом.
- **H8/H9/H10:** помечены 🔎 needs-runtime — требуют прогона на поднятом окружении для 100% подтверждения.

---

## 8. План ремедиации для фазы рефакторинга

Порядок (от безопасного-высокоценного к рискованному):

1. **Конфиг/мидлвары (низкий риск):** CSRF (C1), CORS (C8), TrustProxies (H14), auth на `/browser/*` (C2), `admin.main` на settings (C7), throttle на `/contents` (M11), telegram secret (H4).
2. **БД-констрейнты (миграции):** unique на `purchases.transaction_id`, `promocode_usages`, `product_disputes.transaction_id` (C6, H7, H15) — добавлять с предварительной чисткой дублей.
3. **Санитизация XSS (сквозной фикс):** серверный санитайзер + DOMPurify/escape во всех `v-html`/`innerHTML` (C5, H2, H3, H6).
4. **Авторизация/ownership:** проверка покупки в BrowserController (C3), подписанные гостевые ссылки (H1), abilities для extension-токена (H13), social-login линковка (C4).
5. **Логика возвратов/споров/модерации:** refund-revoke (H11), reset moderation on edit (H5), replacement-проверки (M9), auto-close фиксы (M10).
6. **Email-инфраструктура:** `App\Mail\BaseMail` (C9).
7. **Чистка/качество (рефакторинг):** инвалидация кэша (M1/M2), dead code, дубли локализации, унификация токен-транспорта, doc-комментарии (поправка §7).

> Каждый фикс — отдельным небольшим коммитом с описанием. Тесты гонять нечем (нет PHP/Node), поэтому верификация — ревью + добавление таргетных unit-тестов туда, где это возможно без запуска.

---

*Сгенерировано на основе 10 параллельных аудитов + ручная перепроверка ключевых находок.*
