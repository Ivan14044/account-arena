#!/bin/bash

###############################################################################
# Account Arena - Автоматическая установка на VPS
# Этот скрипт автоматически настраивает Ubuntu сервер для работы проекта
# Версия: 1.0
# Дата: 2024-11-06
###############################################################################

set -e

# Цвета для вывода
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Функции для вывода
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

# Проверка root прав
if [ "$EUID" -ne 0 ]; then 
    print_error "Пожалуйста, запустите скрипт с правами root (sudo)"
    exit 1
fi

print_header "🚀 АВТОМАТИЧЕСКАЯ УСТАНОВКА ACCOUNT ARENA"

# Запрос данных
print_info "Введите данные для настройки:"
read -p "Введите домен (или IP адрес): " DOMAIN
read -p "Введите email для SSL сертификата: " EMAIL
read -sp "Введите пароль для MySQL базы данных: " DB_PASSWORD
echo

# GitHub репозиторий (публичный URL)
REPO="https://github.com/Ivan14044/account-arena.git"

###############################################################################
# 1. ОБНОВЛЕНИЕ СИСТЕМЫ
###############################################################################
print_header "📦 Обновление системы"
apt update -qq
apt upgrade -y -qq
apt install -y -qq curl git wget unzip software-properties-common
print_success "Система обновлена"

###############################################################################
# 2. УСТАНОВКА NGINX
###############################################################################
print_header "🌐 Установка Nginx"
apt install -y -qq nginx
systemctl enable nginx
systemctl start nginx
print_success "Nginx установлен"

###############################################################################
# 3. УСТАНОВКА PHP 8.2
###############################################################################
print_header "🐘 Установка PHP 8.2"
add-apt-repository ppa:ondrej/php -y > /dev/null 2>&1
apt update -qq
apt install -y -qq php8.2 php8.2-fpm php8.2-cli php8.2-common php8.2-mysql \
    php8.2-zip php8.2-gd php8.2-mbstring php8.2-curl php8.2-xml \
    php8.2-bcmath php8.2-redis php8.2-sqlite3

# Оптимизация PHP-FPM
sed -i 's/;cgi.fix_pathinfo=1/cgi.fix_pathinfo=0/' /etc/php/8.2/fpm/php.ini
sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 50M/' /etc/php/8.2/fpm/php.ini
sed -i 's/post_max_size = 8M/post_max_size = 50M/' /etc/php/8.2/fpm/php.ini
sed -i 's/memory_limit = 128M/memory_limit = 256M/' /etc/php/8.2/fpm/php.ini

systemctl restart php8.2-fpm
print_success "PHP 8.2 установлен и настроен"

###############################################################################
# 4. УСТАНОВКА COMPOSER
###############################################################################
print_header "🎼 Установка Composer"
curl -sS https://getcomposer.org/installer | php > /dev/null 2>&1
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer
print_success "Composer установлен"

###############################################################################
# 5. УСТАНОВКА MYSQL
###############################################################################
print_header "🗄️  Установка MySQL"
export DEBIAN_FRONTEND=noninteractive
apt install -y -qq mysql-server

# Создание базы данных и пользователя
# Используем sudo для доступа к MySQL (для случаев, когда root уже имеет пароль или использует auth_socket)
sudo mysql -e "CREATE DATABASE IF NOT EXISTS subcloudy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER IF NOT EXISTS 'subcloudy'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';"
sudo mysql -e "GRANT ALL PRIVILEGES ON subcloudy.* TO 'subcloudy'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"

# Сохранение пароля
echo "DB_PASSWORD=${DB_PASSWORD}" > /root/.db_creds
chmod 600 /root/.db_creds

print_success "MySQL установлен и настроен"

###############################################################################
# 6. УСТАНОВКА REDIS
###############################################################################
print_header "🔴 Установка Redis"
apt install -y -qq redis-server
systemctl enable redis-server
systemctl start redis-server
print_success "Redis установлен"

###############################################################################
# 7. УСТАНОВКА NODE.JS
###############################################################################
print_header "📗 Установка Node.js"
curl -fsSL https://deb.nodesource.com/setup_20.x | bash - > /dev/null 2>&1
apt install -y -qq nodejs
print_success "Node.js $(node -v) установлен"

###############################################################################
# 8. КЛОНИРОВАНИЕ ПРОЕКТА
###############################################################################
print_header "📥 Клонирование проекта из GitHub"
cd /var/www
if [ -d "subcloudy" ]; then
    rm -rf subcloudy
fi
git clone ${REPO} subcloudy > /dev/null 2>&1
cd subcloudy
print_success "Проект склонирован"

###############################################################################
# 9. НАСТРОЙКА BACKEND
###############################################################################
print_header "⚙️  Настройка Backend (Laravel)"
cd backend

print_info "Установка PHP зависимостей..."
composer install --no-dev --optimize-autoloader --no-interaction > /dev/null 2>&1

print_info "Настройка .env файла..."
if [ ! -f .env ]; then
    cp .env.example .env
    
    # Генерация APP_KEY
    php artisan key:generate --force > /dev/null 2>&1
    
    # Настройка переменных - удаляем старые значения и добавляем новые
    for var in APP_ENV APP_DEBUG APP_URL DB_CONNECTION DB_HOST DB_DATABASE DB_USERNAME DB_PASSWORD REDIS_HOST SESSION_DRIVER CACHE_DRIVER QUEUE_CONNECTION; do
        sed -i "/^${var}=/d" .env 2>/dev/null || true
    done
    
    # Добавляем переменные через cat
    cat >> .env << EOF
APP_ENV=production
APP_DEBUG=false
APP_URL=http://${DOMAIN}
DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=subcloudy
DB_USERNAME=subcloudy
DB_PASSWORD=${DB_PASSWORD}
REDIS_HOST=127.0.0.1
SESSION_DRIVER=redis
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
EOF
fi

print_info "Выполнение миграций..."
php artisan storage:link --force > /dev/null 2>&1
php artisan migrate --force > /dev/null 2>&1
php artisan db:seed --force > /dev/null 2>&1 || true

print_info "Оптимизация Laravel..."
php artisan config:cache > /dev/null 2>&1
php artisan route:cache > /dev/null 2>&1
php artisan view:cache > /dev/null 2>&1
php artisan optimize > /dev/null 2>&1

print_success "Backend настроен"

###############################################################################
# 10. НАСТРОЙКА FRONTEND
###############################################################################
print_header "🎨 Настройка Frontend (Vue.js)"
cd ../frontend

print_info "Создание .env.production..."
cat > .env.production << EOF
VITE_API_URL=http://${DOMAIN}/api
EOF

print_info "Установка зависимостей..."
npm install --silent > /dev/null 2>&1

print_info "Сборка проекта..."
npm run build > /dev/null 2>&1

print_success "Frontend собран"

###############################################################################
# 11. НАСТРОЙКА ПРАВ ДОСТУПА
###############################################################################
print_header "🔐 Настройка прав доступа"
cd /var/www/subcloudy
chown -R www-data:www-data .
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
chmod -R 775 backend/storage backend/bootstrap/cache
print_success "Права доступа установлены"

###############################################################################
# 12. НАСТРОЙКА NGINX
###############################################################################
print_header "🌐 Настройка Nginx"

cat > /etc/nginx/sites-available/account-arena << 'EOF'
server {
    server_name account-arena.com www.account-arena.com;
    root /var/www/subcloudy/frontend/dist;
    index index.html;
    
    access_log /var/log/nginx/account-arena-access.log;
    error_log /var/log/nginx/account-arena-error.log;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/json application/xml+rss;

    # Backend static files - must be before /admin, /api, /supplier
    # Use ^~ for exact match priority
    location ^~ /vendor/ {
        alias /var/www/subcloudy/backend/public/vendor/;
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # Backend admin assets (more specific path)
    location ^~ /assets/admin/ {
        alias /var/www/subcloudy/backend/public/assets/admin/;
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # Storage
    location /storage {
        alias /var/www/subcloudy/backend/storage/app/public;
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # Laravel Backend - API routes
    location /api {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME /var/www/subcloudy/backend/public/index.php;
        include fastcgi_params;
        fastcgi_param REQUEST_URI $request_uri;
    }

    # Laravel Backend - Auth routes
    location /auth {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME /var/www/subcloudy/backend/public/index.php;
        include fastcgi_params;
        fastcgi_param REQUEST_URI $request_uri;
    }

    # Laravel Backend - Admin routes
    location /admin {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME /var/www/subcloudy/backend/public/index.php;
        include fastcgi_params;
        fastcgi_param REQUEST_URI $request_uri;
    }

    # Laravel Backend - Supplier routes
    location /supplier {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME /var/www/subcloudy/backend/public/index.php;
        include fastcgi_params;
        fastcgi_param REQUEST_URI $request_uri;
    }

    # Frontend static files - check if file exists before falling back to SPA
    location / {
        try_files $uri $uri/ @fallback;
    }

    # Fallback to SPA index.html or 404 for missing files
    location @fallback {
        # Check if it's a file request (has extension) - return 404
        if ($uri ~ \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot|webp|json|xml)$) {
            return 404;
        }
        # Otherwise, serve SPA index.html
        try_files /index.html =404;
    }

    # Security
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Static files caching (for frontend)
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot|webp)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # ===== SSL =====
    listen [::]:443 ssl ipv6only=on;
    listen 443 ssl;
    ssl_certificate /etc/letsencrypt/live/account-arena.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/account-arena.com/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;
}

server {
    if ($host = www.account-arena.com) {
        return 301 https://$host$request_uri;
    }
    if ($host = account-arena.com) {
        return 301 https://$host$request_uri;
    }
    
    listen 80;
    listen [::]:80;
    server_name account-arena.com www.account-arena.com;
    return 404;
}

EOF

# Замена placeholder на реальный домен
sed -i "s/SERVER_NAME_PLACEHOLDER/${DOMAIN}/" /etc/nginx/sites-available/account-arena

# Активация конфигурации
ln -sf /etc/nginx/sites-available/account-arena /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# Проверка и перезагрузка
nginx -t > /dev/null 2>&1
systemctl reload nginx

print_success "Nginx настроен"

###############################################################################
# 13. НАСТРОЙКА SSL (если указан домен, а не IP)
###############################################################################
if [[ ! "$DOMAIN" =~ ^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    print_header "🔒 Установка SSL сертификата"
    
    apt install -y -qq certbot python3-certbot-nginx
    
    print_info "Получение SSL сертификата для ${DOMAIN}..."
    certbot --nginx -d ${DOMAIN} --non-interactive --agree-tos --email ${EMAIL} > /dev/null 2>&1
    
    # Обновление .env файлов для HTTPS
    sed -i "s|APP_URL=http://|APP_URL=https://|g" /var/www/subcloudy/backend/.env
    sed -i "s|VITE_API_URL=http://|VITE_API_URL=https://|g" /var/www/subcloudy/frontend/.env.production
    
    # Пересборка frontend
    cd /var/www/subcloudy/frontend
    npm run build > /dev/null 2>&1
    
    # Очистка кэша Laravel
    cd /var/www/subcloudy/backend
    php artisan config:cache > /dev/null 2>&1
    
    print_success "SSL сертификат установлен"
else
    print_info "Пропуск настройки SSL (указан IP адрес вместо домена)"
fi

###############################################################################
# 14. НАСТРОЙКА QUEUE WORKER
###############################################################################
print_header "⚡ Настройка фоновых задач"

cat > /etc/systemd/system/account-arena-worker.service << EOF
[Unit]
Description=Account Arena Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
Restart=always
RestartSec=5
WorkingDirectory=/var/www/subcloudy/backend
ExecStart=/usr/bin/php /var/www/subcloudy/backend/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable account-arena-worker
systemctl start account-arena-worker

print_success "Queue worker запущен"

###############################################################################
# 15. НАСТРОЙКА CRON
###############################################################################
print_header "⏰ Настройка планировщика задач"

# Добавление в crontab для www-data пользователя
(crontab -u www-data -l 2>/dev/null; echo "* * * * * cd /var/www/subcloudy/backend && php artisan schedule:run >> /dev/null 2>&1") | crontab -u www-data -

print_success "Cron задачи настроены"

###############################################################################
# 16. НАСТРОЙКА FIREWALL
###############################################################################
print_header "🛡️  Настройка файрволла"

apt install -y -qq ufw

# Настройка правил
ufw --force reset > /dev/null 2>&1
ufw default deny incoming > /dev/null 2>&1
ufw default allow outgoing > /dev/null 2>&1
ufw allow ssh > /dev/null 2>&1
ufw allow 'Nginx Full' > /dev/null 2>&1
ufw --force enable > /dev/null 2>&1

print_success "Firewall настроен"

###############################################################################
# 17. СОЗДАНИЕ АДМИНИСТРАТОРА
###############################################################################
print_header "👤 Создание администратора"

read -p "Введите email администратора: " ADMIN_EMAIL
read -sp "Введите пароль администратора: " ADMIN_PASSWORD
echo

cd /var/www/subcloudy/backend

php artisan tinker --execute="
use App\Models\User;
use Illuminate\Support\Facades\Hash;

User::create([
    'name' => 'Admin',
    'email' => '${ADMIN_EMAIL}',
    'password' => Hash::make('${ADMIN_PASSWORD}'),
    'is_admin' => true,
    'is_supplier' => false,
    'email_verified_at' => now(),
]);
" > /dev/null 2>&1

print_success "Администратор создан"

###############################################################################
# 18. ФИНАЛЬНАЯ ПРОВЕРКА
###############################################################################
print_header "✅ Финальная проверка"

# Проверка сервисов
print_info "Проверка сервисов..."
systemctl is-active --quiet nginx && print_success "Nginx работает" || print_error "Nginx не работает"
systemctl is-active --quiet php8.2-fpm && print_success "PHP-FPM работает" || print_error "PHP-FPM не работает"
systemctl is-active --quiet mysql && print_success "MySQL работает" || print_error "MySQL не работает"
systemctl is-active --quiet redis-server && print_success "Redis работает" || print_error "Redis не работает"
systemctl is-active --quiet account-arena-worker && print_success "Queue Worker работает" || print_error "Queue Worker не работает"

# Перезапуск всех сервисов
print_info "Перезапуск сервисов..."
systemctl restart php8.2-fpm
systemctl restart nginx
systemctl restart account-arena-worker

###############################################################################
# ЗАВЕРШЕНИЕ
###############################################################################
print_header "🎉 УСТАНОВКА ЗАВЕРШЕНА!"

echo ""
echo -e "${GREEN}╔══════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║                  ИНФОРМАЦИЯ ДЛЯ ДОСТУПА                      ║${NC}"
echo -e "${GREEN}╚══════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${YELLOW}🌐 Адрес сайта:${NC}"
if [[ ! "$DOMAIN" =~ ^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    echo -e "   https://${DOMAIN}"
else
    echo -e "   http://${DOMAIN}"
fi
echo ""
echo -e "${YELLOW}👤 Админ панель:${NC}"
if [[ ! "$DOMAIN" =~ ^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    echo -e "   URL: https://${DOMAIN}/admin"
else
    echo -e "   URL: http://${DOMAIN}/admin"
fi
echo -e "   Email: ${ADMIN_EMAIL}"
echo -e "   Password: ${ADMIN_PASSWORD}"
echo ""
echo -e "${YELLOW}🏪 Панель поставщика:${NC}"
if [[ ! "$DOMAIN" =~ ^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    echo -e "   URL: https://${DOMAIN}/supplier"
else
    echo -e "   URL: http://${DOMAIN}/supplier"
fi
echo ""
echo -e "${YELLOW}📁 Путь к проекту:${NC} /var/www/subcloudy"
echo -e "${YELLOW}🗄️  База данных:${NC} MySQL (subcloudy / ${DB_PASSWORD})"
echo ""
echo -e "${GREEN}╔══════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║            Сохраните эти данные в надёжном месте!           ║${NC}"
echo -e "${GREEN}╚══════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Сохранение информации в файл
cat > /root/account-arena-info.txt << EOF
Account Arena - Информация для доступа
======================================

Дата установки: $(date)

Сайт: $(if [[ ! "$DOMAIN" =~ ^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$ ]]; then echo "https://${DOMAIN}"; else echo "http://${DOMAIN}"; fi)

Админ панель:
- URL: $(if [[ ! "$DOMAIN" =~ ^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$ ]]; then echo "https://${DOMAIN}/admin"; else echo "http://${DOMAIN}/admin"; fi)
- Email: ${ADMIN_EMAIL}
- Password: ${ADMIN_PASSWORD}

Панель поставщика:
- URL: $(if [[ ! "$DOMAIN" =~ ^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$ ]]; then echo "https://${DOMAIN}/supplier"; else echo "http://${DOMAIN}/supplier"; fi)

База данных:
- Имя БД: subcloudy
- Пользователь: subcloudy
- Пароль: ${DB_PASSWORD}

Путь к проекту: /var/www/subcloudy

Полезные команды:
- Просмотр логов: tail -f /var/www/subcloudy/backend/storage/logs/laravel.log
- Перезапуск сервисов: systemctl restart nginx php8.2-fpm account-arena-worker
- Обновление проекта: cd /var/www/subcloudy && git pull && bash /root/update-project.sh
EOF

chmod 600 /root/account-arena-info.txt

print_success "Информация сохранена в /root/account-arena-info.txt"
echo ""
print_info "Для просмотра: cat /root/account-arena-info.txt"
echo ""

