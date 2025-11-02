@extends('adminlte::page')

@section('title', 'Редактировать ваучер #' . $voucher->id)

@section('content_header')
    <h1>Редактировать ваучер #{{ $voucher->id }}</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Данные ваучера</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.vouchers.update', $voucher) }}">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label for="code">Код</label>
                            <div class="input-group">
                                <input type="text" name="code" id="code"
                                       class="form-control @error('code') is-invalid @enderror"
                                       value="{{ old('code', $voucher->code) }}" required>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-secondary" id="generate-code" title="Сгенерировать">🎲</button>
                                </div>
                            </div>
                            @error('code')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="amount">Сумма</label>
                            <input type="number" step="0.01" min="0.01" name="amount" id="amount"
                                   class="form-control @error('amount') is-invalid @enderror"
                                   value="{{ old('amount', $voucher->amount) }}" required>
                            @error('amount')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="currency">Валюта</label>
                            <select name="currency" id="currency"
                                    class="form-control @error('currency') is-invalid @enderror" required>
                                <option value="USD" {{ old('currency', $voucher->currency) == 'USD' ? 'selected' : '' }}>USD</option>
                                <option value="EUR" {{ old('currency', $voucher->currency) == 'EUR' ? 'selected' : '' }}>EUR</option>
                                <option value="UAH" {{ old('currency', $voucher->currency) == 'UAH' ? 'selected' : '' }}>UAH</option>
                                <option value="RUB" {{ old('currency', $voucher->currency) == 'RUB' ? 'selected' : '' }}>RUB</option>
                            </select>
                            @error('currency')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="is_active">Активен</label>
                            <select name="is_active" id="is_active"
                                    class="form-control @error('is_active') is-invalid @enderror">
                                <option value="1" {{ old('is_active', $voucher->is_active) == 1 ? 'selected' : '' }}>Да</option>
                                <option value="0" {{ old('is_active', $voucher->is_active) == 0 ? 'selected' : '' }}>Нет</option>
                            </select>
                            @error('is_active')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="note">Примечание</label>
                            <textarea name="note" id="note" rows="3"
                                      class="form-control @error('note') is-invalid @enderror">{{ old('note', $voucher->note) }}</textarea>
                            @error('note')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        @if($voucher->user)
                        <div class="alert alert-info">
                            <strong>Использован пользователем:</strong><br>
                            <a href="{{ route('admin.users.edit', $voucher->user) }}">
                                {{ $voucher->user->name }} ({{ $voucher->user->email }})
                            </a><br>
                            <small>Дата использования: {{ $voucher->used_at->format('Y-m-d H:i') }}</small>
                        </div>
                        @endif

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Сохранить изменения
                        </button>
                        <a href="{{ route('admin.vouchers.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Отмена
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        document.getElementById('generate-code').addEventListener('click', function() {
            const chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            let code = '';
            for (let i = 0; i < 12; i++) {
                code += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            document.getElementById('code').value = code;
        });
    </script>
@endsection

