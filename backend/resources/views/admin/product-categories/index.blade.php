@extends('adminlte::page')

@section('title', 'Категории товаров')

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <h1 class="m-0 font-weight-light">Категории товаров</h1>
                <p class="text-muted mb-0 mt-1 d-none d-md-block">Управление основными категориями каталога</p>
            </div>
            <div class="d-flex flex-column flex-md-row gap-2 w-100 w-md-auto">
                <a href="{{ route('admin.product-subcategories.index') }}" class="btn btn-info btn-modern w-100 w-md-auto">
                    <i class="fas fa-list mr-2"></i>Подкатегории
                </a>
                <a href="{{ route('admin.product-categories.create') }}" class="btn btn-primary btn-modern w-100 w-md-auto">
                    <i class="fas fa-plus mr-2"></i>Добавить
                </a>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="small-box bg-white shadow-sm border-0 h-100 mb-0">
                <div class="inner p-3">
                    <h3 class="font-weight-light">{{ $stats['total_categories'] }}</h3>
                    <p class="text-muted mb-0">Категорий (основных)</p>
                </div>
                <div class="icon" style="top: 15px; right: 15px;">
                    <i class="fas fa-folder text-primary opacity-25"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="small-box bg-white shadow-sm border-0 h-100 mb-0">
                <div class="inner p-3">
                    <h3 class="font-weight-light">{{ $stats['total_subcategories'] }}</h3>
                    <p class="text-muted mb-0">Подкатегорий</p>
                </div>
                <div class="icon" style="top: 15px; right: 15px;">
                    <i class="fas fa-tags text-info opacity-25"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="small-box bg-white shadow-sm border-0 h-100 mb-0">
                <div class="inner p-3">
                    <h3 class="font-weight-light text-success">{{ $stats['total_products'] }}</h3>
                    <p class="text-muted mb-0">Всего активных товаров</p>
                </div>
                <div class="icon" style="top: 15px; right: 15px;">
                    <i class="fas fa-box-open text-success opacity-25"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            @if (session('success'))
                <div class="alert alert-modern alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-modern alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            @endif

            <div class="card card-modern">
                <div class="card-header-modern">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 font-weight-normal">Список основных категорий</h5>
                        <div>
                            <span class="badge badge-light border text-muted px-2 py-1">
                                <i class="fas fa-info-circle mr-1"></i>
                                Нажмите на название, чтобы редактировать
                            </span>
                        </div>
                    </div>
                </div>
                <div class="card-body-modern">
                    <div class="table-responsive">
                        <table id="categories-table" class="table table-hover modern-table">
                            <thead>
                                <tr>
                                    <th style="width: 60px" class="text-center">ID</th>
                                    <th style="width: 80px" class="text-center">Фото</th>
                                    <th>Название</th>
                                    <th>Подкатегории</th>
                                    <th style="width: 120px" class="text-center">Товаров</th>
                                    <th style="width: 150px" class="text-center">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($categories as $category)
                                    <tr>
                                        <td class="text-center align-middle">
                                            <span class="text-muted small">#{{ $category->id }}</span>
                                        </td>
                                        <td class="text-center align-middle">
                                            @if($category->image_url)
                                                <img src="{{ $category->image_url }}" 
                                                     alt="{{ $category->admin_name }}" 
                                                     class="img-fluid rounded elevation-1"
                                                     style="width: 45px; height: 45px; object-fit: cover; border: 1px solid #eee;"
                                                     onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2250%22 height=%2250%22%3E%3Crect width=%2250%22 height=%2250%22 fill=%22%23f8f9fa%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 font-family=%22Arial%22 font-size=%2220%22 fill=%22%23dee2e6%22%3E%3F%3C/text%3E%3C/svg%3E';">
                                            @else
                                                <div class="d-inline-flex align-items-center justify-content-center bg-light rounded shadow-sm" 
                                                     style="width: 45px; height: 45px; border: 1px solid #eee;">
                                                    <i class="fas fa-image text-muted opacity-50"></i>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="align-middle">
                                            <a href="{{ route('admin.product-categories.edit', $category) }}" class="text-dark font-weight-bold d-block">
                                                {{ $category->admin_name }}
                                            </a>
                                            <small class="text-muted">/{{ $category->slug }}</small>
                                        </td>
                                        <td class="align-middle">
                                            @if($category->children->count() > 0)
                                                <div class="d-flex flex-wrap gap-1">
                                                    @foreach($category->children as $subcategory)
                                                        <a href="{{ route('admin.product-subcategories.edit', $subcategory) }}" 
                                                           class="badge badge-light border mr-1 mb-1 text-dark font-weight-normal py-1 px-2"
                                                           style="font-size: 0.8rem;">
                                                            {{ $subcategory->admin_name }}
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-muted small">Нет подкатегорий</span>
                                            @endif
                                            <a href="{{ route('admin.product-subcategories.create', ['parent_id' => $category->id]) }}" 
                                               class="text-info small mt-1 d-inline-block">
                                                <i class="fas fa-plus-circle mr-1"></i>Добавить подкатегорию
                                            </a>
                                        </td>
                                        <td class="text-center align-middle">
                                            <div class="d-flex flex-column align-items-center">
                                                <span class="badge {{ $category->total_products_count > 0 ? 'badge-success' : 'badge-light' }} badge-pill px-3 py-1" style="font-size: 0.9rem;">
                                                    {{ $category->total_products_count }}
                                                </span>
                                                @if($category->total_products_count > 0 && $category->children_count > 0)
                                                    <small class="text-muted mt-1" style="font-size: 0.7rem;">
                                                        из них {{ $category->products_count }} напрямую
                                                    </small>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-center align-middle">
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('admin.product-categories.edit', $category) }}" 
                                                   class="btn btn-sm btn-outline-primary"
                                                   title="Редактировать"
                                                   data-toggle="tooltip">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button class="btn btn-sm btn-outline-danger btn-delete-category" 
                                                        data-name="{{ $category->admin_name }}"
                                                        data-children-count="{{ $category->children_count }}"
                                                        data-products-count="{{ $category->total_products_count }}"
                                                        data-action="{{ route('admin.product-categories.destroy', $category) }}"
                                                        title="Удалить"
                                                        data-toggle="tooltip">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Единое модальное окно для удаления --}}
    <div class="modal fade" id="singleDeleteModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-danger text-white border-0">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Подтверждение удаления
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body py-4">
                    <div id="category-warning" class="alert alert-warning border-0 shadow-none mb-3 d-none">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong>Внимание!</strong> У этой категории есть <span id="children-count"></span> подкатегорий. Они также будут удалены.
                    </div>
                    <div id="products-warning" class="alert alert-info border-0 shadow-none mb-3 d-none">
                        <i class="fas fa-box-open mr-2"></i>
                        <strong>Информация:</strong> В этой категории (с подкатегориями) <span id="products-count"></span> товаров. Они станут "без категории".
                    </div>
                    <div class="text-center mb-3">
                        <i class="fas fa-folder-minus fa-3x text-danger mb-3"></i>
                        <h6 class="font-weight-bold" id="delete-category-name"></h6>
                    </div>
                    <p class="text-center mb-0">
                        Вы действительно хотите удалить эту категорию?<br>
                        <small class="text-danger">Это действие нельзя отменить!</small>
                    </p>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <form id="delete-category-form" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash-alt mr-2"></i>Да, удалить
                        </button>
                    </form>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-2"></i>Отмена
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('css')
    @include('admin.layouts.modern-styles')
@endsection

@section('js')
    <script>
        $(function () {
            var table = $('#categories-table').DataTable({
                "order": [[0, "asc"]],
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/ru.json"
                },
                "columnDefs": [
                    { "orderable": false, "targets": [1, 4] } // Image and Actions columns
                ]
            });

            $('[data-toggle="tooltip"]').tooltip();

            // ДИНАМИЧЕСКИЕ МОДАЛКИ
            $('.btn-delete-category').on('click', function() {
                const name = $(this).data('name');
                const childrenCount = parseInt($(this).data('children-count'));
                const productsCount = parseInt($(this).data('products-count'));
                const action = $(this).data('action');

                $('#delete-category-name').text(name);
                $('#delete-category-form').attr('action', action);

                if (childrenCount > 0) {
                    $('#children-count').text(childrenCount);
                    $('#category-warning').removeClass('d-none');
                } else {
                    $('#category-warning').addClass('d-none');
                }

                if (productsCount > 0) {
                    $('#products-count').text(productsCount);
                    $('#products-warning').removeClass('d-none');
                } else {
                    $('#products-warning').addClass('d-none');
                }

                $('#singleDeleteModal').modal('show');
            });
        });
    </script>
@endsection
