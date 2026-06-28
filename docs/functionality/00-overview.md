# Account Arena — Полный каталог функционала

> Мастер-документ. Точка входа для поиска багов «по каждому функционалу».
> Детализация каждого модуля — в отдельных файлах `docs/functionality/01..10`.
> Все ссылки на код даны в формате `path:line` внутри детальных документов.

---

## 1. Что это за проект

**Account Arena** — маркетплейс цифровых товаров (продажа аккаунтов/учётных записей и доступов). Покупатель выбирает товар, оплачивает (внутренний баланс, крипта Cryptomus, карта Monobank), получает учётные данные автоматически (из стока) или вручную (через админа), может оспорить покупку, а также запустить купленный аккаунт в **удалённом стриминговом браузере (KASM / browser_api)** и через **браузерное расширение**. Поставщики (suppliers) загружают товар и выводят заработок. Администраторы модерируют товары, обрабатывают споры, ручные выдачи, заявки на вывод и ведут CMS.

### Технологический стек

| Слой | Технологии |
|---|---|
| **Backend** | Laravel (PHP), Sanctum (токены/SPA-сессии), AdminLTE (Blade-админка), очереди, планировщик (cron) |
| **Frontend** | Vue 3 + Vuetify 3, Pinia (+persistedstate), Vue Router, Vue I18n, Tailwind, Vite, Axios |
| **Платежи** | Cryptomus (крипто), Monobank (карты, ECDSA-подпись вебхуков) |
| **Интеграции** | KASM Workspaces / `browser_api` (стриминг браузера), Telegram Bot (поддержка + уведомления + вход), Google OAuth, Facebook Pixel |
| **Хранилище** | MySQL (миграции), файловое хранилище (storage) для вложений/картинок |

### Поверхности приложения (точки входа)

1. **Публичный SPA (витрина)** — `frontend/`, отдаётся через `Seo\SpaController` с SSR-инъекцией мета-тегов.
2. **REST API** — `routes/api.php` (для SPA, гостей, расширения, вебхуков, Telegram).
3. **Админка (Blade/AdminLTE)** — `routes/web.php` prefix `/admin`.
4. **Кабинет поставщика (Blade)** — `routes/web.php` prefix `/supplier`.
5. **SEO/SSR-роуты** — `routes/web.php` (sitemap, мета-инъекция, 301-редиректы).
6. **Вебхуки** — Cryptomus, Monobank, Telegram.

### Роли и их признаки (на модели `User`, булевы колонки)

| Роль | Признак | Доступ |
|---|---|---|
| Гость | нет токена | витрина, гостевая покупка (только карта/крипта), гость-чат |
| Покупатель (user) | авторизован | витрина, баланс, покупки, споры, чат, расширение, запуск аккаунтов |
| Поставщик (supplier) | `is_supplier = true` | кабинет `/supplier`, товары, заказы, скидки, выводы, споры (read) |
| Админ | `is_admin = true` | админка `/admin` |
| Главный админ | `is_main_admin = true` | + управление админами, ручная выдача, удаление промокодов и т.д. |
| Статусы | `is_blocked`, `is_pending` | блокировка / ожидание подтверждения |

> ⚠️ Самостоятельной регистрации поставщика нет: страница «стать поставщиком» — маркетинговая, ведёт в Telegram; роль `is_supplier` выставляет админ вручную.

---

## 2. Карта функциональных модулей

| # | Модуль | Документ | Ключевые акторы |
|---|---|---|---|
| 01 | Аутентификация, пользователи, профиль, контроль доступа | [01-auth-users.md](01-auth-users.md) | гость, user, supplier, admin |
| 02 | Каталог и витрина (категории, товары, статика, CMS-чтение) | [02-catalog-browsing.md](02-catalog-browsing.md) | гость, user |
| 03 | Корзина, оформление, покупки, выдача (авто/ручная) | [03-cart-checkout-purchases.md](03-cart-checkout-purchases.md) | гость, user, admin |
| 04 | Платежи, баланс, транзакции, промокоды, ваучеры, выводы, заработок поставщика | [04-payments-balance.md](04-payments-balance.md) | user, supplier, admin |
| 05 | Кабинет поставщика | [05-supplier-panel.md](05-supplier-panel.md) | supplier, admin |
| 06 | Админ-панель (backoffice) | [06-admin-panel.md](06-admin-panel.md) | admin, main-admin |
| 07 | Споры по товарам (disputes) | [07-disputes.md](07-disputes.md) | user, admin, supplier |
| 08 | Чат поддержки + Уведомления (in-app/email/telegram) | [08-support-notifications.md](08-support-notifications.md) | гость, user, supplier, admin |
| 09 | KASM-запуск аккаунтов, расширение, Telegram-бот, SEO/SSR, CLI | [09-kasm-extension-seo.md](09-kasm-extension-seo.md) | user, бот, краулеры |
| 10 | Архитектура фронтенда (SPA shell) | [10-frontend-architecture.md](10-frontend-architecture.md) | все |

---

## 3. Полный перечень функционала (чек-лист для поиска багов)

### 3.1. Аутентификация и пользователи (→01)
- [ ] Регистрация (email/пароль/имя/язык), выдача двух Sanctum-токенов (SPA + расширение `sc_auth`)
- [ ] Вход по логину/паролю; `remember` → срок токена 30 дней; проверки `is_blocked`/`is_pending`
- [ ] Вход через Google OAuth (popup + postMessage, плюс альтернативный URL-token путь)
- [ ] Вход через Telegram (HMAC-подпись, проверка свежести 24ч, линковка по `telegram_id`)
- [ ] Выход (API — отзыв ВСЕХ токенов + очистка cookie; web/admin — инвалидация сессии)
- [ ] Сброс пароля (forgot/reset, брокер-токены, локализованное письмо)
- [ ] Получение/обновление текущего пользователя, редактирование профиля
- [ ] Контроль доступа: middleware `auth:sanctum`, `admin.auth`, `admin.main`, `supplier.auth`, `ext.auth`, `audit.admin`, `SetLocale`
- [ ] Админ-логин (сессионный, только `is_admin`)
- [ ] (не реализовано/не подключено: email-верификация, 2FA)

### 3.2. Каталог и витрина (→02)
- [ ] Листинг товаров `GET /accounts` (один payload + клиентские фильтр/поиск/сортировка/пагинация, кэш 5 мин)
- [ ] Детальная карточка товара `GET /accounts/{id}` (поиск по id/sku/slug, инкремент просмотров, SEO-мета, канонический редирект)
- [ ] Похожие товары `GET /accounts/{id}/similar` (3-ступенчатый алгоритм, кэш 1ч)
- [ ] Категории и подкатегории (дерево, тип product/article, локализация `CategoryResource`)
- [ ] Статьи (листинг + детали, фильтр `published`, пагинация)
- [ ] Баннеры, статические страницы (`/pages` + динамический catch-all), контент-блоки `/contents/{code}`
- [ ] Site-content и публичные опции (`/options` whitelist), правила покупки, настройки чата
- [ ] Страницы FAQ / Контакты / Гарантии (i18n; форма контактов — фронтенд-only)
- [ ] Мультиязычность (ru-базовый fallback)
- [ ] Видимость/модерация: `is_active = true AND (moderation_status='approved' OR supplier_id IS NULL)`

### 3.3. Корзина, оформление, покупки, выдача (→03)
- [ ] Корзина — полностью клиентская (Pinia + localStorage), серверного «add to cart» нет
- [ ] Оформление: 3 пути — баланс (`POST /cart`, синхронно), карта, крипта (финализация в вебхуке)
- [ ] Гость может оплатить только картой/криптой
- [ ] Создание покупок: `ProductPurchaseService` (prepare → createMultiple → createProductPurchase)
- [ ] Автовыдача: выборка кредов из `accounts_data[used..]`, инкремент `used`, блокировки `lockForUpdate`
- [ ] Ручная выдача: создание `processing`-заказа пустым, обработка админом (`admin.main`)
- [ ] 6 статусов покупки (pending/processing/completed/failed/cancelled/refunded) + история переходов
- [ ] Ожидание стока (`is_waiting_stock`): `process:waiting-stock-orders` (каждые 30 мин)
- [ ] Просрочки ручных заказов: `notify:overdue-manual-orders` (ежечасно, эскалация >24ч)
- [ ] Скачивание/просмотр купленного, отмена покупки, статистика обработки
- [ ] Конкурентный доступ к стоку (3 слоя защиты), идемпотентность вебхуков (guard по transaction id)

### 3.4. Платежи, баланс, финансы (→04)
- [ ] Внутренний баланс: `User.balance` (источник истины) + `BalanceTransaction` (леджер) + `Transaction` (интент)
- [ ] Кредит/дебет баланса, история, статистика, проверка достаточности средств
- [ ] Cryptomus: создание платежа (auth/guest/topup), вебхук c MD5-`sign`, IP-allowlist middleware
- [ ] Monobank: инвойс (minor-units, ISO-валюты), вебхук ECDSA `X-Sign`, `genMonoPubKey.php`
- [ ] Пополнение баланса (оба провайдера)
- [ ] Транзакции (типы, статусы, маппинг методов оплаты)
- [ ] Промокоды: валидация (paused/expired/exhausted/scheduled), тип скидки, лимиты общие/на пользователя, кап 99%, атомарный учёт использования, bulk-создание
- [ ] Ваучеры: активация (race-safe), зачисление на баланс
- [ ] Заработок поставщика: комиссия/наценка, начисление за продажу, холд (default 6ч), `suppliers:release-earnings` (каждые 5 мин), реверс
- [ ] Заявки на вывод: модель + статусы (pending→approved→paid / rejected)

### 3.5. Кабинет поставщика (→05)
- [ ] Логин поставщика (общий guard `web` + проверка `is_supplier`)
- [ ] Дашборд (метрики 30 дней, сток, продано, топ-5, рейтинг, ленивая разблокировка холда)
- [ ] Товары: создание/редактирование/удаление, bulk-загрузка стока, экспорт (под row-lock), загрузка картинок; новый товар → `pending` + `is_active=false`
- [ ] Заказы (read-only, только completed по своим товарам)
- [ ] Скидки (discount_percent + окно дат, 1–99%, проверка владения)
- [ ] Выводы: available vs held, запрос/отмена, реквизиты
- [ ] Уведомления поставщика (in-panel)
- [ ] Споры (read-only, scoped)
- [ ] Рейтинг поставщика (90-дн «% валидных продаж», `suppliers:recalculate-ratings` ежедневно 03:00)

### 3.6. Админ-панель (→06)
- [ ] Контроль доступа: `admin.auth` / `admin.main` / `audit.admin` (+ инлайн-проверки `is_main_admin`)
- [ ] Дашборд: период (today…custom), кэш статистики 10 мин, KPI, графики (продажи/категории/топ-5)
- [ ] Пользователи: CRUD, блокировка, ручная корректировка баланса (BalanceService + AuditLog)
- [ ] Админы/персонал: CRUD (main-admin only), защита главного админа и себя
- [ ] Service Accounts (товар/сток): создание/bulk, обновление с сохранением проданного, export/import, bulkAction (JSON), загрузка картинок, сортировка, заметки, инвалидация кэша
- [ ] Модерация товаров поставщиков: approve/reject (row-lock, валидация, уведомление поставщику)
- [ ] Промокоды и их использования (bulk, удаление — main-admin only)
- [ ] Ваучеры (bulk-генерация с защитой от коллизий)
- [ ] Покупки (read-only + ограниченное удаление), правила покупки
- [ ] CMS: страницы, контенты (repeatable + загрузка файлов), site-content (табы Option, JSON-меню), статьи, категории статей/товаров и подкатегории
- [ ] Email-шаблоны (+ test-SMTP, XSS-санитизация), шаблоны уведомлений (системные неудаляемы), массовая рассылка уведомлений
- [ ] Настройки: SMTP, Telegram (валидация токена + webhook), pixel, споры, чат, тумблеры уведомлений; секреты шифруются в `Option`
- [ ] Аудит-логи (что пишет `AuditAdminActions`: мутирующие методы, со срытием паролей)
- [ ] Уведомления админа (bell-поллер) + настройки по типам
- [ ] Управление поставщиками и их настройками
- [ ] Обработка заявок на вывод (FIFO по earnings, синхронизация баланса)

### 3.7. Споры по товарам (→07)
- [ ] Право на спор (`canDispute`: нет существующего, есть `service_account_id`, ≤30 дней, статус транзакции)
- [ ] Открытие спора (причина + обязательный скриншот файл ≤5МБ или URL)
- [ ] Стейт-машина: `new → in_review → resolved(refund|replacement)/rejected`
- [ ] Решение админа: возврат (на баланс + клоубэк earnings поставщика), замена (атомарно, lock стока), отклонение
- [ ] Видимость поставщику (read-only), влияние на рейтинг/заработок
- [ ] Авто-закрытие (`disputes:auto-close` ежечасно, авто-возврат «молчащих» продавцов)
- [ ] Бейдж new-count (кэш 30с)
- [ ] Уведомления по событиям спора (created → админам, resolved → покупателю, supplier-notification)

### 3.8. Чат поддержки и уведомления (→08)
- [ ] Чат поддержки: создание/получение (гость по email/токену vs user), отправка/приём сообщений
- [ ] Вложения (лимиты 10МБ/файл, 50МБ/запрос, квоты на чат, проверка свободного места)
- [ ] Реакции (модель есть, но **не используется** — нет контроллера/роута/UI)
- [ ] Индикаторы набора (хранятся в Cache, TTL 5с), поллинг (WebSocket'ов нет)
- [ ] Назначение чата админу, статусы (pending→open→closed), внутренние заметки, unread-count, оценка чата
- [ ] Telegram как канал чата (входящий вебхук создаёт `source=telegram` чаты, ответы форвардятся)
- [ ] Уведомления: in-app user (`Notification` + шаблоны, поллинг bell 10с)
- [ ] Уведомления админа (`AdminNotification`, общая строка на событие, тумблеры `AdminNotificationSetting`)
- [ ] Уведомления поставщика (`SupplierNotification`)
- [ ] Email (`EmailService` + DB-шаблоны, динамический SMTP из Options) — ⚠️ ссылается на отсутствующий `App\Mail\BaseMail`/`emails.base`
- [ ] 4 раздельных механизма уведомлений без единого диспетчера

### 3.9. KASM / расширение / Telegram / SEO / CLI (→09)
- [ ] KASM-клиент (`arthur-salenko/kasm-client`): createUser/requestKasm/getKasmStatus/destroyKasm/getImages
- [ ] Боевой запуск: `GET /browser/new` + inline `/browser/stop`, `/stop_all`, `/list` (прокси на `BROWSER_API_URL`) — ⚠️ без авторизации
- [ ] Фронт запуска: `browserSessions.ts` + `useServiceLauncher.ts` (hardened popup, анти-devtools)
- [ ] Расширение: cookie-аутентификация `sc_auth` (ability `extension`), сохранение настроек, статус
- [ ] Telegram-бот: вебхук поддержки (без проверки подписи), `/start`, линковка по `telegram_id`, исходящие уведомления
- [ ] SEO/SSR: `SpaController` (мета, JSON-LD @graph, canonical, hreflang ru/en/uk+x-default, 404+noindex), `SitemapController` (кэш 24ч), 301-карта редиректов, robots.txt
- [ ] Фронт-SEO: `useSeo`/`useHreflang`/`useStructuredData`/`useProductTitle`, FacebookPixel, CookieBanner
- [ ] CLI/cron-расписание: recalculate-ratings (03:00), release-earnings (5 мин), overdue-manual-orders (час), waiting-stock-orders (30 мин), auto-close disputes (час) + ручные команды (slugs/normalize/fix/diagnose)

### 3.10. Архитектура фронтенда (→10)
- [ ] Bootstrap: axios → i18n → router → vuetify → pinia(+persist) → toast → lottie → директива
- [ ] 23 маршрута (5 статических + lazy), catch-all `/:slug` для CMS, гварды `requiresAuth`/`requiresGuest`
- [ ] 15 Pinia-сторов (персистятся только `productCart` и `promo`)
- [ ] Layout: `DefaultLayout` (хардкод), `EmptyLayout` — dead code
- [ ] i18n: en/uk/ru (default uk), `X-Locale` на каждом запросе, синхронизация с бэком
- [ ] Темизация: Tailwind class-dark, `useTheme` singleton, persist + cross-tab sync
- [ ] Глобальный UX: лоадеры, тосты/sweetalert2, cookie-баннер, scroll-to-top, breadcrumbs, lazy-images
- [ ] Axios: `withCredentials` (Sanctum CSRF) + `X-Locale` интерсептор + тост-интерсептор ошибок

---

## 4. Сводная карта роутов

- **API** (`routes/api.php`): auth (register/login/forgot/reset/logout/user), публичные (accounts, articles, categories, pages, options, banners, site-content, contents, support-chat, guest cart+payments), авторизованные (transactions, notifications, cart, vouchers, disputes, purchases, balance/*), платежи (cryptomus/mono create+topup), вебхуки (cryptomus/mono/telegram), browser/* , extension/*.
- **Web** (`routes/web.php`): `/admin/*` (≈30 разделов), `/supplier/*` (8 разделов), SEO/SSR (`SpaController`), sitemap, 301-редиректы, `/auth/google*`, `/auth/telegram/callback`.

---

## 5. Заметки для фазы поиска багов (предварительные находки агентов)

Эти пункты всплыли при документировании и являются **кандидатами** для проверки на фазе багхантинга (не подтверждено эксплуатацией):

1. **Утечка email-существования**: forgot/reset-password используют `exists:users,email` → 422 для неизвестных email.
2. **forgot-password сбрасывает per-IP лимитер** брокера паролей.
3. **Две разные мин. длины пароля** (API=6, web/UI=8).
4. **Logout удаляет все токены** — убивает расширение и все устройства сразу.
5. **Утечка немодерированных товаров** в карусель «похожие» (`getSimilarProducts` фильтрует только `is_active`).
6. **Несогласованность цены**: карточки используют `current_price`, а герой детальной — сырой `price`.
7. **Top-up идемпотентность ограничена 24ч** → ретрай провайдера >24ч может дважды зачислить пополнение.
8. **Гости обходят per-user лимиты промокодов** (нет email/IP fallback).
9. **`/browser/*` без авторизации** (stop/stop_all/list — закрытие чужих сессий?).
10. **Telegram-вебхук без проверки подписи**.
11. **EmailService → отсутствующий `App\Mail\BaseMail`/`emails.base`** (письма могут падать).
12. **Два пересекающихся SEO-инъектора** (`SpaController` vs `InjectSpaMetaTags`) с разными canonical.
13. **`deduct_from_supplier` чекбокс валидируется, но игнорируется** (клоубэк всегда).
14. **`canDispute` vs `store`**: разная логика связи с товаром и проверки статуса транзакции.
15. **Неиспользуемый код**: `SupportMessageReaction`, `extractKeywords()`, `EmptyLayout`, осиротевшие Lottie-ассеты.
16. **Гостевые вебхуки/частые HTTP 200 при внутренних ошибках** → расхождения сверяются по логам.

> Полный разбор каждого пункта — на следующей фазе. Здесь они зафиксированы, чтобы ничего не потерять.

---

*Сгенерировано параллельным анализом 10 подсистем. Детали и `path:line`-ссылки — в файлах 01–10.*
