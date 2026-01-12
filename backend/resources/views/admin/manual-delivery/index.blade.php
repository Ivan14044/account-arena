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
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $statistics['pending'] }}</h3>
                    <p>Ожидают обработки</p>
                </div>
                <div class="icon">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $statistics['processed_today'] }}</h3>
                    <p>Обработано сегодня</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $statistics['processed_this_week'] }}</h3>
                    <p>Обработано на этой неделе</p>
                </div>
                <div class="icon">
                    <i class="fas fa-calendar-week"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $statistics['average_processing_time'] ?? 'N/A' }}</h3>
                    <p>Среднее время (часов)</p>
                </div>
                <div class="icon">
                    <i class="fas fa-hourglass-half"></i>
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
                    Заказы на обработку ({{ $pendingOrders->count() }})
                </h3>
                <div class="card-tools d-flex gap-2 align-items-center">
                    <a href="{{ route('admin.manual-delivery.analytics') }}" class="btn btn-sm btn-info">
                        <i class="fas fa-chart-bar mr-1"></i>Аналитика
                    </a>
                    <form method="GET" action="{{ route('admin.manual-delivery.index') }}" class="d-inline">
                        <select name="delivery_type" 
                                class="form-control form-control-sm" 
                                onchange="this.form.submit()"
                                style="width: auto; display: inline-block;">
                            <option value="all" {{ $deliveryType === 'all' ? 'selected' : '' }}>Все типы выдачи</option>
                            <option value="automatic" {{ $deliveryType === 'automatic' ? 'selected' : '' }}>Автоматическая выдача</option>
                            <option value="manual" {{ $deliveryType === 'manual' ? 'selected' : '' }}>Ручная выдача</option>
                        </select>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Форма фильтров и сортировки -->
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('admin.manual-delivery.index') }}" class="row g-3">
                <!-- Скрытые поля для сохранения текущих фильтров -->
                <input type="hidden" name="delivery_type" value="{{ $deliveryType }}">
                
                <div class="col-md-3">
                    <label class="form-label">Дата создания (с)</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Дата создания (по)</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo }}">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Email покупателя</label>
                    <input type="text" name="customer_email" class="form-control form-control-sm" 
                           placeholder="email@example.com" value="{{ $customerEmail }}">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">ID покупателя</label>
                    <input type="number" name="customer_id" class="form-control form-control-sm" 
                           placeholder="ID" value="{{ $customerId }}">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Номер заказа</label>
                    <input type="text" name="order_number" class="form-control form-control-sm" 
                           placeholder="ORD-..." value="{{ $orderNumber }}">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Сортировать по</label>
                    <select name="sort_by" class="form-control form-control-sm">
                        <option value="created_at" {{ $sortBy === 'created_at' ? 'selected' : '' }}>Дата создания</option>
                        <option value="total_amount" {{ $sortBy === 'total_amount' ? 'selected' : '' }}>Сумма заказа</option>
                        <option value="quantity" {{ $sortBy === 'quantity' ? 'selected' : '' }}>Количество</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Порядок</label>
                    <select name="sort_order" class="form-control form-control-sm">
                        <option value="asc" {{ $sortOrder === 'asc' ? 'selected' : '' }}>По возрастанию</option>
                        <option value="desc" {{ $sortOrder === 'desc' ? 'selected' : '' }}>По убыванию</option>
                    </select>
                </div>
                
                <div class="col-md-7 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">
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
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Номер заказа</th>
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
                                            $hoursInProcessing = $order->created_at->diffInHours(now());
                                            $daysInProcessing = $order->created_at->diffInDays(now());
                                        @endphp
                                        @if($daysInProcessing > 0)
                                            <span class="badge badge-{{ $daysInProcessing >= 3 ? 'danger' : ($daysInProcessing >= 2 ? 'warning' : 'info') }}">
                                                {{ $daysInProcessing }} дн. {{ $hoursInProcessing % 24 }} ч.
                                            </span>
                                        @else
                                            <span class="badge badge-info">{{ $hoursInProcessing }} ч.</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.manual-delivery.show', $order) }}" 
                                           class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit mr-1"></i>
                                            Обработать
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@stop

@section('js')
<script>
(function() {
    let lastCount = {{ $statistics['pending'] }};
    let soundPlayed = false;
    let lastUpdateTime = Date.now();
    
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
        fetch('{{ route("admin.manual-delivery.statistics") }}')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const newCount = data.data.pending;
                    const pendingElement = document.querySelector('.small-box.bg-warning .inner h3');
                    const titleElement = document.querySelector('.card-title');
                    
                    // Обновляем счетчики
                    if (pendingElement) {
                        pendingElement.textContent = newCount;
                    }
                    if (titleElement) {
                        titleElement.innerHTML = '<i class="fas fa-list mr-2"></i>Заказы на обработку (' + newCount + ')';
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
            })
            .catch(error => console.error('Error updating statistics:', error));
    }
    
    function updateMenuBadge(count) {
        const menuBadge = document.querySelector('#manual-delivery-count');
        if (menuBadge) {
            if (count > 0) {
                menuBadge.textContent = count > 99 ? '99+' : count;
                menuBadge.className = 'badge badge-warning navbar-badge';
                menuBadge.style.display = 'inline-block';
            } else {
                menuBadge.style.display = 'none';
            }
        }
    }
    
    // Обновляем каждые 30 секунд
    setInterval(updateStatistics, 30000);
    
    // Обновляем badge в меню при загрузке страницы
    updateMenuBadge(lastCount);
    
    // Обновляем статистику сразу при загрузке
    updateStatistics();
})();
</script>
@stop
