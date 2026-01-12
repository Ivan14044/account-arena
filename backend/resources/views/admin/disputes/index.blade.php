@extends('adminlte::page')

@section('title', 'Претензии')

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="m-0 font-weight-light">Претензии</h1>
            </div>
        </div>
    </div>
@stop

@section('content')
    {{-- Статистика --}}
    <div class="row mb-4">
        <div class="col-lg-3 col-6 mb-3">
            <div class="stat-card stat-card-warning">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Новые претензии</div>
                        <div class="stat-value">{{ $stats['new'] }}</div>
                        <a href="{{ route('admin.disputes.index', ['status' => 'new']) }}" class="text-warning">
                            {{ __('Подробнее') }} <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6 mb-3">
            <div class="stat-card stat-card-info">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">На рассмотрении</div>
                        <div class="stat-value">{{ $stats['in_review'] }}</div>
                        <a href="{{ route('admin.disputes.index', ['status' => 'in_review']) }}" class="text-info">
                            {{ __('Подробнее') }} <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6 mb-3">
            <div class="stat-card stat-card-success">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Решенные</div>
                        <div class="stat-value">{{ $stats['resolved'] }}</div>
                        <a href="{{ route('admin.disputes.index', ['status' => 'resolved']) }}" class="text-success">
                            {{ __('Подробнее') }} <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6 mb-3">
            <div class="stat-card stat-card-danger">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Отклоненные</div>
                        <div class="stat-value">{{ $stats['rejected'] }}</div>
                        <a href="{{ route('admin.disputes.index', ['status' => 'rejected']) }}" class="text-danger">
                            {{ __('Подробнее') }} <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Статистика по владельцам товаров --}}
    <div class="row mb-4">
        <div class="col-lg-6 col-12 mb-3">
            <div class="stat-card stat-card-primary">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Претензии на мои товары</div>
                        <div class="stat-value">{{ $stats['admin_products'] }}</div>
                        <a href="{{ route('admin.disputes.index', ['owner' => 'admin']) }}" class="text-primary">
                            Посмотреть <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-12 mb-3">
            <div class="stat-card stat-card-info">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Претензии на товары поставщиков</div>
                        <div class="stat-value">{{ $stats['supplier_products'] }}</div>
                        <a href="{{ route('admin.disputes.index', ['owner' => 'suppliers']) }}" class="text-info">
                            Посмотреть <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Фильтры --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Фильтры</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-minus"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.disputes.index') }}" method="GET" class="form-inline">
                <div class="form-group mr-2 mb-2">
                    <label class="mr-2">Статус:</label>
                    <select name="status" class="form-control">
                        <option value="">Все</option>
                        <option value="new" {{ request('status') == 'new' ? 'selected' : '' }}>Новые</option>
                        <option value="in_review" {{ request('status') == 'in_review' ? 'selected' : '' }}>На рассмотрении</option>
                        <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>Решенные</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Отклоненные</option>
                    </select>
                </div>

                <div class="form-group mr-2 mb-2">
                    <label class="mr-2">Владелец товара:</label>
                    <select name="owner" class="form-control">
                        <option value="">Все товары</option>
                        <option value="admin" {{ request('owner') == 'admin' ? 'selected' : '' }}>🛡️ Мои товары</option>
                        <option value="suppliers" {{ request('owner') == 'suppliers' ? 'selected' : '' }}>Товары поставщиков</option>
                    </select>
                </div>

                <div class="form-group mr-2 mb-2">
                    <label class="mr-2">Дата с:</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>

                <div class="form-group mr-2 mb-2">
                    <label class="mr-2">Дата по:</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>

                <div class="form-group mr-2 mb-2">
                    <input type="text" name="search" class="form-control" placeholder="Поиск по ID или email..." value="{{ request('search') }}">
                </div>

                <button type="submit" class="btn btn-primary mr-2 mb-2">
                    <i class="fas fa-search"></i>Применить</button>
                <a href="{{ route('admin.disputes.index') }}" class="btn btn-secondary mb-2">
                    <i class="fas fa-redo"></i>Сбросить</a>
            </form>
        </div>
    </div>

    {{-- Таблица претензий --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Список претензий</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover text-nowrap">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Дата</th>
                        <th>Покупатель</th>
                        <th>Номер заказа</th>
                        <th>Товар</th>
                        <th>Поставщик</th>
                        <th>Причина</th>
                        <th>Сумма</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($disputes as $dispute)
                        <tr>
                            <td>#{{ $dispute->id }}</td>
                            <td>{{ $dispute->created_at->format('d.m.Y H:i') }}</td>
                            <td>
                                <a href="{{ route('admin.users.edit', $dispute->user) }}">
                                    {{ $dispute->user->name }}
                                </a>
                                <br>
                                <small class="text-muted">{{ $dispute->user->email }}</small>
                            </td>
                            <td>
                                @if($dispute->transaction && $dispute->transaction->purchase && $dispute->transaction->purchase->order_number)
                                    <a href="{{ route('admin.purchases.show', $dispute->transaction->purchase->id) }}" class="text-primary">
                                        {{ $dispute->transaction->purchase->order_number }}
                                    </a>
                                @elseif($dispute->transaction)
                                    <span class="text-muted">#{{ $dispute->transaction->id }}</span>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                @if($dispute->serviceAccount)
                                    {{ $dispute->serviceAccount->title }}
                                    <br>
                                    <small class="text-muted">{{ $dispute->serviceAccount->login }}</small>
                                @else
                                    <span class="text-muted">Товар удален</span>
                                @endif
                            </td>
                                        <td>
                                            @if($dispute->supplier_id && $dispute->supplier)
                                                <a href="{{ route('admin.suppliers.show', $dispute->supplier) }}">
                                                    {{ $dispute->supplier->name }}
                                                </a>
                                            @else
                                                <span class="badge badge-info">Администратор</span>
                                            @endif
                                        </td>
                            <td>
                                <span class="badge badge-secondary">
                                    {{ $dispute->getReasonText() }}
                                </span>
                            </td>
                            <td>${{ number_format($dispute->transaction->amount, 2) }}</td>
                            <td>
                                <span class="badge {{ $dispute->getStatusBadgeClass() }}">
                                    {{ $dispute->getStatusText() }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('admin.disputes.show', $dispute) }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">
                                Претензии не найдены
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($disputes->hasPages())
            <div class="card-footer clearfix">
                {{ $disputes->links() }}
            </div>
        @endif
    </div>
@stop

@section('css')
    @include('admin.layouts.modern-styles')
@endsection

