# Решение проблем

## Проблема: Страница пустая / не отображается

### Причина
Проект требует настроенной базы данных MySQL. Без БД API возвращает ошибки 500, и фронтенд не может загрузить данные.

### Решение

#### Вариант 1: Установить и настроить MySQL

1. **Установите MySQL** (если еще не установлен):
   - Скачайте MySQL Installer с https://dev.mysql.com/downloads/installer/
   - Выберите "MySQL Server" и установите
   - При установке задайте пароль для root

2. **Создайте базу данных**:
   ```bash
   mysql -u root -p
   ```
   В консоли MySQL:
   ```sql
   CREATE DATABASE ai_bot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   EXIT;
   ```

3. **Обновите backend/.env**:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=ai_bot
   DB_USERNAME=root
   DB_PASSWORD=ваш_пароль_от_root
   ```

4. **Запустите миграции**:
   ```bash
   cd backend
   php artisan migrate
   php artisan db:seed
   ```

5. **Перезапустите серверы** и откройте http://localhost:3000

#### Вариант 2: Использовать SQLite (проще для разработки)

1. **Установите SQLite** (обычно уже установлен)

2. **Измените backend/.env**:
   ```env
   DB_CONNECTION=sqlite
   # DB_HOST=127.0.0.1
   # DB_PORT=3306
   # DB_DATABASE=ai_bot
   # DB_USERNAME=root
   # DB_PASSWORD=
   ```

3. **Создайте файл БД**:
   ```bash
   cd backend/database
   New-Item -ItemType File -Name database.sqlite
   ```

4. **Сбросьте конфигурацию**:
   ```bash
   cd backend
   php artisan config:clear
   php artisan migrate
   php artisan db:seed
   ```

5. **Перезапустите серверы**

#### Вариант 3: Использовать Docker (если установлен)

Создайте файл `docker-compose.yml` в корне проекта:

```yaml
version: '3.8'

services:
  mysql:
    image: mysql:8.0
    container_name: ai-bot-mysql
    environment:
      MYSQL_ROOT_PASSWORD: rootpassword
      MYSQL_DATABASE: ai_bot
    ports:
      - "3306:3306"
    volumes:
      - mysql-data:/var/lib/mysql

volumes:
  mysql-data:
```

Запустите:
```bash
docker-compose up -d
```

Затем следуйте инструкциям из Варианта 1, начиная с шага 3.

## Другие проблемы

### CORS ошибки
Backend уже настроен на разрешение всех origins в `config/cors.php`. Если проблемы остаются, проверьте, что frontend обращается к правильному URL.

### Порт 8000 или 3000 занят
Измените порт в командах запуска или убейте процесс:
```bash
# Windows
netstat -ano | findstr :8000
taskkill /PID <номер_процесса> /F
```

### PHP ошибки
Убедитесь, что PHP 8.1+ установлен:
```bash
php -v
```

### Composer ошибки
Обновите зависимости:
```bash
cd backend
composer install --no-interaction
```

### Node.js ошибки
Очистите кэш и переустановите:
```bash
cd frontend
Remove-Item -Recurse -Force node_modules package-lock.json
npm install
```

## Проверка работоспособности

После настройки базы данных проверьте:

1. **API работает**:
   ```bash
   Invoke-WebRequest http://localhost:8000/api/services
   ```
   Должен вернуть JSON с сервисами.

2. **Frontend загружается**:
   ```bash
   Invoke-WebRequest http://localhost:3000
   ```
   Должен вернуть HTML (статус 200).

3. **База данных подключена**:
   ```bash
   cd backend
   php artisan tinker
   >>> DB::connection()->getPdo();
   ```
   Должно показать объект PDO без ошибок.

## Полезные команды

```bash
# Очистить кэш Laravel
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Пересобрать зависимости
composer dump-autoload

# Проверить маршруты
php artisan route:list

# Проверить доступность портов
netstat -ano | findstr ":8000 :3000"
```

## Контакты

Если проблема не решена, проверьте логи:
- Backend: `backend/storage/logs/laravel.log`
- Frontend: Консоль браузера (F12)

