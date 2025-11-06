@extends('adminlte::page')

@section('title', '')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1></h1>
        <a href="{{ route('admin.suppliers.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i>Назад</a>
    </div>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ session('success') }}
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Ссылка на поддержку в Telegram</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.suppliers.settings.update') }}" method="POST">
                @csrf
                
                <div class="form-group">
                    <label for="telegram_support_link">
                        <i class="fab fa-telegram"></i> Ссылка на Telegram
                    </label>
                    <input type="url" 
                           name="telegram_support_link" 
                           id="telegram_support_link" 
                           class="form-control @error('telegram_support_link') is-invalid @enderror"
                           value="{{ old('telegram_support_link', $telegramSupportLink) }}"
                           required
                           placeholder="https://t.me/your_support">
                    @error('telegram_support_link')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">
                        Эта ссылка будет показана поставщикам, если их способ оплаты не поддерживается
                    </small>
                </div>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Информация:</strong> Поставщики увидят эту ссылку на странице вывода средств с предложением связаться с администратором, если их способ оплаты не поддерживается.
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>Сохранить</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Предпросмотр</h3>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                Если вашего способа оплаты нет в списке, пожалуйста, свяжитесь с администратором: 
                <a href="{{ $telegramSupportLink }}" target="_blank" class="alert-link">
                    <i class="fab fa-telegram"></i> Написать в Telegram
                </a>
            </div>
        </div>
    </div>
@endsection


