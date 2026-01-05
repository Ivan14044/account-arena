@extends('adminlte::page')

@section('title', 'Модерация товара #' . $product->id)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Модерация товара #{{ $product->id }}</h1>
        <a href="{{ route('admin.product-moderation.index') }}" class="btn btn-secondary">
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
                    <h3 class="card-title">Информация о товаре</h3>
                    <div class="card-tools">
                        <span class="badge badge-warning badge-lg">
                            Ожидает модерации
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-3">ID товара:</dt>
                        <dd class="col-sm-9">#{{ $product->id }}</dd>

                        <dt class="col-sm-3">Название:</dt>
                        <dd class="col-sm-9">
                            <strong>{{ $product->title }}</strong>
                            @if($product->title_en)
                                <br><small class="text-muted">EN: {{ $product->title_en }}</small>
                            @endif
                            @if($product->title_uk)
                                <br><small class="text-muted">UK: {{ $product->title_uk }}</small>
                            @endif
                        </dd>

                        <dt class="col-sm-3">Поставщик:</dt>
                        <dd class="col-sm-9">
                            @if($product->supplier)
                                <a href="{{ route('admin.users.edit', $product->supplier) }}" class="text-primary">
                                    <i class="fas fa-user-tag"></i> {{ $product->supplier->name }}
                                </a>
                                <br><small class="text-muted">{{ $product->supplier->email }}</small>
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </dd>

                        <dt class="col-sm-3">Категория:</dt>
                        <dd class="col-sm-9">
                            @if($product->category)
                                {{ $product->category->admin_name ?? 'Категория #' . $product->category->id }}
                            @else
                                <span class="text-muted">Не указана</span>
                            @endif
                        </dd>

                        <dt class="col-sm-3">Цена:</dt>
                        <dd class="col-sm-9">
                            <strong>${{ number_format($product->price, 2) }}</strong>
                            @if($product->hasActiveDiscount())
                                <br><small class="text-success">Скидка: {{ $product->discount_percent }}%</small>
                                <br><small class="text-muted">Цена со скидкой: ${{ number_format($product->getCurrentPrice(), 2) }}</small>
                            @endif
                        </dd>

                        <dt class="col-sm-3">Аккаунтов:</dt>
                        <dd class="col-sm-9">
                            @php
                                $accountsData = is_array($product->accounts_data) ? $product->accounts_data : [];
                                $totalAccounts = count($accountsData);
                                $usedAccounts = $product->used ?? 0;
                                $availableAccounts = max(0, $totalAccounts - $usedAccounts);
                            @endphp
                            <span class="badge badge-info">Доступно: {{ $availableAccounts }}</span>
                            <span class="badge badge-secondary">Всего: {{ $totalAccounts }}</span>
                            <span class="badge badge-warning">Использовано: {{ $usedAccounts }}</span>
                        </dd>

                        <dt class="col-sm-3">Дата создания:</dt>
                        <dd class="col-sm-9">
                            {{ $product->created_at->format('d.m.Y H:i:s') }}
                            <br><small class="text-muted">{{ $product->created_at->diffForHumans() }}</small>
                        </dd>

                        @if($product->description)
                            <dt class="col-sm-3">Описание:</dt>
                            <dd class="col-sm-9">
                                <div class="alert alert-light">
                                    {!! nl2br(e($product->description)) !!}
                                </div>
                            </dd>
                        @endif

                        @if($product->image_url)
                            <dt class="col-sm-3">Изображение:</dt>
                            <dd class="col-sm-9">
                                <img src="{{ $product->image_url }}" alt="{{ $product->title }}" class="img-thumbnail" style="max-width: 200px;">
                            </dd>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- Предпросмотр аккаунтов --}}
            @if($totalAccounts > 0)
                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">Предпросмотр аккаунтов ({{ min(10, $totalAccounts) }} из {{ $totalAccounts }})</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Аккаунт</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($previewAccounts as $index => $account)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td><code>{{ $account }}</code></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if($totalAccounts > 10)
                            <p class="text-muted mb-0">
                                <small>Показаны первые 10 аккаунтов из {{ $totalAccounts }}. Остальные будут доступны после одобрения товара.</small>
                            </p>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        {{-- Действия --}}
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Действия</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.product-moderation.approve', $product) }}" method="POST" class="mb-3">
                        @csrf
                        <button type="submit" class="btn btn-success btn-block" onclick="return confirm('Одобрить этот товар?');">
                            <i class="fas fa-check mr-2"></i>Одобрить товар
                        </button>
                    </form>

                    <button type="button" class="btn btn-danger btn-block" data-toggle="modal" data-target="#rejectModal">
                        <i class="fas fa-times mr-2"></i>Отклонить товар
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal для отклонения -->
    <div class="modal fade" id="rejectModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('admin.product-moderation.reject', $product) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Отклонить товар</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p>Вы уверены, что хотите отклонить товар <strong>"{{ $product->title }}"</strong>?</p>
                        <div class="form-group">
                            <label for="moderation_comment">Причина отклонения <span class="text-danger">*</span></label>
                            <textarea name="moderation_comment" id="moderation_comment" class="form-control" rows="4" required maxlength="1000" placeholder="Укажите причину отклонения товара..."></textarea>
                            <small class="form-text text-muted">Этот комментарий будет отправлен поставщику.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-danger">Отклонить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop
