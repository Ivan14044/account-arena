@extends('adminlte::page')

@section('title', 'Создать запрос на вывод')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Создать запрос на вывод</h1>
        <div>
            <a href="{{ route('supplier.withdrawals.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Назад
            </a>
        </div>
    </div>
@endsection

@section('content')
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ session('error') }}
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Информация о выводе</h3>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>Доступный баланс:</strong> {{ number_format($supplier->supplier_balance, 2) }} $
            </div>

            <form action="{{ route('supplier.withdrawals.store') }}" method="POST">
                @csrf
                
                <div class="form-group">
                    <label for="amount">Сумма вывода ($)</label>
                    <input type="number" 
                           step="0.01" 
                           min="1" 
                           max="{{ $supplier->supplier_balance }}"
                           name="amount" 
                           id="amount" 
                           class="form-control @error('amount') is-invalid @enderror"
                           value="{{ old('amount') }}"
                           required>
                    @error('amount')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">
                        Максимум: {{ number_format($supplier->supplier_balance, 2) }} $
                    </small>
                </div>

                <div class="form-group">
                    <label for="payment_method">Способ вывода</label>
                    <select name="payment_method" id="payment_method" class="form-control @error('payment_method') is-invalid @enderror" required>
                        <option value="">Выберите способ</option>
                        @if($supplier->trc20_wallet)
                            <option value="trc20" {{ old('payment_method') == 'trc20' ? 'selected' : '' }}>
                                TRC-20 ({{ $supplier->trc20_wallet }})
                            </option>
                        @endif
                        @if($supplier->card_number_uah)
                            <option value="card_uah" {{ old('payment_method') == 'card_uah' ? 'selected' : '' }}>
                                Карта грн ({{ $supplier->card_number_uah }})
                            </option>
                        @endif
                    </select>
                    @error('payment_method')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    Убедитесь, что указанные реквизиты верны. После создания запроса изменить их будет невозможно.
                </div>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    Если вашего способа оплаты нет в списке, пожалуйста, свяжитесь с администратором: 
                    <a href="{{ $telegramSupportLink }}" target="_blank" class="alert-link">
                        <i class="fab fa-telegram"></i> Написать в Telegram
                    </a>
                </div>

                <button type="submit" class="btn btn-success">
                    <i class="fas fa-check"></i> Создать запрос
                </button>
                <a href="{{ route('supplier.withdrawals.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Отмена
                </a>
            </form>
        </div>
    </div>
@endsection

