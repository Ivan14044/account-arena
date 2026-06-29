# Account Arena — Анализ для рефакторинга (фаза 1: только анализ, код не менялся)

> Цель: привести проект к чистой, модульной структуре итеративно, без изменения внешнего поведения.
> Это **отчёт**. Код не трогался. Рефакторинг — только после подтверждения отчёта.
> Источник: 5 параллельных read-only аудитов + ручная сверка. Все ссылки — `path:line`.

## Как читать
Категории по вашему ТЗ:
- **A. Дублирующаяся логика** (один и тот же код/правило в нескольких местах)
- **B. «Изменение в >1 месте»** (нет единого источника истины)
- **C. Крупные файлы/функции с несколькими зонами ответственности**
- **D. Смешение слоёв** (бизнес-логика / представление / данные)
- **E. Магические числа и строки**

Приоритет (P1 — максимальный эффект/риск):
1. **P1** — Дублирование платёжных контроллеров Mono/Cryptomus (~70% копипаста, security-инварианты) → A1, C2.3/2.4, D3
2. **P1** — Идемпотентность вебхуков (6–8 копий одного инварианта) → A3
3. **P1** — Ценообразование/скидки/комиссия (гость vs юзер дают разные суммы) → A2, B6
4. **P2** — Статусные словари дублируются backend↔frontend (+ нет констант у Transaction/SupplierEarning/moderation) → B1, E6
5. **P2** — Кэш-ключи: write в одном файле, forget в других (был реальный oversell-баг) → A4, B3
6. **P2** — Локализация: 7+ копий «выбрать перевод по locale» + список `ru/en/uk` в ~20 местах → A8, B5, frontend §1
7. **P3** — Магические значения (999, 30, TTL, лимиты загрузок, ключи Option) → E
8. **P3** — Крупные компоненты/модели с presentation внутри → C, D4

---

## A. Дублирующаяся логика (DRY)

### A1. Платёжные вебхуки Mono ≈ Cryptomus — почти клоны (P1)
~80% тела webhook-логики совпадает построчно (различия: метки, источник id, kopecks/100).
- Mono: `backend/app/Http/Controllers/MonoController.php` — `webhook()` `42-153`, `handleTopUpWebhook()` `402-522`, `handleUserPurchaseWebhook()` `536-708`, `handleGuestWebhook()` `722-864`.
- Cryptomus: `backend/app/Http/Controllers/CryptomusController.php` — `webhook()` `368-450`, `handleTopUpWebhook()` `455-541`, `handleUserPurchaseWebhook()` `546-669`, `handleGuestWebhook()` `674-802`.
- Дублируются блоки: dedupe-guard, `match` по payment_type, stock re-validation + price recompute loop (Mono `594-635`/`764-801`, Crypto `589-629`/`712-749`), `createMultiplePurchases(...)`, notification fan-out, `handleUnknownPaymentType`.
- Уже есть дрейф: Mono на «No valid products» отдаёт 400, Cryptomus — то `success`, то 400; Cryptomus TopUp не шлёт письмо. В Cryptomus есть мёртвый `prepareProductsForPurchase()` (`816-837`).
- **Риск:** правка правила в одном провайдере молча расходится с другим (security/деньги).
- **SSOT:** `PaymentFulfillmentService` (нормализованный DTO → одна реализация topup/user/guest), контроллеры = verify подпись → нормализовать payload → делегировать.

### A2. Скидка/комиссия/цена пересчитываются вне модели (P1)
- **Combined personal+promo, cap 99%** — идентичный блок в 3 местах: `CartController.php:54-72`, `MonoController.php:320-337`, `CryptomusController.php:69-85`.
- **Guest promo-only** — иной вариант (`(int)` каст, без cap): `MonoController.php:224-228`, `CryptomusController.php:207-211`, и третий вариант `GuestCartController.php:61-64` (`floatval`, без округления). → **разные суммы по одному промокоду** для гостя vs юзера.
- **Per-item `getCurrentPrice()*qty`** — `ProductPurchaseService.php:53-54` (каноника) + `MonoController.php:202-203,615-616,784-785` + `CryptomusController.php:187-188,610-611,732-733`.
- **Discount-формула повторена 5× внутри одного метода** модели: `ServiceAccount.php:191-194,216-219,226-229,240-243,264-267`.
- **SSOT:** `PricingService::computeOrderTotal(items, user, promo)` + приватный `applyDiscount()` в модели.

### A3. Идемпотентный compare-and-set — 6 копий + 2 не-атомарных (P1)
Идиома `Transaction::whereKey()->where('status','!=','completed')->update(...)`:
- `MonoController.php:442-452,577-589,750-761`; `CryptomusController.php:480-490,575-586,698-709`.
- Не-атомарные «сиблинги» (старый стиль `if status!=='completed' { save() }`): `MonoController.php:134-137`, `CryptomusController.php:430-433`.
- **SSOT:** `Transaction::claimForCompletion(): bool`. (Это я добавлял в фазе фиксов — теперь стоит вынести в один метод.)

### A4. Кэш-ключи каталога дублируются в 4 файлах (P2)
- Reader: `Api/AccountController.php:17` (`active_accounts_list_v4`).
- Invalidators (каждый форгетит `_v1.._v4`): `Admin/ServiceAccountController.php:808-811`, `Observers/ServiceAccountObserver.php:19-26`, `Observers/CategoryObserver.php:25-28`.
- `similar_products_v2_*` (`ServiceAccount.php:431`) — **нигде не инвалидируется** (только TTL).
- Дрейф уже вызывал реальный oversell (исторический M1). Добавить `_v5` = править 4 файла.
- **SSOT:** `ProductCache::flushCatalog()/flushSimilar(id)` + класс ключей; либо cache tags.

### A5. Правила валидации ServiceAccount — 4 копии (P3)
- Supplier: `Supplier/ProductController.php:424-443` (`getRules`) + инлайн bulk `103-110`.
- Admin: `Admin/ServiceAccountController.php:649-689` (`getRules`) + инлайн bulk `121-127`.
- Повторяются `price=required|numeric|min:0.01`, `title=required|string|max:255`, `image=...|max:2048`, `_en/_uk` поля.
- **SSOT:** `ProductRules::base($id)` или FormRequest-база (в проекте уже есть `CartStoreRequest`, `CreateDisputeRequest`).

### A6. Нотификации — 4 механизма + ручной fan-out в каждом месте (P2)
- Механизмы: `NotifierService`, `NotificationTemplateService`, `EmailService`, инлайн `SupplierNotification::create`.
- Повтор `sendFromTemplate('product_purchase','admin_product_purchase',[...])`: `MonoController.php:678-688,835-845`, `CryptomusController.php:855-865,774-784`, `CartController.php:212-222`.
- Trio «email + sendToUser('purchase')»: `MonoController.php:658-675`, `CryptomusController.php:844-853`, `CartController.php:200-243`.
- **SSOT:** `PurchaseNotifier::completed(...)` — один orchestrator; webhooks/cart вызывают его.

### A7. Расчёт остатка/наличия — модель vs raw SQL (P3)
- Каноника PHP: `ServiceAccount.php:291-326` (`getAvailableStock`), JSON-нормализация `308-317`.
- Копия нормализации: `ProductPurchaseService.php:107-116`.
- Raw `JSON_LENGTH(accounts_data)`: `ServiceAccount.php:454`, `Admin/ServiceAccountController.php:26`, `Api/AccountController.php:26`.
- Инлайн availability (999/0, `max(0,total-used)`): `Api/AccountController.php:41-48,116-118,193-197`, `Admin/DashboardController.php:59-66`.
- **SSOT:** `getAvailableStock()` единственный авторитет + `normalizeAccountsData()` helper + query scope для SQL-выражения.

### A8. Прочий копипаст (P3)
- **Алгоритм выдачи аккаунтов** (slot-select + `used`++) в 3 местах: `ProductPurchaseService.php:146-160`, `Admin/ServiceAccountController.php:386-415` (export), `ManualDeliveryService.php:116-119`. → `AccountAllocator`.
- **Locale resolution** `in_array($locale,['ru','en','uk'])`: `ProductPurchaseService.php:125`, `NotifierService.php:62`, `Seo/SpaController.php:39` (+ §B5).
- **`X-Locale` парсинг** дублирует middleware `SetLocale`: `Api/ProductDisputeController.php:21,250`, `Api/SupportChatController.php:95`, `OptionController.php:100`.
- **Visibility-фильтр** (`is_active AND (approved OR supplier_id IS NULL)`) в 5 замыканиях: `ServiceAccount.php:442-445`, `Api/AccountController.php:31-34,89-92,166-169`, `Admin/DashboardController.php:53-56`. → `scopePubliclyVisible()` (security-релевантно).
- **`escapeHtml` определён дважды** инлайн в одном blade: `admin/support-chats/show.blade.php:415-426` и `707-713` (+ см. D6).
- **Frontend localization** — 7+ копий (см. отдельный блок ниже).

#### Frontend-дубли (детально)
- «Перевод по locale» — 7+ реализаций с РАЗНЫМИ правилами fallback: `utils/localization.ts:5-66`, `composables/useProductTitle.ts:13-39`, `composables/useBanners.ts:26-34`, `components/home/HeroSection.vue:135-145` (копия useBanners), `stores/productCategories.ts:68-77`, `components/home/CatalogSection.vue:194-197`, `stores/siteContent.ts:102-156`, + article-вариант (`useArticle.ts:41`, `stores/articles.ts:37-81`, `ArticleDetails.vue:100`, `ArticlesAll.vue:157,169`, `NotificationBell.vue:360`). **ru-fallback расходится** между копиями. → один `getLocalizedField` + `useLocalized()`.
- **Форматирование цены** `Intl.NumberFormat('ru-RU',{currency})` копипаст в 5 файлах: `ProductCard.vue:288-303`, `AccountDetail.vue:529-544,775-783`, `AccountSection.vue:93-108`, `ServiceCart.vue:46-54`, `UserMenu.vue:161-172`; расходящийся `toFixed(2)` вариант: `CheckoutPage.vue:566-569`. → `utils/money.ts` (`formatPrice`, `effectivePrice`).
- **Token/Authorization** транспорт: глобальный `axios.defaults` + руками per-request заголовки в `stores/auth.ts` (`:204,225,245,262,280`) и `stores/notifications.js` (`:18-24,65-74,99-109,137-144`). → interceptor + `tokenStorage`.
- **localStorage `product_favorites`** — свой локальный const в 3 компонентах: `AccountSection.vue:309`, `SimilarProducts.vue:66`, `AccountDetail.vue:560`.
- **IntersectionObserver** реализован 3× (`useIntersectionObserver.ts`, `useLazyImage.ts`, `directives/intersect.ts`).

---

## B. «Изменение в более чем одном месте» (нет единого источника истины)

### B1. Статусные словари дублируются по слоям (P2)
**Purchase status** — ≥6 мест: константы `Purchase.php:92-97`; text `getStatusText()` `102-116`; badge `getStatusBadgeClass()` `118-131`; blade-фильтр (хардкод) `admin/purchases/index.blade.php:140-143`; frontend class-map `ProfilePage.vue:1966-1976`; frontend i18n `i18n/locales/ru.json:121-128` (+en/uk); инлайн `=== 'completed'` в `OrderSuccessPage.vue:143,367,514,743,...`.
- **Уже дрейфит:** backend `pending => "В обработке"` vs frontend `pending => "В ожидании"`.
**Dispute** — лучше (есть константы), но reason-enum хардкодится даже внутри backend: `CreateDisputeRequest.php:13` повторяет `REASON_*` вместо `Enum::values()`; + frontend `<option>` `ProfilePage.vue:1237-1275` + i18n.
**Без констант вообще:** `Transaction` (status/type — raw strings везде), `SupplierEarning` (`held/available/withdrawn/reversed` — ~10 файлов), `ServiceAccount.moderation_status` (`approved/pending/rejected` — ~18 мест), `WithdrawalRequest` (`pending/paid`).
- **SSOT:** PHP backed-enum (value + label-key + badge) → отдать на фронт через meta-endpoint или генерируемый TS; FormRequest `in:` строить из enum; blade-dropdown итерировать enum.

### B2. Ключи Option (`Option::get('...')`) — голые строки, нет реестра (P3)
- `currency` — ~34 чтения (+ хардкод `'USD'` в ~8 фронт-компонентах).
- telegram_* / support_chat_* / smtp_* / dispute_auto_close_* — читаются в сервисах/контроллерах/blade, пишутся в `Admin/SettingController`. `default_lang` — читается (`EmailService.php:54`), **нет писателя/сидера** (orphan).
- i18n-ключи `{key}_{ru|en|uk}` — триплицированы вручную.
- **SSOT:** `OptionKey` enum/const + правила/сидер из него; типизированный options-DTO на фронт.

### B3. Кэш-ключи: write↔forget в разных файлах (P2)
- `active_accounts_list_v4` (write `AccountController.php:17` / forget ×3, §A4). `similar_products_*` — без forget.
- `disputes_new_count` — write `Admin/ProductDisputeController.php:282`, forget в 5 местах (`:117,202,230,248` + `Api/ProductDisputeController.php:223`).
- `banners_pos_*` — позиции захардкожены отдельно в `BannerObserver`.
- `support_chat_settings_{locale}` — forget циклом по локалям (`SettingController.php:89`, привязка к §B5).
- **SSOT:** `CacheKeys` + builder-методы, либо cache tags.

### B4. Имена cookie/token/storage (P3)
- Backend: `'sc_auth'` (4 места: `EncryptCookies.php:15`, `ExtensionAuth.php:17`, `Auth/AuthController.php:28-38`), `'auth_token'`, ability `'extension'` (mint `AuthController.php:91-93,149-151` + check `ExtensionAuth.php:44`).
- Frontend storage-ключи: `'token'`,`'user'`,`'user-language'`,`'theme'`,`'product_cart'`,`'product_favorites'`,`'cookies_accepted'` — каждый повторён на каждом обращении.
- **SSOT:** backend `TokenAbility/CookieName` const; frontend `storageKeys.ts`.

### B5. Локали `ru/en/uk` захардкожены с двух сторон (P2)
- Каноника есть: `backend/config/langs.php:4-6`, корректно используется только `SetLocale.php:15`.
- Хардкод backend: `Seo/*` (`ProductController.php:95`, `SitemapController.php:23`, `CategoryController.php:120`, `SpaController.php:39,922`, `ServicePageController.php:53`), `OptionController.php:71-84`, `InjectSpaMetaTags.php:294-300`, `Admin/EmailTemplateController.php`, десятки `?? ...['ru']` дефолтов.
- Хардкод frontend: `i18n/index.js:12`, `LanguageSelector.vue:88-91`, `utils/localization.ts:16-34`, `useHreflang.ts:6`, `pluralize.ts`, и т.д.
- Добавить локаль = ~20+ правок.
- **SSOT:** backend — `array_keys(config('langs'))` везде; frontend — один locale-модуль (или meta-endpoint).

### B6. Комиссия/hold/цены — одно правило в нескольких кодировках (P1/P2)
- Buyer-формула `price/(1-c/100)`: `ServiceAccount.php:211-269`.
- Supplier-формула (другая запись того же): `ProductPurchaseService.php:271-326` (share `:276`).
- Hold default `6`: `ProductPurchaseService.php:311` (+ коммент `:258`); bounds только в `Admin/UserController.php:69-70`; cast в `User.php:50,54`; release через `SupplierEarning::scopeReadyToRelease` + `ReleaseSupplierEarnings`.
- supplier-настройки расщеплены между `Admin/UserController` и `Admin/SupplierController`.
- **SSOT:** `SupplierPricing`/`SupplierConfig` (default hold, bounds, `priceWithCommission()`+`supplierShare()` рядом) + enum статусов SupplierEarning.

### B7. Эндпоинты: фронт-строки vs backend-роуты (P3)
- Нет фронт-констант эндпоинтов; пути-литералы по сторам. Некоторые в 2 местах: `GET /purchases` (`ProfilePage.vue:2028` + `OrderSuccessPage.vue:903`), `GET /banners/all` (`stores/banners.ts:54` + `useBanners.ts:65`).
- **SSOT:** `api/endpoints.ts` (в идеале генерация из OpenAPI/route-export).

### B8. Валидация/лимиты дублируются client↔server (P3)
- Cancel reason `10..500`: backend `Api/PurchaseController.php:224` / front disable `ProfilePage.vue:1730` / i18n hint `:1717` — число в 3 местах.
- Dispute reason enum — backend FormRequest + front `<option>` + model const (3 копии).
- Screenshot mime — дублируется; **size 5120 KB не проверяется на клиенте** (>5MB пройдёт клиент, упадёт на сервере).
- Quantity clamp — `productCart.ts:66-118` vs backend.
- **SSOT:** общий контракт правил (сериализованные FormRequest-правила / shared schema) + per-field константы.

---

## C. Крупные файлы/функции с несколькими зонами ответственности

### C1. Backend size census (>400 строк)
| Строк | Файл |
|---:|---|
| 1192 | `Seo/SpaController.php` |
| 976 | `MonoController.php` |
| 873 | `CryptomusController.php` |
| 813 | `Admin/ServiceAccountController.php` |
| 605 | `Services/ManualDeliveryService.php` |
| 595 | `Services/ProductPurchaseService.php` |
| 561 | `Services/TelegramBotService.php` |
| 542 | `Admin/SupportChatController.php` |
| 526 | `Models/ServiceAccount.php` |
| 496 | `Api/SupportChatController.php` |
| 477 | `Supplier/ProductController.php` |
| 426 | `Api/SiteContentController.php` |
| 424 | `Services/BalanceService.php` |

Blade: `admin/layouts/modern-styles.blade.php` **5011** (CSS в шаблоне), `support-chats/show.blade.php` **2454**, `service-accounts/index.blade.php` **2104**, `site-content/index.blade.php` **1825**, `users/edit.blade.php` **968**.

### C2. God-методы
- **`ProductPurchaseService::createProductPurchase()` `85-348`** (~263 строки): re-lock + JSON-normalize + locale/suffix + slot-select + 3 слоя валидации + `used`++ + Transaction + delivery-branch + Purchase + history + **supplier-earning math** (`253-341`). → `AccountAllocator` + `PurchaseFactory` + `SupplierEarningCalculator`.
- **`createMultiplePurchases()` `362-468`**: транзакция + промокод + (вне транзакции) cache invalidation + нотификации + low-stock scan. → событие `PurchasesCreated` + listener.
- **Mono/Cryptomus webhook-хендлеры** (см. A1) — каждый 120-170 строк, mix parsing+idempotency+stock+price+earning+3 нотификации+JSON.
- **`SpaController` `1192`** — routing (`64-147`) + data-access (запросы по всему) + SEO-meta/JSON-LD + HTML-render (`generate*Content` `1006-1191`, инлайн-стили) + regex-инъекция в index.html (`874-974`) + захардкоженный мультиязычный контент (FAQ `722-842`). Латентный баг: `generateCategoryContent` фильтрует `where('stock_count','>',0)` — такой колонки нет (`:1071`).
- **`Admin/ServiceAccountController::bulkAction()` `495-647`** — арифметика цен + мутации + ручная транзакция.

### C3. Frontend крупные компоненты (>250 строк)
| Строк | Файл | Смешано |
|---:|---|---|
| 3349 | `pages/account/ProfilePage.vue` | profile+orders+disputes+subscriptions+status-maps+polling+форматирование |
| 2666 | `components/SupportChatWidget.vue` | TG/inline UI + polling + typing + статусы + хардкод t.me |
| 1825 | `pages/CheckoutPage.vue` | cart+SEO+payment-redirect+discount-rules+guest+currency |
| 1644 | `pages/account/AccountDetail.vue` | fetch+SEO+JSON-LD+price-format+presentation |
| 1182 | `pages/OrderSuccessPage.vue` | order+2 polling+инлайн status-checks |
| 998 | `components/products/ProductCard.vue` | presentation+price/discount+cart |
- → выносить data-fetch/SEO/status-map/polling в композаблы (`useAccountDetail`, `useProductSeo`, `useSupportChat`, `constants/statuses.ts`).

---

## D. Смешение слоёв (бизнес-логика / представление / данные)

### D1. Контроллеры с бизнес-логикой (должно быть в сервисах/моделях)
- Webhook fulfillment/деньги: `MonoController.php:402-864`, `CryptomusController.php:455-802`.
- Dispute replacement money/inventory: `Admin/ProductDisputeController.php:129-209` (своя транзакция + lock + бизнес-правила + выдача).
- Inline checkout pricing: `Mono/Cryptomus create*Payment` (см. A2).
- Bulk price engine: `Admin/ServiceAccountController::bulkAction() 495-647`.
- Export повторяет inventory-алгоритм: `Admin/ServiceAccountController::export() 353-428`.

### D2. Модели с представлением (UI-строки/CSS через `__()`)
- `ProductDispute.php`: `getDecisionText()341-349`, `getReasonText()354-365`, `getStatusBadgeClass()370-379` (CSS), `getStatusText()384-393`. **Баг-доказательство:** контроллер зовёт с `$locale`, а сигнатуры без параметра → locale игнорируется (`ProductDisputeController.php:54,59,61`).
- `Purchase.php`: `getStatusText()102-113`, `getStatusBadgeClass()118-129` (Bootstrap-токены).
- `User.php`: `getRatingLevel()216-271` — эмодзи 🏆💎, звёзды, CSS-class, билингв-строки.
- → `*Presenter` / API Resources / view-composers; в модели — только константы и предикаты (`isCompleted()`).

### D3. Модели как сервис/кэш-слой
- `ServiceAccount::getSimilarProducts() 429-495` — рекомендательный движок (cache+3-стадийный ранкинг+raw SQL) в модели; мёртвый `extractKeywords() 503-525`.
- `Option::get/set 25-65` — cache + **шифрование секретов** (`30,55-61`) + JSON в одном статич-фасаде. → `SettingsRepository` + encrypted-cast.
- `SupportChat` — typing-state в Cache (`195-219`), агрегаты размеров.
- `User::calculateSupplierRating()/getRatingDetails() 276-326` — 90-дн агрегатные запросы в модели → `SupplierRatingService`.

### D4. Blade с крупным инлайн-JS/логикой
- `admin/support-chats/show.blade.php` (2454): ~1300 строк инлайн-JS в 4 `<script>`; polling (`setInterval 3000/2000`), DOM-render строками, **двойной `escapeHtml`** (`415-426` и `707-713`), file-upload, форматтеры дублируются; два больших блока — почти дубликаты (guest vs user). → вынести в JS-модуль/Vue-компонент, один `escapeHtml`.
- `modern-styles.blade.php` (5011) — CSS в шаблоне → в скомпилированный стиль.

---

## E. Магические числа и строки

- **`999`** (unlimited/manual сток): `ServiceAccount.php:297`, `Api/AccountController.php:42`; frontend `AccountSection.vue:353`, `ProductCard.vue:180,346`, `SimilarProducts.vue:114`. → `ServiceAccount::UNLIMITED_STOCK` + boolean-флаг в API.
- **Hold `6` / commission `0` / `/100`**: `ProductPurchaseService.php:271-272,311`, `ServiceAccount.php:234-235,248`; bound `8760` `Admin/UserController.php:70`. → `config/suppliers.php`.
- **Dispute `30` дней** (2 копии + текст): `Api/ProductDisputeController.php:122,125,319`; auto-close `24`: `AutoCloseDisputes.php:38-39`; overdue `24`: `NotifyOverdueManualOrders.php:38,41`. → `ProductDispute::DISPUTE_WINDOW_DAYS`/config.
- **Rate limits**: `api.php` `60,1`(×4) `300,1`(×2) `120,1` `10,1` `100,1`(×2); `web.php:169-170`. → именованные limiters `RateLimiter::for(...)`.
- **Cache TTL**: `3600`(~10×), `300`,`600`,`30`,`86400`,`5` — список с `path:line` в отчёте агента (Option/OptionController/Banner/Article/CategoryService/ServiceAccount/PurchaseController/AccountController/Dashboard/ProductDispute/Sitemap/SupportChat). → `config/cache_ttl.php` / `CacheTtl::*`.
- **Status-литералы вместо констант** (худшие: `SupplierEarning` `held/available/withdrawn/reversed`, `moderation_status` `approved/pending/rejected`, `Transaction` без констант, raw `'completed'`/`'success'`/`'refunded'` в Mono/Crypto/Dashboard/BalanceController). → добавить `STATUS_*`/`MODERATION_*` константы и заменить.
- **24h**: `BalanceService.php:294` (`subHours(24)`), `SocialAuthController.php:179` (`86400`), `MonoPaymentService.php:16` (const), `NotifyOverdueManualOrders.php:38,41`. → одна `Duration::DAY`/именованная const.
- **Локали `['ru','en','uk']`** — см. B5.
- **Cookie/ability имена** `sc_auth`/`auth_token`/`extension` — см. B4.
- **Option-ключи** — см. B2.
- **Лимиты загрузок**: `5120`(×5), `2048`(×6), `10240`(×5), и raw `N*1024*1024` (50/100/200/500MB) в support-chat (`Api/SupportChatController.php:214,229,250`, `Admin/SupportChatController.php:146,161`) — лимит дублируется в тексте сообщения (drift). → `config/uploads.php`.
- **Синтетические email**: `@telegram.org` (`SocialAuthController.php:145`) vs `@telegram.local` (`TelegramBotService.php:482`) — несогласованно. → `User::TELEGRAM_EMAIL_DOMAIN` + helper.
- **Frontend**: дефолт locale `'uk'` vs `'ru'` (несогласованно), polling-интервалы (3s/2s/10s/5000), `SITE_URL='https://account-arena.com'` (`useHreflang.ts:5`, `useProductTitle.ts:61`), `t.me/support`, тосты-строки в `bootstrap.js:51-60`/`auth.ts`. → `constants/`.

---

## Мёртвый код (кандидаты — подтвердить перед удалением)
- `frontend/src/components/layout/EmptyLayout.vue` — 0 импортёров.
- `vue3-lottie` плагин/зависимость — зарегистрирован (`main.js:12,43`), нигде не используется.
- `ServiceAccount::extractKeywords()` (`503-525`) — нереализованная фича.
- Cryptomus `prepareProductsForPurchase()` (`816-837`) — не вызывается.
- `utils/scrollToElement.ts` — есть, но `CheckoutPage` зовёт `scrollIntoView()` напрямую.
- Forget мёртвых ключей `active_accounts_list_v1..v3`.

---

## Предлагаемый порядок рефакторинга (итеративно, по одному шагу, с остановкой и показом)

> Каждый шаг — behaviour-preserving, отдельная ветка → PR → merge, с прогоном тестов/сборки.
> Шаги, помеченные **[нужно ваше согласие]**, затрагивают много файлов/публичные контракты — спрошу до старта.

1. **Status-константы (backend, низкий риск, чисто внутренние):** добавить `STATUS_*` в `Transaction`, `SupplierEarning`, `WithdrawalRequest`; `MODERATION_*` в `ServiceAccount`; заменить raw-литералы на константы. Поведение не меняется. **[нужно ваше согласие — много файлов]**
2. **`CacheKeys` + `ProductCache` helper:** централизовать ключи и инвалидацию (+ починить отсутствующий forget `similar_products`). Низкий риск, точечно.
3. **`Transaction::claimForCompletion()`:** вынести идемпотентность (6 копий → 1). Покрыть тестом.
4. **`PricingService::computeOrderTotal()`:** единый расчёт скидок (устраняет расхождение гость/юзер). Тесты на эквивалентность сумм. **[влияет на деньги — покажу диффы до merge]**
5. **`PaymentFulfillmentService`:** вынести общий webhook-pipeline; Mono/Cryptomus делегируют. Крупный шаг. **[нужно ваше согласие]**
6. **`AccountAllocator` + `PurchaseFactory` + `SupplierEarningCalculator`:** разбить God-метод `createProductPurchase`. **[нужно ваше согласие]**
7. **Presenters:** вынести `getStatusText/Badge/Reason` из моделей; починить баг с `$locale`. Backend + blade.
8. **Локали из `config/langs.php`** (backend) + один locale-модуль (frontend). **[много файлов]**
9. **Frontend SSOT:** `utils/money.ts`, унифицированный `getLocalizedField`, `constants/{statuses,storageKeys,endpoints}.ts`, token-interceptor. Покомпонентно.
10. **Магические значения → config/константы** (999, 30, TTL, лимиты загрузок, Option-ключи). Точечно, низкий риск.
11. **Чистка мёртвого кода** (после подтверждения каждого пункта).
12. **Крупные blade/компоненты** (SpaController, support-chat blade, ProfilePage) — разбивка в самом конце, отдельными PR.

---

## Вопросы к вам перед стартом рефакторинга
1. Делать **все** пункты или выбрать подмножество/приоритет? Если всё — в каком порядке (предложен выше)?
2. Шаги 1, 5, 6, 8 затрагивают **много файлов**. Подтверждаете подход (константы/сервисы/конфиг) или хотите обсудить дизайн каждого?
3. Status-словарь backend↔frontend: делать «правильный» SSOT (PHP enum + meta-endpoint/генерация на фронт) или ограничиться отдельными константами на каждой стороне (дешевле, но контракт остаётся ручным)?
4. Мёртвый код (EmptyLayout, lottie, extractKeywords, prepareProductsForPurchase): удалять или оставить? (Удаление lottie уберёт зависимость из package.json.)
5. Презентацию из моделей выносить в **Presenters** (новый слой) или в **API Resources** (которые уже частично есть)?
6. Можно ли менять `bootstrap/cache`-нейтральные внутренние сигнатуры сервисов (они не публичный API), или есть внешние потребители (очереди/cron/другие репозитории), о которых я не знаю?

*Код не изменялся. Жду подтверждения отчёта и ответов на вопросы — после этого начну рефакторинг маленькими шагами с остановками.*
