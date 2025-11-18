@extends('adminlte::page')

@section('title', 'Товары')

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="m-0 font-weight-light">
                    Управление товарами
                </h1>
                <p class="text-muted mb-0 mt-1">Каталог цифровых товаров и сервисов для продажи</p>
            </div>
            <div>
                <a href="{{ route('admin.service-accounts.create') }}" class="btn btn-primary btn-modern">
                    <i class="fas fa-plus mr-2"></i>Добавить товар
                </a>
            </div>
        </div>
    </div>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-modern alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-modern alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    @if(session('export_success'))
        <div class="alert alert-modern alert-success alert-dismissible fade show">
            <i class="fas fa-download mr-2"></i>{{ session('export_success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <!-- Статистика -->
    <div class="row mb-4">
        <div class="col-lg-3 col-6">
            <div class="stat-card stat-card-primary">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Всего товаров</div>
                        <div class="stat-value">{{ $serviceAccounts->count() }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="stat-card stat-card-success">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Активные</div>
                        <div class="stat-value">{{ $serviceAccounts->where('is_active', true)->count() }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="stat-card stat-card-warning">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Продано</div>
                        <div class="stat-value">{{ $serviceAccounts->sum('used') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="stat-card stat-card-info">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Общая стоимость</div>
                        <div class="stat-value">${{ number_format($serviceAccounts->sum('price'), 0) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Фильтры по категориям -->
    <div class="card card-modern mb-4">
        <div class="card-header-modern">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0 font-weight-normal">
                        <i class="fas fa-filter mr-2 text-primary"></i>Фильтр по категориям
                    </h5>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="resetAllFilters" title="Сбросить все фильтры">
                    <i class="fas fa-redo mr-1"></i>Сбросить фильтры
                </button>
            </div>
        </div>
        <div class="card-body-modern" style="padding: 1.25rem 1.5rem;">
            <div class="category-filters-wrapper">
                <div class="category-filters-main">
                    <div class="btn-group btn-group-filter" role="group">
                        <button type="button" class="btn btn-filter active" data-category-id="all" id="filterCategoryAll">
                            Все товары
                            <span class="badge badge-light ml-2">{{ $serviceAccounts->count() }}</span>
                        </button>
                    </div>
                    @if($noCategoryCount > 0)
                    <div class="btn-group btn-group-filter" role="group">
                        <button type="button" class="btn btn-filter btn-category-none" 
                                data-category-id="none" 
                                data-is-parent="false"
                                title="Показать товары без категории">
                            Без категории
                            <span class="badge badge-light ml-2">{{ $noCategoryCount }}</span>
                        </button>
                    </div>
                    @endif
                    @foreach($parentCategories as $parentCategory)
                        @php
                            $categoryName = $parentCategory->admin_name ?? 'Категория #' . $parentCategory->id;
                            $categorySubcategories = $subcategories->where('parent_id', $parentCategory->id);
                            $productsCount = $parentCategory->products_count ?? 0;
                        @endphp
                        <div class="category-group" data-parent-category-id="{{ $parentCategory->id }}">
                            <div class="btn-group btn-group-filter" role="group">
                                <button type="button" class="btn btn-filter btn-category-parent" 
                                        data-category-id="{{ $parentCategory->id }}" 
                                        data-is-parent="true"
                                    title="Показать товары из категории и всех её подкатегорий">
                                {{ $categoryName }}
                                <span class="badge badge-light ml-2">{{ $productsCount }}</span>
                                <i class="fas fa-chevron-down ml-2 category-arrow" style="font-size: 0.75rem;"></i>
                            </button>
                            </div>
                            <div class="subcategories-container" style="display: none; margin-top: 0.5rem;">
                                <div class="btn-group btn-group-filter-sub" role="group">
                                    @foreach($categorySubcategories as $subcategory)
                                        @php
                                            $subcategoryName = $subcategory->admin_name ?? 'Подкатегория #' . $subcategory->id;
                                            $subcategoryCount = $subcategory->products_count ?? 0;
                                        @endphp
                                        <button type="button" class="btn btn-filter btn-category-sub" 
                                                data-category-id="{{ $subcategory->id }}" 
                                                data-parent-id="{{ $subcategory->parent_id }}"
                                                data-is-parent="false"
                                                title="Показать товары только из этой подкатегории">
                                            {{ $subcategoryName }}
                                            <span class="badge badge-light ml-2">{{ $subcategoryCount }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Таблица -->
    <div class="card card-modern">
        <div class="card-header-modern">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Список товаров</h5>
                    <small class="text-muted">Всего записей: <span id="totalCount">{{ $serviceAccounts->count() }}</span></small>
                </div>
                <div class="filters-container d-flex align-items-center gap-3">
                    <div class="btn-group btn-group-filter" role="group">
                        <button type="button" class="btn btn-filter active" id="filterAll">Все</button>
                        <button type="button" class="btn btn-filter" id="filterActive">Активные</button>
                        <button type="button" class="btn btn-filter" id="filterInactive">Неактивные</button>
                    </div>
                    <div class="sort-container">
                        <label for="sortSelect" class="mr-2 mb-0" style="font-size: 0.875rem; color: #6c757d;">
                            <i class="fas fa-sort mr-1"></i>Сортировка:
                        </label>
                        <select id="sortSelect" class="form-control form-control-sm" style="display: inline-block; width: auto; min-width: 180px;">
                            <option value="sort_order-asc" selected>Ручной порядок</option>
                            <option value="1-asc">ID (по возрастанию)</option>
                            <option value="1-desc">ID (по убыванию)</option>
                            <option value="5-asc">Цена (по возрастанию)</option>
                            <option value="5-desc">Цена (по убыванию)</option>
                            <option value="9-desc">Дата создания (новые сначала)</option>
                            <option value="9-asc">Дата создания (старые сначала)</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body-modern">
            <div class="table-responsive">
                <table id="service-accounts-table" class="table table-hover modern-table">
                    <thead>
                        <tr>
                            <th style="width: 40px" class="text-center">
                                <i class="fas fa-grip-vertical text-muted" title="Перетащите для изменения порядка"></i>
                            </th>
                            <th style="width: 50px" class="text-center">ID</th>
                            <th style="width: 80px" class="text-center">Изображение</th>
                            <th style="min-width: 250px">Товар</th>
                            <th class="text-center">Категория</th>
                            <th class="text-center">Цена</th>
                            <th class="text-center">В наличии</th>
                            <th class="text-center">Продано</th>
                            <th>Статус</th>
                            <th class="text-center">Дата создания</th>
                            <th style="width: 180px" class="text-center">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($serviceAccounts as $serviceAccount)
                        @php
                            $totalQuantity = is_array($serviceAccount->accounts_data) ? count($serviceAccount->accounts_data) : 0;
                            $soldCount = $serviceAccount->used ?? 0;
                            $availableCount = max(0, $totalQuantity - $soldCount);
                            $categoryId = $serviceAccount->category_id ?? null;
                            $categoryName = $serviceAccount->category ? ($serviceAccount->category->admin_name ?? 'Без категории') : 'Без категории';
                            // Определяем parent_id категории для фильтрации
                            $categoryParentId = $serviceAccount->category ? ($serviceAccount->category->parent_id ?? null) : null;
                        @endphp
                        <tr data-id="{{ $serviceAccount->id }}" 
                            data-category-id="{{ $categoryId ?? '' }}" 
                            data-category-parent-id="{{ $categoryParentId ?? '' }}"
                            class="sortable-row">
                            <td class="text-center align-middle drag-handle" style="cursor: move;">
                                <i class="fas fa-grip-vertical text-muted"></i>
                            </td>
                            <td class="text-center align-middle">
                                <span class="badge badge-secondary">#{{ $serviceAccount->id }}</span>
                            </td>
                            <td class="text-center align-middle">
                                @if($serviceAccount->image_url)
                                    <img src="{{ $serviceAccount->image_url }}" 
                                         alt="{{ $serviceAccount->title }}" 
                                         class="rounded"
                                         style="width: 60px; height: 60px; object-fit: cover; border: 1px solid #e3e6f0;">
                                @else
                                    <div class="rounded d-flex align-items-center justify-content-center" 
                                         style="width: 60px; height: 60px; background: #f8f9fc; border: 1px solid #e3e6f0;">
                                        <i class="fas fa-image text-muted fa-2x"></i>
                                    </div>
                                @endif
                            </td>
                            <td class="align-middle">
                                <div>
                                    <div class="font-weight-bold text-dark">
                                        {{ $serviceAccount->title ?: 'Без названия' }}
                                    </div>
                                    @if($serviceAccount->description)
                                        <small class="text-muted d-block mt-1">
                                            {{ Str::limit(strip_tags($serviceAccount->description), 60) }}
                                        </small>
                                    @endif
                                </div>
                            </td>
                            <td class="text-center align-middle">
                                @if($categoryId)
                                    <span class="badge badge-info badge-modern" title="{{ $categoryName }}">
                                        {{ $categoryName }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center align-middle">
                                @if($serviceAccount->price)
                                    <strong class="text-success" style="font-size: 1.1rem;">
                                        ${{ number_format($serviceAccount->price, 2) }}
                                    </strong>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center align-middle">
                                @if($availableCount > 0)
                                    <span class="badge badge-success badge-modern font-weight-bold" style="font-size: 0.875rem;">
                                        {{ $availableCount }} шт.
                                    </span>
                                @else
                                    <span class="badge badge-secondary badge-modern">
                                        Нет в наличии
                                    </span>
                                @endif
                                @if($totalQuantity > 0)
                                    <div><small class="text-muted">Всего: {{ $totalQuantity }}</small></div>
                                @endif
                            </td>
                            <td class="text-center align-middle">
                                @if($soldCount > 0)
                                    <span class="badge badge-warning badge-modern font-weight-bold" style="font-size: 0.875rem;">
                                        {{ $soldCount }} шт.
                                    </span>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </td>
                            <td class="align-middle">
                                @if(!$serviceAccount->is_active)
                                    <span class="badge badge-danger badge-modern">
                                        <i class="fas fa-ban mr-1"></i>Неактивен
                                    </span>
                                @elseif($availableCount > 0)
                                    <span class="badge badge-success badge-modern">
                                        <i class="fas fa-check-circle mr-1"></i>Активен
                                    </span>
                                @else
                                    <span class="badge badge-secondary badge-modern">
                                        <i class="fas fa-box mr-1"></i>Нет в наличии
                                    </span>
                                @endif
                            </td>
                            <td class="text-center align-middle">
                                <small class="text-muted">
                                    <i class="far fa-calendar-alt mr-1"></i>
                                    {{ \Carbon\Carbon::parse($serviceAccount->created_at)->format('d.m.Y H:i') }}
                                </small>
                            </td>
                            <td class="text-center align-middle">
                                <div class="btn-group action-buttons" role="group">
                                    <a href="{{ route('admin.service-accounts.edit', $serviceAccount) }}"
                                       class="btn btn-sm btn-primary" 
                                       title="Редактировать"
                                       data-toggle="tooltip">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    @if($totalQuantity > 0)
                                    <button class="btn btn-sm btn-success" 
                                            onclick="exportAccountsFromIndex({{ $serviceAccount->id }}, {{ $availableCount }})"
                                            title="Экспорт товаров"
                                            data-toggle="tooltip">
                                        <i class="fas fa-download"></i>
                                    </button>
                                    @endif

                                    <button class="btn btn-sm btn-info" 
                                            data-toggle="modal"
                                            data-target="#importModal{{ $serviceAccount->id }}" 
                                            title="Импорт товаров"
                                            data-toggle-tooltip="tooltip">
                                        <i class="fas fa-upload"></i>
                                    </button>

                                    <button class="btn btn-sm btn-danger" 
                                            data-toggle="modal"
                                            data-target="#deleteModal{{ $serviceAccount->id }}" 
                                            title="Удалить"
                                            data-toggle-tooltip="tooltip">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>

                                <!-- Модальное окно удаления -->
                                <div class="modal fade" id="deleteModal{{ $serviceAccount->id }}" tabindex="-1" role="dialog">
                                    <div class="modal-dialog modal-dialog-centered" role="document">
                                        <div class="modal-content modal-modern">
                                            <div class="modal-header-modern">
                                                <h5 class="modal-title">
                                                    <i class="fas fa-exclamation-triangle mr-2 text-danger"></i>
                                                    Подтверждение удаления
                                                </h5>
                                                <button type="button" class="close" data-dismiss="modal">
                                                    <span>&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body-modern text-center">
                                                <i class="fas fa-box-open fa-3x text-danger mb-3"></i>
                                                <h6 class="font-weight-bold">{{ $serviceAccount->title ?: 'Товар' }}</h6>
                                                <p class="text-muted mb-0">В наличии: {{ $availableCount }} шт.</p>
                                                <small class="text-danger">Это действие нельзя отменить!</small>
                                            </div>
                                            <div class="modal-footer-modern justify-content-center">
                                                <form action="{{ route('admin.service-accounts.destroy', $serviceAccount) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-modern">
                                                        <i class="fas fa-trash-alt mr-2"></i>Да, удалить
                                                    </button>
                                                </form>
                                                <button type="button" class="btn btn-secondary btn-modern" data-dismiss="modal">
                                                    Отмена
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Модальное окно импорта -->
                                <div class="modal fade" id="importModal{{ $serviceAccount->id }}" tabindex="-1" role="dialog">
                                    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                                        <div class="modal-content modal-modern">
                                            <div class="modal-header-modern">
                                                <h5 class="modal-title">
                                                    <i class="fas fa-upload mr-2 text-info"></i>
                                                    Импорт товаров
                                                </h5>
                                                <button type="button" class="close" data-dismiss="modal">
                                                    <span>&times;</span>
                                                </button>
                                            </div>
                                            <form action="{{ route('admin.service-accounts.import', $serviceAccount) }}" method="POST">
                                                @csrf
                                                <div class="modal-body-modern">
                                                    <div class="form-group-modern">
                                                        <label for="import_data{{ $serviceAccount->id }}" class="form-label-modern">
                                                            Данные для загрузки
                                                        </label>
                                                        <textarea 
                                                            name="import_data" 
                                                            id="import_data{{ $serviceAccount->id }}" 
                                                            class="form-control form-control-modern font-monospace" 
                                                            rows="15" 
                                                            placeholder="Вставьте данные товаров. Каждая строка = один товар" 
                                                            required></textarea>
                                                        <small class="form-text text-muted">
                                                            <i class="fas fa-info-circle mr-1"></i>
                                                            Каждая строка будет добавлена как один товар. Новые строки будут добавлены к существующим.
                                                        </small>
                                                    </div>
                                                    <div class="form-group-modern mb-0">
                                                        <label for="import_count{{ $serviceAccount->id }}" class="form-label-modern">
                                                            Количество строк для загрузки:
                                                        </label>
                                                        <input 
                                                            type="number" 
                                                            id="import_count{{ $serviceAccount->id }}" 
                                                            class="form-control form-control-modern" 
                                                            value="0" 
                                                            readonly>
                                                    </div>
                                                </div>
                                                <div class="modal-footer-modern">
                                                    <button type="button" class="btn btn-secondary btn-modern" data-dismiss="modal">Отмена</button>
                                                    <button type="submit" class="btn btn-primary btn-modern">
                                                        <i class="fas fa-save mr-2"></i>Сохранить
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('css')
    @include('admin.layouts.modern-styles')
    <style>
        /* Стили для drag-and-drop */
        .sortable-row {
            transition: background-color 0.2s;
        }
        
        .sortable-ghost {
            opacity: 0.4;
            background-color: #f0f0f0 !important;
        }
        
        .sortable-chosen {
            background-color: #e3f2fd !important;
        }
        
        .sortable-drag {
            opacity: 0.8;
        }
        
        .drag-handle {
            cursor: move;
            user-select: none;
        }
        
        .drag-handle:hover {
            color: #007bff !important;
        }
        
        .sortable-row:hover {
            background-color: #f8f9fa;
        }
        
        /* Стили для селектора сортировки */
        .sort-container {
            display: flex;
            align-items: center;
        }
        
        .sort-container label {
            margin-bottom: 0;
            white-space: nowrap;
        }
        
        #sortSelect {
            display: inline-block;
            width: auto;
            min-width: 180px;
        }
        
        @media (max-width: 768px) {
            .filters-container {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 10px !important;
            }
            
            .sort-container {
                width: 100%;
            }
            
            #sortSelect {
                width: 100%;
            }
        }
        
        /* Стили для фильтров по категориям в стиле админ-панели */
        .category-filters-wrapper {
            width: 100%;
        }
        
        .category-filters-main {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: flex-start;
        }
        
        .category-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        /* Стили для подкатегорий */
        .btn-group-filter-sub {
            background: #f0f4f8;
            border-radius: 0.375rem;
            padding: 0.25rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.25rem;
        }
        
        .btn-category-sub {
            font-size: 0.8125rem;
            padding: 0.4rem 0.875rem;
        }
        
        .btn-category-sub.active {
            background: white;
            color: #4e73df;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        }
        
        /* Анимация стрелки для родительских категорий */
        .btn-category-parent.active .category-arrow {
            transform: rotate(180deg);
        }
        
        .category-arrow {
            transition: transform 0.2s ease;
            margin-left: 0.5rem;
        }
        
        /* Плавное появление подкатегорий */
        .subcategories-container {
            animation: fadeInDown 0.3s ease;
        }
        
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Стили для бейджей с количеством товаров */
        .badge-light {
            background-color: rgba(255, 255, 255, 0.8);
            color: #5a6c7d;
            font-weight: 600;
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            margin-left: 0.5rem;
        }
        
        .btn-filter.active .badge-light {
            background-color: rgba(255, 255, 255, 0.95);
            color: #4e73df;
        }
        
        .btn-category-sub .badge-light {
            background-color: rgba(255, 255, 255, 0.7);
            font-size: 0.7rem;
            padding: 0.2rem 0.4rem;
        }
        
        .btn-category-sub.active .badge-light {
            background-color: rgba(255, 255, 255, 0.9);
            color: #4e73df;
        }
        
        /* Адаптивность */
        @media (max-width: 768px) {
            .category-filters-main {
                flex-direction: column;
            }
            
            .category-group {
                width: 100%;
            }
            
            .btn-group-filter,
            .btn-group-filter-sub {
                width: 100%;
            }
            
            .btn-group-filter .btn-filter,
            .btn-group-filter-sub .btn-filter {
                flex: 1;
                min-width: auto;
            }
        }
    </style>
@endsection

@section('js')
    <!-- SortableJS для drag-and-drop -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <script>
        $(document).ready(function () {
            // Восстановить сохраненную сортировку из localStorage
            var savedSort = localStorage.getItem('serviceAccountsSort') || 'sort_order-asc';
            
            // Определить начальную сортировку для DataTables
            var initialOrder = [];
            if (savedSort !== 'sort_order-asc') {
                var parts = savedSort.split('-');
                var column = parseInt(parts[0]);
                var direction = parts[1];
                initialOrder = [[column, direction]];
            }
            
            // DataTable с обновленными индексами колонок
            // Теперь колонка 0 - drag handle, колонка 1 - ID, и т.д.
            var table = $('#service-accounts-table').DataTable({
                "order": initialOrder.length > 0 ? initialOrder : [], // Сортировка по умолчанию или без сортировки
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/ru.json"
                },
                "pageLength": 25,
                "columnDefs": [
                    { "orderable": false, "targets": [0, 2, 10] }, // Drag handle, Изображение, Действия
                    { "orderable": true, "targets": [1, 4, 5, 6, 7, 8, 9] } // ID, Категория, Цена, В наличии, Продано, Статус, Дата
                ]
            });

            // Установить сохраненную сортировку в селекторе
            $('#sortSelect').val(savedSort);

            // Обработчик изменения сортировки
            $('#sortSelect').on('change', function() {
                var sortValue = $(this).val();
                applySort(sortValue);
                localStorage.setItem('serviceAccountsSort', sortValue);
            });

            // Функция применения сортировки
            function applySort(sortValue) {
                if (sortValue === 'sort_order-asc') {
                    // Для ручного порядка просто отключаем сортировку DataTables
                    table.order([]).draw(false);
                } else {
                    var parts = sortValue.split('-');
                    var column = parseInt(parts[0]);
                    var direction = parts[1];
                    
                    // Применяем сортировку в DataTables для отображения
                    table.order([[column, direction]]).draw();
                    
                    // Определяем поле для сортировки в БД
                    // Колонки: 0-drag, 1-ID, 2-изображение, 3-товар, 4-категория, 5-цена, 6-в наличии, 7-продано, 8-статус, 9-дата, 10-действия
                    var sortBy = '';
                    if (column === 1) {
                        sortBy = 'id';
                    } else if (column === 5) {
                        sortBy = 'price';
                    } else if (column === 9) {
                        sortBy = 'created_at';
                    }
                    
                    // Если это не ручной порядок, сохраняем сортировку в БД
                    if (sortBy) {
                        saveSortOrderToDatabase(sortBy, direction);
                    }
                }
            }

            // Функция для сохранения сортировки в базу данных
            function saveSortOrderToDatabase(sortBy, direction) {
                // Показать индикатор загрузки
                var loadingToast = $('<div class="alert alert-info" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 250px;">' +
                    '<i class="fas fa-spinner fa-spin mr-2"></i>Сохранение сортировки...</div>');
                $('body').append(loadingToast);

                $.ajax({
                    url: '{{ route("admin.service-accounts.apply-sort-order") }}',
                    method: 'POST',
                    data: {
                        sort_by: sortBy,
                        direction: direction,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        loadingToast.remove();
                        var successToast = $('<div class="alert alert-success" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 250px;">' +
                            '<i class="fas fa-check-circle mr-2"></i>Сортировка сохранена! Порядок обновлен для клиентов.</div>');
                        $('body').append(successToast);
                        setTimeout(function() {
                            successToast.fadeOut(function() {
                                $(this).remove();
                            });
                        }, 3000);
                    },
                    error: function(xhr) {
                        loadingToast.remove();
                        var errorToast = $('<div class="alert alert-danger" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 250px;">' +
                            '<i class="fas fa-exclamation-circle mr-2"></i>Ошибка при сохранении сортировки</div>');
                        $('body').append(errorToast);
                        setTimeout(function() {
                            errorToast.fadeOut(function() {
                                $(this).remove();
                            });
                        }, 3000);
                    }
                });
            }

            // Инициализация drag-and-drop для tbody
            var tbody = document.querySelector('#service-accounts-table tbody');
            var savedTableOrder = null; // Сохраняем текущую сортировку перед перетаскиванием
            
            var sortable = Sortable.create(tbody, {
                handle: '.drag-handle', // Иконка для перетаскивания
                animation: 150,
                ghostClass: 'sortable-ghost', // Класс для визуального эффекта при перетаскивании
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                onStart: function(evt) {
                    // Сохраняем текущую сортировку DataTables перед перетаскиванием
                    savedTableOrder = table.order();
                    // Временно отключаем сортировку DataTables для плавного перетаскивания
                    table.order([]).draw(false);
                },
                onEnd: function(evt) {
                    // Обновить порядок после перетаскивания
                    // Это всегда сохраняет ручной порядок
                    updateSortOrder();
                    // Обновляем селектор на "Ручной порядок" после перетаскивания
                    $('#sortSelect').val('sort_order-asc');
                    localStorage.setItem('serviceAccountsSort', 'sort_order-asc');
                    // Оставляем сортировку DataTables отключенной, чтобы показать ручной порядок
                    // savedTableOrder больше не нужен, так как мы переключаемся на ручной порядок
                }
            });

            // Функция для обновления порядка сортировки
            function updateSortOrder() {
                var items = [];
                $('#service-accounts-table tbody tr').each(function(index) {
                    var id = $(this).data('id');
                    if (id) {
                        items.push({
                            id: id,
                            sort_order: index + 1
                        });
                    }
                });

                if (items.length === 0) return;

                // Показать индикатор загрузки
                var loadingToast = $('<div class="alert alert-info" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 250px;">' +
                    '<i class="fas fa-spinner fa-spin mr-2"></i>Обновление порядка...</div>');
                $('body').append(loadingToast);

                $.ajax({
                    url: '{{ route("admin.service-accounts.update-sort-order") }}',
                    method: 'POST',
                    data: {
                        items: items,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        loadingToast.remove();
                        var successToast = $('<div class="alert alert-success" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 250px;">' +
                            '<i class="fas fa-check-circle mr-2"></i>Порядок обновлен!</div>');
                        $('body').append(successToast);
                        setTimeout(function() {
                            successToast.fadeOut(function() {
                                $(this).remove();
                            });
                        }, 2000);
                    },
                    error: function(xhr) {
                        loadingToast.remove();
                        var errorToast = $('<div class="alert alert-danger" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 250px;">' +
                            '<i class="fas fa-exclamation-circle mr-2"></i>Ошибка при обновлении порядка</div>');
                        $('body').append(errorToast);
                        setTimeout(function() {
                            errorToast.fadeOut(function() {
                                $(this).remove();
                            });
                        }, 3000);
                    }
                });
            }

            // Tooltips
            $('[data-toggle="tooltip"]').tooltip();

            // Фильтры по статусу (обновлены индексы колонок: теперь статус в колонке 8)
            $('#filterAll').on('click', function() {
                table.column(8).search('').draw();
                $('.btn-filter').removeClass('active');
                $(this).addClass('active');
                updateTotalCount();
            });

            $('#filterActive').on('click', function() {
                table.column(8).search('Активен').draw();
                $('.btn-filter').removeClass('active');
                $(this).addClass('active');
                updateTotalCount();
            });

            $('#filterInactive').on('click', function() {
                table.column(8).search('Неактивен').draw();
                $('.btn-filter').removeClass('active');
                $(this).addClass('active');
                updateTotalCount();
            });

            // Фильтры по категориям
            var currentCategoryFilter = 'all';
            var categorySubcategories = {}; // Храним подкатегории для каждой родительской категории
            var categoryFilterFunction = null; // Текущая функция фильтрации
            
            // Собираем информацию о подкатегориях
            $('.category-group').each(function() {
                var parentId = $(this).data('parent-category-id');
                var subcategoryIds = [];
                $(this).find('.btn-category-sub').each(function() {
                    subcategoryIds.push($(this).data('category-id'));
                });
                categorySubcategories[parentId] = subcategoryIds;
            });

            // Обработчик для кнопки "Все товары"
            $('#filterCategoryAll').on('click', function() {
                currentCategoryFilter = 'all';
                applyCategoryFilter('all');
                $('.btn-filter').removeClass('active');
                $(this).addClass('active');
                // Скрываем все подкатегории
                $('.subcategories-container').slideUp(200);
            });

            // Обработчики для родительских категорий
            $('.btn-category-parent').on('click', function(e) {
                e.stopPropagation();
                var categoryId = $(this).data('category-id');
                var isParent = $(this).data('is-parent') === true;
                var $categoryGroup = $(this).closest('.category-group');
                var $subcategoriesContainer = $categoryGroup.find('.subcategories-container');
                
                // Если подкатегории уже открыты, закрываем их и применяем фильтр по родительской категории
                if ($subcategoriesContainer.is(':visible')) {
                    $subcategoriesContainer.slideUp(200);
                    // Применяем фильтр по родительской категории
                    currentCategoryFilter = categoryId;
                    applyCategoryFilter(categoryId, isParent);
                    $('.btn-filter').not('.btn-category-sub').removeClass('active');
                    $(this).addClass('active');
                } else {
                    // Закрываем все другие подкатегории
                    $('.subcategories-container').not($subcategoriesContainer).slideUp(200);
                    // Сбрасываем активное состояние других родительских категорий
                    $('.btn-category-parent').not($(this)).removeClass('active');
                    // Открываем подкатегории текущей категории
                    $subcategoriesContainer.slideDown(200);
                    // Применяем фильтр по родительской категории
                    currentCategoryFilter = categoryId;
                    applyCategoryFilter(categoryId, isParent);
                    $('.btn-filter').not('.btn-category-sub').removeClass('active');
                    $(this).addClass('active');
                }
            });

            // Обработчики для подкатегорий
            $('.btn-category-sub').on('click', function(e) {
                e.stopPropagation();
                var categoryId = $(this).data('category-id');
                var isParent = $(this).data('is-parent') === false;
                
                currentCategoryFilter = categoryId;
                applyCategoryFilter(categoryId, isParent);
                
                $('.btn-filter').removeClass('active');
                $(this).addClass('active');
                // Также активируем родительскую категорию
                $(this).closest('.category-group').find('.btn-category-parent').addClass('active');
            });

            // Функция применения фильтра по категории
            function applyCategoryFilter(categoryId, isParent) {
                // Удаляем предыдущую функцию фильтрации, если она была
                if (categoryFilterFunction) {
                    var index = $.fn.dataTable.ext.search.indexOf(categoryFilterFunction);
                    if (index !== -1) {
                        $.fn.dataTable.ext.search.splice(index, 1);
                    }
                }
                
                // Создаем новую функцию фильтрации
                if (categoryId === 'all') {
                    // Показать все товары - не добавляем фильтр
                    categoryFilterFunction = null;
                } else if (categoryId === 'none') {
                    // Показать товары без категории
                    categoryFilterFunction = function(settings, data, dataIndex) {
                        var row = table.row(dataIndex).node();
                        var rowCategoryId = $(row).data('category-id');
                        return !rowCategoryId || rowCategoryId === '';
                    };
                    $.fn.dataTable.ext.search.push(categoryFilterFunction);
                } else if (isParent) {
                    // Показать товары из родительской категории и всех её подкатегорий
                    var subcategoryIds = categorySubcategories[categoryId] || [];
                    var allCategoryIds = [parseInt(categoryId), ...subcategoryIds.map(id => parseInt(id))];
                    
                    categoryFilterFunction = function(settings, data, dataIndex) {
                        var row = table.row(dataIndex).node();
                        var rowCategoryId = $(row).data('category-id');
                        var rowCategoryParentId = $(row).data('category-parent-id');
                        
                        if (!rowCategoryId) return false;
                        
                        // Показываем если категория товара совпадает с выбранной или её подкатегорией
                        return allCategoryIds.includes(parseInt(rowCategoryId)) || 
                               parseInt(rowCategoryParentId) === parseInt(categoryId);
                    };
                    $.fn.dataTable.ext.search.push(categoryFilterFunction);
                } else {
                    // Показать товары только из выбранной подкатегории
                    categoryFilterFunction = function(settings, data, dataIndex) {
                        var row = table.row(dataIndex).node();
                        var rowCategoryId = $(row).data('category-id');
                        if (!rowCategoryId) return false;
                        return parseInt(rowCategoryId) === parseInt(categoryId);
                    };
                    $.fn.dataTable.ext.search.push(categoryFilterFunction);
                }
                
                table.draw();
                updateTotalCount();
            }

            // Функция обновления счетчика товаров
            function updateTotalCount() {
                var visibleRows = table.rows({search: 'applied'}).count();
                $('#totalCount').text(visibleRows);
            }

            // Инициализация счетчика
            updateTotalCount();

            // Обработчик для кнопки "Сбросить все фильтры"
            $('#resetAllFilters').on('click', function() {
                // Сброс фильтра по категориям
                currentCategoryFilter = 'all';
                applyCategoryFilter('all');
                $('.btn-filter').removeClass('active');
                $('#filterCategoryAll').addClass('active');
                
                // Сброс фильтра по статусу
                table.column(8).search('').draw();
                $('.btn-filter').not('.btn-category-sub').removeClass('active');
                $('#filterAll').addClass('active');
                $('#filterCategoryAll').addClass('active');
                
                // Сброс сортировки на ручной порядок
                $('#sortSelect').val('sort_order-asc');
                localStorage.setItem('serviceAccountsSort', 'sort_order-asc');
                table.order([]).draw(false);
                
                // Скрываем все подкатегории
                $('.subcategories-container').slideUp(200);
                $('.btn-category-parent').removeClass('active');
                
                // Обновляем счетчик
                updateTotalCount();
                
                // Показываем уведомление
                var toast = $('<div class="alert alert-info" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 250px;">' +
                    '<i class="fas fa-check-circle mr-2"></i>Все фильтры сброшены</div>');
                $('body').append(toast);
                setTimeout(function() {
                    toast.fadeOut(function() {
                        $(this).remove();
                    });
                }, 2000);
            });

            // Обработчик для кнопки "Без категории"
            $('.btn-category-none').on('click', function() {
                var categoryId = 'none';
                currentCategoryFilter = categoryId;
                applyCategoryFilter(categoryId, false);
                
                $('.btn-filter').removeClass('active');
                $(this).addClass('active');
                $('.subcategories-container').slideUp(200);
                $('.btn-category-parent').removeClass('active');
            });

            // Update count when typing in import modals
            @foreach ($serviceAccounts as $serviceAccount)
                $('#import_data{{ $serviceAccount->id }}').on('input', function() {
                    const lines = this.value.split('\n').filter(line => line.trim() !== '');
                    $('#import_count{{ $serviceAccount->id }}').val(lines.length);
                });
            @endforeach

            // Автоскрытие алертов
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);
        });

        // Export function for index page
        function exportAccountsFromIndex(productId, totalQuantity) {
            const countStr = prompt('Сколько товаров выгрузить? (всего: ' + totalQuantity + ')', totalQuantity);
            
            if (countStr === null) return; // User cancelled
            
            const count = parseInt(countStr);
            if (isNaN(count) || count < 1) {
                alert('Введите корректное число');
                return;
            }

            // Show loading
            const btn = event.target.closest('button');
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btn.disabled = true;

            // Create hidden iframe for download
            const iframe = document.createElement('iframe');
            iframe.style.display = 'none';
            iframe.src = '/admin/service-accounts/' + productId + '/export?count=' + count;
            document.body.appendChild(iframe);

            // After download completes, reload page
            setTimeout(function() {
                document.body.removeChild(iframe);
                window.location.reload();
            }, 2000);
        }
    </script>
@endsection
