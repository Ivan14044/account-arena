# Account Arena — План ремедиации и статус исправлений

> Обновлено после поднятия полноценного тулчейна (PHP 8.3 + Node 22 + Composer)
> в пользовательском окружении и прогона фиксов с верификацией.
> Все баги и их `path:line` — в `docs/bugs/00-overview.md` и `docs/bugs/01..10`.

## Окружение верификации (поднято)
- PHP 8.3.31 (static-php-cli, все нужные расширения), Node 22, npm 10, Composer 2.10 — в `~/.local/toolchain`.
- Backend: `composer install` ОК; тесты на sqlite `:memory:` (phpunit.xml). Барьер MySQL-only миграций снят (driver-aware).
- Frontend: `npm install` ОК; верификация через `vite build` + `vue-tsc`.
- **Локальный запуск одной командой** (`scripts/start.sh`): MySQL+Redis в Docker (`docker-compose.yml`), PHP+Vite нативно через тулчейн. Инструкция — `docs/LOCAL_DEV.md`. Верифицировано в браузере через Chrome MCP: каталог рендерится, API 200, консоль без ошибок.
- **CORS для локального dev:** фронт `:3000` ≠ API `:8000`, `config/cors.php` по умолчанию пускает только `APP_URL`. Без `CORS_ALLOWED_ORIGINS=http://localhost:3000` браузер блокировал все запросы («Проблема с сетью»/`AxiosError`), при этом `curl` давал 200 (не проверяет CORS). `scripts/start.sh` теперь прописывает `CORS_ALLOWED_ORIGINS`/`FRONTEND_URL`/`SANCTUM_STATEFUL_DOMAINS` в локальный `.env`. Прод (same-origin SPA) не затронут. Поймано только браузерной проверкой — занесено правилом в CLAUDE.md §2/§3.
- **Фикс миграции на чистой MySQL:** `2025_11_14_141040_add_parent_id_to_categories_table` использовала `->after('type')`, но колонка `type` добавляется более поздней миграцией (`2025_12_01_000000`). На sqlite `after()` игнорируется (поэтому тесты не ловили), на свежей MySQL — падение `Unknown column 'type'`. Теперь `after('type')` применяется только при наличии колонки (поведение-сохраняющий гард; на уже мигрированном проде не выполняется повторно). Верификация: полный `migrate` на чистой MySQL ОК; sqlite-базлайн без изменений (15 failed / 40 passed).
- Базлайн тестов: 21 failed → 15 failed → **0 failed / 63 passed** (полностью зелёный). Оставшиеся 15 «пред-существующих sqlite-артефактов» доведены до нуля в раунде багфиксов:
  - handler-422/401/403 (#43) → +2 dispute-теста; IDOR 403→404 (#50) → +2; promocode case-insensitive lookup + тест-баги `percent_discount`/locale (#51) → +4; `getApiUser` через sanctum-guard + null (#52) → +5 cart/purchase; FK-хардкод `userId`/scaffold ExampleTest (#53) → +2.
  - Реальные code-фиксы (не только тесты): case-insensitive промокоды, `getApiUser` (guard-резолв + типобезопасность), 404-вместо-403 на чужих покупках.

---

## ✅ СДЕЛАНО (закоммичено на ветке `refactor/security-hardening`, верифицировано)

| ID | Severity | Фикс | Верификация |
|---|---|---|---|
| C2 | Crit | `/browser/*` под `auth:sanctum`+throttle | route:list, фронт авторизован |
| C3 | Crit | `BrowserController::new` проверяет владение профилем (только свои completed-покупки) | php -l, разбор модели |
| C5 | Crit | Экранирование (`escapeHtml`) в live-поллере админ-чата (был stored XSS аноним→админ) | разбор обоих render-путей |
| C6 | Crit | Атомарная идемпотентность вебхуков Mono+Cryptomus (6 хендлеров) — двойная выдача | php -l, no-regress |
| C7 | Crit | `admin.main` на settings/site-content (секреты, SSRF) | route:list |
| C8 | Crit | CORS из ENV (не `*`+credentials) | config loads |
| C9 | Crit | Создан `App\Mail\BaseMail` (письма падали молча) | Mailable->render() = OK |
| H4 | High | Проверка `secret_token` Telegram-вебхука (+setWebhook) | php -l, gated |
| H5 | High | Сброс модерации при правке товара поставщиком | **тест PASS** |
| H7 | High | Unique `promocode_usages.order_id` + идемпотентный учёт | миграция применяется |
| H10 | High | Атомарная идемпотентность top-up (Mono+Cryptomus) | php -l |
| H15 | High | Статус-чек в `store()` + unique `product_disputes.transaction_id` | **2 теста PASS** |
| M1 | Med | Инвалидация `active_accounts_list_v4` (кэш каталога) | **2 теста PASS** |
| M2 | Med | Фильтр модерации в `getSimilarProducts` (утечка немодерированных) | разбор запроса |
| M4 | Med | `safeRedirectPath` — anti open-redirect на логине | vite build |
| M5 | Med | `JSON_HEX_TAG` в JSON-LD (SSR) — `</script>`-breakout | php -l, флаг во всех 5 |
| M9 | Med | `resolveReplacement` сверяет service_id/supplier_id | php -l |
| M10 | Med | auto-close: реальный админ + актуальный refund_amount; null-guard notifySupplier | php -l |
| M11 | Med | throttle на `/contents/{code}` | route |
| M12 | Med | Учёт флага `deduct_from_supplier` | php -l |
| B5/H2 | High | DOMPurify + `v-safe-html` во всех 15 серверных `v-html` | **vite build OK** |
| infra | — | Driver-aware миграции; `HasFactory` для Transaction | сьют 21→15 падений |
| docs | — | Полная документация функционала + багов + поправки | — |

**Поправки к находкам агентов (проверено и зафиксировано):**
- **«Переплата поставщику» (отчёт 04 BUG-01) — ЛОЖЬ.** Код платит поставщику его базовую цену (наценка сверху для покупателя). Исправлен только doc-комментарий.
- **TrustProxies (H14) — ЛОЖЬ/инверсия.** `$proxies = null` = доверять никаким (безопасно). НЕ трогать.
- **C4 (Telegram social-login) — фактически НЕ эксплуатируется** в текущей реализации: `validateTelegramData` включает все поля (в т.ч. инжектированный email) в HMAC, поэтому несигнированный email подделать нельзя. Тем не менее применено **defense-in-depth**: линковка только по `telegram_id`.
- **C1 (CSRF) — практически НИЖЕ заявленного:** `config/session.php` уже `same_site = 'lax'`, поэтому cross-site POST не несёт сессионную cookie → классический CSRF на админку/кабинет уже блокируется. См. ниже про полное включение токенов.
- **C8 (CORS)** — реальный мисконфиг (исправлен), но эксплуатируемость браузерами при `*`+credentials и так ограничена.

---

## ⏳ ОСТАЁТСЯ (требует браузерного QA / координации деплоя / операционных действий)

### C1 — полное включение CSRF-токенов (defense-in-depth поверх SameSite=Lax)
Уже частично закрыто `same_site=lax`. Полное включение токенов рискованно без браузерного QA: админка на **AdminLTE** с миксом `fetch` (3+ файла, в т.ч. чат-поллер) и jQuery (5 файлов). Шаги:
1. `VerifyCsrfToken::$except` → оставить только `api/*`-вне-web и `auth/telegram/callback`.
2. `<meta name="csrf-token">` на всех admin/supplier страницах (через конфиг/layout AdminLTE).
3. Глобально: `$.ajaxSetup({headers:{'X-CSRF-TOKEN':…}})` + обёртка `window.fetch`, добавляющая заголовок для same-origin мутаций.
4. Прокликать каждое AJAX-действие админки и кабинета.

### H1 — IDOR гостевых покупок
Гость читает `/purchases/{id}?guest_email=…` (креды) по совпадению email. Фикс: подписанные ссылки (`URL::signedRoute`) или одноразовый order-token; правка `OrderSuccessPage.vue` и письма. Требует прогона гостевого флоу в браузере.

### H11 — refund-and-keep
Возврат не отзывает выданные креды. Полноценный фикс — операционный (ротация пароля аккаунта на стороне поставщика/системы). В системе можно дополнительно помечать `Purchase` как `refunded`.

### H13 — extension `sc_auth` токен
Проверять ability `extension` на чувствительных роутах; cookie httpOnly+secure, срок жизни/ротация.

### B5/H3 (серверная часть) — санитизация HTML при ЗАПИСИ
Клиентский рендер уже защищён (DOMPurify). Для глубины добавить серверный санитайзер (`mews/purifier`) при сохранении rich-text и в SSR (`seo/article.blade.php` рендерит `{!! $seoText !!}` сырым).

### Прочее (Low/Med)
- M3: audit-log не должен писать секреты/`account_data`; логировать только на 2xx.
- ✅ **Лимит промокодов для гостей — ИСПРАВЛЕНО** (PR #55): один email — не более 5 использований одного промокода (`PromocodeValidationService::GUEST_USAGE_LIMIT`). Колонка `promocode_usages.guest_email` (миграция проверена на чистой MySQL: up+down), запись при гостевых покупках, проверка в Guest/Mono/Cryptomus. +2 теста.
- ✅ **User enumeration в forgot/reset — ИСПРАВЛЕНО** (PR #45): убран `exists:users,email`, generic-ответы forgot/reset (broker не раскрывает существование email).
- Dead code: `extractKeywords()`/`EmptyLayout`/осиротевшие Lottie — удалены ранее (step 11). `SupportMessageReaction` — **используется** (`SupportMessage`/`SupportChatController`), не мёртвый; не трогаем.
- Полная sqlite-портируемость остальных миграций (для зелёного тест-сьюта в CI) — *остаётся* (~13 пред-существующих падений: case-insensitive collation в promocode-тестах, cart-флоу на sqlite, `/`→404, supplier_id NOT NULL, IDOR-тесты). Высокий риск (миграции) — отдельной задачей.

### ✅ Исправлено (баги, найденные при рефакторинге)
- ✅ **API exception handler маскировал ошибки под 500** (PR #43): `Handler::render` для api/* отдавал 500 на любой контроллерный `ValidationException` (без `errors`), `AuthenticationException`, `AuthorizationException`. Теперь 422+errors / 401 / 403. Это был корень симптома **`POST /vouchers/activate` → 500 вместо 422**. Регресс-тест `ExceptionHandlerTest`; сьют 16→13 падений.
- ✅ **SSR `/categories` → 500 — ИСПРАВЛЕНО** (PR #44): `categories` не имеет колонок `is_active`/`sort_order` (запрос падал дважды). Теперь `Category::productCategories()->orderBy('id')`. Проверено реальным SSR на MySQL (500→200).
- **ProfilePage: автообновление processing-заказов/таймер никогда не работали** — `start*` не вызывались (мёртвый код удалён в step 12.5). Это **фича-решение** (нужно ли авто-refresh + нагрузка на сервер), а не баг рефакторинга — намеренно НЕ включал. Если нужно — `start*` в `onMounted`, `stop*` в `onBeforeUnmount`.

### ✅ Исправлено (продолжение)
- ✅ **SSR `/categories/{slug}` деталь → 404 — ИСПРАВЛЕНО** (PR #47): причина — `generateCategoryContent` запрашивал несуществующую колонку `service_accounts.stock_count`; QueryException молча сворачивался в 404 в `getCategoryMetaTags`. Убран `stock_count`, фильтр продуктов приведён к каталожному (`is_active` + видимость по модерации); в catch добавлен `\Log::warning` (ошибки рендера больше не маскируются под 404). Проверено реальным SSR на MySQL: 404→200.

---

## Админ-панель: мобильная адаптивность и единый стиль (ветка `feat/admin-ui-mobile-harmony`)

> Аудит всех страниц `/admin` на мобильном вьюпорте (375px, реальная эмуляция
> устройства) + десктопе. Метод: предпросмотр воркт-ри-бэкенда + измерение
> горизонтального переполнения каждой страницы в 375px-iframe (срабатывают
> mobile media-queries) и визуальные скриншоты.

### Результаты аудита
- **Мобильная адаптивность уже на хорошем уровне:** ни одна страница списков/форм
  не даёт горизонтального переполнения в 375px. Таблицы обёрнуты в
  `.table-responsive` (скролл внутри), таблица товаров на мобильном превращается
  в карточки, stat-карточки складываются в сетку. Существующая система
  per-page mobile-секций в `layouts/modern-styles.blade.php` покрывает большинство.
- **Главная проблема консистентности была не в вёрстке, а в языке:** ~17 CRUD-форм
  и страница входа рендерили часть UI по-английски на русскоязычной панели.

### ✅ Сделано (верифицировано в браузере, тесты зелёные к базлайну)
- **Страница входа** переписана со standalone-дизайном под фирменный стиль панели
  (SB Admin 2): градиент, брендовая карточка, иконки, show/hide пароля, полностью
  по-русски, без неуместной ссылки «Register a new membership». Сообщения об
  ошибках входа переведены. Покрыто `tests/Feature/AdminLoginTest` (4 теста).
- **Локализация CRUD-форм** (articles, admins, profile, contents, email-templates,
  pages, service-accounts, users/edit, settings, site-content): весь видимый UI
  переведён на русский; `name/id/route/value`, логика, JS и токены-переменные не
  тронуты. На вкладке EN редактора контента подписи стали русскими (значения
  полей остаются на своём языке).
- **Навбар:** ссылка выхода `Log Out` → `Выйти` через override строки
  `adminlte::adminlte.log_out` (без смены глобальной локали; чинит и кабинет
  поставщика).
- **Единый «почерк» хедеров (26 вью):** create/edit/show формы переведены с
  дефолтного AdminLTE `<h1>` на стилизованный `content-header-modern` (лёгкий
  заголовок + приглушённый подзаголовок + адаптивная кнопка «Назад к списку» /
  действия), подключают общий `modern-styles`, кнопки формы — `btn-modern` со
  стэком на мобильном. Теперь формы выглядят так же, как страницы-списки.
- В `modern-styles` добавлены утилиты `gap-*` и `w-md-auto` (в Bootstrap 4 их
  нет), на которые уже опирались хедеры списков — попутно починены их кнопки
  действий, растягивавшиеся на всю ширину на десктопе.
- Мелкие правки: опечатка «Тауймаут» → «Таймаут».

### Остаётся (опционально, низкий приоритет)
- Несколько show-страниц (`disputes/show`, `suppliers/show`, …) уже выглядят
  «современно», но за счёт собственных inline-стилей, а не общего `modern-styles`
  — визуально консистентны, но источник стилей разный.

> Stat-карточки `users`/`service-accounts` приведены к единому мобильному
> паттерну (2×2) — сделано.

---

## Публичный фронтенд (SPA): мобильный аудит и единый язык (ветка `feat/web-mobile-harmony`)

> Аудит всех 18 публичных маршрутов SPA через клиентский роутер на реальном
> вьюпорте 375px (true emulation) + десктоп. Метод: объективный замер
> горизонтального переполнения по каждому маршруту + визуальные скриншоты.

### Результаты аудита
- **Мобильная адаптивность уже отличная:** все 18 маршрутов без горизонтального
  переполнения в 375px; страницы (главная, вход, карточка товара, become-supplier,
  checkout, контакты, FAQ и др.) аккуратно адаптируются (Vuetify/Tailwind).
- **SPA хорошо интернационализирована** (vue-i18n, ru/en/uk). Реальная проблема —
  не вёрстка, а точечные пробелы локализации (аналог англ-меток в админке).

### ✅ Сделано (верифицировано в браузере, `vite build` зелёный)
- **Паритет локалей:** ru.json не хватало 7 ключей `profile.purchases.*`
  (`last_update*`, `status_history`, `changed_by`, `chat_not_available`),
  которые использует `ProfilePage` — рус. пользователи видели англ. fallback.
  Добавлены рус. переводы (и 4 недостающих `last_update*` в uk.json). Теперь все
  три локали в полном паритете (642 ключа, 0 пропусков).
- **Страница 404** (`NotFound.vue`) показывала хардкод-английский («Page not
  found», «Go back to home») на рус. сайте → переведена на i18n (`notFound.*`).
- **Переключатель темы** (`ThemeSwitcher`) — хардкод-русские legend/aria-labels,
  видимые EN/UK пользователям → i18n (`theme.*`).
- **Чат поддержки**: хардкод-русский тултип «Прикрепить файл» + англ. aria-labels
  (scroll-to-top, breadcrumb, close-menu) → i18n (`supportChat.attachFile`,
  `common.*`).

---

## Как воспроизвести верификацию
```bash
export PATH="$HOME/.local/toolchain/php:$HOME/.local/toolchain/node/bin:$HOME/.local/toolchain/bin:$PATH"
# backend
cd backend && php artisan test
# frontend
cd ../frontend && npx vite build && npx vue-tsc --noEmit
```
