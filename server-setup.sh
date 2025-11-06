#!/bin/bash

###############################################################################
# АВТОМАТИЧЕСКАЯ УСТАНОВКА СЕРВЕРА ДЛЯ SUBCLOUDY (ACCOUNT ARENA)
# Ubuntu 20 LTS + Nginx + PHP 8.2 + MySQL + Redis + Node.js
###############################################################################

set -e  # Остановка при ошибке

echo "=================================="
echo "🚀 НАЧАЛО УСТАНОВКИ СЕРВЕРА"
echo "=================================="
echo ""

# Цвета для вывода
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Функция для вывода успешных сообщений
success() {
    echo -e "${GREEN}✅ $1${NC}"
}

# Функция для вывода информационных сообщений
info() {
    echo -e "${YELLOW}ℹ️  $1${NC}"
}

# Функция для вывода ошибок
error() {
    echo -e "${RED}❌ $1${NC}"
}

###############################################################################
# ШАГ 1: ОБНОВЛЕНИЕ СИСТЕМЫ
###############################################################################
info "ШАГ 1/17: Обновление системы..."
apt update -y
apt upgrade -y
apt install -y software-properties-common curl wget unzip git ufw zip
success "Система обновлена"
echo ""

###############################################################################
# ШАГ 2: УСТАНОВКА NGINX
###############################################################################
info "ШАГ 2/17: Установка Nginx..."
apt install -y nginx
systemctl start nginx
systemctl enable nginx
success "Nginx установлен и запущен"
echo ""

###############################################################################
# ШАГ 3: УСТАНОВКА PHP 8.2
###############################################################################
info "ШАГ 3/17: Установка PHP 8.2..."
add-apt-repository ppa:ondrej/php -y
apt update -y
apt install -y php8.2-fpm php8.2-mysql php8.2-xml php8.2-mbstring \
    php8.2-curl php8.2-zip php8.2-gd php8.2-redis php8.2-bcmath \
    php8.2-intl php8.2-soap php8.2-cli php8.2-common php8.2-opcache
success "PHP 8.2 установлен: $(php -v | head -n 1)"
echo ""

###############################################################################
# ШАГ 4: УСТАНОВКА MYSQL 8.0
###############################################################################
info "ШАГ 4/17: Установка MySQL 8.0..."
export DEBIAN_FRONTEND=noninteractive
apt install -y mysql-server
systemctl start mysql
systemctl enable mysql
success "MySQL установлен"
echo ""

###############################################################################
# ШАГ 5: НАСТРОЙКА MYSQL (БЕЗ ИНТЕРАКТИВА)
###############################################################################
info "ШАГ 5/17: Настройка MySQL..."

# Генерация случайного пароля для MySQL root
MYSQL_ROOT_PASSWORD=$(openssl rand -base64 32)

# Установка пароля root без интерактивного режима
mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '${MYSQL_ROOT_PASSWORD}';"
mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" -e "DELETE FROM mysql.user WHERE User='';"
mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" -e "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');"
mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" -e "DROP DATABASE IF EXISTS test;"
mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" -e "DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';"
mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" -e "FLUSH PRIVILEGES;"

# Сохранение пароля в файл
echo "${MYSQL_ROOT_PASSWORD}" > /root/mysql_root_password.txt
chmod 600 /root/mysql_root_password.txt

success "MySQL настроен. Пароль root сохранен в /root/mysql_root_password.txt"
echo ""

###############################################################################
# ШАГ 6: СОЗДАНИЕ БАЗЫ ДАННЫХ ДЛЯ ПРОЕКТА
###############################################################################
info "ШАГ 6/17: Создание базы данных subcloudy..."

# Генерация пароля для пользователя БД
DB_PASSWORD=$(openssl rand -base64 32)

mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" <<EOF
CREATE DATABASE IF NOT EXISTS subcloudy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'subcloudy'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';
GRANT ALL PRIVILEGES ON subcloudy.* TO 'subcloudy'@'localhost';
FLUSH PRIVILEGES;
EOF

# Сохранение данных БД в файл
cat > /root/database_credentials.txt <<EOF
Database Name: subcloudy
Database User: subcloudy
Database Password: ${DB_PASSWORD}
Database Host: localhost
Database Port: 3306
EOF
chmod 600 /root/database_credentials.txt

success "База данных создана. Учетные данные в /root/database_credentials.txt"
echo ""

###############################################################################
# ШАГ 7: УСТАНОВКА REDIS
###############################################################################
info "ШАГ 7/17: Установка Redis..."
apt install -y redis-server
systemctl start redis-server
systemctl enable redis-server
success "Redis установлен и запущен: $(redis-cli ping)"
echo ""

###############################################################################
# ШАГ 8: УСТАНОВКА COMPOSER
###############################################################################
info "ШАГ 8/17: Установка Composer..."
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer
success "Composer установлен: $(composer --version | head -n 1)"
echo ""

###############################################################################
# ШАГ 9: УСТАНОВКА NODE.JS 20
###############################################################################
info "ШАГ 9/17: Установка Node.js 20..."
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs
success "Node.js установлен: $(node -v), npm: $(npm -v)"
echo ""

###############################################################################
# ШАГ 10: НАСТРОЙКА ФАЙРВОЛА UFW
###############################################################################
info "ШАГ 10/17: Настройка файрвола..."
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable
success "Файрвол настроен"
ufw status
echo ""

###############################################################################
# ШАГ 11: СОЗДАНИЕ ПОЛЬЗОВАТЕЛЯ DEPLOYER
###############################################################################
info "ШАГ 11/17: Создание пользователя deployer..."

# Генерация пароля для deployer
DEPLOYER_PASSWORD=$(openssl rand -base64 16)

# Создание пользователя без интерактивного ввода
useradd -m -s /bin/bash deployer || true
echo "deployer:${DEPLOYER_PASSWORD}" | chpasswd
usermod -aG sudo deployer

# Создание SSH директории
mkdir -p /home/deployer/.ssh
chown -R deployer:deployer /home/deployer/.ssh
chmod 700 /home/deployer/.ssh

# Сохранение пароля
echo "Deployer Password: ${DEPLOYER_PASSWORD}" > /root/deployer_password.txt
chmod 600 /root/deployer_password.txt

success "Пользователь deployer создан. Пароль в /root/deployer_password.txt"
echo ""

###############################################################################
# ШАГ 12: СОЗДАНИЕ ДИРЕКТОРИИ ПРОЕКТА
###############################################################################
info "ШАГ 12/17: Создание структуры директорий..."
mkdir -p /var/www/subcloudy/{backend,frontend}
chown -R www-data:www-data /var/www/subcloudy
success "Директории созданы"
echo ""

###############################################################################
# ШАГ 13: НАСТРОЙКА PHP-FPM
###############################################################################
info "ШАГ 13/17: Настройка PHP-FPM..."

# Оптимизация настроек PHP для продакшена
cat > /etc/php/8.2/fpm/conf.d/99-custom.ini <<EOF
memory_limit = 256M
max_execution_time = 300
max_input_time = 300
post_max_size = 64M
upload_max_filesize = 64M
max_file_uploads = 20
opcache.enable = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 10000
opcache.revalidate_freq = 2
opcache.fast_shutdown = 1
EOF

systemctl restart php8.2-fpm
success "PHP-FPM настроен и перезапущен"
echo ""

###############################################################################
# ШАГ 14: НАСТРОЙКА NGINX (БАЗОВАЯ КОНФИГУРАЦИЯ)
###############################################################################
info "ШАГ 14/17: Создание конфигурации Nginx..."

cat > /etc/nginx/sites-available/account-arena.com <<'EOF'
server {
    listen 80;
    listen [::]:80;
    server_name account-arena.com www.account-arena.com;
    
    root /var/www/subcloudy/frontend/dist;
    index index.html;

    access_log /var/log/nginx/account-arena-access.log;
    error_log /var/log/nginx/account-arena-error.log;

    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss application/json application/javascript;

    # Frontend SPA
    location / {
        try_files $uri $uri/ /index.html;
    }

    # API Backend
    location /api {
        alias /var/www/subcloudy/backend/public;
        try_files $uri $uri/ @backend;

        location ~ \.php$ {
            include fastcgi_params;
            fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
            fastcgi_param SCRIPT_FILENAME /var/www/subcloudy/backend/public/index.php;
            fastcgi_param PATH_INFO $fastcgi_path_info;
        }
    }

    location @backend {
        rewrite /api/(.*)$ /api/index.php?/$1 last;
    }

    # Storage
    location /storage {
        alias /var/www/subcloudy/backend/storage/app/public;
    }

    # Deny hidden files
    location ~ /\. {
        deny all;
    }
}
EOF

# Активация конфигурации
ln -sf /etc/nginx/sites-available/account-arena.com /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# Проверка и перезагрузка
nginx -t
systemctl reload nginx

success "Nginx настроен для account-arena.com"
echo ""

###############################################################################
# ШАГ 15: УСТАНОВКА CERTBOT ДЛЯ SSL
###############################################################################
info "ШАГ 15/17: Установка Certbot..."
apt install -y certbot python3-certbot-nginx
success "Certbot установлен (SSL настроишь позже командой: certbot --nginx -d account-arena.com -d www.account-arena.com)"
echo ""

###############################################################################
# ШАГ 16: СОЗДАНИЕ SYSTEMD СЕРВИСА ДЛЯ LARAVEL QUEUE
###############################################################################
info "ШАГ 16/17: Настройка Laravel Queue Worker..."

cat > /etc/systemd/system/laravel-worker.service <<EOF
[Unit]
Description=Laravel Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/subcloudy/backend/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable laravel-worker

success "Laravel Queue Worker настроен (запустится после деплоя проекта)"
echo ""

###############################################################################
# ШАГ 17: НАСТРОЙКА CRON ДЛЯ LARAVEL SCHEDULER
###############################################################################
info "ШАГ 17/17: Настройка Laravel Scheduler..."

# Добавление задачи в crontab для www-data
(crontab -u www-data -l 2>/dev/null || true; echo "* * * * * cd /var/www/subcloudy/backend && php artisan schedule:run >> /dev/null 2>&1") | crontab -u www-data -

success "Laravel Scheduler настроен"
echo ""

###############################################################################
# СОЗДАНИЕ ИТОГОВОГО ОТЧЕТА
###############################################################################

cat > /root/SERVER_SETUP_COMPLETE.txt <<EOF
========================================
✅ УСТАНОВКА СЕРВЕРА ЗАВЕРШЕНА УСПЕШНО!
========================================

🖥️  ИНФОРМАЦИЯ О СЕРВЕРЕ:
- OS: $(lsb_release -d | cut -f2)
- IP: $(hostname -I | awk '{print $1}')
- Hostname: $(hostname)

📦 УСТАНОВЛЕННОЕ ПО:
- Nginx: $(nginx -v 2>&1 | cut -d'/' -f2)
- PHP: $(php -v | head -n 1 | cut -d' ' -f2)
- MySQL: $(mysql --version | cut -d' ' -f6 | cut -d',' -f1)
- Redis: $(redis-cli --version | cut -d' ' -f2)
- Composer: $(composer --version | cut -d' ' -f3)
- Node.js: $(node -v)
- npm: $(npm -v)

🔐 ВАЖНЫЕ УЧЕТНЫЕ ДАННЫЕ:

MySQL Root:
  Пароль: см. /root/mysql_root_password.txt

База данных Subcloudy:
  см. /root/database_credentials.txt

Пользователь Deployer:
  см. /root/deployer_password.txt

📁 СТРУКТУРА ПРОЕКТА:
/var/www/subcloudy/
├── backend/  - Laravel проект (загрузи сюда код)
└── frontend/ - Vue проект (загрузи сюда код)

🌐 ДОМЕН: account-arena.com
   Конфигурация: /etc/nginx/sites-available/account-arena.com

🔒 SSL: Запусти после деплоя проекта:
   certbot --nginx -d account-arena.com -d www.account-arena.com

📋 СЛЕДУЮЩИЕ ШАГИ:

1. Загрузи проект в /var/www/subcloudy/

2. Настрой backend:
   cd /var/www/subcloudy/backend
   composer install --optimize-autoloader --no-dev
   cp .env.example .env
   # Отредактируй .env (используй данные из /root/database_credentials.txt)
   php artisan key:generate
   php artisan storage:link
   php artisan migrate --force
   php artisan db:seed --force
   php artisan config:cache
   chown -R www-data:www-data /var/www/subcloudy/backend
   chmod -R 755 /var/www/subcloudy/backend
   chmod -R 775 /var/www/subcloudy/backend/storage
   chmod -R 775 /var/www/subcloudy/backend/bootstrap/cache

3. Настрой frontend:
   cd /var/www/subcloudy/frontend
   npm install
   npm run build
   chown -R www-data:www-data /var/www/subcloudy/frontend/dist

4. Запусти queue worker:
   systemctl start laravel-worker

5. Установи SSL:
   certbot --nginx -d account-arena.com -d www.account-arena.com

6. Проверь сайт: https://account-arena.com

📖 ПОЛНАЯ ДОКУМЕНТАЦИЯ:
   См. файл ИНСТРУКЦИЯ_НАСТРОЙКИ_СЕРВЕРА.md в проекте

🎉 ГОТОВО! Сервер полностью настроен и готов к деплою!
EOF

###############################################################################
# ФИНАЛЬНЫЙ ВЫВОД
###############################################################################

echo ""
echo "=========================================="
echo -e "${GREEN}✅ УСТАНОВКА ЗАВЕРШЕНА УСПЕШНО!${NC}"
echo "=========================================="
echo ""
echo "📄 Детальный отчет сохранен в: /root/SERVER_SETUP_COMPLETE.txt"
echo ""
echo "🔐 ВАЖНО! Сохрани эти файлы в безопасном месте:"
echo "   - /root/mysql_root_password.txt"
echo "   - /root/database_credentials.txt"
echo "   - /root/deployer_password.txt"
echo ""
echo "📋 СЛЕДУЮЩИЙ ШАГ: Загрузи код проекта в /var/www/subcloudy/"
echo ""
echo "📖 Читай полную инструкцию: cat /root/SERVER_SETUP_COMPLETE.txt"
echo ""
echo "🎉 Сервер готов к работе!"
echo ""


