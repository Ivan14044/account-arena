# 🌐 Account Arena

**Профессиональная платформа для продажи цифровых товаров и готовых аккаунтов**

Полнофункциональное решение для автоматизированной торговли цифровыми товарами с развитой системой управления для администраторов и поставщиков.

[![Laravel](https://img.shields.io/badge/Laravel-10.x-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![Vue.js](https://img.shields.io/badge/Vue.js-3.x-4FC08D?logo=vue.js&logoColor=white)](https://vuejs.org)
[![TypeScript](https://img.shields.io/badge/TypeScript-5.x-3178C6?logo=typescript&logoColor=white)](https://www.typescriptlang.org)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

---

## 📋 Содержание

- [Возможности](#-возможности)
- [Технологии](#-технологии)
- [Быстрый старт](#-быстрый-старт)
- [Установка](#-установка)
- [Конфигурация](#-конфигурация)
- [Развертывание](#-развертывание)
- [API](#-api-endpoints)
- [Лицензия](#-лицензия)

---

## 🚀 Быстрый старт

### Автоматическое развертывание на VPS

Для автоматической установки на чистый сервер используйте готовые скрипты:

```bash
# Ubuntu 20.04/22.04
wget https://raw.githubusercontent.com/Ivan14044/account-arena/main/setup-server.sh
chmod +x setup-server.sh
./setup-server.sh
```

Скрипт автоматически установит все зависимости, настроит базу данных, соберет фронтенд и запустит сервисы.

📖 **Детальная документация:**
- [QUICK_START.md](QUICK_START.md) - Быстрое руководство
- [SERVER_SETUP_GUIDE.md](SERVER_SETUP_GUIDE.md) - Полная инструкция по настройке сервера
- [DEPLOY.md](DEPLOY.md) - Инструкции по деплою
- [SERVER_COMMANDS.md](SERVER_COMMANDS.md) - Команды для работы с сервером
- [SSH_SETUP.md](SSH_SETUP.md) - Настройка SSH ключей

---

## ✨ Возможности

### Для клиентов
- 🔐 Авторизация через Email, Google, Telegram
- 🛒 Интуитивный каталог товаров с поиском и фильтрацией
- 💳 Интеграция с Cryptomus и Monobank
- 📦 Мгновенная доставка после оплаты
- 💰 Система баланса и история транзакций
- 🌍 Поддержка 3 языков (RU, EN, UK)
- 🌓 Адаптивный дизайн и темная тема
- 🎟️ Система промокодов и ваучеров

### Админ-панель
- 📊 Дашборд с аналитикой и статистикой
- 👥 Управление пользователями и поставщиками
- 📦 CRUD товаров с мультиязычной поддержкой
- 🏷️ Категории и теги
- 📰 CMS для статей и контента
- 💌 Настраиваемые email-шаблоны
- 🔔 Система уведомлений
- 💸 Управление выплатами поставщикам
- 🛡️ Система рассмотрения споров

### Кабинет поставщика
- 📊 Персональный дашборд с графиками продаж
- 📦 Управление ассортиментом
- 🛍️ Мониторинг заказов в реальном времени
- 💸 Гибкая система скидок
- 💰 Вывод средств с автоматическими расчетами комиссии
- 📈 Детальная аналитика эффективности
- ⭐ Рейтинговая система качества

---

## 🛠 Технологии

### Backend
- **[Laravel 10](https://laravel.com)** - Enterprise PHP framework
- **[Laravel Sanctum](https://laravel.com/docs/10.x/sanctum)** - SPA аутентификация
- **[AdminLTE 3](https://adminlte.io)** - Панель администрирования
- **MySQL** - База данных (SQLite для разработки)
- **[GeoIP2](https://github.com/maxmind/GeoIP2-php)** - Геолокация пользователей

### Frontend
- **[Vue 3](https://vuejs.org)** - Progressive JavaScript framework
- **[TypeScript](https://www.typescriptlang.org)** - Статическая типизация
- **[Pinia](https://pinia.vuejs.org)** - State management
- **[Vue Router 4](https://router.vuejs.org)** - Маршрутизация
- **[Vite](https://vitejs.dev)** - Современный сборщик
- **[Tailwind CSS](https://tailwindcss.com)** - Utility-first CSS
- **[Vuetify 3](https://vuetifyjs.com)** - Material Design компоненты
- **[Vue I18n](https://vue-i18n.intlify.dev)** - Интернационализация
- **[Swiper](https://swiperjs.com)** - Слайдеры и карусели
- **[Lottie](https://lottiefiles.com)** - Анимации

### Платежные системы
- **[Cryptomus](https://cryptomus.com)** - Криптовалютные платежи
- **[Monobank](https://www.monobank.ua)** - Украинская платежная система

### DevOps
- **Nginx** - Web сервер
- **Systemd** - Process manager для queue workers
- **Git** - Контроль версий

---

## 📦 Установка

### Системные требования

- **PHP** >= 8.1 (с расширениями: PDO, SQLite, mbstring, OpenSSL, JSON, cURL)
- **Composer** >= 2.0
- **Node.js** >= 18.x
- **NPM** >= 9.x (или Yarn >= 1.22)
- **Git**

### Установка Backend

```bash
cd backend

# Установка зависимостей
composer install --optimize-autoloader

# Создание конфигурации
cp .env.example .env

# Генерация ключа приложения
php artisan key:generate

# Создание базы данных (для SQLite)
touch database/database.sqlite

# Или настройте MySQL в .env файле (см. выше)

# Выполнение миграций
php artisan migrate --seed

# Создание символической ссылки для хранилища
php artisan storage:link

# Запуск сервера разработки
php artisan serve
```

Backend доступен: `http://localhost:8000`

### Установка Frontend

```bash
cd frontend

# Установка зависимостей
npm install

# Создание конфигурации
cp .env.example .env

# Запуск dev-сервера
npm run dev
```

Frontend доступен: `http://localhost:3000` (или другой порт, указанный Vite)

### Создание администратора

```bash
cd backend
php artisan tinker
```

```php
use App\Models\User;
use Illuminate\Support\Facades\Hash;

User::create([
    'name' => 'Admin',
    'email' => 'admin@example.com',
    'password' => Hash::make('secure_password_here'),
    'is_admin' => true,
]);
```

Вход в админ-панель: `http://localhost:8000/admin/login`

---

## ⚙️ Конфигурация

### Backend (.env)

Ключевые параметры конфигурации:

```env
# Основные настройки
APP_NAME="Account Arena"
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:3000

# База данных (для разработки используйте SQLite, для продакшена - MySQL)
DB_CONNECTION=sqlite
# Для MySQL раскомментируйте:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=account_arena
# DB_USERNAME=root
# DB_PASSWORD=

# OAuth провайдеры
GOOGLE_CLIENT_ID=your_client_id
GOOGLE_CLIENT_SECRET=your_client_secret
TELEGRAM_BOT_TOKEN=your_bot_token

# Платежные системы
CRYPTOMUS_API_KEY=your_cryptomus_api_key
CRYPTOMUS_MERCHANT_ID=your_merchant_id
MONO_API_KEY=your_monobank_api_key

# CORS
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1
```

### Frontend (.env)

```env
# API endpoint
VITE_API_URL=http://localhost:8000/api
```

### Создание поставщика

1. Войдите в админ-панель (`/login`)
2. Перейдите в **Пользователи**
3. Выберите пользователя или создайте нового
4. Установите флаг **"Поставщик товаров"**
5. Настройте комиссию (например, 10%)
6. Сохраните изменения

Вход в панель поставщика: `http://localhost:8000/supplier/login`

---

## 📁 Структура проекта

```plaintext
account-arena/
│
├── backend/                      # Laravel Backend
│   ├── app/
│   │   ├── Console/Commands/     # Artisan команды
│   │   ├── Exceptions/           # Обработчики исключений
│   │   ├── Helpers/              # Вспомогательные функции
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── Admin/        # Контроллеры админ-панели
│   │   │   │   ├── Api/          # REST API endpoints
│   │   │   │   ├── Auth/         # Аутентификация
│   │   │   │   ├── Seo/          # SEO контроллеры (SSR)
│   │   │   │   └── Supplier/     # Кабинет поставщика
│   │   │   ├── Middleware/       # Middleware
│   │   │   ├── Requests/         # Form Request валидация
│   │   │   └── Responses/        # Кастомные ответы
│   │   ├── Models/               # Eloquent модели
│   │   ├── Notifications/        # Уведомления
│   │   ├── Observers/            # Model observers
│   │   ├── Providers/            # Service providers
│   │   ├── Services/             # Бизнес-логика
│   │   └── Traits/               # Переиспользуемые трейты
│   ├── config/                    # Конфигурационные файлы
│   ├── database/
│   │   ├── migrations/           # Миграции БД
│   │   ├── seeders/              # Сидеры
│   │   └── factories/            # Фабрики для тестов
│   ├── public/                   # Публичная директория
│   ├── resources/
│   │   ├── lang/                 # Локализация
│   │   └── views/                # Blade шаблоны
│   ├── routes/
│   │   ├── api.php               # API маршруты
│   │   ├── web.php               # Web маршруты
│   │   ├── channels.php          # Broadcast каналы
│   │   └── console.php           # Console команды
│   ├── storage/                   # Хранилище файлов
│   ├── tests/                     # Тесты
│   ├── artisan                   # Artisan CLI
│   └── composer.json             # PHP зависимости
│
├── frontend/                      # Vue.js SPA
│   ├── src/
│   │   ├── assets/               # Статические ресурсы
│   │   ├── components/           # Vue компоненты
│   │   ├── composables/          # Composition API функции
│   │   ├── directives/           # Кастомные директивы
│   │   ├── i18n/                 # Переводы (Vue I18n)
│   │   ├── pages/                # Страницы приложения
│   │   ├── plugins/              # Vue плагины
│   │   ├── router.js             # Vue Router конфигурация
│   │   ├── stores/               # Pinia stores
│   │   ├── types/                # TypeScript типы
│   │   └── utils/                # Утилиты
│   ├── public/                   # Статические файлы
│   ├── dist/                     # Собранные файлы (после build)
│   ├── package.json              # Node.js зависимости
│   ├── vite.config.js            # Vite конфигурация
│   ├── tailwind.config.js        # Tailwind CSS конфигурация
│   └── tsconfig.json             # TypeScript конфигурация
│
├── deploy.sh                      # Скрипт деплоя (Bash)
├── deploy.ps1                     # Скрипт деплоя (PowerShell)
├── deploy-with-password.ps1       # Деплой с паролем (PowerShell)
├── update-project.sh              # Обновление на сервере
├── setup-server.sh                # Автоматическая установка
├── .gitignore                     # Git ignore правила
└── README.md                      # Документация
```

---

## 🔐 Переменные окружения

### Backend (.env):
```env
APP_NAME=Account Arena
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:3000

DB_CONNECTION=sqlite

GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret

TELEGRAM_BOT_TOKEN=your_telegram_bot_token

CRYPTOMUS_API_KEY=your_cryptomus_api_key
CRYPTOMUS_MERCHANT_ID=your_merchant_id
MONO_API_KEY=your_monobank_api_key
```

### Frontend (.env):
```env
VITE_API_URL=http://localhost:8000/api
```

> Примечание: фронтенд поддерживает обе переменные `VITE_API_URL` и `VITE_API_BASE`. Рекомендуется использовать `VITE_API_URL`. Если заданы обе, приоритет у `VITE_API_URL`.
---

## 🔌 API Endpoints

### Публичные (без аутентификации)

**Аутентификация:**
```http
POST /api/register              # Регистрация
POST /api/login                 # Вход
POST /api/forgot-password       # Восстановление пароля
POST /api/reset-password        # Сброс пароля
```

**Каталог и контент:**
```http
GET  /api/accounts                      # Каталог товаров
GET  /api/accounts/{id}                 # Детали товара
GET  /api/accounts/{id}/similar         # Похожие товары
GET  /api/articles                      # Список статей
GET  /api/articles/{id}                 # Детали статьи
GET  /api/categories                    # Список категорий
GET  /api/categories/{id}/subcategories  # Подкатегории
GET  /api/pages                         # Статические страницы
GET  /api/banners                       # Баннеры
GET  /api/site-content                  # Контент сайта
GET  /api/purchase-rules                # Правила покупки
GET  /api/support-chat-settings         # Настройки чата поддержки
```

**Промокоды:**
```http
POST /api/promocodes/validate           # Проверка промокода
```

**Чат поддержки (публичный):**
```http
POST /api/support-chat/create           # Создание/получение чата
GET  /api/support-chat/{id}/messages    # Сообщения чата
POST /api/support-chat/{id}/messages     # Отправка сообщения
POST /api/support-chat/{id}/typing       # Индикатор печати
POST /api/support-chat/{id}/typing/stop  # Остановка индикатора
GET  /api/support-chat/{id}/typing/status # Статус печати
POST /api/support-chat/{id}/rating       # Оценка чата
```

**Гостевые покупки:**
```http
POST /api/guest/cart                    # Создание гостевой корзины
POST /api/guest/mono/create-payment     # Создание платежа Monobank (гость)
POST /api/guest/cryptomus/create-payment # Создание платежа Cryptomus (гость)
```

**Health check:**
```http
GET  /api/health                        # Проверка здоровья сервиса
GET  /api/ping                          # Ping endpoint
```

### Защищенные (требуют Bearer токен)

**Профиль пользователя:**
```http
GET  /api/user                          # Профиль пользователя
POST /api/user                          # Обновление профиля
GET  /api/logout                        # Выход
```

**Уведомления:**
```http
GET  /api/notifications                 # Список уведомлений
POST /api/notifications/read            # Отметить как прочитанное
POST /api/notifications/read-all        # Отметить все как прочитанные
```

**Корзина и покупки:**
```http
POST /api/cart                          # Оформление заказа
GET  /api/purchases                     # Список покупок
GET  /api/purchases/{id}                # Детали покупки
GET  /api/purchases/{id}/download       # Скачать товар
```

**Транзакции:**
```http
GET  /api/transactions                  # История транзакций
```

**Баланс:**
```http
GET  /api/balance                       # Текущий баланс
GET  /api/balance/history               # История баланса
POST /api/balance/check-funds           # Проверка достаточности средств
GET  /api/balance/statistics            # Статистика баланса
```

**Платежи:**
```http
POST /api/mono/create-payment          # Создание платежа Monobank
POST /api/cryptomus/create-payment     # Создание платежа Cryptomus
POST /api/mono/topup                    # Пополнение баланса Monobank
POST /api/cryptomus/topup               # Пополнение баланса Cryptomus
```

**Споры:**
```http
GET  /api/disputes                      # Список споров
POST /api/disputes                      # Создание спора
GET  /api/disputes/{id}                 # Детали спора
GET  /api/transactions/{id}/can-dispute # Проверка возможности спора
```

**Ваучеры:**
```http
POST /api/vouchers/activate             # Активация ваучера
```

**Чат поддержки (авторизованный):**
```http
GET  /api/support-chats                 # Список чатов пользователя
```

**Расширение браузера:**
```http
POST /api/extension/settings            # Сохранение настроек расширения
GET  /api/extension/auth                # Статус авторизации расширения
```

**Браузер API:**
```http
GET  /api/browser/new                   # Создание нового браузера
POST /api/browser/stop                  # Остановка браузера
POST /api/browser/stop_all              # Остановка всех браузеров
GET  /api/browser/list                  # Список браузеров
```

### Webhooks

```http
POST /api/cryptomus/webhook             # Webhook Cryptomus
POST /api/mono/webhook                  # Webhook Monobank
POST /api/telegram/webhook              # Webhook Telegram
```

### Дополнительные endpoints

```http
GET  /api/contents/{code}               # Получение контента по коду
GET  /api/options                       # Опции системы
GET  /api/cookie/check                  # Проверка согласия на cookies
```

---


## 🚀 Развертывание

### Production на VPS

**Рекомендованные провайдеры:**
- [DigitalOcean](https://www.digitalocean.com) ($6/мес)
- [Hetzner](https://www.hetzner.com) (€4/мес)
- [Vultr](https://www.vultr.com) ($6/мес)

**Автоматическая установка:**

```bash
wget https://raw.githubusercontent.com/Ivan14044/account-arena/main/setup-server.sh
chmod +x setup-server.sh
./setup-server.sh
```

**Скрипты деплоя:**
- `setup-server.sh` - Первоначальная установка
- `deploy.sh` - Обновление с локального ПК (Bash)
- `deploy.ps1` - Обновление с локального ПК (PowerShell)
- `update-project.sh` - Обновление на сервере

### Production сборка

**Backend:**
```bash
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**Frontend:**
```bash
npm run build
# Собранные файлы в dist/
```

### Альтернативные варианты

**Раздельный хостинг:**
- Backend: [Railway](https://railway.app) / [Render](https://render.com)
- Frontend: [Vercel](https://vercel.com) / [Netlify](https://netlify.com)

⚠️ Требуется корректная настройка CORS в Laravel.

---

## 🤝 Вклад в проект

Мы приветствуем вклад сообщества! Пожалуйста:

1. Fork проекта
2. Создайте feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit изменения (`git commit -m 'Add AmazingFeature'`)
4. Push в branch (`git push origin feature/AmazingFeature`)
5. Откройте Pull Request

Для крупных изменений сначала создайте Issue для обсуждения.

---

## 📝 Лицензия

Проект распространяется под лицензией MIT. Подробности в файле [LICENSE](LICENSE).

---

## 👨‍💻 Автор

**Ivan Knysh**

[![GitHub](https://img.shields.io/badge/GitHub-Ivan14044-181717?logo=github)](https://github.com/Ivan14044)
[![Email](https://img.shields.io/badge/Email-iknys62%40icloud.com-EA4335?logo=gmail&logoColor=white)](mailto:iknys62@icloud.com)

---

## 📞 Поддержка

Возникли вопросы или проблемы? Создайте [Issue](https://github.com/Ivan14044/account-arena/issues) в репозитории.

---

<div align="center">

**Сделано с ❤️ и ☕**

[![Star History](https://img.shields.io/github/stars/Ivan14044/account-arena?style=social)](https://github.com/Ivan14044/account-arena/stargazers)

</div>
