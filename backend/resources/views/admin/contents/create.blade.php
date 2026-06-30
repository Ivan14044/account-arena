@extends('adminlte::page')

@section('title', 'Добавить контент')

@section('content_header')
    <h1>Добавить контент</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Данные контента</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.contents.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label for="name">Название</label>
                            <input type="text" name="name" id="name"
                                   class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}">
                            @error('name')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        @csrf
                        <div class="form-group">
                            <label for="code">Код</label>
                            <input type="text" name="code" id="code"
                                   class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}">
                            @error('code')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary">Создать</button>
                        <a href="{{ route('admin.contents.index') }}" class="btn btn-secondary">Отмена</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
