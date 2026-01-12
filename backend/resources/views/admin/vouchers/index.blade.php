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
            <div class="stat-card stat-card-primary stat-card-compact">
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
            <div class="stat-card stat-card-success stat-card-compact">
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
            <div class="stat-card stat-card-warning stat-card-compact">
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
            <div class="stat-card stat-card-info stat-card-compact">
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
        <div class="card-header-modern p-0">
            <ul class="nav nav-pills p-2">
                <li class="nav-item"><a class="nav-link active" href="#all" data-toggle="tab" id="filterAll">Все</a></li>
                <li class="nav-item"><a class="nav-link" href="#active" data-toggle="tab" id="filterActive">Активные</a></li>
                <li class="nav-item"><a class="nav-link" href="#used" data-toggle="tab" id="filterUsed">Использованные</a></li>
                <li class="nav-item"><a class="nav-link" href="#inactive" data-toggle="tab" id="filterInactive">Неактивные</a></li>
            </ul>
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
                        <tr>
                            <td class="text-center align-middle">
                                <span class="badge badge-light font-weight-bold">#{{ $voucher->id }}</span>
                            </td>
                            <td class="align-middle">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-ticket-alt text-primary mr-2" style="font-size: 1.1rem; opacity: 0.7;"></i>
                                    <code>{{ $voucher->code }}</code>
                                </div>
                            </td>
                            <td class="align-middle font-weight-bold text-success">
                                ${{ number_format($voucher->amount, 2) }}
                            </td>
                            <td class="align-middle">
                                <span class="badge badge-light border">{{ strtoupper($voucher->currency) }}</span>
                            </td>
                            <td class="align-middle">
                                @if($voucher->user)
                                    <a href="{{ route('admin.users.edit', $voucher->user) }}" class="text-decoration-none">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle-sm mr-2" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                                {{ strtoupper(substr($voucher->user->name ?? $voucher->user->email, 0, 1)) }}
                                            </div>
                                            <div>
                                                <div class="font-weight-bold text-dark small">{{ $voucher->user->name }}</div>
                                                <div class="text-muted" style="font-size: 0.7rem;">{{ $voucher->user->email }}</div>
                                            </div>
                                        </div>
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="align-middle text-muted" data-order="{{ $voucher->used_at ? strtotime($voucher->used_at) : 0 }}">
                                @if($voucher->used_at)
                                    <small>
                                        <i class="far fa-calendar-check mr-1"></i>
                                        {{ $voucher->used_at->format('d.m.Y') }}
                                        <br>
                                        <i class="far fa-clock mr-1"></i>
                                        {{ $voucher->used_at->format('H:i') }}
                                    </small>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td class="align-middle">
                                @if($voucher->isUsed())
                                    <span class="badge badge-warning badge-modern">Использован</span>
                                @elseif($voucher->is_active)
                                    <span class="badge badge-success badge-modern">Активен</span>
                                @else
                                    <span class="badge badge-danger badge-modern">Неактивен</span>
                                @endif
                            </td>
                            <td class="text-center align-middle">
                                <div class="action-buttons justify-content-center">
                                    <a href="{{ route('admin.vouchers.edit', $voucher) }}" 
                                       class="btn btn-sm btn-primary" 
                                       title="Редактировать"
                                       data-toggle="tooltip">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="btn btn-sm btn-danger btn-delete-voucher" 
                                            data-code="{{ $voucher->code }}"
                                            data-amount="{{ number_format($voucher->amount, 2) }}"
                                            data-action="{{ route('admin.vouchers.destroy', $voucher) }}"
                                            title="Удалить"
                                            data-toggle-tooltip="tooltip">
                                        <i class="fas fa-trash-alt"></i>
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
                <div class="modal-body py-4 text-center">
                    <i class="fas fa-ticket-alt fa-3x text-danger mb-3"></i>
                    <h6 class="font-weight-bold">Ваучер <code id="delete-voucher-code"></code></h6>
                    <p class="mb-0">Сумма: <strong class="text-success">$<span id="delete-voucher-amount"></span></strong></p>
                    <p class="mt-3">Вы действительно хотите удалить этот ваучер?<br>
                    <small class="text-danger">Это действие нельзя отменить!</small></p>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <form id="delete-voucher-form" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-modern">
                            <i class="fas fa-trash-alt mr-2"></i>Да, удалить
                        </button>
                    </form>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        Отмена
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('css')
    @include('admin.layouts.modern-styles')
    <style>
        .avatar-circle-sm {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.8rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
@stop

@section('js')
    <script>
        $(document).ready(function () {
            var table = $('#vouchers-table').DataTable({
                "order": [[0, "desc"]],
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Russian.json"
                },
                "pageLength": 25,
                "columnDefs": [
                    { "orderable": false, "targets": 7 }
                ],
                "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip'
            });

            // Фильтры по табу
            $('#filterAll').on('click', function() { table.column(6).search('').draw(); });
            $('#filterActive').on('click', function() { table.column(6).search('Активен').draw(); });
            $('#filterUsed').on('click', function() { table.column(6).search('Использован').draw(); });
            $('#filterInactive').on('click', function() { table.column(6).search('Неактивен').draw(); });

            $('[data-toggle="tooltip"]').tooltip();

            // Динамическая модалка
            $('.btn-delete-voucher').on('click', function() {
                $('#delete-voucher-code').text($(this).data('code'));
                $('#delete-voucher-amount').text($(this).data('amount'));
                $('#delete-voucher-form').attr('action', $(this).data('action'));
                $('#singleDeleteModal').modal('show');
            });
        });
    </script>
@endsection
