@extends('adminlte::page')

@section('title', 'Поставщики')

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="m-0 font-weight-light">
                    Поставщики
                </h1>
                <p class="text-muted mb-0 mt-1">Управление партнерами и поставщиками цифровых товаров</p>
            </div>
            <div>
                <a href="{{ route('admin.suppliers.settings') }}" class="btn btn-secondary btn-modern">
                    <i class="fas fa-cog mr-2"></i>Настройки
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
    <div class="row mb-4 suppliers-stats">
        <div class="col-lg-4 col-6">
            <div class="stat-card stat-card-primary stat-card-compact">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Всего поставщиков</div>
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
                        <div class="stat-label">Активные</div>
                        <div class="stat-value">{{ $statistics['active'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-6">
            <div class="stat-card stat-card-info stat-card-compact">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Общий баланс</div>
                        <div class="stat-value">${{ number_format($statistics['total_balance'], 0) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Поиск -->
    <div class="card card-modern mb-4">
        <div class="card-body-modern p-3">
            <form action="{{ route('admin.suppliers.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-9">
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-white border-right-0"><i class="fas fa-search text-muted"></i></span>
                        </div>
                        <input type="text" name="search" class="form-control border-left-0" placeholder="Поиск по имени или email поставщика..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-3 d-flex">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1 mr-2">
                        <i class="fas fa-filter mr-1"></i>Найти
                    </button>
                    @if(request('search'))
                        <a href="{{ route('admin.suppliers.index') }}" class="btn btn-secondary btn-sm" title="Сбросить фильтр">
                            <i class="fas fa-undo mr-1"></i>Сброс
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card card-modern">
        <div class="card-header-modern">
            <h5 class="mb-0">Список поставщиков <span class="badge badge-light border ml-1">{{ $suppliers->total() }}</span></h5>
        </div>
        <div class="card-body-modern p-0">
            @if($suppliers->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover modern-table mb-0 suppliers-table">
                    <thead>
                    <tr>
                        <th style="width: 60px" class="text-center">ID</th>
                        <th>Имя</th>
                        <th>Email</th>
                        <th class="text-center">Рейтинг</th>
                        <th class="text-center">Баланс</th>
                        <th class="text-center">Комиссия</th>
                        <th class="text-center">Методы вывода</th>
                        <th style="width: 100px" class="text-center">Действия</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($suppliers as $supplier)
                        <tr>
                            <td class="text-center align-middle">
                                <span class="badge badge-light font-weight-bold">#{{ $supplier->id }}</span>
                            </td>
                            <td class="align-middle font-weight-bold">{{ $supplier->name }}</td>
                            <td class="align-middle text-muted small">{{ $supplier->email }}</td>
                            <td class="text-center align-middle">
                                @php
                                    $rating = $supplier->supplier_rating ?? 100;
                                    $level = $supplier->getRatingLevel();
                                @endphp
                                <div class="d-inline-block">
                                    <span class="badge badge-{{ $level['class'] }} badge-modern px-2 py-1">
                                        {{ $level['icon'] }} {{ $rating }}%
                                    </span>
                                    <div class="mt-1" style="font-size: 0.7rem;">
                                        @for($i = 0; $i < $level['stars']; $i++)
                                            <i class="fas fa-star text-warning" style="font-size: 0.6rem;"></i>
                                        @endfor
                                    </div>
                                </div>
                            </td>
                            <td class="text-center align-middle font-weight-bold text-success">
                                ${{ number_format($supplier->supplier_balance, 2) }}
                            </td>
                            <td class="text-center align-middle">
                                <span class="badge badge-light border">{{ $supplier->supplier_commission }}%</span>
                            </td>
                            <td class="text-center align-middle">
                                <div class="d-flex justify-content-center gap-2">
                                    @if($supplier->trc20_wallet)
                                        <span class="text-info" title="TRC-20: {{ $supplier->trc20_wallet }}" data-toggle="tooltip">
                                            <i class="fas fa-coins"></i>
                                        </span>
                                    @endif
                                    @if($supplier->card_number_uah)
                                        <span class="text-primary" title="Карта: {{ $supplier->card_number_uah }}" data-toggle="tooltip">
                                            <i class="fas fa-credit-card"></i>
                                        </span>
                                    @endif
                                    @if(!$supplier->trc20_wallet && !$supplier->card_number_uah)
                                        <span class="text-muted small italic">—</span>
                                    @endif
                                </div>
                            </td>
                            <td class="text-center align-middle">
                                <div class="action-buttons justify-content-center">
                                    <a href="{{ route('admin.suppliers.show', $supplier) }}" 
                                       class="btn btn-sm btn-primary" 
                                       title="Просмотр профиля"
                                       data-toggle="tooltip">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="p-5 text-center text-muted">
                <i class="fas fa-user-tie fa-3x mb-3 opacity-20"></i>
                <p class="font-weight-bold mb-0">Поставщики не найдены</p>
                <small>Попробуйте изменить параметры поиска</small>
            </div>
            @endif
        </div>
        @if($suppliers->hasPages())
            <div class="card-footer-modern bg-white p-3 border-top">
                <div class="d-flex justify-content-center">
                    {{ $suppliers->appends(request()->query())->links() }}
                </div>
            </div>
        @endif
    </div>
@endsection

@section('css')
    @include('admin.layouts.modern-styles')
@endsection

@section('js')
    <script>
        $(document).ready(function () {
            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>
@endsection
