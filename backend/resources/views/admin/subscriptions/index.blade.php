@extends('adminlte::page')

@section('title', 'Покупки' . (!empty($user) ? ' пользователя ' . $user->email : ''))

@section('content_header')
    <h1>Покупки{{ !empty($user) ? ' пользователя ' . $user->email : '' }}</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-12">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Список покупок</h3>
                </div>
                <div class="card-body">
                    <table id="subscriptions-table" class="table table-bordered table-striped">
                        <thead>
                        <tr>
                            <th style="width: 30px">ID</th>
                            @if(empty($user))
                                <th>Пользователь</th>
                            @endif
                            <th>Сервис</th>
                            <th>Статус</th>
                            <th>Сумма</th>
                            <th>Информация о платеже</th>
                            <th>Дата создания</th>
                            <th>Действия</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($subscriptions as $subscription)
                            <tr>
                                <td>{{ $subscription->id }}</td>
                                @if(empty($user))
                                    <td>{{ $subscription->user->email }}</td>
                                @endif
                                <td>
                                    <div class="d-flex" style="gap: 5px">
                                        <img src="{{ url($subscription->service->logo) }}"
                                             title="{{ $subscription->service->code }}"
                                            class="img-fluid img-bordered" style="width: 35px;">
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $status = $subscription->status;
                                    @endphp

                                    @if ($status === \App\Models\Subscription::STATUS_ACTIVE)
                                        <span class="badge badge-success">Активна</span>
                                    @elseif ($status === \App\Models\Subscription::STATUS_CANCELED)
                                        <span class="badge badge-danger">Отменена</span>
                                    @elseif ($status === \App\Models\Subscription::STATUS_ENDED)
                                        <span class="badge badge-secondary">Завершена</span>
                                    @else
                                        <span class="badge badge-light">{{ ucfirst($status) }}</span>
                                    @endif
                                    @if($subscription->is_trial)
                                        <br>
                                        <span class="badge badge-primary">Пробная</span>
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
                                <td data-order="{{ strtotime($subscription->created_at) }}">
                                    {{ \Carbon\Carbon::parse($subscription->created_at)->format('Y-m-d H:i') }}
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
                                        <button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#toggleStatusModal{{ $subscription->id }}" title="Отменить подписку">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                    @elseif ($subscription->status == \App\Models\Subscription::STATUS_CANCELED)
                                        <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#toggleStatusModal{{ $subscription->id }}" title="Активировать подписку">
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
                                                        {{ $subscription->status === 'active' ? 'Отменить подписку' : 'Активировать подписку' }}
                                                    </h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>

                                                <div class="modal-body">
                                                    Вы уверены, что хотите
                                                    {{ $subscription->status === 'active' ? 'отменить' : 'активировать' }}
                                                    эту подписку?
                                                </div>

                                                <div class="modal-footer">
                                                    <button type="submit" class="btn btn-{{ $subscription->status === 'active' ? 'danger' : 'success' }}">
                                                        Да, {{ $subscription->status === 'active' ? 'отменить' : 'активировать' }}
                                                    </button>
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
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
                                                    <h5 class="modal-title" id="nextPaymentModalLabel">Установить дату следующего платежа</h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>

                                                <div class="modal-body">
                                                    <div class="form-group">
                                                        <label for="next_payment_at_{{ $subscription->id }}">Дата следующего платежа</label>
                                                        <input type="datetime-local" name="next_payment_at" id="next_payment_at_{{ $subscription->id }}"
                                                               class="form-control"
                                                               value="{{ \Carbon\Carbon::parse($subscription->next_payment_at)->format('Y-m-d\TH:i') }}">
                                                    </div>
                                                </div>

                                                <div class="modal-footer">
                                                    <button type="submit" class="btn btn-primary">Сохранить</button>
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>

                                    <div class="modal fade" id="deleteModal{{ $subscription->id }}" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="deleteModalLabel">Подтверждение удаления</h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    Вы уверены, что хотите удалить эту подписку?
                                                </div>
                                                <div class="modal-footer">
                                                    <form action="{{ route('admin.subscriptions.destroy', $subscription) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger">Да, удалить</button>

                                                        @if(!empty($user))
                                                            <input type="hidden" name="return_url" value="{{ url()->current() }}">
                                                        @endif
                                                    </form>
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
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
        $(document).ready(function () {
            $('#subscriptions-table').DataTable({
                "order": [[0, "desc"]],
                "columnDefs": [
                    { "orderable": false, "targets": {{ empty($user) ? 6 : 7 }} }
                ]
            });
        });
    </script>
@endsection
