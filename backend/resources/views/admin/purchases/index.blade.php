@extends('adminlte::page')

@section('title', __('Управление покупками'))

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <h1 class="m-0 font-weight-light">
                    {{ __('Управление покупками товаров') }}
                </h1>
                <p class="text-muted mb-0 mt-1 d-none d-md-block">{{ __('Просмотр и управление всеми покупками цифровых товаров') }}</p>
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
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <!-- Статистика -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 col-6 mb-3 mb-lg-0">
            <div class="stat-card stat-card-info stat-card-compact w-100">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">{{ __('Всего покупок') }}</div>
                        <div class="stat-value">{{ number_format($stats['total']) }}</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 col-6 mb-3 mb-lg-0">
            <div class="stat-card stat-card-success stat-card-compact w-100">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">{{ __('Покупок сегодня') }}</div>
                        <div class="stat-value">{{ number_format($stats['today']) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-6">
            <div class="stat-card stat-card-warning stat-card-compact w-100">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">{{ __('Покупок в этом месяце') }}</div>
                        <div class="stat-value">{{ number_format($stats['this_month']) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-6">
            <div class="stat-card stat-card-primary stat-card-compact w-100">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">{{ __('Общий доход') }}</div>
                        <div class="stat-value">${{ number_format($stats['total_revenue'], 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Фильтры -->
    <div class="card card-modern mb-4">
        <div class="card-header-modern">
            <h5 class="mb-0 font-weight-normal"><i class="fas fa-filter mr-2 text-muted"></i>{{ __('Фильтры') }}</h5>
        </div>
        <div class="card-body p-4">
            <form method="GET" action="{{ route('admin.purchases.index') }}">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">{{ __('Поиск') }}</label>
                        <input type="text" name="search" class="form-control form-control-modern" 
                               placeholder="{{ __('Номер заказа или email') }}" 
                               value="{{ request('search') }}">
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">{{ __('Тип покупателя') }}</label>
                        <select name="buyer_type" class="form-control form-control-modern">
                            <option value="">{{ __('Все') }}</option>
                            <option value="registered" {{ request('buyer_type') == 'registered' ? 'selected' : '' }}>{{ __('Зарегистрированные') }}</option>
                            <option value="guest" {{ request('buyer_type') == 'guest' ? 'selected' : '' }}>{{ __('Гости') }}</option>
                        </select>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">{{ __('ID Пользователя') }}</label>
                        <input type="number" name="user_id" class="form-control form-control-modern" 
                               placeholder="{{ __('Введите ID') }}" 
                               value="{{ request('user_id') }}">
                        @if(isset($users) && count($users) > 0)
                            <small class="text-success"><i class="fas fa-check-circle mr-1"></i>{{ $users->first()->email }}</small>
                        @endif
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">{{ __('ID Товара') }}</label>
                        <input type="number" name="product_id" class="form-control form-control-modern" 
                               placeholder="{{ __('Введите ID') }}" 
                               value="{{ request('product_id') }}">
                        @if(isset($products) && count($products) > 0)
                            <small class="text-info"><i class="fas fa-info-circle mr-1"></i>{{ \Illuminate\Support\Str::limit($products->first()->title, 30) }}</small>
                        @endif
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">{{ __('Статус') }}</label>
                        <select name="status" class="form-control form-control-modern">
                            <option value="">{{ __('Все статусы') }}</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>{{ __('Завершено') }}</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>{{ __('В обработке') }}</option>
                            <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>{{ __('Ошибка') }}</option>
                            <option value="refunded" {{ request('status') == 'refunded' ? 'selected' : '' }}>{{ __('Возврат') }}</option>
                        </select>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">{{ __('С даты') }}</label>
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                            </div>
                            <input type="date" name="date_from" class="form-control form-control-modern" value="{{ request('date_from') }}">
                        </div>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">{{ __('По дату') }}</label>
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                            </div>
                            <input type="date" name="date_to" class="form-control form-control-modern" value="{{ request('date_to') }}">
                        </div>
                    </div>

                    <div class="col-md-12 mt-2">
                        <button type="submit" class="btn btn-primary btn-modern mr-2">
                            <i class="fas fa-search mr-2"></i>{{ __('Применить фильтры') }}
                        </button>
                        <a href="{{ route('admin.purchases.index') }}" class="btn btn-secondary btn-modern">
                            <i class="fas fa-redo mr-2"></i>{{ __('Сбросить') }}
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Таблица покупок -->
    <div class="card card-modern">
        <div class="card-header-modern">
            <h5 class="mb-0 font-weight-normal">{{ __('Список покупок') }} ({{ $purchases->total() }})</h5>
        </div>
        <div class="card-body-modern">
            <div class="table-responsive">
                <table class="table table-hover modern-table purchases-table">
                    <thead>
                        <tr>
                            <th style="width: 60px" class="text-center">ID</th>
                            <th>{{ __('Заказ') }}</th>
                            <th>{{ __('Покупатель') }}</th>
                            <th>{{ __('Товар') }}</th>
                            <th class="text-center">{{ __('Кол-во') }}</th>
                            <th>{{ __('Сумма') }}</th>
                            <th class="text-center">{{ __('Статус') }}</th>
                            <th>{{ __('Дата') }}</th>
                            <th class="text-center" style="width: 100px">{{ __('Действия') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchases as $purchase)
                            <tr>
                                <td class="text-center align-middle">
                                    <span class="badge badge-light">#{{ $purchase->id }}</span>
                                </td>
                                <td class="align-middle">
                                    <code class="text-primary font-weight-bold">{{ $purchase->order_number ?? 'N/A' }}</code>
                                </td>
                                <td class="align-middle">
                                    @if($purchase->user)
                                        <div class="d-flex align-items-center">
                                            <div class="mr-2">
                                                <i class="fas fa-user text-muted"></i>
                                            </div>
                                            <div>
                                                <a href="{{ route('admin.users.edit', $purchase->user) }}" class="text-dark font-weight-bold">
                                                    {{ $purchase->user->email }}
                                                </a>
                                                <br>
                                                <small class="text-muted">{{ $purchase->user->name }}</small>
                                            </div>
                                        </div>
                                    @elseif($purchase->guest_email)
                                        <div class="d-flex align-items-center">
                                            <div class="mr-2">
                                                <i class="fas fa-user-circle text-info"></i>
                                            </div>
                                            <div>
                                                <span class="text-dark font-weight-bold">{{ $purchase->guest_email }}</span>
                                                <br>
                                                <span class="badge badge-info badge-modern" style="font-size: 0.65rem;">{{ __('ГОСТЬ') }}</span>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td class="align-middle">
                                    @if($purchase->serviceAccount)
                                        <a href="{{ route('admin.service-accounts.edit', $purchase->serviceAccount) }}" class="text-primary font-weight-500">
                                            {{ \Illuminate\Support\Str::limit($purchase->serviceAccount->title, 40) }}
                                        </a>
                                    @else
                                        <span class="text-muted">{{ __('Товар удален') }}</span>
                                    @endif
                                </td>
                                <td class="text-center align-middle">
                                    <span class="badge badge-info badge-modern">{{ $purchase->quantity }} {{ __('шт.') }}</span>
                                </td>
                                <td class="align-middle">
                                    <div class="font-weight-bold">${{ number_format($purchase->total_amount, 2) }}</div>
                                    <small class="text-muted">${{ number_format($purchase->price, 2) }} / {{ __('ед.') }}</small>
                                </td>
                                <td class="text-center align-middle">
                                    <span class="badge badge-{{ $purchase->getStatusBadgeClass() }} badge-modern">{{ $purchase->getStatusText() }}</span>
                                </td>
                                <td class="align-middle">
                                    <small class="text-muted">
                                        <i class="far fa-calendar-alt mr-1"></i>
                                        {{ $purchase->created_at->translatedFormat('d.m.Y') }}
                                        <br>
                                        <i class="far fa-clock mr-1"></i>
                                        {{ $purchase->created_at->translatedFormat('H:i') }}
                                    </small>
                                </td>
                                <td class="text-center align-middle">
                                    <a href="{{ route('admin.purchases.show', $purchase) }}" 
                                       class="btn btn-sm btn-primary" 
                                       title="{{ __('Просмотр') }}"
                                       data-toggle="tooltip">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <div class="empty-state">
                                        <i class="fas fa-shopping-cart fa-3x mb-3 text-muted opacity-50"></i>
                                        <p class="text-muted mb-0">{{ __('Покупки не найдены') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($purchases->hasPages())
            <div class="card-footer bg-white border-top">
                <div class="d-flex justify-content-center">
                    {{ $purchases->appends(request()->all())->links('pagination::bootstrap-4') }}
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
        $(document).ready(function() {
            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>
@endsection
