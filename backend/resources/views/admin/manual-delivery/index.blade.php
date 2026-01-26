@extends('adminlte::page')

@section('title', 'Ручная обработка заказов')

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="m-0 font-weight-light">
                    Ручная обработка заказов
                </h1>
                <p class="text-muted mb-0 mt-1">Заказы, требующие ручной выдачи товара</p>
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
    <div class="row mb-4 manual-delivery-stats">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card stat-card-warning stat-card-compact">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Ожидают обработки</div>
                        <div class="stat-value">{{ $statistics['pending'] }}</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card stat-card-success stat-card-compact">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Обработано сегодня</div>
                        <div class="stat-value">{{ $statistics['processed_today'] }}</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card stat-card-info stat-card-compact">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-week"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Обработано на этой неделе</div>
                        <div class="stat-value">{{ $statistics['processed_this_week'] }}</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card stat-card-primary stat-card-compact">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Среднее время (часов)</div>
                        <div class="stat-value">{{ $statistics['average_processing_time'] ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Список заказов -->
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="card-title">
                    <i class="fas fa-list mr-2"></i>
                    Заказы с ручной выдачей ({{ $pendingOrders->count() }})
                </h3>
                <div class="card-tools">
                </div>
            </div>
        </div>
        
        <!-- Форма фильтров и сортировки -->
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('admin.manual-delivery.index') }}" class="row g-3">
                <!-- Скрытые поля для сохранения текущих фильтров -->
                <input type="hidden" name="delivery_type" value="{{ $deliveryType }}">
                <input type="hidden" name="status" value="{{ $statusFilter ?? 'all' }}">
                
                <div class="col-md-2 mb-3">
                    <label class="form-label">Статус</label>
                    <select name="status" class="form-control form-control-sm" onchange="this.form.submit()">
                        <option value="all" {{ ($statusFilter ?? 'all') === 'all' ? 'selected' : '' }}>Все</option>
                        <option value="processing" {{ ($statusFilter ?? 'all') === 'processing' ? 'selected' : '' }}>В обработке</option>
                        <option value="completed" {{ ($statusFilter ?? 'all') === 'completed' ? 'selected' : '' }}>Обработано</option>
                    </select>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label class="form-label">Дата создания (с)</label>
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fas fa-calendar-alt"></i>
                            </span>
                        </div>
                        <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label class="form-label">Дата создания (по)</label>
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fas fa-calendar-alt"></i>
                            </span>
                        </div>
                        <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
                    </div>
                </div>
                
                <div class="col-md-2 mb-3">
                    <label class="form-label">Email покупателя</label>
                    <input type="text" name="customer_email" class="form-control form-control-sm" 
                           placeholder="email@example.com" value="{{ $customerEmail }}">
                </div>
                
                <div class="col-md-2 mb-3">
                    <label class="form-label">ID покупателя</label>
                    <input type="number" name="customer_id" class="form-control form-control-sm" 
                           placeholder="ID" value="{{ $customerId }}">
                </div>
                
                <div class="col-md-2 mb-3">
                    <label class="form-label">Номер заказа</label>
                    <input type="text" name="order_number" class="form-control form-control-sm" 
                           placeholder="ORD-..." value="{{ $orderNumber }}">
                </div>
                
                <div class="col-md-3 mb-3">
                    <label class="form-label">Сортировать по</label>
                    <select name="sort_by" class="form-control form-control-sm">
                        <option value="created_at" {{ $sortBy === 'created_at' ? 'selected' : '' }}>Дата создания</option>
                        <option value="total_amount" {{ $sortBy === 'total_amount' ? 'selected' : '' }}>Сумма заказа</option>
                        <option value="quantity" {{ $sortBy === 'quantity' ? 'selected' : '' }}>Количество</option>
                    </select>
                </div>
                
                <div class="col-md-2 mb-3">
                    <label class="form-label">Порядок</label>
                    <select name="sort_order" class="form-control form-control-sm">
                        <option value="asc" {{ $sortOrder === 'asc' ? 'selected' : '' }}>По возрастанию</option>
                        <option value="desc" {{ $sortOrder === 'desc' ? 'selected' : '' }}>По убыванию</option>
                    </select>
                </div>
                
                <div class="col-md-7 d-flex align-items-end mb-3">
                    <button type="submit" class="btn btn-primary btn-sm mr-2">
                        <i class="fas fa-filter mr-1"></i>Применить фильтры
                    </button>
                    <a href="{{ route('admin.manual-delivery.index', ['delivery_type' => $deliveryType]) }}" 
                       class="btn btn-secondary btn-sm">
                        <i class="fas fa-times mr-1"></i>Сбросить
                    </a>
                </div>
            </form>
        </div>
        <div class="card-body p-0">
            @if($pendingOrders->isEmpty())
                <div class="text-center py-5">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <p class="text-muted">Нет заказов, ожидающих обработки</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover manual-delivery-table">
                        <thead>
                            <tr>
                                <th>Номер заказа</th>
                                <th>Статус</th>
                                <th>Покупатель</th>
                                <th>Товар</th>
                                <th>Количество</th>
                                <th>Сумма</th>
                                <th>
                                    <a href="{{ route('admin.manual-delivery.index', array_merge(request()->query(), ['sort_by' => 'created_at', 'sort_order' => $sortBy === 'created_at' && $sortOrder === 'asc' ? 'desc' : 'asc'])) }}" 
                                       class="text-dark text-decoration-none">
                                        Дата создания
                                        @if($sortBy === 'created_at')
                                            <i class="fas fa-sort-{{ $sortOrder === 'asc' ? 'up' : 'down' }}"></i>
                                        @endif
                                    </a>
                                </th>
                                <th>Время в обработке</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingOrders as $order)
                                <tr>
                                    <td>
                                        <strong>#{{ $order->order_number }}</strong>
                                    </td>
                                    <td>
                                        @if($order->status === \App\Models\Purchase::STATUS_PROCESSING)
                                            <span class="badge badge-warning">В обработке</span>
                                        @elseif($order->status === \App\Models\Purchase::STATUS_COMPLETED)
                                            <span class="badge badge-success">Обработано</span>
                                            @if($order->processed_at)
                                                <br><small class="text-muted">{{ $order->processed_at->format('d.m.Y H:i') }}</small>
                                            @endif
                                        @else
                                            <span class="badge badge-secondary">{{ $order->status }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($order->user)
                                            <div>{{ $order->user->name }}</div>
                                            <small class="text-muted">{{ $order->user->email }}</small>
                                        @else
                                            <div>Гость</div>
                                            <small class="text-muted">{{ $order->guest_email }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $order->serviceAccount->title ?? 'Товар удален' }}
                                    </td>
                                    <td>
                                        <span class="badge badge-info">{{ $order->quantity }} шт.</span>
                                    </td>
                                    <td>
                                        <strong>{{ number_format($order->total_amount, 2) }} {{ $order->transaction->currency ?? 'USD' }}</strong>
                                    </td>
                                    <td>
                                        {{ $order->created_at->format('d.m.Y H:i') }}
                                    </td>
                                    <td>
                                        @php
                                            $minutesInProcessing = $order->created_at->diffInMinutes(now());
                                            $hoursInProcessing = floor($minutesInProcessing / 60);
                                            $remainingMinutes = $minutesInProcessing % 60;
                                            $daysInProcessing = floor($hoursInProcessing / 24);
                                            $remainingHours = $hoursInProcessing % 24;
                                        @endphp
                                        @if($daysInProcessing > 0)
                                            <span class="badge badge-{{ $daysInProcessing >= 3 ? 'danger' : ($daysInProcessing >= 2 ? 'warning' : 'info') }}">
                                                {{ $daysInProcessing }} дн. {{ $remainingHours }} ч. {{ $remainingMinutes }} мин.
                                            </span>
                                        @elseif($hoursInProcessing > 0)
                                            <span class="badge badge-info">{{ $hoursInProcessing }} ч. {{ $remainingMinutes }} мин.</span>
                                        @else
                                            <span class="badge badge-info">{{ $remainingMinutes }} мин.</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.manual-delivery.show', $order) }}" 
                                           class="btn btn-sm {{ $order->status === \App\Models\Purchase::STATUS_PROCESSING ? 'btn-primary' : 'btn-info' }}">
                                            <i class="fas {{ $order->status === \App\Models\Purchase::STATUS_PROCESSING ? 'fa-edit' : 'fa-eye' }} mr-1"></i>
                                            {{ $order->status === \App\Models\Purchase::STATUS_PROCESSING ? 'Обработать' : 'Просмотр' }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <!-- Пагинация -->
                <div class="card-footer clearfix">
                    <div class="float-right">
                        {{ $pendingOrders->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
@stop

@section('css')
    @include('admin.layouts.modern-styles')
@endsection

@section('js')
<script>
(function() {
    let lastCount = {{ $statistics['pending'] }};
    
    // Звук уведомления
    const notificationSound = new Audio('{{ asset("assets/admin/sounds/notification.mp3") }}');
    notificationSound.volume = 0.5;
    
    function playNotificationSound() {
        try {
            notificationSound.play().catch(e => {
                // Если не удалось воспроизвести, используем Web Audio API как fallback
                if (!window.AudioContext && !window.webkitAudioContext) return;
                
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();
                
                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);
                
                oscillator.frequency.value = 800;
                oscillator.type = 'sine';
                
                gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);
                
                oscillator.start(audioContext.currentTime);
                oscillator.stop(audioContext.currentTime + 0.3);
            });
        } catch(e) {
            console.log('Sound play failed:', e);
        }
    }
    
    function updateStatistics() {
        // Clear any existing timeout to prevent duplicates
        if (window.updateStatsTimeout) clearTimeout(window.updateStatsTimeout);
        
        fetch('{{ route("admin.manual-delivery.statistics") }}', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            credentials: 'same-origin'
        })
            .then(response => {
                // Проверяем, что ответ действительно JSON
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('Response is not JSON');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    const newCount = data.data.pending;
                    const pendingElement = document.querySelector('.stat-card-warning .stat-value');
                    const titleElement = document.querySelector('.card-title');
                    
                    // Обновляем счетчики
                    if (pendingElement) {
                        pendingElement.textContent = newCount;
                    }
                    if (titleElement) {
                        titleElement.innerHTML = '<i class="fas fa-list mr-2"></i>Заказы с ручной выдачей (' + newCount + ')';
                    }
                    
                    // Если появились новые заказы - звуковое уведомление
                    if (newCount > lastCount && lastCount >= 0) {
                        playNotificationSound();
                        // Показываем визуальное уведомление
                        if (typeof toastr !== 'undefined') {
                            toastr.warning('Появился новый заказ на обработку!', 'Новый заказ', {
                                timeOut: 5000,
                                positionClass: 'toast-top-right'
                            });
                        }
                    }
                    
                    lastCount = newCount;
                    lastUpdateTime = Date.now();
                    
                    // Обновляем badge в меню
                    updateMenuBadge(newCount);
                }
                
                // SUCCESS: Poll again in 60s
                window.updateStatsTimeout = setTimeout(updateStatistics, 60000);
            })
            .catch(error => {
                // Логируем ошибку только в development режиме
                if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                    console.error('Error updating statistics:', error);
                }
                
                // ERROR: Poll again in 120s (backoff)
                window.updateStatsTimeout = setTimeout(updateStatistics, 120000);
            });
    }
    
    function updateMenuBadge(count) {
        const $li = document.getElementById('manual-delivery-count');
        if (!$li) return;

        const $p = $li.querySelector('a.nav-link p');
        if (!$p) return;

        let $badge = $li.querySelector('.badge');

        if (count > 0) {
            if (!$badge) {
                $badge = document.createElement('span');
                $badge.className = 'badge badge-warning right';
                $p.appendChild($badge);
            }
            $badge.textContent = count > 99 ? '99+' : count;
            $badge.style.display = 'inline-block';
        } else if ($badge) {
            $badge.style.display = 'none';
        }
    }
    
    // Обновляем badge в меню при загрузке страницы
    updateMenuBadge(lastCount);
    
    // Обновляем статистику сразу при загрузке (start nested loop)
    updateStatistics();
})();
</script>
@stop
