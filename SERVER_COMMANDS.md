# 📋 Полезные команды для управления Account Arena на сервере

## 🔄 Обновление проекта

### Быстрое обновление (рекомендуется)
```bash
cd /var/www/account-arena
bash update-project.sh
```

### Ручное обновление
```bash
cd /var/www/account-arena
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

# Перезапуск
systemctl restart php8.2-fpm nginx account-arena-worker
```

---

## 📊 Просмотр логов

### Laravel логи (ошибки приложения)
```bash
# Последние 50 строк
tail -50 /var/www/account-arena/backend/storage/logs/laravel.log

# Следить в реальном времени
tail -f /var/www/account-arena/backend/storage/logs/laravel.log

# Поиск ошибок
grep -i "error" /var/www/account-arena/backend/storage/logs/laravel.log
```

### Nginx логи (веб-сервер)
```bash
# Access log (кто заходил на сайт)
tail -100 /var/log/nginx/account-arena-access.log

# Error log (ошибки Nginx)
tail -100 /var/log/nginx/account-arena-error.log

# В реальном времени
tail -f /var/log/nginx/account-arena-error.log
```

### PHP-FPM логи
```bash
tail -50 /var/log/php8.2-fpm.log
```

### Queue Worker логи
```bash
# Последние логи
journalctl -u account-arena-worker -n 50

# В реальном времени
journalctl -u account-arena-worker -f

# За последний час
journalctl -u account-arena-worker --since "1 hour ago"
```

### MySQL логи
```bash
tail -50 /var/log/mysql/error.log
```

---

## 🔄 Управление сервисами

### Nginx
```bash
# Статус
systemctl status nginx

# Перезапуск
systemctl restart nginx

# Перезагрузка конфигурации (без остановки)
systemctl reload nginx

# Проверка конфигурации
nginx -t

# Включение автозапуска
systemctl enable nginx
```

### PHP-FPM
```bash
# Статус
systemctl status php8.2-fpm

# Перезапуск
systemctl restart php8.2-fpm

# Остановка
systemctl stop php8.2-fpm

# Запуск
systemctl start php8.2-fpm
```

### MySQL
```bash
# Статус
systemctl status mysql

# Перезапуск
systemctl restart mysql

# Вход в консоль
mysql -u root -p

# Вход в БД проекта
mysql -u account_arena -p account_arena
```

### Redis
```bash
# Статус
systemctl status redis-server

# Перезапуск
systemctl restart redis-server

# Проверка работы
redis-cli ping
# Должно вернуть: PONG

# Очистка кэша Redis
redis-cli FLUSHALL
```

### Queue Worker
```bash
# Статус
systemctl status account-arena-worker

# Перезапуск
systemctl restart account-arena-worker

# Остановка
systemctl stop account-arena-worker

# Просмотр логов
journalctl -u account-arena-worker -f
```

### Все сервисы сразу
```bash
# Перезапуск всех сервисов
systemctl restart nginx php8.2-fpm mysql redis-server account-arena-worker

# Проверка статуса всех
systemctl status nginx php8.2-fpm mysql redis-server account-arena-worker
```

---

## 🧹 Очистка кэша

### Laravel кэш
```bash
cd /var/www/account-arena/backend

# Очистка всего кэша
php artisan cache:clear

# Очистка конфигурации
php artisan config:clear

# Очистка роутов
php artisan route:clear

# Очистка view (шаблонов)
php artisan view:clear

# Очистка всего сразу
php artisan cache:clear && php artisan config:clear && php artisan route:clear && php artisan view:clear

# После очистки - создание оптимизированного кэша
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### Redis кэш
```bash
# Полная очистка Redis
redis-cli FLUSHALL

# Очистка только базы данных 0 (кэш)
redis-cli -n 0 FLUSHDB

# Очистка только сессий
redis-cli -n 1 FLUSHDB
```

### Nginx кэш (если настроен)
```bash
# Очистка кэша Nginx
rm -rf /var/cache/nginx/*
systemctl reload nginx
```

---

## 🗄️ Работа с базой данных

### Вход в MySQL
```bash
# От имени root
mysql -u root -p

# От имени пользователя проекта
mysql -u account_arena -p account_arena
```

### Полезные SQL команды
```sql
-- Показать все таблицы
SHOW TABLES;

-- Показать структуру таблицы
DESCRIBE users;

-- Количество пользователей
SELECT COUNT(*) FROM users;

-- Последние 10 пользователей
SELECT id, name, email, created_at FROM users ORDER BY id DESC LIMIT 10;

-- Сделать пользователя админом
UPDATE users SET is_admin = 1 WHERE email = 'admin@example.com';

-- Сброс пароля пользователя
UPDATE users SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE email = 'admin@example.com';
-- Пароль будет: password
```

### Бэкап базы данных
```bash
# Создание бэкапа
mysqldump -u account_arena -p account_arena > /root/backup_$(date +%Y%m%d_%H%M%S).sql

# Создание сжатого бэкапа
mysqldump -u account_arena -p account_arena | gzip > /root/backup_$(date +%Y%m%d_%H%M%S).sql.gz

# Восстановление из бэкапа
mysql -u account_arena -p account_arena < /root/backup_20241106_120000.sql

# Восстановление из сжатого бэкапа
gunzip < /root/backup_20241106_120000.sql.gz | mysql -u account_arena -p account_arena
```

### Автоматический бэкап (cron)
```bash
# Редактировать crontab
crontab -e

# Добавить строку для ежедневного бэкапа в 3:00
0 3 * * * mysqldump -u account_arena -p'ВАШ_ПАРОЛЬ' account_arena | gzip > /root/backups/account_arena_$(date +\%Y\%m\%d).sql.gz

# Создать директорию для бэкапов
mkdir -p /root/backups
```

---

## 👤 Управление пользователями

### Создание администратора через Artisan Tinker
```bash
cd /var/www/account-arena/backend
php artisan tinker
```

В консоли Tinker:
```php
use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Создание админа
User::create([
    'name' => 'Admin',
    'email' => 'admin@example.com',
    'password' => Hash::make('ваш_пароль'),
    'is_admin' => true,
    'is_supplier' => false,
    'email_verified_at' => now(),
]);

// Сделать существующего пользователя админом
$user = User::where('email', 'user@example.com')->first();
$user->is_admin = true;
$user->save();

// Сброс пароля
$user = User::where('email', 'admin@example.com')->first();
$user->password = Hash::make('новый_пароль');
$user->save();
```

### Через SQL
```bash
mysql -u account_arena -p account_arena
```

```sql
-- Создать нового админа
INSERT INTO users (name, email, password, is_admin, email_verified_at, created_at, updated_at) 
VALUES ('Admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW(), NOW());
-- Пароль: password

-- Сделать пользователя админом
UPDATE users SET is_admin = 1 WHERE email = 'user@example.com';

-- Сделать пользователя поставщиком
UPDATE users SET is_supplier = 1 WHERE email = 'supplier@example.com';
```

---

## 🔐 Права доступа

### Исправление прав после обновления
```bash
cd /var/www/account-arena

# Владелец - www-data (пользователь веб-сервера)
chown -R www-data:www-data .

# Права на директории
find . -type d -exec chmod 755 {} \;

# Права на файлы
find . -type f -exec chmod 644 {} \;

# Storage и cache - 775 (нужна запись)
chmod -R 775 backend/storage backend/bootstrap/cache
```

### Проверка прав
```bash
# Показать права на storage
ls -la backend/storage/

# Показать владельца файлов
ls -la backend/
```

---

## 🔍 Мониторинг системы

### Использование ресурсов
```bash
# Общая информация
htop

# Использование CPU и RAM
top

# Использование диска
df -h

# Использование диска по директориям
du -sh /var/www/account-arena/*

# Использование памяти
free -h

# Список процессов PHP
ps aux | grep php

# Список процессов Nginx
ps aux | grep nginx
```

### Проверка портов
```bash
# Проверка открытых портов
netstat -tulpn

# Проверка кто слушает порт 80
netstat -tulpn | grep :80

# Проверка подключений к MySQL
netstat -an | grep 3306
```

### Размер базы данных
```bash
mysql -u account_arena -p -e "SELECT table_schema AS 'Database', ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)' FROM information_schema.TABLES WHERE table_schema = 'account_arena' GROUP BY table_schema;"
```

---

## 🔥 Firewall (UFW)

### Просмотр правил
```bash
ufw status verbose
```

### Открытие портов
```bash
# HTTP
ufw allow 80/tcp

# HTTPS
ufw allow 443/tcp

# SSH
ufw allow 22/tcp

# MySQL (только локально)
ufw deny 3306/tcp
```

### Блокировка IP
```bash
# Заблокировать IP
ufw deny from 123.123.123.123

# Разблокировать IP
ufw delete deny from 123.123.123.123
```

---

## 🆘 Экстренное восстановление

### Сайт не открывается
```bash
# 1. Проверить все сервисы
systemctl status nginx php8.2-fpm mysql redis-server

# 2. Перезапустить всё
systemctl restart nginx php8.2-fpm mysql redis-server account-arena-worker

# 3. Проверить логи
tail -50 /var/log/nginx/account-arena-error.log
tail -50 /var/www/account-arena/backend/storage/logs/laravel.log

# 4. Проверить конфигурацию Nginx
nginx -t

# 5. Исправить права
cd /var/www/account-arena
chown -R www-data:www-data .
chmod -R 775 backend/storage backend/bootstrap/cache
```

### Ошибка 500
```bash
# 1. Включить debug режим
cd /var/www/account-arena/backend
nano .env
# Изменить: APP_DEBUG=true

# 2. Очистить кэш
php artisan cache:clear
php artisan config:clear

# 3. Проверить логи
tail -50 storage/logs/laravel.log

# 4. После исправления - отключить debug
nano .env
# Изменить: APP_DEBUG=false
php artisan config:cache
```

### База данных не работает
```bash
# 1. Проверить статус
systemctl status mysql

# 2. Перезапустить
systemctl restart mysql

# 3. Проверить подключение
mysql -u account_arena -p account_arena

# 4. Проверить .env
cat /var/www/account-arena/backend/.env | grep DB_
```

---

## 📦 Обновление системы

### Обновление Ubuntu
```bash
apt update
apt upgrade -y
apt autoremove -y
```

### Обновление PHP
```bash
# Обновление до последней версии PHP 8.2
apt update
apt upgrade php8.2-* -y
systemctl restart php8.2-fpm
```

### Обновление Composer
```bash
composer self-update
```

### Обновление Node.js
```bash
# Установка новой LTS версии
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs
```

---

## 🎯 Полезные алиасы

Добавьте в `~/.bashrc` для быстрого доступа:

```bash
nano ~/.bashrc
```

Добавьте в конец файла:

```bash
# Account Arena aliases
alias aa-update='cd /var/www/account-arena && bash update-project.sh'
alias aa-logs='tail -f /var/www/account-arena/backend/storage/logs/laravel.log'
alias aa-nginx='tail -f /var/log/nginx/account-arena-error.log'
alias aa-restart='systemctl restart nginx php8.2-fpm mysql redis-server account-arena-worker'
alias aa-status='systemctl status nginx php8.2-fpm mysql redis-server account-arena-worker'
alias aa-cache='cd /var/www/account-arena/backend && php artisan cache:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache'
alias aa-cd='cd /var/www/account-arena'
```

Применить изменения:
```bash
source ~/.bashrc
```

Теперь можно использовать:
- `aa-update` - обновить проект
- `aa-logs` - смотреть логи Laravel
- `aa-nginx` - смотреть логи Nginx
- `aa-restart` - перезапустить все сервисы
- `aa-status` - статус всех сервисов
- `aa-cache` - очистить и пересоздать кэш
- `aa-cd` - перейти в директорию проекта

---

**💡 Совет:** Сохраните этот файл и используйте как справочник при работе с сервером!

