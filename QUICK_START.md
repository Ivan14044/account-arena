# 🚀 Быстрый старт - Account Arena на сервере

## 📌 Краткая инструкция

### Вариант 1: Автоматическая установка (Рекомендуется)

**1. Подключитесь к серверу:**
```bash
ssh root@31.131.26.78
```

**2. Скачайте и запустите скрипт установки:**
```bash
wget https://raw.githubusercontent.com/Ivan14044/account-arena/main/setup-server.sh
chmod +x setup-server.sh
./setup-server.sh
```

**3. Следуйте инструкциям на экране:**
- Введите домен или IP адрес
- Введите email для SSL
- Придумайте пароль для БД
- Создайте администратора

**4. Готово! 🎉** 
Сайт будет доступен по указанному адресу.

---

### Вариант 2: Деплой с локального компьютера

**Требования:**
- Git Bash или WSL на Windows
- SSH доступ к серверу

**1. Убедитесь, что на сервере установлено всё необходимое:**
```bash
# Подключитесь к серверу и выполните:
wget https://raw.githubusercontent.com/Ivan14044/account-arena/main/setup-server.sh
chmod +x setup-server.sh
./setup-server.sh
```

**2. На локальном компьютере запустите деплой:**
```bash
cd "D:\project\Account Arena"
bash deploy.sh
```

---

## 🔄 Обновление проекта

### С локального компьютера:

**1. Закоммитьте изменения:**
```bash
cd "D:\project\Account Arena"
git add .
git commit -m "Update features"
git push origin main
```

**2. Запустите деплой:**
```bash
bash deploy.sh
```

### На сервере:

**Способ 1 - Автоматический:**
```bash
ssh root@31.131.26.78
cd /var/www/account-arena
bash update-project.sh
```

**Способ 2 - Ручной:**
```bash
ssh root@31.131.26.78
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

# Перезапуск сервисов
systemctl restart php8.2-fpm nginx account-arena-worker
```

---

## 🔑 Доступ к панелям

### Админ панель:
```
URL: http://31.131.26.78/admin
(или https://yourdomain.com/admin если настроен SSL)
```

### Панель поставщика:
```
URL: http://31.131.26.78/supplier
(или https://yourdomain.com/supplier если настроен SSL)
```

---

## 📊 Полезные команды на сервере

### Просмотр логов:
```bash
# Laravel логи
tail -f /var/www/account-arena/backend/storage/logs/laravel.log

# Nginx логи
tail -f /var/log/nginx/account-arena-error.log

# Queue worker логи
journalctl -u account-arena-worker -f
```

### Перезапуск сервисов:
```bash
systemctl restart nginx
systemctl restart php8.2-fpm
systemctl restart account-arena-worker
systemctl restart mysql
systemctl restart redis-server
```

### Очистка кэша Laravel:
```bash
cd /var/www/account-arena/backend
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Проверка статуса сервисов:
```bash
systemctl status nginx
systemctl status php8.2-fpm
systemctl status mysql
systemctl status redis-server
systemctl status account-arena-worker
```

---

## 🆘 Решение проблем

### Сайт не открывается:
```bash
# Проверьте Nginx
systemctl status nginx
nginx -t

# Проверьте права
cd /var/www/account-arena
chown -R www-data:www-data .
chmod -R 775 backend/storage backend/bootstrap/cache
```

### Ошибка 500:
```bash
# Проверьте логи
tail -50 /var/www/account-arena/backend/storage/logs/laravel.log

# Очистите кэш
cd /var/www/account-arena/backend
php artisan cache:clear
php artisan config:cache
```

### API не работает:
```bash
# Проверьте PHP-FPM
systemctl status php8.2-fpm
systemctl restart php8.2-fpm

# Проверьте .env файл
cat /var/www/account-arena/backend/.env | grep APP_URL
```

---

## 📞 Поддержка

**GitHub:** https://github.com/Ivan14044/account-arena

**Email:** iknys62@icloud.com

**Документация:** См. файл `SERVER_SETUP_GUIDE.md`

---

## ✅ Чеклист

- [ ] Сервер настроен (Nginx, PHP, MySQL, Redis, Node.js)
- [ ] Проект клонирован с GitHub
- [ ] Backend настроен и запущен
- [ ] Frontend собран
- [ ] Nginx сконфигурирован
- [ ] SSL установлен (если есть домен)
- [ ] Создан администратор
- [ ] Сайт открывается в браузере
- [ ] Админ панель доступна
- [ ] API работает

---

**Готово! Ваш Account Arena развёрнут! 🎉**

