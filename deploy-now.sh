#!/bin/bash

###############################################################################
# Account Arena - Автоматический деплой на VPS
# Скрипт для обновления проекта на сервере после push в GitHub
# Версия: 1.0
###############################################################################

set -e

# Настройки (измените на свой IP и репозиторий)
VPS="root@31.131.26.78"
REPO="https://github.com/Ivan14044/account-arena.git"

# Цвета
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m'

print_header() {
    echo -e "\n${BLUE}╔══════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${BLUE}║${NC} $1"
    echo -e "${BLUE}╚══════════════════════════════════════════════════════════════╝${NC}\n"
}

print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

print_info() {
    echo -e "${YELLOW}ℹ${NC} $1"
}

print_header "🚀 ДЕПЛОЙ НА VPS"

# Проверка доступа к серверу
print_info "Проверка подключения к серверу..."
if ! ssh -o ConnectTimeout=5 -o StrictHostKeyChecking=no $VPS "echo 'Подключено'" > /dev/null 2>&1; then
    print_error "Не удалось подключиться к серверу ${VPS}"
    echo "Проверьте SSH доступ и попробуйте снова"
    exit 1
fi
print_success "Подключение к серверу установлено"
echo ""

# Выполнение на сервере
ssh -o StrictHostKeyChecking=no $VPS << 'ENDSSH'
set -e

# Цвета на сервере
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "\n${BLUE}══════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}        ОБНОВЛЕНИЕ ПРОЕКТА НА СЕРВЕРЕ${NC}"
echo -e "${BLUE}══════════════════════════════════════════════════════════════${NC}\n"

echo -e "${YELLOW}📥 Загрузка проекта из GitHub...${NC}"
cd /var/www

# Проверка существования директории
if [ -d "subcloudy" ]; then
    echo -e "${YELLOW}ℹ Обновление существующего проекта...${NC}"
    cd subcloudy
    git fetch origin main
    git reset --hard origin/main
else
    echo -e "${YELLOW}ℹ Первоначальное клонирование проекта...${NC}"
    git clone ${REPO} subcloudy
    cd subcloudy
fi

echo -e "${GREEN}✓ Проект загружен${NC}\n"

echo -e "${YELLOW}⚙️  Настройка Backend...${NC}"
cd backend

# Composer
composer install --no-dev --optimize-autoloader --no-interaction

# .env
if [ ! -f .env ]; then
    cp .env.example .env
    
    # Получение пароля БД
    if [ -f /root/.db_creds ]; then
        DB_PASS=$(grep DB_PASSWORD /root/.db_creds | cut -d'=' -f2)
        
        # Настройка .env
        sed -i "s|APP_ENV=.*|APP_ENV=production|g" .env
        sed -i "s|APP_DEBUG=.*|APP_DEBUG=false|g" .env
        sed -i "s|APP_URL=.*|APP_URL=https://account-arena.com|g" .env
        sed -i "s|DB_HOST=.*|DB_HOST=localhost|g" .env
        sed -i "s|DB_DATABASE=.*|DB_DATABASE=subcloudy|g" .env
        sed -i "s|DB_USERNAME=.*|DB_USERNAME=subcloudy|g" .env
        sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=${DB_PASS}|g" .env
        sed -i "s|REDIS_HOST=.*|REDIS_HOST=127.0.0.1|g" .env
        sed -i "s|SESSION_DRIVER=.*|SESSION_DRIVER=redis|g" .env
        sed -i "s|CACHE_DRIVER=.*|CACHE_DRIVER=redis|g" .env
        sed -i "s|QUEUE_CONNECTION=.*|QUEUE_CONNECTION=redis|g" .env
    fi
fi

# Laravel команды
php artisan key:generate --force
php artisan storage:link --force
php artisan migrate --force
php artisan db:seed --force --class=SiteContentSeeder || true
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

echo -e "${GREEN}✓ Backend настроен${NC}\n"

echo -e "${YELLOW}🎨 Настройка Frontend...${NC}"
cd ../frontend

# Определение URL на основе настроек
DOMAIN=$(grep -m 1 'server_name' /etc/nginx/sites-available/account-arena 2>/dev/null | awk '{print $2}' | sed 's/;//' || echo "localhost")
if [[ ! "$DOMAIN" =~ ^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$ ]] && [ -d "/etc/letsencrypt/live/${DOMAIN}" ]; then
    API_URL="https://${DOMAIN}/api"
else
    API_URL="http://${DOMAIN}/api"
fi

# .env.production
cat > .env.production << EOF
VITE_API_URL=${API_URL}
EOF

echo -e "${YELLOW}ℹ API URL: ${API_URL}${NC}"

# npm
npm install --silent
npm run build

echo -e "${GREEN}✓ Frontend собран${NC}\n"

echo -e "${YELLOW}🔐 Установка прав доступа...${NC}"
cd /var/www/subcloudy
chown -R www-data:www-data .
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
chmod -R 775 backend/storage backend/bootstrap/cache

echo "🌐 Обновление Nginx..."
cat > /etc/nginx/sites-available/account-arena.com << 'NGINXEOF'
server {
    listen 80;
    listen [::]:80;
    server_name account-arena.com www.account-arena.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name account-arena.com www.account-arena.com;

    ssl_certificate /etc/letsencrypt/live/account-arena.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/account-arena.com/privkey.pem;

    root /var/www/subcloudy/frontend/dist;
    index index.html;

    access_log /var/log/nginx/account-arena.com-access.log;
    error_log /var/log/nginx/account-arena.com-error.log;

    gzip on;
    gzip_vary on;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/json;

    location / {
        try_files $uri $uri/ /index.html;
    }

    location /api {
        alias /var/www/subcloudy/backend/public;
        try_files $uri $uri/ @backend;

        location ~ \.php$ {
            include snippets/fastcgi-php.conf;
            fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
            fastcgi_param SCRIPT_FILENAME /var/www/subcloudy/backend/public/index.php;
            fastcgi_param PATH_INFO $fastcgi_path_info;
        }
    }

    location @backend {
        rewrite /api/(.*)$ /api/index.php?/$1 last;
    }

    location /storage {
        alias /var/www/subcloudy/backend/storage/app/public;
    }

    location ~ /\. {
        deny all;
    }
}
NGINXEOF

nginx -t && systemctl reload nginx

echo -e "${GREEN}✓ Права установлены${NC}\n"

# Обновление Nginx конфигурации только если она изменилась
echo -e "${YELLOW}🌐 Проверка конфигурации Nginx...${NC}"
if nginx -t > /dev/null 2>&1; then
    echo -e "${GREEN}✓ Конфигурация Nginx корректна${NC}"
    systemctl reload nginx
else
    echo -e "${RED}✗ Ошибка в конфигурации Nginx${NC}"
fi

echo -e "\n${YELLOW}🔄 Перезапуск сервисов...${NC}"
systemctl restart php8.2-fpm
systemctl restart account-arena-worker 2>/dev/null || true
systemctl restart redis-server

echo -e "${GREEN}✓ Сервисы перезапущены${NC}\n"

# Получение домена для вывода
DOMAIN=$(grep -m 1 'server_name' /etc/nginx/sites-available/account-arena 2>/dev/null | awk '{print $2}' | sed 's/;//' || echo "localhost")
if [[ ! "$DOMAIN" =~ ^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$ ]] && [ -d "/etc/letsencrypt/live/${DOMAIN}" ]; then
    SITE_URL="https://${DOMAIN}"
else
    SITE_URL="http://${DOMAIN}"
fi

echo ""
echo -e "${GREEN}╔══════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║                                                              ║${NC}"
echo -e "${GREEN}║          ✅ ДЕПЛОЙ ЗАВЕРШЕН УСПЕШНО!                        ║${NC}"
echo -e "${GREEN}║                                                              ║${NC}"
echo -e "${GREEN}╚══════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${YELLOW}🌐 Сайт доступен:${NC} ${SITE_URL}"
echo -e "${YELLOW}📊 Админ панель:${NC} ${SITE_URL}/admin"
echo -e "${YELLOW}🏪 Панель поставщика:${NC} ${SITE_URL}/supplier"
echo ""
echo -e "${YELLOW}📋 Проверка статуса сервисов:${NC}"
systemctl is-active --quiet nginx && echo -e "${GREEN}  ✓ Nginx работает${NC}" || echo -e "${RED}  ✗ Nginx не работает${NC}"
systemctl is-active --quiet php8.2-fpm && echo -e "${GREEN}  ✓ PHP-FPM работает${NC}" || echo -e "${RED}  ✗ PHP-FPM не работает${NC}"
systemctl is-active --quiet mysql && echo -e "${GREEN}  ✓ MySQL работает${NC}" || echo -e "${RED}  ✗ MySQL не работает${NC}"
systemctl is-active --quiet redis-server && echo -e "${GREEN}  ✓ Redis работает${NC}" || echo -e "${RED}  ✗ Redis не работает${NC}"
systemctl is-active --quiet account-arena-worker && echo -e "${GREEN}  ✓ Queue Worker работает${NC}" || echo -e "${YELLOW}  ⚠ Queue Worker не настроен${NC}"
echo ""

ENDSSH

print_header "🎉 ДЕПЛОЙ ЗАВЕРШЁН!"
echo ""
echo -e "${GREEN}✅ Проект успешно развёрнут на сервере!${NC}"
echo ""
echo -e "${YELLOW}Для доступа к сайту откройте браузер${NC}"
echo ""


