@extends('adminlte::page')

@section('title', 'Категории товаров')

@section('content_header')
    <h1>Категории товаров</h1>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Список категорий товаров</h3>
                    <a href="{{ route('admin.product-categories.create') }}" class="btn btn-primary float-right">+ Добавить</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="categories-table" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th style="width: 60px">ID</th>
                                    <th>Название</th>
                                    <th style="width: 120px">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($categories as $category)
                                    <tr>
                                        <td>{{ $category->id }}</td>
                                        <td>{{ $category->admin_name }}</td>
                                        <td>
                                            <a href="{{ route('admin.product-categories.edit', $category) }}" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal{{ $category->id }}">
                                                <i class="fas fa-trash"></i>
                                            </button>

                                            <div class="modal fade" id="deleteModal{{ $category->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Подтверждение удаления</h5>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            Вы уверены, что хотите удалить эту категорию товаров?
                                                        </div>
                                                        <div class="modal-footer">
                                                            <form action="{{ route('admin.product-categories.destroy', $category) }}" method="POST" class="d-inline">
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
            $('#categories-table').DataTable({
                "order": [[0, "asc"]],
                "columnDefs": [
                    { "orderable": false, "targets": 2 }
                ]
            });
        });
    </script>
@endsection

