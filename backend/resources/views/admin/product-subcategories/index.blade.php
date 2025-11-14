@extends('adminlte::page')

@section('title', 'Подкатегории товаров')

@section('content_header')
    <h1>Подкатегории товаров</h1>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"></h3>
                    <div class="float-right">
                        <a href="{{ route('admin.product-categories.index') }}" class="btn btn-info mr-2">
                            <i class="fas fa-list"></i> Категории
                        </a>
                        <a href="{{ route('admin.product-subcategories.create') }}" class="btn btn-primary">+ Добавить</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="subcategories-table" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th style="width: 60px">ID</th>
                                    <th>Название</th>
                                    <th>Родительская категория</th>
                                    <th style="width: 120px">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($subcategories as $subcategory)
                                    <tr>
                                        <td>{{ $subcategory->id }}</td>
                                        <td>{{ $subcategory->admin_name }}</td>
                                        <td>
                                            @if($subcategory->parent)
                                                {{ $subcategory->parent->admin_name }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.product-subcategories.edit', $subcategory) }}" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal{{ $subcategory->id }}">
                                                <i class="fas fa-trash"></i>
                                            </button>

                                            <div class="modal fade" id="deleteModal{{ $subcategory->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Подтверждение удаления</h5>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            Вы уверены, что хотите удалить подкатегорию "{{ $subcategory->admin_name }}"?
                                                        </div>
                                                        <div class="modal-footer">
                                                            <form action="{{ route('admin.product-subcategories.destroy', $subcategory) }}" method="POST" class="d-inline">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-danger">Да, удалить</button>
                                                            </form>
                                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
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
        </div>
    </div>
@endsection

@section('js')
    <script>
        $(function () {
            $('#subcategories-table').DataTable({
                "order": [[0, "asc"]],
                "columnDefs": [
                    { "orderable": false, "targets": 3 }
                ]
            });
        });
    </script>
@endsection

