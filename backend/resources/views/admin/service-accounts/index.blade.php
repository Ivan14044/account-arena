@extends('adminlte::page')

@section('title', 'Товары')

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="m-0 font-weight-light">
                    Управление товарами
                </h1>
                <p class="text-muted mb-0 mt-1">Каталог цифровых товаров и сервисов для продажи</p>
            </div>
            <div>
                <a href="{{ route('admin.service-accounts.create') }}" class="btn btn-primary btn-modern">
                    <i class="fas fa-plus mr-2"></i>Добавить товар
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

    @if(session('error'))
        <div class="alert alert-modern alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    @if(session('export_success'))
        <div class="alert alert-modern alert-success alert-dismissible fade show">
            <i class="fas fa-download mr-2"></i>{{ session('export_success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <!-- Статистика -->
    <div class="row mb-4">
        <div class="col-lg-3 col-6">
            <div class="stat-card stat-card-primary">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Всего товаров</div>
                        <div class="stat-value">{{ $serviceAccounts->count() }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="stat-card stat-card-success">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Активные</div>
                        <div class="stat-value">{{ $serviceAccounts->where('is_active', true)->count() }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="stat-card stat-card-warning">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Продано</div>
                        <div class="stat-value">{{ $serviceAccounts->sum('used') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="stat-card stat-card-info">
                <div class="stat-card-body">
                    <div class="stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Общая стоимость</div>
                        <div class="stat-value">${{ number_format($serviceAccounts->sum('price'), 0) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Таблица -->
    <div class="card card-modern">
        <div class="card-header-modern">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Список товаров</h5>
                    <small class="text-muted">Всего записей: {{ $serviceAccounts->count() }}</small>
                </div>
                <div class="filters-container">
                    <div class="btn-group btn-group-filter" role="group">
                        <button type="button" class="btn btn-filter active" id="filterAll">Все</button>
                        <button type="button" class="btn btn-filter" id="filterActive">Активные</button>
                        <button type="button" class="btn btn-filter" id="filterInactive">Неактивные</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body-modern">
            <div class="table-responsive">
                <table id="service-accounts-table" class="table table-hover modern-table">
                    <thead>
                        <tr>
                            <th style="width: 50px" class="text-center">ID</th>
                            <th style="width: 80px" class="text-center">Изображение</th>
                            <th style="min-width: 250px">Товар</th>
                            <th class="text-center">Цена</th>
                            <th class="text-center">В наличии</th>
                            <th class="text-center">Продано</th>
                            <th>Статус</th>
                            <th class="text-center">Дата создания</th>
                            <th style="width: 180px" class="text-center">Действия</th>
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
                            <td class="text-center align-middle">
                                <span class="badge badge-secondary">#{{ $serviceAccount->id }}</span>
                            </td>
                            <td class="text-center align-middle">
                                @if($serviceAccount->image_url)
                                    <img src="{{ $serviceAccount->image_url }}" 
                                         alt="{{ $serviceAccount->title }}" 
                                         class="rounded"
                                         style="width: 60px; height: 60px; object-fit: cover; border: 1px solid #e3e6f0;">
                                @else
                                    <div class="rounded d-flex align-items-center justify-content-center" 
                                         style="width: 60px; height: 60px; background: #f8f9fc; border: 1px solid #e3e6f0;">
                                        <i class="fas fa-image text-muted fa-2x"></i>
                                    </div>
                                @endif
                            </td>
                            <td class="align-middle">
                                <div>
                                    <div class="font-weight-bold text-dark">
                                        {{ $serviceAccount->title ?: 'Без названия' }}
                                    </div>
                                    @if($serviceAccount->description)
                                        <small class="text-muted d-block mt-1">
                                            {{ Str::limit(strip_tags($serviceAccount->description), 60) }}
                                        </small>
                                    @endif
                                </div>
                            </td>
                            <td class="text-center align-middle">
                                @if($serviceAccount->price)
                                    <strong class="text-success" style="font-size: 1.1rem;">
                                        ${{ number_format($serviceAccount->price, 2) }}
                                    </strong>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center align-middle">
                                @if($availableCount > 0)
                                    <span class="badge badge-success badge-modern font-weight-bold" style="font-size: 0.875rem;">
                                        {{ $availableCount }} шт.
                                    </span>
                                @else
                                    <span class="badge badge-secondary badge-modern">
                                        Нет в наличии
                                    </span>
                                @endif
                                @if($totalQuantity > 0)
                                    <div><small class="text-muted">Всего: {{ $totalQuantity }}</small></div>
                                @endif
                            </td>
                            <td class="text-center align-middle">
                                @if($soldCount > 0)
                                    <span class="badge badge-warning badge-modern font-weight-bold" style="font-size: 0.875rem;">
                                        {{ $soldCount }} шт.
                                    </span>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </td>
                            <td class="align-middle">
                                @if(!$serviceAccount->is_active)
                                    <span class="badge badge-danger badge-modern">
                                        <i class="fas fa-ban mr-1"></i>Неактивен
                                    </span>
                                @elseif($availableCount > 0)
                                    <span class="badge badge-success badge-modern">
                                        <i class="fas fa-check-circle mr-1"></i>Активен
                                    </span>
                                @else
                                    <span class="badge badge-secondary badge-modern">
                                        <i class="fas fa-box mr-1"></i>Нет в наличии
                                    </span>
                                @endif
                            </td>
                            <td class="text-center align-middle">
                                <small class="text-muted">
                                    <i class="far fa-calendar-alt mr-1"></i>
                                    {{ \Carbon\Carbon::parse($serviceAccount->created_at)->format('d.m.Y H:i') }}
                                </small>
                            </td>
                            <td class="text-center align-middle">
                                <div class="btn-group action-buttons" role="group">
                                    <a href="{{ route('admin.service-accounts.edit', $serviceAccount) }}"
                                       class="btn btn-sm btn-primary" 
                                       title="Редактировать"
                                       data-toggle="tooltip">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    @if($totalQuantity > 0)
                                    <button class="btn btn-sm btn-success" 
                                            onclick="exportAccountsFromIndex({{ $serviceAccount->id }}, {{ $totalQuantity }})"
                                            title="Экспорт товаров"
                                            data-toggle="tooltip">
                                        <i class="fas fa-download"></i>
                                    </button>

                                    <button class="btn btn-sm btn-info" 
                                            data-toggle="modal"
                                            data-target="#importModal{{ $serviceAccount->id }}" 
                                            title="Импорт товаров"
                                            data-toggle-tooltip="tooltip">
                                        <i class="fas fa-upload"></i>
                                    </button>
                                    @endif

                                    <button class="btn btn-sm btn-danger" 
                                            data-toggle="modal"
                                            data-target="#deleteModal{{ $serviceAccount->id }}" 
                                            title="Удалить"
                                            data-toggle-tooltip="tooltip">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>

                                <!-- Модальное окно удаления -->
                                <div class="modal fade" id="deleteModal{{ $serviceAccount->id }}" tabindex="-1" role="dialog">
                                    <div class="modal-dialog modal-dialog-centered" role="document">
                                        <div class="modal-content modal-modern">
                                            <div class="modal-header-modern">
                                                <h5 class="modal-title">
                                                    <i class="fas fa-exclamation-triangle mr-2 text-danger"></i>
                                                    Подтверждение удаления
                                                </h5>
                                                <button type="button" class="close" data-dismiss="modal">
                                                    <span>&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body-modern text-center">
                                                <i class="fas fa-box-open fa-3x text-danger mb-3"></i>
                                                <h6 class="font-weight-bold">{{ $serviceAccount->title ?: 'Товар' }}</h6>
                                                <p class="text-muted mb-0">В наличии: {{ $availableCount }} шт.</p>
                                                <small class="text-danger">Это действие нельзя отменить!</small>
                                            </div>
                                            <div class="modal-footer-modern justify-content-center">
                                                <form action="{{ route('admin.service-accounts.destroy', $serviceAccount) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-modern">
                                                        <i class="fas fa-trash-alt mr-2"></i>Да, удалить
                                                    </button>
                                                </form>
                                                <button type="button" class="btn btn-secondary btn-modern" data-dismiss="modal">
                                                    Отмена
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Модальное окно импорта -->
                                <div class="modal fade" id="importModal{{ $serviceAccount->id }}" tabindex="-1" role="dialog">
                                    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                                        <div class="modal-content modal-modern">
                                            <div class="modal-header-modern">
                                                <h5 class="modal-title">
                                                    <i class="fas fa-upload mr-2 text-info"></i>
                                                    Импорт товаров
                                                </h5>
                                                <button type="button" class="close" data-dismiss="modal">
                                                    <span>&times;</span>
                                                </button>
                                            </div>
                                            <form action="{{ route('admin.service-accounts.import', $serviceAccount) }}" method="POST">
                                                @csrf
                                                <div class="modal-body-modern">
                                                    <div class="form-group-modern">
                                                        <label for="import_data{{ $serviceAccount->id }}" class="form-label-modern">
                                                            Данные для загрузки
                                                        </label>
                                                        <textarea 
                                                            name="import_data" 
                                                            id="import_data{{ $serviceAccount->id }}" 
                                                            class="form-control form-control-modern font-monospace" 
                                                            rows="15" 
                                                            placeholder="Вставьте данные товаров. Каждая строка = один товар" 
                                                            required></textarea>
                                                        <small class="form-text text-muted">
                                                            <i class="fas fa-info-circle mr-1"></i>
                                                            Каждая строка будет добавлена как один товар. Новые строки будут добавлены к существующим.
                                                        </small>
                                                    </div>
                                                    <div class="form-group-modern mb-0">
                                                        <label for="import_count{{ $serviceAccount->id }}" class="form-label-modern">
                                                            Количество строк для загрузки:
                                                        </label>
                                                        <input 
                                                            type="number" 
                                                            id="import_count{{ $serviceAccount->id }}" 
                                                            class="form-control form-control-modern" 
                                                            value="0" 
                                                            readonly>
                                                    </div>
                                                </div>
                                                <div class="modal-footer-modern">
                                                    <button type="button" class="btn btn-secondary btn-modern" data-dismiss="modal">Отмена</button>
                                                    <button type="submit" class="btn btn-primary btn-modern">
                                                        <i class="fas fa-save mr-2"></i>Сохранить
                                                    </button>
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
@endsection

@section('css')
    @include('admin.layouts.modern-styles')
@endsection

@section('js')
    <script>
        $(document).ready(function () {
            // DataTable
            var table = $('#service-accounts-table').DataTable({
                "order": [[0, "desc"]],
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/ru.json"
                },
                "pageLength": 25,
                "columnDefs": [
                    { "orderable": false, "targets": [1, 8] }
                ]
            });

            // Tooltips
            $('[data-toggle="tooltip"]').tooltip();

            // Фильтры
            $('#filterAll').on('click', function() {
                table.column(6).search('').draw();
                $('.btn-filter').removeClass('active');
                $(this).addClass('active');
            });

            $('#filterActive').on('click', function() {
                table.column(6).search('Активен').draw();
                $('.btn-filter').removeClass('active');
                $(this).addClass('active');
            });

            $('#filterInactive').on('click', function() {
                table.column(6).search('Неактивен').draw();
                $('.btn-filter').removeClass('active');
                $(this).addClass('active');
            });

            // Update count when typing in import modals
            @foreach ($serviceAccounts as $serviceAccount)
                $('#import_data{{ $serviceAccount->id }}').on('input', function() {
                    const lines = this.value.split('\n').filter(line => line.trim() !== '');
                    $('#import_count{{ $serviceAccount->id }}').val(lines.length);
                });
            @endforeach

            // Автоскрытие алертов
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);
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
