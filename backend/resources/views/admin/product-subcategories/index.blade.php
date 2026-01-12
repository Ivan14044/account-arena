@extends('adminlte::page')

@section('title', 'Подкатегории товаров')

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="m-0 font-weight-light">
                    Подкатегории товаров
                </h1>
                <p class="text-muted mb-0 mt-1">Управление вложенными категориями для группировки товаров</p>
            </div>
            <div>
                <a href="{{ route('admin.product-categories.index') }}" class="btn btn-secondary btn-modern mr-2">
                    <i class="fas fa-list mr-2"></i>Категории
                </a>
                <a href="{{ route('admin.product-subcategories.create') }}" class="btn btn-primary btn-modern">
                    <i class="fas fa-plus mr-2"></i>Добавить подкатегорию
                </a>
            </div>
        </div>
    </div>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-modern alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <div class="card card-modern">
        <div class="card-body-modern">
            <div class="table-responsive">
                <table id="subcategories-table" class="table table-hover modern-table">
                    <thead>
                        <tr>
                            <th style="width: 60px" class="text-center">ID</th>
                            <th>Название</th>
                            <th>Родительская категория</th>
                            <th style="width: 120px" class="text-center">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($subcategories as $subcategory)
                            <tr>
                                <td class="text-center align-middle">
                                    <span class="badge badge-light font-weight-bold">#{{ $subcategory->id }}</span>
                                </td>
                                <td class="align-middle font-weight-bold">{{ $subcategory->admin_name }}</td>
                                <td class="align-middle">
                                    @if($subcategory->parent)
                                        <span class="badge badge-info badge-modern px-2 py-1">
                                            <i class="fas fa-folder mr-1"></i>{{ $subcategory->parent->admin_name }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center align-middle">
                                    <div class="action-buttons justify-content-center">
                                        <a href="{{ route('admin.product-subcategories.edit', $subcategory) }}" 
                                           class="btn btn-sm btn-primary" 
                                           title="Редактировать"
                                           data-toggle="tooltip">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        <button class="btn btn-sm btn-danger btn-delete-subcategory" 
                                                data-name="{{ $subcategory->admin_name }}"
                                                data-action="{{ route('admin.product-subcategories.destroy', $subcategory) }}"
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
                    <i class="fas fa-folder-minus fa-3x text-danger mb-3"></i>
                    <h6 class="font-weight-bold" id="delete-subcategory-name"></h6>
                    <p class="mt-3">Вы действительно хотите удалить эту подкатегорию?<br>
                    <small class="text-danger">Это действие нельзя отменить!</small></p>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <form id="delete-subcategory-form" method="POST">
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
            $('#subcategories-table').DataTable({
                "order": [[0, "desc"]],
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Russian.json"
                },
                "pageLength": 25,
                "columnDefs": [
                    { "orderable": false, "targets": 3 }
                ],
                "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip'
            });

            $('[data-toggle="tooltip"]').tooltip();

            // Динамическая модалка
            $('.btn-delete-subcategory').on('click', function() {
                $('#delete-subcategory-name').text($(this).data('name'));
                $('#delete-subcategory-form').attr('action', $(this).data('action'));
                $('#singleDeleteModal').modal('show');
            });
        });
    </script>
@endsection
