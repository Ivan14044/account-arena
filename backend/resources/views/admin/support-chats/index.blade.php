@extends('adminlte::page')

@section('title', 'Чат поддержки')

@section('css')
<style>
    .unread-badge {
        background-color: #dc3545 !important;
        color: white !important;
        font-size: 11px !important;
        font-weight: 600 !important;
        padding: 3px 7px !important;
        line-height: 1.2 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        border-radius: 10px !important;
        min-width: 20px !important;
        height: 20px !important;
    }
</style>
@stop

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
    <!-- Вкладки для фильтрации по источнику -->
    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item">
            <a class="nav-link {{ !request()->has('source') || request('source') === '' ? 'active' : '' }}" 
               href="{{ route('admin.support-chats.index') }}">
                <i class="fas fa-list mr-1"></i> Все чаты
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request('source') === 'website' ? 'active' : '' }}" 
               href="{{ route('admin.support-chats.index', ['source' => 'website']) }}">
                <i class="fas fa-globe mr-1"></i> Чаты с сайта
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request('source') === 'telegram' ? 'active' : '' }}" 
               href="{{ route('admin.support-chats.index', ['source' => 'telegram']) }}">
                <i class="fab fa-telegram mr-1"></i> Чаты Telegram
            </a>
        </li>
    </ul>

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
                            <th>Источник</th>
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
                                    @if($chat->isFromTelegram())
                                        {{-- Для Telegram чатов показываем имя и аватарку (БЕЗ никнейма) --}}
                                        <div class="d-flex align-items-center">
                                            @if($chat->telegram_photo)
                                                <img src="{{ asset($chat->telegram_photo) }}" 
                                                     alt="Avatar" 
                                                     class="rounded-circle mr-2" 
                                                     style="width: 32px; height: 32px; object-fit: cover;"
                                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                <div class="rounded-circle mr-2 bg-secondary align-items-center justify-content-center" 
                                                     style="width: 32px; height: 32px; color: white; font-size: 14px; display: none;">
                                                    {{ mb_substr($chat->guest_name ?? 'U', 0, 1) }}
                                                </div>
                                            @else
                                                <div class="rounded-circle mr-2 bg-secondary d-flex align-items-center justify-content-center" 
                                                     style="width: 32px; height: 32px; color: white; font-size: 14px;">
                                                    {{ mb_substr($chat->guest_name ?? 'U', 0, 1) }}
                                                </div>
                                            @endif
                                            <span class="text-muted">
                                                <i class="fab fa-telegram mr-1"></i>
                                                {{ $chat->guest_name ?? 'Telegram User' }}
                                            </span>
                                            @if(($chat->unread_count ?? 0) > 0)
                                                <span class="badge badge-danger ml-2 unread-badge" style="min-width: 20px; border-radius: 10px;">
                                                    {{ $chat->unread_count > 99 ? '99+' : $chat->unread_count }}
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <div class="d-flex align-items-center">
                                            @if($chat->user)
                                                {{ $chat->user->name }}
                                            @else
                                                <span class="text-muted">{{ $chat->guest_name ?? 'Гость' }}</span>
                                            @endif
                                            @if(($chat->unread_count ?? 0) > 0)
                                                <span class="badge badge-danger ml-2 unread-badge" style="min-width: 20px; border-radius: 10px;">
                                                    {{ $chat->unread_count > 99 ? '99+' : $chat->unread_count }}
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if($chat->isFromTelegram())
                                        {{-- Для Telegram чатов не показываем email --}}
                                        <span class="text-muted">—</span>
                                    @else
                                        {{ $chat->user ? $chat->user->email : $chat->guest_email }}
                                    @endif
                                </td>
                                <td>
                                    @if($chat->isFromTelegram())
                                        <span class="badge badge-info">
                                            <i class="fab fa-telegram mr-1"></i> Telegram
                                        </span>
                                    @else
                                        <span class="badge badge-secondary">
                                            <i class="fas fa-globe mr-1"></i> Сайт
                                        </span>
                                    @endif
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
                                <td colspan="9" class="text-center text-muted py-4">
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