# 🌐 НАСТРОЙКА GITHUB PAGES - ПОШАГОВАЯ ИНСТРУКЦИЯ

## ✅ КОД УЖЕ НА GITHUB!

**Репозиторий:** https://github.com/Ivan14044/market

---

## 📋 ШАГ 1: Включить GitHub Pages

1. **Перейти в настройки:**
   ```
   https://github.com/Ivan14044/market/settings/pages
   ```

2. **Source:** выбрать **"Deploy from a branch"**

3. **Branch:** выбрать **`main`**

4. **Folder:** выбрать **`/ (root)`** или **`/frontend/dist`**

5. Нажать **Save**

6. **Подождать 2-3 минуты**

7. **Сайт будет доступен:**
   ```
   https://ivan14044.github.io/market/
   ```

---

## ⚠️ ВАЖНО: GitHub Pages - только для статики!

GitHub Pages НЕ поддерживает:
- ❌ PHP / Laravel
- ❌ Базы данных
- ❌ Backend API

Поэтому на GitHub Pages будет работать **ТОЛЬКО FRONTEND**.

---

## 🚀 ДЛЯ ПОЛНОЙ РАБОТЫ САЙТА

Нужно задеплоить **Backend отдельно**.

### ЛУЧШИЙ ВАРИАНТ: Railway (бесплатно)

#### ШАГ 1: Зарегистрироваться
```
https://railway.app
```
Войти через GitHub

#### ШАГ 2: Создать проект
1. **New Project**
2. **Deploy from GitHub repo**
3. Выбрать `Ivan14044/market`
4. **Root Directory:** `backend` ⚠️ важно!
5. Railway автоматически определит Laravel

#### ШАГ 3: Добавить переменные окружения

В Railway Dashboard → Variables → Raw Editor:

```env
APP_NAME=SubCloudy
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:СГЕНЕРИРОВАТЬ_ЛОКАЛЬНО
APP_URL=${{RAILWAY_PUBLIC_DOMAIN}}

DB_CONNECTION=mysql
DB_HOST=${{MYSQL_HOST}}
DB_PORT=${{MYSQL_PORT}}
DB_DATABASE=${{MYSQL_DATABASE}}
DB_USERNAME=${{MYSQL_USER}}
DB_PASSWORD=${{MYSQL_PASSWORD}}

SESSION_DRIVER=database
CACHE_DRIVER=database
QUEUE_CONNECTION=database

FRONTEND_URL=https://ivan14044.github.io

# Ваши API ключи
GOOGLE_CLIENT_ID=ваш_ключ
GOOGLE_CLIENT_SECRET=ваш_секрет
TELEGRAM_BOT_TOKEN=ваш_токен
CRYPTOMUS_API_KEY=ваш_ключ
MONO_API_KEY=ваш_ключ
```

#### ШАГ 4: Добавить MySQL базу данных

В Railway:
1. **New** → **Database** → **Add MySQL**
2. Переменные автоматически добавятся

#### ШАГ 5: Деплой!

Railway автоматически задеплоит backend!

**Backend будет:** `https://market-production.up.railway.app`

---

## 🔗 ШАГ 4: Связать Frontend и Backend

### В GitHub Secrets:

1. Перейти: https://github.com/Ivan14044/market/settings/secrets/actions

2. **New repository secret:**
   - Name: `VITE_API_URL`
   - Value: `https://market-production.up.railway.app/api`

3. **Save**

4. **Пересобрать фронтенд:**
   - Actions → Deploy Frontend → Re-run workflow

---

## ✅ ИТОГОВАЯ СХЕМА

```
Пользователи
    ↓
Frontend: https://ivan14044.github.io/market/
    ↓ (API calls)
Backend: https://market-production.up.railway.app
    ↓
Railway MySQL Database
```

---

## 📱 АЛЬТЕРНАТИВА: Vercel для Frontend (ЛУЧШЕ)

**Вместо GitHub Pages используйте Vercel:**

### Преимущества:
- ✅ Быстрее
- ✅ Автоматический HTTPS
- ✅ Лучший CDN
- ✅ Custom domains
- ✅ Preview deployments

### Команды:
```bash
npm i -g vercel
cd frontend
vercel --prod
```

**Будет:** `https://market.vercel.app` (красивее!)

---

## 🎯 РЕКОМЕНДАЦИЯ

### Лучшая связка (100% бесплатно):

1. **Frontend:** Vercel
   ```bash
   cd frontend
   vercel --prod
   ```

2. **Backend:** Railway
   ```
   https://railway.app
   → Deploy from GitHub
   → Root: backend
   ```

3. **Связать:**
   - В Vercel: Environment Variable `VITE_API_URL` = Railway URL
   - В Railway: Environment Variable `FRONTEND_URL` = Vercel URL

---

## ✨ ТЕКУЩИЙ СТАТУС

✅ Код на GitHub: https://github.com/Ivan14044/market  
⏳ Ждем включения GitHub Pages  
⏳ Ждем деплоя Backend на Railway  

---

## 📞 ЧТО ДЕЛАТЬ СЕЙЧАС?

1. **Перейти:** https://github.com/Ivan14044/market/settings/pages
2. **Включить GitHub Pages** (инструкция выше)
3. **Перейти:** https://railway.app
4. **Задеплоить Backend**
5. **Готово!** 🎉

---

**Хотите, чтобы я помог с настройкой Railway?** Могу дать подробную инструкцию!

