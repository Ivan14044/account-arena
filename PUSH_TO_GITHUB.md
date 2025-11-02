# 📤 ИНСТРУКЦИЯ: Загрузка проекта на GitHub

## ✅ ВСЁ ГОТОВО К ЗАГРУЗКЕ!

**Репозиторий:** https://github.com/Ivan14044/market

---

## 🚀 ШАГ 1: Push кода на GitHub

### Выполните эту команду:

```bash
cd D:\project\Subcloudy
git push -u origin main
```

---

## 🔐 Возможные проблемы и решения:

### Проблема 1: "Authentication failed"

**Решение:**
GitHub теперь требует Personal Access Token вместо пароля.

1. Перейти: https://github.com/settings/tokens
2. Generate new token (classic)
3. Выбрать scopes: `repo` (полный доступ)
4. Скопировать токен
5. При push использовать токен вместо пароля

**Или настроить SSH:**
```bash
# Генерация SSH ключа
ssh-keygen -t ed25519 -C "iknys62@icloud.com"

# Добавить в GitHub: https://github.com/settings/keys
# Изменить remote на SSH:
git remote set-url origin git@github.com:Ivan14044/market.git
```

---

### Проблема 2: "Repository not found"

**Проверьте:**
- Репозиторий создан: https://github.com/Ivan14044/market
- У вас есть доступ к репозиторию
- Правильно указан remote:

```bash
git remote -v
# Должно показать:
# origin  https://github.com/Ivan14044/market.git (fetch)
# origin  https://github.com/Ivan14044/market.git (push)
```

---

### Проблема 3: "Large files detected"

Если файлы слишком большие (>100MB):

```bash
# Использовать Git LFS
git lfs install
git lfs track "*.sqlite"
git lfs track "*.mp4"
git add .gitattributes
git commit -m "Add Git LFS"
```

---

## ✅ ШАГ 2: Включить GitHub Pages

После успешного push:

1. Перейти: https://github.com/Ivan14044/market/settings/pages

2. **Source:** Deploy from a branch или GitHub Actions

3. **Branch:** `main`  **Folder:** `/(root)`

4. Или использовать GitHub Actions (уже настроено в `.github/workflows/deploy-frontend.yml`)

5. Нажать **Save**

6. Подождать 2-3 минуты

7. **Сайт будет доступен:**
   ```
   https://ivan14044.github.io/market/
   ```

---

## 🎯 ШАГ 3: Деплой Backend

**⚠️ Важно:** GitHub Pages работает только для статических сайтов (HTML/CSS/JS).

Для Laravel backend нужен отдельный хостинг:

### Рекомендуемые варианты (бесплатные):

1. **Railway** (самый простой) ⭐
   - Перейти: https://railway.app
   - Sign up with GitHub
   - New Project → Deploy from GitHub repo
   - Выбрать `Ivan14044/market`
   - Root Directory: `backend`
   - Автоматически настроится!

2. **Render** 
   - Перейти: https://render.com
   - New → Web Service
   - Connect GitHub
   - Настроить переменные

---

## 📝 ВАЖНО!

### После деплоя backend обновить Frontend:

В файле `frontend/.env.production` (создать если нет):
```env
VITE_API_URL=https://your-backend.up.railway.app/api
```

Пересобрать и задеплоить фронтенд заново.

---

## 🌐 ИТОГОВАЯ СХЕМА

```
Users → https://ivan14044.github.io/market/ (Frontend)
          ↓
        API calls
          ↓
        https://your-backend.up.railway.app (Backend)
          ↓
        Database (Railway MySQL)
```

---

## ✅ CHECKLIST

- [x] Git инициализирован
- [x] Код закоммичен
- [x] Remote добавлен
- [x] GitHub Actions настроен
- [ ] **PUSH НА GITHUB** ← Вы здесь
- [ ] Настроить GitHub Pages
- [ ] Деплой Backend
- [ ] Обновить API URL
- [ ] Тестирование

---

## 🚀 ГОТОВЫ К PUSH!

Выполните команду:

```bash
git push -u origin main
```

И сайт автоматически задеплоится на GitHub Pages!

---

**Создано для SubCloudy** 🌟

