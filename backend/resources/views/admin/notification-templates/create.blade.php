@extends('adminlte::page')

@section('title', 'Создать шаблон уведомления')

@section('content_header')
    <h1>Создать шаблон уведомления</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-12">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Данные шаблона</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.notification-templates.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label for="code">Код <span class="text-danger">*</span></label>
                            <input type="text" name="code" id="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" placeholder="например, custom_notification">
                            <small class="form-text text-muted">Уникальный код шаблона (строчные буквы, допускаются подчеркивания)</small>
                            @error('code')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="name">Название <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}">
                            @error('name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" name="is_mass" id="is_mass" value="1" class="form-check-input" {{ old('is_mass') ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_mass">
                                    Шаблон для массовых уведомлений
                                </label>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header no-border border-0 p-0">
                                <ul class="nav nav-tabs" id="custom-tabs-one-tab" role="tablist">
                                    @foreach(config('langs') as $code => $flag)
                                        @php($hasError = $errors->has('title.' . $code) || $errors->has('message.' . $code))
                                        <li class="nav-item">
                                            <a class="nav-link @if($hasError) text-danger @endif {{ $code == 'ru' ? 'active' : null }}"
                                               id="tab_{{ $code }}" data-toggle="pill" href="#tab_message_{{ $code }}" role="tab">
                                                <span class="flag-icon flag-icon-{{ $flag }} mr-1"></span> {{ strtoupper($code) }}  @if($hasError)*@endif
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                            <div class="card-body">
                                <div class="tab-content">
                                    @foreach(config('langs') as $code => $flag)
                                        <div class="tab-pane fade show {{ $code == 'ru' ? 'active' : null }}" id="tab_message_{{ $code }}" role="tabpanel">
                                            <div class="form-group">
                                                <label for="title_{{ $code }}">Заголовок</label>
                                                <input type="text" name="title[{{ $code }}]" id="title_{{ $code }}"
                                                       class="form-control @error('title.' . $code) is-invalid @enderror"
                                                       value="{{ old('title.' . $code) }}">
                                                @error('title.' . $code)
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="form-group">
                                                <label for="message_{{ $code }}">Сообщение</label>
                                                <div class="alert alert-info">
                                                    Вы можете использовать переменные в формате <code>:variable_name</code> (например, <code>:order_number</code>, <code>:product_name</code>)
                                                </div>
                                                <textarea style="height: 210px"
                                                          name="message[{{ $code }}]"
                                                          class="ckeditor form-control @error('message.' . $code) is-invalid @enderror"
                                                          id="message_{{ $code }}">{{ old('message.' . $code) }}</textarea>
                                                @error('message.' . $code)
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Создать</button>
                        <button type="submit" name="save" class="btn btn-primary">Создать и продолжить</button>
                        <a href="{{ route('admin.notification-templates.index', ['type' => 'custom']) }}" class="btn btn-secondary">Отмена</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        document.querySelectorAll('.ckeditor').forEach(function (textarea) {
            ClassicEditor
                .create(textarea)
                .then(editor => {
                    editor.editing.view.change(writer => {
                        writer.setStyle('height', '500px', editor.editing.view.document.getRoot());
                    });
                })
                .catch(error => {
                    console.error(error);
                });
        });
    </script>
@endsection

