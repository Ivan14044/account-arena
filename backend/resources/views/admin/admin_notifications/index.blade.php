@extends('adminlte::page')

@section('title', 'Уведомления администратора')

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="m-0 font-weight-light">
                    Уведомления
                </h1>
                <p class="text-muted mb-0 mt-1">Системные уведомления и оповещения для администраторов</p>
            </div>
            <div>
                <form action="{{ route('admin.admin_notifications.read-all') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-modern">
                        <i class="fas fa-check-double mr-2"></i>Прочитать всё
                    </button>
                </form>
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

    <div class="card card-modern">
        <div class="card-body-modern">
            <div class="table-responsive">
                <table id="notifications-table" class="table table-hover modern-table">
                    <thead>
                    <tr>
                        <th style="width: 60px" class="text-center">ID</th>
                        <th>Тип</th>
                        <th>Содержание</th>
                        <th class="text-center">Статус</th>
                        <th class="text-center">Дата</th>
                        <th style="width: 120px" class="text-center">Действия</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($notifications as $notification)
                        <tr @if(!$notification->read) class="row-unread" @endif>
                            <td class="text-center align-middle">
                                <span class="badge badge-light font-weight-bold">#{{ $notification->id }}</span>
                            </td>
                            <td class="align-middle text-nowrap">
                                <span class="badge badge-info badge-modern px-2 py-1">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    @php
                                        $typeKey = 'notifier.types.' . $notification->type;
                                        $translatedType = __($typeKey);
                                        if ($translatedType === $typeKey) {
                                            // Если перевод не найден, форматируем сам тип
                                            $translatedType = ucfirst(str_replace('_', ' ', $notification->type));
                                        }
                                    @endphp
                                    {{ $translatedType }}
                                </span>
                            </td>
                            <td class="align-middle">
                                <div class="font-weight-bold text-dark">{{ $notification->formatted_title }}</div>
                                <div class="text-muted small mt-1">{{ $notification->formatted_message }}</div>
                            </td>
                            <td class="text-center align-middle">
                                @if($notification->read)
                                    <span class="text-success small">
                                        <i class="fas fa-check-circle mr-1"></i>Прочитано
                                    </span>
                                @else
                                    <span class="text-primary font-weight-bold small">
                                        <i class="fas fa-circle mr-1"></i>Новое
                                    </span>
                                @endif
                            </td>
                            <td class="text-center align-middle text-muted" data-order="{{ strtotime($notification->created_at) }}">
                                <small>
                                    <i class="far fa-calendar-alt mr-1"></i>
                                    {{ \Carbon\Carbon::parse($notification->created_at)->format('d.m.Y') }}
                                    <br>
                                    <i class="far fa-clock mr-1"></i>
                                    {{ \Carbon\Carbon::parse($notification->created_at)->format('H:i') }}
                                </small>
                            </td>
                            <td class="text-center align-middle">
                                <div class="action-buttons justify-content-center">
                                    @if(!$notification->read)
                                        <a href="{{ route('admin.admin_notifications.read', $notification->id) }}"
                                           class="btn btn-sm btn-success"
                                           title="Отметить как прочитанное"
                                           data-toggle="tooltip">
                                            <i class="fas fa-check"></i>
                                        </a>
                                    @endif

                                    <button class="btn btn-sm btn-danger btn-delete-notification" 
                                            data-title="{{ $notification->title }}"
                                            data-action="{{ route('admin.admin_notifications.destroy', $notification->id) }}"
                                            title="Удалить"
                                            data-toggle-tooltip="tooltip">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Единое модальное окно для удаления --}}
    <div class="modal fade" id="singleDeleteModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-danger text-white border-0">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Подтверждение удаления
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body py-4 text-center">
                    <i class="fas fa-bell-slash fa-3x text-danger mb-3"></i>
                    <h6 class="font-weight-bold" id="delete-notification-title"></h6>
                    <p class="mt-3">Вы действительно хотите удалить это уведомление?<br>
                    <small class="text-danger">Это действие нельзя отменить!</small></p>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <form id="delete-notification-form" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-modern">
                            <i class="fas fa-trash-alt mr-2"></i>Да, удалить
                        </button>
                    </form>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        Отмена
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('css')
    @include('admin.layouts.modern-styles')
    <style>
        .row-unread {
            background-color: rgba(78, 115, 223, 0.05);
        }
        .row-unread:hover {
            background-color: rgba(78, 115, 223, 0.08) !important;
        }
    </style>
@endsection

@section('js')
    <script>
        $(document).ready(function() {
            $('#notifications-table').DataTable({
                'order': [[0, 'desc']],
                'language': {
                    'url': '//cdn.datatables.net/plug-ins/1.10.24/i18n/Russian.json'
                },
                'pageLength': 25,
                'columnDefs': [
                    { 'orderable': false, 'targets': 5 }
                ],
                "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip'
            });

            $('[data-toggle="tooltip"]').tooltip();

            // Динамическая модалка
            $('.btn-delete-notification').on('click', function() {
                $('#delete-notification-title').text($(this).data('title'));
                $('#delete-notification-form').attr('action', $(this).data('action'));
                $('#singleDeleteModal').modal('show');
            });
        });
    </script>
@endsection
