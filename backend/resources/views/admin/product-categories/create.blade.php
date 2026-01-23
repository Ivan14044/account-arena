@extends('adminlte::page')

@section('title', 'Добавить категорию товаров')

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="m-0 font-weight-light">Добавить категорию товаров</h1>
                <p class="text-muted mb-0 mt-1">Создание нового раздела в каталоге</p>
            </div>
            <div>
                <a href="{{ route('admin.product-categories.index') }}" class="btn btn-outline-secondary btn-modern">
                    <i class="fas fa-arrow-left mr-2"></i>К списку
                </a>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-9">
            <div class="card card-modern shadow-sm border-0">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('admin.product-categories.store') }}" enctype="multipart/form-data">
                        @csrf

                        <!-- Language Tabs -->
                        <div class="card card-outline card-primary shadow-none border">
                            <div class="card-header p-0 border-bottom-0">
                                <ul class="nav nav-tabs px-3 pt-2" id="custom-tabs-lang" role="tablist">
                                    @foreach (config('langs') as $code => $flag)
                                        @php($hasError = $errors->has('name.' . $code))
                                        <li class="nav-item">
                                            <a class="nav-link @if ($hasError) text-danger @endif {{ $code == 'ru' ? 'active' : null }}" 
                                               id="tab_{{ $code }}" data-toggle="pill" href="#content_{{ $code }}" role="tab">
                                                <span class="flag-icon flag-icon-{{ $flag }} mr-1"></span> 
                                                {{ strtoupper($code) }} 
                                                @if($hasError)<i class="fas fa-exclamation-circle ml-1 small"></i>@endif
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                            <div class="card-body">
                                <div class="tab-content">
                                    @foreach (config('langs') as $code => $flag)
                                        <div class="tab-pane fade show {{ $code == 'ru' ? 'active' : null }}" id="content_{{ $code }}" role="tabpanel">
                                            <div class="form-group mb-4">
                                                <label for="name_{{ $code }}" class="font-weight-normal text-muted small text-uppercase mb-1">Название категории</label>
                                                <input type="text" name="name[{{ $code }}]" id="name_{{ $code }}" 
                                                       class="form-control form-control-lg border-0 bg-light @error('name.' . $code) is-invalid @enderror" 
                                                       value="{{ old('name.' . $code) }}" placeholder="Введите название...">
                                                @error('name.' . $code)
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group mb-4">
                                                        <label for="meta_title_{{ $code }}" class="font-weight-normal text-muted small text-uppercase mb-1">Мета заголовок (SEO)</label>
                                                        <input type="text" name="meta_title[{{ $code }}]" id="meta_title_{{ $code }}" 
                                                               class="form-control border-0 bg-light @error('meta_title.' . $code) is-invalid @enderror" 
                                                               value="{{ old('meta_title.' . $code) }}">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group mb-4">
                                                        <label for="meta_description_{{ $code }}" class="font-weight-normal text-muted small text-uppercase mb-1">Мета описание (SEO)</label>
                                                        <input type="text" name="meta_description[{{ $code }}]" id="meta_description_{{ $code }}" 
                                                               class="form-control border-0 bg-light @error('meta_description.' . $code) is-invalid @enderror" 
                                                               value="{{ old('meta_description.' . $code) }}">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label for="text_{{ $code }}" class="font-weight-normal text-muted small text-uppercase mb-1">Описание категории (HTML)</label>
                                                <textarea name="text[{{ $code }}]" id="text_{{ $code }}" 
                                                          class="ckeditor form-control @error('text.' . $code) is-invalid @enderror">{!! old('text.' . $code) !!}</textarea>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <hr class="my-4 opacity-50">

                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="form-group mb-4">
                                    <label for="image" class="font-weight-normal text-muted small text-uppercase mb-1">Изображение категории</label>
                                    <div class="custom-file">
                                        <input type="file" name="image" id="image"
                                               class="custom-file-input @error('image') is-invalid @enderror"
                                               accept="image/jpeg,image/png,image/jpg,image/gif,image/webp">
                                        <label class="custom-file-label border-0 bg-light" for="image">Выберите файл...</label>
                                    </div>
                                    @error('image')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Рекомендуемый размер: 512x512px. До 2MB.</small>
                                </div>
                            </div>
                            <div class="col-md-6 text-center">
                                <div id="imagePreview" class="mt-2" style="display: none;">
                                    <div class="position-relative d-inline-block">
                                        <img id="previewImg" src="" alt="Preview" 
                                             class="img-fluid rounded shadow-sm border" 
                                             style="max-width: 150px; max-height: 150px; object-fit: cover;">
                                        <span class="badge badge-primary position-absolute" style="top: -10px; right: -10px;">Превью</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions mt-5 pt-3 border-top d-flex justify-content-between">
                            <button type="button" class="btn btn-light btn-modern text-muted px-4" onclick="window.history.back()">
                                Отмена
                            </button>
                            <div>
                                <button type="submit" class="btn btn-primary btn-modern px-5">
                                    <i class="fas fa-save mr-2"></i>Создать категорию
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3">
            <div class="card card-modern shadow-sm border-0">
                <div class="card-header-modern bg-light py-2">
                    <h6 class="mb-0 small text-uppercase font-weight-bold">Помощь</h6>
                </div>
                <div class="card-body small">
                    <p class="mb-2"><i class="fas fa-info-circle text-info mr-1"></i> <strong>Название</strong> — отображается на сайте и в меню.</p>
                    <p class="mb-2"><i class="fas fa-hashtag text-info mr-1"></i> <strong>SEO данные</strong> — помогают поисковикам правильно индексировать категорию.</p>
                    <p class="mb-0"><i class="fas fa-image text-info mr-1"></i> <strong>Изображение</strong> — используется на главной странице и в каталоге.</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('css')
    @include('admin.layouts.modern-styles')
    <style>
        .ck-editor__editable_inline {
            min-height: 200px;
        }
        .custom-file-label::after {
            content: "Обзор";
        }
    </style>
@endsection

@section('js')
    <script src="https://cdn.ckeditor.com/ckeditor5/35.1.0/classic/ckeditor.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Preview image from file input
            const imageInput = document.getElementById('image');
            if (imageInput) {
                imageInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    const preview = document.getElementById('imagePreview');
                    const img = document.getElementById('previewImg');
                    const label = this.nextElementSibling;
                    
                    if (file) {
                        label.textContent = file.name;
                        
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            img.src = e.target.result;
                            preview.style.display = 'block';
                        };
                        reader.readAsDataURL(file);
                    } else {
                        label.textContent = 'Выберите файл...';
                        preview.style.display = 'none';
                    }
                });
            }

            // Initialize CKEditor
            document.querySelectorAll('.ckeditor').forEach(function (textarea) {
                ClassicEditor
                    .create(textarea, {
                        toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'undo', 'redo']
                    })
                    .catch(error => console.error(error));
            });
        });
    </script>
@endsection
