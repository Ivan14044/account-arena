@extends('adminlte::page')

@section('title', 'Журнал действий')

@section('content_header')
<div class="content-header-modern">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="m-0 text-dark">Журнал действий</h1>
            <p class="text-muted mb-0 mt-1">История активности администраторов</p>
        </div>
    </div>
</div>
@stop

@section('content')
    <div class="card card-modern">
        <div class="card-header border-0">
            <h3 class="card-title"><i class="fas fa-filter mr-2"></i>Фильтры</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
            </div>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.activity-logs.index') }}" method="GET">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Администратор</label>
                            <select name="user_id" class="form-control select2">
                                <option value="">Все</option>
                                @foreach($admins as $admin)
                                    <option value="{{ $admin->id }}" {{ request('user_id') == $admin->id ? 'selected' : '' }}>
                                        {{ $admin->name }} (ID: {{ $admin->id }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Действие</label>
                            <select name="action" class="form-control select2">
                                <option value="">Все</option>
                                @foreach($actions as $act)
                                    <option value="{{ $act }}" {{ request('action') == $act ? 'selected' : '' }}>
                                        {{ $act }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Тип объекта</label>
                            <input type="text" name="model_type" class="form-control" value="{{ request('model_type') }}" placeholder="Например: User">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>ID объекта</label>
                            <input type="text" name="model_id" class="form-control" value="{{ request('model_id') }}" placeholder="ID">
                        </div>
                    </div>
                    <div class="col-12 text-right">
                        <a href="{{ route('admin.activity-logs.index') }}" class="btn btn-default mr-2">Сбросить</a>
                        <button type="submit" class="btn btn-primary">Искать</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-modern">
        <div class="card-body p-0">
            <!-- Desktop Table View -->
            <div class="table-responsive d-none d-md-block">
                <table class="table table-striped table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Дата</th>
                            <th>Админ</th>
                            <th>Действие</th>
                            <th>Объект</th>
                            <th>Детали</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td style="white-space: nowrap;">{{ $log->created_at->format('d.m.Y H:i:s') }}</td>
                                <td>
                                    @if($log->user)
                                        <div class="d-flex align-items-center">
                                            <div class="user-avatar-sm mr-2 bg-primary text-white rounded-circle d-flex justify-content-center align-items-center" style="width: 30px; height: 30px;">
                                                {{ substr($log->user->name, 0, 1) }}
                                            </div>
                                            <div>
                                                <a href="{{ route('admin.users.edit', $log->user_id) }}" class="text-dark font-weight-bold">
                                                    {{ $log->user->name }}
                                                </a>
                                                <small class="d-block text-muted">ID: {{ $log->user_id }}</small>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-muted">System/Deleted</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-info">{{ $log->action }}</span>
                                </td>
                                <td>
                                    @if($log->model_type)
                                        <span class="badge badge-light border">{{ class_basename($log->model_type) }}</span>
                                        <small class="text-muted ml-1">#{{ $log->model_id }}</small>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if(!empty($log->changes))
                                        <button class="btn btn-xs btn-outline-primary" data-toggle="modal" data-target="#modal-log-{{ $log->id }}">
                                            <i class="fas fa-eye mr-1"></i>Просмотр
                                        </button>
                                    @else
                                        <span class="text-muted text-sm">-</span>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-muted">{{ $log->ip }}</small>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="fas fa-search fa-3x mb-3 text-gray-300"></i>
                                    <p class="mb-0">Записей в журнале не найдено</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card View -->
            <div class="d-md-none bg-light">
                @forelse($logs as $log)
                    <div class="card mb-2 shadow-none border-bottom rounded-0">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge badge-info">{{ $log->action }}</span>
                                <small class="text-muted">{{ $log->created_at->format('d.m.Y H:i') }}</small>
                            </div>
                            
                            <div class="mb-2">
                                @if($log->user)
                                    <div class="d-flex align-items-center mb-1">
                                        <i class="fas fa-user-shield text-muted mr-2" style="width: 16px;"></i>
                                        <a href="{{ route('admin.users.edit', $log->user_id) }}" class="text-dark font-weight-bold">
                                            {{ $log->user->name }}
                                        </a>
                                    </div>
                                @else
                                    <div class="text-muted"><i class="fas fa-robot mr-2"></i>System</div>
                                @endif
                                
                                @if($log->model_type)
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-cube text-muted mr-2" style="width: 16px;"></i>
                                        <span>{{ class_basename($log->model_type) }} #{{ $log->model_id }}</span>
                                    </div>
                                @endif
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                                <small class="text-muted"><i class="fas fa-globe mr-1"></i>{{ $log->ip }}</small>
                                
                                @if(!empty($log->changes))
                                    <button class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#modal-log-{{ $log->id }}">
                                        Детали
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-search fa-3x mb-3 text-gray-300"></i>
                        <p class="mb-0">Записей в журнале не найдено</p>
                    </div>
                @endforelse
            </div>
            
            <!-- Shared Modals (moved outside loops to avoid duplication issues if we wanted, but keep inside for simplicity of ID matching) -->
            <!-- Note: Modals are duplicated in HTML but IDs are unique by log ID, so it works. Ideally move modals outside. -->
            @foreach($logs as $log)
                @if(!empty($log->changes))
                    <div class="modal fade" id="modal-log-{{ $log->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Детали изменения #{{ $log->id }}</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body bg-light">
                                    <pre class="mb-0 border rounded p-3 bg-white"><code>{{ json_encode($log->changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Закрыть</button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach

        </div>
        <div class="card-footer d-flex justify-content-center">
            {{ $logs->links('pagination::bootstrap-4') }}
        </div>
    </div>
@endsection

@section('css')
    @include('admin.layouts.modern-styles')
@endsection
