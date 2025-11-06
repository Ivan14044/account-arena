@extends('adminlte::page')

@section('title', 'Редактировать промокод #' . $promocode->id)

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="m-0 font-weight-light">
                    <i class="fas fa-edit text-primary"></i> Редактирование промокода #{{ $promocode->id }}
                </h1>
                <p class="text-muted mb-0 mt-1">Изменение настроек промокода <strong>{{ $promocode->code }}</strong></p>
            </div>
            <div>
                <a href="{{ route('admin.promocodes.index') }}" class="btn btn-outline-secondary btn-modern">
                    <i class="fas fa-arrow-left mr-2"></i>Назад к списку
                </a>
            </div>
        </div>
    </div>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-modern alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-modern alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle mr-2"></i><strong>Ошибка!</strong> Проверьте правильность заполнения полей.
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card card-modern">
                <div class="card-header-modern">
                    <h5 class="mb-0">
                        <i class="fas fa-tag mr-2"></i>Основные данные
                    </h5>
                </div>
                <div class="card-body-modern" style="padding: 1.5rem;">
                    <form method="POST" action="{{ route('admin.promocodes.update', $promocode) }}">
                        @csrf
                        @method('PUT')

                        <div class="form-group-modern">
                            <label for="code" class="form-label-modern">
                                <i class="fas fa-barcode mr-1"></i>Код промокода <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="text" name="code" id="code" class="form-control form-control-modern @error('code') is-invalid @enderror" value="{{ old('code', $promocode->code) }}" placeholder="Например: SALE2024">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary btn-modern" id="generate-code" title="Сгенерировать случайный код">
                                        <i class="fas fa-random"></i>
                                    </button>
                                </div>
                            </div>
                            @error('code')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i> Введите уникальный код или нажмите <i class="fas fa-random"></i> для генерации
                            </small>
                        </div>

                        {{-- Скрытое поле типа - всегда discount для маркетплейса товаров --}}
                        <input type="hidden" name="type" value="discount">

                        <div class="form-group-modern">
                            <label for="percent_discount" class="form-label-modern">
                                <i class="fas fa-percent mr-1"></i>Процент скидки <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="number" min="0" max="100" step="1" name="percent_discount" id="percent_discount" class="form-control form-control-modern @error('percent_discount') is-invalid @enderror" value="{{ old('percent_discount', $promocode->percent_discount) }}" placeholder="10">
                                <div class="input-group-append">
                                    <span class="input-group-text bg-light"><i class="fas fa-percent"></i></span>
                                </div>
                            </div>
                            @error('percent_discount')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i> Укажите процент скидки от 0 до 100
                            </small>
                        </div>

                        @if($promocode->batch_id)
                            <div class="form-group-modern">
                                <label for="prefix" class="form-label-modern">
                                    <i class="fas fa-tag mr-1"></i>Префикс
                                </label>
                                <input type="text" name="prefix" id="prefix" class="form-control form-control-modern @error('prefix') is-invalid @enderror" value="{{ old('prefix', $promocode->prefix) }}" placeholder="Префикс партии">
                                @error('prefix')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle"></i> Префикс для партии промокодов
                                </small>
                            </div>
                        @endif

                        <div class="section-divider mt-4 mb-4">
                            <h5 class="section-title">
                                <i class="fas fa-sliders-h mr-2"></i>Ограничения использования
                            </h5>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group-modern">
                                    <label for="per_user_limit" class="form-label-modern">
                                        <i class="fas fa-user-clock mr-1"></i>Лимит на одного пользователя <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" min="1" step="1" name="per_user_limit" id="per_user_limit" class="form-control form-control-modern @error('per_user_limit') is-invalid @enderror" value="{{ old('per_user_limit', $promocode->per_user_limit ?? 1) }}" placeholder="1">
                                    @error('per_user_limit')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle"></i> Сколько раз один пользователь может использовать промокод
                                    </small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group-modern">
                                    <label for="usage_limit" class="form-label-modern">
                                        <i class="fas fa-chart-line mr-1"></i>Общий лимит использования <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" min="0" step="1" name="usage_limit" id="usage_limit" class="form-control form-control-modern @error('usage_limit') is-invalid @enderror" value="{{ old('usage_limit', $promocode->usage_limit) }}" placeholder="0">
                                    @error('usage_limit')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle"></i> Общее количество использований (0 = безлимит)
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group-modern">
                                    <label for="starts_at" class="form-label-modern">
                                        <i class="fas fa-calendar-check mr-1"></i>Дата начала действия
                                    </label>
                                    <input type="datetime-local" name="starts_at" id="starts_at" class="form-control form-control-modern @error('starts_at') is-invalid @enderror" value="{{ old('starts_at', optional($promocode->starts_at)->format('Y-m-d\TH:i')) }}">
                                    @error('starts_at')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle"></i> Оставьте пустым для немедленного начала
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group-modern">
                                    <label for="expires_at" class="form-label-modern">
                                        <i class="fas fa-calendar-times mr-1"></i>Дата окончания действия
                                    </label>
                                    <input type="datetime-local" name="expires_at" id="expires_at" class="form-control form-control-modern @error('expires_at') is-invalid @enderror" value="{{ old('expires_at', optional($promocode->expires_at)->format('Y-m-d\TH:i')) }}">
                                    @error('expires_at')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle"></i> Оставьте пустым, если промокод бессрочный
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group-modern">
                            <label for="is_active" class="form-label-modern">
                                <i class="fas fa-toggle-on mr-1"></i>Статус <span class="text-danger">*</span>
                            </label>
                            <select name="is_active" id="is_active" class="form-control form-control-modern @error('is_active') is-invalid @enderror">
                                <option value="1" {{ old('is_active', $promocode->is_active) == 1 ? 'selected' : '' }}>
                                    ✅ Активен
                                </option>
                                <option value="0" {{ old('is_active', $promocode->is_active) == 0 ? 'selected' : '' }}>
                                    ⛔ Неактивен
                                </option>
                            </select>
                            @error('is_active')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i> Только активные промокоды могут быть использованы клиентами
                            </small>
                        </div>
                        
                        <div class="form-actions mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-primary btn-modern btn-lg">
                                <i class="fas fa-save mr-2"></i>Сохранить
                            </button>
                            <button type="submit" name="save" class="btn btn-outline-primary btn-modern btn-lg">
                                <i class="fas fa-save mr-2"></i>Сохранить и продолжить
                            </button>
                            <a href="{{ route('admin.promocodes.index') }}" class="btn btn-outline-secondary btn-modern btn-lg">
                                <i class="fas fa-times mr-2"></i>Отмена
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('css')
    @include('admin.layouts.modern-styles')
    
    <style>
        /* ============================================
           ДОПОЛНИТЕЛЬНЫЕ СТИЛИ ДЛЯ РЕДАКТИРОВАНИЯ ПРОМОКОДА
           ============================================ */
        
        /* АНИМАЦИЯ ПЕРЕКЛЮЧЕНИЯ ПОЛЕЙ */
        #discount-field, #services-field {
            transition: all 0.3s ease-in-out;
        }
        
        /* ФОРМА ДЕЙСТВИЙ */
        .form-actions {
            background: #f8f9fc;
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin-left: -1.5rem;
            margin-right: -1.5rem;
            margin-bottom: -1.5rem;
            border-top: 2px solid #e3e6f0;
        }
        
        /* ЧЕКБОКСЫ В ТАБЛИЦЕ СЕРВИСОВ */
        .custom-control-input:checked ~ .custom-control-label::before {
            background-color: #4e73df;
            border-color: #4e73df;
        }
        
        .custom-control-label {
            cursor: pointer;
        }
        
        /* INPUT GROUP */
        .input-group-text {
            background-color: #f8f9fc;
            border: 1px solid #d1d3e2;
            color: #5a6c7d;
            font-weight: 500;
        }
        
        .input-group-append .btn {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }
        
        /* PLACEHOLDER */
        .form-control-modern::placeholder {
            color: #a8b1bd;
            font-style: italic;
        }
        
        /* ТАБЛИЦА СЕРВИСОВ */
        .table-responsive.bg-modern {
            border: 1px solid #e3e6f0;
        }
        
        .table-responsive.bg-modern table {
            margin-bottom: 0;
        }
        
        .table-bordered {
            border-color: #e3e6f0;
        }
        
        .table-bordered th,
        .table-bordered td {
            border-color: #e3e6f0;
        }
        
        .thead-light th {
            background-color: #f8f9fc;
            color: #5a6c7d;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }
        
        /* ПОДСКАЗКИ */
        .form-text.text-muted {
            font-size: 0.8125rem;
            margin-top: 0.5rem;
            color: #858796;
        }
        
        .form-text.text-muted i {
            opacity: 0.8;
        }
        
        /* РАЗДЕЛИТЕЛЬ СЕКЦИЙ */
        .section-divider {
            position: relative;
            margin: 2rem 0;
        }
        
        .section-divider .section-title {
            background: white;
            padding: 0 1rem 0.75rem 0;
            display: inline-block;
            border-bottom: 2px solid #e3e6f0;
            width: 100%;
        }
        
        /* ОШИБКИ ВАЛИДАЦИИ */
        .invalid-feedback {
            font-size: 0.8125rem;
            margin-top: 0.5rem;
        }
        
        .is-invalid {
            border-color: #e74a3b !important;
        }
        
        .is-invalid:focus {
            border-color: #e74a3b !important;
            box-shadow: 0 0 0 0.2rem rgba(231, 74, 59, 0.1) !important;
        }
        
        /* АДАПТИВНОСТЬ */
        @media (max-width: 991px) {
            .col-lg-8 {
                max-width: 100%;
            }
        }
    </style>
@endsection

@section('js')
<script>
    $(function () {
        // ========================================
        // Генерация случайного кода промокода
        // ========================================
        function generateCode(length) {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            let code = '';
            for (let i = 0; i < length; i++) {
                code += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            return code;
        }

        $('#generate-code').on('click', function () {
            const newCode = generateCode(8);
            $('#code').val(newCode);
            
            // Визуальная обратная связь
            $(this).html('<i class="fas fa-check"></i>').addClass('btn-success').removeClass('btn-outline-secondary');
            setTimeout(() => {
                $(this).html('<i class="fas fa-random"></i>').removeClass('btn-success').addClass('btn-outline-secondary');
            }, 1000);
        });

        // ========================================
        // Подтверждение перед отменой
        // ========================================
        $('a[href*="promocodes.index"]').on('click', function(e) {
            const hasData = $('#code').val();
            if (hasData) {
                if (!confirm('Вы уверены, что хотите отменить редактирование промокода? Все несохраненные изменения будут потеряны.')) {
                    e.preventDefault();
                }
            }
        });

        // ========================================
        // Индикатор загрузки при отправке формы
        // ========================================
        $('form').on('submit', function() {
            $(this).find('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Сохранение...');
        });
    });
</script>
@endsection

