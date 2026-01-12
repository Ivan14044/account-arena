@extends('adminlte::page')

@section('title', 'Обработка заказа #' . $purchase->order_number)

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="m-0 font-weight-light">
                    Обработка заказа #{{ $purchase->order_number }}
                </h1>
                <p class="text-muted mb-0 mt-1">Ручная выдача товара</p>
            </div>
            <a href="{{ route('admin.manual-delivery.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-2"></i>Назад к списку
            </a>
        </div>
    </div>
@stop

@section('content')
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <div class="row">
        <!-- Информация о заказе -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-info-circle mr-2"></i>
                        Информация о заказе
                    </h3>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Номер заказа:</dt>
                        <dd class="col-sm-7"><strong>#{{ $purchase->order_number }}</strong></dd>
                        
                        <dt class="col-sm-5">Покупатель:</dt>
                        <dd class="col-sm-7">
                            @if($purchase->user)
                                <div>{{ $purchase->user->name }}</div>
                                <small class="text-muted">{{ $purchase->user->email }}</small>
                            @else
                                <div>Гость</div>
                                <small class="text-muted">{{ $purchase->guest_email }}</small>
                            @endif
                        </dd>
                        
                        <dt class="col-sm-5">Товар:</dt>
                        <dd class="col-sm-7">{{ $purchase->serviceAccount->title ?? 'Товар удален' }}</dd>
                        
                        <dt class="col-sm-5">Количество:</dt>
                        <dd class="col-sm-7"><span class="badge badge-info">{{ $purchase->quantity }} шт.</span></dd>
                        
                        <dt class="col-sm-5">Цена за единицу:</dt>
                        <dd class="col-sm-7">{{ number_format($purchase->price, 2) }} {{ $purchase->transaction->currency ?? 'USD' }}</dd>
                        
                        <dt class="col-sm-5">Общая сумма:</dt>
                        <dd class="col-sm-7"><strong>{{ number_format($purchase->total_amount, 2) }} {{ $purchase->transaction->currency ?? 'USD' }}</strong></dd>
                        
                        <dt class="col-sm-5">Способ оплаты:</dt>
                        <dd class="col-sm-7">{{ $purchase->transaction->payment_method ?? 'Неизвестно' }}</dd>
                        
                        <dt class="col-sm-5">Дата создания:</dt>
                        <dd class="col-sm-7">{{ $purchase->created_at->format('d.m.Y H:i') }}</dd>
                    </dl>
                </div>
            </div>

            @if($purchase->serviceAccount->manual_delivery_instructions)
                <div class="card mt-3">
                    <div class="card-header bg-info">
                        <h3 class="card-title text-white">
                            <i class="fas fa-book mr-2"></i>
                            Инструкции для обработки
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="text-muted">
                            {!! nl2br(e($purchase->serviceAccount->manual_delivery_instructions)) !!}
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Форма обработки -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary">
                    <h3 class="card-title text-white">
                        <i class="fas fa-edit mr-2"></i>
                        Выдача товара
                    </h3>
                </div>
                <form action="{{ route('admin.manual-delivery.process', $purchase) }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label for="account_data">
                                Данные аккаунтов <span class="text-danger">*</span>
                                <small class="text-muted">(Требуется: {{ $purchase->quantity }} {{ $purchase->quantity === 1 ? 'аккаунт' : 'аккаунтов' }})</small>
                            </label>
                            <div id="account-data-container">
                                @for($i = 0; $i < $purchase->quantity; $i++)
                                    <div class="mb-3">
                                        <label class="small text-muted">Аккаунт {{ $i + 1 }}:</label>
                                        <textarea 
                                            name="account_data[]" 
                                            class="form-control @error('account_data.' . $i) is-invalid @enderror" 
                                            rows="3" 
                                            required
                                            placeholder="Введите данные аккаунта (логин, пароль и т.д.)"
                                        >{{ old('account_data.' . $i) }}</textarea>
                                        @error('account_data.' . $i)
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                @endfor
                            </div>
                            @error('account_data')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="processing_notes">Заметки для покупателя</label>
                            <textarea 
                                name="processing_notes" 
                                class="form-control" 
                                rows="3"
                                placeholder="Дополнительная информация для покупателя (необязательно)"
                            >{{ old('processing_notes') }}</textarea>
                            <small class="form-text text-muted">Эти заметки будут видны покупателю в его личном кабинете</small>
                        </div>

                        <div class="form-group">
                            <label for="admin_notes">Внутренние заметки</label>
                            <textarea 
                                name="admin_notes" 
                                class="form-control" 
                                rows="3"
                                placeholder="Внутренние заметки для администраторов (необязательно)"
                            >{{ old('admin_notes', $purchase->admin_notes) }}</textarea>
                            <small class="form-text text-muted">Эти заметки видны только администраторам</small>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-check mr-2"></i>
                            Завершить обработку и выдать товар
                        </button>
                        <a href="{{ route('admin.manual-delivery.index') }}" class="btn btn-secondary">
                            Отмена
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop

@section('js')
<script>
    // Валидация количества аккаунтов
    document.querySelector('form').addEventListener('submit', function(e) {
        const accountData = document.querySelectorAll('textarea[name="account_data[]"]');
        const requiredCount = {{ $purchase->quantity }};
        
        if (accountData.length !== requiredCount) {
            e.preventDefault();
            alert('Количество полей для аккаунтов должно быть равно ' + requiredCount);
            return false;
        }
        
        let emptyCount = 0;
        accountData.forEach(function(textarea) {
            if (!textarea.value.trim()) {
                emptyCount++;
            }
        });
        
        if (emptyCount > 0) {
            e.preventDefault();
            alert('Все поля должны быть заполнены. Заполнено: ' + (requiredCount - emptyCount) + ' из ' + requiredCount);
            return false;
        }
    });
</script>
@stop
