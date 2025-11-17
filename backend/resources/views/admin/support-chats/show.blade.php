@extends('adminlte::page')

@section('title', 'Чат #' . $chat->id)

@section('content_header')
    <div class="content-header-modern">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="m-0 font-weight-light">
                    <i class="fas fa-comments mr-2"></i>Чат #{{ $chat->id }}
                </h1>
                <p class="text-muted mb-0 mt-1">
                    @if($chat->user)
                        Пользователь: {{ $chat->user->name }} ({{ $chat->user->email }})
                    @else
                        Гость: {{ $chat->guest_name }} ({{ $chat->guest_email }})
                    @endif
                </p>
            </div>
            <div>
                <a href="{{ route('admin.support-chats.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Назад к списку
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
                            <textarea name="message" id="admin-message-input" class="form-control" rows="3" required placeholder="Введите сообщение..."></textarea>
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
                    
                    <script>
                    (function() {
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
                            
                            // Вставляем перед индикатором печати
                            const typingIndicator = document.getElementById('user-typing-indicator');
                            if (typingIndicator) {
                                messagesContainer.insertBefore(messageDiv, typingIndicator);
                            } else {
                                messagesContainer.appendChild(messageDiv);
                            }
                        }
                        
                        // Загрузка новых сообщений
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
                                            guest_name: @json($chat->guest_name)
                                        };
                                        
                                        data.messages.forEach(function(message) {
                                            addMessageToDOM(message, chatData);
                                        });
                                        
                                        // Прокрутка вниз, если пользователь был внизу
                                        if (wasAtBottom) {
                                            setTimeout(function() {
                                                messagesContainer.scrollTop = messagesContainer.scrollHeight;
                                            }, 100);
                                        }
                                    }
                                },
                                error: function() {
                                    // Игнорируем ошибки
                                }
                            });
                        }
                        
                        // Запуск polling для новых сообщений
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
                        
                        // Остановка при отправке
                        document.getElementById('send-message-form').addEventListener('submit', function() {
                            sendStopTyping();
                            // После отправки сообщения перезагружаем страницу для обновления сообщений
                            // Или можно вызвать loadNewMessages() через небольшую задержку
                            setTimeout(function() {
                                loadNewMessages();
                            }, 1000);
                        });
                        
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
                </div>
            </div>
        </div>

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

                    <form method="POST" action="{{ route('admin.support-chats.update-status', $chat->id) }}" class="mb-3" id="update-status-form">
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

    <style>
        .message-bubble {
            padding: 12px 16px;
            border-radius: 12px;
            background: #f3f4f6;
        }
        .message-admin {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .message-user {
            background: #e5e7eb;
        }
        .message-header {
            font-size: 0.85em;
            margin-bottom: 4px;
        }
        .message-text {
            word-wrap: break-word;
        }
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
    </style>
@stop