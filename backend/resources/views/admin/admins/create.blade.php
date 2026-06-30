@extends('adminlte::page')

@section('title', 'Добавить администратора')

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <h1 class="m-0 font-weight-light">Добавить администратора</h1>
                <p class="text-muted mb-0 mt-1 d-none d-md-block">Создание новой учётной записи администратора</p>
            </div>
            <div class="w-100 w-md-auto">
                <a href="{{ route('admin.admins.index') }}" class="btn btn-secondary btn-modern w-100 w-md-auto">
                    <i class="fas fa-arrow-left mr-2"></i>Назад к списку
                </a>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Данные администратора</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.admins.store') }}">
                        @csrf
                        <div class="form-group">
                            <label for="name">Имя</label>
                            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}">
                            @error('name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}">
                            @error('email')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="is_blocked">Статус</label>
                            <select name="is_blocked" id="is_blocked" class="form-control @error('is_blocked') is-invalid @enderror">
                                <option value="0" {{ old('is_blocked') == 0 ? 'selected' : '' }}>Активен</option>
                                <option value="1" {{ old('is_blocked') == 1 ? 'selected' : '' }}>Заблокирован</option>
                            </select>
                            @error('is_blocked')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="password">Новый пароль</label>
                            <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror">
                            @error('password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="password_confirmation">Подтвердите пароль</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control">
                        </div>

                        <div class="d-flex flex-column flex-sm-row gap-2 mt-4">
                            <button type="submit" class="btn btn-primary btn-modern">
                                <i class="fas fa-save mr-2"></i>Создать
                            </button>
                            <a href="{{ route('admin.admins.index') }}" class="btn btn-secondary btn-modern">Отмена</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('css')
    @include('admin.layouts.modern-styles')
@endsection
