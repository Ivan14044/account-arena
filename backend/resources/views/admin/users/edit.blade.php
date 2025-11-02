@extends('adminlte::page')

@section('title', 'Редактировать пользователя #' . $user->id)

@section('content_header')
    <h1>Редактировать пользователя #{{ $user->id }}</h1>
@stop

@section('content')
    <div class="row">
        @if(session('success'))
            <div class="col-12">
                <div class="alert alert-success">{{ session('success') }}</div>
            </div>
        @endif

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Данные пользователя</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.users.update', $user) }}">
                        @csrf
                        @method('PUT')
                        <div class="form-group">
                            <label for="name">Имя</label>
                            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}">
                            @error('name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}">
                            @error('email')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="is_blocked">Статус</label>
                            <select name="is_blocked" id="is_blocked" class="form-control @error('is_blocked') is-invalid @enderror">
                                <option value="0" {{ old('is_blocked', $user->is_blocked) == 0 ? 'selected' : '' }}>Активен</option>
                                <option value="1" {{ old('is_blocked', $user->is_blocked) == 1 ? 'selected' : '' }}>Заблокирован</option>
                                <option value="2" {{ old('is_blocked', $user->is_pending && !$user->is_blocked ? 2 : 0) == 2 ? 'selected' : '' }}>Ожидает</option>
                            </select>
                            @error('is_blocked')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="is_supplier" name="is_supplier" value="1" {{ old('is_supplier', $user->is_supplier) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="is_supplier">Поставщик товаров</label>
                            </div>
                            <small class="form-text text-muted">Дает доступ к кабинету поставщика для добавления товаров</small>
                        </div>

                        <div id="supplier-fields" style="display: {{ old('is_supplier', $user->is_supplier) ? 'block' : 'none' }};">
                            <div class="form-group">
                                <label for="supplier_balance">Баланс поставщика (USD)</label>
                                <input type="number" step="0.01" name="supplier_balance" id="supplier_balance" 
                                       class="form-control @error('supplier_balance') is-invalid @enderror" 
                                       value="{{ old('supplier_balance', $user->supplier_balance ?? 0) }}">
                                @error('supplier_balance')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="supplier_commission">Комиссия платформы (%)</label>
                                <input type="number" step="0.01" min="0" max="100" name="supplier_commission" id="supplier_commission" 
                                       class="form-control @error('supplier_commission') is-invalid @enderror" 
                                       value="{{ old('supplier_commission', $user->supplier_commission ?? 10) }}">
                                @error('supplier_commission')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Процент комиссии, который будет удерживаться с продаж</small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password">Новый пароль</label>
                            <small>Оставьте пустым, чтобы сохранить текущий</small>
                            <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror">
                            @error('password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="password_confirmation">Подтверждение пароля</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control">
                        </div>

                        <hr>

                        <h5>Персональная скидка</h5>

                        <div class="form-group">
                            <label for="personal_discount">Процент скидки</label>
                            <input type="number" min="0" max="100" name="personal_discount" id="personal_discount" class="form-control @error('personal_discount') is-invalid @enderror" value="{{ old('personal_discount', $user->personal_discount ?? 0) }}">
                            @error('personal_discount')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Скидка будет применяться автоматически ко всем покупкам этого пользователя</small>
                        </div>

                        <div class="form-group">
                            <label for="personal_discount_expires_at">Срок действия</label>
                            <input type="datetime-local" name="personal_discount_expires_at" id="personal_discount_expires_at" class="form-control @error('personal_discount_expires_at') is-invalid @enderror" value="{{ old('personal_discount_expires_at', optional($user->personal_discount_expires_at ?? null)?->format('Y-m-d\TH:i')) }}">
                            @error('personal_discount_expires_at')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Оставьте пустым для неограниченного срока действия</small>
                        </div>

                        <button type="submit" class="btn btn-primary">Сохранить</button>
                        <button type="submit" name="save" class="btn btn-primary">Сохранить и продолжить</button>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Отмена</a>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Список покупок</h3>
                </div>
                <div class="card-body">
                    <table id="subscriptions-table" class="table table-bordered table-striped">
                        <thead>
                        <tr>
                            <th style="width: 30px">ID</th>
                            <th>Service</th>
                            <th>Status</th>
                            <th>Amount</th>
                            <th>Payment Info</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($subscriptions as $subscription)
                        <tr>
                            <td>{{ $subscription->id }}</td>
                            <td>
                                <div class="d-flex" style="gap: 5px">
                                    <img src="{{ url($subscription->service->logo) }}"
                                         title="{{ $subscription->service->code }}"
                                         class="img-fluid img-bordered" style="width: 35px;">
                                </div>
                            </td>
                            <td>
                                @if($subscription->status != \App\Models\Subscription::STATUS_ACTIVE)
                                    <span class="badge badge-danger">Canceled</span>
                                @else
                                    <span class="badge badge-success">Active</span>
                                @endif
                                @if($subscription->is_trial)
                                    <br>
                                    <span class="badge badge-primary">Trial</span>
                                @endif
                            </td>
                            @php
                                $last = $subscription->transactions->sortByDesc('created_at')->first();
                            @endphp
                            <td>
                                {{ $last?->amount ?? '-' }} {{ strtoupper($last?->currency ?? '') }}
                                <br>
                                <small>{{ $subscription->payment_method_label }}</small>
                            </td>
                            <td data-order="{{ strtotime($subscription->next_payment_at) }}">
                                <i class="fas fa-calendar-plus text-secondary mr-1" title="Next payment at"></i> {{ \Carbon\Carbon::parse($subscription->next_payment_at)->format('Y-m-d H:i') }} <br>
                                <i class="fas fa-receipt text-secondary mr-1" title="Last payment at"></i> {{ $last?->created_at?->format('Y-m-d H:i') ?? '-' }}
                            </td>
                            <td class="d-flex flex-wrap align-items-center" style="gap: 5px; max-width: 110px; overflow: hidden;">
                                <a href="{{ route('admin.subscriptions.edit', $subscription) . (!empty($user) ? '?back_url=' . url()->current() : '') }}" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>

                                <a href="{{ route('admin.subscriptions.transactions', $subscription) . (!empty($user) ? '?back_url=' . url()->current() : '') }}"
                                   class="btn btn-sm btn-{{ $subscription->transactions()->count() ? 'success' : 'secondary' }}">
                                    <i class="fas fa-file-invoice-dollar"></i>
                                </a>

                                <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#nextPaymentModal{{ $subscription->id }}">
                                    <i class="far fa-clock"></i>
                                </button>

                                @if ($subscription->status == \App\Models\Subscription::STATUS_ACTIVE)
                                    <button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#toggleStatusModal{{ $subscription->id }}" title="Cancel Subscription">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                @elseif ($subscription->status == \App\Models\Subscription::STATUS_CANCELED)
                                    <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#toggleStatusModal{{ $subscription->id }}" title="Activate Subscription">
                                        <i class="fas fa-play"></i>
                                    </button>
                                @endif

                                <button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal{{ $subscription->id }}">
                                    <i class="fas fa-trash"></i>
                                </button>

                                <div class="modal fade" id="toggleStatusModal{{ $subscription->id }}" tabindex="-1" role="dialog" aria-labelledby="toggleStatusModalLabel" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <form action="{{ route('admin.subscriptions.toggle-status', $subscription) }}" method="POST" class="modal-content">
                                            @csrf
                                            @method('PUT')

                                            <div class="modal-header">
                                                <h5 class="modal-title" id="toggleStatusModalLabel">
                                                    {{ $subscription->status === 'active' ? 'Cancel Subscription' : 'Activate Subscription' }}
                                                </h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>

                                            <div class="modal-body">
                                                Are you sure you want to
                                                {{ $subscription->status === 'active' ? 'cancel' : 'activate' }}
                                                this subscription?
                                            </div>

                                            <div class="modal-footer">
                                                <button type="submit" class="btn btn-{{ $subscription->status === 'active' ? 'danger' : 'success' }}">
                                                    Yes, {{ $subscription->status === 'active' ? 'Cancel' : 'Activate' }}
                                                </button>
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <div class="modal fade" id="nextPaymentModal{{ $subscription->id }}" tabindex="-1" role="dialog" aria-labelledby="nextPaymentModalLabel" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <form action="{{ route('admin.subscriptions.update-next-payment', $subscription) }}" method="POST" class="modal-content">
                                            @csrf
                                            @method('PUT')

                                            <div class="modal-header">
                                                <h5 class="modal-title" id="nextPaymentModalLabel">Set Next Payment Date</h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>

                                            <div class="modal-body">
                                                <div class="form-group">
                                                    <label for="next_payment_at_{{ $subscription->id }}">Next Payment At</label>
                                                    <input type="datetime-local" name="next_payment_at" id="next_payment_at_{{ $subscription->id }}"
                                                           class="form-control"
                                                           value="{{ \Carbon\Carbon::parse($subscription->next_payment_at)->format('Y-m-d\TH:i') }}">
                                                </div>
                                            </div>

                                            <div class="modal-footer">
                                                <button type="submit" class="btn btn-primary">Save</button>
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <div class="modal fade" id="deleteModal{{ $subscription->id }}" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                Are you sure you want to delete this subscription?
                                            </div>
                                            <div class="modal-footer">
                                                <form action="{{ route('admin.subscriptions.destroy', $subscription) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger">Yes, Delete</button>

                                                    @if(!empty($user))
                                                    <input type="hidden" name="return_url" value="{{ url()->current() }}">
                                                    @endif
                                                </form>
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const isSupplierCheckbox = document.getElementById('is_supplier');
            const supplierFields = document.getElementById('supplier-fields');
            
            if (isSupplierCheckbox && supplierFields) {
                isSupplierCheckbox.addEventListener('change', function() {
                    supplierFields.style.display = this.checked ? 'block' : 'none';
                });
            }
        });
    </script>
@endsection
