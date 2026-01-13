@extends('adminlte::page')

@section('title', 'Редактировать категорию товаров')

@section('content_header')
    <h1>Редактировать категорию товаров</h1>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Данные категории</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.product-categories.update', $category) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

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
                                                <input type="text" name="name[{{ $code }}]" id="name_{{ $code }}" class="form-control @error('name.' . $code) is-invalid @enderror" value="{{ old('name.' . $code, $categoryData[$code]['name'] ?? '') }}">
                                                @error('name.' . $code)
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="form-group">
                                                <label for="meta_title_{{ $code }}">Мета заголовок (SEO)</label>
                                                <input type="text" name="meta_title[{{ $code }}]" id="meta_title_{{ $code }}" class="form-control @error('meta_title.' . $code) is-invalid @enderror" value="{{ old('meta_title.' . $code, $categoryData[$code]['meta_title'] ?? '') }}">
                                                @error('meta_title.' . $code)
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="form-group">
                                                <label for="meta_description_{{ $code }}">Мета описание (SEO)</label>
                                                <input type="text" name="meta_description[{{ $code }}]" id="meta_description_{{ $code }}" class="form-control @error('meta_description.' . $code) is-invalid @enderror" value="{{ old('meta_description.' . $code, $categoryData[$code]['meta_description'] ?? '') }}">
                                                @error('meta_description.' . $code)
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="form-group">
                                                <label for="text_{{ $code }}">Текст (HTML доступен)</label>
                                                <textarea style="height: 210px" name="text[{{ $code }}]" id="text_{{ $code }}" class="ckeditor form-control @error('text.' . $code) is-invalid @enderror">{!! old('text.' . $code, $categoryData[$code]['text'] ?? '') !!}</textarea>
                                                @error('text.' . $code)
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <hr style="margin: 1.5rem 0;">

                        <div class="form-group">
                            <label for="image">Изображение категории</label>
                            <input type="file" name="image" id="image"
                                   class="form-control @error('image') is-invalid @enderror"
                                   accept="image/jpeg,image/png,image/jpg,image/gif,image/webp">
                            @error('image')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Выберите изображение с компьютера (JPEG, PNG, GIF, WebP, до 2MB)</small>
                        </div>

                        <div id="imagePreview" class="form-group mb-0" style="{{ $category->image_url ? '' : 'display: none;' }}">
                            @if($category->image_url)
                                <img id="previewImg" src="{{ $category->image_url }}" alt="Preview" class="img-fluid" style="max-width: 200px; max-height: 200px; border-radius: 8px; border: 1px solid #ddd;">
                            @else
                                <img id="previewImg" src="" alt="Preview" class="img-fluid" style="max-width: 200px; max-height: 200px; border-radius: 8px; border: 1px solid #ddd; display: none;">
                            @endif
                        </div>

                        <button type="submit" class="btn btn-primary">Сохранить</button>
                        <button type="submit" name="save" class="btn btn-primary">Сохранить и продолжить</button>
                        <a href="{{ route('admin.product-categories.index') }}" class="btn btn-secondary">Отмена</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
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
                    let img = document.getElementById('previewImg');
                    
                    if (file) {
                        // Validate file type
                        const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
                        if (!validTypes.includes(file.type)) {
                            alert('Пожалуйста, выберите изображение в формате JPEG, PNG, GIF или WebP');
                            this.value = '';
                            return;
                        }
                        
                        // Validate file size (2MB)
                        if (file.size > 2 * 1024 * 1024) {
                            alert('Размер изображения не должен превышать 2MB');
                            this.value = '';
                            return;
                        }
                        
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            if (!img) {
                                preview.innerHTML = '<img id="previewImg" src="' + e.target.result + '" alt="Preview" class="img-fluid" style="max-width: 200px; max-height: 200px; border-radius: 8px; border: 1px solid #ddd;">';
                                img = document.getElementById('previewImg');
                            } else {
                                img.src = e.target.result;
                                img.style.display = 'block';
                            }
                            preview.style.display = 'block';
                        };
                        reader.readAsDataURL(file);
                    } else {
                        // Если файл не выбран и нет существующего изображения, скрываем превью
                        const hasExistingImage = {{ $category->image_url ? 'true' : 'false' }};
                        if (!hasExistingImage) {
                            preview.style.display = 'none';
                        }
                    }
                });
            }
        });

        // Initialize CKEditor with image upload
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

