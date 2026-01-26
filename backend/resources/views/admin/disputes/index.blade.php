@extends('adminlte::page')

@section('title', 'Претензии')

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="m-0 font-weight-light">Претензии</h1>
                <p class="text-muted mb-0 mt-1">Управление спорами и претензиями от покупателей</p>
            </div>
        </div>
    </div>
@stop

@section('content')
    {{-- Статистика --}}
    <div class="row mb-4 disputes-stats">
        <div class="col-lg-3 col-6 mb-3 d-flex">
            <div class="stat-card stat-card-warning stat-card-compact w-100">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Новые</div>
                        <div class="stat-value">{{ $stats['new'] }}</div>
                        <a href="{{ route('admin.disputes.index', ['status' => 'new']) }}" class="text-warning small font-weight-bold">
                            {{ __('Подробнее') }} <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6 mb-3 d-flex">
            <div class="stat-card stat-card-info stat-card-compact w-100">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">В работе</div>
                        <div class="stat-value">{{ $stats['in_review'] }}</div>
                        <a href="{{ route('admin.disputes.index', ['status' => 'in_review']) }}" class="text-info small font-weight-bold">
                            {{ __('Подробнее') }} <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6 mb-3 d-flex">
            <div class="stat-card stat-card-success stat-card-compact w-100">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Решенные</div>
                        <div class="stat-value">{{ $stats['resolved'] }}</div>
                        <a href="{{ route('admin.disputes.index', ['status' => 'resolved']) }}" class="text-success small font-weight-bold">
                            {{ __('Подробнее') }} <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6 mb-3 d-flex">
            <div class="stat-card stat-card-danger stat-card-compact w-100">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Отклоненные</div>
                        <div class="stat-value">{{ $stats['rejected'] }}</div>
                        <a href="{{ route('admin.disputes.index', ['status' => 'rejected']) }}" class="text-danger small font-weight-bold">
                            {{ __('Подробнее') }} <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Фильтры --}}
    <div class="card card-modern mb-4">
        <div class="card-header-modern">
            <h5 class="mb-0 font-weight-normal"><i class="fas fa-filter mr-2 text-muted"></i>Фильтры</h5>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('admin.disputes.index') }}" method="GET" class="row g-3">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Статус</label>
                    <select name="status" class="form-control form-control-modern">
                        <option value="">Все статусы</option>
                        <option value="new" {{ request('status') == 'new' ? 'selected' : '' }}>Новые</option>
                        <option value="in_review" {{ request('status') == 'in_review' ? 'selected' : '' }}>На рассмотрении</option>
                        <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>Решенные</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Отклоненные</option>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Владелец товара</label>
                    <select name="owner" class="form-control form-control-modern">
                        <option value="">{{ __('Все товары') }}</option>
                        <option value="admin" {{ request('owner') == 'admin' ? 'selected' : '' }}>🛡️ {{ __('Мои товары') }}</option>
                        <option value="suppliers" {{ request('owner') == 'suppliers' ? 'selected' : '' }}>{{ __('Товары поставщиков') }}</option>
                    </select>
                </div>

                <div class="col-md-2 mb-3">
                    <label class="form-label">С даты</label>
                    <input type="date" name="date_from" class="form-control form-control-modern" value="{{ request('date_from') }}">
                </div>

                <div class="col-md-2 mb-3">
                    <label class="form-label">По дату</label>
                    <input type="date" name="date_to" class="form-control form-control-modern" value="{{ request('date_to') }}">
                </div>

                <div class="col-md-2 mb-3">
                    <label class="form-label">Поиск</label>
                    <input type="text" name="search" class="form-control form-control-modern" placeholder="ID или email..." value="{{ request('search') }}">
                </div>

                <div class="col-12 mt-2">
                    <button type="submit" class="btn btn-primary btn-modern mr-2">
                        <i class="fas fa-search mr-2"></i>Применить фильтры
                    </button>
                    <a href="{{ route('admin.disputes.index') }}" class="btn btn-secondary btn-modern">
                        <i class="fas fa-redo mr-2"></i>Сбросить
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Таблица претензий --}}
    <div class="card card-modern">
        <div class="card-header-modern">
            <h5 class="mb-0 font-weight-normal">Список претензий ({{ $disputes->total() }})</h5>
        </div>
        <div class="card-body-modern">
            <div class="table-responsive">
                <table class="table table-hover disputes-table">
                    <thead>
                        <tr>
                            <th style="width: 60px" class="text-center">ID</th>
                            <th>Покупатель</th>
                            <th>Заказ</th>
                            <th>Товар</th>
                            <th>Поставщик</th>
                            <th>Причина</th>
                            <th>Сумма</th>
                            <th class="text-center">Статус</th>
                            <th class="text-center">Дата</th>
                            <th class="text-center" style="width: 100px">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($disputes as $dispute)
                            <tr>
                                <td class="text-center align-middle">
                                    <span class="badge badge-light">#{{ $dispute->id }}</span>
                                </td>
                                <td class="align-middle">
                                    <div class="font-weight-bold text-dark">{{ $dispute->user->name }}</div>
                                    <small class="text-muted">{{ $dispute->user->email }}</small>
                                </td>
                                <td class="align-middle">
                                    @if($dispute->transaction && $dispute->transaction->purchase && $dispute->transaction->purchase->order_number)
                                        <a href="{{ route('admin.purchases.show', $dispute->transaction->purchase->id) }}" class="text-primary font-weight-bold">
                                            {{ $dispute->transaction->purchase->order_number }}
                                        </a>
                                    @elseif($dispute->transaction)
                                        <code class="text-muted small">#T{{ $dispute->transaction->id }}</code>
                                    @else
                                        <span class="text-muted small">N/A</span>
                                    @endif
                                </td>
                                <td class="align-middle">
                                    @if($dispute->serviceAccount)
                                        <div class="font-weight-500">{{ \Illuminate\Support\Str::limit($dispute->serviceAccount->title, 30) }}</div>
                                        @if($dispute->serviceAccount->sku)
                                            <small class="text-muted">SKU: {{ $dispute->serviceAccount->sku }}</small>
                                        @endif
                                    @else
                                        <span class="text-muted small">Товар удален</span>
                                    @endif
                                </td>
                                <td class="align-middle">
                                    @if($dispute->supplier_id && $dispute->supplier)
                                        <span class="text-dark font-weight-500">{{ $dispute->supplier->name }}</span>
                                    @else
                                        <span class="badge badge-info badge-modern" style="font-size: 0.65rem;">АДМИН</span>
                                    @endif
                                </td>
                                <td class="align-middle">
                                    <span class="badge badge-light border" style="font-size: 0.75rem;">
                                        {{ $dispute->getReasonText() }}
                                    </span>
                                </td>
                                <td class="align-middle font-weight-bold">
                                    ${{ number_format($dispute->transaction->amount ?? 0, 2) }}
                                </td>
                                <td class="text-center align-middle">
                                    <span class="badge {{ $dispute->getStatusBadgeClass() }} badge-modern">
                                        {{ $dispute->getStatusText() }}
                                    </span>
                                </td>
                                <td class="text-center align-middle">
                                    <small class="text-muted">
                                        {{ $dispute->created_at->translatedFormat('d.m.Y') }}
                                        <br>
                                        {{ $dispute->created_at->translatedFormat('H:i') }}
                                        <br>
                                        <span class="small opacity-75">({{ $dispute->created_at->diffForHumans() }})</span>
                                    </small>
                                </td>
                                <td class="text-center align-middle">
                                    <a href="{{ route('admin.disputes.show', $dispute) }}" class="btn btn-sm btn-primary" title="Просмотр" data-toggle="tooltip">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-5">
                                    <div class="empty-state">
                                        <i class="fas fa-exclamation-triangle fa-3x mb-3 text-muted opacity-50"></i>
                                        <p class="text-muted mb-0">Претензии не найдены</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($disputes->hasPages())
            <div class="card-footer bg-white border-top d-flex justify-content-center">
                {{ $disputes->appends(request()->all())->links('pagination::bootstrap-4') }}
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
            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>
@endsection
