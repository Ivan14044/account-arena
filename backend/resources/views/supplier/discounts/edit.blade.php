@extends('adminlte::page')

@section('title', 'Редактировать скидку')

@section('content_header')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
        <h1 class="mb-2 mb-md-0">Редактировать скидку</h1>
        <div class="d-flex flex-column flex-sm-row gap-2 w-100 w-md-auto">
            <a href="{{ route('supplier.discounts.index') }}" class="btn btn-secondary">
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
            <form method="POST" action="{{ route('supplier.discounts.update', $discount) }}">
                @csrf
                @method('PUT')

                <div class="alert alert-info">
                    <strong>Товар:</strong> {{ $discount->title }}<br>
                    <strong>Базовая цена:</strong> {{ number_format($discount->price, 2) }} $
                </div>

                <div class="form-group">
                    <label for="discount_percent">Размер скидки (%) *</label>
                    <input type="number" name="discount_percent" id="discount_percent" min="0" max="99" step="0.01"
                           class="form-control @error('discount_percent') is-invalid @enderror"
                           value="{{ old('discount_percent', $discount->discount_percent) }}" required>
                    @error('discount_percent')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">От 0% до 99% (0% = удалить скидку)</small>
                </div>

                <div id="pricePreview" class="alert alert-success">
                    <strong>Цена со скидкой:</strong> <span id="discountedPrice">{{ number_format($discount->getCurrentPrice(), 2) }} $</span>
                </div>

                <div class="row">
                    <div class="col-md-6 col-12 mb-2">
                        <div class="form-group">
                            <label for="discount_start_date">Дата начала (необязательно)</label>
                            <input type="datetime-local" name="discount_start_date" id="discount_start_date"
                                   class="form-control @error('discount_start_date') is-invalid @enderror"
                                   value="{{ old('discount_start_date', $discount->discount_start_date ? $discount->discount_start_date->format('Y-m-d\TH:i') : '') }}">
                            @error('discount_start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6 col-12 mb-2">
                        <div class="form-group">
                            <label for="discount_end_date">Дата окончания (необязательно)</label>
                            <input type="datetime-local" name="discount_end_date" id="discount_end_date"
                                   class="form-control @error('discount_end_date') is-invalid @enderror"
                                   value="{{ old('discount_end_date', $discount->discount_end_date ? $discount->discount_end_date->format('Y-m-d\TH:i') : '') }}">
                            @error('discount_end_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <hr>

                <div class="d-flex flex-column flex-sm-row gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Сохранить изменения
                    </button>
                    <a href="{{ route('supplier.discounts.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Отмена
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const basePrice = {{ $discount->price }};
    const discountInput = document.getElementById('discount_percent');
    const discountedPriceSpan = document.getElementById('discountedPrice');

    discountInput.addEventListener('input', function() {
        const discountPercent = parseFloat(this.value) || 0;
        const discount = (basePrice * discountPercent) / 100;
        const finalPrice = basePrice - discount;
        discountedPriceSpan.textContent = finalPrice.toFixed(2) + ' $';
    });
});
</script>
@endsection

