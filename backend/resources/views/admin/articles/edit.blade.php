@extends('adminlte::page')

@section('title', 'Редактирование статьи')

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <h1 class="m-0 font-weight-light">Редактирование статьи</h1>
                <p class="text-muted mb-0 mt-1 d-none d-md-block">Изменение существующей записи</p>
            </div>
            <div class="w-100 w-md-auto">
                <a href="{{ route('admin.articles.index') }}" class="btn btn-secondary btn-modern w-100 w-md-auto">
                    <i class="fas fa-arrow-left mr-2"></i>Назад к списку
                </a>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">

            <section class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.articles.update', $article->id) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label for="categories">Категории</label>
                            <select name="categories[]" id="categories" class="select2 form-control @error('categories') is-invalid @enderror" multiple>
                                @php($selected = $article->categories->pluck('id')->toArray())
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" {{ in_array($category->id, old('categories', $selected)) ? 'selected' : '' }}>
                                        {{ $category->admin_name ?? 'Category #' . $category->id }}
                                    </option>
                                @endforeach
                            </select>
                            @error('categories')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="is_active">Статус</label>
                            <select name="is_active" id="is_active" class="form-control @error('is_active') is-invalid @enderror">
                                <option value="1" {{ $article->status == 'published' ? 'selected' : '' }}>Опубликовано</option>
                                <option value="0" {{ $article->status == 'draft' ? 'selected' : '' }}>Черновик</option>
                            </select>
                            @error('is_active')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="img">Изображение статьи</label>
                            @if($article->img)
                                <input type="hidden" name="img_text" value="{{ $article->img }}">
                            @endif
                            <input type="file" accept="image/*" class="form-control-file @error('img') is-invalid @enderror" id="img" name="img">
                            @if ($article->img)
                                <div id="articleImage" class="mt-2">
                                    <img src="{{ url($article->img) }}" class="img-fluid img-bordered" style="width: 150px;">
                                    <a href="#" onclick="removeArticleImage(event)" class="d-block mt-1">Удалить</a>
                                </div>
                            @endif
                            @error('img')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="card mt-4">
                            <div class="card-header no-border border-0 p-0">
                                <ul class="nav nav-tabs" id="custom-tabs-one-tab" role="tablist">
                                    @foreach (config('langs') as $code => $flag)
                                        @php($hasError = $errors->has('title.' . $code) || $errors->has('content.' . $code) || $errors->has('short.' . $code))
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
                                                <label for="meta_title_{{ $code }}">Meta-заголовок</label>
                                                <input type="text" name="meta_title[{{ $code }}]" id="meta_title_{{ $code }}" class="form-control @error('meta_title.' . $code) is-invalid @enderror" value="{{ old('meta_title.' . $code, $articleData[$code]['meta_title'] ?? '') }}">
                                                @error('meta_title.' . $code)
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="form-group">
                                                <label for="meta_description_{{ $code }}">Meta-описание</label>
                                                <input type="text" name="meta_description[{{ $code }}]" id="meta_description_{{ $code }}" class="form-control @error('meta_description.' . $code) is-invalid @enderror" value="{{ old('meta_description.' . $code, $articleData[$code]['meta_description'] ?? '') }}">
                                                @error('meta_description.' . $code)
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="form-group">
                                                <label for="short_{{ $code }}">Краткий текст</label>
                                                <textarea name="short[{{ $code }}]" id="short_{{ $code }}" class="form-control @error('short.' . $code) is-invalid @enderror" rows="3">{{ old('short.' . $code, $articleData[$code]['short'] ?? '') }}</textarea>
                                                @error('short.' . $code)
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="form-group">
                                                <label for="title_{{ $code }}">Заголовок</label>
                                                <input type="text" name="title[{{ $code }}]" id="title_{{ $code }}" class="form-control @error('title.' . $code) is-invalid @enderror" value="{{ old('title.' . $code, $articleData[$code]['title'] ?? '') }}">
                                                @error('title.' . $code)
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="form-group">
                                                <label for="content_{{ $code }}">Содержимое</label>
                                                <textarea style="height: 210px" name="content[{{ $code }}]" class="ckeditor form-control @error('content.' . $code) is-invalid @enderror" id="content_{{ $code }}">{!! old('content.' . $code, $articleData[$code]['content'] ?? '') !!}</textarea>
                                                @error('content.' . $code)
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
                            <button type="submit" name="save" class="btn btn-primary btn-modern">Сохранить и продолжить</button>
                            <a href="{{ route('admin.articles.index') }}" class="btn btn-secondary btn-modern">Отмена</a>
                        </div>
                    </form>
                </div>
            </section>

        </div>
    </div>
@endsection

@section('js')
    <script src="https://cdn.ckeditor.com/ckeditor5/35.1.0/classic/ckeditor.js"></script>
    <style>
        .select2-selection { height: 38px!important; width: 100%; }
    </style>
    <script>
        function removeArticleImage(event) {
            event.preventDefault();

            const container = document.getElementById('articleImage');
            if (container) {
                container.remove();
            }

            const imgText = document.querySelector('input[name="img_text"]');
            if (imgText) {
                imgText.remove();
            }

            const imgInput = document.getElementById('img');
            if (imgInput) {
                imgInput.value = '';
            }
        }
        document.querySelectorAll('.ckeditor').forEach(function(textarea) {
            ClassicEditor
                .create(textarea)
                .then(editor => {
                    editor.editing.view.change(writer => {
                        writer.setStyle('height', '170px', editor.editing.view.document.getRoot());
                    });
                })
                .catch(error => {
                    console.error(error);
                });
        });

        $(document).ready(function () {
            $('#categories').select2({
                placeholder: 'Выберите категории',
                allowClear: true
            });
        });
    </script>
@endsection

@section('css')
    @include('admin.layouts.modern-styles')
@endsection
