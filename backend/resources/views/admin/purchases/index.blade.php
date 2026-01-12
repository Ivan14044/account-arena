@extends('adminlte::page')

@section('title', 'Управление покупками')

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="m-0 font-weight-light">
                    Управление покупками товаров
                </h1>
                <p class="text-muted mb-0 mt-1">Просмотр и управление всеми покупками цифровых товаров</p>
            </div>
        </div>
    </div>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <!-- Статистика -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card stat-card-info">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Всего покупок</div>
                        <div class="stat-value">{{ $stats['total'] }}</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card stat-card-success">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Покупок сегодня</div>
                        <div class="stat-value">{{ $stats['today'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card stat-card-warning">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Покупок в этом месяце</div>
                        <div class="stat-value">{{ $stats['this_month'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card stat-card-primary">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Общий доход</div>
                        <div class="stat-value">${{ number_format($stats['total_revenue'], 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Фильтры -->
    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-filter"></i> Фильтры</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-minus"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.purchases.index') }}">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Поиск</label>
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Номер заказа или email" 
                                   value="{{ request('search') }}">
                            <small class="form-text text-muted">По email зарегистрированного пользователя или гостя</small>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Тип покупателя</label>
                            <select name="buyer_type" class="form-control">
                                <option value="">Все</option>
                                <option value="registered" {{ request('buyer_type') == 'registered' ? 'selected' : '' }}>Зарегистрированные</option>
                                <option value="guest" {{ request('buyer_type') == 'guest' ? 'selected' : '' }}>Гости</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Пользователь</label>
                            <select name="user_id" class="form-control">
                                <option value="">Все пользователи</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->email }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Товар</label>
                            <select name="product_id" class="form-control">
                                <option value="">Все товары</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                                        {{ $product->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Статус</label>
                            <select name="status" class="form-control">
                                <option value="">Все статусы</option>
                                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Завершено</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>В обработке</option>
                                <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Ошибка</option>
                                <option value="refunded" {{ request('status') == 'refunded' ? 'selected' : '' }}>Возврат</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>С даты</label>
                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>По дату</label>
                            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Применить
                                </button>
                                <a href="{{ route('admin.purchases.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-redo"></i> Сбросить
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Таблица покупок -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-list"></i> Список покупок ({{ $purchases->total() }})</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Номер заказа</th>
                        <th>Пользователь</th>
                        <th>Товар</th>
                        <th>Количество</th>
                        <th>Сумма</th>
                        <th>Статус</th>
                        <th>Дата</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchases as $purchase)
                        <tr>
                            <td>{{ $purchase->id }}</td>
                            <td>
                                <code class="text-primary">{{ $purchase->order_number ?? 'N/A' }}</code>
                            </td>
                            <td>
                                @if($purchase->user)
                                    <!-- Зарегистрированный пользователь -->
                                    <a href="{{ route('admin.users.edit', $purchase->user) }}" class="text-decoration-none">
                                        <i class="fas fa-user"></i> {{ $purchase->user->email }}
                                    </a>
                                    <br>
                                    <small class="text-muted">{{ $purchase->user->name }}</small>
                                @elseif($purchase->guest_email)
                                    <!-- Гостевая покупка -->
                                    <i class="fas fa-user-circle text-info"></i> {{ $purchase->guest_email }}
                                    <br>
                                    <small class="badge badge-info">Гость</small>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                @if($purchase->serviceAccount)
                                    <a href="{{ route('admin.service-accounts.edit', $purchase->serviceAccount) }}" class="text-decoration-none">
                                        {{ $purchase->serviceAccount->title }}
                                    </a>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-info">{{ $purchase->quantity }} шт.</span>
                            </td>
                            <td>
                                <strong>${{ number_format($purchase->total_amount, 2) }}</strong>
                                <br>
                                <small class="text-muted">${{ number_format($purchase->price, 2) }} × {{ $purchase->quantity }}</small>
                            </td>
                            <td>
                                @php
                                    $statusColors = [
                                        'completed' => 'success',
                                        'pending' => 'warning',
                                        'failed' => 'danger',
                                        'refunded' => 'info',
                                    ];
                                    $statusLabels = [
                                        'completed' => 'Завершено',
                                        'pending' => 'В обработке',
                                        'failed' => 'Ошибка',
                                        'refunded' => 'Возврат',
                                    ];
                                    $color = $statusColors[$purchase->status] ?? 'secondary';
                                    $label = $statusLabels[$purchase->status] ?? $purchase->status;
                                @endphp
                                <span class="badge badge-{{ $color }}">{{ $label }}</span>
                            </td>
                            <td>
                                {{ $purchase->created_at->format('d.m.Y H:i') }}
                                <br>
                                <small class="text-muted">{{ $purchase->created_at->diffForHumans() }}</small>
                            </td>
                            <td>
                                <a href="{{ route('admin.purchases.show', $purchase) }}" 
                                   class="btn btn-sm btn-info" 
                                   title="Подробнее">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                Покупки не найдены
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($purchases->hasPages())
            <div class="card-footer">
                {{ $purchases->links() }}
            </div>
        @endif
    </div>
@endsection

@section('css')
    @include('admin.layouts.modern-styles')
@endsection




