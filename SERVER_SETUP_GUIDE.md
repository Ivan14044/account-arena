# 🚀 Полная инструкция по развертыванию Account Arena на VPS

## 📋 Содержание
1. [Требования](#требования)
2. [Подготовка сервера](#подготовка-сервера)
3. [Установка окружения](#установка-окружения)
4. [Деплой проекта](#деплой-проекта)
5. [Настройка SSL](#настройка-ssl)
6. [Обновление проекта](#обновление-проекта)

---

## 🖥️ Требования

### Минимальные требования к серверу:
- **OS**: Ubuntu 20.04 / 22.04
- **CPU**: 1 core
- **RAM**: 2GB
- **Disk**: 20GB SSD
- **IP**: Статический публичный IP

### Необходимые данные:
- IP адрес сервера: `31.131.26.78`
- Доступ по SSH (root или sudo)
- Доменное имя (опционально, но рекомендуется)

---

## 🔧 Подготовка сервера

### Шаг 1: Подключение к серверу

```bash
ssh root@31.131.26.78
```

### Шаг 2: Обновление системы

```bash
apt update && apt upgrade -y
```

### Шаг 3: Установка базовых утилит

```bash
apt install -y curl git wget unzip software-properties-common
```

---

## ⚙️ Установка окружения

### 1. Установка Nginx

```bash
apt install -y nginx
systemctl enable nginx
systemctl start nginx
```

### 2. Установка PHP 8.2

```bash
add-apt-repository ppa:ondrej/php -y
apt update
apt install -y php8.2 php8.2-fpm php8.2-cli php8.2-common php8.2-mysql \
php8.2-zip php8.2-gd php8.2-mbstring php8.2-curl php8.2-xml \
php8.2-bcmath php8.2-redis php8.2-sqlite3
```

### 3. Установка Composer

```bash
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer
```

### 4. Установка MySQL

```bash
apt install -y mysql-server

# Безопасная настройка MySQL
mysql_secure_installation
```

### 5. Создание базы данных

```bash
# Вход в MySQL
mysql -u root -p

# В MySQL консоли:
CREATE DATABASE subcloudy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'subcloudy'@'localhost' IDENTIFIED BY 'ВАШ_СЛОЖНЫЙ_ПАРОЛЬ';
GRANT ALL PRIVILEGES ON subcloudy.* TO 'subcloudy'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Сохраните пароль в файл для автоматизации
echo "DB_PASSWORD=ВАШ_СЛОЖНЫЙ_ПАРОЛЬ" > /root/.db_creds
chmod 600 /root/.db_creds
```

### 6. Установка Redis

```bash
apt install -y redis-server
systemctl enable redis-server
systemctl start redis-server

# Проверка
redis-cli ping
# Должно вернуть: PONG
```

### 7. Установка Node.js и NPM

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs

# Проверка версий
node -v   # Должно быть >= 18
npm -v
```

---

## 🚀 Деплой проекта

### Метод 1: Автоматический деплой (рекомендуется)

С вашего локального компьютера запустите:

```bash
# Windows PowerShell
cd D:\project\Subcloudy
bash deploy-now.sh
```

Скрипт автоматически:
- Загрузит код с GitHub на сервер
- Установит все зависимости
- Настроит базу данных
- Соберёт frontend
- Настроит Nginx
- Запустит проект

### Метод 2: Ручной деплой

#### На сервере:

```bash
# Перейти в директорию веб-проектов
cd /var/www

# Клонировать репозиторий
git clone https://github.com/Ivan14044/account-arena.git subcloudy
cd subcloudy

# Backend
cd backend
composer install --no-dev --optimize-autoloader

# Создание .env
cp .env.example .env
nano .env
# Настройте переменные окружения (см. секцию ниже)

# Laravel команды
php artisan key:generate
php artisan storage:link
php artisan migrate --seed
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Frontend
cd ../frontend

# Создание .env.production
echo 'VITE_API_URL=http://31.131.26.78/api' > .env.production

npm install
npm run build

# Права доступа
cd /var/www/account-arena
chown -R www-data:www-data .
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
chmod -R 775 backend/storage backend/bootstrap/cache
```

### Настройка .env файла

```env
APP_NAME="Account Arena"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:ВАSH_КЛЮЧ_СГЕНЕРИРОВАННЫЙ_ARTISAN
APP_URL=http://31.131.26.78

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=subcloudy
DB_USERNAME=subcloudy
DB_PASSWORD=ВАШ_ПАРОЛЬ_ОТ_БД

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Платежные системы (заполните когда будут ключи)
CRYPTOMUS_API_KEY=
CRYPTOMUS_MERCHANT_ID=

MONO_API_KEY=

# Email (опционально)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yoursite.com
MAIL_FROM_NAME="${APP_NAME}"
```

---

## 🌐 Настройка Nginx

### Создание конфигурации

```bash
nano /etc/nginx/sites-available/account-arena
```

Вставьте конфигурацию:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name 31.131.26.78;

    root /var/www/account-arena/frontend/dist;
    index index.html;

    access_log /var/log/nginx/account-arena-access.log;
    error_log /var/log/nginx/account-arena-error.log;

    # Gzip сжатие
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/json application/xml+rss;

    # Frontend (Vue.js SPA)
    location / {
        try_files $uri $uri/ /index.html;
    }

    # Backend API
    location /api {
        alias /var/www/account-arena/backend/public;
        try_files $uri $uri/ @backend;

        location ~ \.php$ {
            include snippets/fastcgi-php.conf;
            fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
            fastcgi_param SCRIPT_FILENAME /var/www/account-arena/backend/public/index.php;
            fastcgi_param PATH_INFO $fastcgi_path_info;
            include fastcgi_params;
        }
    }

    location @backend {
        rewrite /api/(.*)$ /api/index.php?/$1 last;
    }

    # Admin панель Laravel
    location /admin {
        alias /var/www/account-arena/backend/public;
        try_files $uri $uri/ @admin;

        location ~ \.php$ {
            include snippets/fastcgi-php.conf;
            fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
            fastcgi_param SCRIPT_FILENAME /var/www/account-arena/backend/public/index.php;
            include fastcgi_params;
        }
    }

    location @admin {
        rewrite /admin/(.*)$ /admin/index.php?/$1 last;
    }

    # Supplier панель
    location /supplier {
        alias /var/www/account-arena/backend/public;
        try_files $uri $uri/ @supplier;

        location ~ \.php$ {
            include snippets/fastcgi-php.conf;
            fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
            fastcgi_param SCRIPT_FILENAME /var/www/account-arena/backend/public/index.php;
            include fastcgi_params;
        }
    }

    location @supplier {
        rewrite /supplier/(.*)$ /supplier/index.php?/$1 last;
    }

    # Storage (загруженные файлы)
    location /storage {
        alias /var/www/account-arena/backend/storage/app/public;
    }

    # Безопасность
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Кэширование статики
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

### Активация конфигурации

```bash
# Создание симлинка
ln -s /etc/nginx/sites-available/account-arena /etc/nginx/sites-enabled/

# Удаление дефолтного сайта
rm /etc/nginx/sites-enabled/default

# Проверка конфигурации
nginx -t

# Перезагрузка Nginx
systemctl reload nginx
```

---

## 🔒 Настройка SSL (если есть домен)

### Если у вас есть доменное имя:

```bash
# Установка Certbot
apt install -y certbot python3-certbot-nginx

# Получение SSL сертификата
certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Автоматическое обновление сертификата
certbot renew --dry-run
```

### Обновите .env файлы:

**backend/.env:**
```env
APP_URL=https://yourdomain.com
```

**frontend/.env.production:**
```env
VITE_API_URL=https://yourdomain.com/api
```

Пересоберите frontend:
```bash
cd /var/www/account-arena/frontend
npm run build
```

---

## 🔄 Обновление проекта

### Автоматическое обновление

С вашего локального компьютера:

```bash
# 1. Закоммитьте изменения
git add .
git commit -m "Update features"
git push origin main

# 2. Запустите деплой
bash deploy-now.sh
```

### Ручное обновление на сервере

```bash
cd /var/www/account-arena

# Получение последних изменений
git pull origin main

# Backend
cd backend
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Frontend
cd ../frontend
npm install
npm run build

# Права
cd /var/www/account-arena
chown -R www-data:www-data .
chmod -R 775 backend/storage backend/bootstrap/cache

# Перезапуск сервисов
systemctl restart php8.2-fpm
systemctl reload nginx
```

---

## 🎯 Создание администратора

```bash
cd /var/www/account-arena/backend
php artisan tinker
```

В консоли tinker:

```php
use App\Models\User;
use Illuminate\Support\Facades\Hash;

User::create([
    'name' => 'Admin',
    'email' => 'admin@account-arena.com',
    'password' => Hash::make('StrongPassword123!'),
    'is_admin' => true,
    'is_supplier' => false,
    'email_verified_at' => now(),
]);
```

Доступ к админ панели:
- URL: `http://31.131.26.78/admin`
- Email: `admin@account-arena.com`
- Password: `StrongPassword123!`

---

## 🔧 Настройка фоновых задач (Queue Workers)

### Создание systemd сервиса

```bash
nano /etc/systemd/system/account-arena-worker.service
```

Содержимое:

```ini
[Unit]
Description=Account Arena Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
Restart=always
RestartSec=5
WorkingDirectory=/var/www/account-arena/backend
ExecStart=/usr/bin/php /var/www/account-arena/backend/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600

[Install]
WantedBy=multi-user.target
```

Запуск:

```bash
systemctl daemon-reload
systemctl enable account-arena-worker
systemctl start account-arena-worker
systemctl status account-arena-worker
```

### Настройка Cron для планировщика Laravel

```bash
crontab -e
```

Добавьте:

```cron
* * * * * cd /var/www/account-arena/backend && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🛡️ Безопасность

### 1. Настройка файрволла

```bash
# UFW firewall
apt install -y ufw

ufw default deny incoming
ufw default allow outgoing
ufw allow ssh
ufw allow 'Nginx Full'
ufw enable
ufw status
```

### 2. Отключение debug режима

В `backend/.env`:
```env
APP_DEBUG=false
```

### 3. Регулярные обновления

```bash
# Создайте скрипт автообновления системы
nano /root/update-system.sh
```

```bash
#!/bin/bash
apt update
apt upgrade -y
apt autoremove -y
```

```bash
chmod +x /root/update-system.sh

# Добавьте в cron (раз в неделю)
crontab -e
# 0 3 * * 0 /root/update-system.sh
```

---

## 📊 Мониторинг и логи

### Просмотр логов

```bash
# Nginx логи
tail -f /var/log/nginx/account-arena-access.log
tail -f /var/log/nginx/account-arena-error.log

# Laravel логи
tail -f /var/www/account-arena/backend/storage/logs/laravel.log

# PHP-FPM логи
tail -f /var/log/php8.2-fpm.log

# Queue worker логи
journalctl -u account-arena-worker -f
```

### Мониторинг ресурсов

```bash
# Использование CPU/RAM
htop

# Использование диска
df -h

# Процессы PHP
ps aux | grep php

# Redis
redis-cli info
```

---

## 🚨 Решение проблем

### Проблема: 502 Bad Gateway

```bash
# Проверьте PHP-FPM
systemctl status php8.2-fpm
systemctl restart php8.2-fpm

# Проверьте права
cd /var/www/account-arena
chown -R www-data:www-data .
chmod -R 775 backend/storage backend/bootstrap/cache
```

### Проблема: 500 Internal Server Error

```bash
# Проверьте логи Laravel
tail -100 /var/www/account-arena/backend/storage/logs/laravel.log

# Очистите кэш
cd /var/www/account-arena/backend
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Проблема: База данных не подключается

```bash
# Проверьте MySQL
systemctl status mysql

# Проверьте доступ
mysql -u subcloudy -p subcloudy

# Проверьте .env файл
cat /var/www/account-arena/backend/.env | grep DB_
```

### Проблема: Frontend не загружается

```bash
# Пересоберите frontend
cd /var/www/account-arena/frontend
npm run build

# Проверьте права
ls -la dist/
chown -R www-data:www-data dist/
```

---

## 📞 Поддержка

Если возникли проблемы:
1. Проверьте логи (раздел "Мониторинг и логи")
2. Создайте issue на GitHub: https://github.com/Ivan14044/account-arena/issues
3. Email: iknys62@icloud.com

---

## ✅ Чеклист после установки

- [ ] Сервер обновлён
- [ ] Nginx установлен и запущен
- [ ] PHP 8.2 установлен
- [ ] MySQL установлен и настроен
- [ ] Redis установлен и запущен
- [ ] Node.js установлен
- [ ] Проект склонирован из GitHub
- [ ] Backend настроен (.env файл)
- [ ] Frontend собран
- [ ] Nginx сконфигурирован
- [ ] SSL настроен (если есть домен)
- [ ] Создан администратор
- [ ] Queue worker запущен
- [ ] Cron настроен
- [ ] Firewall настроен
- [ ] Сайт открывается в браузере

---

**Готово! 🎉 Ваш сайт Account Arena развёрнут на сервере!**

Проверьте: `http://31.131.26.78`

