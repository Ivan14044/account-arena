@extends('adminlte::page')

@section('title', 'Редактировать пользователя #' . $user->id)

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="m-0 font-weight-light">
                    Пользователь #{{ $user->id }}
                </h1>
                <p class="text-muted mb-0 mt-1">
                    <i class="fas fa-user mr-1"></i>{{ $user->name }}
                    <span class="mx-2">•</span>
                    <i class="fas fa-envelope mr-1"></i>{{ $user->email }}
                </p>
            </div>
            <div>
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-modern">
                    <i class="fas fa-arrow-left mr-2"></i>Назад к списку
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

    @if(session('error'))
        <div class="alert alert-modern alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle mr-2"></i>{{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <!-- Карточка баланса -->
    <div class="balance-card-modern mb-4">
        <div class="balance-card-content">
            <div class="balance-info">
                <div class="balance-label">
                    <i class="fas fa-wallet mr-2"></i>Баланс аккаунта
                </div>
                <div class="balance-amount">
                    ${{ number_format($user->balance ?? 0, 2) }}
                    <span class="balance-currency">USD</span>
                </div>
            </div>
            <div class="balance-action">
                <button type="button" class="btn btn-primary btn-modern" data-toggle="modal" data-target="#manageBalanceModal">
                    <i class="fas fa-edit mr-2"></i>Управление балансом
                </button>
            </div>
        </div>
    </div>

    <!-- Табы навигации -->
    <div class="card card-modern">
        <div class="card-header-modern p-0">
            <ul class="nav nav-tabs-modern" id="userTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="general-tab" data-toggle="tab" href="#general" role="tab">
                        <i class="fas fa-user mr-2"></i>Основная информация
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="purchases-tab" data-toggle="tab" href="#purchases" role="tab">
                        <i class="fas fa-shopping-cart mr-2"></i>История покупок
                        <span class="badge badge-light ml-1">{{ $purchases->count() }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="settings-tab" data-toggle="tab" href="#settings" role="tab">
                        <i class="fas fa-cog mr-2"></i>Настройки
                    </a>
                </li>
            </ul>
        </div>

        <div class="card-body-modern p-4">
            <div class="tab-content" id="userTabsContent">
                <!-- ТАБ 1: Основная информация -->
                <div class="tab-pane fade show active" id="general" role="tabpanel">
                    <form method="POST" action="{{ route('admin.users.update', $user) }}">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="section-title mb-4">Личные данные</h5>

                                <div class="form-group-modern">
                                    <label for="name" class="form-label-modern">Имя пользователя</label>
                                    <input type="text" name="name" id="name" class="form-control form-control-modern @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}">
                                    @error('name')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group-modern">
                                    <label for="email" class="form-label-modern">Email адрес</label>
                                    <input type="email" name="email" id="email" class="form-control form-control-modern @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}">
                                    @error('email')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group-modern">
                                    <label for="is_blocked" class="form-label-modern">Статус аккаунта</label>
                                    <select name="is_blocked" id="is_blocked" class="form-control form-control-modern @error('is_blocked') is-invalid @enderror">
                                        <option value="0" {{ old('is_blocked', $user->getStatus()) == 'active' ? 'selected' : '' }}>✅ Активен</option>
                                        <option value="1" {{ old('is_blocked', $user->getStatus()) == 'blocked' ? 'selected' : '' }}>🚫 Заблокирован</option>
                                        <option value="2" {{ old('is_blocked', $user->getStatus()) == 'pending' ? 'selected' : '' }}>⏳ Ожидает (Pending)</option>
                                    </select>
                                    @error('is_blocked')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <h5 class="section-title mb-4">Безопасность</h5>

                                <div class="form-group-modern">
                                    <label for="password" class="form-label-modern">Новый пароль</label>
                                    <input type="password" name="password" id="password" class="form-control form-control-modern @error('password') is-invalid @enderror" placeholder="Оставьте пустым, чтобы не менять">
                                    @error('password')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Минимум 6 символов</small>
                                </div>

                                <div class="form-group-modern">
                                    <label for="password_confirmation" class="form-label-modern">Подтверждение пароля</label>
                                    <input type="password" name="password_confirmation" id="password_confirmation" class="form-control form-control-modern" placeholder="Повторите пароль">
                                </div>
                            </div>
                        </div>

                        <hr class="section-divider">

                        <div class="row">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary btn-modern">
                                    <i class="fas fa-save mr-2"></i>Сохранить изменения
                                </button>
                                <button type="submit" name="save" class="btn btn-outline-primary btn-modern">
                                    <i class="fas fa-save mr-2"></i>Сохранить и продолжить
                                </button>
                                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-modern">
                                    <i class="fas fa-times mr-2"></i>Отмена
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- ТАБ 2: История покупок товаров -->
                <div class="tab-pane fade" id="purchases" role="tabpanel">
                    <h5 class="section-title mb-4">
                        <i class="fas fa-shopping-bag mr-2"></i>История покупок товаров
                    </h5>

                    @if($purchases->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover modern-table-clean">
                                <thead>
                                    <tr>
                                        <th style="width: 80px">ID</th>
                                        <th>Номер заказа</th>
                                        <th>Товар</th>
                                        <th>Количество</th>
                                        <th>Сумма</th>
                                        <th>Статус</th>
                                        <th>Дата покупки</th>
                                        <th style="width: 120px" class="text-center">Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($purchases as $purchase)
                                    <tr>
                                        <td class="font-weight-bold">#{{ $purchase->id }}</td>
                                        <td>
                                            <code class="text-primary">{{ $purchase->order_number ?? 'N/A' }}</code>
                                        </td>
                                        <td>
                                            @if($purchase->serviceAccount)
                                                <div class="d-flex align-items-center">
                                                    @if($purchase->serviceAccount->image_url)
                                                        <img src="{{ $purchase->serviceAccount->image_url }}"
                                                             alt="{{ $purchase->serviceAccount->title }}"
                                                             class="mr-2"
                                                             style="width: 32px; height: 32px; object-fit: contain; border-radius: 4px;">
                                                    @endif
                                                    <span>{{ $purchase->serviceAccount->title }}</span>
                                                </div>
                                            @else
                                                <span class="text-muted">Товар удален</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-info badge-modern">{{ $purchase->quantity }} шт.</span>
                                        </td>
                                        <td>
                                            <div class="font-weight-bold text-success">${{ number_format($purchase->total_amount, 2) }}</div>
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
                                            <span class="badge badge-{{ $color }} badge-modern">{{ $label }}</span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <i class="far fa-calendar mr-1"></i>{{ $purchase->created_at->format('d.m.Y H:i') }}
                                                <br>
                                                <span class="text-muted">{{ $purchase->created_at->diffForHumans() }}</span>
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('admin.purchases.show', $purchase) }}"
                                               class="btn btn-sm btn-info btn-modern"
                                               title="Подробнее">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Итоговая статистика -->
                        <div class="card bg-light border-0 mt-3">
                            <div class="card-body py-3">
                                <div class="row text-center">
                                    <div class="col-md-4">
                                        <div class="text-muted small">Всего покупок</div>
                                        <div class="h5 mb-0 font-weight-bold">{{ $purchases->count() }}</div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="text-muted small">Общая сумма</div>
                                        <div class="h5 mb-0 font-weight-bold text-success">
                                            ${{ number_format($purchases->where('status', 'completed')->sum('total_amount'), 2) }}
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="text-muted small">Товаров куплено</div>
                                        <div class="h5 mb-0 font-weight-bold text-info">
                                            {{ $purchases->sum('quantity') }} шт.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="empty-state">
                            <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                            <p class="text-muted mb-0">У пользователя пока нет покупок товаров</p>
                        </div>
                    @endif
                </div>

                <!-- ТАБ 3: Настройки -->
                <div class="tab-pane fade" id="settings" role="tabpanel">
                    <form method="POST" action="{{ route('admin.users.update', $user) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="name" value="{{ $user->name }}">
                        <input type="hidden" name="email" value="{{ $user->email }}">
                        @php
                            $statusMap = [
                                'active' => 0,
                                'blocked' => 1,
                                'pending' => 2
                            ];
                            $statusValue = $statusMap[$user->getStatus()] ?? 0;
                        @endphp
                        <input type="hidden" name="is_blocked" value="{{ $statusValue }}">


                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="section-title mb-4">Роль поставщика</h5>

                                <div class="form-group-modern">
                                    <div class="custom-control custom-switch custom-switch-modern">
                                        <input type="checkbox" class="custom-control-input" id="is_supplier" name="is_supplier" value="1" {{ old('is_supplier', $user->is_supplier) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="is_supplier">
                                            <span class="font-weight-500">Поставщик товаров</span>
                                            <br>
                                            <small class="text-muted">Предоставляет доступ к панели поставщика для добавления товаров</small>
                                        </label>
                                    </div>
                                </div>

                                <div id="supplier-fields" style="display: {{ old('is_supplier', $user->is_supplier) ? 'block' : 'none' }};">
                                    <div class="form-group-modern">
                                        <label for="supplier_balance" class="form-label-modern">Баланс поставщика (USD)</label>
                                        <input type="number" step="0.01" name="supplier_balance" id="supplier_balance"
                                               class="form-control form-control-modern @error('supplier_balance') is-invalid @enderror"
                                               value="{{ old('supplier_balance', $user->supplier_balance ?? 0) }}">
                                        @error('supplier_balance')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted">Заработок от продаж товаров</small>
                                    </div>

                                    <div class="form-group-modern">
                                        <label for="supplier_commission" class="form-label-modern">Комиссия платформы (%)</label>
                                        <input type="number" step="0.01" min="0" max="100" name="supplier_commission" id="supplier_commission"
                                               class="form-control form-control-modern @error('supplier_commission') is-invalid @enderror"
                                               value="{{ old('supplier_commission', $user->supplier_commission ?? 10) }}">
                                        @error('supplier_commission')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted">Процент комиссии с каждой продажи</small>
                                    </div>

                                    <div class="form-group-modern">
    <label for="supplier_hold_hours" class="form-label-modern">Время холда (в часах)</label>
    <input type="number" name="supplier_hold_hours" id="supplier_hold_hours" min="0" step="1"
           class="form-control form-control-modern @error('supplier_hold_hours') is-invalid @enderror"
           value="{{ old('supplier_hold_hours', $user->supplier_hold_hours ?? 6) }}">
    @error('supplier_hold_hours')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
    <small class="text-muted">Сколько часов после покупки средства будут в холде до возможности вывода (по умолчанию 6 часов)</small>
</div>

                                </div>
                            </div>

                            <div class="col-md-6">
                                <h5 class="section-title mb-4">
                                    Персональная скидка
                                    @if($user->hasActivePersonalDiscount())
                                        <span class="badge badge-success float-right" style="font-size: 0.7rem; margin-top: 4px;">Активна</span>
                                    @else
                                        <span class="badge badge-secondary float-right" style="font-size: 0.7rem; margin-top: 4px;">Неактивна</span>
                                    @endif
                                </h5>

                                <div class="form-group-modern">
                                    <label for="personal_discount" class="form-label-modern">Размер скидки (%)</label>
                                    <input type="number" min="0" max="100" name="personal_discount" id="personal_discount"
                                           class="form-control form-control-modern @error('personal_discount') is-invalid @enderror"
                                           value="{{ old('personal_discount', $user->personal_discount ?? 0) }}">
                                    @error('personal_discount')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Применяется автоматически ко всем покупкам</small>
                                </div>

                                <div class="form-group-modern">
                                    <label for="personal_discount_expires_at" class="form-label-modern">Срок действия скидки</label>
                                    <input type="datetime-local" name="personal_discount_expires_at" id="personal_discount_expires_at"
                                           class="form-control form-control-modern @error('personal_discount_expires_at') is-invalid @enderror"
                                           value="{{ old('personal_discount_expires_at', optional($user->personal_discount_expires_at ?? null)?->format('Y-m-d\TH:i')) }}">
                                    @error('personal_discount_expires_at')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Оставьте пустым для неограниченного срока</small>
                                </div>
                            </div>
                        </div>

                        <hr class="section-divider">

                        <div class="row">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary btn-modern">
                                    <i class="fas fa-save mr-2"></i>Сохранить настройки
                                </button>
                                <button type="submit" name="save" class="btn btn-outline-primary btn-modern">
                                    <i class="fas fa-save mr-2"></i>Сохранить и продолжить
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно управления балансом -->
    <div class="modal fade" id="manageBalanceModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <form action="{{ route('admin.users.update-balance', $user) }}" method="POST" class="modal-content modal-modern" id="balanceForm">
                @csrf

                <div class="modal-header-modern">
                    <h5 class="modal-title">
                        <i class="fas fa-wallet mr-2"></i>Управление балансом аккаунта
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <!-- Текущий баланс -->
                    <div class="current-balance-display mb-4">
                        <div class="text-center p-4 bg-light rounded">
                            <small class="text-muted text-uppercase d-block mb-2">Текущий баланс</small>
                            <h2 class="mb-0 font-weight-bold">${{ number_format($user->balance ?? 0, 2) }} <span class="text-muted h5">USD</span></h2>
                        </div>
                    </div>

                    <!-- Выбор операции -->
                    <div class="form-group-modern">
                        <label class="form-label-modern">Выберите операцию</label>
                        <div class="operation-buttons">
                            <label class="operation-btn operation-btn-add active">
                                <input type="radio" name="operation" value="add" checked>
                                <div class="operation-btn-content">
                                    <i class="fas fa-plus-circle"></i>
                                    <span>Пополнить</span>
                                </div>
                            </label>
                            <label class="operation-btn operation-btn-subtract">
                                <input type="radio" name="operation" value="subtract">
                                <div class="operation-btn-content">
                                    <i class="fas fa-minus-circle"></i>
                                    <span>Списать</span>
                                </div>
                            </label>
                            <label class="operation-btn operation-btn-set">
                                <input type="radio" name="operation" value="set">
                                <div class="operation-btn-content">
                                    <i class="fas fa-equals"></i>
                                    <span>Установить</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Сумма -->
                    <div class="form-group-modern">
                        <label for="balance_amount" class="form-label-modern">
                            <span id="amountLabel">Сумма пополнения (USD)</span>
                        </label>
                        <div class="input-group input-group-lg">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-light border-right-0">
                                    <i class="fas fa-dollar-sign text-muted"></i>
                                </span>
                            </div>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                name="amount"
                                id="balance_amount"
                                class="form-control form-control-modern border-left-0"
                                placeholder="0.00"
                                required
                            >
                        </div>

                        <!-- Предпросмотр -->
                        <div class="balance-preview mt-3" id="balancePreview" style="display: none;">
                            <div class="text-center p-3 bg-light rounded border">
                                <small class="text-muted d-block mb-1">Новый баланс</small>
                                <h4 class="mb-0" id="newBalancePreview">$0.00 USD</h4>
                            </div>
                        </div>
                    </div>

                    <!-- Комментарий -->
                    <div class="form-group-modern">
                        <label for="balance_comment" class="form-label-modern">Комментарий</label>
                        <textarea
                            name="comment"
                            id="balance_comment"
                            class="form-control form-control-modern"
                            rows="3"
                            placeholder="Укажите причину изменения баланса..."
                            maxlength="500"
                        ></textarea>
                        <small class="text-muted">Комментарий будет сохранен в логах системы</small>
                    </div>

                    <!-- Предупреждение -->
                    <div class="alert alert-modern mb-0" id="operationWarning">
                        <i class="fas fa-info-circle mr-2"></i>
                        <span id="warningText">Будет создана транзакция в истории пользователя</span>
                    </div>
                </div>

                <div class="modal-footer-modern">
                    <button type="button" class="btn btn-outline-secondary btn-modern" data-dismiss="modal">
                        Отмена
                    </button>
                    <button type="submit" class="btn btn-primary btn-modern" id="submitBalanceBtn">
                        <i class="fas fa-plus-circle mr-2"></i>Пополнить баланс
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('css')
    <style>
        /* ============================================
           MODERN & STRICT DESIGN SYSTEM
           ============================================ */

        /* ЗАГОЛОВОК СТРАНИЦЫ */
        .content-header-modern h1 {
            font-size: 1.75rem;
            color: #2c3e50;
            letter-spacing: -0.5px;
        }

        /* КНОПКИ */
        .btn-modern {
            padding: 0.5rem 1.25rem;
            font-weight: 500;
            letter-spacing: 0.3px;
            border-radius: 0.375rem;
            border: none;
            transition: all 0.2s ease;
        }

        .btn-modern:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        /* КАРТОЧКА БАЛАНСА */
        .balance-card-modern {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.25);
        }

        .balance-card-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 2rem 2.5rem;
        }

        .balance-label {
            color: rgba(255,255,255,0.9);
            font-size: 0.875rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .balance-amount {
            color: white;
            font-size: 2.5rem;
            font-weight: 700;
            line-height: 1;
        }

        .balance-currency {
            font-size: 1.25rem;
            font-weight: 500;
            opacity: 0.9;
        }

        /* ТАБЫ */
        .nav-tabs-modern {
            border-bottom: 2px solid #e3e6f0;
            padding: 0 1.5rem;
        }

        .nav-tabs-modern .nav-item {
            margin-bottom: -2px;
        }

        .nav-tabs-modern .nav-link {
            border: none;
            color: #5a6c7d;
            padding: 1rem 1.5rem;
            font-weight: 500;
            transition: all 0.2s ease;
            border-bottom: 2px solid transparent;
        }

        .nav-tabs-modern .nav-link:hover {
            color: #4e73df;
            border-bottom-color: #e3e6f0;
        }

        .nav-tabs-modern .nav-link.active {
            color: #4e73df;
            background: transparent;
            border-bottom-color: #4e73df;
        }

        /* КАРТОЧКА */
        .card-modern {
            border: 1px solid #e3e6f0;
            border-radius: 0.75rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .card-header-modern {
            background: white;
            border-bottom: none;
        }

        .card-body-modern {
            background: white;
        }

        /* ФОРМЫ */
        .form-group-modern {
            margin-bottom: 1.5rem;
        }

        .form-label-modern {
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
            display: block;
        }

        .form-control-modern {
            border: 1px solid #d1d3e2;
            border-radius: 0.375rem;
            padding: 0.65rem 1rem;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }

        .form-control-modern:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.1);
        }

        /* РАЗДЕЛИТЕЛИ СЕКЦИЙ */
        .section-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #e3e6f0;
        }

        .section-divider {
            border-top: 2px solid #e3e6f0;
            margin: 2rem 0;
        }

        /* ОПЕРАЦИОННЫЕ КНОПКИ */
        .operation-buttons {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }

        .operation-btn {
            position: relative;
            cursor: pointer;
            margin: 0;
        }

        .operation-btn input {
            position: absolute;
            opacity: 0;
        }

        .operation-btn-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1.25rem;
            border: 2px solid #e3e6f0;
            border-radius: 0.5rem;
            background: white;
            transition: all 0.2s ease;
        }

        .operation-btn-content i {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .operation-btn-content span {
            font-weight: 500;
            font-size: 0.875rem;
        }

        .operation-btn:hover .operation-btn-content {
            border-color: #4e73df;
            background: #f8f9fc;
        }

        .operation-btn.active .operation-btn-content {
            border-color: #4e73df;
            background: #4e73df;
            color: white;
        }

        .operation-btn-add.active .operation-btn-content {
            background: #1cc88a;
            border-color: #1cc88a;
        }

        .operation-btn-subtract.active .operation-btn-content {
            background: #e74a3b;
            border-color: #e74a3b;
        }

        .operation-btn-set.active .operation-btn-content {
            background: #f6c23e;
            border-color: #f6c23e;
        }

        /* МОДАЛЬНОЕ ОКНО */
        .modal-modern {
            border: none;
            border-radius: 0.75rem;
            overflow: hidden;
        }

        .modal-header-modern {
            background: #f8f9fc;
            border-bottom: 2px solid #e3e6f0;
            padding: 1.5rem 2rem;
        }

        .modal-header-modern .modal-title {
            font-weight: 600;
            color: #2c3e50;
        }

        .modal-footer-modern {
            background: #f8f9fc;
            border-top: 2px solid #e3e6f0;
            padding: 1.25rem 2rem;
        }

        /* ТАБЛИЦА */
        .modern-table-clean {
            font-size: 0.875rem;
        }

        .modern-table-clean thead th {
            background: #f8f9fc;
            border: none;
            border-bottom: 2px solid #e3e6f0;
            color: #5a6c7d;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            padding: 1rem;
        }

        .modern-table-clean tbody td {
            padding: 1rem;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
        }

        .modern-table-clean tbody tr:hover {
            background: #f8f9fc;
        }

        /* БЕЙДЖИ */
        .badge-modern {
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 0.25rem;
        }

        /* EMPTY STATE */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }

        /* ALERTS */
        .alert-modern {
            border: none;
            border-left: 4px solid;
            border-radius: 0.5rem;
            padding: 1rem 1.25rem;
        }

        .alert-success {
            background: #d1fae5;
            border-left-color: #10b981;
            color: #065f46;
        }

        .alert-danger {
            background: #fee2e2;
            border-left-color: #ef4444;
            color: #991b1b;
        }

        .alert-warning {
            background: #fef3c7;
            border-left-color: #f59e0b;
            color: #78350f;
        }

        /* ТИПОГРАФИКА */
        .font-weight-500 {
            font-weight: 500;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        /* CUSTOM SWITCH MODERN */
        .custom-switch-modern .custom-control-label {
            padding-left: 2.5rem;
        }

        .custom-switch-modern .custom-control-label::before {
            width: 3rem;
            height: 1.5rem;
            border-radius: 1rem;
        }

        .custom-switch-modern .custom-control-label::after {
            width: calc(1.5rem - 4px);
            height: calc(1.5rem - 4px);
            border-radius: 50%;
        }
    </style>
@endsection

@section('js')
    <script>
        $(document).ready(function() {
            // Toggle supplier fields
            const isSupplierCheckbox = $('#is_supplier');
            const supplierFields = $('#supplier-fields');

            isSupplierCheckbox.on('change', function() {
                supplierFields.slideToggle(200);
            });

            // Balance management
            const currentBalance = {{ $user->balance ?? 0 }};
            let selectedOperation = 'add';

            // Auto-focus on amount field
            $('#manageBalanceModal').on('shown.bs.modal', function () {
                $('#balance_amount').focus().select();
                updateBalancePreview();
            });

            // Operation change
            $('input[name="operation"]').on('change', function() {
                selectedOperation = $(this).val();

                // Update active class
                $('.operation-btn').removeClass('active');
                $(this).closest('.operation-btn').addClass('active');

                updateBalancePreview();
                updateUIForOperation();
            });

            // Amount input
            $('#balance_amount').on('input', updateBalancePreview);

            // Update balance preview
            function updateBalancePreview() {
                const amount = parseFloat($('#balance_amount').val()) || 0;
                let newBalance = 0;

                switch(selectedOperation) {
                    case 'add':
                        newBalance = currentBalance + amount;
                        break;
                    case 'subtract':
                        newBalance = currentBalance - amount;
                        break;
                    case 'set':
                        newBalance = amount;
                        break;
                }

                if (amount > 0) {
                    $('#balancePreview').fadeIn(200);
                    $('#newBalancePreview').text('$' + newBalance.toFixed(2) + ' USD');

                    // Color indication
                    $('#newBalancePreview').removeClass('text-danger text-success text-warning text-primary');
                    if (newBalance < 0) {
                        $('#newBalancePreview').addClass('text-danger');
                    } else if (newBalance > currentBalance) {
                        $('#newBalancePreview').addClass('text-success');
                    } else if (newBalance < currentBalance) {
                        $('#newBalancePreview').addClass('text-warning');
                    } else {
                        $('#newBalancePreview').addClass('text-primary');
                    }
                } else {
                    $('#balancePreview').fadeOut(200);
                }
            }

            // Update UI for operation
            function updateUIForOperation() {
                const warningAlert = $('#operationWarning');
                const amountLabel = $('#amountLabel');
                const submitBtn = $('#submitBalanceBtn');

                switch(selectedOperation) {
                    case 'add':
                        amountLabel.text('Сумма пополнения (USD)');
                        warningAlert.removeClass('alert-danger alert-warning').addClass('alert-success');
                        $('#warningText').text('Баланс будет увеличен. Транзакция будет создана.');
                        submitBtn.removeClass('btn-danger btn-warning').addClass('btn-primary');
                        submitBtn.html('<i class="fas fa-plus-circle mr-2"></i>Пополнить баланс');
                        break;
                    case 'subtract':
                        amountLabel.text('Сумма списания (USD)');
                        warningAlert.removeClass('alert-success alert-warning').addClass('alert-danger');
                        $('#warningText').text('Баланс будет уменьшен. Убедитесь, что достаточно средств!');
                        submitBtn.removeClass('btn-primary btn-warning').addClass('btn-danger');
                        submitBtn.html('<i class="fas fa-minus-circle mr-2"></i>Списать средства');
                        break;
                    case 'set':
                        amountLabel.text('Новый баланс (USD)');
                        warningAlert.removeClass('alert-success alert-danger').addClass('alert-warning');
                        $('#warningText').text('Баланс будет установлен в указанную сумму.');
                        submitBtn.removeClass('btn-primary btn-danger').addClass('btn-warning');
                        submitBtn.html('<i class="fas fa-equals mr-2"></i>Установить баланс');
                        break;
                }
            }

            // Initialize
            updateUIForOperation();
        });
    </script>
@endsection
