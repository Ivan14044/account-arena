@extends('adminlte::page')

@section('title', 'Сервисы')

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="m-0 font-weight-light">
                    Управление сервисами
                </h1>
                <p class="text-muted mb-0 mt-1">Настройка доступных сервисов и их параметров</p>
            </div>
            <div>
                <a href="{{ route('admin.services.create') }}" class="btn btn-primary btn-modern">
                    <i class="fas fa-plus mr-2"></i>Создать сервис
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
        <div class="col-lg-3 col-6">
            <div class="stat-card stat-card-primary">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-server"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Всего сервисов</div>
                        <div class="stat-value">{{ $services->count() }}</div>
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
                        <div class="stat-value">{{ $services->where('is_active', true)->count() }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="stat-card stat-card-danger">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-ban"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Неактивные</div>
                        <div class="stat-value">{{ $services->where('is_active', false)->count() }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="stat-card stat-card-info">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-sort-amount-down"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Позиций</div>
                        <div class="stat-value">{{ $services->max('position') ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Таблица -->
    <div class="card card-modern">
        <div class="card-header-modern">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Список сервисов</h5>
                    <small class="text-muted">Всего записей: {{ $services->count() }}</small>
                </div>
                <div class="filters-container">
                    <div class="btn-group btn-group-filter" role="group">
                        <button type="button" class="btn btn-filter active" id="filterAll">Все</button>
                        <button type="button" class="btn btn-filter" id="filterActive">Активные</button>
                        <button type="button" class="btn btn-filter" id="filterInactive">Неактивные</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body-modern">
            <div class="table-responsive">
                <table id="services-table" class="table table-hover modern-table">
                    <thead>
                        <tr>
                            <th style="width: 70px" class="text-center">Позиция</th>
                            <th style="width: 50px" class="text-center">ID</th>
                            <th style="min-width: 250px">Сервис</th>
                            <th>Статус</th>
                            <th>Дата создания</th>
                            <th style="width: 120px" class="text-center">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($services as $service)
                        <tr data-status="{{ $service->is_active ? 'active' : 'inactive' }}">
                            <td class="text-center align-middle">
                                <span class="badge badge-light font-weight-bold">{{ $service->position }}</span>
                            </td>
                            <td class="text-center align-middle">
                                <span class="badge badge-secondary">#{{ $service->id }}</span>
                            </td>
                            <td class="align-middle">
                                <div class="d-flex align-items-center">
                                    @if($service->logo)
                                        <img src="{{ asset($service->logo) }}"
                                             alt="{{ $service->admin_name }}"
                                             class="rounded mr-3"
                                             style="width: 40px; height: 40px; object-fit: contain; border: 1px solid #e3e6f0; padding: 4px;">
                                    @endif
                                    <div>
                                        <div class="font-weight-bold text-dark">{{ $service->admin_name }}</div>
                                        <small class="text-muted">{{ $service->code }}</small>
                                    </div>
                                </div>
                            </td>
                            <td class="align-middle">
                                @if($service->is_active)
                                    <span class="badge badge-success badge-modern">
                                        <i class="fas fa-check-circle mr-1"></i>Активен
                                    </span>
                                @else
                                    <span class="badge badge-danger badge-modern">
                                        <i class="fas fa-ban mr-1"></i>Неактивен
                                    </span>
                                @endif
                            </td>
                            <td class="align-middle">
                                <small class="text-muted">
                                    <i class="far fa-calendar-alt mr-1"></i>
                                    {{ \Carbon\Carbon::parse($service->created_at)->format('d.m.Y H:i') }}
                                </small>
                            </td>
                            <td class="text-center align-middle">
                                <div class="btn-group action-buttons" role="group">
                                    <a href="{{ route('admin.services.edit', $service) }}" 
                                       class="btn btn-sm btn-primary" 
                                       title="Редактировать"
                                       data-toggle="tooltip">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="btn btn-sm btn-danger" 
                                            data-toggle="modal" 
                                            data-target="#deleteModal{{ $service->id }}"
                                            title="Удалить"
                                            data-toggle-tooltip="tooltip">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>

                                <!-- Модальное окно удаления -->
                                <div class="modal fade" id="deleteModal{{ $service->id }}" tabindex="-1" role="dialog">
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
                                                <i class="fas fa-server fa-3x text-danger mb-3"></i>
                                                <h6 class="font-weight-bold">{{ $service->admin_name }}</h6>
                                                <p class="text-muted mb-0">Вы действительно хотите удалить этот сервис?</p>
                                                <small class="text-danger">Это действие нельзя отменить!</small>
                                            </div>
                                            <div class="modal-footer-modern justify-content-center">
                                                <form action="{{ route('admin.services.destroy', $service) }}" method="POST">
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
@endsection

@section('js')
    <script>
        $(document).ready(function () {
            // DataTable
            var table = $('#services-table').DataTable({
                "order": [[0, "asc"]],
                "columnDefs": [
                    { "orderable": false, "targets": 5 }
                ],
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/ru.json"
                },
                "pageLength": 25
            });

            // Tooltips
            $('[data-toggle="tooltip"]').tooltip();

            // Фильтры
            $('#filterAll').on('click', function() {
                table.column(3).search('').draw();
                $('.btn-filter').removeClass('active');
                $(this).addClass('active');
            });

            $('#filterActive').on('click', function() {
                table.column(3).search('Активен').draw();
                $('.btn-filter').removeClass('active');
                $(this).addClass('active');
            });

            $('#filterInactive').on('click', function() {
                table.column(3).search('Неактивен').draw();
                $('.btn-filter').removeClass('active');
                $(this).addClass('active');
            });

            // Автоскрытие алертов
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);
        });
    </script>
@endsection
