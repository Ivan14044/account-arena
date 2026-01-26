@extends('adminlte::page')

@section('title', __('Покупка') . ' #' . $purchase->order_number)

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="m-0 font-weight-light">
                    <i class="fas fa-shopping-bag mr-2"></i>{{ __('Покупка') }} #{{ $purchase->order_number }}
                    <small class="text-muted ml-2">(ID: {{ $purchase->id }})</small>
                </h1>
            </div>
            <div>
                <a href="{{ route('admin.purchases.index') }}" class="btn btn-secondary btn-modern">
                    <i class="fas fa-arrow-left mr-2"></i>{{ __('Назад к списку') }}
                </a>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="row">
        <!-- Основная информация -->
        <div class="col-md-8">
            <div class="card card-modern mb-4">
                <div class="card-header-modern">
                    <h5 class="mb-0 font-weight-normal"><i class="fas fa-info-circle mr-2 text-muted"></i>{{ __('Информация о покупке') }}</h5>
                </div>
                <div class="card-body-modern p-0">
                    <table class="table table-hover mb-0">
                        <tr>
                            <th style="width: 250px;" class="pl-4">{{ __('Номер заказа') }}</th>
                            <td><code class="text-primary font-weight-bold" style="font-size: 1.1rem;">{{ $purchase->order_number }}</code></td>
                        </tr>
                        <tr>
                            <th class="pl-4">{{ __('Покупатель') }}</th>
                            <td>
                                @if($purchase->user)
                                    <div class="d-flex align-items-center">
                                        <div class="mr-3">
                                            <div class="avatar-circle-sm bg-primary-soft text-primary">
                                                {{ strtoupper($purchase->user->name[0] ?? 'U') }}
                                            </div>
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
                                        <div class="mr-3">
                                            <div class="avatar-circle-sm bg-info-soft text-info text-center pt-1" style="width: 32px; height: 32px; border-radius: 50%; font-size: 0.8rem; line-height: 24px;">
                                                <i class="fas fa-user-secret"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <span class="text-dark font-weight-bold">{{ $purchase->guest_email }}</span>
                                            <br>
                                            <span class="badge badge-info-soft text-info badge-pill">{{ __('Гостевой заказ') }}</span>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted">{{ __('Не указан') }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th class="pl-4">{{ __('Товар') }}</th>
                            <td>
                                @if($purchase->serviceAccount)
                                    <a href="{{ route('admin.service-accounts.edit', $purchase->serviceAccount) }}" class="font-weight-500">
                                        {{ $purchase->serviceAccount->title }}
                                    </a>
                                    @if($purchase->serviceAccount->sku)
                                        <br><small class="text-muted">SKU: {{ $purchase->serviceAccount->sku }}</small>
                                    @endif
                                @else
                                    <span class="text-danger font-weight-bold">{{ __('Товар удален') }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th class="pl-4">{{ __('Количество') }}</th>
                            <td><span class="badge badge-info badge-modern">{{ $purchase->quantity }} {{ __('шт.') }}</span></td>
                        </tr>
                        <tr>
                            <th class="pl-4">{{ __('Цена за единицу') }}</th>
                            <td>${{ number_format($purchase->price, 2) }}</td>
                        </tr>
                        <tr>
                            <th class="pl-4">{{ __('Общая сумма') }}</th>
                            <td>
                                <div class="h5 mb-0 text-success font-weight-bold">${{ number_format($purchase->total_amount, 2) }}</div>
                            </td>
                        </tr>
                        <tr>
                            <th class="pl-4">{{ __('Статус') }}</th>
                            <td>
                                <span class="badge badge-{{ $purchase->getStatusBadgeClass() }} badge-modern px-3 py-2" style="font-size: 0.85rem;">
                                    {{ $purchase->getStatusText() }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th class="pl-4">{{ __('Дата создания') }}</th>
                            <td>
                                <div class="text-dark">{{ $purchase->created_at->translatedFormat('d F Y, H:i') }}</div>
                                <small class="text-muted">{{ $purchase->created_at->diffForHumans() }}</small>
                            </td>
                        </tr>
                        @if($purchase->transaction)
                            <tr>
                                <th class="pl-4">{{ __('ID транзакции') }}</th>
                                <td><code class="text-muted">#{{ $purchase->transaction->id }}</code></td>
                            </tr>
                            <tr>
                                <th class="pl-4">{{ __('Метод оплаты') }}</th>
                                <td>
                                    @php
                                        $methodNames = [
                                            'balance' => __('Баланс'),
                                            'crypto' => __('Криптовалюта'),
                                            'credit_card' => __('Банковская карта'),
                                            'free' => __('Бесплатно'),
                                        ];
                                        $method = $purchase->transaction->payment_method;
                                    @endphp
                                    <span class="badge badge-light border">{{ $methodNames[$method] ?? $method }}</span>
                                </td>
                            </tr>
                        @endif
                    </table>
                </div>
            </div>

            <!-- Данные аккаунтов -->
            <div class="card card-modern mb-4 overflow-hidden">
                <div class="card-header-modern bg-success-soft">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-success"><i class="fas fa-key mr-2"></i>{{ __('Выданные данные') }} ({{ count($purchase->account_data ?? []) }})</h5>
                        @if(is_array($purchase->account_data) && count($purchase->account_data) > 0)
                            <button class="btn btn-sm btn-success btn-modern" 
                                    onclick="downloadAllPurchaseAccounts(this)" 
                                    data-accounts="{{ json_encode($purchase->account_data) }}"
                                    data-order="{{ $purchase->order_number }}">
                                <i class="fas fa-file-download mr-1"></i> {{ __('Скачать всё') }} (.txt)
                            </button>
                        @endif
                    </div>
                </div>
                <div class="card-body-modern p-4">
                    @if(is_array($purchase->account_data) && count($purchase->account_data) > 0)
                        <div class="row">
                            @foreach($purchase->account_data as $index => $accountItem)
                                <div class="col-12 mb-3">
                                    <div class="account-data-item p-3 rounded border bg-light shadow-none">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="badge badge-light border">{{ __('Аккаунт') }} #{{ $index + 1 }}</span>
                                            <div class="btn-group">
                                                <button class="btn btn-xs btn-outline-primary" 
                                                        onclick="copyToClipboard(this)" 
                                                        data-text="{{ is_string($accountItem) ? $accountItem : json_encode($accountItem, JSON_UNESCAPED_UNICODE) }}"
                                                        title="{{ __('Копировать') }}">
                                                    <i class="fas fa-copy mr-1"></i>{{ __('Копировать') }}
                                                </button>
                                                <button class="btn btn-xs btn-outline-info" 
                                                        onclick="downloadSingleAccount(this)" 
                                                        data-text="{{ is_string($accountItem) ? $accountItem : json_encode($accountItem, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}"
                                                        data-filename="order_{{ $purchase->order_number }}_acc_{{ $index + 1 }}.txt"
                                                        title="{{ __('Скачать') }}">
                                                    <i class="fas fa-download"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <pre class="mb-0 bg-white p-3 rounded border" style="font-size: 0.95rem; font-family: 'Fira Code', 'Roboto Mono', monospace;">{{ is_string($accountItem) ? $accountItem : json_encode($accountItem, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-exclamation-triangle fa-3x text-warning opacity-50 mb-3"></i>
                            <p class="text-muted mb-0">{{ __('Нет данных для этой покупки') }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Заметки и обработка -->
            @if($purchase->admin_notes || $purchase->processing_notes || $purchase->processed_by)
                <div class="card card-modern border-warning-soft">
                    <div class="card-header-modern bg-warning-soft">
                        <h5 class="mb-0 text-warning"><i class="fas fa-clipboard-list mr-2"></i>{{ __('Заметки и история обработки') }}</h5>
                    </div>
                    <div class="card-body-modern p-4">
                        <div class="row">
                            @if($purchase->processing_notes)
                                <div class="col-md-6 mb-4 mb-md-0">
                                    <div class="font-weight-bold small text-muted text-uppercase mb-2">{{ __('Инструкции к заказу') }}</div>
                                    <div class="p-3 bg-light rounded border border-warning-soft">
                                        {{ $purchase->processing_notes }}
                                    </div>
                                </div>
                            @endif

                            @if($purchase->admin_notes)
                                <div class="col-md-6">
                                    <div class="font-weight-bold small text-muted text-uppercase mb-2">{{ __('Внутренние заметки') }}</div>
                                    <div class="p-3 bg-light rounded border border-warning-soft">
                                        {{ $purchase->admin_notes }}
                                    </div>
                                </div>
                            @endif
                        </div>

                        @if($purchase->processed_by)
                            <div class="mt-4 pt-4 border-top">
                                <div class="d-flex align-items-center">
                                    <div class="mr-3">
                                        <div class="avatar-circle bg-light text-muted border">
                                            <i class="fas fa-user-cog"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-muted small">{{ __('Обработал заказ') }}:</div>
                                        <div class="font-weight-bold text-dark">
                                            @if($purchase->processor)
                                                {{ $purchase->processor->name }} <span class="text-muted font-weight-normal ml-1">({{ $purchase->processor->email }})</span>
                                            @else
                                                Admin ID #{{ $purchase->processed_by }}
                                            @endif
                                        </div>
                                        <div class="text-muted small">
                                            <i class="far fa-clock mr-1"></i>
                                            {{ $purchase->processed_at ? $purchase->processed_at->translatedFormat('d F Y, H:i') : __('Неизвестно') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <!-- Боковая панель -->
        <div class="col-md-4">
            <!-- Действия -->
            <div class="card card-modern mb-4">
                <div class="card-header-modern">
                    <h5 class="mb-0 font-weight-normal"><i class="fas fa-bolt mr-2 text-primary"></i>{{ __('Действия') }}</h5>
                </div>
                <div class="card-body-modern p-3">
                    @if($purchase->user)
                        <a href="{{ route('admin.users.edit', $purchase->user) }}" class="btn btn-light btn-block btn-modern text-left py-2 mb-2">
                            <i class="fas fa-user-circle mr-3 text-info"></i>{{ __('Профиль покупателя') }}
                        </a>
                    @endif

                    @if($purchase->serviceAccount)
                        <a href="{{ route('admin.service-accounts.edit', $purchase->serviceAccount) }}" class="btn btn-light btn-block btn-modern text-left py-2 mb-2">
                            <i class="fas fa-store mr-3 text-warning"></i>{{ __('Редактировать товар') }}
                        </a>
                    @endif
                    
                    <button class="btn btn-light btn-block btn-modern text-left py-2 mb-2" onclick="window.print()">
                        <i class="fas fa-print mr-3 text-secondary"></i>{{ __('Печать счета') }}
                    </button>
                </div>
            </div>

            <!-- Статистика пользователя -->
            @if($purchase->user)
                <div class="card card-modern mb-4 shadow-sm border-0">
                    <div class="card-header-modern">
                        <h5 class="mb-0 font-weight-normal"><i class="fas fa-chart-line mr-2 text-success"></i>{{ __('Статистика клиента') }}</h5>
                    </div>
                    <div class="card-body-modern p-0">
                        <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                            <div class="text-muted small uppercase font-weight-bold">{{ __('Баланс') }}</div>
                            <div class="h5 mb-0 font-weight-bold text-dark">${{ number_format($purchase->user->balance, 2) }}</div>
                        </div>
                        <div class="p-4 d-flex justify-content-between align-items-center">
                            <div class="text-muted small uppercase font-weight-bold">{{ __('Всего покупок') }}</div>
                            <div class="h5 mb-0 font-weight-bold text-dark">{{ $purchase->user->purchases()->count() }}</div>
                        </div>
                    </div>
                    <div class="card-footer-modern bg-light text-center p-3">
                        <a href="{{ route('admin.purchases.index', ['user_id' => $purchase->user_id]) }}" class="small font-weight-bold">
                            {{ __('Все заказы клиента') }} <i class="fas fa-chevron-right ml-1"></i>
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@section('css')
    @include('admin.layouts.modern-styles')
@endsection

@section('js')
<script>
function copyToClipboard(button) {
    const text = button.getAttribute('data-text');
    const originalContent = button.innerHTML;
    
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            button.innerHTML = '<i class="fas fa-check mr-1"></i>{{ __("Скопировано") }}';
            button.classList.remove('btn-outline-primary');
            button.classList.add('btn-success');
            
            setTimeout(() => {
                button.innerHTML = originalContent;
                button.classList.remove('btn-success');
                button.classList.add('btn-outline-primary');
            }, 2000);
        });
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
    
    let content = "Order: " + orderNumber + "\n";
    content += "Date: " + new Date().toLocaleString() + "\n";
    content += "------------------------------------------\n\n";
    
    accounts.forEach((acc, index) => {
        content += `[Account #${index + 1}]\n`;
        content += (typeof acc === 'string' ? acc : JSON.stringify(acc, null, 2)) + "\n";
        content += "------------------------------------------\n\n";
    });
    
    downloadFile(content, `order_${orderNumber}_accounts.txt`);
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




