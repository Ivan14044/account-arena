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
            <div class="stat-card stat-card-primary stat-card-compact">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Всего товаров</div>
                        <div class="stat-value">{{ \App\Models\ServiceAccount::count() }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="stat-card stat-card-success stat-card-compact">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Активные</div>
                        <div class="stat-value">{{ \App\Models\ServiceAccount::where('is_active', true)->count() }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="stat-card stat-card-warning stat-card-compact">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Продано</div>
                        <div class="stat-value">{{ \App\Models\ServiceAccount::sum('used') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="stat-card stat-card-info stat-card-compact">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Общая стоимость</div>
                        <div class="stat-value">${{ number_format(\App\Models\ServiceAccount::sum('price'), 0) }}</div>
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
                            <span class="badge badge-light ml-2">{{ \App\Models\ServiceAccount::count() }}</span>
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
            <div class="card-header-content">
                <div class="card-header-title">
                    <h5 class="mb-0">Список товаров</h5>
                    <small class="text-muted">Всего записей: <span id="totalCount">{{ $serviceAccounts->total() }}</span></small>
                </div>
                <div class="card-header-controls">
                    <div class="filters-container">
                        <div class="btn-group btn-group-filter" role="group">
                            <button type="button" class="btn btn-filter active" id="filterAll">Все</button>
                            <button type="button" class="btn btn-filter" id="filterActive">Активные</button>
                            <button type="button" class="btn btn-filter" id="filterInactive">Неактивные</button>
                        </div>
                    </div>
                    <div class="sort-container">
                        <label for="sortSelect" class="sort-label">
                            <i class="fas fa-sort mr-1"></i>Сортировка:
                        </label>
                        <select id="sortSelect" class="form-control form-control-sm sort-select">
                            <option value="sort_order-asc" selected>Ручной порядок</option>
                            <option value="1-asc">ID (по возрастанию)</option>
                            <option value="1-desc">ID (по убыванию)</option>
                            <option value="5-asc">Цена (по возрастанию)</option>
                            <option value="5-desc">Цена (по убыванию)</option>
                            <option value="10-desc">Дата создания (новые сначала)</option>
                            <option value="10-asc">Дата создания (старые сначала)</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body-modern">
            <div class="top-scrollbar-wrapper" style="overflow-x: auto; overflow-y: hidden; height: 16px; display: none;">
                <div class="top-scrollbar-content" style="height: 16px;"></div>
            </div>
            <div class="table-responsive table-container-modern">
                <table id="service-accounts-table" class="table table-hover modern-table table-sm">
                    <thead>
                        <tr>
                            <th style="width: 40px" class="text-center">
                                <input type="checkbox" id="select-all-products" title="Выбрать все">
                            </th>
                            <th style="width: 40px" class="text-center">
                                <i class="fas fa-grip-vertical text-muted" title="Перетащите для изменения порядка"></i>
                            </th>
                            <th style="width: 65px" class="text-center">ID</th>
                            <th style="width: 135px" class="text-center">Артикул</th>
                            <th style="width: 75px" class="text-center">Фото</th>
                            <th style="min-width: 180px">Товар</th>
                            <th style="min-width: 120px" class="text-center">Категория</th>
                            <th style="min-width: 100px" class="text-center">Цена</th>
                            <th style="min-width: 115px" class="text-center">В наличии</th>
                            <th style="min-width: 100px" class="text-center">Продано</th>
                            <th style="min-width: 120px">Статус</th>
                            <th style="width: 180px" class="text-center">Действия</th>
                            <th style="width: 140px" class="text-center">Дата создания</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($serviceAccounts as $serviceAccount)
                        @php
                            $availableCount = $serviceAccount->getAvailableStock();
                            $totalQuantity = $serviceAccount->total_qty_from_json ?? (is_array($serviceAccount->accounts_data) ? count($serviceAccount->accounts_data) : 0);
                            $soldCount = $serviceAccount->used ?? 0;
                            $categoryId = $serviceAccount->category_id ?? null;
                            $categoryName = null;
                            if ($serviceAccount->category) {
                                $categoryName = $serviceAccount->category->admin_name;
                            }
                            // Определяем parent_id категории для фильтрации
                            $categoryParentId = $serviceAccount->category ? ($serviceAccount->category->parent_id ?? null) : null;
                        @endphp
                        <tr data-id="{{ $serviceAccount->id }}" 
                            data-category-id="{{ $categoryId ?? '' }}" 
                            data-category-parent-id="{{ $categoryParentId ?? '' }}"
                            class="sortable-row">
                            <td class="text-center align-middle">
                                <input type="checkbox" class="product-checkbox" value="{{ $serviceAccount->id }}" data-product-id="{{ $serviceAccount->id }}">
                            </td>
                            <td class="text-center align-middle drag-handle" style="cursor: move;">
                                <i class="fas fa-grip-vertical text-muted"></i>
                            </td>
                            <td class="text-center align-middle">
                                <span class="badge badge-secondary">#{{ $serviceAccount->id }}</span>
                            </td>
                            <td class="text-center align-middle">
                                <code class="text-dark" style="font-size: 0.875rem; background: #f8f9fc; padding: 0.25rem 0.5rem; border-radius: 0.25rem;">
                                    {{ $serviceAccount->sku ?? '—' }}
                                </code>
                            </td>
                            <td class="text-center align-middle">
                                @if($serviceAccount->image_url)
                                    <img src="{{ $serviceAccount->image_url }}" 
                                         alt="{{ $serviceAccount->title }}" 
                                         class="rounded"
                                         style="width: 50px; height: 50px; object-fit: cover; border: 1px solid #e3e6f0;">
                                @else
                                    <img src="{{ asset('img/logo_trans.webp') }}" 
                                         alt="Логотип" 
                                         class="rounded"
                                         style="width: 50px; height: 50px; object-fit: contain; padding: 5px; background: #f8f9fc; border: 1px solid #e3e6f0; opacity: 0.6;">
                                @endif
                            </td>
                            <td class="align-middle">
                                <div style="word-break: break-word; white-space: normal;">
                                    <div class="font-weight-bold text-dark" style="line-height: 1.2;">
                                        {{ $serviceAccount->title ?: 'Без названия' }}
                                    </div>
                                    @if($serviceAccount->description)
                                        <small class="text-muted d-block mt-1" style="line-height: 1.2;">
                                            {{ Str::limit(strip_tags($serviceAccount->description), 60) }}
                                        </small>
                                    @endif
                                </div>
                            </td>
                            <td class="text-center align-middle">
                                @if($categoryId && $categoryName)
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
                                @if($serviceAccount->requiresManualDelivery())
                                    @if($serviceAccount->is_active)
                                        <span class="badge badge-success badge-modern font-weight-bold" style="font-size: 0.875rem;">
                                            В наличии
                                        </span>
                                    @else
                                        <span class="badge badge-secondary badge-modern">
                                            Нет в наличии
                                        </span>
                                    @endif
                                @else
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
                                <div class="btn-group action-buttons" role="group">
                                    <a href="{{ route('admin.service-accounts.edit', $serviceAccount) }}"
                                       class="btn btn-sm btn-primary" 
                                       title="Редактировать"
                                       data-toggle="tooltip">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    @if($totalQuantity > 0)
                                    <button class="btn btn-sm btn-success btn-export" 
                                            data-id="{{ $serviceAccount->id }}"
                                            data-title="{{ $serviceAccount->title }}"
                                            data-count="{{ $availableCount }}"
                                            title="Экспорт товаров"
                                            data-toggle="tooltip">
                                        <i class="fas fa-download"></i>
                                    </button>
                                    @endif

                                    <button class="btn btn-sm btn-info btn-notes" 
                                            data-id="{{ $serviceAccount->id }}"
                                            data-title="{{ $serviceAccount->title }}"
                                            data-notes="{{ e($serviceAccount->admin_notes ?? '') }}"
                                            title="Заметки: {{ Str::limit($serviceAccount->admin_notes ?? 'Нет заметок', 50) }}"
                                            data-toggle="tooltip">
                                        <i class="fas fa-comment-alt {{ $serviceAccount->admin_notes ? '' : 'opacity-50' }}"></i>
                                        @if($serviceAccount->admin_notes)
                                            <span class="badge badge-warning badge-pill ml-1" style="font-size: 0.6rem;">!</span>
                                        @endif
                                    </button>

                                    <button class="btn btn-sm btn-info btn-import" 
                                            data-id="{{ $serviceAccount->id }}"
                                            data-title="{{ $serviceAccount->title }}"
                                            data-action="{{ route('admin.service-accounts.import', $serviceAccount) }}"
                                            title="Импорт товаров"
                                            data-toggle="tooltip">
                                        <i class="fas fa-upload"></i>
                                    </button>

                                    <button class="btn btn-sm btn-danger btn-delete" 
                                            data-id="{{ $serviceAccount->id }}"
                                            data-title="{{ $serviceAccount->title }}"
                                            data-count="{{ $availableCount }}"
                                            data-action="{{ route('admin.service-accounts.destroy', $serviceAccount) }}"
                                            title="Удалить"
                                            data-toggle="tooltip">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </td>
                            <td class="text-center align-middle">
                                <small class="text-muted">
                                    <i class="far fa-calendar-alt mr-1"></i>
                                    {{ \Carbon\Carbon::parse($serviceAccount->created_at)->format('d.m.Y H:i') }}
                                </small>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($serviceAccounts->hasPages())
                <div class="px-4 py-3 border-top">
                    <div class="d-flex justify-content-center">
                        {{ $serviceAccounts->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- ЕДИНЫЕ ДИНАМИЧЕСКИЕ МОДАЛЬНЫЕ ОКНА -->
    
    <!-- Модальное окно удаления -->
    <div class="modal fade" id="singleDeleteModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content modal-modern">
                <div class="modal-header modal-header-modern bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle mr-2 text-danger"></i>
                        Подтверждение удаления
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body modal-body-modern text-center">
                    <i class="fas fa-box-open fa-3x text-danger mb-3"></i>
                    <h6 class="font-weight-bold" id="delete-item-title">Товар</h6>
                    <p class="text-muted mb-0">В наличии: <span id="delete-item-count">0</span> шт.</p>
                    <small class="text-danger">Это действие нельзя отменить!</small>
                </div>
                <div class="modal-footer modal-footer-modern justify-content-center">
                    <form id="delete-item-form" method="POST" class="d-inline">
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

    <!-- Модальное окно экспорта -->
    <div class="modal fade" id="singleExportModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content modal-modern">
                <div class="modal-header modal-header-modern bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-download mr-2 text-success"></i>
                        Экспорт товаров
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body modal-body-modern">
                    <p class="mb-3">Сколько товаров выгрузить из "<strong id="export-item-title"></strong>"?</p>
                    <div class="form-group">
                        <label>Количество (всего доступно: <strong id="export-max-count">0</strong>)</label>
                        <input type="number" class="form-control" id="export-item-quantity" min="1" required>
                        <small class="form-text text-muted">Введите количество товаров для экспорта</small>
                    </div>
                </div>
                <div class="modal-footer modal-footer-modern justify-content-center">
                    <button type="button" class="btn btn-success btn-modern" id="confirm-single-export">
                        <i class="fas fa-download mr-2"></i>Экспортировать
                    </button>
                    <button type="button" class="btn btn-secondary btn-modern" data-dismiss="modal">
                        Отмена
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно импорта -->
    <div class="modal fade" id="singleImportModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content modal-modern">
                <div class="modal-header modal-header-modern bg-info text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-upload mr-2 text-info"></i>
                        Импорт товаров: <span id="import-item-title"></span>
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="import-item-form" method="POST">
                    @csrf
                    <div class="modal-body modal-body-modern">
                        <div class="form-group-modern">
                            <label class="form-label-modern">Данные для загрузки</label>
                            <textarea name="import_data" id="import-data-textarea" 
                                      class="form-control form-control-modern font-monospace" 
                                      rows="15" placeholder="Вставьте данные товаров. Каждая строка = один товар" 
                                      required></textarea>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle mr-1"></i>
                                Каждая строка будет добавлена как один товар.
                            </small>
                        </div>
                        <div class="form-group-modern mb-0">
                            <label class="form-label-modern">Количество строк для загрузки:</label>
                            <input type="number" id="import-lines-count" class="form-control form-control-modern" value="0" readonly>
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

    <!-- Панель массовых действий -->
    <div id="bulk-actions-panel" class="bulk-actions-panel" style="display: none;">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>Выбрано товаров: <span id="selected-count">0</span></strong>
                </div>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-success" data-action="activate" title="Активировать выбранные товары">
                        <i class="fas fa-check-circle mr-1"></i>Активировать
                    </button>
                    <button type="button" class="btn btn-sm btn-warning" data-action="deactivate" title="Скрыть выбранные товары">
                        <i class="fas fa-ban mr-1"></i>Скрыть
                    </button>
                    <button type="button" class="btn btn-sm btn-primary" data-action="change-price" title="Изменить цену">
                        <i class="fas fa-dollar-sign mr-1"></i>Изменить цену
                    </button>
                    <button type="button" class="btn btn-sm btn-info" data-action="change-category" title="Изменить категорию">
                        <i class="fas fa-folder mr-1"></i>Категория
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary" data-action="change-delivery-type" title="Изменить тип выдачи">
                        <i class="fas fa-hand-paper mr-1"></i>Тип выдачи
                    </button>
                    <button type="button" class="btn btn-sm btn-danger" data-action="delete" title="Удалить выбранные товары">
                        <i class="fas fa-trash mr-1"></i>Удалить
                    </button>
                    <button type="button" class="btn btn-sm btn-light" id="clear-selection" title="Снять выделение">
                        <i class="fas fa-times mr-1"></i>Отменить
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно для изменения цены -->
    <div class="modal fade" id="changePriceModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Изменить цену товаров</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="change-price-form">
                        <div class="form-group">
                            <label>Действие</label>
                            <select class="form-control" id="price-action" required>
                                <option value="increase">Увеличить на процент</option>
                                <option value="decrease">Уменьшить на процент</option>
                                <option value="set">Установить фиксированную цену</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label id="price-label">Процент изменения (%)</label>
                            <input type="number" class="form-control" id="price-value" min="0" max="1000" step="0.01" required>
                            <small class="form-text text-muted" id="price-hint">Введите процент для изменения цены</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-primary" id="confirm-change-price">Применить</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно для изменения категории -->
    <div class="modal fade" id="changeCategoryModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Изменить категорию товаров</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="change-category-form">
                        <div class="form-group">
                            <label>Родительская категория</label>
                            <select class="form-control" id="parent-category-select">
                                <option value="">Без категории</option>
                                @foreach($parentCategories as $category)
                                    <option value="{{ $category->id }}">{{ $category->admin_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Подкатегория</label>
                            <select class="form-control" id="subcategory-select" name="category_id">
                                <option value="">Без категории</option>
                            </select>
                            <small class="form-text text-muted">Выберите подкатегорию или оставьте только родительскую категорию</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-primary" id="confirm-change-category">Применить</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно для изменения типа выдачи -->
    <div class="modal fade" id="changeDeliveryTypeModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Изменить тип выдачи товаров</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="change-delivery-type-form">
                        <div class="form-group">
                            <label>Тип выдачи</label>
                            <select class="form-control" id="delivery-type-select" required>
                                <option value="automatic">Автоматическая выдача</option>
                                <option value="manual">Ручная выдача</option>
                            </select>
                            <small class="form-text text-muted">При автоматической выдаче товар выдается сразу после оплаты. При ручной выдаче товар выдается менеджером вручную.</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-primary" id="confirm-change-delivery-type">Применить</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно подтверждения удаления -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Подтверждение удаления
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Вы уверены, что хотите удалить <strong id="delete-count">0</strong> выбранных товаров?</p>
                    <p class="text-danger"><strong>Это действие нельзя отменить!</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-danger" id="confirm-delete">Удалить</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно для заметок администратора -->
    <div class="modal fade" id="adminNotesModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content text-left">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-comment-alt mr-2 text-info"></i>Заметки администратора
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label class="text-muted small mb-1">Товар:</label>
                        <div id="product-notes-title" class="font-weight-bold text-dark"></div>
                    </div>
                    <div class="form-group">
                        <label for="admin-notes-textarea" class="font-weight-600">Внутренняя заметка:</label>
                        <textarea id="admin-notes-textarea" class="form-control" rows="6" placeholder="Напишите здесь важную информацию о товаре, которая будет видна только администраторам..."></textarea>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" id="copy-notes-btn">
                        <i class="fas fa-copy mr-1"></i>Копировать
                    </button>
                    <div>
                        <button type="button" class="btn btn-light" data-dismiss="modal">Отмена</button>
                        <button type="button" class="btn btn-primary" id="save-notes-btn">
                            <i class="fas fa-save mr-1"></i>Сохранить
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('css')
    @include('admin.layouts.modern-styles')
    <style>
        /* Панель массовых действий */
        .bulk-actions-panel {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #ffffff;
            border-top: 2px solid #e3e6f0;
            box-shadow: 0 -2px 8px rgba(0,0,0,0.08);
            z-index: 1050;
        }

        .bulk-actions-panel .container-fluid {
            padding: 1rem 1.5rem;
        }

        .bulk-actions-panel strong {
            color: #2c3e50;
            font-weight: 600;
            font-size: 0.9375rem;
        }

        .bulk-actions-panel #selected-count {
            color: #4e73df;
            font-weight: 700;
        }

        .bulk-actions-panel .btn {
            margin-left: 0.25rem;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .bulk-actions-panel .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.12);
        }

        /* Стили для колонки артикула */
        #service-accounts-table td code {
            font-family: 'Courier New', Courier, monospace;
            font-weight: 500;
        }

        /* Чекбоксы в таблице */
        .product-checkbox {
            cursor: pointer;
            width: 18px;
            height: 18px;
        }

        .product-checkbox:checked {
            background-color: #4e73df;
            border-color: #4e73df;
        }

        /* Выделение выбранных строк */
        tr.selected {
            background-color: #f0f7ff !important;
        }

        tr.selected:hover {
            background-color: #e0efff !important;
        }
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

        /* Более компактная таблица */
        .modern-table {
            font-size: 0.875rem;
        }
        
        .modern-table th, 
        .modern-table td {
            padding: 0.5rem 0.4rem !important;
            vertical-align: middle !important;
        }

        .modern-table thead th {
            font-size: 0.8125rem;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            font-weight: 700;
        }

        .badge-modern {
            padding: 0.35em 0.6em;
            font-size: 80%;
        }

        .action-buttons .btn-sm {
            padding: 0.2rem 0.4rem;
            font-size: 0.8125rem;
        }

        /* Верхний скроллбар */
        .top-scrollbar-wrapper {
            margin-bottom: 2px;
            border-bottom: 1px solid #f1f3f9;
        }
        
        .top-scrollbar-wrapper::-webkit-scrollbar {
            height: 10px;
        }
        
        .top-scrollbar-wrapper::-webkit-scrollbar-track {
            background: #f8f9fc;
        }
        
        .top-scrollbar-wrapper::-webkit-scrollbar-thumb {
            background: #d1d3e2;
            border-radius: 5px;
        }
        
        .top-scrollbar-wrapper::-webkit-scrollbar-thumb:hover {
            background: #b7b9cc;
        }

        .table-container-modern::-webkit-scrollbar {
            height: 10px;
        }
        
        .table-container-modern::-webkit-scrollbar-track {
            background: #f8f9fc;
        }
        
        .table-container-modern::-webkit-scrollbar-thumb {
            background: #d1d3e2;
            border-radius: 5px;
        }
        
        .table-container-modern::-webkit-scrollbar-thumb:hover {
            background: #b7b9cc;
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
                "paging": false,
                "info": false,
                "columnDefs": [
                    { "orderable": false, "targets": [0, 2, 9] }, // Drag handle, Изображение, Действия
                    { "orderable": true, "targets": [1, 4, 5, 6, 7, 8, 10] } // ID, Категория, Цена, В наличии, Продано, Статус, Дата
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
                    // Колонки: 0-drag, 1-ID, 2-артикул, 3-изображение, 4-товар, 5-категория, 6-цена, 7-в наличии, 8-продано, 9-статус, 10-дата, 11-действия
                    var sortBy = '';
                    if (column === 1) {
                        sortBy = 'id';
                    } else if (column === 5) {
                        sortBy = 'price';
                    } else if (column === 10) {
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

            // Фильтры по статусу (обновлены индексы колонок: теперь статус в колонке 9)
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

            // Автоскрытие алертов
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);

            // ============================================
            // ОБРАБОТКА ДИНАМИЧЕСКИХ МОДАЛОК
            // ============================================

            // Удаление
            $('.btn-delete').on('click', function() {
                const id = $(this).data('id');
                const title = $(this).data('title');
                const count = $(this).data('count');
                const action = $(this).data('action');

                $('#delete-item-title').text(title);
                $('#delete-item-count').text(count);
                $('#delete-item-form').attr('action', action);
                $('#singleDeleteModal').modal('show');
            });

            // Экспорт
            let currentExportId = null;
            $('.btn-export').on('click', function() {
                currentExportId = $(this).data('id');
                const title = $(this).data('title');
                const count = $(this).data('count');

                $('#export-item-title').text(title);
                $('#export-max-count').text(count);
                $('#export-item-quantity').val(count).attr('max', count);
                $('#singleExportModal').modal('show');
            });

            $('#confirm-single-export').on('click', function() {
                const count = parseInt($('#export-item-quantity').val());
                const max = parseInt($('#export-item-quantity').attr('max'));

                if (isNaN(count) || count < 1 || count > max) {
                    alert('Введите корректное число от 1 до ' + max);
                    return;
                }

                $('#singleExportModal').modal('hide');
                
                // Используем существующую логику экспорта
                const exportBtn = $('.btn-export[data-id="' + currentExportId + '"]');
                const originalHtml = exportBtn.html();
                exportBtn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

                const iframe = document.createElement('iframe');
                iframe.style.display = 'none';
                iframe.src = '/admin/service-accounts/' + currentExportId + '/export?count=' + count;
                document.body.appendChild(iframe);

                setTimeout(function() {
                    document.body.removeChild(iframe);
                    exportBtn.html(originalHtml).prop('disabled', false);
                    window.location.reload();
                }, 2000);
            });

            // Импорт
            $('.btn-import').on('click', function() {
                const title = $(this).data('title');
                const action = $(this).data('action');

                $('#import-item-title').text(title);
                $('#import-item-form').attr('action', action);
                $('#import-data-textarea').val('');
                $('#import-lines-count').val(0);
                $('#singleImportModal').modal('show');
            });

            $('#import-data-textarea').on('input', function() {
                const lines = this.value.split('\n').filter(line => line.trim() !== '');
                $('#import-lines-count').val(lines.length);
            });

            // СИНХРОНИЗАЦИЯ СКРОЛЛБАРОВ
            const tableContainer = $('.table-container-modern');
            const topScrollbarWrapper = $('.top-scrollbar-wrapper');
            const topScrollbarContent = $('.top-scrollbar-content');

            function syncScrollbars() {
                const scrollWidth = tableContainer[0].scrollWidth;
                const clientWidth = tableContainer[0].clientWidth;

                if (scrollWidth > clientWidth) {
                    topScrollbarWrapper.show();
                    topScrollbarContent.width(scrollWidth);
                } else {
                    topScrollbarWrapper.hide();
                }
            }

            // Инициализация при загрузке
            syncScrollbars();

            // Обновление при изменении размера окна
            $(window).on('resize', syncScrollbars);

            // Синхронизация прокрутки
            topScrollbarWrapper.on('scroll', function() {
                tableContainer.scrollLeft($(this).scrollLeft());
            });

            tableContainer.on('scroll', function() {
                topScrollbarWrapper.scrollLeft($(this).scrollLeft());
            });

            // Также обновляем при отрисовке таблицы (если данных стало больше/меньше)
            table.on('draw', function() {
                setTimeout(syncScrollbars, 100);
            });
        });

        // ============================================
        // МАССОВОЕ УПРАВЛЕНИЕ ТОВАРАМИ
        // ============================================
        
        let selectedProducts = new Set();
        const bulkPanel = $('#bulk-actions-panel');
        const selectedCountSpan = $('#selected-count');
        
        // Обработчик "Выбрать все"
        $('#select-all-products').on('change', function() {
            const isChecked = $(this).is(':checked');
            $('.product-checkbox').prop('checked', isChecked);
            
            if (isChecked) {
                $('.product-checkbox').each(function() {
                    selectedProducts.add(parseInt($(this).val()));
                    $(this).closest('tr').addClass('selected');
                });
            } else {
                selectedProducts.clear();
                $('tr').removeClass('selected');
            }
            
            updateBulkPanel();
        });
        
        // Обработчик выбора отдельного товара
        $(document).on('change', '.product-checkbox', function() {
            const productId = parseInt($(this).val());
            const isChecked = $(this).is(':checked');
            
            if (isChecked) {
                selectedProducts.add(productId);
                $(this).closest('tr').addClass('selected');
            } else {
                selectedProducts.delete(productId);
                $(this).closest('tr').removeClass('selected');
                $('#select-all-products').prop('checked', false);
            }
            
            updateBulkPanel();
        });
        
        // Обновление панели массовых действий
        function updateBulkPanel() {
            const count = selectedProducts.size;
            selectedCountSpan.text(count);
            
            if (count > 0) {
                bulkPanel.fadeIn(200);
                // Добавляем отступ снизу для контента, чтобы панель не перекрывала
                $('body').css('padding-bottom', '60px');
            } else {
                bulkPanel.fadeOut(200);
                $('body').css('padding-bottom', '0');
            }
        }
        
        // Кнопка "Отменить выбор"
        $('#clear-selection').on('click', function() {
            selectedProducts.clear();
            $('.product-checkbox').prop('checked', false);
            $('#select-all-products').prop('checked', false);
            $('tr').removeClass('selected');
            updateBulkPanel();
        });
        
        // Обработчики массовых действий
        $('[data-action="activate"]').on('click', function() {
            if (selectedProducts.size === 0) {
                alert('Выберите товары для активации');
                return;
            }
            performBulkAction('activate', {});
        });
        
        $('[data-action="deactivate"]').on('click', function() {
            if (selectedProducts.size === 0) {
                alert('Выберите товары для скрытия');
                return;
            }
            performBulkAction('deactivate', {});
        });
        
        $('[data-action="change-price"]').on('click', function() {
            if (selectedProducts.size === 0) {
                alert('Выберите товары для изменения цены');
                return;
            }
            $('#changePriceModal').modal('show');
        });
        
        $('[data-action="change-category"]').on('click', function() {
            if (selectedProducts.size === 0) {
                alert('Выберите товары для изменения категории');
                return;
            }
            $('#changeCategoryModal').modal('show');
        });
        
        $('[data-action="change-delivery-type"]').on('click', function() {
            if (selectedProducts.size === 0) {
                alert('Выберите товары для изменения типа выдачи');
                return;
            }
            $('#changeDeliveryTypeModal').modal('show');
        });
        
        $('[data-action="delete"]').on('click', function() {
            if (selectedProducts.size === 0) {
                alert('Выберите товары для удаления');
                return;
            }
            $('#delete-count').text(selectedProducts.size);
            $('#deleteConfirmModal').modal('show');
        });
        
        // Обработчик изменения типа действия для цены
        $('#price-action').on('change', function() {
            const action = $(this).val();
            const label = $('#price-label');
            const input = $('#price-value');
            const hint = $('#price-hint');
            
            if (action === 'set') {
                label.text('Новая цена (USD)');
                input.attr('min', '0.01');
                input.attr('step', '0.01');
                hint.text('Введите новую фиксированную цену');
            } else {
                label.text('Процент изменения (%)');
                input.attr('min', '0');
                input.attr('max', '1000');
                input.attr('step', '0.01');
                hint.text(action === 'increase' ? 'Введите процент увеличения цены' : 'Введите процент уменьшения цены');
            }
        });
        
        // Загрузка подкатегорий при выборе родительской категории
        $('#parent-category-select').on('change', function() {
            const parentId = $(this).val();
            const subcategorySelect = $('#subcategory-select');
            subcategorySelect.html('<option value="">Без категории</option>');
            
            if (parentId) {
                // Загружаем подкатегории через AJAX или используем данные из Blade
                @php
                    $subcategoriesByParent = [];
                    foreach($subcategories as $sub) {
                        $subcategoriesByParent[$sub->parent_id][] = $sub;
                    }
                @endphp
                
                const subcategories = @json($subcategoriesByParent);
                
                if (subcategories[parentId]) {
                    subcategories[parentId].forEach(function(sub) {
                        subcategorySelect.append(`<option value="${sub.id}">${sub.admin_name}</option>`);
                    });
                }
            }
        });
        
        // Подтверждение изменения цены
        $('#confirm-change-price').on('click', function() {
            const action = $('#price-action').val();
            const value = parseFloat($('#price-value').val());
            
            if (!value || value < 0) {
                alert('Введите корректное значение');
                return;
            }
            
            if (action !== 'set' && value > 1000) {
                alert('Процент не может быть больше 1000%');
                return;
            }
            
            if (action === 'set' && value < 0.01) {
                alert('Минимальная цена: 0.01 USD');
                return;
            }
            
            performBulkAction('change_price', {
                action_type: action,
                value: value
            });
            
            $('#changePriceModal').modal('hide');
            $('#change-price-form')[0].reset();
        });
        
        // Подтверждение изменения категории
        $('#confirm-change-category').on('click', function() {
            const categoryId = $('#subcategory-select').val() || $('#parent-category-select').val() || null;
            
            performBulkAction('change_category', {
                category_id: categoryId
            });
            
            $('#changeCategoryModal').modal('hide');
            $('#change-category-form')[0].reset();
        });
        
        // Подтверждение изменения типа выдачи
        $('#confirm-change-delivery-type').on('click', function() {
            const deliveryType = $('#delivery-type-select').val();
            
            performBulkAction('change_delivery_type', {
                delivery_type: deliveryType
            });
            
            $('#changeDeliveryTypeModal').modal('hide');
        });
        
        // Подтверждение удаления
        $('#confirm-delete').on('click', function() {
            performBulkAction('delete', {});
            $('#deleteConfirmModal').modal('hide');
        });
        
        // Основная функция выполнения массовых действий
        function performBulkAction(action, params) {
            if (selectedProducts.size === 0) {
                alert('Выберите товары для выполнения действия');
                return;
            }
            
            const ids = Array.from(selectedProducts);
            
            // Сохраняем оригинальный HTML всех кнопок
            const actionButtons = {};
            $('[data-action]').each(function() {
                const actionName = $(this).data('action');
                actionButtons[actionName] = $(this).html();
            });
            
            // Показываем индикатор загрузки
            const loadingHtml = '<i class="fas fa-spinner fa-spin mr-2"></i>Обработка...';
            $('[data-action]').prop('disabled', true);
            $('[data-action="' + action + '"]').html(loadingHtml);
            
            $.ajax({
                url: '{{ route("admin.service-accounts.bulk-action") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    action: action,
                    ids: ids,
                    ...params
                },
                success: function(response) {
                    if (response.success) {
                        // Показываем уведомление
                        const message = response.message || 'Операция выполнена успешно';
                        showNotification('success', message);
                        
                        // Очищаем выбор
                        selectedProducts.clear();
                        $('.product-checkbox').prop('checked', false);
                        $('#select-all-products').prop('checked', false);
                        $('tr').removeClass('selected');
                        updateBulkPanel();
                        
                        // Обновляем страницу через 1 секунду
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        showNotification('error', response.message || 'Произошла ошибка');
                        // Восстанавливаем кнопки
                        $('[data-action]').prop('disabled', false);
                        for (const [actionName, html] of Object.entries(actionButtons)) {
                            $('[data-action="' + actionName + '"]').html(html);
                        }
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'Произошла ошибка при выполнении операции';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                        // Обработка ошибок валидации
                        const errors = xhr.responseJSON.errors;
                        errorMessage = Object.values(errors).flat().join(', ');
                    }
                    showNotification('error', errorMessage);
                    // Восстанавливаем кнопки
                    $('[data-action]').prop('disabled', false);
                    for (const [actionName, html] of Object.entries(actionButtons)) {
                        $('[data-action="' + actionName + '"]').html(html);
                    }
                }
            });
        }
        
        // Функция показа уведомлений
        function showNotification(type, message) {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
            
            const alertHtml = `
                <div class="alert ${alertClass} alert-dismissible fade show" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                    <i class="fas ${icon} mr-2"></i>${message}
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>
            `;
            
            $('body').append(alertHtml);
            
            setTimeout(function() {
                $('.alert').fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
        
        // Export function for index page
        function confirmExport(productId, totalQuantity) {
            const countInput = document.getElementById('exportCount' + productId);
            if (!countInput) return;
            
            const count = parseInt(countInput.value);
            if (isNaN(count) || count < 1 || count > totalQuantity) {
                alert('Введите корректное число от 1 до ' + totalQuantity);
                countInput.focus();
                return;
            }

            // Close modal
            $('#exportModal' + productId).modal('hide');

            // Show loading on export button
            const exportBtn = document.querySelector('[data-target="#exportModal' + productId + '"]');
            if (exportBtn) {
                const originalHtml = exportBtn.innerHTML;
                exportBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                exportBtn.disabled = true;

                // Create hidden iframe for download
                const iframe = document.createElement('iframe');
                iframe.style.display = 'none';
                iframe.src = '/admin/service-accounts/' + productId + '/export?count=' + count;
                document.body.appendChild(iframe);

                // After download completes, reload page
                setTimeout(function() {
                    document.body.removeChild(iframe);
                    exportBtn.innerHTML = originalHtml;
                    exportBtn.disabled = false;
                    window.location.reload();
                }, 2000);
            } else {
                // Fallback if button not found
                const iframe = document.createElement('iframe');
                iframe.style.display = 'none';
                iframe.src = '/admin/service-accounts/' + productId + '/export?count=' + count;
                document.body.appendChild(iframe);

                setTimeout(function() {
                    document.body.removeChild(iframe);
                    window.location.reload();
                }, 2000);
            }
        }

        // --- Заметки администратора ---
        let currentNoteProductId = null;
        let originalNoteBtn = null;

        // Открытие модального окна заметок
        $(document).on('click', '.btn-notes', function() {
            const btn = $(this);
            currentNoteProductId = btn.data('id');
            const title = btn.data('title');
            const notes = btn.attr('data-notes') || ''; // Используем attr чтобы получать актуальное значение
            originalNoteBtn = btn;

            $('#product-notes-title').text(title);
            $('#admin-notes-textarea').val(notes);
            $('#adminNotesModal').modal('show');
        });

        // Кнопка сохранения заметок
        $('#save-notes-btn').on('click', function() {
            const notes = $('#admin-notes-textarea').val();
            const btn = $(this);
            const originalHtml = btn.innerHTML;

            btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

            $.ajax({
                url: `/admin/service-accounts/${currentNoteProductId}/update-notes`,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    admin_notes: notes
                },
                success: function(response) {
                    if (response.success) {
                        // Обновляем данные в кнопке на странице без перезагрузки
                        originalNoteBtn.attr('data-notes', notes);
                        
                        // Обновляем иконку (делаем ее яркой если есть текст)
                        const icon = originalNoteBtn.find('i');
                        if (notes.trim()) {
                            icon.removeClass('opacity-50');
                        } else {
                            icon.addClass('opacity-50');
                        }

                        $('#adminNotesModal').modal('hide');
                        showNotification('success', response.message);
                    }
                },
                error: function(xhr) {
                    console.error('Ошибка сохранения заметок:', xhr);
                    showNotification('error', 'Ошибка при сохранении заметок');
                },
                complete: function() {
                    btn.html('<i class="fas fa-save mr-1"></i>Сохранить').prop('disabled', false);
                }
            });
        });

        // Кнопка копирования
        $('#copy-notes-btn').on('click', function() {
            const textarea = document.getElementById('admin-notes-textarea');
            textarea.select();
            document.execCommand('copy');
            
            const originalText = $(this).html();
            $(this).html('<i class="fas fa-check mr-1"></i>Скопировано!');
            setTimeout(() => {
                $(this).html(originalText);
            }, 2000);
        });
    </script>
@endsection
