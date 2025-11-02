@extends('adminlte::page')

@section('title', 'Добавить товар')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Добавить товар</h1>
        <div>
            <a href="{{ route('supplier.products.index') }}" class="btn btn-info">
                <i class="fas fa-list"></i> Мои товары
            </a>
            <a href="{{ route('supplier.dashboard') }}" class="btn btn-info">
                <i class="fas fa-home"></i> Главная
            </a>
            <a href="{{ route('supplier.logout') }}" class="btn btn-secondary">
                <i class="fas fa-sign-out-alt"></i> Выход
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Информация о товаре</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('supplier.products.store') }}" enctype="multipart/form-data">
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
                                    <label for="title">Название товара *</label>
                                    <input type="text" name="title" id="title"
                                           class="form-control @error('title') is-invalid @enderror"
                                           value="{{ old('title') }}" required>
                                    @error('title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="description">Описание</label>
                                    <textarea name="description" id="description" rows="4"
                                              class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                                    @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="additional_description">Дополнительное описание</label>
                                    <textarea name="additional_description" id="additional_description" rows="3"
                                              class="form-control @error('additional_description') is-invalid @enderror">{{ old('additional_description') }}</textarea>
                                    @error('additional_description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- English Tab -->
                            <div class="tab-pane fade" id="en" role="tabpanel">
                                <div class="form-group">
                                    <label for="title_en">Title (English)</label>
                                    <input type="text" name="title_en" id="title_en"
                                           class="form-control @error('title_en') is-invalid @enderror"
                                           value="{{ old('title_en') }}">
                                    @error('title_en')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="description_en">Description (English)</label>
                                    <textarea name="description_en" id="description_en" rows="4"
                                              class="form-control @error('description_en') is-invalid @enderror">{{ old('description_en') }}</textarea>
                                    @error('description_en')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="additional_description_en">Additional Description (English)</label>
                                    <textarea name="additional_description_en" id="additional_description_en" rows="3"
                                              class="form-control @error('additional_description_en') is-invalid @enderror">{{ old('additional_description_en') }}</textarea>
                                    @error('additional_description_en')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Ukrainian Tab -->
                            <div class="tab-pane fade" id="uk" role="tabpanel">
                                <div class="form-group">
                                    <label for="title_uk">Назва (Українська)</label>
                                    <input type="text" name="title_uk" id="title_uk"
                                           class="form-control @error('title_uk') is-invalid @enderror"
                                           value="{{ old('title_uk') }}">
                                    @error('title_uk')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="description_uk">Опис (Українська)</label>
                                    <textarea name="description_uk" id="description_uk" rows="4"
                                              class="form-control @error('description_uk') is-invalid @enderror">{{ old('description_uk') }}</textarea>
                                    @error('description_uk')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="additional_description_uk">Додатковий опис (Українська)</label>
                                    <textarea name="additional_description_uk" id="additional_description_uk" rows="3"
                                              class="form-control @error('additional_description_uk') is-invalid @enderror">{{ old('additional_description_uk') }}</textarea>
                                    @error('additional_description_uk')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="form-group">
                            <label for="image">Изображение товара</label>
                            <input type="file" name="image" id="image"
                                   class="form-control @error('image') is-invalid @enderror"
                                   accept="image/jpeg,image/png,image/jpg,image/gif,image/webp">
                            @error('image')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">JPEG, PNG, GIF, WebP (макс. 2MB)</small>
                        </div>

                        <div id="imagePreview" class="form-group mb-3" style="display: none;">
                            <img id="previewImg" src="" alt="Preview" class="img-fluid" style="max-width: 200px; max-height: 200px; border-radius: 8px; border: 1px solid #ddd;">
                        </div>

                        <div class="form-group">
                            <label for="category_id">Категория товара</label>
                            <select name="category_id" id="category_id"
                                    class="form-control @error('category_id') is-invalid @enderror">
                                <option value="">Без категории</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->admin_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="price">Цена (USD) *</label>
                                    <input type="number" step="0.01" name="price" id="price"
                                           class="form-control @error('price') is-invalid @enderror"
                                           value="{{ old('price') }}" required>
                                    @error('price')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox mt-4">
                                        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="is_active">Активен (доступен для продажи)</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="bulk_accounts">Аккаунты (по одному на строке)</label>
                            <textarea name="bulk_accounts" id="bulk_accounts" rows="10"
                                      class="form-control @error('bulk_accounts') is-invalid @enderror"
                                      placeholder="логин:пароль&#10;логин2:пароль2&#10;или любой другой формат">{{ old('bulk_accounts') }}</textarea>
                            @error('bulk_accounts')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Введите аккаунты, по одному на строке. Каждая строка = один аккаунт.</small>
                        </div>

                        <div class="d-flex justify-content-between mb-3">
                            <button type="button" class="btn btn-warning btn-sm" onclick="removeDuplicates()">
                                <i class="fas fa-trash-alt"></i> Удалить дубликаты
                            </button>
                            <button type="button" class="btn btn-light btn-sm" onclick="shuffleLines()">
                                <i class="fas fa-random"></i> Перемешать
                            </button>
                        </div>

                        <hr>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Создать товар
                        </button>
                        <a href="{{ route('supplier.products.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Отмена
                        </a>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">Информация</h3>
                </div>
                <div class="card-body">
                    <p><strong>Комиссия платформы:</strong> {{ auth()->user()->supplier_commission }}%</p>
                    <p class="text-muted">При продаже товара комиссия платформы будет вычтена автоматически.</p>
                    <hr>
                    <p><strong>Ваш баланс:</strong> {{ number_format(auth()->user()->supplier_balance, 2) }} USD</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const imageInput = document.getElementById('image');
            if (imageInput) {
                imageInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    const preview = document.getElementById('imagePreview');
                    const img = document.getElementById('previewImg');
                    
                    if (file) {
                        const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
                        if (!validTypes.includes(file.type)) {
                            alert('Пожалуйста, выберите изображение в формате JPEG, PNG, GIF или WebP');
                            this.value = '';
                            return;
                        }
                        
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

    // Remove duplicate lines
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

