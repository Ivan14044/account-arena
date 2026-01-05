@extends('adminlte::page')

@section('title', 'Создать запрос на вывод')

@section('content_header')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
        <h1 class="mb-2 mb-md-0">Создать запрос на вывод</h1>
        <div class="d-flex flex-column flex-sm-row w-100 w-md-auto">
            <a href="{{ route('supplier.withdrawals.index') }}" class="btn btn-secondary mb-2 mb-sm-0">
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

    @php
        // Безопасные fallback'ы — контроллер должен передать $availableAmount и $heldAmount
        $available = (float)($availableAmount ?? 0);
        $held = (float)($heldAmount ?? 0);
        // max в input должен быть в формате с точкой для HTML
        $maxAvailableForInput = number_format($available, 2, '.', '');
    @endphp

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Информация о выводе</h3>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>Доступный баланс:</strong> {{ number_format($available, 2) }} $
                <br>
                <small class="text-muted">Средства, которые вы можете запросить на вывод</small>
            </div>

            <div class="mb-3">
                <div class="info-box">
                    <div class="info-box-content p-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>В холде</strong>
                                <div class="text-muted">Ожидают окончания холд-периода</div>
                            </div>
                            <div class="text-right">
                                <span class="h4 mb-0">{{ number_format($held, 2) }} $</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if($available <= 0)
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    На данный момент у вас нет доступных средств для вывода. Дождитесь окончания холда или обратитесь к администратору.
                </div>
            @endif

            <form action="{{ route('supplier.withdrawals.store') }}" method="POST">
                @csrf

                <div class="form-group">
                    <label for="amount">Сумма вывода ($)</label>
                    <input type="number"
                           step="0.01"
                           min="1"
                           max="{{ $maxAvailableForInput }}"
                           name="amount"
                           id="amount"
                           class="form-control @error('amount') is-invalid @enderror"
                           value="{{ old('amount') }}"
                           @if($available <= 0) disabled @endif
                           required>
                    @error('amount')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">
                        Максимум: {{ number_format($available, 2) }} $
                    </small>
                </div>

                <div class="form-group">
                    <label for="payment_method">Способ вывода</label>
                    <select name="payment_method" id="payment_method" class="form-control @error('payment_method') is-invalid @enderror" required @if($available <= 0) disabled @endif>
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

                <div class="d-flex flex-column flex-sm-row">
                    <button type="submit" class="btn btn-success mb-2 mb-sm-0 mr-sm-2" @if($available <= 0) disabled title="Нет доступных средств" @endif>
                        <i class="fas fa-check"></i> Создать запрос
                    </button>
                    <a href="{{ route('supplier.withdrawals.index') }}" class="btn btn-secondary mb-2 mb-sm-0">
                        <i class="fas fa-times"></i> Отмена
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
