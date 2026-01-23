@extends('adminlte::page')

@section('title', 'Вывод средств')

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <h1 class="m-0 font-weight-light">
                    Запросы на вывод средств
                </h1>
                <p class="text-muted mb-0 mt-1 d-none d-md-block">Управление выплатами поставщикам платформы</p>
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
            <i class="fas fa-exclamation-triangle mr-2"></i>{{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <!-- Статистика -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 col-6 mb-3 mb-lg-0">
            <div class="stat-card stat-card-primary stat-card-compact w-100">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Всего запросов</div>
                        <div class="stat-value">{{ $statistics['total'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-6 mb-3 mb-lg-0">
            <div class="stat-card stat-card-warning stat-card-compact w-100">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Ожидают оплаты</div>
                        <div class="stat-value">{{ $statistics['pending'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-6">
            <div class="stat-card stat-card-success stat-card-compact w-100">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-check-double"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Выплачено</div>
                        <div class="stat-value">{{ $statistics['paid'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-6">
            <div class="stat-card stat-card-info stat-card-compact w-100">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Сумма выплат</div>
                        <div class="stat-value">${{ number_format($statistics['total_amount'], 0) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Фильтры -->
    <div class="card card-modern mb-4">
        <div class="card-header-modern">
            <h5 class="mb-0 font-weight-normal"><i class="fas fa-filter mr-2 text-muted"></i>Фильтры</h5>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('admin.withdrawal-requests.index') }}" method="GET" class="row g-3">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Статус</label>
                    <select name="status" class="form-control form-control-modern">
                        <option value="">Все статусы</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>В обработке</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Одобрен</option>
                        <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Оплачен</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Отклонен</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Поставщик</label>
                    <select name="supplier_id" class="form-control form-control-modern">
                        <option value="">Все поставщики</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">С даты</label>
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                        </div>
                        <input type="date" name="date_from" class="form-control form-control-modern" value="{{ request('date_from') }}">
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">По дату</label>
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                        </div>
                        <input type="date" name="date_to" class="form-control form-control-modern" value="{{ request('date_to') }}">
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end mb-3">
                    <button type="submit" class="btn btn-primary btn-modern mr-2 flex-grow-1">
                        <i class="fas fa-filter mr-1"></i>Найти
                    </button>
                    <a href="{{ route('admin.withdrawal-requests.index') }}" class="btn btn-secondary btn-modern" title="Сбросить">
                        <i class="fas fa-undo"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-modern">
        <div class="card-header-modern p-0">
            <ul class="nav nav-pills p-2">
                <li class="nav-item"><a class="nav-link {{ !request('status') ? 'active' : '' }}" href="{{ route('admin.withdrawal-requests.index') }}">Все</a></li>
                <li class="nav-item"><a class="nav-link {{ request('status') == 'pending' ? 'active' : '' }}" href="{{ route('admin.withdrawal-requests.index', ['status' => 'pending']) }}">Ожидают</a></li>
                <li class="nav-item"><a class="nav-link {{ request('status') == 'paid' ? 'active' : '' }}" href="{{ route('admin.withdrawal-requests.index', ['status' => 'paid']) }}">Выплачены</a></li>
            </ul>
        </div>
        <div class="card-body-modern">
            <div class="table-responsive">
                <table id="withdrawals-table" class="table table-hover modern-table">
                    <thead>
                    <tr>
                        <th style="width: 60px" class="text-center">ID</th>
                        <th>Поставщик</th>
                        <th class="text-center">Сумма</th>
                        <th class="text-center">Метод</th>
                        <th>Реквизиты</th>
                        <th class="text-center">Статус</th>
                        <th class="text-center">Дата</th>
                        <th style="width: 80px" class="text-center">Действия</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($withdrawalRequests as $request)
                        <tr>
                            <td class="text-center align-middle">
                                <span class="badge badge-light">#{{ $request->id }}</span>
                            </td>
                            <td class="align-middle">
                                <a href="{{ route('admin.suppliers.show', $request->supplier) }}" class="font-weight-bold text-primary">
                                    {{ $request->supplier->name }}
                                </a>
                                <div class="text-muted small">{{ $request->supplier->email }}</div>
                            </td>
                            <td class="text-center align-middle font-weight-bold" style="font-size: 1.1rem;">
                                ${{ number_format($request->amount, 2) }}
                            </td>
                            <td class="text-center align-middle">
                                @if($request->payment_method == 'trc20')
                                    <span class="badge badge-info badge-modern"><i class="fas fa-coins mr-1"></i>TRC-20</span>
                                @else
                                    <span class="badge badge-primary badge-modern"><i class="fas fa-credit-card mr-1"></i>Карта</span>
                                @endif
                            </td>
                            <td class="align-middle">
                                <code class="text-muted small" style="background: #f8f9fc; padding: 2px 4px;">{{ $request->payment_details }}</code>
                            </td>
                            <td class="text-center align-middle">
                                @if($request->status == 'pending')
                                    <span class="badge badge-warning badge-modern animate-pulse">В обработке</span>
                                @elseif($request->status == 'approved')
                                    <span class="badge badge-info badge-modern">Одобрен</span>
                                @elseif($request->status == 'paid')
                                    <span class="badge badge-success badge-modern">Оплачен</span>
                                @else
                                    <span class="badge badge-danger badge-modern">Отклонен</span>
                                @endif
                            </td>
                            <td class="text-center align-middle">
                                <small class="text-muted">
                                    <i class="far fa-calendar-alt mr-1"></i>
                                    {{ $request->created_at->format('d.m.Y') }}
                                    <br>
                                    <i class="far fa-clock mr-1"></i>
                                    {{ $request->created_at->format('H:i') }}
                                </small>
                            </td>
                            <td class="text-center align-middle">
                                <a href="{{ route('admin.withdrawal-requests.show', $request) }}" 
                                   class="btn btn-sm btn-primary" 
                                   title="Просмотр"
                                   data-toggle="tooltip">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="empty-state">
                                    <i class="fas fa-file-invoice-dollar fa-3x mb-3 text-muted opacity-50"></i>
                                    <p class="text-muted mb-0">Запросы не найдены</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($withdrawalRequests->hasPages())
            <div class="card-footer bg-white border-top d-flex justify-content-center">
                {{ $withdrawalRequests->appends(request()->all())->links('pagination::bootstrap-4') }}
            </div>
        @endif
    </div>
@endsection

@section('css')
    @include('admin.layouts.modern-styles')
    <style>
        .animate-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .7; }
        }
    </style>
@endsection

@section('js')
    <script>
        $(document).ready(function () {
            $('#withdrawals-table').DataTable({
                "order": [[0, "desc"]],
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/ru.json"
                },
                "paging": false,
                "info": false,
                "searching": false,
                "dom": 't',
                "columnDefs": [
                    { "orderable": false, "targets": 7 }
                ]
            });

            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>
@endsection
