@extends('adminlte::page')

@section('title', 'Прокси')

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="m-0 font-weight-light">
                    Управление прокси
                </h1>
                <p class="text-muted mb-0 mt-1">Настройка и управление прокси-серверами</p>
            </div>
            <div>
                <a href="{{ route('admin.proxies.create') }}" class="btn btn-primary btn-modern">
                    <i class="fas fa-plus mr-2"></i>Добавить прокси
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
                        <i class="fas fa-network-wired"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Всего прокси</div>
                        <div class="stat-value">{{ $proxies->count() }}</div>
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
                        <div class="stat-value">{{ $proxies->where('is_active', true)->count() }}</div>
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
                        <div class="stat-value">{{ $proxies->where('is_active', false)->count() }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="stat-card stat-card-info">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-globe"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Стран</div>
                        <div class="stat-value">{{ $proxies->pluck('country')->unique()->count() }}</div>
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
                    <h5 class="mb-0">Список прокси</h5>
                    <small class="text-muted">Всего записей: {{ $proxies->count() }}</small>
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
                <table id="proxies-table" class="table table-hover modern-table">
                    <thead>
                        <tr>
                            <th style="width: 50px" class="text-center">ID</th>
                            <th style="min-width: 300px">Прокси</th>
                            <th>Статус</th>
                            <th class="text-center">Страна</th>
                            <th class="text-center">Дата создания</th>
                            <th class="text-center">Истекает</th>
                            <th style="width: 120px" class="text-center">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($proxies as $proxy)
                        <tr data-status="{{ $proxy->is_active ? 'active' : 'inactive' }}">
                            <td class="text-center align-middle">
                                <span class="badge badge-secondary">#{{ $proxy->id }}</span>
                            </td>
                            <td class="align-middle">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-network-wired text-primary mr-3" style="font-size: 1.25rem;"></i>
                                    <div>
                                        <code style="font-size: 0.9rem; background: #f8f9fc; padding: 0.25rem 0.5rem; border-radius: 0.25rem; border: 1px solid #e3e6f0;">
                                            {{ $proxy->getFullProxy() }}
                                        </code>
                                    </div>
                                </div>
                            </td>
                            <td class="align-middle">
                                @if($proxy->is_active)
                                    <span class="badge badge-success badge-modern">
                                        <i class="fas fa-check-circle mr-1"></i>Активен
                                    </span>
                                @else
                                    <span class="badge badge-danger badge-modern">
                                        <i class="fas fa-ban mr-1"></i>Неактивен
                                    </span>
                                @endif
                            </td>
                            <td class="text-center align-middle">
                                <span class="badge badge-light font-weight-bold" style="font-size: 0.875rem;">
                                    {{ strtoupper($proxy->country) }}
                                </span>
                            </td>
                            <td class="text-center align-middle">
                                <small class="text-muted">
                                    <i class="far fa-calendar-alt mr-1"></i>
                                    {{ \Carbon\Carbon::parse($proxy->created_at)->format('d.m.Y H:i') }}
                                </small>
                            </td>
                            <td class="text-center align-middle">
                                @if($proxy->expiring_at)
                                    @php
                                        $expiringAt = \Carbon\Carbon::parse($proxy->expiring_at);
                                        $isExpiringSoon = $expiringAt->diffInDays(now()) <= 7 && $expiringAt->isFuture();
                                        $isExpired = $expiringAt->isPast();
                                    @endphp
                                    <small class="{{ $isExpired ? 'text-danger' : ($isExpiringSoon ? 'text-warning' : 'text-muted') }}">
                                        <i class="far fa-clock mr-1"></i>
                                        {{ $expiringAt->format('d.m.Y H:i') }}
                                    </small>
                                    @if($isExpiringSoon && !$isExpired)
                                        <br><small class="badge badge-warning badge-modern">Истекает скоро</small>
                                    @elseif($isExpired)
                                        <br><small class="badge badge-danger badge-modern">Истек</small>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center align-middle">
                                <div class="btn-group action-buttons" role="group">
                                    <a href="{{ route('admin.proxies.edit', $proxy) }}" 
                                       class="btn btn-sm btn-primary" 
                                       title="Редактировать"
                                       data-toggle="tooltip">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="btn btn-sm btn-danger" 
                                            data-toggle="modal" 
                                            data-target="#deleteModal{{ $proxy->id }}"
                                            title="Удалить"
                                            data-toggle-tooltip="tooltip">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>

                                <!-- Модальное окно удаления -->
                                <div class="modal fade" id="deleteModal{{ $proxy->id }}" tabindex="-1" role="dialog">
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
                                                <i class="fas fa-network-wired fa-3x text-danger mb-3"></i>
                                                <h6 class="font-weight-bold">Прокси</h6>
                                                <p class="text-muted mb-0"><code>{{ $proxy->getFullProxy() }}</code></p>
                                                <small class="text-danger">Это действие нельзя отменить!</small>
                                            </div>
                                            <div class="modal-footer-modern justify-content-center">
                                                <form action="{{ route('admin.proxies.destroy', $proxy) }}" method="POST">
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
            var table = $('#proxies-table').DataTable({
                "order": [[0, "desc"]],
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/ru.json"
                },
                "pageLength": 25,
                "columnDefs": [
                    { "orderable": false, "targets": 6 }
                ]
            });

            // Tooltips
            $('[data-toggle="tooltip"]').tooltip();

            // Фильтры
            $('#filterAll').on('click', function() {
                table.column(2).search('').draw();
                $('.btn-filter').removeClass('active');
                $(this).addClass('active');
            });

            $('#filterActive').on('click', function() {
                table.column(2).search('Активен').draw();
                $('.btn-filter').removeClass('active');
                $(this).addClass('active');
            });

            $('#filterInactive').on('click', function() {
                table.column(2).search('Неактивен').draw();
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
