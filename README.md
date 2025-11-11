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
wget https://raw.githubusercontent.com/YOUR_USERNAME/account-arena/main/setup-server.sh
chmod +x setup-server.sh
./setup-server.sh
```

Скрипт автоматически установит все зависимости, настроит базу данных, соберет фронтенд и запустит сервисы.

📖 **Детальная документация:**
- [QUICK_START.md](QUICK_START.md) - Быстрое руководство
- [SERVER_SETUP_GUIDE.md](SERVER_SETUP_GUIDE.md) - Полная инструкция по настройке

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
- **SQLite** - Легковесная база данных (опционально MySQL/PostgreSQL)
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
- **[Chart.js](https://www.chartjs.org)** - Интерактивные графики

### Платежные системы
- **[Cryptomus](https://cryptomus.com)** - Криптовалютные платежи
- **[Monobank](https://www.monobank.ua)** - Украинская платежная система

### DevOps
- **Nginx** - Web сервер
- **PM2** - Process manager
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

# Создание базы данных
touch database/database.sqlite

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

Вход в админ-панель: `http://localhost:8000/login`

---

## ⚙️ Конфигурация

### Backend (.env)

Ключевые параметры конфигурации:

```env
# Основные настройки
APP_NAME="Account Arena"
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:3000

# База данных
DB_CONNECTION=sqlite

# OAuth провайдеры
GOOGLE_CLIENT_ID=your_client_id
GOOGLE_CLIENT_SECRET=your_client_secret
TELEGRAM_BOT_TOKEN=your_bot_token

# Платежные системы
CRYPTOMUS_MERCHANT_ID=your_merchant_id
CRYPTOMUS_PAYMENT_KEY=your_payment_key
MONOBANK_API_TOKEN=your_token

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
│   │   ├── Http/Controllers/
│   │   │   ├── Admin/           # Контроллеры админки
│   │   │   ├── Api/             # REST API endpoints
│   │   │   ├── Auth/            # Аутентификация
│   │   │   └── Supplier/        # Кабинет поставщика
│   │   ├── Models/              # Eloquent модели
│   │   ├── Services/            # Бизнес-логика
│   │   └── Observers/           # Model observers
│   ├── database/
│   │   ├── migrations/          # Миграции БД
│   │   └── seeders/             # Тестовые данные
│   ├── routes/
│   │   ├── api.php              # API маршруты
│   │   └── web.php              # Web маршруты
│   └── resources/views/         # Blade шаблоны
│
├── frontend/                     # Vue.js SPA
│   ├── src/
│   │   ├── components/          # Vue компоненты
│   │   ├── pages/               # Страницы приложения
│   │   ├── stores/              # Pinia state stores
│   │   ├── composables/         # Переиспользуемая логика
│   │   ├── types/               # TypeScript типы
│   │   ├── router.js            # Vue Router конфигурация
│   │   └── i18n/                # Переводы
│   └── public/                  # Статические ресурсы
│
├── .env.example                  # Примеры конфигурации
├── deploy-now.sh                 # Скрипт деплоя
├── setup-server.sh               # Скрипт установки
└── README.md                     # Документация
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

CRYPTOMUS_API_KEY=your_cryptomus_key
MONO_API_KEY=your_mono_key
```

### Frontend (.env):
```env
VITE_API_URL=http://localhost:8000/api
```

> Примечание: фронтенд поддерживает обе переменные `VITE_API_URL` и `VITE_API_BASE`. Рекомендуется использовать `VITE_API_URL`. Если заданы обе, приоритет у `VITE_API_URL`.
---

## 🔌 API Endpoints

### Публичные

```http
GET  /api/services         # Список сервисов
GET  /api/accounts         # Каталог товаров
GET  /api/articles         # Статьи
GET  /api/categories       # Категории
POST /api/register         # Регистрация
POST /api/login            # Вход
```

### Защищенные (требуют Bearer токен)

```http
GET    /api/user                      # Профиль пользователя
POST   /api/user                      # Обновление профиля
GET    /api/transactions              # История покупок
GET    /api/notifications             # Уведомления
POST   /api/cart                      # Оформление заказа
GET    /api/purchases                 # Купленные товары
POST   /api/disputes                  # Создание спора
GET    /api/balance/history           # История баланса
```

Подробная документация API доступна после установки по адресу `/api/documentation` (требуется установить пакет `l5-swagger`).

---


## 🚀 Развертывание

### Production на VPS

**Рекомендованные провайдеры:**
- [DigitalOcean](https://www.digitalocean.com) ($6/мес)
- [Hetzner](https://www.hetzner.com) (€4/мес)
- [Vultr](https://www.vultr.com) ($6/мес)

**Автоматическая установка:**

```bash
wget https://raw.githubusercontent.com/YOUR_USERNAME/account-arena/main/setup-server.sh
chmod +x setup-server.sh
./setup-server.sh
```

**Скрипты деплоя:**
- `setup-server.sh` - Первоначальная установка
- `deploy-now.sh` - Обновление с локального ПК
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

Возникли вопросы или проблемы? Создайте [Issue](https://github.com/YOUR_USERNAME/account-arena/issues) в репозитории.

---

<div align="center">

**Сделано с ❤️ и ☕**

[![Star History](https://img.shields.io/github/stars/YOUR_USERNAME/account-arena?style=social)](https://github.com/YOUR_USERNAME/account-arena/stargazers)

</div>
