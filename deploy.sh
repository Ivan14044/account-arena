#!/bin/bash

###############################################################################
# Account Arena - Простой скрипт деплоя
# Выполняет обновление проекта на сервере пошагово
###############################################################################

set -e

VPS="root@31.131.26.78"
PROJECT_DIR="/var/www/subcloudy"

# Цвета
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${BLUE}══════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}        ДЕПЛОЙ ACCOUNT ARENA${NC}"
echo -e "${BLUE}══════════════════════════════════════════════════════════════${NC}\n"

# Шаг 1: Получение изменений
echo -e "${YELLOW}[1/7] Получение изменений из GitHub...${NC}"
ssh -o ConnectTimeout=10 -o StrictHostKeyChecking=no $VPS "cd $PROJECT_DIR && git pull origin main" || {
    echo -e "${RED}Ошибка при получении изменений${NC}"
    exit 1
}
echo -e "${GREEN}✓ Изменения получены${NC}\n"

# Шаг 2: Backend - Composer
echo -e "${YELLOW}[2/7] Установка зависимостей Backend...${NC}"
ssh -o ConnectTimeout=30 -o StrictHostKeyChecking=no $VPS "cd $PROJECT_DIR/backend && composer install --no-dev --optimize-autoloader --no-interaction" || {
    echo -e "${RED}Ошибка при установке зависимостей${NC}"
    exit 1
}
echo -e "${GREEN}✓ Зависимости установлены${NC}\n"

# Шаг 3: Backend - Миграции
echo -e "${YELLOW}[3/7] Выполнение миграций...${NC}"
ssh -o ConnectTimeout=30 -o StrictHostKeyChecking=no $VPS "cd $PROJECT_DIR/backend && php artisan migrate --force" || {
    echo -e "${RED}Ошибка при выполнении миграций${NC}"
    exit 1
}
echo -e "${GREEN}✓ Миграции выполнены${NC}\n"

# Шаг 4: Backend - Кэш
echo -e "${YELLOW}[4/7] Очистка и обновление кэша...${NC}"
ssh -o ConnectTimeout=20 -o StrictHostKeyChecking=no $VPS "cd $PROJECT_DIR/backend && php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan optimize" || {
    echo -e "${RED}Ошибка при обновлении кэша${NC}"
    exit 1
}
echo -e "${GREEN}✓ Кэш обновлен${NC}\n"

# Шаг 5: Frontend - Установка зависимостей
echo -e "${YELLOW}[5/7] Установка зависимостей Frontend...${NC}"
ssh -o ConnectTimeout=60 -o StrictHostKeyChecking=no $VPS "cd $PROJECT_DIR/frontend && npm install --silent" || {
    echo -e "${RED}Ошибка при установке зависимостей Frontend${NC}"
    exit 1
}
echo -e "${GREEN}✓ Зависимости Frontend установлены${NC}\n"

# Шаг 6: Frontend - Сборка
echo -e "${YELLOW}[6/7] Сборка Frontend...${NC}"
ssh -o ConnectTimeout=120 -o StrictHostKeyChecking=no $VPS "cd $PROJECT_DIR/frontend && npm run build" || {
    echo -e "${RED}Ошибка при сборке Frontend${NC}"
    exit 1
}
echo -e "${GREEN}✓ Frontend собран${NC}\n"

# Шаг 7: Права и перезапуск
echo -e "${YELLOW}[7/7] Установка прав и перезапуск сервисов...${NC}"
ssh -o ConnectTimeout=20 -o StrictHostKeyChecking=no $VPS "cd $PROJECT_DIR && chown -R www-data:www-data . && chmod -R 775 backend/storage backend/bootstrap/cache && systemctl restart php8.2-fpm && systemctl reload nginx && (systemctl restart account-arena-worker 2>/dev/null || true)" || {
    echo -e "${RED}Ошибка при установке прав или перезапуске${NC}"
    exit 1
}
echo -e "${GREEN}✓ Права установлены, сервисы перезапущены${NC}\n"

echo -e "${GREEN}══════════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}        ✅ ДЕПЛОЙ ЗАВЕРШЕН УСПЕШНО!${NC}"
echo -e "${GREEN}══════════════════════════════════════════════════════════════${NC}\n"
