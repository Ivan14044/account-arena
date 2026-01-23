@extends('adminlte::page')

@section('title', 'Претензия #' . $dispute->id)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Претензия #{{ $dispute->id }}</h1>
        <a href="{{ route('admin.disputes.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Назад к списку
        </a>
    </div>
@stop

@section('content')
    <div class="row">
        {{-- Основная информация --}}
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Информация о претензии</h3>
                    <div class="card-tools">
                        <span class="badge {{ $dispute->getStatusBadgeClass() }} badge-lg">
                            {{ $dispute->getStatusText() }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-3">Дата создания:</dt>
                        <dd class="col-sm-9">{{ $dispute->created_at->format('d.m.Y H:i:s') }}</dd>

                        <dt class="col-sm-3">Покупатель:</dt>
                        <dd class="col-sm-9">
                            <a href="{{ route('admin.users.edit', $dispute->user) }}">
                                {{ $dispute->user->name }} ({{ $dispute->user->email }})
                            </a>
                        </dd>

                        <dt class="col-sm-3">Товар:</dt>
                        <dd class="col-sm-9">
                            @if($dispute->serviceAccount)
                                <strong>{{ $dispute->serviceAccount->title }}</strong><br>
                                @if($dispute->serviceAccount->sku)
                                    <small class="text-muted">SKU: {{ $dispute->serviceAccount->sku }}</small>
                                @endif
                            @else
                                <span class="text-muted">Товар удален</span>
                            @endif
                        </dd>

                        <dt class="col-sm-3">Поставщик:</dt>
                        <dd class="col-sm-9">
                            @if($dispute->supplier_id && $dispute->supplier)
                                <a href="{{ route('admin.suppliers.show', $dispute->supplier) }}" class="text-primary">
                                    <i class="fas fa-user-tag"></i> {{ $dispute->supplier->name }} ({{ $dispute->supplier->email }})
                                </a>
                            @else
                                <span class="badge badge-info">
                                    <i class="fas fa-shield-alt"></i> Товар администратора
                                </span>
                            @endif
                        </dd>

                        <dt class="col-sm-3">Транзакция:</dt>
                        <dd class="col-sm-9">
                            @if($dispute->transaction)
                                @if($dispute->transaction->purchase && $dispute->transaction->purchase->order_number)
                                    <a href="{{ route('admin.purchases.show', $dispute->transaction->purchase->id) }}" class="text-primary">
                                        {{ $dispute->transaction->purchase->order_number }}
                                    </a>
                                @else
                                    ID: #{{ $dispute->transaction->id }}
                                @endif
                                — ${{ number_format($dispute->transaction->amount, 2) }}
                                <br>
                                <small class="text-muted">{{ $dispute->transaction->created_at->format('d.m.Y H:i') }}</small>
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </dd>

                        <dt class="col-sm-3">Причина:</dt>
                        <dd class="col-sm-9">
                            <span class="badge badge-secondary">{{ $dispute->getReasonText() }}</span>
                        </dd>

                        <dt class="col-sm-3">Описание проблемы:</dt>
                        <dd class="col-sm-9">
                            <div class="alert alert-light">
                                {{ $dispute->customer_description }}
                            </div>
                        </dd>

                        @if($dispute->screenshot_url)
                            <dt class="col-sm-3">Скриншот:</dt>
                            <dd class="col-sm-9">
                                <div class="mb-2">
                                    <a href="{{ $dispute->screenshot_url }}" target="_blank" class="btn btn-sm btn-info">
                                        <i class="fas fa-external-link-alt"></i> Открыть скриншот
                                    </a>
                                    @if($dispute->screenshot_type === 'upload')
                                        <span class="badge badge-success">Загружен файл</span>
                                    @else
                                        <span class="badge badge-info">Ссылка</span>
                                    @endif
                                </div>
                                <div class="border p-2">
                                    <img src="{{ $dispute->screenshot_url }}" 
                                         alt="Скриншот проблемы" 
                                         class="img-fluid"
                                         style="max-height: 400px; cursor: pointer;"
                                         onclick="window.open('{{ $dispute->screenshot_url }}', '_blank')">
                                </div>
                            </dd>
                        @endif

                        @if($dispute->resolved_at)
                            <dt class="col-sm-3">Дата решения:</dt>
                            <dd class="col-sm-9">{{ $dispute->resolved_at->format('d.m.Y H:i:s') }}</dd>

                            <dt class="col-sm-3">Решение:</dt>
                            <dd class="col-sm-9">
                                <span class="badge badge-info">{{ $dispute->getDecisionText() }}</span>
                            </dd>

                            @if($dispute->refund_amount)
                                <dt class="col-sm-3">Сумма возврата:</dt>
                                <dd class="col-sm-9">${{ number_format($dispute->refund_amount, 2) }}</dd>
                            @endif

                            @if($dispute->admin_comment)
                                <dt class="col-sm-3">Комментарий администратора:</dt>
                                <dd class="col-sm-9">
                                    <div class="alert alert-info">
                                        {{ $dispute->admin_comment }}
                                    </div>
                                </dd>
                            @endif

                            @if($dispute->resolver)
                                <dt class="col-sm-3">Обработал:</dt>
                                <dd class="col-sm-9">{{ $dispute->resolver->name }}</dd>
                            @endif
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        {{-- Действия --}}
        <div class="col-md-4">
            @if($dispute->status === 'new' || $dispute->status === 'in_review')
                {{-- Кнопка "Взять на рассмотрение" --}}
                @if($dispute->status === 'new')
                    <div class="card card-warning mb-3">
                        <div class="card-header">
                            <h3 class="card-title">Действия</h3>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('admin.disputes.mark-in-review', $dispute) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-warning btn-block">
                                    <i class="fas fa-eye"></i> Взять на рассмотрение
                                </button>
                            </form>
                        </div>
                    </div>
                @endif

                {{-- Форма возврата средств --}}
                <div class="card card-success">
                    <div class="card-header bg-success text-white">
                        <h3 class="card-title"><i class="fas fa-undo mr-2"></i>Сделать возврат</h3>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.disputes.resolve-refund', $dispute) }}" method="POST">
                            @csrf
                            @method('POST')
                            
                            <div class="form-group">
                                <label>Сумма возврата:</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">$</span>
                                    </div>
                                    <input type="text" class="form-control" 
                                           value="{{ number_format($dispute->transaction->amount, 2) }}" 
                                           readonly>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" 
                                           id="deduct_from_supplier" name="deduct_from_supplier" 
                                           value="1" checked>
                                    <label class="custom-control-label" for="deduct_from_supplier">
                                        Списать с баланса поставщика
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Комментарий <span class="text-danger">*</span></label>
                                <textarea name="admin_comment" class="form-control" rows="3" 
                                          required placeholder="Укажите причину возврата..."></textarea>
                            </div>

                            <button type="submit" class="btn btn-success btn-block" 
                                    onclick="return confirm('Вернуть средства покупателю?')">
                                <i class="fas fa-dollar-sign"></i> Вернуть средства
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Форма замены товара --}}
                <div class="card card-info">
                    <div class="card-header bg-info text-white">
                        <h3 class="card-title"><i class="fas fa-exchange-alt mr-2"></i>Выдать замену</h3>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.disputes.resolve-replacement', $dispute) }}" method="POST" id="replacementForm">
                            @csrf
                            @method('POST')

                            <div class="form-group">
                                <label>Выберите товар на замену:</label>
                                <select name="replacement_account_id" class="form-control" id="replacementProduct" required>
                                    <option value="">Загрузка...</option>
                                </select>
                                <small class="text-muted">Доступные товары того же типа от этого поставщика</small>
                            </div>

                            <div class="form-group">
                                <label>Комментарий <span class="text-danger">*</span></label>
                                <textarea name="admin_comment" class="form-control" rows="3" 
                                          required placeholder="Укажите причину замены..."></textarea>
                            </div>

                            <button type="submit" class="btn btn-info btn-block" 
                                    onclick="return confirm('Выдать замену покупателю?')">
                                <i class="fas fa-exchange-alt"></i> Выдать замену
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Форма отклонения --}}
                <div class="card card-danger">
                    <div class="card-header">
                        <h3 class="card-title">Отклонить претензию</h3>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.disputes.reject', $dispute) }}" method="POST">
                            @csrf
                            @method('POST')

                            <div class="form-group">
                                <label>Причина отклонения <span class="text-danger">*</span></label>
                                <textarea name="admin_comment" class="form-control" rows="3" 
                                          required placeholder="Укажите причину отклонения..."></textarea>
                            </div>

                            <button type="submit" class="btn btn-danger btn-block" 
                                    onclick="return confirm('Отклонить претензию?')">
                                <i class="fas fa-times"></i> Отклонить
                            </button>
                        </form>
                    </div>
                </div>
            @else
                {{-- Претензия уже обработана --}}
                <div class="card card-{{ $dispute->status === 'resolved' ? 'success' : 'danger' }}">
                    <div class="card-header">
                        <h3 class="card-title">Статус</h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-{{ $dispute->status === 'resolved' ? 'success' : 'danger' }}">
                            <i class="fas fa-{{ $dispute->status === 'resolved' ? 'check-circle' : 'times-circle' }}"></i>
                            Претензия {{ $dispute->status === 'resolved' ? 'решена' : 'отклонена' }}
                        </div>
                        <p><strong>Решение:</strong> {{ $dispute->getDecisionText() }}</p>
                        @if($dispute->resolver)
                            <p><strong>Обработал:</strong> {{ $dispute->resolver->name }}</p>
                        @endif
                        <p><strong>Дата:</strong> {{ $dispute->resolved_at->format('d.m.Y H:i:s') }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
@stop

@section('js')
<script>
$(document).ready(function() {
    // Загружаем доступные товары для замены
    $.ajax({
        url: '{{ route('admin.disputes.replacement-products', $dispute) }}',
        method: 'GET',
        success: function(response) {
            const select = $('#replacementProduct');
            select.empty();
            
            if (response.products && response.products.length > 0) {
                select.append('<option value="">Выберите товар...</option>');
                response.products.forEach(function(product) {
                    let skuText = product.sku ? ` [${product.sku}]` : '';
                    select.append(`<option value="${product.id}">${product.title}${skuText}</option>`);
                });
            } else {
                select.append('<option value="">Нет доступных товаров</option>');
                $('#replacementForm button[type="submit"]').prop('disabled', true);
            }
        },
        error: function() {
            $('#replacementProduct').html('<option value="">Ошибка загрузки</option>');
            $('#replacementForm button[type="submit"]').prop('disabled', true);
        }
    });
});
</script>
@stop

@section('css')
<style>
    .badge-lg {
        font-size: 1rem;
        padding: 0.5rem 1rem;
    }
</style>
@stop

