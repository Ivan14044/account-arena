#!/usr/bin/env bash
# ============================================================================
#  Account Arena — остановить локальный запуск.
#
#    ./scripts/stop.sh           остановить сайт (база данных остаётся жить)
#    ./scripts/stop.sh --all     остановить ещё и контейнеры MySQL/Redis
#
#  Данные базы при этом НЕ удаляются. Чтобы стереть базу — ./scripts/start.sh --fresh
# ============================================================================
set -euo pipefail

C="\033[1;36m"; G="\033[1;32m"; N="\033[0m"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
STATE="$ROOT/.dev-state"

stop_pid() {
  if [[ -f "$1" ]]; then
    kill "$(cat "$1")" >/dev/null 2>&1 || true
    rm -f "$1"
  fi
}

echo -e "${C}▶ Останавливаю сайт (бэкенд + фронтенд)…${N}"
stop_pid "$STATE/backend.pid"
stop_pid "$STATE/frontend.pid"
# на всякий случай добиваем процессы по портам
for port in 8000 3000; do
  pid="$(lsof -ti tcp:"$port" 2>/dev/null || true)"
  [[ -n "$pid" ]] && kill $pid >/dev/null 2>&1 || true
done

if [[ "${1:-}" == "--all" ]]; then
  echo -e "${C}▶ Останавливаю контейнеры MySQL/Redis…${N}"
  docker compose -f "$ROOT/docker-compose.yml" down
fi

echo -e "${G}✔ Готово. Сайт остановлен.${N}"
