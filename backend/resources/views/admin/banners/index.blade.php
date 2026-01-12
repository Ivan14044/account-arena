@extends('adminlte::page')

@section('title', 'Рекламные баннеры')

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="m-0 font-weight-light">
                    Рекламные баннеры
                </h1>
                <p class="text-muted mb-0 mt-1">Управление баннерами на главной странице платформы</p>
            </div>
            <div>
                <a href="{{ route('admin.banners.create') }}" class="btn btn-primary btn-modern">
                    <i class="fas fa-plus mr-2"></i>Создать баннер
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

    <!-- Статистика -->
    <div class="row mb-4">
        <div class="col-lg-4 col-6">
            <div class="stat-card stat-card-primary stat-card-compact">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-images"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Всего баннеров</div>
                        <div class="stat-value">{{ $statistics['total'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-6">
            <div class="stat-card stat-card-success stat-card-compact">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Активны сейчас</div>
                        <div class="stat-value">{{ $statistics['active'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-6">
            <div class="stat-card stat-card-danger stat-card-compact">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-eye-slash"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Выключены</div>
                        <div class="stat-value">{{ $statistics['inactive'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Информационный блок -->
    <div class="alert alert-modern alert-info mb-4">
        <div class="d-flex">
            <div class="mr-3">
                <i class="fas fa-info-circle fa-2x"></i>
            </div>
            <div>
                <h5 class="alert-heading font-weight-bold mb-1">Информация о позициях:</h5>
                <p class="mb-0"><strong>home_top_wide:</strong> Верхний широкий баннер (1 шт). <strong>home_top:</strong> Сетка баннеров под широким (4 шт, порядок 1-4).</p>
            </div>
        </div>
    </div>

    <div class="card card-modern">
        <div class="card-header-modern p-0">
            <div class="d-flex justify-content-between align-items-center p-2">
                <ul class="nav nav-pills">
                    <li class="nav-item"><a class="nav-link active" href="#all" data-toggle="tab" id="filterAll">Все</a></li>
                    <li class="nav-item"><a class="nav-link" href="#active" data-toggle="tab" id="filterActiveTab">Активные</a></li>
                    <li class="nav-item"><a class="nav-link" href="#inactive" data-toggle="tab" id="filterInactiveTab">Выключены</a></li>
                </ul>
                <div class="card-tools ml-auto pr-2">
                    <form action="{{ route('admin.banners.index') }}" method="GET" class="form-inline" id="searchForm">
                        <div class="input-group input-group-sm">
                            <input type="text" name="search" class="form-control" placeholder="Поиск..." value="{{ request('search') }}">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-default"><i class="fas fa-search"></i></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="card-body-modern">
            <div class="table-responsive">
                <table id="banners-table" class="table table-hover modern-table">
                    <thead>
                    <tr>
                        <th style="width: 60px" class="text-center">ID</th>
                        <th style="width: 150px" class="text-center">Изображение</th>
                        <th>Название</th>
                        <th class="text-center">Тип</th>
                        <th class="text-center">Позиция</th>
                        <th class="text-center">Статус</th>
                        <th class="text-center">Период показа</th>
                        <th style="width: 120px" class="text-center">Действия</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($banners as $banner)
                        <tr>
                            <td class="text-center align-middle">
                                <span class="badge badge-secondary">#{{ $banner->id }}</span>
                            </td>
                            <td class="text-center align-middle">
                                @if($banner->image_url)
                                    <img src="{{ $banner->image_url }}" alt="{{ $banner->title }}" 
                                         class="rounded shadow-sm" style="max-width: 120px; max-height: 60px; object-fit: contain; border: 1px solid #e3e6f0;">
                                @else
                                    <div class="rounded d-flex align-items-center justify-content-center mx-auto" style="width: 100px; height: 50px; background: #f8f9fc; border: 1px solid #e3e6f0;">
                                        <i class="fas fa-image text-muted"></i>
                                    </div>
                                @endif
                            </td>
                            <td class="align-middle">
                                <div class="font-weight-bold">{{ $banner->title }}</div>
                                @if($banner->link)
                                    <a href="{{ $banner->link }}" target="_blank" class="text-xs text-primary">
                                        <i class="fas fa-external-link-alt mr-1"></i>Ссылка
                                    </a>
                                @endif
                            </td>
                            <td class="text-center align-middle">
                                @if($banner->position === 'home_top_wide')
                                    <span class="badge badge-info badge-modern">Широкий</span>
                                @else
                                    <span class="badge badge-secondary badge-modern">Обычный</span>
                                @endif
                            </td>
                            <td class="text-center align-middle">
                                <span class="badge badge-primary badge-modern">
                                    {{ $banner->order }}
                                </span>
                            </td>
                            <td class="text-center align-middle">
                                @if($banner->isCurrentlyActive())
                                    <span class="badge badge-success badge-modern">Активен</span>
                                @elseif($banner->is_active)
                                    <span class="badge badge-warning badge-modern">Запланирован</span>
                                @else
                                    <span class="badge badge-secondary badge-modern">Выключен</span>
                                @endif
                            </td>
                            <td class="text-center align-middle text-muted">
                                @if($banner->start_date || $banner->end_date)
                                    <small>
                                        @if($banner->start_date)
                                            <i class="far fa-calendar-alt mr-1"></i>{{ $banner->start_date->format('d.m.Y') }}
                                        @endif
                                        @if($banner->end_date)
                                            <br><i class="fas fa-arrow-right mx-1" style="font-size: 0.7rem;"></i>{{ $banner->end_date->format('d.m.Y') }}
                                        @endif
                                    </small>
                                @else
                                    <span class="text-muted small">Всегда</span>
                                @endif
                            </td>
                            <td class="text-center align-middle">
                                <div class="action-buttons justify-content-center">
                                    <a href="{{ route('admin.banners.edit', $banner) }}" 
                                       class="btn btn-sm btn-primary" 
                                       title="Редактировать"
                                       data-toggle="tooltip">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <form action="{{ route('admin.banners.destroy', $banner) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" 
                                                title="Удалить"
                                                data-toggle-tooltip="tooltip"
                                                onclick="return confirm('Вы уверены?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @if($banners->hasPages())
            <div class="card-footer-modern bg-white p-3 border-top">
                {{ $banners->links() }}
            </div>
        @endif
    </div>
@endsection

@section('css')
    @include('admin.layouts.modern-styles')
@endsection

@section('js')
    <script>
        $(document).ready(function() {
            var table = $('#banners-table').DataTable({
                "order": [[0, "desc"]],
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Russian.json"
                },
                "paging": false,
                "info": false,
                "searching": true,
                "dom": 't', // Only table, we use custom search
                "columnDefs": [
                    { "orderable": false, "targets": [1, 7] }
                ]
            });

            // Custom search logic if needed
            $('#searchForm').on('submit', function(e) {
                // If using server side pagination, let it submit.
                // If using client side DataTables, prevent default and search
                // table.search($('input[name="search"]').val()).draw();
                // e.preventDefault();
            });

            // Фильтры по табу
            $('#filterAll').on('click', function() {
                table.column(5).search('').draw();
            });

            $('#filterActiveTab').on('click', function() {
                table.column(5).search('Активен').draw();
            });

            $('#filterInactiveTab').on('click', function() {
                table.column(5).search('Выключен').draw();
            });

            $('[data-toggle="tooltip"]').tooltip();
            $('[data-toggle-tooltip="tooltip"]').tooltip();
        });
    </script>
@endsection

