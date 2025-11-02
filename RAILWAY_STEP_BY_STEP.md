# 🚂 ПОШАГОВАЯ ИНСТРУКЦИЯ: ДЕПЛОЙ НА RAILWAY

## 📋 ШАГ 1: Регистрация

1. Открыть: **https://railway.app**
2. Нажать **"Start a New Project"** или **"Login with GitHub"**
3. Авторизоваться через GitHub
4. Разрешить доступ к репозиториям

---

## 📦 ШАГ 2: Создание проекта

1. **Dashboard → "New Project"**

2. **Выбрать:** "Deploy from GitHub repo"

3. **Выбрать репозиторий:** `Ivan14044/market`

4. **⚠️ ВАЖНО! Root Directory:**
   - Нажать **"Add variables"**
   - Нажать **"Settings"** (шестеренка)
   - **Root Directory:** `backend`
   - **Save**

5. Railway начнет автоматический деплой

---

## 🗄️ ШАГ 3: Добавить MySQL базу данных

1. **В проекте нажать:** "+ New"

2. **Выбрать:** "Database" → "Add MySQL"

3. Railway автоматически:
   - Создаст базу данных
   - Добавит переменные: `MYSQL_HOST`, `MYSQL_PORT`, `MYSQL_DATABASE`, `MYSQL_USER`, `MYSQL_PASSWORD`

---

## ⚙️ ШАГ 4: Добавить переменные окружения

1. **Кликнуть на сервис** (backend)

2. **Перейти:** "Variables"

3. **Нажать:** "Raw Editor"

4. **Вставить** содержимое файла `RAILWAY_ENV_VARIABLES.txt`

5. **⚠️ ОБЯЗАТЕЛЬНО ИЗМЕНИТЬ:**

### APP_KEY - сгенерировать локально:
```bash
cd D:\project\Subcloudy\backend
php artisan key:generate --show
```
Скопировать результат и вставить в Railway.

### Заполнить свои API ключи:
- `GOOGLE_CLIENT_ID`
- `GOOGLE_CLIENT_SECRET`
- `TELEGRAM_BOT_TOKEN`
- `CRYPTOMUS_API_KEY`
- `MONO_API_KEY`

6. **Нажать:** "Deploy" или сервис перезапустится автоматически

---

## 🌐 ШАГ 5: Получить публичный URL

1. **В сервисе (backend) → "Settings"**

2. **Networking → "Generate Domain"**

3. Railway создаст домен типа:
   ```
   market-production.up.railway.app
   ```

4. **Скопировать этот URL!**

---

## 🔗 ШАГ 6: Обновить CORS

1. **В Railway Variables добавить:**
   ```env
   FRONTEND_URL=https://ivan14044.github.io
   SANCTUM_STATEFUL_DOMAINS=ivan14044.github.io,market.vercel.app
   ```

2. **Локально** обновить `backend/config/cors.php`:
   ```php
   'allowed_origins' => [
       'https://ivan14044.github.io',
       'https://market.vercel.app', // если используете Vercel
   ],
   ```

3. **Push изменения:**
   ```bash
   git add .
   git commit -m "Update CORS for production"
   git push
   ```

Railway автоматически передеплоит!

---

## 🎨 ШАГ 7: Настроить Frontend

### В GitHub:

1. **Перейти:** https://github.com/Ivan14044/market/settings/secrets/actions

2. **New repository secret:**
   - **Name:** `VITE_API_URL`
   - **Value:** `https://market-production.up.railway.app/api` (ваш Railway URL)

3. **Save**

### Обновить локально:

Создать `frontend/.env.production`:
```env
VITE_API_URL=https://market-production.up.railway.app/api
```

**Собрать и задеплоить:**
```bash
cd frontend
npm run build

# Если используете Vercel:
vercel --prod

# Если GitHub Pages:
# Загрузить dist/ в отдельную ветку gh-pages
```

---

## 🗄️ ШАГ 8: Запустить миграции и создать админа

### В Railway Dashboard:

1. **Кликнуть на сервис backend**

2. **Перейти во вкладку:** "Deployments"

3. **Последний деплой → View Logs**

4. Должны увидеть:
   ```
   INFO  Running migrations.
   ```

### Создать админа через Railway CLI:

```bash
# Установить Railway CLI
npm i -g @railway/cli

# Войти
railway login

# Подключиться к проекту
railway link

# Выполнить команду
railway run php artisan tinker
```

В tinker:
```php
use App\Models\User;
use Illuminate\Support\Facades\Hash;

User::create([
    'name' => 'Admin',
    'email' => 'admin@subcloudy.com',
    'password' => Hash::make('YourSecurePassword123!'),
    'is_admin' => true,
    'is_supplier' => false,
]);
```

---

## ✅ ПРОВЕРКА РАБОТЫ

### Backend:
```
https://market-production.up.railway.app
```
Должна открыться страница Laravel.

### API:
```
https://market-production.up.railway.app/api/accounts
```
Должен вернуть JSON с товарами.

### Frontend:
```
https://ivan14044.github.io/market/
```
Должен открыться ваш сайт!

---

## 🎯 ИТОГОВАЯ СХЕМА

```
GitHub Repository
    ↓
    ├── Frontend → GitHub Pages → https://ivan14044.github.io/market/
    └── Backend → Railway → https://market-production.up.railway.app
                     ↓
                   MySQL Database (Railway)
```

---

## 📊 МОНИТОРИНГ

### Railway Dashboard покажет:
- ✅ CPU usage
- ✅ Memory usage
- ✅ Deployment logs
- ✅ Database metrics
- ✅ Request count

---

## 💰 СТОИМОСТЬ

**Railway бесплатный tier:**
- ✅ 500 часов выполнения/месяц
- ✅ 100 GB исходящего трафика
- ✅ Неограниченное количество проектов
- ✅ MySQL база данных включена

**Этого хватит для ~20 дней непрерывной работы!**

---

## 🆘 ЧАСТЫЕ ПРОБЛЕМЫ

### 1. "Build failed"
**Решение:** Проверьте что Root Directory = `backend`

### 2. "Migration error"
**Решение:** Убедитесь что MySQL database добавлена

### 3. "APP_KEY missing"
**Решение:** Сгенерируйте локально и добавьте в Variables

### 4. "CORS error"
**Решение:** Проверьте `FRONTEND_URL` и `SANCTUM_STATEFUL_DOMAINS`

---

## 📝 ВАЖНЫЕ ФАЙЛЫ СОЗДАНЫ

- ✅ `railway.json` - конфигурация Railway (в корне)
- ✅ `backend/railway.json` - конфигурация для backend
- ✅ `backend/Procfile` - команды запуска
- ✅ `backend/nixpacks.toml` - настройки сборки
- ✅ `RAILWAY_ENV_VARIABLES.txt` - список переменных
- ✅ `RAILWAY_STEP_BY_STEP.md` - эта инструкция

---

## 🚀 НАЧИНАЕМ!

**Откройте прямо сейчас:** https://railway.app

Следуйте инструкции выше шаг за шагом!

Когда дойдете до добавления переменных окружения - используйте файл `RAILWAY_ENV_VARIABLES.txt`

**Если возникнут проблемы на любом шаге - напишите, и я помогу!** 💪

