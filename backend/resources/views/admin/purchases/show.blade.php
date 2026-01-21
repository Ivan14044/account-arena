@extends('adminlte::page')

@section('title', 'Покупка #' . $purchase->order_number)

@section('content_header')
    <h1>
        <i class="fas fa-shopping-bag"></i> Покупка #{{ $purchase->order_number }}
        <small class="text-muted">(ID: {{ $purchase->id }})</small>
    </h1>
@stop

@section('content')
    <div class="row">
        <!-- Основная информация -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-info-circle"></i> Информация о покупке</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th style="width: 200px;">Номер заказа</th>
                            <td><code class="text-primary">{{ $purchase->order_number }}</code></td>
                        </tr>
                        <tr>
                            <th>Пользователь</th>
                            <td>
                                @if($purchase->user)
                                    <a href="{{ route('admin.users.edit', $purchase->user) }}">
                                        <i class="fas fa-user"></i> {{ $purchase->user->email }}
                                    </a>
                                    <br>
                                    <small class="text-muted">{{ $purchase->user->name }}</small>
                                @elseif($purchase->guest_email)
                                    <i class="fas fa-user"></i> {{ $purchase->guest_email }}
                                    <br>
                                    <small class="text-muted">Гостевой заказ</small>
                                @else
                                    <span class="text-muted">Не указан</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Товар</th>
                            <td>
                                @if($purchase->serviceAccount)
                                    <a href="{{ route('admin.service-accounts.edit', $purchase->serviceAccount) }}">
                                        {{ $purchase->serviceAccount->title }}
                                    </a>
                                @else
                                    <span class="text-muted">Товар удален</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Количество</th>
                            <td><span class="badge badge-info">{{ $purchase->quantity }} шт.</span></td>
                        </tr>
                        <tr>
                            <th>Цена за единицу</th>
                            <td>${{ number_format($purchase->price, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Общая сумма</th>
                            <td><strong class="text-success">${{ number_format($purchase->total_amount, 2) }}</strong></td>
                        </tr>
                        <tr>
                            <th>Статус</th>
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
                        </tr>
                        <tr>
                            <th>Дата покупки</th>
                            <td>
                                {{ $purchase->created_at->format('d.m.Y H:i:s') }}
                                <br>
                                <small class="text-muted">{{ $purchase->created_at->diffForHumans() }}</small>
                            </td>
                        </tr>
                        @if($purchase->transaction)
                            <tr>
                                <th>ID транзакции</th>
                                <td>#{{ $purchase->transaction->id }}</td>
                            </tr>
                            <tr>
                                <th>Метод оплаты</th>
                                <td>
                                    @php
                                        $methods = [
                                            'balance' => 'Баланс',
                                            'crypto' => 'Криптовалюта',
                                            'credit_card' => 'Банковская карта',
                                            'free' => 'Бесплатно',
                                        ];
                                    @endphp
                                    {{ $methods[$purchase->transaction->payment_method] ?? $purchase->transaction->payment_method }}
                                </td>
                            </tr>
                        @endif
                    </table>
                </div>
            </div>

            <!-- Данные аккаунтов -->
            <div class="card">
                <div class="card-header bg-success">
                    <h3 class="card-title">
                        <i class="fas fa-user-lock"></i> Выданные данные аккаунтов ({{ count($purchase->account_data ?? []) }} шт.)
                    </h3>
                    @if(is_array($purchase->account_data) && count($purchase->account_data) > 0)
                        <div class="card-tools">
                            <button class="btn btn-sm btn-light" 
                                    onclick="downloadAllPurchaseAccounts(this)" 
                                    data-accounts="{{ json_encode($purchase->account_data) }}"
                                    data-order="{{ $purchase->order_number }}"
                                    title="Скачать все">
                                <i class="fas fa-file-download text-success"></i> Скачать всё (.txt)
                            </button>
                        </div>
                    @endif
                </div>
                <div class="card-body">
                    @if(is_array($purchase->account_data) && count($purchase->account_data) > 0)
                        @foreach($purchase->account_data as $index => $accountItem)
                            <div class="mb-3 p-3 bg-light border rounded">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-fill">
                                        <h6 class="mb-2">Аккаунт #{{ $index + 1 }}</h6>
                                        <pre class="mb-0" style="font-size: 0.9rem;">{{ is_string($accountItem) ? $accountItem : json_encode($accountItem, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </div>
                                    <div class="btn-group-vertical ml-2">
                                        <button 
                                            class="btn btn-sm btn-primary" 
                                            onclick="copyToClipboard(this)" 
                                            data-text="{{ is_string($accountItem) ? $accountItem : json_encode($accountItem, JSON_UNESCAPED_UNICODE) }}"
                                            title="Копировать">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                        <button 
                                            class="btn btn-sm btn-info" 
                                            onclick="downloadSingleAccount(this)" 
                                            data-text="{{ is_string($accountItem) ? $accountItem : json_encode($accountItem, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}"
                                            data-filename="order_{{ $purchase->order_number }}_acc_{{ $index + 1 }}.txt"
                                            title="Скачать">
                                            <i class="fas fa-download"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> Нет данных аккаунтов для этой покупки
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Боковая панель -->
        <div class="col-md-4">
            <!-- Действия -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-cog"></i> Действия</h3>
                </div>
                <div class="card-body">
                    <a href="{{ route('admin.purchases.index') }}" class="btn btn-secondary btn-block">
                        <i class="fas fa-arrow-left"></i> Назад к списку
                    </a>
                    
                    @if($purchase->user)
                        <a href="{{ route('admin.users.edit', $purchase->user) }}" class="btn btn-info btn-block">
                            <i class="fas fa-user-edit"></i> Профиль пользователя
                        </a>
                    @endif

                    @if($purchase->serviceAccount)
                        <a href="{{ route('admin.service-accounts.edit', $purchase->serviceAccount) }}" class="btn btn-warning btn-block">
                            <i class="fas fa-edit"></i> Редактировать товар
                        </a>
                    @endif
                </div>
            </div>

            <!-- Статистика пользователя -->
            @if($purchase->user)
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-chart-bar"></i> Статистика пользователя</h3>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr>
                                <th>Баланс:</th>
                                <td class="text-right">${{ number_format($purchase->user->balance, 2) }}</td>
                            </tr>
                            <tr>
                                <th>Всего покупок:</th>
                                <td class="text-right">{{ $purchase->user->purchases()->count() }}</td>
                            </tr>
                            <tr>
                                <th>Активных подписок:</th>
                                <td class="text-right">{{ $purchase->user->subscriptions()->where('status', 'active')->count() }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@section('js')
<script>
function copyToClipboard(button) {
    const text = button.getAttribute('data-text');
    
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            // Визуальная обратная связь
            const originalHTML = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check"></i>';
            button.classList.remove('btn-primary');
            button.classList.add('btn-success');
            
            setTimeout(() => {
                button.innerHTML = originalHTML;
                button.classList.remove('btn-success');
                button.classList.add('btn-primary');
            }, 1500);
        }).catch(err => {
            alert('Ошибка копирования: ' + err);
        });
    } else {
        // Fallback для старых браузеров
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        
        alert('Скопировано!');
    }
}

function downloadSingleAccount(button) {
    const text = button.getAttribute('data-text');
    const filename = button.getAttribute('data-filename');
    downloadFile(text, filename);
}

function downloadAllPurchaseAccounts(button) {
    const accounts = JSON.parse(button.getAttribute('data-accounts'));
    const orderNumber = button.getAttribute('data-order');
    
    let content = "";
    accounts.forEach((acc, index) => {
        content += `--- Account #${index + 1} ---\n`;
        content += (typeof acc === 'string' ? acc : JSON.stringify(acc, null, 2)) + "\n\n";
    });
    
    downloadFile(content, `order_${orderNumber}_all_accounts.txt`);
}

function downloadFile(content, filename) {
    const blob = new Blob([content], { type: 'text/plain' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
}
</script>
@endsection




