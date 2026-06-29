#!/usr/bin/env bash
# ============================================================================
#  Account Arena — запуск сайта одной командой (для локальной разработки).
#
#  Что делает:
#    1. Поднимает MySQL + Redis в Docker.
#    2. Готовит бэкенд (Laravel): .env, зависимости, ключ, миграции, демо-данные.
#    3. Готовит фронтенд (Vue/Vite): зависимости.
#    4. Запускает бэкенд (порт 8000) и фронтенд (порт 3000) в фоне.
#
#  Использование:
#    ./scripts/start.sh          обычный запуск
#    ./scripts/start.sh --fresh  пересоздать базу с нуля (СОТРЁТ локальные данные)
#
#  Остановить всё:  ./scripts/stop.sh
# ============================================================================
set -euo pipefail

# --- Цвета для читаемого вывода ---------------------------------------------
B="\033[1m"; G="\033[1;32m"; Y="\033[1;33m"; R="\033[1;31m"; C="\033[1;36m"; N="\033[0m"
say()  { echo -e "${C}▶ ${1}${N}"; }
ok()   { echo -e "${G}✔ ${1}${N}"; }
warn() { echo -e "${Y}! ${1}${N}"; }
die()  { echo -e "${R}✗ ${1}${N}"; exit 1; }

# --- Пути --------------------------------------------------------------------
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
BACKEND="$ROOT/backend"
FRONTEND="$ROOT/frontend"
STATE="$ROOT/.dev-state"
LOGS="$STATE/logs"
mkdir -p "$LOGS"

FRESH=0
[[ "${1:-}" == "--fresh" ]] && FRESH=1

# --- 0. Тулчейн (PHP / Node / Composer) -------------------------------------
say "Подключаю тулчейн (PHP 8.3 + Node 22 + Composer)…"
# shellcheck disable=SC1090
source "$HOME/.local/toolchain/env.sh" 2>/dev/null || die "Не найден ~/.local/toolchain/env.sh — тулчейн не установлен."
command -v php >/dev/null      || die "php не найден после подключения тулчейна."
command -v composer >/dev/null || die "composer не найден после подключения тулчейна."
command -v node >/dev/null     || die "node не найден после подключения тулчейна."
ok "PHP $(php -r 'echo PHP_VERSION;')  ·  Node $(node -v)  ·  Composer есть"

# --- 1. Docker: MySQL + Redis -----------------------------------------------
say "Проверяю Docker…"
command -v docker >/dev/null || die "Docker не установлен. Установи Docker Desktop и запусти его."
docker info >/dev/null 2>&1   || die "Docker установлен, но не запущен. Открой Docker Desktop и подожди, пока он стартует."
ok "Docker работает"

if [[ $FRESH -eq 1 ]]; then
  warn "Режим --fresh: удаляю старую базу данных…"
  docker compose -f "$ROOT/docker-compose.yml" down -v >/dev/null 2>&1 || true
  rm -f "$STATE/initialized"
fi

say "Поднимаю MySQL и Redis…"
docker compose -f "$ROOT/docker-compose.yml" up -d

say "Жду, пока база данных будет готова (это может занять до минуты при первом запуске)…"
for i in $(seq 1 60); do
  if docker compose -f "$ROOT/docker-compose.yml" exec -T mysql \
       mysqladmin ping -h localhost -psubcloudy >/dev/null 2>&1; then
    ok "MySQL готов"; break
  fi
  [[ $i -eq 60 ]] && die "MySQL не поднялся за отведённое время. Попробуй ещё раз или перезапусти Docker."
  sleep 2
done

# --- 2. Бэкенд: .env --------------------------------------------------------
if [[ ! -f "$BACKEND/.env" ]]; then
  say "Создаю backend/.env (локальные настройки)…"
  cat > "$BACKEND/.env" <<'ENV'
APP_NAME="Account Arena"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=subcloudy
DB_USERNAME=subcloudy
DB_PASSWORD=subcloudy

REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null

SESSION_DRIVER=redis
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis

# Письма пишутся в лог-файл, реальная почта не нужна для локалки
MAIL_MAILER=log

# Внешние интеграции (платежи, Google, Telegram) для локального просмотра не нужны
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
MONOBANK_TOKEN=
MONOBANK_PUBLIC_KEY=
ENV
  ok "backend/.env создан"
else
  ok "backend/.env уже есть — не трогаю"
fi

# --- 3. Бэкенд: зависимости -------------------------------------------------
if [[ ! -d "$BACKEND/vendor" ]]; then
  say "Ставлю PHP-зависимости (composer install) — первый раз дольше…"
  (cd "$BACKEND" && composer install --no-interaction --prefer-dist)
  ok "PHP-зависимости установлены"
else
  ok "PHP-зависимости уже на месте"
fi

# --- 4. Бэкенд: ключ приложения ---------------------------------------------
if ! grep -q '^APP_KEY=base64:' "$BACKEND/.env"; then
  say "Генерирую ключ приложения…"
  (cd "$BACKEND" && php artisan key:generate --force >/dev/null)
  ok "Ключ сгенерирован"
fi

# --- 5. Бэкенд: миграции + демо-данные --------------------------------------
say "Применяю миграции базы данных…"
(cd "$BACKEND" && php artisan migrate --force)

if [[ ! -f "$STATE/initialized" ]]; then
  say "Первый запуск — наполняю базу демо-данными…"
  seed() {
    if (cd "$BACKEND" && php artisan db:seed --class="$1" --force >/dev/null 2>&1); then
      ok "  данные: $1"
    else
      warn "  пропустил $1 (необязательно для запуска)"
    fi
  }
  seed AdminSeeder
  seed OptionSeeder
  seed SiteContentSeeder
  seed NotificationTemplateSeeder
  seed EmailTemplateSeeder
  seed СategorySeeder      # имя класса с кириллической «С» — так в проекте
  seed TestProductsSeeder
  touch "$STATE/initialized"
  ok "Демо-данные загружены"
else
  ok "База уже наполнена ранее — пропускаю демо-данные (для пересоздания: ./scripts/start.sh --fresh)"
fi

# --- 6. Фронтенд: зависимости -----------------------------------------------
if [[ ! -d "$FRONTEND/node_modules" ]]; then
  say "Ставлю зависимости фронтенда (npm install) — первый раз дольше…"
  (cd "$FRONTEND" && npm install --no-audit --no-fund)
  ok "Зависимости фронтенда установлены"
else
  ok "Зависимости фронтенда уже на месте"
fi
if [[ ! -f "$FRONTEND/.env" ]]; then
  printf 'VITE_API_URL=http://localhost:8000/api\nNODE_ENV=development\n' > "$FRONTEND/.env"
  ok "frontend/.env создан"
fi

# --- 7. Останавливаю старые процессы, если были -----------------------------
stop_pid() { [[ -f "$1" ]] && kill "$(cat "$1")" >/dev/null 2>&1 || true; rm -f "$1"; }
stop_pid "$STATE/backend.pid"
stop_pid "$STATE/frontend.pid"

# --- 8. Запускаю бэкенд и фронтенд в фоне ------------------------------------
# disown — чтобы серверы пережили закрытие окна Терминала (запуск двойным кликом).
say "Запускаю бэкенд (порт 8000)…"
( cd "$BACKEND" && exec php artisan serve --host=127.0.0.1 --port=8000 ) \
  >"$LOGS/backend.log" 2>&1 &
echo $! > "$STATE/backend.pid"
disown 2>/dev/null || true

say "Запускаю фронтенд (порт 3000)…"
( cd "$FRONTEND" && exec npm run dev ) \
  >"$LOGS/frontend.log" 2>&1 &
echo $! > "$STATE/frontend.pid"
disown 2>/dev/null || true

# Ждём, пока фронт реально откликнется
say "Жду готовности сайта…"
for i in $(seq 1 30); do
  if curl -sf -o /dev/null "http://localhost:3000" 2>/dev/null; then break; fi
  sleep 1
done

# --- 9. Готово --------------------------------------------------------------
echo
echo -e "${G}════════════════════════════════════════════════════════════${N}"
echo -e "${G}${B}  ГОТОВО! Сайт запущен.${N}"
echo -e "${G}════════════════════════════════════════════════════════════${N}"
echo -e "  ${B}Сайт:${N}        ${C}http://localhost:3000${N}"
echo -e "  ${B}Админка:${N}     ${C}http://localhost:8000/admin${N}"
echo -e "  ${B}API:${N}         ${C}http://localhost:8000/api${N}"
echo
echo -e "  ${B}Вход в админку:${N}"
echo -e "    логин:  ${C}admin@mail.com${N}"
echo -e "    пароль: ${C}123123123${N}"
echo
echo -e "  ${B}Остановить всё:${N}  ${C}./scripts/stop.sh${N}"
echo -e "  ${B}Логи:${N}           $LOGS/backend.log , $LOGS/frontend.log"
echo -e "${G}════════════════════════════════════════════════════════════${N}"
