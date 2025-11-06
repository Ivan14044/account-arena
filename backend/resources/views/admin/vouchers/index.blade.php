@extends('adminlte::page')

@section('title', 'Ваучеры')

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="m-0 font-weight-light">
                    Управление ваучерами
                </h1>
                <p class="text-muted mb-0 mt-1">Создание и управление промо-ваучерами для пополнения баланса</p>
            </div>
            <div>
                <a href="{{ route('admin.vouchers.create') }}" class="btn btn-primary btn-modern">
                    <i class="fas fa-plus mr-2"></i>Создать ваучер
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
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Всего ваучеров</div>
                        <div class="stat-value">{{ $vouchers->count() }}</div>
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
                        <div class="stat-value">{{ $vouchers->where('is_active', true)->where('used_at', null)->count() }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="stat-card stat-card-warning">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-check-double"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Использованные</div>
                        <div class="stat-value">{{ $vouchers->whereNotNull('used_at')->count() }}</div>
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
                        <div class="stat-label">Общая сумма</div>
                        <div class="stat-value">${{ number_format($vouchers->sum('amount'), 0) }}</div>
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
                    <h5 class="mb-0">Список ваучеров</h5>
                    <small class="text-muted">Всего записей: {{ $vouchers->count() }}</small>
                </div>
                <div class="filters-container">
                    <div class="btn-group btn-group-filter" role="group">
                        <button type="button" class="btn btn-filter active" id="filterAll">Все</button>
                        <button type="button" class="btn btn-filter" id="filterActive">Активные</button>
                        <button type="button" class="btn btn-filter" id="filterUsed">Использованные</button>
                        <button type="button" class="btn btn-filter" id="filterInactive">Неактивные</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body-modern">
            <div class="table-responsive">
                <table id="vouchers-table" class="table table-hover modern-table">
                    <thead>
                        <tr>
                            <th style="width: 60px" class="text-center">ID</th>
                            <th style="min-width: 180px">Код ваучера</th>
                            <th>Сумма</th>
                            <th>Валюта</th>
                            <th>Пользователь</th>
                            <th>Использован</th>
                            <th>Статус</th>
                            <th style="width: 120px" class="text-center">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($vouchers as $voucher)
                        <tr data-status="{{ $voucher->isUsed() ? 'used' : ($voucher->is_active ? 'active' : 'inactive') }}">
                            <td class="text-center align-middle">
                                <span class="badge badge-secondary">#{{ $voucher->id }}</span>
                            </td>
                            <td class="align-middle">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-ticket-alt text-primary mr-2" style="font-size: 1.25rem;"></i>
                                    <div>
                                        <code style="font-size: 1rem; background: #f8f9fc; padding: 0.25rem 0.5rem; border-radius: 0.25rem; border: 1px solid #e3e6f0;">
                                            {{ $voucher->code }}
                                        </code>
                                    </div>
                                </div>
                            </td>
                            <td class="align-middle">
                                <strong class="text-success" style="font-size: 1.1rem;">
                                    ${{ number_format($voucher->amount, 2) }}
                                </strong>
                            </td>
                            <td class="align-middle">
                                <span class="badge badge-light font-weight-bold">{{ strtoupper($voucher->currency) }}</span>
                            </td>
                            <td class="align-middle">
                                @if($voucher->user)
                                    <a href="{{ route('admin.users.edit', $voucher->user) }}" class="text-decoration-none">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle mr-2" style="width: 30px; height: 30px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.75rem;">
                                                {{ strtoupper(substr($voucher->user->name ?? $voucher->user->email, 0, 1)) }}
                                            </div>
                                            <div>
                                                <div class="font-weight-bold text-dark" style="font-size: 0.875rem;">{{ $voucher->user->name }}</div>
                                                <small class="text-muted">{{ $voucher->user->email }}</small>
                                            </div>
                                        </div>
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="align-middle">
                                @if($voucher->used_at)
                                    <small class="text-success">
                                        <i class="far fa-calendar-check mr-1"></i>
                                        {{ $voucher->used_at->format('d.m.Y H:i') }}
                                    </small>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="align-middle">
                                @if($voucher->isUsed())
                                    <span class="badge badge-warning badge-modern">
                                        <i class="fas fa-check-double mr-1"></i>Использован
                                    </span>
                                @elseif($voucher->is_active)
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
                                <div class="btn-group action-buttons" role="group">
                                    <a href="{{ route('admin.vouchers.edit', $voucher) }}" 
                                       class="btn btn-sm btn-primary" 
                                       title="Редактировать"
                                       data-toggle="tooltip">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="btn btn-sm btn-danger" 
                                            data-toggle="modal" 
                                            data-target="#deleteModal{{ $voucher->id }}"
                                            title="Удалить"
                                            data-toggle-tooltip="tooltip">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>

                                <!-- Модальное окно удаления -->
                                <div class="modal fade" id="deleteModal{{ $voucher->id }}" tabindex="-1" role="dialog">
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
                                                <i class="fas fa-ticket-alt fa-3x text-danger mb-3"></i>
                                                <h6 class="font-weight-bold">Ваучер <code>{{ $voucher->code }}</code></h6>
                                                <p class="text-muted mb-0">Сумма: <strong class="text-success">${{ number_format($voucher->amount, 2) }}</strong></p>
                                                <small class="text-danger">Это действие нельзя отменить!</small>
                                            </div>
                                            <div class="modal-footer-modern justify-content-center">
                                                <form action="{{ route('admin.vouchers.destroy', $voucher) }}" method="POST">
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
            var table = $('#vouchers-table').DataTable({
                "order": [[0, "desc"]],
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/ru.json"
                },
                "pageLength": 25,
                "columnDefs": [
                    { "orderable": false, "targets": 7 }
                ]
            });

            // Tooltips
            $('[data-toggle="tooltip"]').tooltip();

            // Фильтры
            $('#filterAll').on('click', function() {
                table.column(6).search('').draw();
                $('.btn-filter').removeClass('active');
                $(this).addClass('active');
            });

            $('#filterActive').on('click', function() {
                table.column(6).search('Активен').draw();
                $('.btn-filter').removeClass('active');
                $(this).addClass('active');
            });

            $('#filterUsed').on('click', function() {
                table.column(6).search('Использован').draw();
                $('.btn-filter').removeClass('active');
                $(this).addClass('active');
            });

            $('#filterInactive').on('click', function() {
                table.column(6).search('Неактивен').draw();
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
