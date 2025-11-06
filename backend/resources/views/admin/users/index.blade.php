@extends('adminlte::page')

@section('title', 'Пользователи')

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="m-0 font-weight-light">
                    Управление пользователями
                </h1>
                <p class="text-muted mb-0 mt-1">Управление учетными записями клиентов и поставщиков</p>
            </div>
            <div>
                <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-modern">
                    <i class="fas fa-plus mr-2"></i>Создать пользователя
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
            <div class="stat-card stat-card-primary">
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
            <div class="stat-card stat-card-success">
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
            <div class="stat-card stat-card-info">
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
            <div class="stat-card stat-card-danger">
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
                                    $purchasesCount = $user->transactions()->count();
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

                                    <button class="btn btn-sm btn-{{ $user->is_blocked ? 'success' : 'warning' }}" 
                                            data-toggle="modal" 
                                            data-target="#blockModal{{ $user->id }}"
                                            title="{{ $user->is_blocked ? 'Разблокировать' : 'Заблокировать' }}"
                                            data-toggle-tooltip="tooltip">
                                        <i class="fas fa-{{ $user->is_blocked ? 'unlock' : 'lock' }}"></i>
                                    </button>

                                    <button class="btn btn-sm btn-danger" 
                                            data-toggle="modal" 
                                            data-target="#deleteModal{{ $user->id }}"
                                            title="Удалить"
                                            data-toggle-tooltip="tooltip">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>

                                <!-- Модальное окно удаления -->
                                <div class="modal fade" id="deleteModal{{ $user->id }}" tabindex="-1" role="dialog">
                                    <div class="modal-dialog modal-dialog-centered" role="document">
                                        <div class="modal-content border-0 shadow-lg">
                                            <div class="modal-header bg-danger text-white border-0">
                                                <h5 class="modal-title">
                                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                                    Подтверждение удаления
                                                </h5>
                                                <button type="button" class="close text-white" data-dismiss="modal">
                                                    <span>&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body py-4">
                                                <div class="text-center mb-3">
                                                    <i class="fas fa-user-times fa-3x text-danger mb-3"></i>
                                                    <h6 class="font-weight-bold">{{ $user->name }}</h6>
                                                    <p class="text-muted mb-0">{{ $user->email }}</p>
                                                </div>
                                                <p class="text-center mb-0">
                                                    Вы действительно хотите удалить этого пользователя?<br>
                                                    <small class="text-danger">Это действие нельзя отменить!</small>
                                                </p>
                                            </div>
                                            <div class="modal-footer border-0 justify-content-center">
                                                <form action="{{ route('admin.users.destroy', $user) }}" method="POST">
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

                                <!-- Модальное окно блокировки -->
                                <div class="modal fade" id="blockModal{{ $user->id }}" tabindex="-1" role="dialog">
                                    <div class="modal-dialog modal-dialog-centered" role="document">
                                        <div class="modal-content border-0 shadow-lg">
                                            <div class="modal-header bg-{{ $user->is_blocked ? 'success' : 'warning' }} text-white border-0">
                                                <h5 class="modal-title">
                                                    <i class="fas fa-{{ $user->is_blocked ? 'unlock' : 'lock' }} mr-2"></i>
                                                    {{ $user->is_blocked ? 'Разблокировать' : 'Заблокировать' }} пользователя
                                                </h5>
                                                <button type="button" class="close text-white" data-dismiss="modal">
                                                    <span>&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body py-4">
                                                <div class="text-center mb-3">
                                                    <i class="fas fa-user-{{ $user->is_blocked ? 'check' : 'lock' }} fa-3x text-{{ $user->is_blocked ? 'success' : 'warning' }} mb-3"></i>
                                                    <h6 class="font-weight-bold">{{ $user->name }}</h6>
                                                    <p class="text-muted mb-0">{{ $user->email }}</p>
                                                </div>
                                                <p class="text-center mb-0">
                                                    Вы уверены, что хотите {{ $user->is_blocked ? 'разблокировать' : 'заблокировать' }} этого пользователя?
                                                </p>
                                            </div>
                                            <div class="modal-footer border-0 justify-content-center">
                                                <form action="{{ route('admin.users.block', $user) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="btn btn-{{ $user->is_blocked ? 'success' : 'warning' }}">
                                                        <i class="fas fa-{{ $user->is_blocked ? 'unlock' : 'lock' }} mr-2"></i>
                                                        {{ $user->is_blocked ? 'Разблокировать' : 'Заблокировать' }}
                                                    </button>
                                                </form>
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                                    <i class="fas fa-times mr-2"></i>Отмена
                                                </button>
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

        /* КНОПКА СОЗДАНИЯ */
        .btn-modern {
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            letter-spacing: 0.3px;
            border-radius: 0.375rem;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            transition: all 0.2s ease;
        }
        .btn-modern:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        /* КАРТОЧКИ СТАТИСТИКИ */
        .stat-card {
            background: white;
            border: 1px solid #e3e6f0;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            border-left: 4px solid;
        }
        
        .stat-card-primary { border-left-color: #4e73df; }
        .stat-card-success { border-left-color: #1cc88a; }
        .stat-card-info { border-left-color: #36b9cc; }
        .stat-card-danger { border-left-color: #e74a3b; }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
        
        .stat-card-body {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .stat-card-primary .stat-icon {
            background: rgba(78, 115, 223, 0.1);
            color: #4e73df;
        }
        .stat-card-success .stat-icon {
            background: rgba(28, 200, 138, 0.1);
            color: #1cc88a;
        }
        .stat-card-info .stat-icon {
            background: rgba(54, 185, 204, 0.1);
            color: #36b9cc;
        }
        .stat-card-danger .stat-icon {
            background: rgba(231, 74, 59, 0.1);
            color: #e74a3b;
        }
        
        .stat-content {
            text-align: right;
        }
        
        .stat-label {
            font-size: 0.75rem;
            color: #858796;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
            line-height: 1;
        }

        /* КАРТОЧКА ТАБЛИЦЫ */
        .card-modern {
            border: 1px solid #e3e6f0;
            border-radius: 0.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        .card-header-modern {
            background: white;
            border-bottom: 2px solid #e3e6f0;
            padding: 1.25rem 1.5rem;
        }
        
        .card-header-modern h5 {
            color: #2c3e50;
            font-weight: 500;
        }
        
        .card-body-modern {
            padding: 0;
        }

        /* ФИЛЬТРЫ */
        .filters-container {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-group-filter {
            background: #f8f9fc;
            border-radius: 0.375rem;
            padding: 0.25rem;
        }
        
        .btn-filter {
            background: transparent;
            border: none;
            color: #5a6c7d;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            border-radius: 0.25rem;
            transition: all 0.2s ease;
        }
        
        .btn-filter:hover {
            background: rgba(0,0,0,0.05);
            color: #2c3e50;
        }
        
        .btn-filter.active {
            background: white;
            color: #4e73df;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        }

        /* ТАБЛИЦА */
        .modern-table {
            font-size: 0.875rem;
            margin-bottom: 0;
        }
        
        .modern-table thead th {
            background: #f8f9fc;
            border-top: none;
            border-bottom: 2px solid #e3e6f0;
            color: #5a6c7d;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            padding: 1rem 1.25rem;
        }
        
        .modern-table tbody td {
            padding: 1rem 1.25rem;
            vertical-align: middle;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .modern-table tbody tr {
            transition: background-color 0.2s ease;
        }
        
        .modern-table tbody tr:hover {
            background-color: #f8f9fc;
        }
        
        .modern-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* АВАТАРЫ */
        .user-avatar img,
        .avatar-placeholder {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid #e3e6f0;
        }
        
        .avatar-placeholder {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
        }

        /* БЕЙДЖИ */
        .badge-modern {
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 0.25rem;
            letter-spacing: 0.3px;
        }
        
        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .badge-info {
            background: #dbeafe;
            color: #1e40af;
        }

        /* КНОПКИ ДЕЙСТВИЙ */
        .btn-group .btn {
            border-radius: 0.25rem !important;
            margin: 0 2px;
            transition: all 0.2s ease;
        }
        
        .btn-group .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.12);
        }
        
        .btn-sm {
            padding: 0.375rem 0.75rem;
        }

        /* МОДАЛЬНЫЕ ОКНА */
        .modal-content {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        .modal-header {
            border-bottom: 1px solid #e3e6f0;
            padding: 1.5rem;
        }
        
        .modal-body {
            padding: 2rem;
        }
        
        .modal-footer {
            border-top: 1px solid #e3e6f0;
            padding: 1.25rem 1.5rem;
        }

        /* ALERTS */
        .alert {
            border: none;
            border-left: 4px solid;
            border-radius: 0.375rem;
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

        /* АНИМАЦИИ */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .stat-card {
            animation: fadeIn 0.4s ease;
        }
        
        .stat-card:nth-child(1) { animation-delay: 0s; }
        .stat-card:nth-child(2) { animation-delay: 0.1s; }
        .stat-card:nth-child(3) { animation-delay: 0.2s; }
        .stat-card:nth-child(4) { animation-delay: 0.3s; }

        /* ТИПОГРАФИКА */
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            color: #2c3e50;
        }
        
        .font-weight-light {
            font-weight: 300 !important;
        }

        /* ДОПОЛНИТЕЛЬНЫЕ СТИЛИ */
        .text-muted {
            color: #858796 !important;
        }
        
        .shadow-sm {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
        }
    </style>
@endsection

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
                $('.btn-group .btn').removeClass('active');
                $(this).addClass('active');
            });

            $('#filterActive').on('click', function() {
                table.search('').columns().search('');
                table.column(3).search('^(?!.*Заблокирован)(?!.*Поставщик).*$', true, false).draw();
                $('.btn-group .btn').removeClass('active');
                $(this).addClass('active');
            });

            $('#filterBlocked').on('click', function() {
                table.search('').columns().search('');
                table.column(3).search('Заблокирован').draw();
                $('.btn-group .btn').removeClass('active');
                $(this).addClass('active');
            });

            $('#filterSuppliers').on('click', function() {
                table.search('').columns().search('');
                table.column(3).search('Поставщик').draw();
                $('.btn-group .btn').removeClass('active');
                $(this).addClass('active');
            });

            // Активировать фильтр "Все" по умолчанию
            $('#filterAll').addClass('active');

            // Автоскрытие алертов через 5 секунд
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);

            // Анимация при наведении на статистику
            $('.small-box').on('mouseenter', function() {
                $(this).find('.icon i').addClass('fa-bounce');
            }).on('mouseleave', function() {
                $(this).find('.icon i').removeClass('fa-bounce');
            });
        });
    </script>
@endsection
