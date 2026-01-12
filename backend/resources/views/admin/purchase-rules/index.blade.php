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
        <div class="alert alert-modern alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.purchase-rules.store') }}">
        @csrf

        <div class="card card-modern mb-4">
            <div class="card-header-modern">
                <h5 class="mb-0 font-weight-normal">
                    <i class="fas fa-cog mr-2 text-primary"></i>Общие настройки
                </h5>
            </div>
            <div class="card-body">
                <div class="form-group mb-0">
                    <div class="custom-control custom-switch custom-switch-lg">
                        <input 
                            type="checkbox" 
                            class="custom-control-input" 
                            id="purchase_rules_enabled" 
                            name="purchase_rules_enabled" 
                            value="1"
                            {{ $rules_enabled ? 'checked' : '' }}
                        >
                        <label class="custom-control-label font-weight-bold" for="purchase_rules_enabled" style="padding-top: 2px; cursor: pointer;">
                            Включить обязательное согласие с правилами при оформлении заказа
                        </label>
                    </div>
                    <div class="mt-2 ml-4 pl-3 border-left">
                        <small class="text-muted">
                            <i class="fas fa-info-circle mr-1 text-info"></i>
                            Если включено, пользователь должен будет подтвердить свое согласие с данными правилами перед завершением покупки.
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-modern">
            <div class="card-header-modern p-0 border-0">
                <ul class="nav nav-tabs-modern" id="rules-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="tab-ru" data-toggle="pill" href="#content-ru" role="tab">
                            <span class="flag-icon flag-icon-ru mr-2 shadow-sm rounded-sm"></span>
                            Русский
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-en" data-toggle="pill" href="#content-en" role="tab">
                            <span class="flag-icon flag-icon-us mr-2 shadow-sm rounded-sm"></span>
                            English
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-uk" data-toggle="pill" href="#content-uk" role="tab">
                            <span class="flag-icon flag-icon-ua mr-2 shadow-sm rounded-sm"></span>
                            Українська
                        </a>
                    </li>
                </ul>
            </div>

            <div class="card-body-modern p-4">
                <div class="tab-content" id="rules-content">
                    <!-- Русский -->
                    <div class="tab-pane fade show active" id="content-ru" role="tabpanel">
                        <div class="form-group mb-0">
                            <label for="purchase_rules_ru" class="form-label d-flex align-items-center mb-3">
                                <i class="fas fa-align-left mr-2 text-primary"></i>
                                Текст правил на русском языке
                            </label>
                            <textarea 
                                class="form-control @error('purchase_rules_ru') is-invalid @enderror" 
                                id="purchase_rules_ru" 
                                name="purchase_rules_ru" 
                                rows="15"
                                placeholder="Введите правила покупки на русском языке..."
                                style="font-size: 0.95rem; line-height: 1.6; resize: vertical;"
                            >{{ old('purchase_rules_ru', $rules_ru) }}</textarea>
                            @error('purchase_rules_ru')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="mt-3">
                                <small class="text-muted bg-light p-2 rounded d-inline-block">
                                    <i class="fas fa-code mr-1"></i> Можно использовать HTML-теги для оформления текста
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- English -->
                    <div class="tab-pane fade" id="content-en" role="tabpanel">
                        <div class="form-group mb-0">
                            <label for="purchase_rules_en" class="form-label d-flex align-items-center mb-3">
                                <i class="fas fa-align-left mr-2 text-primary"></i>
                                Rules Text (English)
                            </label>
                            <textarea 
                                class="form-control @error('purchase_rules_en') is-invalid @enderror" 
                                id="purchase_rules_en" 
                                name="purchase_rules_en" 
                                rows="15"
                                placeholder="Enter purchase rules in English..."
                                style="font-size: 0.95rem; line-height: 1.6; resize: vertical;"
                            >{{ old('purchase_rules_en', $rules_en) }}</textarea>
                            @error('purchase_rules_en')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="mt-3">
                                <small class="text-muted bg-light p-2 rounded d-inline-block">
                                    <i class="fas fa-code mr-1"></i> HTML tags are allowed for text formatting
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Українська -->
                    <div class="tab-pane fade" id="content-uk" role="tabpanel">
                        <div class="form-group mb-0">
                            <label for="purchase_rules_uk" class="form-label d-flex align-items-center mb-3">
                                <i class="fas fa-align-left mr-2 text-primary"></i>
                                Текст правил українською мовою
                            </label>
                            <textarea 
                                class="form-control @error('purchase_rules_uk') is-invalid @enderror" 
                                id="purchase_rules_uk" 
                                name="purchase_rules_uk" 
                                rows="15"
                                placeholder="Введіть правила покупки українською мовою..."
                                style="font-size: 0.95rem; line-height: 1.6; resize: vertical;"
                            >{{ old('purchase_rules_uk', $rules_uk) }}</textarea>
                            @error('purchase_rules_uk')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="mt-3">
                                <small class="text-muted bg-light p-2 rounded d-inline-block">
                                    <i class="fas fa-code mr-1"></i> Можна використовувати HTML-теги для оформлення тексту
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer bg-white border-top p-4">
                <div class="d-flex align-items-center">
                    <button type="submit" class="btn btn-primary btn-modern px-5 shadow-sm">
                        <i class="fas fa-save mr-2"></i>Сохранить правила
                    </button>
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-link text-muted ml-3">
                        <i class="fas fa-times mr-1"></i> Отмена
                    </a>
                </div>
            </div>
        </div>
    </form>
@stop

@section('css')
    @include('admin.layouts.modern-styles')
    <style>
        .custom-switch-lg .custom-control-label::before {
            height: 1.5rem;
            width: 2.75rem;
            border-radius: 1.5rem;
        }
        .custom-switch-lg .custom-control-label::after {
            width: calc(1.5rem - 4px);
            height: calc(1.5rem - 4px);
            border-radius: 1.5rem;
        }
        .custom-switch-lg .custom-control-input:checked ~ .custom-control-label::after {
            transform: translateX(1.25rem);
        }
    </style>
@stop
