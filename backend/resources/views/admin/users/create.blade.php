@extends('adminlte::page')

@section('title', 'Добавить пользователя')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0">
            <i class="fas fa-user-plus text-primary mr-2"></i>
            Добавить нового пользователя
        </h1>
        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary shadow-sm">
            <i class="fas fa-arrow-left mr-2"></i>Назад к списку
        </a>
    </div>
@stop

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10">
            <div class="card shadow-sm modern-card">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-id-card mr-2"></i>
                        Данные нового пользователя
                    </h3>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('admin.users.store') }}" id="createUserForm">
                        @csrf
                        
                        <!-- Информационный блок -->
                        <div class="alert alert-info border-0 shadow-sm mb-4">
                            <i class="fas fa-info-circle mr-2"></i>
                            <strong>Внимание:</strong> После создания пользователь получит доступ к системе с указанным email и паролем.
                        </div>

                        <!-- Email -->
                        <div class="form-group">
                            <label for="email" class="font-weight-bold">
                                <i class="fas fa-envelope mr-2 text-primary"></i>
                                Email адрес (Логин)
                                <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-at"></i>
                                    </span>
                                </div>
                                <input 
                                    type="email" 
                                    name="email" 
                                    id="email" 
                                    class="form-control form-control-lg @error('email') is-invalid @enderror" 
                                    value="{{ old('email') }}" 
                                    placeholder="user@example.com"
                                    required
                                    autofocus
                                >
                                @error('email')
                                    <div class="invalid-feedback d-block">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>{{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <small class="form-text text-muted">
                                <i class="fas fa-lightbulb mr-1"></i>
                                Этот email будет использоваться для входа в систему
                            </small>
                        </div>

                        <!-- Пароль -->
                        <div class="form-group">
                            <label for="password" class="font-weight-bold">
                                <i class="fas fa-key mr-2 text-primary"></i>
                                Пароль
                                <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                </div>
                                <input 
                                    type="password" 
                                    name="password" 
                                    id="password" 
                                    class="form-control form-control-lg @error('password') is-invalid @enderror"
                                    placeholder="Введите надежный пароль"
                                    required
                                >
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="fas fa-eye" id="togglePasswordIcon"></i>
                                    </button>
                                    <button class="btn btn-outline-primary" type="button" id="generatePassword">
                                        <i class="fas fa-random mr-1"></i>Сгенерировать
                                    </button>
                                </div>
                                @error('password')
                                    <div class="invalid-feedback d-block">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>{{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <small class="form-text text-muted">
                                <i class="fas fa-shield-alt mr-1"></i>
                                Минимум 6 символов. Рекомендуется использовать буквы, цифры и символы
                            </small>
                            <!-- Индикатор силы пароля -->
                            <div class="password-strength mt-2" id="passwordStrength" style="display: none;">
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                </div>
                                <small class="password-strength-text"></small>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Кнопки действий -->
                        <div class="row">
                            <div class="col-md-6">
                                <button type="submit" class="btn btn-primary btn-lg btn-block shadow-sm">
                                    <i class="fas fa-user-plus mr-2"></i>Создать пользователя
                                </button>
                            </div>
                            <div class="col-md-6">
                                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary btn-lg btn-block">
                                    <i class="fas fa-times mr-2"></i>Отмена
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Дополнительная информация -->
            <div class="card shadow-sm mt-3">
                <div class="card-body">
                    <h5 class="font-weight-bold mb-3">
                        <i class="fas fa-question-circle text-info mr-2"></i>
                        Полезная информация
                    </h5>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="fas fa-check text-success mr-2"></i>
                            Имя пользователя будет автоматически создано из email
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success mr-2"></i>
                            Пользователь сможет изменить свои данные после входа
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success mr-2"></i>
                            Вы сможете управлять балансом и подписками пользователя позже
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('css')
    <style>
        .modern-card {
            border: none;
            border-radius: 1rem;
            overflow: hidden;
        }
        
        .modern-card .card-header {
            border-bottom: none;
            padding: 1.5rem;
        }

        .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        }

        .input-group-text {
            border-color: #dee2e6;
        }

        .password-strength-text {
            display: block;
            margin-top: 5px;
            font-weight: 600;
        }

        .btn {
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .alert {
            border-radius: 0.5rem;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card {
            animation: fadeInDown 0.5s ease;
        }
    </style>
@endsection

@section('js')
    <script>
        $(document).ready(function() {
            // Показать/скрыть пароль
            $('#togglePassword').on('click', function() {
                const passwordInput = $('#password');
                const passwordIcon = $('#togglePasswordIcon');
                
                if (passwordInput.attr('type') === 'password') {
                    passwordInput.attr('type', 'text');
                    passwordIcon.removeClass('fa-eye').addClass('fa-eye-slash');
                } else {
                    passwordInput.attr('type', 'password');
                    passwordIcon.removeClass('fa-eye-slash').addClass('fa-eye');
                }
            });

            // Генератор пароля
            $('#generatePassword').on('click', function() {
                const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
                let password = '';
                for (let i = 0; i < 12; i++) {
                    password += chars.charAt(Math.floor(Math.random() * chars.length));
                }
                $('#password').val(password).attr('type', 'text');
                $('#togglePasswordIcon').removeClass('fa-eye').addClass('fa-eye-slash');
                checkPasswordStrength(password);
            });

            // Проверка силы пароля
            $('#password').on('input', function() {
                checkPasswordStrength($(this).val());
            });

            function checkPasswordStrength(password) {
                const strengthIndicator = $('#passwordStrength');
                const progressBar = strengthIndicator.find('.progress-bar');
                const strengthText = strengthIndicator.find('.password-strength-text');

                if (password.length === 0) {
                    strengthIndicator.hide();
                    return;
                }

                strengthIndicator.show();

                let strength = 0;
                
                // Длина
                if (password.length >= 6) strength += 20;
                if (password.length >= 8) strength += 10;
                if (password.length >= 12) strength += 10;
                
                // Содержит строчные буквы
                if (/[a-z]/.test(password)) strength += 15;
                
                // Содержит заглавные буквы
                if (/[A-Z]/.test(password)) strength += 15;
                
                // Содержит цифры
                if (/[0-9]/.test(password)) strength += 15;
                
                // Содержит символы
                if (/[^a-zA-Z0-9]/.test(password)) strength += 15;

                progressBar.css('width', strength + '%');
                
                if (strength < 40) {
                    progressBar.removeClass().addClass('progress-bar bg-danger');
                    strengthText.text('Слабый пароль').css('color', '#dc3545');
                } else if (strength < 70) {
                    progressBar.removeClass().addClass('progress-bar bg-warning');
                    strengthText.text('Средний пароль').css('color', '#ffc107');
                } else {
                    progressBar.removeClass().addClass('progress-bar bg-success');
                    strengthText.text('Надежный пароль').css('color', '#28a745');
                }
            }

            // Валидация формы
            $('#createUserForm').on('submit', function(e) {
                const email = $('#email').val();
                const password = $('#password').val();

                if (!email || !password) {
                    e.preventDefault();
                    alert('Пожалуйста, заполните все обязательные поля');
                    return false;
                }

                if (password.length < 6) {
                    e.preventDefault();
                    alert('Пароль должен содержать минимум 6 символов');
                    return false;
                }
            });
        });
    </script>
@endsection
