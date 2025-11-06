#!/bin/bash

###############################################################################
# Account Arena - Скрипт обновления проекта на сервере
# Используется для быстрого обновления после push на GitHub
###############################################################################

set -e

# Цвета
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

print_header() {
    echo -e "\n${BLUE}╔══════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${BLUE}║${NC} $1"
    echo -e "${BLUE}╚══════════════════════════════════════════════════════════════╝${NC}\n"
}

print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

print_info() {
    echo -e "${YELLOW}ℹ${NC} $1"
}

# Проверка root прав
if [ "$EUID" -ne 0 ]; then 
    echo "Пожалуйста, запустите скрипт с правами root (sudo)"
    exit 1
fi

print_header "🔄 ОБНОВЛЕНИЕ ACCOUNT ARENA"

# Переход в директорию проекта
cd /var/www/subcloudy

print_info "Получение последних изменений из GitHub..."
git pull origin main

###############################################################################
# ОБНОВЛЕНИЕ BACKEND
###############################################################################
print_header "⚙️  Обновление Backend"

cd backend

print_info "Обновление зависимостей..."
composer install --no-dev --optimize-autoloader --no-interaction > /dev/null 2>&1

print_info "Выполнение миграций..."
php artisan migrate --force > /dev/null 2>&1

print_info "Очистка кэша..."
php artisan cache:clear > /dev/null 2>&1
php artisan config:clear > /dev/null 2>&1
php artisan route:clear > /dev/null 2>&1
php artisan view:clear > /dev/null 2>&1

print_info "Оптимизация..."
php artisan config:cache > /dev/null 2>&1
php artisan route:cache > /dev/null 2>&1
php artisan view:cache > /dev/null 2>&1
php artisan optimize > /dev/null 2>&1

print_success "Backend обновлён"

###############################################################################
# ОБНОВЛЕНИЕ FRONTEND
###############################################################################
print_header "🎨 Обновление Frontend"

cd ../frontend

print_info "Установка зависимостей..."
npm install --silent > /dev/null 2>&1

print_info "Сборка проекта..."
npm run build > /dev/null 2>&1

print_success "Frontend обновлён"

###############################################################################
# УСТАНОВКА ПРАВ
###############################################################################
print_header "🔐 Установка прав доступа"

cd /var/www/subcloudy
chown -R www-data:www-data .
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
chmod -R 775 backend/storage backend/bootstrap/cache

print_success "Права доступа обновлены"

###############################################################################
# ПЕРЕЗАПУСК СЕРВИСОВ
###############################################################################
print_header "🔄 Перезапуск сервисов"

systemctl restart php8.2-fpm
systemctl reload nginx
systemctl restart account-arena-worker

print_success "Сервисы перезапущены"

###############################################################################
# ПРОВЕРКА СТАТУСА
###############################################################################
print_header "✅ Проверка статуса"

systemctl is-active --quiet nginx && print_success "Nginx работает" || echo "❌ Nginx не работает"
systemctl is-active --quiet php8.2-fpm && print_success "PHP-FPM работает" || echo "❌ PHP-FPM не работает"
systemctl is-active --quiet mysql && print_success "MySQL работает" || echo "❌ MySQL не работает"
systemctl is-active --quiet redis-server && print_success "Redis работает" || echo "❌ Redis не работает"
systemctl is-active --quiet account-arena-worker && print_success "Queue Worker работает" || echo "❌ Queue Worker не работает"

print_header "🎉 ОБНОВЛЕНИЕ ЗАВЕРШЕНО!"

# Получение домена из nginx конфига
DOMAIN=$(grep -m 1 'server_name' /etc/nginx/sites-available/account-arena | awk '{print $2}' | sed 's/;//')

echo ""
echo -e "${GREEN}Сайт обновлён и доступен по адресу:${NC}"
if [[ ! "$DOMAIN" =~ ^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    echo -e "${YELLOW}https://${DOMAIN}${NC}"
else
    echo -e "${YELLOW}http://${DOMAIN}${NC}"
fi
echo ""

# Просмотр последних логов
echo -e "${YELLOW}Последние 10 строк логов:${NC}"
tail -10 /var/www/subcloudy/backend/storage/logs/laravel.log 2>/dev/null || echo "Логов пока нет"
echo ""

