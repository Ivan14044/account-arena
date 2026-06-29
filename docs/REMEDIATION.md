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
- Базлайн тестов улучшен: было **21 failed / 11 passed** → стало **15 failed / 22 passed** (+5 новых регресс-тестов). Оставшиеся 15 падений — пред-существующие артефакты sqlite (case-insensitive collation, `/`→404 без SSR-индекса, supplier_id NOT NULL на sqlite, factory-нюансы), НЕ связаны с правками.

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
- Per-user лимит промокодов для гостей (fallback по email/IP).
- User enumeration в forgot/reset (`exists:users,email`).
- Dead code: `SupportMessageReaction`, `extractKeywords()`, `EmptyLayout`, осиротевшие Lottie.
- Полная sqlite-портируемость остальных миграций (для зелёного тест-сьюта в CI).

---

## Как воспроизвести верификацию
```bash
export PATH="$HOME/.local/toolchain/php:$HOME/.local/toolchain/node/bin:$HOME/.local/toolchain/bin:$PATH"
# backend
cd backend && php artisan test
# frontend
cd ../frontend && npx vite build && npx vue-tsc --noEmit
```
