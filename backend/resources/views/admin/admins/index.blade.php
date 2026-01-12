@extends('adminlte::page')

@section('title', 'Администраторы')

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="m-0 font-weight-light">
                    Администраторы
                </h1>
                <p class="text-muted mb-0 mt-1">Управление учетными записями администраторов платформы</p>
            </div>
            <div>
                <a href="{{ route('admin.admins.create') }}" class="btn btn-primary btn-modern">
                    <i class="fas fa-plus mr-2"></i>Добавить администратора
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
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Всего администраторов</div>
                        <div class="stat-value">{{ $statistics['total'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-6">
            <div class="stat-card stat-card-success stat-card-compact">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Активные</div>
                        <div class="stat-value">{{ $statistics['active'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-6">
            <div class="stat-card stat-card-danger stat-card-compact">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-user-slash"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Заблокированные</div>
                        <div class="stat-value">{{ $statistics['blocked'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-modern">
        <div class="card-header-modern p-0">
            <ul class="nav nav-pills p-2">
                <li class="nav-item"><a class="nav-link active" href="#all" data-toggle="tab" id="filterAll">Все</a></li>
                <li class="nav-item"><a class="nav-link" href="#active" data-toggle="tab" id="filterActive">Активные</a></li>
                <li class="nav-item"><a class="nav-link" href="#blocked" data-toggle="tab" id="filterBlocked">Заблокированные</a></li>
            </ul>
        </div>
        <div class="card-body-modern">
            <div class="table-responsive">
                <table id="admins-table" class="table table-hover modern-table">
                    <thead>
                    <tr>
                        <th style="width: 60px" class="text-center">ID</th>
                        <th>Имя</th>
                        <th>Email</th>
                        <th class="text-center">Статус</th>
                        <th class="text-center">Дата создания</th>
                        <th style="width: 150px" class="text-center">Действия</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td class="text-center align-middle">
                                <span class="badge badge-light font-weight-bold">#{{ $user->id }}</span>
                            </td>
                            <td class="align-middle font-weight-bold">{{ $user->name }}</td>
                            <td class="align-middle text-muted small">{{ $user->email }}</td>
                            <td class="text-center align-middle">
                                @if($user->is_blocked)
                                    <span class="badge badge-danger badge-modern">Заблокирован</span>
                                @else
                                    <span class="badge badge-success badge-modern">Активен</span>
                                @endif
                            </td>
                            <td class="text-center align-middle text-muted" data-order="{{ strtotime($user->created_at) }}">
                                <small>
                                    <i class="far fa-calendar-alt mr-1"></i>
                                    {{ \Carbon\Carbon::parse($user->created_at)->format('d.m.Y') }}
                                    <br>
                                    <i class="far fa-clock mr-1"></i>
                                    {{ \Carbon\Carbon::parse($user->created_at)->format('H:i') }}
                                </small>
                            </td>
                            <td class="text-center align-middle">
                                <div class="action-buttons justify-content-center">
                                    <a href="{{ route('admin.admins.edit', $user) }}" 
                                       class="btn btn-sm btn-primary" 
                                       title="Редактировать"
                                       data-toggle="tooltip">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <button class="btn btn-sm btn-warning btn-block-admin" 
                                            data-name="{{ $user->name }}"
                                            data-blocked="{{ $user->is_blocked }}"
                                            data-action="{{ route('admin.admins.block', $user) }}"
                                            title="{{ $user->is_blocked ? 'Разблокировать' : 'Заблокировать' }}"
                                            data-toggle-tooltip="tooltip">
                                        <i class="fas fa-{{ $user->is_blocked ? 'lock-open' : 'lock' }}"></i>
                                    </button>

                                    <button class="btn btn-sm btn-danger btn-delete-admin" 
                                            data-name="{{ $user->name }}"
                                            data-action="{{ route('admin.admins.destroy', $user) }}"
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
                    <i class="fas fa-user-times fa-3x text-danger mb-3"></i>
                    <h6 class="font-weight-bold" id="delete-admin-name"></h6>
                    <p class="mt-3">Вы действительно хотите удалить этого администратора?<br>
                    <small class="text-danger">Это действие нельзя отменить!</small></p>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <form id="delete-admin-form" method="POST">
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

    {{-- Единое модальное окно для блокировки --}}
    <div class="modal fade" id="singleBlockModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg">
                <div id="block-modal-header" class="modal-header text-white border-0">
                    <h5 class="modal-title" id="block-modal-title"></h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body py-4 text-center">
                    <i id="block-modal-icon" class="fas fa-3x mb-3"></i>
                    <h6 class="font-weight-bold" id="block-admin-name"></h6>
                    <p class="mt-3" id="block-modal-text"></p>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <form id="block-admin-form" method="POST">
                        @csrf
                        <button type="submit" id="block-modal-btn" class="btn btn-modern">
                            <span id="block-modal-btn-text"></span>
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
            var table = $('#admins-table').DataTable({
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
            $('#filterAll').on('click', function() {
                table.column(3).search('').draw();
            });

            $('#filterActive').on('click', function() {
                table.column(3).search('Активен').draw();
            });

            $('#filterBlocked').on('click', function() {
                table.column(3).search('Заблокирован').draw();
            });

            $('[data-toggle="tooltip"]').tooltip();

            // ДИНАМИЧЕСКИЕ МОДАЛКИ
            $('.btn-delete-admin').on('click', function() {
                $('#delete-admin-name').text($(this).data('name'));
                $('#delete-admin-form').attr('action', $(this).data('action'));
                $('#singleDeleteModal').modal('show');
            });

            $('.btn-block-admin').on('click', function() {
                const name = $(this).data('name');
                const isBlocked = $(this).data('blocked');
                const action = $(this).data('action');

                $('#block-admin-name').text(name);
                $('#block-admin-form').attr('action', action);

                if (isBlocked) {
                    $('#block-modal-header').removeClass('bg-warning').addClass('bg-success');
                    $('#block-modal-title').text('Разблокировать администратора');
                    $('#block-modal-icon').removeClass('fa-lock text-warning').addClass('fa-lock-open text-success');
                    $('#block-modal-text').text('Вы уверены, что хотите разблокировать этого администратора?');
                    $('#block-modal-btn').removeClass('btn-warning').addClass('btn-success');
                    $('#block-modal-btn-text').text('Разблокировать');
                } else {
                    $('#block-modal-header').removeClass('bg-success').addClass('bg-warning');
                    $('#block-modal-title').text('Заблокировать администратора');
                    $('#block-modal-icon').removeClass('fa-lock-open text-success').addClass('fa-lock text-warning');
                    $('#block-modal-text').text('Вы уверены, что хотите заблокировать этого администратора?');
                    $('#block-modal-btn').removeClass('btn-success').addClass('btn-warning');
                    $('#block-modal-btn-text').text('Заблокировать');
                }

                $('#singleBlockModal').modal('show');
            });
        });
    </script>
@endsection
