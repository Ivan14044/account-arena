@extends('adminlte::page')

@section('title', 'Мои товары')

@section('content_header')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
        <h1 class="mb-2 mb-md-0">Мои товары</h1>
        <div class="d-flex flex-column flex-sm-row gap-2 w-100 w-md-auto">
            <a href="{{ route('supplier.dashboard') }}" class="btn btn-info">
                <i class="fas fa-home"></i> Главная
            </a>
            <a href="{{ route('supplier.logout') }}" class="btn btn-secondary">
                <i class="fas fa-sign-out-alt"></i> Выход
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="card">
                <div class="card-header d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center">
                    <h3 class="card-title mb-2 mb-sm-0">Список товаров</h3>
                    <a href="{{ route('supplier.products.create') }}" class="btn btn-primary">+ Добавить товар</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="products-table" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th style="width: 60px">ID</th>
                                    <th>Название</th>
                                    <th style="width: 100px">Цена</th>
                                    <th style="width: 100px">В наличии</th>
                                    <th style="width: 100px">Продано</th>
                                    <th style="width: 80px">Статус</th>
                                    <th style="width: 150px">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($products as $product)
                                    <tr>
                                        <td>{{ $product->id }}</td>
                                        <td>
                                            {{ $product->title }}
                                            @if($product->category)
                                                <br><small class="text-muted">{{ $product->category->admin_name }}</small>
                                            @endif
                                        </td>
                                        <td>{{ number_format($product->price, 2) }} USD</td>
                                        <td>
                                            @php
                                                $accountsData = $product->accounts_data;
                                                $totalQty = is_array($accountsData) ? count($accountsData) : 0;
                                                $used = $product->used ?? 0;
                                                $available = max(0, $totalQty - $used);
                                            @endphp
                                            {{ $available }} шт.
                                        </td>
                                        <td>{{ $product->used ?? 0 }} шт.</td>
                                        <td>
                                            @if($product->is_active)
                                                <span class="badge badge-success">Активен</span>
                                            @else
                                                <span class="badge badge-secondary">Не активен</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group-vertical btn-group-sm d-sm-inline-flex" role="group">
                                                <a href="{{ route('supplier.products.edit', $product) }}" class="btn btn-warning mb-1 mb-sm-0">
                                                    <i class="fas fa-edit"></i> <span class="d-sm-none">Редактировать</span>
                                                </a>
                                                <button class="btn btn-danger" data-toggle="modal" data-target="#deleteModal{{ $product->id }}">
                                                    <i class="fas fa-trash"></i> <span class="d-sm-none">Удалить</span>
                                                </button>
                                            </div>

                                            <!-- Delete Modal -->
                                            <div class="modal fade" id="deleteModal{{ $product->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Подтверждение удаления</h5>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            Вы уверены, что хотите удалить товар "{{ $product->title }}"?
                                                        </div>
                                                        <div class="modal-footer">
                                                            <form action="{{ route('supplier.products.destroy', $product) }}" method="POST" class="d-inline">
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
            $('#products-table').DataTable({
                "order": [[0, "desc"]],
                "columnDefs": [
                    { "orderable": false, "targets": 6 }
                ]
            });
        });
    </script>
@endsection

