@extends('adminlte::page')

@section('title', 'Страницы')

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="m-0 font-weight-light">
                    Статические страницы
                </h1>
                <p class="text-muted mb-0 mt-1">Управление текстовыми страницами и правилами платформы</p>
            </div>
            <div>
                <a href="{{ route('admin.pages.create') }}" class="btn btn-primary btn-modern">
                    <i class="fas fa-plus mr-2"></i>Добавить страницу
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
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Всего страниц</div>
                        <div class="stat-value">{{ $statistics['total'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-6">
            <div class="stat-card stat-card-success stat-card-compact">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
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
                        <i class="fas fa-eye-slash"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Неактивные</div>
                        <div class="stat-value">{{ $statistics['inactive'] }}</div>
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
                <li class="nav-item"><a class="nav-link" href="#inactive" data-toggle="tab" id="filterInactive">Неактивные</a></li>
            </ul>
        </div>
        <div class="card-body-modern">
            <div class="table-responsive">
                <table id="pages-table" class="table table-hover modern-table">
                    <thead>
                    <tr>
                        <th style="width: 60px" class="text-center">ID</th>
                        <th>Название</th>
                        <th>URL адрес (Slug)</th>
                        <th class="text-center">Статус</th>
                        <th class="text-center">Дата создания</th>
                        <th style="width: 120px" class="text-center">Действия</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($pages as $page)
                        <tr>
                            <td class="text-center align-middle">
                                <span class="badge badge-secondary">#{{ $page->id }}</span>
                            </td>
                            <td class="align-middle font-weight-bold">{{ $page->name }}</td>
                            <td class="align-middle">
                                <a href="{{ url($page->slug) }}" target="_blank" class="text-primary">
                                    <i class="fas fa-external-link-alt mr-1" style="font-size: 0.75rem;"></i>{{ $page->slug }}
                                </a>
                            </td>
                            <td class="text-center align-middle">
                                @if(!$page->is_active)
                                    <span class="badge badge-danger badge-modern">Неактивна</span>
                                @else
                                    <span class="badge badge-success badge-modern">Активна</span>
                                @endif
                            </td>
                            <td class="text-center align-middle text-muted" data-order="{{ strtotime($page->created_at) }}">
                                <small>
                                    <i class="far fa-calendar-alt mr-1"></i>
                                    {{ \Carbon\Carbon::parse($page->created_at)->format('d.m.Y') }}
                                    <br>
                                    <i class="far fa-clock mr-1"></i>
                                    {{ \Carbon\Carbon::parse($page->created_at)->format('H:i') }}
                                </small>
                            </td>
                            <td class="text-center align-middle">
                                <div class="action-buttons justify-content-center">
                                    <a href="{{ route('admin.pages.edit', $page) }}" 
                                       class="btn btn-sm btn-primary" 
                                       title="Редактировать"
                                       data-toggle="tooltip">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <button class="btn btn-sm btn-danger" 
                                            data-toggle="modal" 
                                            data-target="#deleteModal{{ $page->id }}"
                                            title="Удалить"
                                            data-toggle-tooltip="tooltip">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>

                                <div class="modal fade" id="deleteModal{{ $page->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered" role="document">
                                        <div class="modal-content modal-modern">
                                            <div class="modal-header modal-header-modern bg-danger text-white">
                                                <h5 class="modal-title">Подтверждение удаления</h5>
                                                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                            </div>
                                            <div class="modal-body modal-body-modern text-left">
                                                Вы уверены, что хотите удалить страницу <strong>{{ $page->name }}</strong>? Это действие нельзя отменить.
                                            </div>
                                            <div class="modal-footer modal-footer-modern">
                                                <form action="{{ route('admin.pages.destroy', $page) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-modern">Удалить</button>
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
            var table = $('#pages-table').DataTable({
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
                table.column(3).search('Активна').draw();
            });

            $('#filterInactive').on('click', function() {
                table.column(3).search('Неактивна').draw();
            });

            $('[data-toggle="tooltip"]').tooltip();
            $('[data-toggle-tooltip="tooltip"]').tooltip();
        });
    </script>
@endsection
