@extends('adminlte::page')

@section('title', 'Управление пользователями')

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1>Пользователи</h1>
                <p class="text-muted mb-0">Управление учетными записями, балансом и статусами пользователей</p>
            </div>
            <div>
                <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-modern">
                    <i class="fas fa-plus mr-2"></i>Новый пользователь
                </a>
            </div>
        </div>
    </div>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <!-- Статистика -->
    <div class="row mb-4">
        <div class="col-lg-3 col-6">
            <div class="stat-card stat-card-primary stat-card-compact">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Всего пользователей</div>
                        <div class="stat-value">{{ $users->count() }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="stat-card stat-card-success stat-card-compact">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Активные</div>
                        <div class="stat-value">{{ $users->where('is_blocked', false)->count() }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="stat-card stat-card-info stat-card-compact">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-store"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Поставщики</div>
                        <div class="stat-value">{{ $users->where('is_supplier', true)->count() }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="stat-card stat-card-danger stat-card-compact">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-user-lock"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Заблокированы</div>
                        <div class="stat-value">{{ $users->where('is_blocked', true)->count() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Таблица пользователей -->
    <div class="card card-modern">
        <div class="card-header-modern">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0 font-weight-normal">Список пользователей</h5>
                    <small class="text-muted">Всего записей: {{ $users->count() }}</small>
                </div>
                <div class="filters-container">
                    <div class="btn-group btn-group-filter" role="group">
                        <button type="button" class="btn btn-filter active" id="filterAll" data-filter="all">
                            Все
                        </button>
                        <button type="button" class="btn btn-filter" id="filterActive" data-filter="active">
                            Активные
                        </button>
                        <button type="button" class="btn btn-filter" id="filterBlocked" data-filter="blocked">
                            Заблокированные
                        </button>
                        <button type="button" class="btn btn-filter" id="filterSuppliers" data-filter="suppliers">
                            Поставщики
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body-modern">
            <div class="table-responsive">
                <table id="users-table" class="table table-hover table-striped mb-0 modern-table">
                    <thead class="thead-light">
                        <tr>
                            <th style="width: 50px" class="text-center">ID</th>
                            <th style="min-width: 250px">Пользователь</th>
                            <th>Баланс</th>
                            <th>Статус</th>
                            <th>Покупки</th>
                            <th>Дата регистрации</th>
                            <th class="text-center" style="width: 180px">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($users as $user)
                        <tr class="user-row" data-status="{{ $user->is_blocked ? 'blocked' : ($user->is_pending ? 'pending' : 'active') }}">
                            <td class="text-center align-middle">
                                <span class="badge badge-light font-weight-bold">{{ $user->id }}</span>
                            </td>
                            <td class="align-middle">
                                <div class="d-flex align-items-center">
                                    <div class="user-avatar mr-3">
                                        @if($user->avatar)
                                            <img src="{{ $user->avatar }}" alt="{{ $user->name }}" class="rounded-circle" width="40" height="40">
                                        @else
                                            <div class="avatar-placeholder rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; font-weight: bold;">
                                                {{ strtoupper(substr($user->name, 0, 1)) }}
                                            </div>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="font-weight-bold text-dark">{{ $user->name }}</div>
                                        <small class="text-muted">
                                            <i class="fas fa-envelope mr-1"></i>{{ $user->email }}
                                        </small>
                                    </div>
                                </div>
                            </td>
                            <td class="align-middle">
                                <!-- Баланс аккаунта для покупок (для всех пользователей) -->
                                <span class="badge badge-light font-weight-bold" style="font-size: 14px;" title="Баланс для покупок">
                                    <i class="fas fa-wallet text-success mr-1"></i>
                                    ${{ number_format($user->balance ?? 0, 2) }}
                                </span>
                            </td>
                            <td class="align-middle">
                                @if($user->is_blocked)
                                    <span class="badge badge-danger badge-modern">
                                        <i class="fas fa-ban mr-1"></i>Заблокирован
                                    </span>
                                @else
                                    <span class="badge badge-success badge-modern">
                                        <i class="fas fa-check-circle mr-1"></i>Активен
                                    </span>
                                @endif
                                @if($user->is_supplier)
                                    <br>
                                    <span class="badge badge-info badge-modern mt-1">
                                        <i class="fas fa-store mr-1"></i>Поставщик
                                    </span>
                                @endif
                            </td>
                            <td class="align-middle">
                                @php
                                    $purchasesCount = $user->purchases()->count();
                                @endphp
                                <div class="text-center">
                                    <span class="badge {{ $purchasesCount > 0 ? 'badge-success' : 'badge-light' }} badge-modern" title="{{ $purchasesCount }} покупок" style="font-size: 1rem; padding: 0.5rem 0.75rem;">
                                        <i class="fas fa-shopping-cart mr-1"></i>
                                        {{ $purchasesCount }}
                                    </span>
                                </div>
                            </td>
                            <td class="align-middle" data-order="{{ strtotime($user->created_at) }}">
                                <small class="text-muted">
                                    <i class="far fa-calendar-alt mr-1"></i>
                                    {{ \Carbon\Carbon::parse($user->created_at)->format('d.m.Y') }}
                                    <br>
                                    <i class="far fa-clock mr-1"></i>
                                    {{ \Carbon\Carbon::parse($user->created_at)->format('H:i') }}
                                </small>
                            </td>
                            <td class="text-center align-middle">
                                <div class="btn-group" role="group">
                                    <a href="{{ route('admin.users.edit', $user) }}"
                                       class="btn btn-sm btn-primary"
                                       title="Редактировать"
                                       data-toggle="tooltip">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <button class="btn btn-sm btn-{{ $user->is_blocked ? 'success' : 'warning' }} btn-block-user"
                                            data-name="{{ $user->name }}"
                                            data-email="{{ $user->email }}"
                                            data-blocked="{{ $user->is_blocked }}"
                                            data-action="{{ route('admin.users.block', $user) }}"
                                            title="{{ $user->is_blocked ? 'Разблокировать' : 'Заблокировать' }}"
                                            data-toggle-tooltip="tooltip">
                                        <i class="fas fa-{{ $user->is_blocked ? 'unlock' : 'lock' }}"></i>
                                    </button>

                                    <button class="btn btn-sm btn-danger btn-delete-user"
                                            data-name="{{ $user->name }}"
                                            data-email="{{ $user->email }}"
                                            data-action="{{ route('admin.users.destroy', $user) }}"
                                            title="Удалить"
                                            data-toggle-tooltip="tooltip">
                                        <i class="fas fa-trash-alt"></i>
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
                <div class="modal-body py-4">
                    <div class="text-center mb-3">
                        <i class="fas fa-user-times fa-3x text-danger mb-3"></i>
                        <h6 class="font-weight-bold" id="delete-user-name"></h6>
                        <p class="text-muted mb-0" id="delete-user-email"></p>
                    </div>
                    <p class="text-center mb-0">
                        Вы действительно хотите удалить этого пользователя?<br>
                        <small class="text-danger">Это действие нельзя отменить!</small>
                    </p>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <form id="delete-user-form" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash-alt mr-2"></i>Да, удалить
                        </button>
                    </form>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-2"></i>Отмена
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Единое модальное окно для блокировки/разблокировки --}}
    <div class="modal fade" id="singleBlockModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg">
                <div id="block-modal-header" class="modal-header text-white border-0">
                    <h5 class="modal-title">
                        <i id="block-modal-icon-title" class="fas mr-2"></i>
                        <span id="block-modal-title-text"></span> пользователя
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body py-4">
                    <div class="text-center mb-3">
                        <i id="block-modal-icon-body" class="fas fa-3x mb-3"></i>
                        <h6 class="font-weight-bold" id="block-user-name"></h6>
                        <p class="text-muted mb-0" id="block-user-email"></p>
                    </div>
                    <p class="text-center mb-0">
                        Вы уверены, что хотите <span id="block-action-text"></span> этого пользователя?
                    </p>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <form id="block-user-form" method="POST">
                        @csrf
                        <button type="submit" id="block-modal-submit" class="btn">
                            <i id="block-modal-submit-icon" class="fas mr-2"></i>
                            <span id="block-modal-submit-text"></span>
                        </button>
                    </form>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-2"></i>Отмена
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('css')
    @include('admin.layouts.modern-styles')
    <style>
        /* Специфичные стили для страницы пользователей */
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #f8f9fc;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4e73df;
            font-weight: 600;
            font-size: 0.75rem;
            border: 1px solid #e3e6f0;
        }

        .user-info-cell {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .user-details {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-weight: 600;
            color: #2c3e50;
            line-height: 1.2;
        }

        .user-email {
            font-size: 0.75rem;
            color: #858796;
        }

        .balance-badge {
            font-family: 'Monaco', 'Consolas', monospace;
            font-weight: 600;
        }

        /* Дополнительная настройка для модальных окон на этой странице */
        #singleDeleteModal .modal-body,
        #singleBlockModal .modal-body {
            padding: 2.5rem 2rem;
        }
    </style>
@stop

@section('js')
    <script>
        $(document).ready(function () {
            // Инициализация DataTable
            var table = $('#users-table').DataTable({
                "order": [[0, "desc"]],
                "columnDefs": [
                    { "orderable": false, "targets": 6 }
                ],
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/ru.json"
                },
                "pageLength": 25,
                "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
            });

            // Инициализация тултипов
            $('[data-toggle="tooltip"]').tooltip();

            // Фильтры по статусу
            $('#filterAll').on('click', function() {
                table.column(3).search('').draw();
                $('.btn-group-filter .btn').removeClass('active');
                $(this).addClass('active');
            });

            $('#filterActive').on('click', function() {
                table.search('').columns().search('');
                table.column(3).search('Активен').draw();
                $('.btn-group-filter .btn').removeClass('active');
                $(this).addClass('active');
            });

            $('#filterBlocked').on('click', function() {
                table.search('').columns().search('');
                table.column(3).search('Заблокирован').draw();
                $('.btn-group-filter .btn').removeClass('active');
                $(this).addClass('active');
            });

            $('#filterSuppliers').on('click', function() {
                table.search('').columns().search('');
                table.column(3).search('Поставщик').draw();
                $('.btn-group-filter .btn').removeClass('active');
                $(this).addClass('active');
            });

            // Автоскрытие алертов через 5 секунд
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);

            // Анимация при наведении на статистику
            $('.stat-card').on('mouseenter', function() {
                $(this).find('.stat-icon i').addClass('fa-bounce');
            }).on('mouseleave', function() {
                $(this).find('.stat-icon i').removeClass('fa-bounce');
            });

            // ДИНАМИЧЕСКИЕ МОДАЛКИ
            $('.btn-delete-user').on('click', function() {
                const name = $(this).data('name');
                const email = $(this).data('email');
                const action = $(this).data('action');

                $('#delete-user-name').text(name);
                $('#delete-user-email').text(email);
                $('#delete-user-form').attr('action', action);
                $('#singleDeleteModal').modal('show');
            });

            $('.btn-block-user').on('click', function() {
                const name = $(this).data('name');
                const email = $(this).data('email');
                const isBlocked = $(this).data('blocked');
                const action = $(this).data('action');

                $('#block-user-name').text(name);
                $('#block-user-email').text(email);
                $('#block-user-form').attr('action', action);

                if (isBlocked) {
                    $('#block-modal-header').removeClass('bg-warning').addClass('bg-success');
                    $('#block-modal-icon-title').removeClass('fa-lock').addClass('fa-unlock');
                    $('#block-modal-title-text').text('Разблокировать');
                    $('#block-modal-icon-body').removeClass('fa-user-lock text-warning').addClass('fa-user-check text-success');
                    $('#block-action-text').text('разблокировать');
                    $('#block-modal-submit').removeClass('btn-warning').addClass('btn-success');
                    $('#block-modal-submit-icon').removeClass('fa-lock').addClass('fa-unlock');
                    $('#block-modal-submit-text').text('Разблокировать');
                } else {
                    $('#block-modal-header').removeClass('bg-success').addClass('bg-warning');
                    $('#block-modal-icon-title').removeClass('fa-unlock').addClass('fa-lock');
                    $('#block-modal-title-text').text('Заблокировать');
                    $('#block-modal-icon-body').removeClass('fa-user-check text-success').addClass('fa-user-lock text-warning');
                    $('#block-action-text').text('заблокировать');
                    $('#block-modal-submit').removeClass('btn-success').addClass('btn-warning');
                    $('#block-modal-submit-icon').removeClass('fa-unlock').addClass('fa-lock');
                    $('#block-modal-submit-text').text('Заблокировать');
                }

                $('#singleBlockModal').modal('show');
            });
        });
    </script>
@stop
