@extends('adminlte::page')

@section('title', 'Чат #' . $chat->id)

@section('css')
    <!-- Custom Emoji Picker Styles -->
@stop

@section('content_header')
    <!-- Telegram-style header -->
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-modern alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    @if (session('telegram_send_error'))
        <div class="alert alert-modern alert-warning alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle mr-2"></i>{{ session('telegram_send_error') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    @if($chat->isFromTelegram())
        {{-- Telegram Layout --}}
        <div class="telegram-layout">
        <!-- Telegram-style Chat Container -->
        <div class="telegram-chat-container">
            <!-- Chat Header -->
            <div class="telegram-header">
            <div class="d-flex align-items-center">
                <a href="{{ route('admin.support-chats.index') }}" class="back-button">
                    <i class="fas fa-arrow-left"></i>
                </a>
                
                @if($chat->isFromTelegram() && $chat->telegram_photo)
                    <img src="{{ asset($chat->telegram_photo) }}" alt="Avatar" class="chat-avatar">
                @else
                    <div class="chat-avatar-placeholder">
                        {{ mb_substr($chat->guest_name ?? $chat->user->name ?? 'U', 0, 1) }}
                    </div>
                @endif
                
                <div class="chat-info">
                    <h5 class="chat-name mb-0">
                        @if($chat->isFromTelegram())
                            {{ $chat->guest_name ?? 'Telegram пользователь' }}
                        @else
                            {{ $chat->user->name ?? $chat->guest_name ?? 'Гость' }}
                        @endif
                    </h5>
                    <p class="chat-status mb-0">
                        <i class="fas fa-circle online-indicator"></i> Онлайн
                    </p>
                </div>
            </div>
            
            <div class="chat-actions">
                <button type="button" class="action-button" id="search-toggle">
                    <i class="fas fa-search"></i>
                </button>
                <button type="button" class="action-button">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
            </div>
        </div>
        
        <!-- Search Bar (hidden by default) -->
        <div class="search-bar" id="search-bar" style="display: none;">
            <input type="text" id="search-messages-input" class="form-control" placeholder="Поиск сообщений...">
            <button type="button" class="btn-close-search" id="close-search">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <!-- Messages Container -->
        <div id="messages-container" class="messages-container">
                    @foreach($chat->messages as $message)
                        <div class="mb-3 message-item" data-message-id="{{ $message->id }}">
                            <div class="d-flex {{ $message->sender_type === 'admin' ? 'justify-content-end' : 'justify-content-start' }}">
                                <div class="message-bubble {{ $message->sender_type === 'admin' ? 'message-admin' : 'message-user' }}" style="max-width: 65%;">
                                    @if($message->sender_type !== 'admin')
                                        {{-- Для пользователей показываем имя только если это первое сообщение в группе --}}
                                        @php
                                            $prevMessage = $loop->index > 0 ? $chat->messages[$loop->index - 1] : null;
                                            $showName = !$prevMessage || $prevMessage->sender_type !== $message->sender_type || 
                                                       $prevMessage->created_at->diffInMinutes($message->created_at) > 5;
                                        @endphp
                                        @if($showName)
                                            <div class="message-sender-name">
                                                {{ $chat->guest_name ?? 'Пользователь' }}
                                            </div>
                                        @endif
                                    @endif
                                    
                                    <div class="message-content">
                                        @if(!empty($message->message))
                                            <div class="message-text">
                                                {{ $message->message }}
                                            </div>
                                        @endif
                                        @if($message->attachments->count() > 0)
                                            <div class="message-attachments mt-2">
                                                @foreach($message->attachments as $attachment)
                                                    <div class="attachment-item mb-2">
                                                        @if($attachment->isImage())
                                                            <img src="{{ $attachment->full_url }}" 
                                                                 alt="{{ $attachment->file_name }}" 
                                                                 class="img-thumbnail image-preview-trigger" 
                                                                 style="max-width: 200px; max-height: 200px; cursor: pointer;"
                                                                 data-image-url="{{ $attachment->full_url }}"
                                                                 data-image-name="{{ $attachment->file_name }}"
                                                                 title="Нажмите для просмотра">
                                                        @else
                                                            <a href="{{ $attachment->full_url }}" target="_blank" class="d-flex align-items-center text-decoration-none" download>
                                                                <i class="fas fa-file mr-2"></i>
                                                                <span>{{ $attachment->file_name }}</span>
                                                                <small class="ml-2">({{ $attachment->formatted_size }})</small>
                                                            </a>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                        
                                        {{-- Time in bottom right corner like in Telegram --}}
                                        <div class="message-time">
                                            {{ $message->created_at->format('H:i') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    <!-- Индикатор печати пользователя -->
                    <div id="user-typing-indicator" style="display: none;" class="mb-3 message-item">
                        <div class="d-flex justify-content-start">
                            <div class="message-bubble message-user" style="max-width: 70%;">
                                <div class="message-header mb-1">
                                    <strong>
                                        @if($chat->user)
                                            {{ $chat->user->name ?? 'Пользователь' }}
                                        @else
                                            {{ $chat->guest_name ?? 'Гость' }}
                                        @endif
                                    </strong>
                                </div>
                                <div class="message-text typing-message">
                                    <span class="typing-indicator">
                                        <span></span>
                                        <span></span>
                                        <span></span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Messages Container -->
            
            <!-- Telegram-style Input Area -->
            <div class="telegram-input-wrapper">
                    <form method="POST" action="{{ route('admin.support-chats.send-message', $chat->id) }}" id="send-message-form" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="_method" value="POST">
                        
                        <!-- Attachments Preview -->
                        <div id="admin-attachments-preview" class="attachments-preview-area"></div>
                        
                        <!-- Input Container -->
                        <div class="input-container">
                            <!-- Attachment Button -->
                            <label for="admin-attachments-input" class="attach-button" title="Прикрепить файл">
                                <i class="fas fa-paperclip"></i>
                            </label>
                            <input type="file" name="attachments[]" id="admin-attachments-input" class="d-none" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar">
                            
                            <!-- Message Input -->
                            <textarea name="message" 
                                      id="admin-message-input" 
                                      class="message-input" 
                                      rows="1" 
                                      placeholder="Напишите сообщение..."></textarea>
                            
                            <!-- Actions Row -->
                            <div class="input-actions">
                                <!-- Emoji Button -->
                                <button type="button" class="emoji-button" id="emoji-button" title="Эмодзи">
                                    <i class="far fa-smile"></i>
                                </button>
                                
                                <!-- Send Button -->
                                <button type="submit" class="send-button" id="send-btn" title="Отправить">
                                    <i class="fas fa-paper-plane" id="send-icon"></i>
                                    <span class="spinner-border spinner-border-sm d-none" id="send-spinner" role="status" aria-hidden="true"></span>
                                </button>
                            </div>
                        </div>
            </form>
            
            <!-- Emoji Picker Container (appears from bottom like in Telegram) -->
            <div id="emoji-picker-container" class="emoji-picker-wrapper" style="display: none;"></div>
            </div>
            <!-- End Telegram Input Wrapper -->
        </div>
        <!-- End Telegram Chat Container -->
    </div>
    <!-- End Telegram Layout -->
    @else
        {{-- Old Layout for non-Telegram chats --}}
        <div class="row">
            <div class="col-md-9">
                <div class="card card-modern">
                    <div class="card-header-modern">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Сообщения</h5>
                            <div class="search-messages-wrapper">
                                <input type="text" id="search-messages-input" class="form-control form-control-sm" placeholder="Поиск по сообщениям..." style="width: 250px;">
                            </div>
                        </div>
                    </div>
                    <div class="card-body" id="messages-container" style="max-height: 600px; overflow-y: auto;">
                        @foreach($chat->messages as $message)
                            <div class="mb-3 message-item" data-message-id="{{ $message->id }}">
                                <div class="d-flex {{ $message->sender_type === 'admin' ? 'justify-content-end' : 'justify-content-start' }}">
                                    <div class="message-bubble {{ $message->sender_type === 'admin' ? 'message-admin' : 'message-user' }}" style="max-width: 70%;">
                                        <div class="message-header mb-1">
                                            <strong>
                                                @if($message->sender_type === 'admin')
                                                    {{ $message->user->name ?? 'Администратор' }}
                                                @elseif($message->sender_type === 'guest')
                                                    {{ $chat->guest_name ?? 'Гость' }}
                                                @else
                                                    {{ $message->user->name ?? 'Пользователь' }}
                                                @endif
                                            </strong>
                                            <span class="text-muted ml-2" style="font-size: 0.85em;">
                                                {{ $message->created_at->format('d.m.Y H:i') }}
                                            </span>
                                        </div>
                                        <div class="message-text">
                                            {{ $message->message }}
                                        </div>
                                        @if($message->attachments->count() > 0)
                                            <div class="message-attachments mt-2">
                                                @foreach($message->attachments as $attachment)
                                                    <div class="attachment-item mb-2">
                                                        @if($attachment->isImage())
                                                            <a href="{{ $attachment->full_url }}" target="_blank" class="d-block">
                                                                <img src="{{ $attachment->full_url }}" alt="{{ $attachment->file_name }}" class="img-thumbnail" style="max-width: 200px; max-height: 200px; cursor: pointer;">
                                                            </a>
                                                        @else
                                                            <a href="{{ $attachment->full_url }}" target="_blank" class="d-flex align-items-center text-decoration-none" download>
                                                                <i class="fas fa-file mr-2"></i>
                                                                <span>{{ $attachment->file_name }}</span>
                                                                <small class="ml-2">({{ $attachment->formatted_size }})</small>
                                                            </a>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <!-- Индикатор печати пользователя -->
                        <div id="user-typing-indicator" style="display: none;" class="mb-3 message-item">
                            <div class="d-flex justify-content-start">
                                <div class="message-bubble message-user" style="max-width: 70%;">
                                    <div class="message-header mb-1">
                                        <strong>
                                            @if($chat->user)
                                                {{ $chat->user->name ?? 'Пользователь' }}
                                            @else
                                                {{ $chat->guest_name ?? 'Гость' }}
                                            @endif
                                        </strong>
                                    </div>
                                    <div class="message-text typing-message">
                                        <span class="typing-indicator">
                                            <span></span>
                                            <span></span>
                                            <span></span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Форма отправки сообщения -->
                <div class="card card-modern mt-3">
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.support-chats.send-message', $chat->id) }}" id="send-message-form" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group">
                                <label>Ваше сообщение</label>
                                <textarea name="message" id="admin-message-input" class="form-control" rows="3" placeholder="Введите сообщение..."></textarea>
                            </div>
                            <div class="form-group">
                                <label>Вложения (до 5 файлов, макс. 10MB каждый)</label>
                                <input type="file" name="attachments[]" id="admin-attachments-input" class="form-control-file" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar">
                                <small class="form-text text-muted">Поддерживаемые форматы: изображения, PDF, DOC, DOCX, XLS, XLSX, TXT, ZIP, RAR</small>
                                <div id="admin-attachments-preview" style="margin-top: 10px;"></div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Отправить
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-md-3">
                <div class="card card-modern">
                    <div class="card-header-modern">
                        <h5 class="mb-0">Информация</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>Статус:</strong><br>
                            <span class="badge badge-{{ $chat->status === 'open' ? 'success' : ($chat->status === 'closed' ? 'secondary' : 'warning') }}">
                                {{ $chat->status === 'open' ? 'Открыт' : ($chat->status === 'closed' ? 'Закрыт' : 'В ожидании') }}
                            </span>
                        </div>

                        <form method="POST" action="{{ route('admin.support-chats.update-status', $chat->id) }}" class="mb-3">
                            @csrf
                            <div class="form-group">
                                <label>Изменить статус</label>
                                <select name="status" class="form-control">
                                    <option value="open" {{ $chat->status === 'open' ? 'selected' : '' }}>Открыт</option>
                                    <option value="pending" {{ $chat->status === 'pending' ? 'selected' : '' }}>В ожидании</option>
                                    <option value="closed" {{ $chat->status === 'closed' ? 'selected' : '' }}>Закрыт</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary">Обновить</button>
                        </form>
                        
                        @if($chat->rating)
                            <div class="mb-3">
                                <strong>Рейтинг:</strong><br>
                                @for($i = 1; $i <= 5; $i++)
                                    <i class="fas fa-star {{ $i <= $chat->rating ? 'text-warning' : 'text-muted' }}"></i>
                                @endfor
                                @if($chat->rating_comment)
                                    <br><small class="text-muted mt-1 d-block">{{ $chat->rating_comment }}</small>
                                @endif
                            </div>
                        @endif

                        <div class="mb-3">
                            <strong>Создан:</strong><br>
                            {{ $chat->created_at->format('d.m.Y H:i') }}
                        </div>

                        @if($chat->last_message_at)
                            <div class="mb-3">
                                <strong>Последнее сообщение:</strong><br>
                                {{ $chat->last_message_at->format('d.m.Y H:i') }}
                            </div>
                        @endif
                        
                        <hr>
                        <h6 class="mb-3">Внутренние заметки</h6>
                        <div class="mb-3" style="max-height: 200px; overflow-y: auto;">
                            @forelse($chat->notes as $note)
                                <div class="alert alert-info alert-sm mb-2 p-2">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <small class="font-weight-bold">{{ $note->user->name ?? 'Администратор' }}</small>
                                            <br>
                                            <small>{{ $note->note }}</small>
                                        </div>
                                        <form method="POST" action="{{ route('admin.support-chats.delete-note', [$chat->id, $note->id]) }}" class="d-inline" onsubmit="return confirm('Удалить заметку?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-link text-danger p-0 ml-2" title="Удалить">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    </div>
                                    <small class="text-muted">{{ $note->created_at->format('d.m.Y H:i') }}</small>
                                </div>
                            @empty
                                <small class="text-muted">Заметок пока нет</small>
                            @endforelse
                        </div>
                        <form method="POST" action="{{ route('admin.support-chats.add-note', $chat->id) }}">
                            @csrf
                            <div class="form-group mb-0">
                                <textarea name="note" class="form-control form-control-sm" rows="2" placeholder="Добавить заметку..." required></textarea>
                                <button type="submit" class="btn btn-sm btn-secondary mt-2">Добавить</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
    
    {{-- JavaScript для старого layout (только для не-Telegram чатов) --}}
    @if(!$chat->isFromTelegram())
        <script>
        (function() {
            const messageInput = document.getElementById('admin-message-input');
            const typingIndicator = document.getElementById('user-typing-indicator');
            const messagesContainer = document.getElementById('messages-container');
            let typingTimeout = null;
            let typingThrottleTimeout = null;
            let typingCheckInterval = null;
            let messagesPollInterval = null;
            
            function getLastMessageId() {
                const messageItems = messagesContainer.querySelectorAll('.message-item[data-message-id]');
                if (messageItems.length === 0) return 0;
                const lastItem = messageItems[messageItems.length - 1];
                return parseInt(lastItem.getAttribute('data-message-id')) || 0;
            }
            
            function formatDateTime(dateString) {
                const date = new Date(dateString);
                const day = String(date.getDate()).padStart(2, '0');
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const year = date.getFullYear();
                const hours = String(date.getHours()).padStart(2, '0');
                const minutes = String(date.getMinutes()).padStart(2, '0');
                return `${day}.${month}.${year} ${hours}:${minutes}`;
            }
            
            function formatFileSize(bytes) {
                if (!bytes) return '0 B';
                const units = ['B', 'KB', 'MB', 'GB'];
                let size = bytes;
                let unit = 0;
                while (size >= 1024 && unit < units.length - 1) {
                    size /= 1024;
                    unit++;
                }
                return `${size.toFixed(2)} ${units[unit]}`;
            }
            
            function isImage(mimeType) {
                return ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'].includes(mimeType);
            }
            
            function addMessageToDOM(message, chat) {
                const messageDiv = document.createElement('div');
                messageDiv.className = 'mb-3 message-item';
                messageDiv.setAttribute('data-message-id', message.id);
                
                const isAdmin = message.sender_type === 'admin';
                let senderName;
                if (isAdmin) {
                    senderName = (message.user && message.user.name) ? message.user.name : 'Администратор';
                } else {
                    if (chat.user) {
                        senderName = (message.user && message.user.name) ? message.user.name : 'Пользователь';
                    } else {
                        senderName = chat.guest_name || 'Гость';
                    }
                }
                
                let attachmentsHtml = '';
                if (message.attachments && message.attachments.length > 0) {
                    attachmentsHtml = '<div class="message-attachments mt-2">';
                    message.attachments.forEach(function(attachment) {
                        if (isImage(attachment.mime_type)) {
                            attachmentsHtml += `
                                <div class="attachment-item mb-2">
                                    <a href="${attachment.file_url}" target="_blank" class="d-block">
                                        <img src="${attachment.file_url}" alt="${attachment.file_name}" class="img-thumbnail" style="max-width: 200px; max-height: 200px; cursor: pointer;">
                                    </a>
                                </div>
                            `;
                        } else {
                            attachmentsHtml += `
                                <div class="attachment-item mb-2">
                                    <a href="${attachment.file_url}" target="_blank" class="d-flex align-items-center text-decoration-none" download>
                                        <i class="fas fa-file mr-2"></i>
                                        <span>${attachment.file_name}</span>
                                        <small class="ml-2">(${formatFileSize(attachment.file_size)})</small>
                                    </a>
                                </div>
                            `;
                        }
                    });
                    attachmentsHtml += '</div>';
                }
                
                messageDiv.innerHTML = `
                    <div class="d-flex ${isAdmin ? 'justify-content-end' : 'justify-content-start'}">
                        <div class="message-bubble ${isAdmin ? 'message-admin' : 'message-user'}" style="max-width: 70%;">
                            <div class="message-header mb-1">
                                <strong>${senderName}</strong>
                                <span class="text-muted ml-2" style="font-size: 0.85em;">
                                    ${formatDateTime(message.created_at)}
                                </span>
                            </div>
                            <div class="message-text">
                                ${message.message}
                            </div>
                            ${attachmentsHtml}
                        </div>
                    </div>
                `;
                
                const typingIndicator = document.getElementById('user-typing-indicator');
                if (typingIndicator) {
                    messagesContainer.insertBefore(messageDiv, typingIndicator);
                } else {
                    messagesContainer.appendChild(messageDiv);
                }
            }
            
            function loadNewMessages() {
                const lastMessageId = getLastMessageId();
                
                $.ajax({
                    url: '/admin/support-chats/{{ $chat->id }}/messages',
                    method: 'GET',
                    data: { last_message_id: lastMessageId },
                    success: function(data) {
                        if (data.success && data.messages && data.messages.length > 0) {
                            const wasAtBottom = messagesContainer.scrollHeight - messagesContainer.scrollTop <= messagesContainer.clientHeight + 50;
                            const chatData = {
                                user: @json($chat->user),
                                guest_name: @json($chat->guest_name)
                            };
                            
                            data.messages.forEach(function(message) {
                                addMessageToDOM(message, chatData);
                            });
                            
                            if (wasAtBottom) {
                                setTimeout(function() {
                                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                                }, 100);
                            }
                        }
                    },
                    error: function() {}
                });
            }
            
            messagesPollInterval = setInterval(loadNewMessages, 3000);
            
            function sendTyping() {
                if (!messageInput.value.trim()) {
                    sendStopTyping();
                    return;
                }
                
                if (typingThrottleTimeout) {
                    return;
                }
                
                $.ajax({
                    url: '/admin/support-chats/{{ $chat->id }}/typing',
                    method: 'POST',
                    data: { _token: '{{ csrf_token() }}' },
                    error: function() {}
                });
                
                typingThrottleTimeout = setTimeout(function() {
                    typingThrottleTimeout = null;
                }, 2000);
                
                clearTimeout(typingTimeout);
                typingTimeout = setTimeout(function() {
                    sendStopTyping();
                }, 3000);
            }
            
            function sendStopTyping() {
                if (typingThrottleTimeout) {
                    clearTimeout(typingThrottleTimeout);
                    typingThrottleTimeout = null;
                }
                
                $.ajax({
                    url: '/admin/support-chats/{{ $chat->id }}/typing/stop',
                    method: 'POST',
                    data: { _token: '{{ csrf_token() }}' },
                    error: function() {}
                });
            }
            
            if (messageInput) {
                messageInput.addEventListener('input', sendTyping);
                messageInput.addEventListener('keydown', sendTyping);
            }
            
            function checkUserTyping() {
                $.ajax({
                    url: '/admin/support-chats/{{ $chat->id }}/typing/user-status',
                    method: 'GET',
                    success: function(data) {
                        if (data.is_typing) {
                            typingIndicator.style.display = 'block';
                            if (messagesContainer) {
                                setTimeout(function() {
                                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                                }, 100);
                            }
                        } else {
                            typingIndicator.style.display = 'none';
                        }
                    },
                    error: function() {}
                });
            }
            
            typingCheckInterval = setInterval(checkUserTyping, 2000);
            
            document.getElementById('send-message-form').addEventListener('submit', function() {
                sendStopTyping();
            });
            
            window.addEventListener('beforeunload', function() {
                sendStopTyping();
                if (typingTimeout) clearTimeout(typingTimeout);
                if (typingThrottleTimeout) clearTimeout(typingThrottleTimeout);
                if (typingCheckInterval) clearInterval(typingCheckInterval);
                if (messagesPollInterval) clearInterval(messagesPollInterval);
            });
            
            const attachmentsInput = document.getElementById('admin-attachments-input');
            const attachmentsPreview = document.getElementById('admin-attachments-preview');
            
            if (attachmentsInput) {
                attachmentsInput.addEventListener('change', function() {
                    attachmentsPreview.innerHTML = '';
                    if (this.files.length > 0) {
                        const fileList = document.createElement('div');
                        fileList.className = 'list-group';
                        Array.from(this.files).forEach(function(file) {
                            const fileItem = document.createElement('div');
                            fileItem.className = 'list-group-item d-flex justify-content-between align-items-center';
                            fileItem.innerHTML = '<span><i class="fas fa-file mr-2"></i>' + file.name + '</span><span class="badge badge-secondary">' + (file.size / 1024 / 1024).toFixed(2) + ' MB</span>';
                            fileList.appendChild(fileItem);
                        });
                        attachmentsPreview.appendChild(fileList);
                    }
                });
            }
            
            const searchInput = document.getElementById('search-messages-input');
            const allMessages = Array.from(messagesContainer.querySelectorAll('.message-bubble'));
            
            if (searchInput && messagesContainer) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase().trim();
                    
                    if (searchTerm === '') {
                        allMessages.forEach(function(messageEl) {
                            const messageWrapper = messageEl.closest('.mb-3');
                            if (messageWrapper) {
                                messageWrapper.style.display = '';
                            }
                        });
                        return;
                    }
                    
                    let hasResults = false;
                    allMessages.forEach(function(messageEl) {
                        const messageWrapper = messageEl.closest('.mb-3');
                        if (!messageWrapper) return;
                        
                        const messageText = messageEl.querySelector('.message-text');
                        if (!messageText) return;
                        
                        const text = messageText.textContent.toLowerCase();
                        if (text.includes(searchTerm)) {
                            messageWrapper.style.display = '';
                            hasResults = true;
                        } else {
                            messageWrapper.style.display = 'none';
                        }
                    });
                });
            }
        })();
        </script>
    @endif
    
    {{-- JavaScript для Telegram layout --}}
    @if($chat->isFromTelegram())
        <script>
                    (function() {
                        const DEBUG_MODE = true; // Enable for debugging (set to true to see logs)
                        
                        function logError(message, error) {
                            if (DEBUG_MODE) console.error('[Chat]', message, error);
                        }
                        
                        function logInfo(message, data) {
                            if (DEBUG_MODE) console.log('[Chat]', message, data || '');
                        }
                        
                        // Перехватываем все AJAX запросы
                        const originalAjax = window.jQuery ? window.jQuery.ajax : null;
                        if (window.jQuery && originalAjax) {
                            const originalAjaxFunction = window.jQuery.ajax;
                            window.jQuery.ajax = function(options) {
                                const url = options.url || '';
                                const method = options.method || options.type || 'GET';
                                
                                // Логируем только важные запросы (не системные)
                                const isImportantRequest = url.includes('/support-chats/') || 
                                                          url.includes('/admin/support-chats/');
                                
                                
                                // Добавляем обработчики успеха и ошибки
                                const originalSuccess = options.success;
                                const originalError = options.error;
                                
                                options.success = function(data, textStatus, jqXHR) {
                                    // Проверяем, что ответ действительно JSON
                                    let parsedData = data;
                                    const isSystemRequest = url.includes('/admin/notifications/') || 
                                                          url.includes('/admin/disputes/unread-count') ||
                                                          url.includes('updateNotification') ||
                                                          url.includes('updateDisputesBadge');
                                    
                                    try {
                                        if (typeof data === 'string') {
                                            parsedData = JSON.parse(data);
                                        }
                                    } catch (e) {
                                        // Если не JSON, но статус 200 - это может быть HTML (ошибка MadelineProto)
                                        if (jqXHR.status === 200 && data && typeof data === 'string' && data.trim().startsWith('<')) {
                                            // Логируем только для важных запросов, системные игнорируем
                                            if (isImportantRequest && !isSystemRequest) {
                                            }
                                        }
                                    }
                                    
                                    if (isImportantRequest && !isSystemRequest) {
                                    }
                                    
                                    if (originalSuccess) {
                                        originalSuccess.apply(this, arguments);
                                    }
                                };
                                
                                options.error = function(jqXHR, textStatus, errorThrown) {
                                    // Игнорируем ошибки парсинга JSON для системных запросов
                                    const isSystemRequest = url.includes('/admin/notifications/') || 
                                                          url.includes('/admin/disputes/unread-count') ||
                                                          url.includes('updateNotification') ||
                                                          url.includes('updateDisputesBadge');
                                    
                                    // Если это системный запрос и ошибка парсинга JSON (возможно HTML ответ от MadelineProto)
                                    if (isSystemRequest && textStatus === 'parsererror' && jqXHR.status === 200) {
                                        // Тихо игнорируем - это известная проблема с MadelineProto
                                        if (originalError) {
                                            originalError.apply(this, arguments);
                                        }
                                        return;
                                    }
                                    
                                    // Логируем только реальные ошибки
                                    if (!isSystemRequest || (jqXHR.status !== 200 && jqXHR.status !== 0)) {
                                        logError('AJAX ERROR', `${method} ${url}`, {
                                            status: jqXHR.status,
                                            statusText: jqXHR.statusText,
                                            error: errorThrown,
                                            response_preview: jqXHR.responseText ? jqXHR.responseText.substring(0, 200) : null
                                        });
                                    }
                                    
                                    if (originalError) {
                                        originalError.apply(this, arguments);
                                    }
                                };
                                
                                return originalAjaxFunction.apply(this, arguments);
                            };
                        }
                        
                        // Перехватываем fetch запросы (только для важных запросов)
                        const originalFetch = window.fetch;
                        window.fetch = function(...args) {
                            const url = args[0];
                            const options = args[1] || {};
                            const method = options.method || 'GET';
                            
                            const isImportantRequest = url.includes('/support-chats/') || 
                                                      url.includes('/admin/support-chats/');
                            
                            return originalFetch.apply(this, args);
                        };
                        
                        
                        // ============================================
                        // ОСНОВНОЙ КОД ЧАТА
                        // ============================================
                        const messageInput = document.getElementById('admin-message-input');
                        const typingIndicator = document.getElementById('user-typing-indicator');
                        const messagesContainer = document.getElementById('messages-container');
                        let typingTimeout = null;
                        let typingThrottleTimeout = null;
                        let typingCheckInterval = null;
                        let messagesPollInterval = null;
                        
                        // Получаем ID последнего сообщения
                        function getLastMessageId() {
                            const messageItems = messagesContainer.querySelectorAll('.message-item[data-message-id]');
                            if (messageItems.length === 0) return 0;
                            const lastItem = messageItems[messageItems.length - 1];
                            return parseInt(lastItem.getAttribute('data-message-id')) || 0;
                        }
                        
                        // Функция для форматирования даты
                        function formatDateTime(dateString) {
                            const date = new Date(dateString);
                            const day = String(date.getDate()).padStart(2, '0');
                            const month = String(date.getMonth() + 1).padStart(2, '0');
                            const year = date.getFullYear();
                            const hours = String(date.getHours()).padStart(2, '0');
                            const minutes = String(date.getMinutes()).padStart(2, '0');
                            return `${day}.${month}.${year} ${hours}:${minutes}`;
                        }
                        
                        // Функция для форматирования размера файла
                        function formatFileSize(bytes) {
                            if (!bytes) return '0 B';
                            const units = ['B', 'KB', 'MB', 'GB'];
                            let size = bytes;
                            let unit = 0;
                            while (size >= 1024 && unit < units.length - 1) {
                                size /= 1024;
                                unit++;
                            }
                            return `${size.toFixed(2)} ${units[unit]}`;
                        }
                        
                        // Функция для проверки, является ли файл изображением
                        function isImage(mimeType) {
                            return ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'].includes(mimeType);
                        }
                        
                        // Добавление нового сообщения в DOM
                        function addMessageToDOM(message, chat) {
                            const messageDiv = document.createElement('div');
                            messageDiv.className = 'mb-3 message-item';
                            messageDiv.setAttribute('data-message-id', message.id);
                            
                            const isAdmin = message.sender_type === 'admin';
                            let senderName;
                            if (isAdmin) {
                                senderName = (message.user && message.user.name) ? message.user.name : 'Администратор';
                            } else {
                                // Для Telegram чатов скрываем username
                                if (chat.source === 'telegram') {
                                    senderName = '<i class="fab fa-telegram mr-1"></i> Telegram User';
                                } else if (chat.user) {
                                    senderName = (message.user && message.user.name) ? message.user.name : 'Пользователь';
                                } else {
                                    senderName = chat.guest_name || 'Гость';
                                }
                            }
                            
                            let attachmentsHtml = '';
                            if (message.attachments && message.attachments.length > 0) {
                                attachmentsHtml = '<div class="message-attachments mt-2">';
                                message.attachments.forEach(function(attachment) {
                                    if (isImage(attachment.mime_type)) {
                                        attachmentsHtml += `
                                            <div class="attachment-item mb-2">
                                                <img src="${attachment.file_url}" 
                                                     alt="${attachment.file_name}" 
                                                     class="img-thumbnail image-preview-trigger" 
                                                     style="max-width: 200px; max-height: 200px; cursor: pointer;"
                                                     data-image-url="${attachment.file_url}"
                                                     data-image-name="${attachment.file_name}"
                                                     title="Нажмите для просмотра">
                                            </div>
                                        `;
                                    } else {
                                        attachmentsHtml += `
                                            <div class="attachment-item mb-2">
                                                <a href="${attachment.file_url}" target="_blank" class="d-flex align-items-center text-decoration-none" download>
                                                    <i class="fas fa-file mr-2"></i>
                                                    <span>${attachment.file_name}</span>
                                                    <small class="ml-2">(${formatFileSize(attachment.file_size)})</small>
                                                </a>
                                            </div>
                                        `;
                                    }
                                });
                                attachmentsHtml += '</div>';
                            }
                            
                            const messageDate = new Date(message.created_at);
                            const timeStr = String(messageDate.getHours()).padStart(2, '0') + ':' + 
                                          String(messageDate.getMinutes()).padStart(2, '0');
                            
                            let senderNameHtml = '';
                            if (!isAdmin) {
                                senderNameHtml = `<div class="message-sender-name">${chat.guest_name || 'Пользователь'}</div>`;
                            }
                            
                            let messageTextHtml = '';
                            if (message.message && message.message.trim()) {
                                messageTextHtml = `<div class="message-text">${message.message}</div>`;
                            }
                            
                            messageDiv.innerHTML = `
                                <div class="d-flex ${isAdmin ? 'justify-content-end' : 'justify-content-start'}">
                                    <div class="message-bubble ${isAdmin ? 'message-admin' : 'message-user'}" style="max-width: 65%;">
                                        ${senderNameHtml}
                                        <div class="message-content">
                                            ${messageTextHtml}
                                            ${attachmentsHtml}
                                            <div class="message-time">${timeStr}</div>
                                        </div>
                                    </div>
                                </div>
                            `;
                            
                            // Insert message in correct position (chronological order)
                            const typingIndicator = document.getElementById('user-typing-indicator');
                            const existingMessages = Array.from(messagesContainer.querySelectorAll('.message-item[data-message-id]'));
                            
                            // Find correct position to insert (after messages with smaller ID)
                            let insertBefore = typingIndicator;
                            for (let i = existingMessages.length - 1; i >= 0; i--) {
                                const existingId = parseInt(existingMessages[i].getAttribute('data-message-id'));
                                if (existingId < message.id) {
                                    insertBefore = existingMessages[i].nextSibling || typingIndicator;
                                    break;
                                }
                            }
                            
                            if (insertBefore) {
                                messagesContainer.insertBefore(messageDiv, insertBefore);
                            } else {
                                messagesContainer.appendChild(messageDiv);
                            }
                        }
                        
                        // Load new messages
                        function loadNewMessages() {
                            const lastMessageId = getLastMessageId();
                            
                            $.ajax({
                                url: '/admin/support-chats/{{ $chat->id }}/messages',
                                method: 'GET',
                                data: {
                                    last_message_id: lastMessageId
                                },
                                success: function(data) {
                                    if (data.success && data.messages && data.messages.length > 0) {
                                        const wasAtBottom = messagesContainer.scrollHeight - messagesContainer.scrollTop <= messagesContainer.clientHeight + 50;
                                        
                                        const chatData = {
                                            user: @json($chat->user),
                                            guest_name: @json($chat->guest_name),
                                            source: @json($chat->source)
                                        };
                                        
                                        // Add messages in order (they come sorted from server)
                                        data.messages.forEach(function(message) {
                                            // Check if message already exists
                                            const existing = messagesContainer.querySelector(`[data-message-id="${message.id}"]`);
                                            if (!existing) {
                                                addMessageToDOM(message, chatData);
                                            }
                                        });
                                        
                                        if (wasAtBottom) {
                                            setTimeout(function() {
                                                messagesContainer.scrollTop = messagesContainer.scrollHeight;
                                            }, 100);
                                        }
                                    }
                                },
                                error: function(jqXHR, textStatus, errorThrown) {
                                    logError('Polling error', { status: jqXHR.status, error: errorThrown });
                                }
                            });
                        }
                        
                        messagesPollInterval = setInterval(loadNewMessages, 3000);
                        
                        function sendTyping() {
                            if (!messageInput.value.trim()) {
                                sendStopTyping();
                                return;
                            }
                            
                            // Throttle: отправляем событие не чаще чем раз в 2 секунды
                            if (typingThrottleTimeout) {
                                return;
                            }
                            
                            $.ajax({
                                url: '/admin/support-chats/{{ $chat->id }}/typing',
                                method: 'POST',
                                data: { _token: '{{ csrf_token() }}' },
                                error: function() {}
                            });
                            
                            // Устанавливаем throttle на 2 секунды
                            typingThrottleTimeout = setTimeout(function() {
                                typingThrottleTimeout = null;
                            }, 2000);
                            
                            // Автоматически останавливаем через 3 секунды бездействия
                            clearTimeout(typingTimeout);
                            typingTimeout = setTimeout(function() {
                                sendStopTyping();
                            }, 3000);
                        }
                        
                        function sendStopTyping() {
                            // Очищаем throttle timeout
                            if (typingThrottleTimeout) {
                                clearTimeout(typingThrottleTimeout);
                                typingThrottleTimeout = null;
                            }
                            
                            $.ajax({
                                url: '/admin/support-chats/{{ $chat->id }}/typing/stop',
                                method: 'POST',
                                data: { _token: '{{ csrf_token() }}' },
                                error: function() {}
                            });
                        }
                        
                        if (messageInput) {
                            messageInput.addEventListener('input', sendTyping);
                            messageInput.addEventListener('keydown', sendTyping);
                        }
                        
                        // Проверка статуса печати пользователя
                        function checkUserTyping() {
                            $.ajax({
                                url: '/admin/support-chats/{{ $chat->id }}/typing/user-status',
                                method: 'GET',
                                success: function(data) {
                                    const messagesContainer = document.getElementById('messages-container');
                                    if (data.is_typing) {
                                        typingIndicator.style.display = 'block';
                                        // Прокрутка вниз при появлении индикатора
                                        if (messagesContainer) {
                                            setTimeout(function() {
                                                messagesContainer.scrollTop = messagesContainer.scrollHeight;
                                            }, 100);
                                        }
                                    } else {
                                        typingIndicator.style.display = 'none';
                                    }
                                },
                                error: function() {}
                            });
                        }
                        
                        typingCheckInterval = setInterval(checkUserTyping, 2000);
                        
                        // Остановка печати при отправке (обрабатывается в основном обработчике формы ниже)
                        
                        // Остановка при уходе со страницы
                        window.addEventListener('beforeunload', function() {
                            sendStopTyping();
                            // Очищаем все таймауты и интервалы
                            if (typingTimeout) {
                                clearTimeout(typingTimeout);
                            }
                            if (typingThrottleTimeout) {
                                clearTimeout(typingThrottleTimeout);
                            }
                            if (typingCheckInterval) {
                                clearInterval(typingCheckInterval);
                            }
                            if (messagesPollInterval) {
                                clearInterval(messagesPollInterval);
                            }
                        });
                        
                        // Превью выбранных файлов
                        const attachmentsInput = document.getElementById('admin-attachments-input');
                        const attachmentsPreview = document.getElementById('admin-attachments-preview');
                        const sendForm = document.getElementById('send-message-form');
                        
                        logInfo('Form elements initialized', {
                            form: !!sendForm,
                            messageInput: !!messageInput,
                            attachmentsInput: !!attachmentsInput
                        });
                        
                        // Проверяем, что форма существует и кнопка внутри формы
                        if (sendForm) {
                            const sendBtn = document.getElementById('send-btn');
                            if (sendBtn) {
                                const isButtonInForm = sendForm.contains(sendBtn);
                                logInfo('Button check', {
                                    buttonFound: !!sendBtn,
                                    buttonInForm: isButtonInForm,
                                    buttonType: sendBtn.type,
                                    formMethod: sendForm.method,
                                    formAction: sendForm.action
                                });
                                
                                if (!isButtonInForm) {
                                    logError('Send button is not inside the form!');
                                }
                            }
                        }
                        
                        // Функция для отображения предпросмотра файлов
                        function displayFilesPreview(files) {
                            attachmentsPreview.innerHTML = '';
                            if (files && files.length > 0) {
                                const previewContainer = document.createElement('div');
                                previewContainer.className = 'row mt-2';
                                
                                Array.from(files).forEach(function(file, index) {
                                    const isImage = file.type.startsWith('image/');
                                    const col = document.createElement('div');
                                    col.className = 'col-md-3 mb-3';
                                    col.setAttribute('data-file-index', index);
                                    
                                    const card = document.createElement('div');
                                    card.className = 'card position-relative';
                                    
                                    // Добавляем кнопку удаления
                                    const removeBtn = document.createElement('button');
                                    removeBtn.type = 'button';
                                    removeBtn.className = 'btn btn-danger btn-sm position-absolute';
                                    removeBtn.style.cssText = 'top: 5px; right: 5px; z-index: 10; width: 28px; height: 28px; padding: 0; border-radius: 50%;';
                                    removeBtn.innerHTML = '<i class="fas fa-times"></i>';
                                    removeBtn.title = 'Удалить файл';
                                    removeBtn.setAttribute('data-file-index', index);
                                    removeBtn.addEventListener('click', function() {
                                        removeFile(parseInt(this.getAttribute('data-file-index')));
                                    });
                                    card.appendChild(removeBtn);
                                    
                                    if (isImage) {
                                        // Создаем предпросмотр изображения
                                        const reader = new FileReader();
                                        reader.onload = function(e) {
                                            const img = document.createElement('img');
                                            img.src = e.target.result;
                                            img.className = 'card-img-top';
                                            img.style.height = '150px';
                                            img.style.objectFit = 'cover';
                                            img.style.cursor = 'pointer';
                                            img.title = 'Нажмите для просмотра';
                                            
                                            // Добавляем возможность просмотра при клике
                                            img.addEventListener('click', function() {
                                                showImageModal(e.target.result, file.name);
                                            });
                                            
                                            card.appendChild(img);
                                        };
                                        reader.readAsDataURL(file);
                                    } else {
                                        // Для не-изображений показываем иконку
                                        const iconDiv = document.createElement('div');
                                        iconDiv.className = 'card-img-top d-flex align-items-center justify-content-center bg-light';
                                        iconDiv.style.height = '150px';
                                        iconDiv.innerHTML = '<i class="fas fa-file fa-3x text-secondary"></i>';
                                        card.appendChild(iconDiv);
                                    }
                                    
                                    const cardBody = document.createElement('div');
                                    cardBody.className = 'card-body p-2';
                                    cardBody.innerHTML = `
                                        <small class="d-block text-truncate" title="${file.name}">${file.name}</small>
                                        <small class="text-muted">${(file.size / 1024 / 1024).toFixed(2)} MB</small>
                                    `;
                                    card.appendChild(cardBody);
                                    
                                    col.appendChild(card);
                                    previewContainer.appendChild(col);
                                });
                                
                                attachmentsPreview.appendChild(previewContainer);
                            }
                        }
                        
                        // Функция для удаления файла из списка
                        function removeFile(index) {
                            if (!attachmentsInput.files || attachmentsInput.files.length === 0) {
                                return;
                            }
                            
                            // Создаем новый DataTransfer без удаленного файла
                            const dataTransfer = new DataTransfer();
                            const files = Array.from(attachmentsInput.files);
                            
                            files.forEach(function(file, i) {
                                if (i !== index) {
                                    dataTransfer.items.add(file);
                                }
                            });
                            
                            // Обновляем input
                            attachmentsInput.files = dataTransfer.files;
                            
                            // Обновляем предпросмотр
                            displayFilesPreview(attachmentsInput.files);
                            
                            // Показываем уведомление
                            if (dataTransfer.files.length === 0) {
                                const notification = document.createElement('div');
                                notification.className = 'alert alert-info alert-dismissible fade show mt-2';
                                notification.innerHTML = `
                                    <i class="fas fa-info-circle mr-2"></i>
                                    Все файлы удалены
                                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                                `;
                                attachmentsPreview.appendChild(notification);
                                
                                setTimeout(() => {
                                    notification.remove();
                                }, 2000);
                            }
                        }
                        
                        // Функция для показа изображения в модальном окне
                        function showImageModal(imageSrc, imageName) {
                            // Создаем или обновляем модальное окно
                            let modal = document.getElementById('image-preview-modal');
                            if (!modal) {
                                modal = document.createElement('div');
                                modal.id = 'image-preview-modal';
                                modal.className = 'modal fade';
                                modal.tabIndex = -1;
                                modal.setAttribute('role', 'dialog');
                                modal.innerHTML = `
                                    <div class="modal-dialog modal-xl modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="image-preview-title"></h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Закрыть">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body text-center p-0" style="min-height: 300px;">
                                                <img id="image-preview-img" src="" class="img-fluid" style="max-height: 80vh; cursor: zoom-in;">
                                            </div>
                                            <div class="modal-footer">
                                                <a id="image-download-btn" href="" download class="btn btn-primary">
                                                    <i class="fas fa-download mr-1"></i> Скачать
                                                </a>
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                                    <i class="fas fa-times mr-1"></i> Закрыть
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                `;
                                document.body.appendChild(modal);
                                
                                // Добавляем обработчик для закрытия по Escape
                                $(modal).on('shown.bs.modal', function() {
                                    $(this).focus();
                                });
                                
                                // Добавляем обработчик клика по изображению для увеличения
                                const img = document.getElementById('image-preview-img');
                                img.addEventListener('click', function() {
                                    if (this.style.cursor === 'zoom-in') {
                                        this.style.maxHeight = 'none';
                                        this.style.cursor = 'zoom-out';
                                    } else {
                                        this.style.maxHeight = '80vh';
                                        this.style.cursor = 'zoom-in';
                                    }
                                });
                            }
                            
                            // Обновляем содержимое модального окна
                            document.getElementById('image-preview-title').textContent = imageName || 'Просмотр изображения';
                            document.getElementById('image-preview-img').src = imageSrc;
                            document.getElementById('image-preview-img').style.maxHeight = '80vh';
                            document.getElementById('image-preview-img').style.cursor = 'zoom-in';
                            document.getElementById('image-download-btn').href = imageSrc;
                            document.getElementById('image-download-btn').download = imageName || 'image';
                            
                            // Показываем модальное окно
                            $(modal).modal('show');
                        }
                        
                        if (attachmentsInput) {
                            attachmentsInput.addEventListener('change', function() {
                                displayFilesPreview(this.files);
                            });
                        }
                        
                        // Поддержка вставки файлов из буфера обмена (Ctrl+V)
                        if (messageInput) {
                            messageInput.addEventListener('paste', function(e) {
                                const items = e.clipboardData.items;
                                const files = [];
                                
                                for (let i = 0; i < items.length; i++) {
                                    if (items[i].kind === 'file') {
                                        const file = items[i].getAsFile();
                                        if (file) {
                                            files.push(file);
                                        }
                                    }
                                }
                                
                                if (files.length > 0) {
                                    e.preventDefault(); // Предотвращаем вставку в textarea
                                    
                                    // Создаем DataTransfer для добавления файлов к input
                                    const dataTransfer = new DataTransfer();
                                    
                                    // Добавляем существующие файлы из input
                                    if (attachmentsInput.files) {
                                        Array.from(attachmentsInput.files).forEach(file => {
                                            dataTransfer.items.add(file);
                                        });
                                    }
                                    
                                    // Добавляем новые файлы из буфера обмена
                                    files.forEach(file => {
                                        dataTransfer.items.add(file);
                                    });
                                    
                                    // Обновляем input
                                    attachmentsInput.files = dataTransfer.files;
                                    
                                    // Отображаем предпросмотр
                                    displayFilesPreview(attachmentsInput.files);
                                    
                                    // Показываем уведомление
                                    const notification = document.createElement('div');
                                    notification.className = 'alert alert-success alert-dismissible fade show mt-2';
                                    notification.innerHTML = `
                                        <i class="fas fa-check-circle mr-2"></i>
                                        Добавлено ${files.length} ${files.length === 1 ? 'файл' : 'файлов'} из буфера обмена
                                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                                    `;
                                    attachmentsPreview.insertBefore(notification, attachmentsPreview.firstChild);
                                    
                                    // Автоматически скрываем уведомление через 3 секунды
                                    setTimeout(() => {
                                        notification.remove();
                                    }, 3000);
                                }
                            });
                        }
                        
                        // Валидация формы - разрешаем отправку без текста, если есть файлы
                        if (sendForm) {
                            logInfo('Form found, adding submit handler');
                            
                            sendForm.addEventListener('submit', function(e) {
                                logInfo('Form submit event triggered', {
                                    formMethod: sendForm.method,
                                    formAction: sendForm.action,
                                    defaultPrevented: e.defaultPrevented
                                });
                                
                                const messageText = messageInput ? messageInput.value.trim() : '';
                                const hasFiles = attachmentsInput && attachmentsInput.files && attachmentsInput.files.length > 0;
                                
                                logInfo('Validation check', {
                                    messageText: messageText,
                                    hasFiles: hasFiles,
                                    messageLength: messageText.length,
                                    filesCount: attachmentsInput ? attachmentsInput.files.length : 0
                                });
                                
                                if (!messageText && !hasFiles) {
                                    logInfo('Validation failed - no message and no files');
                                    e.preventDefault();
                                    e.stopPropagation();
                                    alert('Введите сообщение или прикрепите файлы');
                                    return false;
                                }
                                
                                logInfo('Form validation passed, allowing submission');
                                
                                // Убеждаемся, что форма имеет правильный method
                                if (sendForm.method.toLowerCase() !== 'post') {
                                    logError('Form method is not POST!', { method: sendForm.method });
                                    sendForm.method = 'POST';
                                }
                                
                                if (typeof sendStopTyping === 'function') {
                                    sendStopTyping();
                                }
                                
                                // Получаем элементы для индикатора загрузки
                                const sendBtn = document.getElementById('send-btn');
                                const sendIcon = document.getElementById('send-icon');
                                const sendSpinner = document.getElementById('send-spinner');
                                
                                if (sendBtn && sendIcon && sendSpinner) {
                                    // ВАЖНО: НЕ отключаем textarea перед отправкой, иначе значение не передастся!
                                    // Блокируем только кнопку
                                    sendBtn.disabled = true;
                                    sendBtn.style.opacity = '0.6';
                                    sendBtn.style.cursor = 'not-allowed';
                                    sendBtn.title = 'Отправка...';
                                    
                                    // НЕ отключаем messageInput - это блокирует передачу значения в POST!
                                    // Вместо этого делаем его readonly визуально
                                    if (messageInput) {
                                        messageInput.style.opacity = '0.6';
                                        messageInput.style.cursor = 'not-allowed';
                                        messageInput.setAttribute('readonly', 'readonly');
                                    }
                                    
                                    // Показываем спиннер, скрываем иконку
                                    sendIcon.classList.add('d-none');
                                    sendSpinner.classList.remove('d-none');
                                    
                                    logInfo('Button state updated - form should submit now', {
                                        formMethod: sendForm.method,
                                        formAction: sendForm.action,
                                        messageValue: messageInput ? messageInput.value : 'no input',
                                        messageLength: messageInput ? messageInput.value.length : 0
                                    });
                                    
                                    // Функция восстановления состояния
                                    const restoreState = function() {
                                        sendBtn.disabled = false;
                                        sendBtn.style.opacity = '1';
                                        sendBtn.style.cursor = 'pointer';
                                        sendBtn.title = 'Отправить';
                                        
                                        if (messageInput) {
                                            messageInput.removeAttribute('readonly');
                                            messageInput.style.opacity = '1';
                                            messageInput.style.cursor = 'text';
                                        }
                                        
                                        sendIcon.classList.remove('d-none');
                                        sendSpinner.classList.add('d-none');
                                    };
                                    
                                    // Восстанавливаем состояние через 15 секунд на случай, если что-то пойдет не так
                                    const restoreTimeout = setTimeout(restoreState, 15000);
                                    
                                    // Сохраняем функцию восстановления для возможного вызова извне
                                    window._restoreSendButtonState = function() {
                                        clearTimeout(restoreTimeout);
                                        restoreState();
                                    };
                                    
                                    // После успешной отправки страница перезагрузится,
                                    // но если произошла ошибка валидации, восстановим состояние
                                    setTimeout(function() {
                                        if (window._restoreSendButtonState && typeof window._restoreSendButtonState === 'function') {
                                            restoreState();
                                        }
                                    }, 3000);
                                }
                                
                                // НЕ вызываем e.preventDefault() - форма должна отправиться
                                // НЕ вызываем e.stopPropagation() - позволяем событию всплыть
                                // return true позволяет форме отправиться
                                
                                // Финальная проверка перед отправкой
                                const finalMethod = sendForm.method || sendForm.getAttribute('method');
                                logInfo('Allowing form submission', {
                                    formMethod: finalMethod,
                                    formAction: sendForm.action,
                                    formId: sendForm.id
                                });
                                
                                // Если метод все еще не POST, принудительно устанавливаем
                                if (finalMethod && finalMethod.toLowerCase() !== 'post') {
                                    logError('Form method is still not POST! Forcing POST method', { 
                                        currentMethod: finalMethod 
                                    });
                                    sendForm.method = 'POST';
                                    sendForm.setAttribute('method', 'POST');
                                }
                                
                                // Позволяем форме отправиться естественным образом
                                // НЕ вызываем e.preventDefault() - это позволит форме отправиться
                                return true;
                            }, false); // Используем capture phase для раннего перехвата
                            
                            // Также добавляем обработчик клика на кнопку для отладки
                            const sendBtn = document.getElementById('send-btn');
                            if (sendBtn) {
                                sendBtn.addEventListener('click', function(e) {
                                    logInfo('Send button clicked', {
                                        buttonType: sendBtn.type,
                                        formId: sendForm.id,
                                        formAction: sendForm.action,
                                        formMethod: sendForm.method
                                    });
                                    
                                    // Убеждаемся, что форма имеет правильный method перед отправкой
                                    if (sendForm.method.toLowerCase() !== 'post') {
                                        logError('Form method is not POST, fixing it!', { method: sendForm.method });
                                        sendForm.method = 'POST';
                                    }
                                    
                                    // Не предотвращаем дефолтное поведение - форма должна отправиться
                                }, false);
                            } else {
                                logError('Send button not found!');
                            }
                        } else {
                            logError('Form not found!', {
                                formId: 'send-message-form',
                                documentReady: document.readyState
                            });
                        }
                        
                        // Обработчик для просмотра изображений
                        document.addEventListener('click', function(e) {
                            if (e.target.classList.contains('image-preview-trigger')) {
                                e.preventDefault();
                                const imageUrl = e.target.getAttribute('data-image-url');
                                const imageName = e.target.getAttribute('data-image-name');
                                showImageModal(imageUrl, imageName);
                            }
                        });
                        
                        // Поиск по сообщениям
                        const searchInput = document.getElementById('search-messages-input');
                        const allMessages = Array.from(messagesContainer.querySelectorAll('.message-bubble'));
                        
                        if (searchInput && messagesContainer) {
                            searchInput.addEventListener('input', function() {
                                const searchTerm = this.value.toLowerCase().trim();
                                
                                if (searchTerm === '') {
                                    // Показываем все сообщения
                                    allMessages.forEach(function(messageEl) {
                                        const messageWrapper = messageEl.closest('.mb-3');
                                        if (messageWrapper) {
                                            messageWrapper.style.display = '';
                                        }
                                    });
                                    return;
                                }
                                
                                // Фильтруем сообщения
                                let hasResults = false;
                                allMessages.forEach(function(messageEl) {
                                    const messageWrapper = messageEl.closest('.mb-3');
                                    if (!messageWrapper) return;
                                    
                                    const messageText = messageEl.querySelector('.message-text');
                                    if (!messageText) return;
                                    
                                    const text = messageText.textContent.toLowerCase();
                                    if (text.includes(searchTerm)) {
                                        messageWrapper.style.display = '';
                                        hasResults = true;
                                        // Подсветка найденного текста
                                        const originalText = messageText.innerHTML;
                                        const highlightedText = originalText.replace(
                                            new RegExp(`(${searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi'),
                                            '<mark style="background: #ffeb3b; padding: 2px 4px; border-radius: 3px;">$1</mark>'
                                        );
                                        messageText.innerHTML = highlightedText;
                                    } else {
                                        messageWrapper.style.display = 'none';
                                    }
                                });
                                
                                // Показываем сообщение если нет результатов
                                if (!hasResults) {
                                    const noResults = messagesContainer.querySelector('.no-search-results');
                                    if (!noResults) {
                                        const noResultsDiv = document.createElement('div');
                                        noResultsDiv.className = 'no-search-results text-center text-muted p-4';
                                        noResultsDiv.innerHTML = '<i class="fas fa-search mr-2"></i>Ничего не найдено';
                                        messagesContainer.appendChild(noResultsDiv);
                                    }
                                } else {
                                    const noResults = messagesContainer.querySelector('.no-search-results');
                                    if (noResults) {
                                        noResults.remove();
                                    }
                                }
                            });
                            
                            // Очистка подсветки при очистке поиска
                            searchInput.addEventListener('blur', function() {
                                if (this.value === '') {
                                    const highlighted = messagesContainer.querySelectorAll('mark');
                                    highlighted.forEach(function(mark) {
                                        const parent = mark.parentNode;
                                        if (parent) {
                                            parent.textContent = parent.textContent;
                                        }
                                    });
                                }
                            });
                        }
                        
                        // Прокрутка вниз при загрузке страницы
                        function scrollToBottom() {
                            const messagesContainer = document.getElementById('messages-container');
                            if (messagesContainer) {
                                messagesContainer.scrollTop = messagesContainer.scrollHeight;
                            }
                        }
                        
                        // Прокручиваем при загрузке страницы (несколько попыток для надежности)
                        function attemptScroll() {
                            scrollToBottom();
                            // Повторяем еще раз через небольшую задержку на случай, если контент еще загружается
                            setTimeout(scrollToBottom, 300);
                            setTimeout(scrollToBottom, 600);
                        }
                        
                        // Прокручиваем при загрузке DOM и window
                        if (document.readyState === 'loading') {
                            document.addEventListener('DOMContentLoaded', function() {
                                attemptScroll();
                            });
                        } else {
                            // DOM уже загружен
                            attemptScroll();
                        }
                        
                        // Также прокручиваем при полной загрузке страницы
                        window.addEventListener('load', function() {
                            setTimeout(scrollToBottom, 100);
                        });
                        
                    })();
                    </script>
                    
                    <!-- Emoji Picker Script -->
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const emojiButton = document.getElementById('emoji-button');
                            const emojiPickerContainer = document.getElementById('emoji-picker-container');
                            const messageInput = document.getElementById('admin-message-input');
                            
                            if (!emojiButton || !emojiPickerContainer || !messageInput) {
                                console.error('Emoji picker elements not found');
                                return;
                            }
                            
                            // Popular emojis organized by categories
                            const emojiCategories = {
                                'Смайлики': ['😀', '😃', '😄', '😁', '😆', '😅', '😂', '🤣', '😊', '😇', '🙂', '🙃', '😉', '😌', '😍', '🥰', '😘', '😗', '😙', '😚', '😋', '😛', '😝', '😜', '🤪', '🤨', '🧐', '🤓', '😎', '🤩', '🥳', '😏', '😒', '😞', '😔', '😟', '😕', '🙁', '😣', '😖', '😫', '😩', '🥺', '😢', '😭', '😤', '😠', '😡', '🤬', '🤯', '😳', '🥵', '🥶', '😱', '😨', '😰', '😥', '😓'],
                                'Жесты': ['🤗', '🤔', '🤭', '🤫', '🤥', '😶', '😐', '😑', '😬', '🙄', '😯', '😦', '😧', '😮', '😲', '🥱', '😴', '🤤', '😪', '😵', '🤐', '🥴', '🤢', '🤮', '🤧', '😷', '🤒', '🤕', '🤑', '🤠', '😈', '👿', '👹', '👺', '🤡', '💩', '👻', '💀', '☠️', '👽', '👾', '🤖', '🎃'],
                                'Люди': ['👋', '🤚', '🖐', '✋', '🖖', '👌', '🤏', '✌️', '🤞', '🤟', '🤘', '🤙', '👈', '👉', '👆', '🖕', '👇', '☝️', '👍', '👎', '✊', '👊', '🤛', '🤜', '👏', '🙌', '👐', '🤲', '🤝', '🙏', '✍️', '💪', '🦾', '🦿', '🦵', '🦶', '👂', '🦻', '👃', '🧠', '🦷', '🦴', '👀', '👁', '👅', '👄'],
                                'Сердца': ['💋', '💌', '💘', '💝', '💖', '💗', '💓', '💞', '💕', '💟', '❣️', '💔', '❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💯', '💢', '💥', '💫', '💦', '💨', '🕳️', '💣', '💬', '👁️‍🗨️', '🗨️', '🗯️', '💭', '💤'],
                                'Предметы': ['👓', '🕶', '🥽', '🥼', '🦺', '👔', '👕', '👖', '🧣', '🧤', '🧥', '🧦', '👗', '👘', '🥻', '🩱', '🩲', '🩳', '👙', '👚', '👛', '👜', '👝', '🛍️', '🎒', '👞', '👟', '🥾', '🥿', '👠', '👡', '🩰', '👢', '👑', '👒', '🎩', '🎓', '🧢', '⛑️', '📿', '💄', '💍', '💎'],
                                'Символы': ['🔥', '⭐', '🌟', '✨', '💫', '💥', '💯', '🎉', '🎊', '🎈', '🎁', '🏆', '🥇', '🥈', '🥉', '⚽', '🏀', '🏈', '⚾', '🎾', '🏐', '🏉', '🎱', '🏓', '🏸', '🥅', '🏒', '🏑', '🏏', '🥃', '🥤', '🧃', '🧉', '🧊', '🥢', '🍽️', '🍴', '🥄', '🔪', '🏺']
                            };
                            
                            function createEmojiPicker() {
                                if (emojiPickerContainer.querySelector('.emoji-picker-content')) {
                                    return; // Already created
                                }
                                
                                const pickerContent = document.createElement('div');
                                pickerContent.className = 'emoji-picker-content';
                                
                                // Create tabs for categories
                                const tabsContainer = document.createElement('div');
                                tabsContainer.className = 'emoji-tabs';
                                
                                const emojisContainer = document.createElement('div');
                                emojisContainer.className = 'emoji-grid';
                                
                                let activeCategory = Object.keys(emojiCategories)[0];
                                
                                // Create tabs
                                Object.keys(emojiCategories).forEach((category, index) => {
                                    const tab = document.createElement('button');
                                    tab.className = 'emoji-tab' + (index === 0 ? ' active' : '');
                                    tab.textContent = category;
                                    tab.addEventListener('click', () => {
                                        // Update active tab
                                        tabsContainer.querySelectorAll('.emoji-tab').forEach(t => t.classList.remove('active'));
                                        tab.classList.add('active');
                                        
                                        // Update emojis
                                        showEmojis(category, emojisContainer);
                                        activeCategory = category;
                                    });
                                    tabsContainer.appendChild(tab);
                                });
                                
                                // Show emojis for first category
                                showEmojis(activeCategory, emojisContainer);
                                
                                pickerContent.appendChild(tabsContainer);
                                pickerContent.appendChild(emojisContainer);
                                emojiPickerContainer.innerHTML = '';
                                emojiPickerContainer.appendChild(pickerContent);
                            }
                            
                            function showEmojis(category, container) {
                                container.innerHTML = '';
                                const emojis = emojiCategories[category];
                                
                                emojis.forEach(emoji => {
                                    const btn = document.createElement('button');
                                    btn.className = 'emoji-item';
                                    btn.textContent = emoji;
                                    btn.title = emoji;
                                    btn.addEventListener('click', () => insertEmoji(emoji));
                                    container.appendChild(btn);
                                });
                            }
                            
                            function insertEmoji(emoji) {
                                const cursorPos = messageInput.selectionStart || messageInput.value.length;
                                const textBefore = messageInput.value.substring(0, cursorPos);
                                const textAfter = messageInput.value.substring(cursorPos);
                                
                                messageInput.value = textBefore + emoji + textAfter;
                                messageInput.selectionStart = messageInput.selectionEnd = cursorPos + emoji.length;
                                messageInput.focus();
                                
                                // Trigger input event for auto-resize
                                messageInput.dispatchEvent(new Event('input'));
                            }
                            
                            // Toggle emoji picker (appears from bottom like in Telegram)
                            emojiButton.addEventListener('click', function(e) {
                                e.preventDefault();
                                e.stopPropagation();
                                
                                const isVisible = emojiPickerContainer.style.display === 'block' || 
                                                  (emojiPickerContainer.style.display === '' && emojiPickerContainer.offsetParent !== null);
                                
                                if (!isVisible) {
                                    createEmojiPicker();
                                    emojiPickerContainer.style.setProperty('display', 'flex', 'important');
                                } else {
                                    emojiPickerContainer.style.setProperty('display', 'none', 'important');
                                }
                            });
                            
                            // Close picker when clicking outside (optional)
                            document.addEventListener('click', function(e) {
                                const isPickerVisible = emojiPickerContainer.style.display === 'flex' || 
                                                        (emojiPickerContainer.style.display === '' && emojiPickerContainer.offsetParent !== null);
                                
                                if (emojiPickerContainer && isPickerVisible) {
                                    // Don't close if clicking inside picker or on button
                                    const clickedInsidePicker = emojiPickerContainer.contains(e.target);
                                    const clickedOnButton = e.target === emojiButton || emojiButton.contains(e.target);
                                    
                                    if (!clickedInsidePicker && !clickedOnButton) {
                                        emojiPickerContainer.style.setProperty('display', 'none', 'important');
                                    }
                                }
                            });
                        });
                    </script>
                    
                    <!-- Auto-resize textarea -->
                    <script>
                        const textarea = document.getElementById('admin-message-input');
                        
                        textarea.addEventListener('input', function() {
                            this.style.height = 'auto';
                            this.style.height = Math.min(this.scrollHeight, 120) + 'px';
                        });
                        
                        // Search toggle
                        const searchToggle = document.getElementById('search-toggle');
                        const searchBar = document.getElementById('search-bar');
                        const closeSearch = document.getElementById('close-search');
                        
                        if (searchToggle) {
                            searchToggle.addEventListener('click', () => {
                                searchBar.style.display = searchBar.style.display === 'none' ? 'flex' : 'none';
                                if (searchBar.style.display === 'flex') {
                                    document.getElementById('search-messages-input').focus();
                                }
                            });
                        }
                        
                        if (closeSearch) {
                            closeSearch.addEventListener('click', () => {
                                searchBar.style.display = 'none';
                                document.getElementById('search-messages-input').value = '';
                                // Trigger search to show all messages
                                const event = new Event('input');
                                document.getElementById('search-messages-input').dispatchEvent(event);
                            });
                        }
                    </script>
                </div>
            </div>
            <!-- End Telegram Chat Container -->
        </div>
    </div>
    <!-- End Telegram Layout -->
    @endif

    {{-- Styles для Telegram layout (только для Telegram чатов) --}}
    @if($chat->isFromTelegram())
    <style>
        /* Telegram Layout - Full Screen Chat */
        .telegram-layout {
            height: calc(100vh - 120px);
            min-height: 600px;
            max-height: calc(100vh - 120px);
            background: #f0f2f5;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
            display: flex !important;
            flex-direction: column !important;
        }
        
        /* Telegram-style Chat Container */
        .telegram-chat-container {
            width: 100%;
            height: 100%;
            background: #fff;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            position: relative;
            min-height: 0; /* Важно для flexbox */
        }
        
        /* Smooth transitions */
        .emoji-picker-wrapper {
            transition: transform 0.3s ease, opacity 0.3s ease;
        }
        
        /* Telegram Header */
        .telegram-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            background: #2481cc;
            color: white;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
            min-height: 56px;
            flex-shrink: 0;
        }
        
        .back-button {
            color: white;
            font-size: 20px;
            margin-right: 15px;
            text-decoration: none;
            transition: opacity 0.2s;
        }
        
        .back-button:hover {
            opacity: 0.8;
        }
        
        .chat-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 12px;
        }
        
        .chat-avatar-placeholder {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 500;
            margin-right: 12px;
        }
        
        .chat-info {
            flex: 1;
        }
        
        .chat-name {
            font-size: 16px;
            font-weight: 500;
            margin-bottom: 2px !important;
        }
        
        .chat-status {
            font-size: 13px;
            opacity: 0.9;
        }
        
        .online-indicator {
            color: #4caf50;
            font-size: 8px;
            margin-right: 4px;
        }
        
        .chat-actions {
            display: flex;
            gap: 15px;
        }
        
        .action-button {
            background: none;
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
            padding: 5px;
            transition: opacity 0.2s;
        }
        
        .action-button:hover {
            opacity: 0.7;
        }
        
        /* Search Bar */
        .search-bar {
            padding: 8px 16px;
            background: #eff1f3;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .search-bar input {
            flex: 1;
            border: 1px solid #ddd;
            border-radius: 20px;
            padding: 6px 15px;
        }
        
        .btn-close-search {
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            color: #666;
        }
        
        /* Messages Area */
        .messages-container {
            flex: 1 1 auto;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 15px;
            background: #f4f4f5 url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect fill="%23f4f4f5" width="100" height="100"/><circle fill="%23e4e4e7" cx="20" cy="20" r="3"/><circle fill="%23e4e4e7" cx="80" cy="80" r="3"/></svg>');
            min-height: 0; /* Важно для flexbox */
        }
        
        #messages-container {
            flex: 1 1 auto;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 15px;
            background: #f4f4f5 url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect fill="%23f4f4f5" width="100" height="100"/><circle fill="%23e4e4e7" cx="20" cy="20" r="3"/><circle fill="%23e4e4e7" cx="80" cy="80" r="3"/></svg>');
            min-height: 0; /* Важно для flexbox */
        }
        
        /* Message Bubbles - Telegram Style */
        .message-item {
            margin-bottom: 8px;
            padding: 2px 0;
        }
        
        .message-bubble {
            padding: 7px 12px;
            border-radius: 12px;
            max-width: 65%;
            min-width: 50px;
            position: relative;
            word-wrap: break-word;
            box-shadow: 0 1px 1px rgba(0,0,0,0.08);
            margin-bottom: 2px;
        }
        
        .message-admin {
            background: #dcf8c6;
            margin-left: auto;
            border-bottom-right-radius: 4px;
            color: #000;
            display: inline-block;
            margin-right: 0;
        }
        
        .message-user {
            background: #ffffff;
            margin-right: auto;
            border-bottom-left-radius: 4px;
            color: #000;
            display: inline-block;
            margin-left: 0;
        }
        
        /* Fix message alignment - Telegram style */
        .message-item > .d-flex.justify-content-end {
            display: flex !important;
            justify-content: flex-end !important;
            align-items: flex-end;
            padding-right: 8px;
            padding-left: 70px;
        }
        
        .message-item > .d-flex.justify-content-start {
            display: flex !important;
            justify-content: flex-start !important;
            align-items: flex-end;
            padding-left: 8px;
            padding-right: 70px;
        }
        
        /* Message header - Telegram style */
        .message-sender-name {
            font-size: 13px;
            font-weight: 500;
            color: #2481cc;
            margin-bottom: 4px;
            padding-left: 2px;
        }
        
        .message-content {
            position: relative;
            padding-right: 40px;
        }
        
        .message-text {
            word-wrap: break-word;
            font-size: 14px;
            line-height: 1.4;
            margin-bottom: 4px;
        }
        
        .message-time {
            position: absolute;
            bottom: -2px;
            right: 8px;
            font-size: 11px;
            color: rgba(0,0,0,0.45);
            white-space: nowrap;
            pointer-events: none;
            background: inherit;
            padding: 0 2px;
        }
        
        .message-admin .message-time {
            color: rgba(0,0,0,0.35);
        }
        
        .message-user .message-time {
            color: rgba(0,0,0,0.45);
        }
        
        /* Если только вложение, время должно быть в углу вложения */
        .message-attachments + .message-time {
            bottom: 4px;
        }
        
        .message-header {
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 4px;
        }
        
        .message-text {
            word-wrap: break-word;
            font-size: 14px;
            line-height: 1.4;
        }
        
        .message-time {
            font-size: 11px;
            color: rgba(0,0,0,0.45);
            margin-top: 4px;
            text-align: right;
        }
        
        /* Input Area - Telegram Style */
        .telegram-input-wrapper {
            position: relative;
            background: #f0f2f5 !important;
            padding: 8px 12px;
            border-top: 1px solid #e4e7e9;
            flex: 0 0 auto !important; /* Не растягивается, не сжимается */
            display: block !important;
            visibility: visible !important;
            z-index: 100;
            width: 100%;
            box-sizing: border-box;
            overflow: visible !important;
        }
        
        #send-message-form {
            display: block !important;
            visibility: visible !important;
            width: 100%;
        }
        
        .attachments-preview-area {
            margin-bottom: 10px;
        }
        
        .input-container {
            display: flex !important;
            align-items: flex-end;
            gap: 8px;
            background: white;
            border-radius: 24px;
            padding: 8px 12px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
            visibility: visible !important;
            width: 100%;
            box-sizing: border-box;
            position: relative;
            z-index: 10;
        }
        
        .attach-button {
            color: #54656f;
            font-size: 22px;
            cursor: pointer;
            margin: 0;
            padding: 4px;
            transition: color 0.2s;
            display: inline-block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        
        .attach-button:hover {
            color: #2481cc;
        }
        
        .message-input {
            flex: 1;
            border: none;
            outline: none;
            resize: none;
            max-height: 120px;
            font-size: 15px;
            padding: 4px 8px;
            line-height: 1.5;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        
        .input-actions {
            display: flex !important;
            align-items: center;
            gap: 8px;
            visibility: visible !important;
            opacity: 1 !important;
        }
        
        .emoji-button {
            background: none;
            border: none;
            color: #54656f;
            font-size: 22px;
            cursor: pointer;
            padding: 4px;
            transition: color 0.2s;
            display: inline-block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        
        .emoji-button:hover {
            color: #2481cc;
        }
        
        .send-button {
            background: #2481cc;
            border: none;
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex !important;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.2s, opacity 0.2s;
            visibility: visible !important;
            opacity: 1 !important;
            position: relative;
        }
        
        .send-button:hover:not(:disabled) {
            background: #1a6db3;
        }
        
        .send-button:disabled {
            cursor: not-allowed !important;
            opacity: 0.6 !important;
            background: #2481cc !important;
        }
        
        .send-button i {
            font-size: 16px;
        }
        
        .send-button .spinner-border-sm {
            width: 1rem;
            height: 1rem;
            border-width: 0.15em;
            border-color: white transparent white transparent;
        }
        
        .message-input:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            background-color: #f5f5f5;
        }
        
        /* Right Panel - Emoji/Stickers/GIF Picker */
        /* Emoji Picker - appears from bottom like in Telegram */
        .emoji-picker-wrapper {
            position: absolute;
            bottom: calc(100% + 0px);
            left: 0;
            right: 0;
            width: 100%;
            height: 350px;
            max-height: 350px;
            background: #ffffff;
            border-top: 1px solid #e4e7e9;
            box-shadow: 0 -4px 12px rgba(0,0,0,0.15);
            display: none;
            flex-direction: column;
            z-index: 100;
            overflow: hidden;
        }
        
        .emoji-picker-wrapper[style*="flex"] {
            display: flex !important;
        }
        
        .emoji-picker-content {
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .emoji-tabs {
            display: flex;
            border-bottom: 1px solid #e0e0e0;
            background: #f8f9fa;
            overflow-x: auto;
        }
        
        .emoji-tab {
            flex: 1;
            min-width: 80px;
            padding: 8px 12px;
            border: none;
            background: transparent;
            cursor: pointer;
            font-size: 12px;
            color: #666;
            transition: all 0.2s;
            white-space: nowrap;
        }
        
        .emoji-tab:hover {
            background: #e9ecef;
        }
        
        .emoji-tab.active {
            background: white;
            color: #2481cc;
            font-weight: 500;
            border-bottom: 2px solid #2481cc;
        }
        
        .emoji-grid {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            gap: 4px;
            padding: 10px;
            flex: 1;
            overflow-y: auto;
            min-height: 0; /* Для flexbox */
            max-height: calc(350px - 40px); /* Минус высота табов */
        }
        
        .emoji-item {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            padding: 6px;
            border-radius: 6px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .emoji-item:hover {
            background: #f0f0f0;
            transform: scale(1.2);
        }
        
        .emoji-item:active {
            transform: scale(1.1);
        }
        
        /* Scrollbar for emoji grid */
        .emoji-grid::-webkit-scrollbar {
            width: 6px;
        }
        
        .emoji-grid::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        .emoji-grid::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 3px;
        }
        
        .emoji-grid::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        
        /* Old typing indicator */
        .typing-message {
            display: flex;
            align-items: center;
            min-height: 20px;
        }
        .typing-indicator {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 0;
        }
        .typing-indicator span {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: currentColor;
            opacity: 0.6;
            animation: typing-bounce 1.4s infinite ease-in-out;
        }
        .typing-indicator span:nth-child(1) {
            animation-delay: 0s;
        }
        .typing-indicator span:nth-child(2) {
            animation-delay: 0.2s;
        }
        .typing-indicator span:nth-child(3) {
            animation-delay: 0.4s;
        }
        @keyframes typing-bounce {
            0%, 60%, 100% {
                transform: translateY(0);
                opacity: 0.6;
            }
            30% {
                transform: translateY(-10px);
                opacity: 1;
            }
        }
        
        /* Стили для просмотра изображений */
        .image-preview-trigger {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .image-preview-trigger:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        #image-preview-modal .modal-body {
            background: #000;
        }
        
        #image-preview-modal img {
            max-width: 100%;
            height: auto;
        }
        
        /* Стили для предпросмотра файлов перед отправкой */
        #admin-attachments-preview .card {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        #admin-attachments-preview .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        #admin-attachments-preview .card-img-top {
            background: #f8f9fa;
        }
        
        /* Стили для кнопки удаления файла */
        #admin-attachments-preview .card .btn-danger {
            opacity: 0.9;
            transition: opacity 0.2s, transform 0.2s;
        }
        
        #admin-attachments-preview .card:hover .btn-danger {
            opacity: 1;
            transform: scale(1.1);
        }
        
        #admin-attachments-preview .card .btn-danger:hover {
            transform: scale(1.2);
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.4);
        }
        
        /* Mobile responsive */
        @media (max-width: 768px) {
            .telegram-chat-container {
                left: 0;
            }
            
            .message-bubble {
                max-width: 85%;
            }
        }
    </style>
    @endif
    
    {{-- Styles для старого layout (только для не-Telegram чатов) --}}
    @if(!$chat->isFromTelegram())
    <style>
        /* Улучшенные стили для заголовков карточек */
        .card-header-modern {
            background: linear-gradient(135deg, #f8f9fc 0%, #ffffff 100%);
            border-bottom: 2px solid #e3e6f0;
            padding: 1.25rem 1.5rem;
        }
        
        .card-header-modern h5 {
            color: #2c3e50;
            font-weight: 600;
            font-size: 1.1rem;
            letter-spacing: -0.3px;
            margin-bottom: 0;
            display: flex;
            align-items: center;
        }
        
        .card-header-modern h5::before {
            content: '';
            display: inline-block;
            width: 4px;
            height: 20px;
            background: #4e73df;
            border-radius: 2px;
            margin-right: 12px;
        }
        
        /* Улучшенные стили для карточек */
        .card-modern {
            border: 1px solid #e3e6f0;
            border-radius: 0.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            overflow: hidden;
            transition: box-shadow 0.3s ease;
        }
        
        .card-modern:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        /* Улучшенные стили для формы поиска */
        .search-messages-wrapper input {
            border: 1px solid #e3e6f0;
            border-radius: 0.375rem;
            transition: all 0.2s ease;
        }
        
        .search-messages-wrapper input:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.15);
        }
        
        /* Улучшенные стили для сообщений */
        .message-bubble {
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        
        .message-admin {
            background: #e3f2fd;
            border-left: 3px solid #2196F3;
        }
        
        .message-user {
            background: #f5f5f5;
            border-left: 3px solid #757575;
        }
        
        .message-header {
            margin-bottom: 0.5rem;
        }
        
        .message-header strong {
            color: #2c3e50;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .message-text {
            color: #2c3e50;
            line-height: 1.5;
        }
    </style>
    @endif
@stop