@extends('adminlte::page')

@section('title', 'Создание нового товара')

@section('content_header')
    <h1>Создание нового товара</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-12">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            
            <div class="card">
                <div class="card-header py-2">
                    <h3 class="card-title" style="font-size: 1.2rem; margin: 0;">Данные товара</h3>
                </div>
                <div class="card-body">
                    <style>
                        .compact-form .form-group { margin-bottom: 0.75rem; }
                        .compact-form label { font-size: 0.9rem; margin-bottom: 0.3rem; }
                        .compact-form input, .compact-form select, .compact-form textarea { 
                            font-size: 0.9rem; 
                            padding: 0.4rem 0.75rem;
                        }
                        .compact-form .btn { padding: 0.3rem 0.75rem; font-size: 0.85rem; }
                        .compact-form .alert { padding: 0.5rem; margin-bottom: 0.5rem; }
                        .content-header h1 { font-size: 1.5rem; }
                    </style>
                    <div class="compact-form">
                        <form method="POST" action="{{ route('admin.service-accounts.store') }}" enctype="multipart/form-data">
                            @csrf

                            <!-- Language Tabs -->
                            <ul class="nav nav-tabs mb-3" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="ru-tab" data-toggle="tab" href="#ru" role="tab">🇷🇺 Русский</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="en-tab" data-toggle="tab" href="#en" role="tab">🇬🇧 English</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="uk-tab" data-toggle="tab" href="#uk" role="tab">🇺🇦 Українська</a>
                                </li>
                            </ul>

                            <div class="tab-content">
                                <!-- Russian Tab -->
                                <div class="tab-pane fade show active" id="ru" role="tabpanel">
                                    <div class="form-group">
                                        <label for="title">Имя товара (русский)</label>
                                        <input type="text" name="title" id="title"
                                               class="form-control @error('title') is-invalid @enderror"
                                               value="{{ old('title') }}" required>
                                        @error('title')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="description">Описание товара (HTML доступен)</label>
                                        <textarea name="description" id="description" rows="5"
                                                  class="ckeditor form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                                        @error('description')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="additional_description">Дополнительное описание (HTML доступен)</label>
                                        <textarea name="additional_description" id="additional_description" rows="5"
                                                  class="ckeditor form-control @error('additional_description') is-invalid @enderror">{{ old('additional_description') }}</textarea>
                                        @error('additional_description')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="meta_title">Мета заголовок (SEO)</label>
                                        <input type="text" name="meta_title" id="meta_title"
                                               class="form-control @error('meta_title') is-invalid @enderror"
                                               value="{{ old('meta_title') }}">
                                        @error('meta_title')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="meta_description">Мета описание (SEO)</label>
                                        <textarea name="meta_description" id="meta_description" rows="3"
                                                  class="form-control @error('meta_description') is-invalid @enderror">{{ old('meta_description') }}</textarea>
                                        @error('meta_description')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- English Tab -->
                                <div class="tab-pane fade" id="en" role="tabpanel">
                                    <div class="form-group">
                                        <label for="title_en">Product Name (English)</label>
                                        <input type="text" name="title_en" id="title_en"
                                               class="form-control @error('title_en') is-invalid @enderror"
                                               value="{{ old('title_en') }}">
                                        @error('title_en')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="description_en">Product Description (HTML available)</label>
                                        <textarea name="description_en" id="description_en" rows="5"
                                                  class="ckeditor form-control @error('description_en') is-invalid @enderror">{{ old('description_en') }}</textarea>
                                        @error('description_en')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="additional_description_en">Additional Description (HTML available)</label>
                                        <textarea name="additional_description_en" id="additional_description_en" rows="5"
                                                  class="ckeditor form-control @error('additional_description_en') is-invalid @enderror">{{ old('additional_description_en') }}</textarea>
                                        @error('additional_description_en')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="meta_title_en">Meta Title (SEO)</label>
                                        <input type="text" name="meta_title_en" id="meta_title_en"
                                               class="form-control @error('meta_title_en') is-invalid @enderror"
                                               value="{{ old('meta_title_en') }}">
                                        @error('meta_title_en')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="meta_description_en">Meta Description (SEO)</label>
                                        <textarea name="meta_description_en" id="meta_description_en" rows="3"
                                                  class="form-control @error('meta_description_en') is-invalid @enderror">{{ old('meta_description_en') }}</textarea>
                                        @error('meta_description_en')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Ukrainian Tab -->
                                <div class="tab-pane fade" id="uk" role="tabpanel">
                                    <div class="form-group">
                                        <label for="title_uk">Назва товару (українською)</label>
                                        <input type="text" name="title_uk" id="title_uk"
                                               class="form-control @error('title_uk') is-invalid @enderror"
                                               value="{{ old('title_uk') }}">
                                        @error('title_uk')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="description_uk">Опис товару (HTML доступний)</label>
                                        <textarea name="description_uk" id="description_uk" rows="5"
                                                  class="ckeditor form-control @error('description_uk') is-invalid @enderror">{{ old('description_uk') }}</textarea>
                                        @error('description_uk')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="additional_description_uk">Додатковий опис (HTML доступний)</label>
                                        <textarea name="additional_description_uk" id="additional_description_uk" rows="5"
                                                  class="ckeditor form-control @error('additional_description_uk') is-invalid @enderror">{{ old('additional_description_uk') }}</textarea>
                                        @error('additional_description_uk')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="meta_title_uk">Мета заголовок (SEO)</label>
                                        <input type="text" name="meta_title_uk" id="meta_title_uk"
                                               class="form-control @error('meta_title_uk') is-invalid @enderror"
                                               value="{{ old('meta_title_uk') }}">
                                        @error('meta_title_uk')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="meta_description_uk">Мета опис (SEO)</label>
                                        <textarea name="meta_description_uk" id="meta_description_uk" rows="3"
                                                  class="form-control @error('meta_description_uk') is-invalid @enderror">{{ old('meta_description_uk') }}</textarea>
                                        @error('meta_description_uk')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Common fields outside tabs -->
                            <hr style="margin: 1.5rem 0;">

                            <div class="form-group">
                                <label for="image">Изображение</label>
                                <input type="file" name="image" id="image"
                                       class="form-control @error('image') is-invalid @enderror"
                                       accept="image/jpeg,image/png,image/jpg,image/gif,image/webp">
                                @error('image')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Выберите изображение с компьютера (JPEG, PNG, GIF, WebP, до 2MB)</small>
                            </div>

                            <div id="imagePreview" class="form-group mb-0" style="display: none;">
                                <img id="previewImg" src="" alt="Preview" class="img-fluid" style="max-width: 200px; max-height: 200px; border-radius: 8px; border: 1px solid #ddd;">
                            </div>

                            <div class="form-group">
                                <label for="category_id">Категория товара</label>
                                <select name="category_id" id="category_id"
                                        class="form-control @error('category_id') is-invalid @enderror">
                                    <option value="">Без категории</option>
                                    @foreach(\App\Models\Category::productCategories()->with('translations')->get() as $category)
                                        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->admin_name ?? 'Category #' . $category->id }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="price">Цена</label>
                                <input type="number" step="0.01" name="price" id="price"
                                       class="form-control @error('price') is-invalid @enderror"
                                       value="{{ old('price') }}" required>
                                @error('price')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="bulk_accounts">Содержимое товара на продажу</label>
                                <textarea name="bulk_accounts" id="bulk_accounts" rows="8"
                                          class="form-control @error('bulk_accounts') is-invalid @enderror font-monospace"
                                          placeholder="Введите данные товаров, по одному на строку&#10;user1@mail.com:password123&#10;user2@mail.com:pass456&#10;login3:password789"></textarea>
                                @error('bulk_accounts')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    Каждая строка = один товар. Любой формат данных
                                </small>
                            </div>

                            <div class="d-flex justify-content-between mb-2">
                                <button type="button" class="btn btn-warning" onclick="removeDuplicates()">
                                    <i class="fas fa-trash-alt"></i> Удалить дубли
                                </button>
                                <button type="button" class="btn btn-light" onclick="shuffleLines()">
                                    <i class="fas fa-random"></i> Перемешать
                                </button>
                            </div>

                            <div class="alert alert-info py-2 mb-2">
                                <div class="small mb-0">
                                    <strong>1 строка = 1 штука</strong> | Автоудаление после продажи
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group mb-2">
                                        <label for="is_active">Статус</label>
                                        <select name="is_active" id="is_active"
                                                class="form-control @error('is_active') is-invalid @enderror">
                                            <option value="1" {{ old('is_active', 1) == 1 ? 'selected' : '' }}>Active</option>
                                            <option value="0" {{ old('is_active', 1) == 0 ? 'selected' : '' }}>Inactive</option>
                                        </select>
                                        @error('is_active')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Создать товар
                            </button>
                            <a href="{{ route('admin.service-accounts.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Отмена
                            </a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        // Wait for DOM to be ready
        document.addEventListener('DOMContentLoaded', function() {
            // Preview image from file input
            const imageInput = document.getElementById('image');
            if (imageInput) {
                imageInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    const preview = document.getElementById('imagePreview');
                    const img = document.getElementById('previewImg');
                    
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
                            img.src = e.target.result;
                            img.style.display = 'block';
                            preview.style.display = 'block';
                        };
                        reader.readAsDataURL(file);
                    } else {
                        preview.style.display = 'none';
                    }
                });
            }
            
        });

        // Initialize CKEditor
        if (typeof ClassicEditor !== 'undefined') {
            document.querySelectorAll('.ckeditor').forEach(function(textarea) {
                ClassicEditor
                    .create(textarea)
                    .then(editor => {
                        editor.editing.view.change(writer => {
                            writer.setStyle('height', '180px', editor.editing.view.document.getRoot());
                        });
                    })
                    .catch(error => {
                        console.error(error);
                    });
            });
        }

        // Remove duplicates from bulk accounts
        function removeDuplicates() {
            const textarea = document.getElementById('bulk_accounts');
            if (!textarea) return;

            const lines = textarea.value.split('\n');
            const uniqueLines = [];
            const seen = new Set();

            lines.forEach(line => {
                const trimmed = line.trim();
                if (trimmed && !seen.has(trimmed)) {
                    seen.add(trimmed);
                    uniqueLines.push(line);
                } else if (!trimmed) {
                    uniqueLines.push(line);
                }
            });

            const removed = lines.length - uniqueLines.length;
            textarea.value = uniqueLines.join('\n');

            if (removed > 0) {
                alert('Удалено дублей: ' + removed);
            } else {
                alert('Дубли не найдены');
            }
        }

        // Shuffle lines randomly
        function shuffleLines() {
            const textarea = document.getElementById('bulk_accounts');
            if (!textarea) return;

            const lines = textarea.value.split('\n');
            
            for (let i = lines.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [lines[i], lines[j]] = [lines[j], lines[i]];
            }

            textarea.value = lines.join('\n');
            alert('Строки перемешаны случайным образом');
        }
    </script>
@endsection
