# 🌐 Account Arena - Платформа для продажи цифровых товаров

Современная платформа для продажи готовых аккаунтов, ключей, лицензий и цифровых товаров с панелью администратора и кабинетом поставщика.

![License](https://img.shields.io/badge/license-MIT-blue.svg)
![Laravel](https://img.shields.io/badge/Laravel-10.x-red.svg)
![Vue](https://img.shields.io/badge/Vue.js-3.x-green.svg)

---

## 🚀 БЫСТРАЯ УСТАНОВКА НА СЕРВЕР (ПРОДАКШН)

### ⚡ Для новичков - автоматическая установка на VPS:

Я создал готовые скрипты для автоматической установки на сервер!

**📖 ИНСТРУКЦИИ:**

1. **`🚀_ЧИТАЙ_МЕНЯ_ПЕРВЫМ.md`** - Начни отсюда! Главная инструкция
2. **`✨_НАЧНИ_ЗДЕСЬ_НОВИЧОК.md`** - Для новичков (очень просто!)
3. **`КОПИРУЙ_И_ВСТАВЬ_ЭТО.txt`** - Скрипт автоустановки (скопируй и вставь в терминал)

**Всего 3 шага:**
1. Подключись к серверу по SSH
2. Скопируй содержимое файла `КОПИРУЙ_И_ВСТАВЬ_ЭТО.txt`
3. Вставь в терминал и жди 20 минут - готово!

Скрипт автоматически установит: Nginx, PHP 8.2, MySQL, Redis, Node.js, SSL сертификат и запустит сайт!

**Подробности:** Смотри файл `СПИСОК_ФАЙЛОВ.md` со всеми инструкциями

---

## ✨ Возможности

### 👥 Для пользователей:
- 🔐 Регистрация и авторизация (Email, Google, Telegram)
- 🛒 Покупка цифровых товаров
- 💳 Оплата через Cryptomus и Mono
- 📦 Моментальная доставка товаров
- 💰 Личный баланс и история покупок
- 🌍 Мультиязычность (RU, EN, UK)
- 🌓 Темная/светлая тема

### 👨‍💼 Для администраторов:
- 📊 Dashboard с аналитикой
- 👤 Управление пользователями
- 📦 Управление товарами
- 🏷️ Категории товаров
- 📰 Система статей и контента
- 💌 Email шаблоны
- 🔔 Система уведомлений
- 🎟️ Промокоды и ваучеры

### 🏪 Для поставщиков:
- 📊 Персональный Dashboard с графиками
- 📦 Управление своими товарами
- 🛍️ Просмотр заказов с фильтрами
- 💸 Система скидок
- 🔔 Уведомления о продажах
- 💰 Отслеживание баланса и комиссии
- 📈 Детальная аналитика по товарам

---

## 🛠 Технологии

### Backend:
- **Laravel 10** - PHP фреймворк
- **SQLite** - база данных
- **Laravel Sanctum** - API аутентификация
- **AdminLTE 3** - админ панель

### Frontend:
- **Vue 3** - JavaScript фреймворк
- **TypeScript** - типизация
- **Pinia** - state management
- **Vue Router** - маршрутизация
- **Vite** - сборщик
- **Tailwind CSS** - стили
- **i18n** - интернационализация
- **Chart.js** - графики

---

## 🚀 Установка

### Требования:
- PHP >= 8.1
- Composer
- Node.js >= 18
- NPM или Yarn

### Backend:

```bash
cd backend

# Установка зависимостей
composer install

# Создание .env файла
copy .env.example .env

# Генерация ключа приложения
php artisan key:generate

# Создание базы данных SQLite
type nul > database/database.sqlite

# Миграции
php artisan migrate

# Создание storage symlink
php artisan storage:link

# Запуск сервера
php artisan serve
```

Backend будет доступен на: `http://localhost:8000`

### Frontend:

```bash
cd frontend

# Установка зависимостей
npm install

# Запуск dev сервера
npm run dev
```

Frontend будет доступен на: `http://localhost:3000`

---

## 👤 Создание администратора

```bash
cd backend
php artisan tinker
```

```php
use App\Models\User;
use Illuminate\Support\Facades\Hash;

User::create([
    'name' => 'Admin',
    'email' => 'admin@mail.com',
    'password' => Hash::make('password123'),
    'is_admin' => true,
    'is_supplier' => false,
]);
```

**Вход в админ панель:**
- URL: `http://localhost:8000/login`
- Email: `admin@mail.com`
- Password: `password123`

---

## 🏪 Создание поставщика

В админ панели:
1. Перейти в **Пользователи**
2. Редактировать нужного пользователя
3. Поставить галочку **"Поставщик товаров"**
4. Установить комиссию (например, 10%)
5. Сохранить

**Вход в панель поставщика:**
- URL: `http://localhost:8000/supplier/login`

---

## 📁 Структура проекта

```
AccountArena/
├── backend/              # Laravel API
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── Admin/        # Админ панель
│   │   │   │   ├── Api/          # API endpoints
│   │   │   │   ├── Auth/         # Аутентификация
│   │   │   │   └── Supplier/     # Панель поставщика
│   │   │   └── Middleware/
│   │   ├── Models/
│   │   └── Providers/
│   ├── database/
│   │   └── migrations/
│   ├── resources/
│   │   └── views/         # Blade шаблоны
│   └── routes/
│       ├── api.php        # API маршруты
│       └── web.php        # Web маршруты
│
└── frontend/             # Vue.js SPA
    ├── src/
    │   ├── components/
    │   ├── pages/
    │   ├── stores/        # Pinia stores
    │   ├── router.js
    │   └── App.vue
    └── public/
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

---

## 📚 API Документация

### Основные endpoints:

**Публичные:**
- `GET /api/accounts` - список товаров
- `GET /api/categories` - категории
- `GET /api/articles` - статьи

**Защищенные (требуют токен):**
- `GET /api/user` - данные пользователя
- `POST /api/cart` - добавить в корзину
- `GET /api/transactions` - история покупок
- `GET /api/notifications` - уведомления

---

## 🎨 Функционал

### Панель администратора:
- ✅ Dashboard с графиками продаж
- ✅ Управление пользователями и ролями
- ✅ CRUD товаров с мультиязычностью
- ✅ Категории товаров
- ✅ Промокоды и скидки
- ✅ Email шаблоны
- ✅ Система уведомлений
- ✅ Статьи и страницы контента

### Панель поставщика:
- ✅ Dashboard со статистикой
- ✅ График продаж за 7 дней
- ✅ Топ-5 товаров
- ✅ Управление товарами
- ✅ Просмотр заказов
- ✅ Система скидок
- ✅ Уведомления о продажах
- ✅ Отслеживание баланса

### Frontend:
- ✅ Главная страница с каталогом
- ✅ Фильтрация товаров
- ✅ Личный кабинет
- ✅ История покупок
- ✅ Адаптивный дизайн
- ✅ Темная тема
- ✅ 3 языка интерфейса

---

## 🌐 Деплой на продакшн

### Опции для хостинга:

**Backend (Laravel):**
- [Railway](https://railway.app) - бесплатный tier
- [Render](https://render.com) - бесплатный tier
- [DigitalOcean](https://www.digitalocean.com) - $5/месяц
- [AWS](https://aws.amazon.com) - pay as you go

**Frontend (Vue.js):**
- [Vercel](https://vercel.com) - бесплатный для static sites
- [Netlify](https://www.netlify.com) - бесплатный
- [GitHub Pages](https://pages.github.com) - бесплатный
- [Cloudflare Pages](https://pages.cloudflare.com) - бесплатный

### Рекомендация:
1. **Backend** → Railway или Render (бесплатно)
2. **Frontend** → Vercel или Netlify (бесплатно)

---

## 📝 Лицензия

MIT License - свободно используйте и модифицируйте.

---

## 👨‍💻 Автор

**Ivan Knysh**
- GitHub: [@Ivan14044](https://github.com/Ivan14044)
- Email: iknys62@icloud.com

---

## 🤝 Вклад в проект

Pull requests приветствуются! Для крупных изменений сначала откройте issue.

---

## 📞 Поддержка

Если у вас возникли вопросы или проблемы, создайте issue в репозитории.

---

**Создано с ❤️ для Account Arena**
