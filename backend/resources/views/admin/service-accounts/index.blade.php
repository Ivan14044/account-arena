@extends('adminlte::page')

@section('title', 'Товары')

@section('content_header')
    <h1>Товары</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-12">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @if(session('export_success'))
                <div class="alert alert-success">{{ session('export_success') }}</div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Список товаров</h3>
                    <a href="{{ route('admin.service-accounts.create') }}" class="btn btn-primary float-right">+ Добавить</a>
                </div>
                <div class="card-body">
                    <table id="service-accounts-table" class="table table-bordered table-striped">
                        <thead>
                        <tr>
                            <th style="width: 40px">ID</th>
                            <th style="width: 80px">Изображение</th>
                            <th>Название</th>
                            <th>Цена</th>
                            <th>Количество</th>
                            <th>Продано</th>
                            <th>Статус</th>
                            <th>Дата создания</th>
                            <th style="width: 120px">Действия</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($serviceAccounts as $serviceAccount)
                            @php
                                $totalQuantity = is_array($serviceAccount->accounts_data) ? count($serviceAccount->accounts_data) : 0;
                                $soldCount = $serviceAccount->used ?? 0;
                                $availableCount = max(0, $totalQuantity - $soldCount);
                            @endphp
                            <tr>
                                <td>{{ $serviceAccount->id }}</td>
                                <td>
                                    @if($serviceAccount->image_url)
                                        <img src="{{ $serviceAccount->image_url }}" alt="{{ $serviceAccount->title }}" class="img-thumbnail" style="max-width: 60px; max-height: 60px; object-fit: cover;">
                                    @else
                                        <div class="bg-light d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                            <i class="fas fa-image text-muted"></i>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $serviceAccount->title ?: 'Без названия' }}</strong><br>
                                    @if($serviceAccount->description)
                                        <small class="text-muted">{{ Str::limit(strip_tags($serviceAccount->description), 50) }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($serviceAccount->price)
                                        <strong class="text-success">${{ number_format($serviceAccount->price, 2) }}</strong>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-info">{{ $totalQuantity }} шт.</span>
                                    @if($totalQuantity > 0)
                                        <br><small class="text-muted">Доступно: {{ $availableCount }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($soldCount > 0)
                                        <span class="badge badge-warning">{{ $soldCount }} шт.</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td>
                                    @if(!$serviceAccount->is_active)
                                        <span class="badge badge-danger">Неактивен</span>
                                    @elseif($availableCount > 0)
                                        <span class="badge badge-success">Активен</span>
                                    @else
                                        <span class="badge badge-secondary">Нет в наличии</span>
                                    @endif
                                </td>
                                <td data-order="{{ strtotime($serviceAccount->created_at) }}">
                                    {{ \Carbon\Carbon::parse($serviceAccount->created_at)->format('Y-m-d H:i') }}
                                </td>
                                <td>
                                    <a href="{{ route('admin.service-accounts.edit', $serviceAccount) }}"
                                       class="btn btn-sm btn-warning" title="Редактировать">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    @if($totalQuantity > 0)
                                    <button class="btn btn-sm btn-success" 
                                            onclick="exportAccountsFromIndex({{ $serviceAccount->id }}, {{ $totalQuantity }})"
                                            title="Выгрузить товар">
                                        <i class="fas fa-download"></i>
                                    </button>

                                    <button class="btn btn-sm btn-info" data-toggle="modal"
                                            data-target="#importModal{{ $serviceAccount->id }}" title="Загрузить товар">
                                        <i class="fas fa-upload"></i>
                                    </button>
                                    @endif

                                    <button class="btn btn-sm btn-danger" data-toggle="modal"
                                            data-target="#deleteModal{{ $serviceAccount->id }}" title="Удалить">
                                        <i class="fas fa-trash"></i>
                                    </button>

                                    <div class="modal fade" id="deleteModal{{ $serviceAccount->id }}" tabindex="-1"
                                         role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="deleteModalLabel">Подтверждение удаления</h5>
                                                    <button type="button" class="close" data-dismiss="modal"
                                                            aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    Вы уверены, что хотите удалить этот товар?
                                                </div>
                                                <div class="modal-footer">
                                                    <form
                                                        action="{{ route('admin.service-accounts.destroy', $serviceAccount) }}"
                                                        method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger">Да, удалить
                                                        </button>
                                                    </form>
                                                    <button type="button" class="btn btn-secondary"
                                                            data-dismiss="modal">Отмена
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Modal для загрузки товара -->
                                    <div class="modal fade" id="importModal{{ $serviceAccount->id }}" tabindex="-1" role="dialog" aria-labelledby="importModalLabel" aria-hidden="true">
                                        <div class="modal-dialog modal-lg" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Загрузить товар</h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <form action="{{ route('admin.service-accounts.import', $serviceAccount) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-body">
                                                        <div class="form-group">
                                                            <label for="import_data">Данные для загрузки</label>
                                                            <textarea name="import_data" id="import_data{{ $serviceAccount->id }}" class="form-control font-monospace" rows="15" placeholder="Вставьте данные товаров. Каждая строка = один товар" required></textarea>
                                                            <small class="form-text text-muted">
                                                                Каждая строка будет добавлена как один товар. Новые строки будут добавлены к существующим.
                                                            </small>
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="import_count">Количество строк для загрузки:</label>
                                                            <input type="number" id="import_count{{ $serviceAccount->id }}" class="form-control" value="0" readonly>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                                                        <button type="submit" class="btn btn-primary">Сохранить</button>
                                                    </div>
                                                </form>
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
@endsection

@section('js')
    <script>
        $(document).ready(function () {
            $('#service-accounts-table').DataTable({
                "order": [[0, "desc"]],
                "columnDefs": [
                    {"orderable": false, "targets": [1, 8]}
                ]
            });

            // Update count when typing in import modals
            @foreach ($serviceAccounts as $serviceAccount)
                $('#import_data{{ $serviceAccount->id }}').on('input', function() {
                    const lines = this.value.split('\n').filter(line => line.trim() !== '');
                    $('#import_count{{ $serviceAccount->id }}').val(lines.length);
                });
            @endforeach
        });

        // Export function for index page
        function exportAccountsFromIndex(productId, totalQuantity) {
            const countStr = prompt('Сколько товаров выгрузить? (всего: ' + totalQuantity + ')', totalQuantity);
            
            if (countStr === null) return; // User cancelled
            
            const count = parseInt(countStr);
            if (isNaN(count) || count < 1) {
                alert('Введите корректное число');
                return;
            }

            // Show loading
            const btn = event.target.closest('button');
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btn.disabled = true;

            // Create hidden iframe for download
            const iframe = document.createElement('iframe');
            iframe.style.display = 'none';
            iframe.src = '/service-accounts/' + productId + '/export?limit=' + count;
            document.body.appendChild(iframe);

            // After download completes, reload page
            setTimeout(function() {
                document.body.removeChild(iframe);
                window.location.reload();
            }, 2000);
        }
    </script>
@endsection
