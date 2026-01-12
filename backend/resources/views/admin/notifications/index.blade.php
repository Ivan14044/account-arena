@extends('adminlte::page')

@section('title', 'Уведомления пользователям')

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="m-0 font-weight-light">
                    Уведомления пользователям
                </h1>
                <p class="text-muted mb-0 mt-1">История отправленных уведомлений пользователям</p>
            </div>
            <div>
                <a href="{{ route('admin.notifications.create') }}" class="btn btn-primary btn-modern">
                    <i class="fas fa-bullhorn mr-2"></i>Массовое уведомление
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

    <!-- Статистика -->
    <div class="row mb-4">
        <div class="col-lg-4 col-6">
            <div class="stat-card stat-card-primary stat-card-compact">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Всего уведомлений</div>
                        <div class="stat-value">{{ $statistics['total'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-6">
            <div class="stat-card stat-card-success stat-card-compact">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-envelope-open"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Прочитано</div>
                        <div class="stat-value">{{ $statistics['read'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-6">
            <div class="stat-card stat-card-warning stat-card-compact">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Не прочитано</div>
                        <div class="stat-value">{{ $statistics['unread'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-modern">
        <div class="card-header-modern p-0">
            <ul class="nav nav-pills p-2">
                <li class="nav-item"><a class="nav-link active" href="#all" data-toggle="tab" id="filterAll">Все</a></li>
                <li class="nav-item"><a class="nav-link" href="#unread" data-toggle="tab" id="filterUnread">Не прочитано</a></li>
                <li class="nav-item"><a class="nav-link" href="#read" data-toggle="tab" id="filterRead">Прочитано</a></li>
            </ul>
        </div>
        <div class="card-body-modern">
            <div class="table-responsive">
                <table id="notifications-table" class="table table-hover modern-table">
                    <thead>
                    <tr>
                        <th style="width: 60px" class="text-center">ID</th>
                        <th>Пользователь</th>
                        <th>Уведомление</th>
                        <th class="text-center">Статус</th>
                        <th class="text-center">Создано</th>
                        <th style="width: 80px" class="text-center">Действия</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($notifications as $notification)
                        <tr>
                            <td class="text-center align-middle">
                                <span class="badge badge-light font-weight-bold">#{{ $notification->id }}</span>
                            </td>
                            <td class="align-middle">
                                <a href="{{ route('admin.users.edit', $notification->user) }}" target="_blank" class="text-primary font-weight-bold text-decoration-none">
                                    {{ $notification->user->name }}
                                </a>
                                <div class="text-muted small">{{ $notification->user->email }}</div>
                            </td>
                            <td class="align-middle">
                                @if($notification->template)
                                    <div class="font-weight-bold">{{ $notification->template->name }}</div>
                                    <div class="text-muted small">Код: <code>{{ $notification->template->code }}</code></div>
                                @else
                                    <span class="text-muted small italic">Без шаблона</span>
                                @endif
                            </td>
                            <td class="text-center align-middle">
                                @if($notification->read_at)
                                    <span class="badge badge-success badge-modern">Прочитано</span>
                                @else
                                    <span class="badge badge-warning badge-modern">Не прочитано</span>
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
                                    <button class="btn btn-sm btn-danger btn-delete-notification" 
                                            data-user="{{ $notification->user->name }}"
                                            data-action="{{ route('admin.notifications.destroy', $notification) }}" 
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
                    <h6 class="font-weight-bold" id="delete-notification-user"></h6>
                    <p class="mt-3">Вы действительно хотите удалить уведомление этого пользователя?<br>
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
@endsection

@section('js')
    <script>
        $(document).ready(function () {
            var table = $('#notifications-table').DataTable({
                "order": [[0, "desc"]],
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Russian.json"
                },
                "pageLength": 25,
                "columnDefs": [
                    { "orderable": false, "targets": 5 }
                ],
                "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip'
            });

            // Фильтры по табу
            $('#filterAll').on('click', function() { table.column(3).search('').draw(); });
            $('#filterUnread').on('click', function() { table.column(3).search('Не прочитано').draw(); });
            $('#filterRead').on('click', function() { table.column(3).search('Прочитано').draw(); });

            $('[data-toggle="tooltip"]').tooltip();

            // Динамическая модалка
            $('.btn-delete-notification').on('click', function() {
                $('#delete-notification-user').text($(this).data('user'));
                $('#delete-notification-form').attr('action', $(this).data('action'));
                $('#singleDeleteModal').modal('show');
            });
        });
    </script>
@endsection
