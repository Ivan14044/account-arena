{{-- Standalone branded admin login (matches the panel's SB Admin 2 design system). --}}
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Вход в админ-панель</title>
    <link rel="icon" href="{{ asset(config('adminlte.logo_img', 'vendor/adminlte/dist/img/AdminLTELogo.png')) }}">
    <style>
        :root {
            --aa-primary: #4e73df;
            --aa-primary-dark: #3a56b0;
            --aa-dark: #2c3e50;
            --aa-muted: #858796;
            --aa-border: #e3e6f0;
            --aa-danger: #e74a3b;
        }
        * { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: var(--aa-dark);
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            padding-top: max(24px, env(safe-area-inset-top));
            padding-bottom: max(24px, env(safe-area-inset-bottom));
            -webkit-font-smoothing: antialiased;
        }
        .login-shell { width: 100%; max-width: 420px; }
        .brand {
            text-align: center;
            margin-bottom: 22px;
            color: #fff;
        }
        .brand__logo {
            width: 64px; height: 64px;
            border-radius: 50%;
            background: #fff;
            display: inline-flex;
            align-items: center; justify-content: center;
            box-shadow: 0 8px 24px rgba(0,0,0,.18);
            margin-bottom: 14px;
            overflow: hidden;
        }
        .brand__logo img { width: 42px; height: 42px; object-fit: contain; }
        .brand__title { font-size: 1.35rem; font-weight: 600; letter-spacing: .2px; }
        .brand__subtitle { font-size: .85rem; opacity: .82; margin-top: 4px; }
        .card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 18px 50px rgba(20, 30, 70, .25);
            padding: 30px 28px;
        }
        .card__heading { font-size: 1.1rem; font-weight: 600; margin: 0 0 4px; }
        .card__hint { font-size: .85rem; color: var(--aa-muted); margin: 0 0 22px; }
        .alert {
            background: #fdecea;
            border: 1px solid #f5c6c2;
            color: #a52d20;
            border-radius: 10px;
            padding: 11px 14px;
            font-size: .85rem;
            margin-bottom: 18px;
            line-height: 1.45;
        }
        .alert ul { margin: 0; padding-left: 18px; }
        .field { margin-bottom: 18px; }
        .field > label {
            display: block;
            font-size: .82rem;
            font-weight: 600;
            margin-bottom: 7px;
            color: #4a5568;
        }
        .input-wrap { position: relative; }
        .input-wrap > svg {
            position: absolute;
            left: 14px; top: 50%;
            transform: translateY(-50%);
            width: 18px; height: 18px;
            color: var(--aa-muted);
            pointer-events: none;
        }
        .input-wrap input[type=email],
        .input-wrap input[type=password],
        .input-wrap input[type=text] {
            width: 100%;
            height: 48px;
            border: 1.5px solid var(--aa-border);
            border-radius: 10px;
            padding: 0 44px 0 42px;
            font-size: 1rem;
            color: var(--aa-dark);
            transition: border-color .15s ease, box-shadow .15s ease;
            background: #fff;
        }
        .input-wrap input:focus {
            outline: none;
            border-color: var(--aa-primary);
            box-shadow: 0 0 0 3px rgba(78, 115, 223, .15);
        }
        .input-wrap input.is-invalid { border-color: var(--aa-danger); }
        .toggle-pass {
            position: absolute;
            right: 8px; top: 50%;
            transform: translateY(-50%);
            border: none; background: transparent;
            color: var(--aa-muted);
            cursor: pointer;
            width: 32px; height: 32px;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 8px;
        }
        .toggle-pass:hover { color: var(--aa-dark); background: #f1f3f9; }
        .field-error { color: var(--aa-danger); font-size: .78rem; margin-top: 6px; }
        .row-between {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 22px;
            flex-wrap: wrap;
        }
        .remember { display: inline-flex; align-items: center; gap: 8px; font-size: .85rem; color: #4a5568; cursor: pointer; user-select: none; }
        .remember input { width: 17px; height: 17px; accent-color: var(--aa-primary); }
        .btn-submit {
            width: 100%;
            height: 48px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--aa-primary) 0%, var(--aa-primary-dark) 100%);
            color: #fff;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            transition: filter .15s ease, transform .05s ease;
        }
        .btn-submit:hover { filter: brightness(1.06); }
        .btn-submit:active { transform: translateY(1px); }
        .login-foot { text-align: center; margin-top: 18px; font-size: .8rem; color: rgba(255,255,255,.8); }
        @media (max-width: 575px) {
            .card { padding: 24px 18px; border-radius: 14px; }
            .brand__logo { width: 56px; height: 56px; }
        }
    </style>
</head>
<body>
    <div class="login-shell">
        <div class="brand">
            <span class="brand__logo">
                <img src="{{ asset(config('adminlte.logo_img', 'vendor/adminlte/dist/img/AdminLTELogo.png')) }}" alt="Логотип">
            </span>
            <div class="brand__title">Account Arena</div>
            <div class="brand__subtitle">Панель администратора</div>
        </div>

        <div class="card">
            <h1 class="card__heading">Вход в систему</h1>
            <p class="card__hint">Введите учётные данные администратора</p>

            @if ($errors->any())
                <div class="alert" role="alert">
                    @if ($errors->count() === 1)
                        {{ $errors->first() }}
                    @else
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif

            <form action="{{ route('admin.login') }}" method="post" novalidate>
                @csrf

                <div class="field">
                    <label for="email">Email</label>
                    <div class="input-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>
                        <input type="email" name="email" id="email" inputmode="email" autocomplete="username"
                               value="{{ old('email') }}"
                               class="@error('email') is-invalid @enderror"
                               placeholder="admin@example.com" required autofocus>
                    </div>
                    @error('email')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="password">Пароль</label>
                    <div class="input-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <input type="password" name="password" id="password" autocomplete="current-password"
                               class="@error('password') is-invalid @enderror"
                               placeholder="••••••••" required>
                        <button type="button" class="toggle-pass" aria-label="Показать пароль" data-toggle-password>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    @error('password')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row-between">
                    <label class="remember">
                        <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                        Запомнить меня
                    </label>
                </div>

                <button type="submit" class="btn-submit">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="m10 17 5-5-5-5"/><path d="M15 12H3"/></svg>
                    Войти
                </button>
            </form>
        </div>

        <div class="login-foot">© {{ date('Y') }} Account Arena</div>
    </div>

    <script>
        document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var input = btn.parentElement.querySelector('input');
                if (!input) return;
                input.type = input.type === 'password' ? 'text' : 'password';
                btn.setAttribute('aria-label', input.type === 'password' ? 'Показать пароль' : 'Скрыть пароль');
            });
        });
    </script>
</body>
</html>
