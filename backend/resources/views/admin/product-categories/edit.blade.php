@extends('adminlte::page')

@section('title', 'Редактировать категорию товаров')

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="m-0 font-weight-light">Редактировать категорию</h1>
                <div class="d-flex align-items-center mt-1">
                    <span class="badge badge-light border text-muted px-2 mr-2">ID: #{{ $category->id }}</span>
                    <p class="text-muted mb-0">{{ $category->admin_name }}</p>
                </div>
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
            @if(session('success'))
                <div class="alert alert-modern alert-success alert-dismissible fade show mb-4">
                    <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            @endif

            <div class="card card-modern shadow-sm border-0">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('admin.product-categories.update', $category) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

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
                                                       value="{{ old('name.' . $code, $categoryData[$code]['name'] ?? '') }}" placeholder="Введите название...">
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
                                                               value="{{ old('meta_title.' . $code, $categoryData[$code]['meta_title'] ?? '') }}">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group mb-4">
                                                        <label for="meta_description_{{ $code }}" class="font-weight-normal text-muted small text-uppercase mb-1">Мета описание (SEO)</label>
                                                        <input type="text" name="meta_description[{{ $code }}]" id="meta_description_{{ $code }}" 
                                                               class="form-control border-0 bg-light @error('meta_description.' . $code) is-invalid @enderror" 
                                                               value="{{ old('meta_description.' . $code, $categoryData[$code]['meta_description'] ?? '') }}">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label for="text_{{ $code }}" class="font-weight-normal text-muted small text-uppercase mb-1">Описание категории (HTML)</label>
                                                <textarea name="text[{{ $code }}]" id="text_{{ $code }}" 
                                                          class="ckeditor form-control @error('text.' . $code) is-invalid @enderror">{!! old('text.' . $code, $categoryData[$code]['text'] ?? '') !!}</textarea>
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
                                    <label for="image" class="font-weight-normal text-muted small text-uppercase mb-1">Обновить изображение</label>
                                    <div class="custom-file">
                                        <input type="file" name="image" id="image"
                                               class="custom-file-input @error('image') is-invalid @enderror"
                                               accept="image/jpeg,image/png,image/jpg,image/gif,image/webp">
                                        <label class="custom-file-label border-0 bg-light" for="image">Выберите новый файл...</label>
                                    </div>
                                    @error('image')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6 text-center">
                                <div id="imagePreview" class="mt-2">
                                    <div class="position-relative d-inline-block">
                                        @if($category->image_url)
                                            <img id="previewImg" src="{{ $category->image_url }}" alt="Preview" 
                                                 class="img-fluid rounded shadow-sm border" 
                                                 style="max-width: 150px; max-height: 150px; object-fit: cover;">
                                            <span class="badge badge-primary position-absolute" style="top: -10px; right: -10px;">Текущее</span>
                                        @else
                                            <div id="no-image-placeholder" class="bg-light rounded d-flex align-items-center justify-content-center border" style="width: 150px; height: 150px;">
                                                <i class="fas fa-image text-muted opacity-50 fa-2x"></i>
                                            </div>
                                            <img id="previewImg" src="" alt="Preview" 
                                                 class="img-fluid rounded shadow-sm border" 
                                                 style="max-width: 150px; max-height: 150px; object-fit: cover; display: none;">
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions mt-5 pt-3 border-top d-flex justify-content-between align-items-center">
                            <button type="button" class="btn btn-light btn-modern text-muted px-4" onclick="window.history.back()">
                                Отмена
                            </button>
                            <div>
                                <button type="submit" name="save" value="1" class="btn btn-outline-primary btn-modern px-4 mr-2">
                                    Сохранить и остаться
                                </button>
                                <button type="submit" class="btn btn-primary btn-modern px-5">
                                    <i class="fas fa-save mr-2"></i>Сохранить изменения
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3">
            <!-- Information Card -->
            <div class="card card-modern shadow-sm border-0 mb-4">
                <div class="card-header-modern bg-light py-2">
                    <h6 class="mb-0 small text-uppercase font-weight-bold">Информация</h6>
                </div>
                <div class="card-body p-3">
                    <div class="mb-3">
                        <label class="text-muted small text-uppercase d-block mb-1">Слаг (URL)</label>
                        <code class="d-block p-2 bg-light rounded" style="word-break: break-all;">{{ $category->slug }}</code>
                    </div>
                    @php
                        $productsCount = \App\Models\ServiceAccount::where('category_id', $category->id)->count();
                        $subCategoriesCount = $category->children()->count();
                    @endphp
                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">Товаров (прямо):</span>
                        <span class="font-weight-bold">{{ $productsCount }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-0 small">
                        <span class="text-muted">Подкатегорий:</span>
                        <span class="font-weight-bold">{{ $subCategoriesCount }}</span>
                    </div>
                </div>
            </div>

            <!-- Management Card -->
            <div class="card card-modern shadow-sm border-0">
                <div class="card-header-modern bg-light py-2">
                    <h6 class="mb-0 small text-uppercase font-weight-bold">Управление</h6>
                </div>
                <div class="card-body p-3">
                    <a href="{{ route('admin.product-subcategories.index', ['parent_id' => $category->id]) }}" class="btn btn-block btn-info btn-sm btn-modern mb-2">
                        <i class="fas fa-list mr-2"></i>Все подкатегории
                    </a>
                    <a href="{{ route('admin.product-subcategories.create', ['parent_id' => $category->id]) }}" class="btn btn-block btn-outline-info btn-sm btn-modern">
                        <i class="fas fa-plus mr-2"></i>Новая подкатегория
                    </a>
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
                    const placeholder = document.getElementById('no-image-placeholder');
                    const label = this.nextElementSibling;
                    
                    if (file) {
                        label.textContent = file.name;
                        
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            img.src = e.target.result;
                            img.style.display = 'block';
                            if (placeholder) placeholder.style.display = 'none';
                        };
                        reader.readAsDataURL(file);
                    } else {
                        label.textContent = 'Выберите новый файл...';
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
