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
                                <span class="badge badge-light font-weight-bold">#{{ $notificationTemplate->id }}</span>
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
                                        <button class="btn btn-sm btn-danger btn-delete-template" 
                                                data-name="{{ $notificationTemplate->name }}"
                                                data-action="{{ route('admin.notification-templates.destroy', $notificationTemplate) }}"
                                                title="Удалить"
                                                data-toggle-tooltip="tooltip">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            @if($notificationTemplates->hasPages())
                <div class="px-4 py-3 border-top">
                    <div class="d-flex justify-content-center">
                        {{ $notificationTemplates->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            @endif
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
                        <i class="fas fa-file-code fa-3x text-danger mb-3"></i>
                        <h6 class="font-weight-bold" id="delete-template-name"></h6>
                    </div>
                    <p class="text-center mb-0">
                        Вы действительно хотите удалить этот шаблон?<br>
                        <small class="text-danger">Это действие нельзя отменить!</small>
                    </p>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <form id="delete-template-form" method="POST">
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
            $('#notification-templates-table').DataTable({
                "order": [[0, "desc"]],
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/ru.json"
                },
                "paging": false,
                "info": false,
                "columnDefs": [
                    { "orderable": false, "targets": 3 }
                ],
                "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip'
            });

            $('[data-toggle="tooltip"]').tooltip();

            // ДИНАМИЧЕСКИЕ МОДАЛКИ
            $('.btn-delete-template').on('click', function() {
                const name = $(this).data('name');
                const action = $(this).data('action');

                $('#delete-template-name').text(name);
                $('#delete-template-form').attr('action', action);
                $('#singleDeleteModal').modal('show');
            });
        });
    </script>
@endsection
