@extends('adminlte::page')

@section('title', 'Правила покупки')

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="m-0 font-weight-light">
                    Правила покупки
                </h1>
                <p class="text-muted mb-0 mt-1">Настройка правил и условий покупки для пользователей</p>
            </div>
        </div>
    </div>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.purchase-rules.store') }}">
        @csrf

        <div class="card card-modern shadow-sm">
            <div class="card-header-modern">
                <h5 class="mb-0 font-weight-normal">
                    <i class="fas fa-cog mr-2"></i>Настройки правил
                </h5>
            </div>
            <div class="card-body">
                <!-- Включить/Выключить правила -->
                <div class="form-group">
                    <div class="custom-control custom-switch">
                        <input 
                            type="checkbox" 
                            class="custom-control-input" 
                            id="purchase_rules_enabled" 
                            name="purchase_rules_enabled" 
                            value="1"
                            {{ $rules_enabled ? 'checked' : '' }}
                        >
                        <label class="custom-control-label" for="purchase_rules_enabled">
                            <strong>Включить обязательное согласие с правилами при оформлении заказа</strong>
                        </label>
                    </div>
                    <small class="form-text text-muted">
                        <i class="fas fa-info-circle mr-1"></i>
                        Если включено, пользователь должен будет поставить галочку согласия перед оформлением заказа
                    </small>
                </div>
            </div>
        </div>

        <!-- Вкладки языков -->
        <div class="card card-modern shadow-sm mt-4">
            <div class="card-header-modern p-0 border-0">
                <ul class="nav nav-tabs-modern" id="rules-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="tab-ru" data-toggle="pill" href="#content-ru" role="tab">
                            <img src="/img/lang/ru.svg" alt="RU" class="mr-2" style="width: 20px; height: 15px;">
                            Русский
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-en" data-toggle="pill" href="#content-en" role="tab">
                            <img src="/img/lang/en.svg" alt="EN" class="mr-2" style="width: 20px; height: 15px;">
                            English
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-uk" data-toggle="pill" href="#content-uk" role="tab">
                            <img src="/img/lang/uk.svg" alt="UK" class="mr-2" style="width: 20px; height: 15px;">
                            Українська
                        </a>
                    </li>
                </ul>
            </div>

            <div class="card-body">
                <div class="tab-content" id="rules-content">
                    <!-- Русский -->
                    <div class="tab-pane fade show active" id="content-ru" role="tabpanel">
                        <div class="form-group">
                            <label for="purchase_rules_ru">
                                <i class="fas fa-align-left mr-1"></i>
                                Текст правил (Русский)
                            </label>
                            <textarea 
                                class="form-control @error('purchase_rules_ru') is-invalid @enderror" 
                                id="purchase_rules_ru" 
                                name="purchase_rules_ru" 
                                rows="12"
                                placeholder="Введите правила покупки на русском языке..."
                            >{{ old('purchase_rules_ru', $rules_ru) }}</textarea>
                            @error('purchase_rules_ru')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                <i class="fas fa-lightbulb mr-1"></i>
                                Используйте Markdown или HTML для форматирования текста
                            </small>
                        </div>
                    </div>

                    <!-- English -->
                    <div class="tab-pane fade" id="content-en" role="tabpanel">
                        <div class="form-group">
                            <label for="purchase_rules_en">
                                <i class="fas fa-align-left mr-1"></i>
                                Rules Text (English)
                            </label>
                            <textarea 
                                class="form-control @error('purchase_rules_en') is-invalid @enderror" 
                                id="purchase_rules_en" 
                                name="purchase_rules_en" 
                                rows="12"
                                placeholder="Enter purchase rules in English..."
                            >{{ old('purchase_rules_en', $rules_en) }}</textarea>
                            @error('purchase_rules_en')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                <i class="fas fa-lightbulb mr-1"></i>
                                Use Markdown or HTML for text formatting
                            </small>
                        </div>
                    </div>

                    <!-- Українська -->
                    <div class="tab-pane fade" id="content-uk" role="tabpanel">
                        <div class="form-group">
                            <label for="purchase_rules_uk">
                                <i class="fas fa-align-left mr-1"></i>
                                Текст правил (Українська)
                            </label>
                            <textarea 
                                class="form-control @error('purchase_rules_uk') is-invalid @enderror" 
                                id="purchase_rules_uk" 
                                name="purchase_rules_uk" 
                                rows="12"
                                placeholder="Введіть правила покупки українською мовою..."
                            >{{ old('purchase_rules_uk', $rules_uk) }}</textarea>
                            @error('purchase_rules_uk')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                <i class="fas fa-lightbulb mr-1"></i>
                                Використовуйте Markdown або HTML для форматування тексту
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer bg-light">
                <button type="submit" class="btn btn-primary btn-modern px-4">
                    <i class="fas fa-save mr-2"></i>Сохранить изменения
                </button>
                <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-modern ml-2">
                    <i class="fas fa-times mr-2"></i>Отмена
                </a>
            </div>
        </div>
    </form>
@stop

@section('css')
    <style>
        /* ============================================
           ЕДИНЫЙ ДИЗАЙН АДМИН-ПАНЕЛИ
           ============================================ */

        /* ЗАГОЛОВОК СТРАНИЦЫ */
        .content-header-modern h1 {
            font-size: 1.75rem;
            color: #2c3e50;
            letter-spacing: -0.5px;
        }

        /* КНОПКИ */
        .btn-modern {
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            letter-spacing: 0.3px;
            border-radius: 0.375rem;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            transition: all 0.2s ease;
        }
        
        .btn-modern:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        /* КАРТОЧКИ */
        .card-modern {
            border: 1px solid #e3e6f0;
            border-radius: 0.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        .card-header-modern {
            background: white;
            border-bottom: 2px solid #e3e6f0;
            padding: 1.25rem 1.5rem;
        }
        
        .card-header-modern h5 {
            color: #2c3e50;
            font-weight: 500;
        }

        /* ВКЛАДКИ */
        .nav-tabs-modern {
            border-bottom: 2px solid #e3e6f0;
        }

        .nav-tabs-modern .nav-link {
            border: none;
            border-bottom: 3px solid transparent;
            padding: 1rem 1.5rem;
            color: #6c757d;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .nav-tabs-modern .nav-link:hover {
            border-bottom-color: #4e73df;
            color: #4e73df;
        }

        .nav-tabs-modern .nav-link.active {
            color: #4e73df;
            border-bottom-color: #4e73df;
            background: transparent;
        }

        /* ALERTS */
        .alert {
            border: none;
            border-left: 4px solid;
            border-radius: 0.375rem;
        }
        
        .alert-success {
            background: #d1fae5;
            border-left-color: #10b981;
            color: #065f46;
        }

        /* ПОЛЯ ВВОДА */
        textarea.form-control {
            border-radius: 0.375rem;
            border: 1px solid #e3e6f0;
            transition: all 0.3s ease;
        }

        textarea.form-control:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }

        /* ТИПОГРАФИКА */
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            color: #2c3e50;
        }
        
        .font-weight-light {
            font-weight: 300 !important;
        }

        /* ДОПОЛНИТЕЛЬНЫЕ СТИЛИ */
        .text-muted {
            color: #858796 !important;
        }
        
        .shadow-sm {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
        }

        .alert-info {
            background: #dbeafe;
            border-left-color: #3b82f6;
            color: #1e40af;
        }
    </style>
@stop

