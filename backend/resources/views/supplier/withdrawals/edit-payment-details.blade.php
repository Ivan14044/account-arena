@extends('adminlte::page')

@section('title', 'Редактировать реквизиты')

@section('content_header')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
        <h1 class="mb-2 mb-md-0">Редактировать реквизиты для вывода</h1>
        <div class="d-flex flex-column flex-sm-row gap-2 w-100 w-md-auto">
            <a href="{{ route('supplier.withdrawals.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Назад
            </a>
        </div>
    </div>
@endsection

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ session('success') }}
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Реквизиты для вывода средств</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('supplier.withdrawals.payment-details.update') }}" method="POST">
                @csrf

                <div class="form-group">
                    <label for="trc20_wallet">
                        <i class="fas fa-coins"></i> TRC-20 кошелек
                    </label>
                    <input type="text"
                           name="trc20_wallet"
                           id="trc20_wallet"
                           class="form-control @error('trc20_wallet') is-invalid @enderror"
                           value="{{ old('trc20_wallet', $supplier->trc20_wallet) }}"
                           placeholder="Введите адрес TRC-20 кошелька">
                    @error('trc20_wallet')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">
                        Укажите ваш кошелек USDT TRC-20 для вывода средств в криптовалюте
                    </small>
                </div>

                <div class="form-group">
                    <label for="card_number_uah">
                        <i class="fas fa-credit-card"></i> Карта (грн)
                    </label>
                    <input type="text"
                           name="card_number_uah"
                           id="card_number_uah"
                           class="form-control @error('card_number_uah') is-invalid @enderror"
                           value="{{ old('card_number_uah', $supplier->card_number_uah) }}"
                           placeholder="Введите номер карты">
                    @error('card_number_uah')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">
                        Укажите номер карты (16 цифр) для вывода средств в гривнах
                    </small>
                </div>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Важно:</strong> Укажите хотя бы один способ вывода средств. Вы можете указать оба способа и выбирать при создании запроса на вывод.
                </div>

                <div class="d-flex flex-column flex-sm-row gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Сохранить
                    </button>
                    <a href="{{ route('supplier.withdrawals.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Отмена
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection




