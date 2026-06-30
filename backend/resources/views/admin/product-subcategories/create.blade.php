@extends('adminlte::page')

@section('title', 'Добавить подкатегорию товаров')

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <h1 class="m-0 font-weight-light">Добавить подкатегорию товаров</h1>
                <p class="text-muted mb-0 mt-1 d-none d-md-block">Новая подкатегория в каталоге товаров</p>
            </div>
            <div class="w-100 w-md-auto">
                <a href="{{ route('admin.product-subcategories.index') }}" class="btn btn-secondary btn-modern w-100 w-md-auto">
                    <i class="fas fa-arrow-left mr-2"></i>Назад к списку
                </a>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Данные подкатегории</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.product-subcategories.store') }}">
                        @csrf

                        <div class="form-group">
                            <label for="parent_id">Родительская категория <span class="text-danger">*</span></label>
                            <select name="parent_id" id="parent_id" class="form-control @error('parent_id') is-invalid @enderror" required>
                                <option value="">Выберите категорию</option>
                                @foreach($parentCategories as $category)
                                    <option value="{{ $category->id }}" {{ old('parent_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->admin_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('parent_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="card mt-1">
                            <div class="card-header no-border border-0 p-0">
                                <ul class="nav nav-tabs" id="custom-tabs-lang" role="tablist">
                                    @foreach (config('langs') as $code => $flag)
                                        @php($hasError = $errors->has('name.' . $code))
                                        <li class="nav-item">
                                            <a class="nav-link @if ($hasError) text-danger @endif {{ $code == 'ru' ? 'active' : null }}" id="tab_{{ $code }}" data-toggle="pill" href="#content_{{ $code }}" role="tab">
                                                <span class="flag-icon flag-icon-{{ $flag }} mr-1"></span> {{ strtoupper($code) }} @if($hasError)*@endif
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                            <div class="card-body">
                                <div class="tab-content">
                                    @foreach (config('langs') as $code => $flag)
                                        <div class="tab-pane fade show {{ $code == 'ru' ? 'active' : null }}" id="content_{{ $code }}" role="tabpanel">
                                            <div class="form-group">
                                                <label for="name_{{ $code }}">Название</label>
                                                <input type="text" name="name[{{ $code }}]" id="name_{{ $code }}" class="form-control @error('name.' . $code) is-invalid @enderror" value="{{ old('name.' . $code) }}">
                                                @error('name.' . $code)
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="form-group">
                                                <label for="meta_title_{{ $code }}">Мета заголовок (SEO)</label>
                                                <input type="text" name="meta_title[{{ $code }}]" id="meta_title_{{ $code }}" class="form-control @error('meta_title.' . $code) is-invalid @enderror" value="{{ old('meta_title.' . $code) }}">
                                                @error('meta_title.' . $code)
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="form-group">
                                                <label for="meta_description_{{ $code }}">Мета описание (SEO)</label>
                                                <input type="text" name="meta_description[{{ $code }}]" id="meta_description_{{ $code }}" class="form-control @error('meta_description.' . $code) is-invalid @enderror" value="{{ old('meta_description.' . $code) }}">
                                                @error('meta_description.' . $code)
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="form-group">
                                                <label for="text_{{ $code }}">Текст (HTML доступен)</label>
                                                <textarea style="height: 210px" name="text[{{ $code }}]" id="text_{{ $code }}" class="ckeditor form-control @error('text.' . $code) is-invalid @enderror">{!! old('text.' . $code) !!}</textarea>
                                                @error('text.' . $code)
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-column flex-sm-row gap-2 mt-3">
                            <button type="submit" class="btn btn-primary btn-modern"><i class="fas fa-save mr-2"></i>Создать</button>
                            <a href="{{ route('admin.product-subcategories.index') }}" class="btn btn-secondary btn-modern">Отмена</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="https://cdn.ckeditor.com/ckeditor5/35.1.0/classic/ckeditor.js"></script>
    <script>
        // Initialize CKEditor
        if (typeof ClassicEditor !== 'undefined') {
            document.querySelectorAll('.ckeditor').forEach(function (textarea) {
                ClassicEditor
                    .create(textarea)
                    .then(editor => {
                        editor.editing.view.change(writer => {
                            writer.setStyle('height', '120px', editor.editing.view.document.getRoot());
                        });
                    })
                    .catch(error => {
                        console.error(error);
                    });
            });
        } else {
            console.warn('ClassicEditor is not defined. CKEditor script may not be loaded.');
        }
    </script>
@endsection

@section('css')
    @include('admin.layouts.modern-styles')
@endsection

