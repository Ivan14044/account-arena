<?php

namespace App\Services;

use App\Models\Option;
use App\Models\SupportChat;
use App\Models\SupportMessage;
use App\Models\SupportMessageAttachment;
use App\Models\User;
use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\AppInfo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TelegramClientService
{
    private ?API $madeline = null;
    private string $sessionPath;
    private bool $enabled;
    private ?bool $isAuthorizedCache = null; // Cache authorization status

    public function __construct()
    {
        // Берем настройки из базы данных (Option), если нет - из config
        $this->enabled = Option::get('telegram_client_enabled', config('telegram.client.enabled', false));
        $this->sessionPath = config('telegram.client.session_path', storage_path('app/telegram/session.madeline'));
        
        // Создаем директорию для сессии, если её нет
        $sessionDir = dirname($this->sessionPath);
        if (!is_dir($sessionDir)) {
            mkdir($sessionDir, 0755, true);
        }
    }

    /**
     * Destructor to clean up resources
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * Explicitly disconnect and clean up resources
     */
    public function disconnect(): void
    {
        if ($this->madeline !== null) {
            try {
                // MadelineProto has internal cleanup mechanism
                // Just clear the reference to allow garbage collection
                $this->madeline = null;
                $this->isAuthorizedCache = null; // Clear authorization cache
                Log::debug('TelegramClientService: Connection closed and resources cleaned up');
            } catch (\Exception $e) {
                Log::warning('Error cleaning up TelegramClientService: ' . $e->getMessage());
                // Force cleanup even if there's an error
                $this->madeline = null;
                $this->isAuthorizedCache = null;
            }
        }
    }

    /**
     * Инициализировать и получить экземпляр MadelineProto
     */
    public function getClient(): ?API
    {
        if (!$this->enabled) {
            Log::warning('Telegram Client не включен в конфигурации');
            return null;
        }

        if ($this->madeline !== null) {
            return $this->madeline;
        }

        try {
            // Перехватываем вывод с самого начала, так как MadelineProto может вывести предупреждения
            // при создании объекта API (например, предупреждение о Windows)
            ob_start();
            
            try {
                // Берем настройки из базы данных (Option), если нет - из config
                $apiId = Option::get('telegram_api_id', config('telegram.client.api_id'));
                $apiHash = Option::get('telegram_api_hash', config('telegram.client.api_hash'));

                if (!$apiId || !$apiHash) {
                    ob_end_clean();
                    Log::error('Telegram API ID или API Hash не настроены');
                    return null;
                }

                // В MadelineProto 8.x настройки передаются через объект Settings
                $settings = new Settings();
                $appInfo = new AppInfo();
                $appInfo->setApiId((int) $apiId);
                $appInfo->setApiHash((string) $apiHash);
                $appInfo->setShowPrompt(false); // Не показывать промпт
                $settings->setAppInfo($appInfo);
                
                // Создание объекта API может вывести предупреждения (например, о Windows)
                $this->madeline = new API($this->sessionPath, $settings);
                
                // НЕ вызываем start() - он выводит HTML в веб-контексте
                // Используем manual login через phoneLogin() и completePhoneLogin()
                // start() нужен только для автоматического интерактивного режима
                
                // Проверяем, есть ли уже авторизованная сессия
                // Если сессия существует и валидна, getSelf() вернет данные пользователя
                // Если нет - это нормально, авторизация будет выполнена через phoneLogin
                
                // Проверяем авторизацию (не логируем ошибки, так как это нормально при отсутствии авторизации)
                // Вывод уже перехвачен выше
                // Кешируем результат авторизации для оптимизации
                try {
                    $self = $this->madeline->getSelf();
                    
                    if ($self) {
                        $userId = is_array($self) ? ($self['id'] ?? null) : (isset($self->id) ? $self->id : null);
                        $this->isAuthorizedCache = true;
                        Log::info('Telegram Client успешно инициализирован и авторизован', ['user_id' => $userId ?? 'unknown']);
                    } else {
                        $this->isAuthorizedCache = false;
                    }
                } catch (\Exception $e) {
                    // Если не авторизован, это нормально - нужно выполнить авторизацию
                    // Не логируем как ошибку, так как это ожидаемое поведение
                    $this->isAuthorizedCache = false;
                    Log::debug('Telegram Client не авторизован (ожидаемое поведение): ' . $e->getMessage());
                }
                
                // Очищаем весь перехваченный вывод (включая предупреждения MadelineProto)
                ob_end_clean();

                return $this->madeline;
            } catch (\Exception $e) {
                // Очищаем вывод даже при ошибке
                ob_end_clean();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Ошибка инициализации Telegram Client: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Авторизация в Telegram
     */
    public function authorize(): void
    {
        try {
            // Берем настройки из базы данных (Option), если нет - из config
            $phoneNumber = Option::get('telegram_phone_number', config('telegram.client.phone_number'));
            
            if (!$phoneNumber) {
                Log::error('Номер телефона для Telegram не настроен');
                throw new \Exception('Номер телефона не настроен. Укажите номер телефона в настройках.');
            }

            $client = $this->getClient();
            if (!$client) {
                throw new \Exception('Не удалось инициализировать Telegram клиент. Проверьте API ID и API Hash.');
            }

            // Проверяем, авторизован ли уже (используем кеш, если доступен)
            if ($this->isAuthorizedCache === true) {
                throw new \Exception('Уже авторизован. Если нужно переключиться на другой аккаунт, используйте "Сбросить сессию".');
            }
            
            // Если кеш не установлен или показывает неавторизован, проверяем
            if ($this->isAuthorizedCache !== true) {
                // getClient() уже перехватывает вывод, но getSelf() может что-то вывести
                // Перехватываем вывод на случай, если getSelf() что-то выведет
                ob_start();
                try {
                    $self = $client->getSelf();
                    
                    // getClient() уже очистил свой буфер, но мы продолжаем перехватывать
                    // Просто очищаем буфер, не получая его содержимое
                    if (ob_get_level() > 0) {
                        ob_end_clean();
                    }
                    
                    if ($self) {
                        $this->isAuthorizedCache = true;
                        $userId = is_array($self) ? ($self['id'] ?? null) : (isset($self->id) ? $self->id : null);
                        Log::info('Уже авторизован в Telegram', ['user_id' => $userId ?? 'unknown']);
                        throw new \Exception('Уже авторизован. Если нужно переключиться на другой аккаунт, используйте "Сбросить сессию".');
                    } else {
                        $this->isAuthorizedCache = false;
                    }
                } catch (\Exception $e) {
                    // Очищаем вывод даже при ошибке
                    if (ob_get_level() > 0) {
                        ob_end_clean();
                    }
                    // Если это наше исключение о том, что уже авторизован - пробрасываем дальше
                    if (strpos($e->getMessage(), 'Уже авторизован') !== false) {
                        throw $e;
                    }
                    // Иначе - не авторизован, продолжаем
                    Log::info('Требуется авторизация: ' . $e->getMessage());
                }
            }

            // Запрашиваем код через phoneLogin
            Log::info('Отправка кода на номер: ' . $phoneNumber);
            
            try {
                // Перехватываем вывод на случай, если phoneLogin попытается вывести HTML
                ob_start();
                $sentCode = $client->phoneLogin($phoneNumber);
                
                // Очищаем буфер после phoneLogin
                if (ob_get_level() > 0) {
                    $output = ob_get_clean();
                    
                    // Если был вывод HTML, это проблема
                    if (!empty($output) && (strpos($output, '<html') !== false || strpos($output, '<form') !== false)) {
                        Log::warning('phoneLogin вывел HTML вместо отправки кода');
                        throw new \Exception('Не удалось отправить код. Попробуйте сбросить сессию и начать заново.');
                    }
                }
                
                Log::info('Код отправлен в Telegram', ['phone' => $phoneNumber]);
            } catch (\Exception $e) {
                // Очищаем вывод даже при ошибке
                while (ob_get_level() > 0) {
                    ob_end_clean();
                }
                
                Log::error('Ошибка отправки кода: ' . $e->getMessage());
                
                // Проверяем, может быть уже есть незавершенная авторизация
                $errorMessage = $e->getMessage();
                if (strpos($errorMessage, 'already') !== false || 
                    strpos($errorMessage, 'уже') !== false ||
                    strpos($errorMessage, 'PHONE_CODE_HASH_EMPTY') !== false) {
                    throw new \Exception('Авторизация уже начата. Введите код, который был отправлен ранее, или сбросьте сессию.');
                }
                
                throw new \Exception('Ошибка отправки кода: ' . $errorMessage);
            }
            
        } catch (\Exception $e) {
            Log::error('Ошибка авторизации в Telegram: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Завершить авторизацию с кодом
     * Согласно документации: https://docs.madelineproto.xyz/docs/LOGIN.html#manual-user
     */
    public function completeAuth(string $code, ?string $password2FA = null, ?string $firstName = null, ?string $lastName = null): array
    {
        // Перехватываем вывод
        $initialObLevel = ob_get_level();
        ob_start();
        
        try {
            $client = $this->getClient();
            
            if (!$client) {
                while (ob_get_level() > $initialObLevel) {
                    ob_end_clean();
                }
                throw new \Exception('Telegram клиент не инициализирован');
            }

            Log::info('Попытка завершения авторизации с кодом');
            
            $authorization = $client->completePhoneLogin($code);
            
            // Очищаем буфер вывода
            while (ob_get_level() > $initialObLevel) {
                ob_end_clean();
            }
            
            // Проверяем результат согласно документации
            if (isset($authorization['_'])) {
                $authType = $authorization['_'];
                
                // Требуется пароль 2FA
                if ($authType === 'account.password') {
                    if ($password2FA) {
                        // Завершаем 2FA авторизацию
                        ob_start();
                        try {
                            $authorization = $client->complete2falogin($password2FA);
                            while (ob_get_level() > $initialObLevel) {
                                ob_end_clean();
                            }
                            Log::info('2FA авторизация завершена');
                        } catch (\Exception $e) {
                            while (ob_get_level() > $initialObLevel) {
                                ob_end_clean();
                            }
                            Log::error('Ошибка 2FA авторизации: ' . $e->getMessage());
                            return ['success' => false, 'message' => 'Ошибка 2FA авторизации: ' . $e->getMessage()];
                        }
                    } else {
                        // Возвращаем информацию о необходимости 2FA
                        return [
                            'success' => false,
                            'needs_2fa' => true,
                            'hint' => $authorization['hint'] ?? null,
                            'message' => 'Требуется пароль двухфакторной аутентификации'
                        ];
                    }
                }
                
                // Требуется регистрация (signup) - не поддерживается, показываем ошибку
                if ($authType === 'account.needSignup') {
                    return [
                        'success' => false,
                        'message' => 'Аккаунт не существует. Используйте существующий аккаунт Telegram.'
                    ];
                }
            }
            
            // Проверяем успешность авторизации
            try {
                $self = $client->getSelf();
                if ($self) {
                    $this->isAuthorizedCache = true; // Обновляем кеш
                    $userId = is_array($self) ? ($self['id'] ?? null) : (isset($self->id) ? $self->id : null);
                    Log::info('Авторизация в Telegram завершена успешно', ['user_id' => $userId ?? 'unknown']);
                    
                    return [
                        'success' => true,
                        'user_id' => $userId,
                        'first_name' => is_array($self) ? ($self['first_name'] ?? null) : (isset($self->first_name) ? $self->first_name : null),
                        'last_name' => is_array($self) ? ($self['last_name'] ?? null) : (isset($self->last_name) ? $self->last_name : null),
                        'username' => is_array($self) ? ($self['username'] ?? null) : (isset($self->username) ? $self->username : null),
                        'phone' => is_array($self) ? ($self['phone'] ?? null) : (isset($self->phone) ? $self->phone : null),
                    ];
                } else {
                    $this->isAuthorizedCache = false;
                    Log::warning('completePhoneLogin вернул результат, но getSelf() не вернул данные');
                    return ['success' => false, 'message' => 'Авторизация не завершена'];
                }
            } catch (\Exception $e) {
                $this->isAuthorizedCache = false;
                Log::warning('Не удалось проверить авторизацию после completePhoneLogin: ' . $e->getMessage());
                return ['success' => false, 'message' => 'Ошибка проверки авторизации: ' . $e->getMessage()];
            }
        } catch (\Exception $e) {
            // Очищаем все буферы вывода при ошибке
            while (ob_get_level() > $initialObLevel) {
                ob_end_clean();
            }
            
            Log::error('Ошибка завершения авторизации: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            // Проверяем тип ошибки
            $errorMessage = $e->getMessage();
            
            // Ошибки кода авторизации
            if (strpos($errorMessage, 'PHONE_CODE_INVALID') !== false) {
                return ['success' => false, 'message' => 'Неверный код авторизации. Проверьте код и попробуйте снова.'];
            } elseif (strpos($errorMessage, 'PHONE_CODE_EXPIRED') !== false) {
                return ['success' => false, 'message' => 'Код авторизации истек. Запросите новый код.'];
            } elseif (strpos($errorMessage, 'PHONE_CODE_EMPTY') !== false) {
                return ['success' => false, 'message' => 'Код не был введен. Введите код из Telegram.'];
            } elseif (strpos($errorMessage, 'SESSION_PASSWORD_NEEDED') !== false) {
                return ['success' => false, 'needs_2fa' => true, 'message' => 'Требуется пароль двухфакторной аутентификации.'];
            } elseif (strpos($errorMessage, 'код') !== false) {
                return ['success' => false, 'message' => 'Ошибка проверки кода: ' . $errorMessage];
            }
            
            return ['success' => false, 'message' => $errorMessage];
        }
    }

    /**
     * Отправить сообщение в Telegram
     */
    public function sendMessage(int $chatId, string $message = '', $attachments = []): bool
    {
        // Перехватываем вывод, так как методы MadelineProto могут вывести HTML
        ob_start();
        
        try {
            $client = $this->getClient();
            
            if (!$client) {
                if (ob_get_level() > 0) {
                    ob_end_clean();
                }
                Log::error("Telegram Client не инициализирован для отправки сообщения");
                return false;
            }

            // НЕ вызываем start() - используем manual login
            // Проверяем авторизацию перед отправкой (используем кеш, если доступен)
            if ($this->isAuthorizedCache === false) {
                if (ob_get_level() > 0) {
                    ob_end_clean();
                }
                Log::error("Telegram Client не авторизован для отправки сообщения (кеш показывает неавторизован)");
                return false;
            }
            
            // Если кеш не установлен, проверяем авторизацию
            if ($this->isAuthorizedCache === null) {
                try {
                    $self = $client->getSelf();
                    if (!$self) {
                        $this->isAuthorizedCache = false;
                        if (ob_get_level() > 0) {
                            ob_end_clean();
                        }
                        Log::error("Telegram Client не авторизован для отправки сообщения (getSelf() вернул null)");
                        return false;
                    }
                    $this->isAuthorizedCache = true;
                } catch (\Exception $e) {
                    $this->isAuthorizedCache = false;
                    if (ob_get_level() > 0) {
                        ob_end_clean();
                    }
                    Log::error("Telegram Client не авторизован: " . $e->getMessage(), [
                        'error_type' => get_class($e),
                        'trace' => substr($e->getTraceAsString(), 0, 500)
                    ]);
                    return false;
                }
            }

            $sentSomething = false;
            $textSent = false;
            $attachmentsSent = 0;
            $attachmentsFailed = 0;

            // Отправляем текстовое сообщение, если оно есть
            if ($message !== '') {
                try {
                    // Убеждаемся, что peer правильного формата (может быть числом или массивом)
                    $peer = $chatId;
                    
                    // В MadelineProto peer должен быть числом для пользователей
                    if (!is_numeric($peer)) {
                        Log::error("Некорректный формат peer для отправки сообщения", [
                            'chat_id' => $chatId,
                            'peer_type' => gettype($peer)
                        ]);
                        throw new \Exception("Некорректный формат peer: ожидается число, получен " . gettype($peer));
                    }
                    
                    $result = $client->messages->sendMessage(
                        peer: (int) $peer,
                        message: $message
                    );
                    
                    $sentSomething = true;
                    $textSent = true;
                    Log::info("Текстовое сообщение отправлено в Telegram чат: {$chatId}", [
                        'text_length' => strlen($message),
                        'result_id' => $result['id'] ?? $result['updates'][0]['id'] ?? null
                    ]);
                } catch (\Exception $e) {
                    Log::error("Ошибка отправки текстового сообщения в Telegram чат: {$chatId}", [
                        'error' => $e->getMessage(),
                        'error_type' => get_class($e),
                        'text_length' => strlen($message),
                        'chat_id' => $chatId,
                        'chat_id_type' => gettype($chatId),
                        'trace' => substr($e->getTraceAsString(), 0, 1000)
                    ]);
                    // Если есть вложения, продолжаем их отправку, даже если текст не отправился
                    // Если вложений нет, пробрасываем ошибку
                    if (empty($attachments)) {
                        throw $e;
                    }
                    // Иначе продолжаем отправку вложений
                }
            }

            // Отправляем вложения по очереди
            if (!empty($attachments)) {
                foreach ($attachments as $attachment) {
                    if ($attachment instanceof SupportMessageAttachment) {
                        try {
                            $result = $this->sendTelegramAttachment($client, $chatId, $attachment);
                            if ($result) {
                                $sentSomething = true;
                                $attachmentsSent++;
                                Log::info("Вложение отправлено в Telegram чат: {$chatId}", [
                                    'attachment_id' => $attachment->id,
                                    'file_name' => $attachment->file_name
                                ]);
                            } else {
                                $attachmentsFailed++;
                                Log::warning("Не удалось отправить вложение в Telegram чат: {$chatId}", [
                                    'attachment_id' => $attachment->id,
                                    'file_name' => $attachment->file_name
                                ]);
                            }
                        } catch (\Exception $e) {
                            $attachmentsFailed++;
                            Log::error("Ошибка отправки вложения в Telegram чат: {$chatId}", [
                                'attachment_id' => $attachment->id,
                                'file_name' => $attachment->file_name,
                                'error' => $e->getMessage()
                            ]);
                            // Продолжаем отправку остальных вложений, даже если одно не отправилось
                        }
                    }
                }
            }
            
            // Очищаем буфер вывода
            if (ob_get_level() > 0) {
                $output = ob_get_clean();
                
                // Если был вывод HTML, это проблема
                if (!empty($output) && (strpos($output, '<html') !== false || strpos($output, '<form') !== false)) {
                    Log::warning('MadelineProto вывел HTML при отправке сообщения');
                }
            }
            
            // Логируем итоговый результат отправки
            if ($sentSomething) {
                Log::info("Сообщение успешно отправлено в Telegram чат: {$chatId}", [
                    'text_sent' => $textSent,
                    'attachments_sent' => $attachmentsSent,
                    'attachments_failed' => $attachmentsFailed,
                    'total_attachments' => !empty($attachments) ? count($attachments) : 0
                ]);
            } else {
                Log::warning("Нечего отправлять в Telegram чат: {$chatId}", [
                    'has_text' => $message !== '',
                    'has_attachments' => !empty($attachments),
                    'attachments_count' => !empty($attachments) ? count($attachments) : 0
                ]);
            }
            
            return $sentSomething;
        } catch (\Exception $e) {
            // Очищаем все буферы вывода при ошибке
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            Log::error("Ошибка отправки сообщения в Telegram: " . $e->getMessage(), [
                'chat_id' => $chatId,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Получить новые сообщения из Telegram
     * Возвращает массив новых сообщений (только необработанные)
     */
    public function getNewMessages(): array
    {
        // Перехватываем вывод на весь метод, чтобы MadelineProto не вывел HTML
        ob_start();
        
        try {
            $client = $this->getClient();
            
            if (!$client) {
                ob_end_clean();
                Log::warning('Telegram Client не инициализирован');
                return [];
            }

            // НЕ вызываем start() - используем manual login
            // Проверяем авторизацию перед получением сообщений
            // Вывод уже перехвачен внешним ob_start()
            try {
                $self = $client->getSelf();
                
                if (!$self) {
                    ob_end_clean();
                    Log::warning('Telegram Client не авторизован. Выполните авторизацию через админ-панель.');
                    return [];
                }
                $userId = is_array($self) ? ($self['id'] ?? null) : (isset($self->id) ? $self->id : null);
                Log::debug('Telegram Client авторизован', ['user_id' => $userId ?? 'unknown']);
            } catch (\Exception $e) {
                ob_end_clean();
                Log::warning('Telegram Client не авторизован: ' . $e->getMessage(), [
                    'error_type' => get_class($e),
                    'trace' => substr($e->getTraceAsString(), 0, 500)
                ]);
                Log::info('Выполните авторизацию через админ-панель или команду: php artisan telegram:auth');
                return [];
            }

            Log::info('Начало получения сообщений из Telegram');
            
            // Получаем все диалоги через правильный API метод MadelineProto 8.x
            try {
                $dialogsResponse = $client->messages->getDialogs([
                    'limit' => 100, // Получаем больше диалогов для надежности
                ]);
            } catch (\Exception $e) {
                Log::warning('Ошибка получения диалогов: ' . $e->getMessage());
                if (ob_get_level() > 0) {
                    ob_end_clean();
                }
                return [];
            }
            
            $dialogs = $dialogsResponse['dialogs'] ?? [];
            $users = $dialogsResponse['users'] ?? [];
            $chats = $dialogsResponse['chats'] ?? [];
            
            // Если диалоги не получены, пробуем еще раз с другими параметрами
            if (empty($dialogs)) {
                Log::warning('Диалоги не получены, повторная попытка...');
                sleep(1);
                try {
                    $dialogsResponse = $client->messages->getDialogs([
                        'limit' => 50, // Пробуем с меньшим лимитом
                        'offset_date' => 0,
                        'offset_id' => 0,
                        'offset_peer' => ['_' => 'inputPeerEmpty'],
                    ]);
                    $dialogs = $dialogsResponse['dialogs'] ?? [];
                    $users = $dialogsResponse['users'] ?? [];
                    $chats = $dialogsResponse['chats'] ?? [];
                } catch (\Exception $e) {
                    Log::warning('Ошибка повторного получения диалогов: ' . $e->getMessage());
                    if (ob_get_level() > 0) {
                        ob_end_clean();
                    }
                    return [];
                }
            }
            
            if (empty($dialogs)) {
                Log::info('Диалоги не найдены. Это нормально, если еще не было сообщений.');
                if (ob_get_level() > 0) {
                    ob_end_clean();
                }
                return [];
            }
            
            Log::info('Найдено диалогов: ' . count($dialogs));
            Log::info('Найдено пользователей: ' . count($users));
            Log::info('Найдено чатов/групп: ' . count($chats));
            
            $newMessages = [];
            $processedDialogs = 0;
            $skippedDialogs = 0;

            foreach ($dialogs as $dialog) {
                try {
                    $peer = $dialog['peer'] ?? null;
                    
                    // Получаем только личные сообщения (не группы/каналы)
                    if (!$peer) {
                        Log::debug("Пропущен диалог: peer отсутствует");
                        continue;
                    }
                    
                    // Определяем тип peer и получаем ID пользователя
                    // В MadelineProto 8.x peer может быть числом или массивом
                    $userId = null;
                    
                    // Если peer - просто число (новый формат MadelineProto 8.x)
                    if (is_numeric($peer)) {
                        $userId = (int)$peer;
                        
                        // Проверяем тип по ID:
                        // Положительные ID (< 1000000000000) - пользователи
                        // Отрицательные ID или очень большие - группы/каналы
                        if ($userId < 0) {
                            $skippedDialogs++;
                            Log::debug("Пропущен диалог: группа/канал с отрицательным ID {$userId}");
                            continue;
                        } elseif ($userId >= 1000000000000) {
                            // Очень большой ID - обычно это канал/супергруппа
                            $skippedDialogs++;
                            Log::debug("Пропущен диалог: канал/супергруппа с ID {$userId}");
                            continue;
                        }
                        // Иначе это обычный пользователь
                    }
                    // Если peer - массив (старый формат или особый случай)
                    elseif (is_array($peer)) {
                        $peerType = $peer['_'] ?? null;
                        
                        if ($peerType === 'peerUser') {
                            $userId = $peer['user_id'] ?? null;
                        } elseif ($peerType === 'peerChat') {
                            $skippedDialogs++;
                            $chatId = $peer['chat_id'] ?? 'unknown';
                            Log::debug("Пропущен диалог типа peerChat (группа, ID: {$chatId})");
                            continue;
                        } elseif ($peerType === 'peerChannel') {
                            $skippedDialogs++;
                            $channelId = $peer['channel_id'] ?? 'unknown';
                            Log::debug("Пропущен диалог типа peerChannel (канал, ID: {$channelId})");
                            continue;
                        } else {
                            // Попытка извлечь user_id напрямую
                            $userId = $peer['user_id'] ?? $peer['id'] ?? null;
                        }
                    }
                    
                    if (!$userId) {
                        $skippedDialogs++;
                        Log::debug("Пропущен диалог: не удалось определить user_id", [
                            'peer_type' => gettype($peer),
                            'peer_value' => is_scalar($peer) ? $peer : json_encode($peer)
                        ]);
                        continue;
                    }
                    
                    // Дополнительная валидация user_id
                    if (!is_numeric($userId) || $userId <= 0) {
                        $skippedDialogs++;
                        Log::debug("Пропущен диалог: некорректный user_id: {$userId}");
                        continue;
                    }
                    
                    $processedDialogs++;
                    Log::info("Обработка диалога с пользователем ID: {$userId}");
                    
                    // Получаем последние сообщения (используем await)
                    try {
                        $messages = $client->messages->getHistory(
                            peer: $userId,
                            limit: 50 // Увеличиваем лимит для надежности
                        );
                    } catch (\Exception $e) {
                        Log::warning("Ошибка получения истории для пользователя {$userId}: " . $e->getMessage());
                        continue;
                    }

                    if (!isset($messages['messages']) || !is_array($messages['messages'])) {
                        continue;
                    }

                    Log::debug("Получено сообщений для пользователя {$userId}: " . count($messages['messages']));
                    
                    // Получаем информацию о пользователе для логирования
                    $userInfo = null;
                    foreach ($users as $user) {
                        if (isset($user['id']) && $user['id'] == $userId) {
                            $userInfo = $user;
                            break;
                        }
                    }
                    $userName = $userInfo['first_name'] ?? $userInfo['username'] ?? "User #{$userId}";
                    Log::debug("Обработка диалога с пользователем: {$userName} (ID: {$userId})");

                    foreach ($messages['messages'] as $msg) {
                        // Проверяем, что это входящее сообщение (не наше)
                        // В MadelineProto поле 'out' указывает, что сообщение отправлено нами
                        // Если 'out' === true, пропускаем
                        if (isset($msg['out']) && $msg['out'] === true) {
                            continue;
                        }

                        $messageId = $msg['id'] ?? null;
                        if (!$messageId) {
                            continue;
                        }
                        
                        // Проверяем, не обработали ли мы уже это сообщение
                        if (SupportMessage::where('telegram_message_id', $messageId)->exists()) {
                            continue;
                        }

                        $rawMessage = $msg['message'] ?? '';
                        $messageText = trim((string) $rawMessage);
                        $hasText = $messageText !== '';
                        $hasMedia = $this->messageHasMedia($msg);

                        // Пропускаем пустые сообщения без медиа
                        if (!$hasText && !$hasMedia) {
                            continue;
                        }

                        // Скачиваем вложения, если есть
                        $attachments = [];
                        if ($hasMedia) {
                            try {
                                $attachments = $this->downloadTelegramAttachments($client, $msg);
                            } catch (\Exception $e) {
                                Log::warning("Ошибка скачивания вложений из Telegram сообщения: " . $e->getMessage(), [
                                    'message_id' => $messageId,
                                    'chat_id' => $userId
                                ]);
                                // Продолжаем обработку, даже если вложения не скачались
                            }
                        }

                        $newMessages[] = [
                            'chat_id' => $userId,
                            'message_id' => $messageId,
                            'text' => $messageText,
                            'date' => $msg['date'] ?? now()->timestamp,
                            'attachments' => $attachments,
                        ];
                        
                        Log::info("Найдено новое сообщение от пользователя {$userId}", [
                            'message_id' => $messageId,
                            'has_text' => $hasText,
                            'has_media' => $hasMedia,
                            'text_preview' => substr($messageText ?: '[media]', 0, 50)
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning("Ошибка обработки диалога: " . $e->getMessage());
                    continue;
                }
            }

            Log::info('Получение сообщений завершено', [
                'new_messages_count' => count($newMessages),
                'processed_dialogs' => $processedDialogs,
                'skipped_dialogs' => $skippedDialogs,
                'total_dialogs' => count($dialogs)
            ]);

            $output = ob_get_clean();
            
            // Если был вывод HTML, логируем это
            if (!empty($output) && (strpos($output, '<html') !== false || strpos($output, '<form') !== false)) {
                Log::warning('MadelineProto вывел HTML при получении сообщений');
            }

            return $newMessages;
        } catch (\Exception $e) {
            ob_end_clean();
            Log::error('Ошибка получения сообщений из Telegram: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return [];
        }
    }

    /**
     * Обработать входящее сообщение из Telegram и создать/обновить чат
     */
    public function processIncomingMessage(array $messageData): ?SupportChat
    {
        try {
            $chatId = $messageData['chat_id'];
            $text = trim((string) ($messageData['text'] ?? ''));
            $messageId = $messageData['message_id'] ?? null;
            $attachments = $messageData['attachments'] ?? [];

            // Если нет текста и вложений — ничего обрабатывать
            if ($text === '' && empty($attachments)) {
                Log::debug("Пропущено пустое сообщение из чата {$chatId}");
                return null;
            }

            // Дубли сообщений предотвращаем через telegram_message_id
            if ($messageId) {
                $existingMessage = SupportMessage::where('telegram_message_id', $messageId)->first();
                if ($existingMessage) {
                    Log::debug("Сообщение с telegram_message_id={$messageId} уже существует (ID: {$existingMessage->id}), пропускаем");
                    // Возвращаем чат, чтобы обновить last_message_at
                    $chat = SupportChat::where('telegram_chat_id', $chatId)
                        ->where('source', SupportChat::SOURCE_TELEGRAM)
                        ->notClosed()
                        ->first();
                    if ($chat) {
                        $chat->update(['last_message_at' => now()]);
                    }
                    return $chat;
                }
            }

            // Ищем существующий чат по telegram_chat_id (включая закрытые)
            $chat = SupportChat::where('telegram_chat_id', $chatId)
                ->where('source', SupportChat::SOURCE_TELEGRAM)
                ->orderBy('created_at', 'desc')
                ->first();

            // Если чат был закрыт, открываем новый
            if ($chat && $chat->status === SupportChat::STATUS_CLOSED) {
                Log::info("Чат #{$chat->id} был закрыт, создаем новый для Telegram ID: {$chatId}");
                $chat = null;
            }

            // Если чата нет, создаем новый
            if (!$chat) {
                // Пытаемся найти пользователя по telegram_id
                $user = User::where('telegram_id', $chatId)->first();

                if ($user) {
                    Log::info("Создание нового чата для пользователя #{$user->id} (Telegram ID: {$chatId})");
                } else {
                    Log::info("Создание нового гостевого чата для Telegram ID: {$chatId}");
                }

                // Получаем информацию о пользователе из Telegram (имя и фото, БЕЗ никнейма)
                $userInfo = $this->getUserInfo($chatId);
                
                $telegramFirstName = $userInfo['first_name'] ?? null;
                $telegramLastName = $userInfo['last_name'] ?? null;
                $telegramPhoto = $userInfo['photo_path'] ?? null;
                
                // Формируем отображаемое имя
                $displayName = null;
                if ($telegramFirstName) {
                    $displayName = $telegramFirstName;
                    if ($telegramLastName) {
                        $displayName .= ' ' . $telegramLastName;
                    }
                } else {
                    // Если не удалось получить имя, используем "User N"
                    $telegramChatsCount = SupportChat::where('source', SupportChat::SOURCE_TELEGRAM)->count();
                    $displayName = "User " . ($telegramChatsCount + 1);
                }

                $chat = SupportChat::create([
                    'user_id' => $user?->id,
                    'source' => SupportChat::SOURCE_TELEGRAM,
                    'telegram_chat_id' => $chatId,
                    'status' => SupportChat::STATUS_PENDING,
                    'guest_name' => $user ? null : $displayName,
                    'guest_email' => $user ? null : "tg{$chatId}@telegram.local",
                    'telegram_first_name' => $telegramFirstName,
                    'telegram_last_name' => $telegramLastName,
                    'telegram_photo' => $telegramPhoto,
                    'last_message_at' => now(),
                ]);
                
                Log::info("Создан чат #{$chat->id} для Telegram пользователя: {$displayName}");
            }

            $messageTimestamp = isset($messageData['date'])
                ? Carbon::createFromTimestamp($messageData['date'])->timezone(config('app.timezone'))
                : now();

            $content = $text !== '' ? $text : '[Вложение из Telegram]';

            // Создаем сообщение и сохраняем идентификатор Telegram
            $supportMessage = new SupportMessage([
                'support_chat_id' => $chat->id,
                'user_id' => $chat->user_id,
                'sender_type' => $chat->user_id ? SupportMessage::SENDER_USER : SupportMessage::SENDER_GUEST,
                'message' => $content,
                'telegram_message_id' => $messageId,
                'is_read' => false,
            ]);

            $supportMessage->created_at = $messageTimestamp;
            $supportMessage->updated_at = $messageTimestamp;
            $supportMessage->save();

            Log::info("Создано сообщение #{$supportMessage->id} в чате #{$chat->id}", [
                'telegram_message_id' => $messageId,
                'has_text' => $text !== '',
                'attachments_count' => count($attachments)
            ]);

            if (!empty($attachments)) {
                foreach ($attachments as $attachment) {
                    SupportMessageAttachment::create([
                        'support_message_id' => $supportMessage->id,
                        'file_name' => $attachment['file_name'] ?? 'file',
                        'file_path' => $attachment['file_path'] ?? null,
                        'file_url' => $attachment['file_url'] ?? null,
                        'mime_type' => $attachment['mime_type'] ?? 'application/octet-stream',
                        'file_size' => $attachment['file_size'] ?? null,
                    ]);
                }
                Log::info("Добавлено вложений: " . count($attachments), [
                    'message_id' => $supportMessage->id
                ]);
            }

            // Обновляем время последнего сообщения и открываем чат
            $chat->update([
                'last_message_at' => now(),
                'status' => SupportChat::STATUS_OPEN,
            ]);

            return $chat;
        } catch (\Exception $e) {
            Log::error("Ошибка обработки входящего сообщения из Telegram", [
                'error' => $e->getMessage(),
                'chat_id' => $messageData['chat_id'] ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Проверить наличие медиа во входящем сообщении
     */
    private function messageHasMedia(array $message): bool
    {
        if (!isset($message['media']) || !is_array($message['media'])) {
            return false;
        }

        return ($message['media']['_'] ?? 'messageMediaEmpty') !== 'messageMediaEmpty';
    }

    /**
     * Скачать вложения из Telegram и подготовить данные для сохранения
     */
    private function downloadTelegramAttachments(API $client, array $message): array
    {
        if (!$this->messageHasMedia($message)) {
            return [];
        }

        // Сохраняем напрямую в public (без символических ссылок для совместимости с Windows)
        $directory = 'support-chat/attachments/' . date('Y/m');
        $fullDirectory = public_path($directory);
        
        if (!file_exists($fullDirectory)) {
            mkdir($fullDirectory, 0755, true);
        }

        try {
            $savedPath = $client->downloadToDir($message, $fullDirectory);
        } catch (\Throwable $e) {
            Log::warning('Не удалось скачать вложение из Telegram', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }

        if (!$savedPath || !file_exists($savedPath)) {
            Log::warning('Файл не был сохранен или не существует', [
                'saved_path' => $savedPath
            ]);
            return [];
        }

        $relativePath = Str::after($savedPath, public_path() . DIRECTORY_SEPARATOR);
        // Нормализуем путь (заменяем обратные слэши на прямые для URL)
        $relativePath = str_replace('\\', '/', $relativePath);
        $fileName = basename($savedPath);
        $mimeType = mime_content_type($savedPath) ?: 'application/octet-stream';
        $fileSize = filesize($savedPath) ?: null;
        $fileUrl = asset($relativePath);

        Log::info('Вложение из Telegram скачано', [
            'file_name' => $fileName,
            'file_path' => $relativePath,
            'file_url' => $fileUrl,
            'mime_type' => $mimeType,
            'size' => $fileSize
        ]);

        return [[
            'file_name' => $fileName,
            'file_path' => $relativePath,
            'file_url' => $fileUrl,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
        ]];
    }

    /**
     * Получить информацию о пользователе Telegram (БЕЗ username для защиты конфиденциальности)
     */
    public function getUserInfo(int $userId): array
    {
        $client = $this->getClient();
        
        if (!$client) {
            return [];
        }

        try {
            ob_start();
            $fullInfo = $client->getFullInfo($userId);
            ob_end_clean();
            
            $firstName = $fullInfo['User']['first_name'] ?? null;
            $lastName = $fullInfo['User']['last_name'] ?? null;
            
            // Получаем фото профиля (БЕЗ username для безопасности)
            $photoPath = null;
            if (isset($fullInfo['User']['photo']) && isset($fullInfo['User']['photo']['_'])) {
                $photoPath = $this->downloadUserPhoto($client, $userId, $fullInfo['User']);
            }
            
            // Возвращаем только безопасную информацию (БЕЗ username)
            return [
                'id' => $userId,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'photo_path' => $photoPath,
                // Намеренно НЕ возвращаем username для защиты конфиденциальности
            ];
        } catch (\Exception $e) {
            Log::error("Ошибка получения информации о пользователе: " . $e->getMessage());
            return ['id' => $userId];
        }
    }

    /**
     * Сбросить сессию Telegram (для переключения аккаунтов)
     */
    public function resetSession(): bool
    {
        try {
            // Закрываем текущий клиент, если он открыт
            if ($this->madeline !== null) {
                try {
                    // Проверяем, есть ли метод stop()
                    if (method_exists($this->madeline, 'stop')) {
                        $this->madeline->stop();
                    }
                } catch (\Exception $e) {
                    // Игнорируем ошибки при остановке
                    Log::debug('Ошибка при остановке клиента (можно игнорировать): ' . $e->getMessage());
                }
                $this->madeline = null;
            }
            
            // Очищаем кеш авторизации
            $this->isAuthorizedCache = null;

            // Удаляем файл сессии
            if (file_exists($this->sessionPath)) {
                @unlink($this->sessionPath);
                Log::info('Сессия Telegram удалена: ' . $this->sessionPath);
            }

            // Удаляем все связанные файлы сессии (MadelineProto может создавать несколько файлов)
            $sessionDir = dirname($this->sessionPath);
            $sessionBase = basename($this->sessionPath, '.madeline');
            
            if (is_dir($sessionDir)) {
                $files = glob($sessionDir . '/' . $sessionBase . '*');
                if ($files) {
                    foreach ($files as $file) {
                        if (is_file($file)) {
                            @unlink($file);
                            Log::info('Удален файл сессии: ' . $file);
                        }
                    }
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Ошибка сброса сессии Telegram: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            throw $e; // Пробрасываем исключение, чтобы контроллер мог его обработать
        }
    }

    /**
     * Отправить одиночное вложение в Telegram чат
     */
    private function sendTelegramAttachment(API $client, int $chatId, SupportMessageAttachment $attachment): bool
    {
        if (!$attachment->file_path) {
            return false;
        }

        // Файлы теперь хранятся напрямую в public/ (без storage/)
        $absolutePath = public_path($attachment->file_path);

        if (!is_file($absolutePath)) {
            Log::warning('Вложение не найдено на диске для отправки в Telegram', [
                'path' => $absolutePath,
                'file_path' => $attachment->file_path,
            ]);
            return false;
        }

        try {
            // Убеждаемся, что peer правильного формата
            $peer = (int) $chatId;
            
            $uploadedFile = $client->upload($absolutePath);
            $mimeType = $attachment->mime_type ?: mime_content_type($absolutePath) ?: 'application/octet-stream';
            $fileName = $attachment->file_name ?: basename($absolutePath);
            $isImage = Str::startsWith($mimeType, 'image/');

            if ($isImage) {
                $result = $client->messages->sendMedia(
                    peer: $peer,
                    media: [
                        '_' => 'inputMediaUploadedPhoto',
                        'file' => $uploadedFile,
                    ],
                    message: ''
                );
                Log::debug("Изображение отправлено в Telegram", [
                    'chat_id' => $chatId,
                    'result_id' => $result['id'] ?? $result['updates'][0]['id'] ?? null
                ]);
            } else {
                $result = $client->messages->sendMedia(
                    peer: $peer,
                    media: [
                        '_' => 'inputMediaUploadedDocument',
                        'file' => $uploadedFile,
                        'mime_type' => $mimeType,
                        'attributes' => [
                            [
                                '_' => 'documentAttributeFilename',
                                'file_name' => $fileName,
                            ],
                        ],
                    ],
                    message: $fileName
                );
                Log::debug("Документ отправлен в Telegram", [
                    'chat_id' => $chatId,
                    'file_name' => $fileName,
                    'result_id' => $result['id'] ?? $result['updates'][0]['id'] ?? null
                ]);
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Не удалось отправить вложение в Telegram', [
                'chat_id' => $chatId,
                'attachment_id' => $attachment->id,
                'file_name' => $attachment->file_name,
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'trace' => substr($e->getTraceAsString(), 0, 500)
            ]);

            return false;
        }
    }

    /**
     * Скачать фото профиля пользователя Telegram
     */
    private function downloadUserPhoto($client, int $userId, array $user): ?string
    {
        try {
            // Проверяем наличие фото
            if (!isset($user['photo']) || !isset($user['photo']['_'])) {
                Log::debug("У пользователя {$userId} нет фото профиля");
                return null;
            }

            // Создаем директорию для аватарок напрямую в public (без символических ссылок)
            $avatarsDir = public_path('telegram/avatars');
            if (!file_exists($avatarsDir)) {
                mkdir($avatarsDir, 0755, true);
            }

            // Генерируем имя файла
            $fileName = "user_{$userId}_" . time() . ".jpg";
            $filePath = $avatarsDir . '/' . $fileName;

            ob_start();
            try {
                // Скачиваем фото напрямую через структуру пользователя
                // MadelineProto сам разберется как скачать фото профиля
                $client->downloadToFile($user, $filePath);
                ob_end_clean();

                // Проверяем, что файл существует и имеет размер больше 0
                if (file_exists($filePath) && filesize($filePath) > 0) {
                    Log::info("Успешно скачано фото пользователя {$userId}, размер: " . filesize($filePath) . " байт");
                    // Возвращаем относительный путь для хранения в БД (без storage/)
                    return 'telegram/avatars/' . $fileName;
                } else {
                    // Удаляем пустой файл
                    if (file_exists($filePath)) {
                        @unlink($filePath);
                    }
                    Log::warning("Фото пользователя {$userId} не скачалось или имеет нулевой размер");
                    return null;
                }
            } catch (\Exception $e) {
                ob_end_clean();
                // Удаляем файл при ошибке
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
                Log::warning("Не удалось скачать фото пользователя {$userId}: " . $e->getMessage());
                return null;
            }
        } catch (\Exception $e) {
            Log::error("Критическая ошибка при скачивании фото пользователя {$userId}: " . $e->getMessage());
            return null;
        }
    }
}

