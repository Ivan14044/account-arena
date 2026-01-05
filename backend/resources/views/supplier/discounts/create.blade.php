@extends('adminlte::page')

@section('title', 'Добавить скидку')

@section('content_header')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
        <h1 class="mb-2 mb-md-0">Добавить скидку</h1>
        <div class="d-flex flex-column flex-sm-row w-100 w-md-auto">
            <a href="{{ route('supplier.discounts.index') }}" class="btn btn-secondary mb-2 mb-sm-0">
                <i class="fas fa-arrow-left"></i> Назад
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Информация о скидке</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('supplier.discounts.store') }}">
                @csrf

                <div class="form-group">
                    <label for="product_id">Выберите товар *</label>
                    <select name="product_id" id="product_id" class="form-control @error('product_id') is-invalid @enderror" required>
                        <option value="">-- Выберите товар --</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                {{ $product->title }} ({{ number_format($product->price, 2) }} $)
                            </option>
                        @endforeach
                    </select>
                    @error('product_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="discount_percent">Размер скидки (%) *</label>
                    <input type="number" name="discount_percent" id="discount_percent" min="1" max="99" step="0.01"
                           class="form-control @error('discount_percent') is-invalid @enderror"
                           value="{{ old('discount_percent') }}" required>
                    @error('discount_percent')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">От 1% до 99%</small>
                </div>

                <div class="row">
                    <div class="col-md-6 col-12 mb-2">
                        <div class="form-group">
                            <label for="discount_start_date">Дата начала (необязательно)</label>
                            <input type="datetime-local" name="discount_start_date" id="discount_start_date"
                                   class="form-control @error('discount_start_date') is-invalid @enderror"
                                   value="{{ old('discount_start_date') }}">
                            @error('discount_start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Оставьте пустым для немедленного старта</small>
                        </div>
                    </div>
                    <div class="col-md-6 col-12 mb-2">
                        <div class="form-group">
                            <label for="discount_end_date">Дата окончания (необязательно)</label>
                            <input type="datetime-local" name="discount_end_date" id="discount_end_date"
                                   class="form-control @error('discount_end_date') is-invalid @enderror"
                                   value="{{ old('discount_end_date') }}">
                            @error('discount_end_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Оставьте пустым для бессрочной скидки</small>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="d-flex flex-column flex-sm-row">
                    <button type="submit" class="btn btn-primary mb-2 mb-sm-0 mr-sm-2">
                        <i class="fas fa-save"></i> Создать скидку
                    </button>
                    <a href="{{ route('supplier.discounts.index') }}" class="btn btn-secondary mb-2 mb-sm-0">
                        <i class="fas fa-times"></i> Отмена
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-info">
            <h3 class="card-title">💡 Подсказка</h3>
        </div>
        <div class="card-body">
            <ul class="mb-0">
                <li>Скидка будет автоматически применена к цене товара на сайте</li>
                <li>Вы можете установить временные рамки действия скидки</li>
                <li>Скидка до 99% от базовой цены</li>
                <li>После создания скидки вы сможете её редактировать или удалить</li>
            </ul>
        </div>
    </div>
@endsection

