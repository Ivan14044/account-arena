@extends('adminlte::page')

@section('title', 'Чат поддержки')

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="m-0 font-weight-light">
                    <i class="fas fa-comments mr-2"></i>Чат поддержки
                </h1>
                <p class="text-muted mb-0 mt-1">Управление чатами поддержки</p>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="card card-modern">
        <div class="card-header">
            <h5 class="mb-0">Список чатов</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Пользователь</th>
                            <th>Email</th>
                            <th>Статус</th>
                            <th>Назначен</th>
                            <th>Последнее сообщение</th>
                            <th>Оценка</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($chats as $chat)
                            <tr>
                                <td>#{{ $chat->id }}</td>
                                <td>
                                    @if($chat->user)
                                        {{ $chat->user->name }}
                                    @else
                                        <span class="text-muted">{{ $chat->guest_name ?? 'Гость' }}</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $chat->user ? $chat->user->email : $chat->guest_email }}
                                </td>
                                <td>
                                    <span class="badge badge-{{ $chat->status === 'open' ? 'success' : ($chat->status === 'closed' ? 'secondary' : 'warning') }}">
                                        {{ $chat->status === 'open' ? 'Открыт' : ($chat->status === 'closed' ? 'Закрыт' : 'В ожидании') }}
                                    </span>
                                </td>
                                <td>
                                    {{ $chat->assignedAdmin ? $chat->assignedAdmin->name : 'Не назначен' }}
                                </td>
                                <td>
                                    @if($chat->last_message_at)
                                        {{ $chat->last_message_at->diffForHumans() }}
                                    @else
                                        <span class="text-muted">Нет сообщений</span>
                                    @endif
                                </td>
                                <td>
                                    @if($chat->rating)
                                        <div class="d-flex align-items-center">
                                            @for($i = 1; $i <= 5; $i++)
                                                <i class="fas fa-star {{ $i <= $chat->rating ? 'text-warning' : 'text-muted' }}" style="font-size: 14px;"></i>
                                            @endfor
                                            @if($chat->rating_comment)
                                                <span class="ml-2" title="{{ $chat->rating_comment }}">
                                                    <i class="fas fa-comment text-info" style="font-size: 12px;"></i>
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.support-chats.show', $chat->id) }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> Открыть
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    Чатов пока нет
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $chats->links() }}
            </div>
        </div>
    </div>
@stop