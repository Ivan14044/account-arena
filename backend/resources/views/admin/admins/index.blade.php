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
                                <span class="badge badge-secondary">#{{ $user->id }}</span>
                            </td>
                            <td class="align-middle font-weight-bold">{{ $user->name }}</td>
                            <td class="align-middle text-muted">{{ $user->email }}</td>
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

                                    <button class="btn btn-sm btn-warning" 
                                            data-toggle="modal" 
                                            data-target="#blockModal{{ $user->id }}"
                                            title="{{ $user->is_blocked ? 'Разблокировать' : 'Заблокировать' }}"
                                            data-toggle-tooltip="tooltip">
                                        <i class="fas fa-{{ $user->is_blocked ? 'lock-open' : 'lock' }}"></i>
                                    </button>

                                    <button class="btn btn-sm btn-danger" 
                                            data-toggle="modal" 
                                            data-target="#deleteModal{{ $user->id }}"
                                            title="Удалить"
                                            data-toggle-tooltip="tooltip">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>

                                <!-- Modals remain same but with modern buttons -->
                                <div class="modal fade" id="deleteModal{{ $user->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered" role="document">
                                        <div class="modal-content modal-modern">
                                            <div class="modal-header modal-header-modern bg-danger text-white">
                                                <h5 class="modal-title">Подтверждение удаления</h5>
                                                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                            </div>
                                            <div class="modal-body modal-body-modern text-left">
                                                Вы уверены, что хотите удалить администратора <strong>{{ $user->name }}</strong>? Это действие нельзя отменить.
                                            </div>
                                            <div class="modal-footer modal-footer-modern">
                                                <form action="{{ route('admin.admins.destroy', $user) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-modern">Удалить</button>
                                                </form>
                                                <button type="button" class="btn btn-secondary btn-modern" data-dismiss="modal">Отмена</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="modal fade" id="blockModal{{ $user->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered" role="document">
                                        <div class="modal-content modal-modern">
                                            <div class="modal-header modal-header-modern {{ $user->is_blocked ? 'bg-success' : 'bg-warning' }} text-white">
                                                <h5 class="modal-title">{{ $user->is_blocked ? 'Разблокировать' : 'Заблокировать' }} администратора</h5>
                                                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                            </div>
                                            <div class="modal-body modal-body-modern text-left">
                                                Вы уверены, что хотите {{ $user->is_blocked ? 'разблокировать' : 'заблокировать' }} администратора <strong>{{ $user->name }}</strong>?
                                            </div>
                                            <div class="modal-footer modal-footer-modern">
                                                <form action="{{ route('admin.admins.block', $user) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn {{ $user->is_blocked ? 'btn-success' : 'btn-warning' }} btn-modern">
                                                        {{ $user->is_blocked ? 'Разблокировать' : 'Заблокировать' }}
                                                    </button>
                                                </form>
                                                <button type="button" class="btn btn-secondary btn-modern" data-dismiss="modal">Отмена</button>
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
                "dom": '<"d-flex justify-content-between align-items-center mb-3"l<"ml-auto"f>>rt<"d-flex justify-content-between align-items-center mt-3"ip>'
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
            $('[data-toggle-tooltip="tooltip"]').tooltip();
        });
    </script>
@endsection
