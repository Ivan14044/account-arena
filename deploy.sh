#!/bin/bash

###############################################################################
# Account Arena - Скрипт деплоя на сервер
# Использование: ./deploy.sh
###############################################################################

set -e

# Настройки
VPS="${SSH_HOST:-account-arena-server}"
PROJECT_DIR="/var/www/account-arena"

# Цвета
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
RED='\033[0;31m'
CYAN='\033[0;36m'
NC='\033[0m'

echo -e "${CYAN}══════════════════════════════════════════════════════════════${NC}"
echo -e "${CYAN}        ДЕПЛОЙ ACCOUNT ARENA${NC}"
echo -e "${CYAN}══════════════════════════════════════════════════════════════${NC}\n"

# Функции для вывода
print_step() {
    echo -e "${YELLOW}[$1/7] $2${NC}"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

# Шаг 1: Получение изменений
print_step 1 "Получение изменений из GitHub..."
if ssh -o ConnectTimeout=10 -o StrictHostKeyChecking=no $VPS "cd $PROJECT_DIR && git pull origin main"; then
    print_success "Изменения получены"
    echo ""
else
    print_error "Ошибка при получении изменений"
    exit 1
fi

# Шаг 2: Backend - Composer
print_step 2 "Установка зависимостей Backend..."
if ssh -o ConnectTimeout=30 -o StrictHostKeyChecking=no $VPS "cd $PROJECT_DIR/backend && composer install --no-dev --optimize-autoloader --no-interaction"; then
    print_success "Зависимости установлены"
    echo ""
else
    print_error "Ошибка при установке зависимостей"
    exit 1
fi

# Шаг 3: Backend - Миграции
print_step 3 "Выполнение миграций..."
if ssh -o ConnectTimeout=30 -o StrictHostKeyChecking=no $VPS "cd $PROJECT_DIR/backend && php artisan migrate --force"; then
    print_success "Миграции выполнены"
    echo ""
else
    print_error "Ошибка при выполнении миграций"
    exit 1
fi

# Шаг 4: Backend - Кэш
print_step 4 "Очистка и обновление кэша..."
if ssh -o ConnectTimeout=20 -o StrictHostKeyChecking=no $VPS "cd $PROJECT_DIR/backend && php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan optimize"; then
    print_success "Кэш обновлен"
    echo ""
else
    print_error "Ошибка при обновлении кэша"
    exit 1
fi

# Шаг 5: Frontend - Установка зависимостей
print_step 5 "Установка зависимостей Frontend..."
if ssh -o ConnectTimeout=60 -o StrictHostKeyChecking=no $VPS "cd $PROJECT_DIR/frontend && npm install --silent"; then
    print_success "Зависимости Frontend установлены"
    echo ""
else
    print_error "Ошибка при установке зависимостей Frontend"
    exit 1
fi

# Шаг 6: Frontend - Сборка
print_step 6 "Сборка Frontend..."
if ssh -o ConnectTimeout=120 -o StrictHostKeyChecking=no $VPS "cd $PROJECT_DIR/frontend && npm run build"; then
    print_success "Frontend собран"
    echo ""
else
    print_error "Ошибка при сборке Frontend"
    exit 1
fi

# Шаг 7: Права и перезапуск
print_step 7 "Установка прав и перезапуск сервисов..."
if ssh -o ConnectTimeout=20 -o StrictHostKeyChecking=no $VPS "cd $PROJECT_DIR && chown -R www-data:www-data . && chmod -R 775 backend/storage backend/bootstrap/cache && systemctl restart php8.2-fpm && systemctl reload nginx && (systemctl restart account-arena-worker 2>/dev/null || true)"; then
    print_success "Права установлены, сервисы перезапущены"
    echo ""
else
    print_error "Ошибка при установке прав или перезапуске"
    exit 1
fi

echo -e "${GREEN}══════════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}        ✅ ДЕПЛОЙ ЗАВЕРШЕН УСПЕШНО!${NC}"
echo -e "${GREEN}══════════════════════════════════════════════════════════════${NC}\n"
