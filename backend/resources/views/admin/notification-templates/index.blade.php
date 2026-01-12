@extends('adminlte::page')

@section('title', 'Шаблоны уведомлений')

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="m-0 font-weight-light">
                    Шаблоны уведомлений
                </h1>
                <p class="text-muted mb-0 mt-1">Управление структурой и текстами системных уведомлений</p>
            </div>
            @if(request('type') === 'custom')
                <div>
                    <a href="{{ route('admin.notification-templates.create') }}" class="btn btn-primary btn-modern">
                        <i class="fas fa-plus mr-2"></i>Создать шаблон
                    </a>
                </div>
            @endif
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
                        <i class="fas fa-copy"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Всего шаблонов</div>
                        <div class="stat-value">{{ $statistics['total'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-6">
            <div class="stat-card stat-card-success stat-card-compact">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Системные</div>
                        <div class="stat-value">{{ $statistics['system'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-6">
            <div class="stat-card stat-card-info stat-card-compact">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Пользовательские</div>
                        <div class="stat-value">{{ $statistics['custom'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-modern">
        <div class="card-header-modern p-0">
            <ul class="nav nav-pills p-2">
                <li class="nav-item">
                    <a class="nav-link {{ request('type') !== 'custom' ? 'active' : '' }}"
                       href="{{ route('admin.notification-templates.index') }}">
                        <i class="fas fa-microchip mr-1"></i>Системные
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request('type') === 'custom' ? 'active' : '' }}"
                       href="{{ route('admin.notification-templates.index', ['type' => 'custom']) }}">
                        <i class="fas fa-user-edit mr-1"></i>Пользовательские
                    </a>
                </li>
            </ul>
        </div>
        <div class="card-body-modern">
            <div class="table-responsive">
                <table id="notification-templates-table" class="table table-hover modern-table">
                    <thead>
                    <tr>
                        <th style="width: 60px" class="text-center">ID</th>
                        <th>Название шаблона</th>
                        <th class="text-center">Код</th>
                        <th style="width: 120px" class="text-center">Действия</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($notificationTemplates as $notificationTemplate)
                        <tr>
                            <td class="text-center align-middle">
                                <span class="badge badge-secondary">#{{ $notificationTemplate->id }}</span>
                            </td>
                            <td class="align-middle font-weight-bold">
                                {{ $notificationTemplate->name }}
                            </td>
                            <td class="text-center align-middle">
                                <code class="text-primary small bg-light px-2 py-1 rounded border">
                                    {{ $notificationTemplate->code }}
                                </code>
                            </td>
                            <td class="text-center align-middle">
                                <div class="action-buttons justify-content-center">
                                    <a href="{{ route('admin.notification-templates.edit', $notificationTemplate) }}" 
                                       class="btn btn-sm btn-primary" 
                                       title="Редактировать"
                                       data-toggle="tooltip">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    @if(request('type') === 'custom')
                                        <button class="btn btn-sm btn-danger" 
                                                data-toggle="modal" 
                                                data-target="#deleteModal{{ $notificationTemplate->id }}"
                                                title="Удалить"
                                                data-toggle-tooltip="tooltip">
                                            <i class="fas fa-trash"></i>
                                        </button>

                                        <div class="modal fade" id="deleteModal{{ $notificationTemplate->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered" role="document">
                                                <div class="modal-content modal-modern">
                                                    <div class="modal-header modal-header-modern bg-danger text-white">
                                                        <h5 class="modal-title">Подтверждение удаления</h5>
                                                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                                    </div>
                                                    <div class="modal-body modal-body-modern text-left">
                                                        Это действие навсегда удалит шаблон <strong>{{ $notificationTemplate->name }}</strong> и все связанные с ним уведомления.
                                                        Вы уверены?
                                                    </div>
                                                    <div class="modal-footer modal-footer-modern">
                                                        <form action="{{ route('admin.notification-templates.destroy', $notificationTemplate) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-danger btn-modern">Да, удалить</button>
                                                        </form>
                                                        <button type="button" class="btn btn-secondary btn-modern" data-dismiss="modal">Отмена</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
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
            $('#notification-templates-table').DataTable({
                "order": [[0, "desc"]],
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Russian.json"
                },
                "pageLength": 25,
                "columnDefs": [
                    { "orderable": false, "targets": 3 }
                ],
                "dom": '<"d-flex justify-content-between align-items-center mb-3"l<"ml-auto"f>>rt<"d-flex justify-content-between align-items-center mt-3"ip>'
            });

            $('[data-toggle="tooltip"]').tooltip();
            $('[data-toggle-tooltip="tooltip"]').tooltip();
        });
    </script>
@endsection
