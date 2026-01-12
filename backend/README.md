# Account Arena - Backend (Laravel)

Backend часть проекта Account Arena на базе Laravel 10.

## 🛠 Технологии

- **Laravel 10.10** - PHP Framework
- **PHP 8.1+** - Язык программирования
- **MySQL** - База данных (SQLite для разработки)
- **Redis** - Кэширование и очереди
- **Laravel Sanctum** - API аутентификация
- **Laravel Socialite** - OAuth провайдеры (Google, Telegram)
- **AdminLTE 3** - Админ-панель
- **GeoIP2** - Геолокация пользователей

## 📁 Структура проекта

```
backend/
├── app/
│   ├── Console/Commands/        # Artisan команды
│   ├── Exceptions/               # Обработчики исключений
│   ├── Helpers/                  # Вспомогательные функции
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/           # Админ-панель (30 контроллеров)
│   │   │   ├── Api/             # REST API (12 контроллеров)
│   │   │   ├── Auth/            # Аутентификация (8 контроллеров)
│   │   │   ├── Seo/             # SEO страницы (SSR)
│   │   │   └── Supplier/        # Кабинет поставщика (8 контроллеров)
│   │   ├── Middleware/          # Middleware (19 файлов)
│   │   ├── Requests/            # Form Request валидация (17 файлов)
│   │   └── Responses/            # Кастомные ответы
│   ├── Models/                   # Eloquent модели (35 моделей)
│   ├── Notifications/            # Уведомления
│   ├── Observers/                # Model observers
│   ├── Providers/                # Service providers
│   ├── Services/                 # Бизнес-логика (10 сервисов)
│   └── Traits/                   # Переиспользуемые трейты
├── config/                       # Конфигурационные файлы
├── database/
│   ├── migrations/               # Миграции БД (125+ файлов)
│   ├── seeders/                  # Сидеры (8 файлов)
│   └── factories/                # Фабрики для тестов
├── public/                       # Публичная директория
├── resources/
│   ├── lang/                     # Локализация (17 файлов)
│   └── views/                    # Blade шаблоны (94 файла)
├── routes/
│   ├── api.php                   # API маршруты
│   ├── web.php                   # Web маршруты
│   ├── channels.php               # Broadcast каналы
│   └── console.php                # Console команды
└── storage/                      # Хранилище файлов
```

## 🚀 Установка

### Требования

- PHP >= 8.1
- Composer >= 2.0
- MySQL 5.7+ или SQLite 3
- Redis (опционально, но рекомендуется)

### Шаги установки

```bash
# Установка зависимостей
composer install

# Копирование конфигурации
cp .env.example .env

# Генерация ключа приложения
php artisan key:generate

# Настройка базы данных в .env
# Для разработки (SQLite):
DB_CONNECTION=sqlite
# Создать файл БД:
touch database/database.sqlite

# Для продакшена (MySQL):
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=account_arena
DB_USERNAME=root
DB_PASSWORD=your_password

# Выполнение миграций
php artisan migrate --seed

# Создание символической ссылки для хранилища
php artisan storage:link

# Запуск сервера разработки
php artisan serve
```

Backend будет доступен на `http://localhost:8000`

## ⚙️ Конфигурация

### Основные переменные окружения (.env)

```env
APP_NAME="Account Arena"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# База данных
DB_CONNECTION=sqlite
# или для MySQL:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=account_arena
# DB_USERNAME=root
# DB_PASSWORD=

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Кэш и сессии
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# OAuth провайдеры
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
TELEGRAM_BOT_TOKEN=

# Платежные системы
CRYPTOMUS_API_KEY=
CRYPTOMUS_MERCHANT_ID=
MONO_API_KEY=

# CORS
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1
FRONTEND_URL=http://localhost:3000
```

## 🔧 Основные команды Artisan

```bash
# Миграции
php artisan migrate                # Выполнить миграции
php artisan migrate:fresh --seed   # Пересоздать БД с сидерами
php artisan migrate:rollback      # Откатить последнюю миграцию

# Кэш
php artisan cache:clear            # Очистить кэш
php artisan config:cache          # Кэшировать конфигурацию
php artisan route:cache           # Кэшировать маршруты
php artisan view:cache            # Кэшировать шаблоны
php artisan optimize               # Оптимизация (все кэши)

# Очереди
php artisan queue:work            # Запустить worker очереди
php artisan queue:restart         # Перезапустить workers

# Планировщик
php artisan schedule:run          # Запустить планировщик (для cron)

# Tinker (интерактивная консоль)
php artisan tinker                # Открыть консоль Laravel
```

## 📊 Основные модели

- **User** - Пользователи системы
- **ServiceAccount** - Товары/аккаунты
- **Category** - Категории товаров
- **Purchase** - Покупки
- **Transaction** - Транзакции
- **BalanceTransaction** - Операции с балансом
- **ProductDispute** - Споры по товарам
- **SupportChat** - Чат поддержки
- **Promocode** - Промокоды
- **Voucher** - Ваучеры
- **Article** - Статьи
- **Banner** - Баннеры

## 🔌 API Endpoints

См. главный [README.md](../README.md#-api-endpoints) для полного списка API endpoints.

Основные группы:
- `/api/auth/*` - Аутентификация
- `/api/accounts/*` - Каталог товаров
- `/api/purchases/*` - Покупки
- `/api/balance/*` - Управление балансом
- `/api/disputes/*` - Споры
- `/api/support-chat/*` - Чат поддержки

## 🎯 Основные сервисы

- **BalanceService** - Управление балансом пользователей
- **ProductPurchaseService** - Логика покупки товаров
- **MonoPaymentService** - Интеграция с Monobank
- **EmailService** - Отправка email
- **NotifierService** - Система уведомлений
- **TelegramBotService** - Интеграция с Telegram
- **PromocodeValidationService** - Валидация промокодов

## 🧪 Тестирование

```bash
# Запуск всех тестов
php artisan test

# Запуск конкретного теста
php artisan test --filter TestName

# С покрытием кода
php artisan test --coverage
```

## 📝 Разработка

### Создание миграции

```bash
php artisan make:migration create_table_name
```

### Создание модели

```bash
php artisan make:model ModelName
php artisan make:model ModelName -m  # С миграцией
```

### Создание контроллера

```bash
php artisan make:controller ControllerName
php artisan make:controller ControllerName --resource  # Resource controller
```

### Создание сервиса

```bash
# Вручную создайте файл в app/Services/
```

## 🔐 Безопасность

- Все пароли хешируются через `bcrypt`
- API защищено через Laravel Sanctum
- Rate limiting на всех endpoints
- CSRF защита для web роутов
- Валидация всех входных данных через Form Requests

## 📚 Дополнительная документация

- [Laravel Documentation](https://laravel.com/docs/10.x)
- [Laravel Sanctum](https://laravel.com/docs/10.x/sanctum)
- [Laravel Socialite](https://laravel.com/docs/10.x/socialite)

## 👨‍💻 Автор

**Ivan Knysh** - [GitHub](https://github.com/Ivan14044)
