@extends('adminlte::page')

@section('title', 'Редактировать email-шаблон #' . $emailTemplate->id)

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <h1 class="m-0 font-weight-light">Редактировать email-шаблон #{{ $emailTemplate->id }}</h1>
                <p class="text-muted mb-0 mt-1 d-none d-md-block">Изменение шаблона письма и переводов</p>
            </div>
            <div class="w-100 w-md-auto">
                <a href="{{ route('admin.email-templates.index') }}" class="btn btn-secondary btn-modern w-100 w-md-auto">
                    <i class="fas fa-arrow-left mr-2"></i>Назад к списку
                </a>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-12">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Данные шаблона</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.email-templates.update', $emailTemplate) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="form-group">
                            <label for="name">Название</label>
                            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $emailTemplate->name) }}">
                            @error('name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="slug">Код</label>
                            <input type="text" id="code" readonly class="form-control" value="{{ $emailTemplate->code }}">
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
                                                       value="{{ old('title.' . $code, $emailTemplateData[$code]['title'] ?? null) }}">
                                                @error('title.' . $code)
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="form-group">
                                                <label for="message_{{ $code }}">Текст</label>
                                                @if ($emailTemplate->code === 'payment_confirmation')
                                                    <div class="alert alert-info">
                                                        Доступные переменные: <code>@{{amount}}</code>
                                                    </div>
                                                @elseif ($emailTemplate->code === 'product_purchase_confirmation')
                                                    <div class="alert alert-info">
                                                        Доступные переменные: <code>@{{products_count}}</code>, <code>@{{total_amount}}</code>
                                                    </div>
                                                @elseif ($emailTemplate->code === 'guest_purchase_confirmation')
                                                    <div class="alert alert-info">
                                                        Доступные переменные: <code>@{{products_count}}</code>, <code>@{{total_amount}}</code>, <code>@{{guest_email}}</code>
                                                    </div>
                                                @elseif ($emailTemplate->code === 'reset_password')
                                                    <div class="alert alert-info">
                                                        Доступные переменные: <code>@{{url}}</code>, <code>@{{email}}</code>, <code>@{{button}}</code> (специальный плейсхолдер для HTML кнопки)
                                                    </div>
                                                @else
                                                    <div class="alert alert-info">
                                                        Вы можете использовать переменные в формате <code>@{{variable_name}}</code>
                                                    </div>
                                                @endif
                                                <textarea style="height: 210px"
                                                          name="message[{{ $code }}]"
                                                          class="ckeditor form-control @error('message.' . $code) is-invalid @enderror"
                                                          id="message_{{ $code }}">{!! old('message.' . $code, $emailTemplateData[$code]['message'] ?? null) !!}</textarea>
                                                @error('message.' . $code)
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-column flex-sm-row gap-2 mt-3">
                            <button type="submit" class="btn btn-primary btn-modern"><i class="fas fa-save mr-2"></i>Сохранить</button>
                            <button type="submit" name="save" class="btn btn-primary btn-modern"><i class="fas fa-save mr-2"></i>Сохранить и продолжить</button>
                            <a href="{{ route('admin.email-templates.index') }}" class="btn btn-secondary btn-modern">Отмена</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Test Email Section -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title">Тестовое письмо</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.email-templates.send-test', $emailTemplate) }}">
                        @csrf
                        <div class="form-group">
                            <label for="test_email">Email для теста</label>
                            <input type="email" name="test_email" id="test_email" 
                                   class="form-control @error('test_email') is-invalid @enderror" 
                                   value="{{ old('test_email', auth()->user()->email ?? '') }}" 
                                   placeholder="test@example.com" required>
                            @error('test_email')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Введите email-адрес для отправки тестового письма</small>
                        </div>
                        <div class="form-group">
                            <label for="test_locale">Язык</label>
                            <select name="locale" id="test_locale" class="form-control">
                                @foreach(config('langs') as $code => $flag)
                                    <option value="{{ $code }}" {{ $code === 'en' ? 'selected' : '' }}>
                                        {{ strtoupper($code) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-paper-plane mr-2"></i> Отправить тест
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('css')
    @include('admin.layouts.modern-styles')
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

