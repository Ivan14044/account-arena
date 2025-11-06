@extends('adminlte::page')

@section('title', 'Редактирование товара #' . $serviceAccount->id)

@section('content_header')
    <h1>Редактирование товара #{{ $serviceAccount->id }}</h1>
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
                        <form method="POST" action="{{ route('admin.service-accounts.update', $serviceAccount) }}" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

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
                                               value="{{ old('title', $serviceAccount->title) }}" required>
                                        @error('title')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="description">Описание товара (HTML доступен)</label>
                                        <textarea name="description" id="description" rows="5"
                                                  class="ckeditor form-control @error('description') is-invalid @enderror">{{ old('description', $serviceAccount->description) }}</textarea>
                                        @error('description')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="additional_description">Дополнительное описание (HTML доступен)</label>
                                        <textarea name="additional_description" id="additional_description" rows="5"
                                                  class="ckeditor form-control @error('additional_description') is-invalid @enderror">{{ old('additional_description', $serviceAccount->additional_description) }}</textarea>
                                        @error('additional_description')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="meta_title">Мета заголовок (SEO)</label>
                                        <input type="text" name="meta_title" id="meta_title"
                                               class="form-control @error('meta_title') is-invalid @enderror"
                                               value="{{ old('meta_title', $serviceAccount->meta_title) }}">
                                        @error('meta_title')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="meta_description">Мета описание (SEO)</label>
                                        <textarea name="meta_description" id="meta_description" rows="3"
                                                  class="form-control @error('meta_description') is-invalid @enderror">{{ old('meta_description', $serviceAccount->meta_description) }}</textarea>
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
                                               value="{{ old('title_en', $serviceAccount->title_en) }}">
                                        @error('title_en')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="description_en">Product Description (HTML available)</label>
                                        <textarea name="description_en" id="description_en" rows="5"
                                                  class="ckeditor form-control @error('description_en') is-invalid @enderror">{{ old('description_en', $serviceAccount->description_en) }}</textarea>
                                        @error('description_en')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="additional_description_en">Additional Description (HTML available)</label>
                                        <textarea name="additional_description_en" id="additional_description_en" rows="5"
                                                  class="ckeditor form-control @error('additional_description_en') is-invalid @enderror">{{ old('additional_description_en', $serviceAccount->additional_description_en) }}</textarea>
                                        @error('additional_description_en')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="meta_title_en">Meta Title (SEO)</label>
                                        <input type="text" name="meta_title_en" id="meta_title_en"
                                               class="form-control @error('meta_title_en') is-invalid @enderror"
                                               value="{{ old('meta_title_en', $serviceAccount->meta_title_en) }}">
                                        @error('meta_title_en')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="meta_description_en">Meta Description (SEO)</label>
                                        <textarea name="meta_description_en" id="meta_description_en" rows="3"
                                                  class="form-control @error('meta_description_en') is-invalid @enderror">{{ old('meta_description_en', $serviceAccount->meta_description_en) }}</textarea>
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
                                               value="{{ old('title_uk', $serviceAccount->title_uk) }}">
                                        @error('title_uk')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="description_uk">Опис товару (HTML доступний)</label>
                                        <textarea name="description_uk" id="description_uk" rows="5"
                                                  class="ckeditor form-control @error('description_uk') is-invalid @enderror">{{ old('description_uk', $serviceAccount->description_uk) }}</textarea>
                                        @error('description_uk')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="additional_description_uk">Додатковий опис (HTML доступний)</label>
                                        <textarea name="additional_description_uk" id="additional_description_uk" rows="5"
                                                  class="ckeditor form-control @error('additional_description_uk') is-invalid @enderror">{{ old('additional_description_uk', $serviceAccount->additional_description_uk) }}</textarea>
                                        @error('additional_description_uk')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="meta_title_uk">Мета заголовок (SEO)</label>
                                        <input type="text" name="meta_title_uk" id="meta_title_uk"
                                               class="form-control @error('meta_title_uk') is-invalid @enderror"
                                               value="{{ old('meta_title_uk', $serviceAccount->meta_title_uk) }}">
                                        @error('meta_title_uk')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="meta_description_uk">Мета опис (SEO)</label>
                                        <textarea name="meta_description_uk" id="meta_description_uk" rows="3"
                                                  class="form-control @error('meta_description_uk') is-invalid @enderror">{{ old('meta_description_uk', $serviceAccount->meta_description_uk) }}</textarea>
                                        @error('meta_description_uk')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Common fields outside tabs -->
                            <hr style="margin: 1.5rem 0;">

                            <div class="form-group">
                                <label for="image"></label>
                                <input type="file" name="image" id="image"
                                       class="form-control @error('image') is-invalid @enderror"
                                       accept="image/jpeg,image/png,image/jpg,image/gif,image/webp">
                                @error('image')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Выберите изображение с компьютера (JPEG, PNG, GIF, WebP, до 2MB)</small>
                            </div>

                            <div id="imagePreview" class="form-group mb-0" style="{{ $serviceAccount->image_url ? '' : 'display: none;' }}">
                                @if($serviceAccount->image_url)
                                    <img id="previewImg" src="{{ $serviceAccount->image_url }}" alt="Preview" class="img-fluid" style="max-width: 200px; max-height: 200px; border-radius: 8px; border: 1px solid #ddd;">
                                @else
                                    <img id="previewImg" src="" alt="Preview" class="img-fluid" style="max-width: 200px; max-height: 200px; border-radius: 8px; border: 1px solid #ddd; display: none;">
                                @endif
                            </div>

                            <div class="form-group">
                                <label for="category_id">Категория товара</label>
                                <select name="category_id" id="category_id"
                                        class="form-control @error('category_id') is-invalid @enderror">
                                    <option value="">Без категории</option>
                                    @foreach(\App\Models\Category::productCategories()->with('translations')->get() as $category)
                                        <option value="{{ $category->id }}" {{ old('category_id', $serviceAccount->category_id) == $category->id ? 'selected' : '' }}>
                                            {{ $category->admin_name ?? 'Category #' . $category->id }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="alert alert-info py-2">
                                <strong>Артикул:</strong> <code>{{ $serviceAccount->sku ?? 'не установлен' }}</code>
                                <small class="d-block mt-1 text-muted">Артикул генерируется автоматически при создании товара и не может быть изменен.</small>
                            </div>

                            <div class="form-group">
                                <label for="price">Цена</label>
                                <input type="number" step="0.01" name="price" id="price"
                                       class="form-control @error('price') is-invalid @enderror"
                                       value="{{ old('price', $serviceAccount->price) }}" required>
                                @error('price')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                @if(is_array($serviceAccount->accounts_data) && count($serviceAccount->accounts_data) > 0)
                                    @php
                                        $totalQuantity = count($serviceAccount->accounts_data);
                                        $soldCount = $serviceAccount->used ?? 0;
                                        $availableCount = max(0, $totalQuantity - $soldCount);
                                        // Получаем только ДОСТУПНЫЕ (непроданные) аккаунты
                                        $availableAccounts = array_slice($serviceAccount->accounts_data, $soldCount);
                                    @endphp
                                    <div class="alert alert-{{ $soldCount > 0 ? 'warning' : 'info' }} py-2 mb-2">
                                        <div class="small mb-0">
                                            <i class="fas fa-info-circle"></i>
                                            Всего аккаунтов: <strong>{{ $totalQuantity }}</strong> | 
                                            Доступно для продажи: <strong class="text-success">{{ $availableCount }}</strong> | 
                                            Уже продано: <strong class="text-danger">{{ $soldCount }}</strong>
                                        </div>
                                        @if($soldCount > 0)
                                            <div class="small mt-1 text-muted">
                                                ⚠️ Проданные аккаунты скрыты в поле ниже. При добавлении новых аккаунтов они будут добавлены ПОСЛЕ проданных.
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    @php
                                        $availableAccounts = [];
                                    @endphp
                                @endif
                                <label for="accounts_data">
                                    <i class="fas fa-user-tag"></i> Доступные аккаунты товара
                                    @if(isset($soldCount) && $soldCount > 0)
                                        <small class="text-muted">({{ $availableCount }} непроданных)</small>
                                    @endif
                                </label>
                                <textarea name="accounts_data" id="accounts_data" rows="8"
                                          class="form-control @error('accounts_data') is-invalid @enderror font-monospace">{{ old('accounts_data', is_array($availableAccounts) && count($availableAccounts) > 0 ? implode("\n", $availableAccounts) : '') }}</textarea>
                                @error('accounts_data')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    <i class="fas fa-lightbulb"></i> Каждая строка = один аккаунт. Любой формат данных (логин:пароль, email, token и т.д.)
                                </small>
                            </div>

                            <div class="d-flex justify-content-between mb-2">
                                <button type="button" class="btn btn-success" onclick="exportAccounts()">
                                    <i class="fas fa-download"></i> 
                                </button>
                                <button type="button" class="btn btn-info" onclick="$('#importModal').modal('show')">
                                    <i class="fas fa-upload"></i> 
                                </button>
                                <button type="button" class="btn btn-warning" onclick="removeDuplicates()">
                                    <i class="fas fa-trash-alt"></i> 
                                </button>
                                <button type="button" class="btn btn-light" onclick="shuffleLines()">
                                    <i class="fas fa-random"></i> 
                                </button>
                            </div>

                            <div class="alert alert-info py-2 mb-2">
                                <div class="small mb-0">
                                    <strong>1 строка = 1 аккаунт</strong> | Автоудаление после продажи
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group mb-2">
                                        <label for="is_active">Статус</label>
                                        <select name="is_active" id="is_active"
                                                class="form-control @error('is_active') is-invalid @enderror">
                                            <option value="1" {{ old('is_active', $serviceAccount->is_active) == 1 ? 'selected' : '' }}>Active</option>
                                            <option value="0" {{ old('is_active', $serviceAccount->is_active) == 0 ? 'selected' : '' }}>Inactive</option>
                                        </select>
                                        @error('is_active')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Сохранить товар
                            </button>
                            <a href="{{ route('admin.service-accounts.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i>Отмена</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal для загрузки товара -->
    <div class="modal fade" id="importModal" tabindex="-1" role="dialog" aria-labelledby="importModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importModalLabel"></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="import_data">Данные для загрузки</label>
                        <textarea id="import_data" class="form-control font-monospace" rows="15" placeholder="Вставьте данные товаров. Каждая строка = один товар"></textarea>
                        <small class="form-text text-muted">
                            Каждая строка будет добавлена как один товар. Новые строки будут добавлены к существующим.
                        </small>
                    </div>
                    <div class="form-group">
                        <label for="import_count">Количество строк для загрузки:</label>
                        <input type="number" id="import_count" class="form-control" value="0" readonly>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-primary" onclick="importAccounts()">Сохранить</button>
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
                        const hasExistingImage = {{ $serviceAccount->image_url ? 'true' : 'false' }};
                        if (!hasExistingImage) {
                            preview.style.display = 'none';
                        }
                    }
                });
            }
        });

        // Initialize CKEditor with image upload
        if (typeof ClassicEditor !== 'undefined') {
            // Custom upload adapter
            class MyUploadAdapter {
                constructor(loader) {
                    this.loader = loader;
                }

                upload() {
                    return this.loader.file.then(file => new Promise((resolve, reject) => {
                        const formData = new FormData();
                        formData.append('upload', file);
                        formData.append('_token', '{{ csrf_token() }}');

                        fetch('{{ route('admin.service-accounts.upload-image') }}', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(result => {
                            if (result.uploaded) {
                                resolve({
                                    default: result.url
                                });
                            } else {
                                reject(result.error.message);
                            }
                        })
                        .catch(error => {
                            reject('Ошибка загрузки изображения');
                        });
                    }));
                }
            }

            function MyCustomUploadAdapterPlugin(editor) {
                editor.plugins.get('FileRepository').createUploadAdapter = (loader) => {
                    return new MyUploadAdapter(loader);
                };
            }

            document.querySelectorAll('.ckeditor').forEach(function(textarea) {
                ClassicEditor
                    .create(textarea, {
                        extraPlugins: [MyCustomUploadAdapterPlugin],
                        toolbar: {
                            items: [
                                'heading', '|',
                                'bold', 'italic', 'link', '|',
                                'bulletedList', 'numberedList', '|',
                                'imageUpload', 'blockQuote', '|',
                                'undo', 'redo'
                            ]
                        },
                        image: {
                            toolbar: [
                                'imageStyle:inline',
                                'imageStyle:block',
                                'imageStyle:side',
                                '|',
                                'toggleImageCaption',
                                'imageTextAlternative'
                            ]
                        }
                    })
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

        // Remove duplicates from accounts
        function removeDuplicates() {
            const textarea = document.getElementById('accounts_data');
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
            const textarea = document.getElementById('accounts_data');
            if (!textarea) return;

            const lines = textarea.value.split('\n');
            
            for (let i = lines.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [lines[i], lines[j]] = [lines[j], lines[i]];
            }

            textarea.value = lines.join('\n');
            alert('Строки перемешаны случайным образом');
        }

        // Export accounts to file
        function exportAccounts() {
            const textarea = document.getElementById('accounts_data');
            if (!textarea || !textarea.value.trim()) {
                alert('Нет данных для выгрузки');
                return;
            }

            const lines = textarea.value.split('\n').filter(line => line.trim() !== '');
            const countStr = prompt('Сколько товаров выгрузить? (всего: ' + lines.length + ')', lines.length);
            
            if (countStr === null) return; // User cancelled
            
            const count = parseInt(countStr);
            if (isNaN(count) || count < 1) {
                alert('Введите корректное число');
                return;
            }

            const exportLines = lines.slice(0, Math.min(count, lines.length));
            const data = exportLines.join('\n');
            
            const blob = new Blob([data], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'product_{{ $serviceAccount->id }}_' + new Date().toISOString().split('T')[0] + '.txt';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }

        // Import accounts from modal
        function importAccounts() {
            const importTextarea = document.getElementById('import_data');
            const mainTextarea = document.getElementById('accounts_data');
            
            if (!importTextarea || !mainTextarea) return;
            
            const importLines = importTextarea.value.split('\n').filter(line => line.trim() !== '');
            
            if (importLines.length === 0) {
                alert('Нет данных для загрузки');
                return;
            }

            const existingLines = mainTextarea.value.split('\n').filter(line => line.trim() !== '');
            const combinedLines = [...existingLines, ...importLines];
            
            mainTextarea.value = combinedLines.join('\n');
            
            // Close modal and clear import data
            $('#importModal').modal('hide');
            importTextarea.value = '';
            document.getElementById('import_count').value = 0;
            
            alert('Загружено ' + importLines.length + ' строк. Всего товаров: ' + combinedLines.length);
        }

        // Update count when typing in import modal
        document.addEventListener('DOMContentLoaded', function() {
            const importTextarea = document.getElementById('import_data');
            const importCount = document.getElementById('import_count');
            
            if (importTextarea && importCount) {
                importTextarea.addEventListener('input', function() {
                    const lines = this.value.split('\n').filter(line => line.trim() !== '');
                    importCount.value = lines.length;
                });
            }
        });
    </script>
@endsection
