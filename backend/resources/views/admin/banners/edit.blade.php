@extends('adminlte::page')

@section('title', 'Редактировать баннер')

@section('content_header')
    <h1>Редактировать баннер: {{ $banner->title }}</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.banners.update', $banner) }}" method="POST" enctype="multipart/form-data">
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

                <div class="tab-content mb-3">
                    <!-- Russian Tab -->
                    <div class="tab-pane fade show active" id="ru" role="tabpanel">
                        <div class="form-group">
                            <label for="title">Название (русский) *</label>
                            <input type="text" name="title" id="title"
                                   class="form-control @error('title') is-invalid @enderror"
                                   value="{{ old('title', $banner->title) }}" required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- English Tab -->
                    <div class="tab-pane fade" id="en" role="tabpanel">
                        <div class="form-group">
                            <label for="title_en">Название (English)</label>
                            <input type="text" name="title_en" id="title_en"
                                   class="form-control @error('title_en') is-invalid @enderror"
                                   value="{{ old('title_en', $banner->title_en) }}">
                            @error('title_en')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Ukrainian Tab -->
                    <div class="tab-pane fade" id="uk" role="tabpanel">
                        <div class="form-group">
                            <label for="title_uk">Название (українською)</label>
                            <input type="text" name="title_uk" id="title_uk"
                                   class="form-control @error('title_uk') is-invalid @enderror"
                                   value="{{ old('title_uk', $banner->title_uk) }}">
                            @error('title_uk')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <hr>

                <div class="form-group">
                    <label>Текущее изображение:</label>
                    @if($banner->image_url)
                        <div class="mb-2">
                            <img src="{{ $banner->image_url }}" alt="{{ $banner->title }}" 
                                 class="img-fluid" style="max-width: 400px; border-radius: 8px; border: 1px solid #ddd;">
                        </div>
                    @endif
                </div>

                <div class="form-group">
                    <label for="image">Новое изображение (оставьте пустым, чтобы не менять)</label>
                    <div class="alert alert-info mb-2">
                        <i class="fas fa-info-circle"></i>
                        <strong>Рекомендуемые размеры:</strong>
                        <ul class="mb-0 mt-2">
                            <li><strong>Оптимально:</strong> 800x200 пикселей (соотношение 4:1)</li>
                            <li><strong>Минимум:</strong> 600x150 пикселей</li>
                            <li><strong>Максимум:</strong> 1200x300 пикселей</li>
                        </ul>
                        <small class="mt-2 d-block">💡 Используйте горизонтальные баннеры для лучшего отображения</small>
                    </div>
                    <input type="file" name="image" id="image"
                           class="form-control @error('image') is-invalid @enderror"
                           accept="image/jpeg,image/png,image/jpg,image/gif,webp">
                    @error('image')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">Максимальный размер: 5MB. Форматы: JPEG, PNG, GIF, WebP</small>
                </div>

                <div id="imagePreview" class="form-group" style="display: none;">
                    <img id="previewImg" src="" alt="Preview" class="img-fluid" 
                         style="max-width: 400px; border-radius: 8px; border: 1px solid #ddd;">
                </div>

                <div class="form-group">
                    <label for="link">Ссылка (URL)</label>
                    <input type="url" name="link" id="link"
                           class="form-control @error('link') is-invalid @enderror"
                           value="{{ old('link', $banner->link) }}"
                           placeholder="https://example.com">
                    @error('link')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">URL, на который ведет баннер при клике</small>
                </div>

                <!-- Hidden position field - always home_top -->
                <input type="hidden" name="position" value="home_top">

                <div class="form-group">
                    <label for="order">Позиция баннера (1-4) *</label>
                    <select name="order" id="order"
                            class="form-control @error('order') is-invalid @enderror" required>
                        @php
                            $takenSlots = $existingBanners->pluck('order')->toArray();
                        @endphp
                        @for($i = 1; $i <= 4; $i++)
                            @php
                                $isTaken = in_array($i, $takenSlots);
                                $existingBanner = $existingBanners->firstWhere('order', $i);
                            @endphp
                            <option value="{{ $i }}" {{ old('order', $banner->order) == $i ? 'selected' : '' }}>
                                Баннер {{ $i }} (заменяет "Здесь реклама {{ $i }}")
                                @if($isTaken)
                                    - ⚠️ Занято: "{{ $existingBanner->title }}"
                                @endif
                            </option>
                        @endfor
                    </select>
                    @error('order')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">
                        Всего 4 позиции. Новый баннер заменит существующий на выбранной позиции.
                    </small>
                </div>

                @if($existingBanners->count() > 0)
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Другие активные баннеры:</strong>
                        <ul class="mb-0 mt-2">
                            @foreach($existingBanners as $existing)
                                <li>Позиция {{ $existing->order }}: "{{ $existing->title }}"</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="start_date">Дата начала показа</label>
                            <input type="datetime-local" name="start_date" id="start_date"
                                   class="form-control @error('start_date') is-invalid @enderror"
                                   value="{{ old('start_date', $banner->start_date ? $banner->start_date->format('Y-m-d\TH:i') : '') }}">
                            @error('start_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Оставьте пустым для показа сразу</small>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="end_date">Дата окончания показа</label>
                            <input type="datetime-local" name="end_date" id="end_date"
                                   class="form-control @error('end_date') is-invalid @enderror"
                                   value="{{ old('end_date', $banner->end_date ? $banner->end_date->format('Y-m-d\TH:i') : '') }}">
                            @error('end_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Оставьте пустым для бессрочного показа</small>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="custom-control custom-switch">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" class="custom-control-input" id="is_active" 
                               name="is_active" value="1" 
                               {{ old('is_active', $banner->is_active) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="is_active">Активен</label>
                    </div>
                </div>

                <div class="form-group">
                    <div class="custom-control custom-switch">
                        <input type="hidden" name="open_new_tab" value="0">
                        <input type="checkbox" class="custom-control-input" id="open_new_tab" 
                               name="open_new_tab" value="1" 
                               {{ old('open_new_tab', $banner->open_new_tab) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="open_new_tab">Открывать в новой вкладке</label>
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Сохранить изменения
                    </button>
                    <a href="{{ route('admin.banners.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Назад к списку
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Preview new image with dimension check
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
                        
                        if (file.size > 5 * 1024 * 1024) {
                            alert('Размер изображения не должен превышать 5MB');
                            this.value = '';
                            return;
                        }
                        
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const image = new Image();
                            image.onload = function() {
                                const width = this.width;
                                const height = this.height;
                                const ratio = width / height;

                                // Показываем предупреждение если размеры не оптимальны
                                if (width < 600 || height < 150) {
                                    alert('⚠️ Предупреждение: Изображение слишком маленькое!\n\n' +
                                          'Текущий размер: ' + width + 'x' + height + 'px\n' +
                                          'Рекомендуемый минимум: 600x150px\n\n' +
                                          'Баннер может выглядеть размыто.');
                                } else if (ratio < 3 || ratio > 5) {
                                    const recommendRatio = (ratio < 3) ? 'слишком квадратное' : 'слишком вытянутое';
                                    if (confirm('⚠️ Соотношение сторон не оптимально!\n\n' +
                                          'Текущее соотношение: ' + ratio.toFixed(2) + ':1 (' + recommendRatio + ')\n' +
                                          'Рекомендуемое: 4:1 (например, 800x200px)\n\n' +
                                          'Продолжить загрузку?')) {
                                        // Пользователь подтвердил
                                    } else {
                                        imageInput.value = '';
                                        return;
                                    }
                                } else {
                                    // Размеры хорошие!
                                    console.log('✅ Размер изображения оптимален: ' + width + 'x' + height + 'px');
                                }

                                img.src = e.target.result;
                                preview.style.display = 'block';
                            };
                            image.src = e.target.result;
                        };
                        reader.readAsDataURL(file);
                    } else {
                        preview.style.display = 'none';
                    }
                });
            }
        });
    </script>
@endsection

