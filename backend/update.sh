# Сначала получите изменения из репозитория
# git pull origin main

# Установка PHP-зависимостей
composer install --no-dev --optimize-autoloader

# Установка JavaScript-зависимостей и сборка фронтенда
npm ci
npm run build

# Применение миграций
php artisan migrate --force

# Оптимизация приложения
php artisan optimize

# Перезапуск очередей
php artisan queue:restart

# Обновление прав доступа
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

echo "Обновление успешно завершено!"