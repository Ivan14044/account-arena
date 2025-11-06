#!/bin/bash

# Автоматический деплой на VPS
set -e

VPS="root@31.131.26.78"
REPO="https://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@github.com/Ivan14044/account-arena.git"

echo "🚀 Начинаю деплой на VPS..."
echo ""

# Выполнение на сервере
ssh -o StrictHostKeyChecking=no $VPS << 'ENDSSH'
set -e

echo "📥 Загрузка проекта из GitHub..."
cd /var/www
rm -rf subcloudy
git clone https://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@github.com/Ivan14044/account-arena.git subcloudy
cd subcloudy

echo "⚙️  Настройка Backend..."
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

echo "🎨 Настройка Frontend..."
cd ../frontend

# .env.production
cat > .env.production << 'EOF'
VITE_API_URL=https://account-arena.com/api
EOF

# npm
npm install
npm run build

echo "🔐 Установка прав..."
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

echo "🔄 Перезапуск сервисов..."
systemctl restart php8.2-fpm
systemctl restart redis-server

echo ""
echo "╔══════════════════════════════════════════════════════════════╗"
echo "║                                                              ║"
echo "║          ✅ ДЕПЛОЙ ЗАВЕРШЕН УСПЕШНО!                        ║"
echo "║                                                              ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""
echo "🌐 Сайт доступен: https://account-arena.com"
echo ""

ENDSSH

echo ""
echo "✅ Готово! Откройте https://account-arena.com"


