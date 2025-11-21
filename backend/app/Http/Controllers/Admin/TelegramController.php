<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TelegramClientService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramController extends Controller
{
    /**
     * Проверить статус авторизации
     */
    public function checkAuthStatus(TelegramClientService $telegramService)
    {
        // Перехватываем весь вывод, чтобы MadelineProto не выводил HTML
        ob_start();
        
        try {
            $client = $telegramService->getClient();
            
            // getClient() уже перехватывает и очищает вывод внутри себя,
            // но мы продолжаем перехватывать на случай, если getSelf() что-то выведет
            
            if (!$client) {
                if (ob_get_level() > 0) {
                    ob_end_clean();
                }
                Log::warning('Telegram Client не инициализирован в checkAuthStatus');
                return response()->json([
                    'authorized' => false,
                    'message' => 'Telegram Client не инициализирован. Проверьте настройки API ID и API Hash.'
                ]);
            }

            // getSelf() на уже инициализированном клиенте не должен ничего выводить,
            // но на всякий случай продолжаем перехватывать вывод
            try {
                $self = $client->getSelf();
                
                // getClient() уже очистил свой буфер, но мы продолжаем перехватывать на случай,
                // если getSelf() что-то выведет (хотя этого не должно быть)
                // Просто очищаем буфер, не получая его содержимое
                if (ob_get_level() > 0) {
                    ob_end_clean();
                }
                
                if ($self && is_array($self)) {
                    // MadelineProto возвращает массив с данными пользователя
                    return response()->json([
                        'authorized' => true,
                        'user_id' => $self['id'] ?? $self['user_id'] ?? null,
                        'first_name' => $self['first_name'] ?? null,
                        'last_name' => $self['last_name'] ?? null,
                        'username' => $self['username'] ?? null,
                        'phone' => $self['phone'] ?? null,
                    ]);
                } elseif ($self) {
                    // Если это объект, пробуем получить данные через свойства
                    return response()->json([
                        'authorized' => true,
                        'user_id' => isset($self->id) ? $self->id : (isset($self['id']) ? $self['id'] : null),
                        'first_name' => isset($self->first_name) ? $self->first_name : (isset($self['first_name']) ? $self['first_name'] : null),
                        'last_name' => isset($self->last_name) ? $self->last_name : (isset($self['last_name']) ? $self['last_name'] : null),
                        'username' => isset($self->username) ? $self->username : (isset($self['username']) ? $self['username'] : null),
                        'phone' => isset($self->phone) ? $self->phone : (isset($self['phone']) ? $self['phone'] : null),
                    ]);
                }
            } catch (\Exception $e) {
                if (ob_get_level() > 0) {
                    ob_end_clean();
                }
                
                // Это нормально, если пользователь не авторизован
                // Но логируем для отладки, чтобы понять, какая именно ошибка
                $errorMessage = $e->getMessage();
                Log::debug('Telegram авторизация не выполнена в checkAuthStatus', [
                    'error' => $errorMessage,
                    'error_class' => get_class($e)
                ]);
                
                // Если это ошибка инициализации или другая критическая ошибка, 
                // возвращаем более информативное сообщение
                if (strpos($errorMessage, 'not authorized') !== false || 
                    strpos($errorMessage, 'AUTH_KEY') !== false ||
                    strpos($errorMessage, 'SESSION_PASSWORD_NEEDED') !== false) {
                    return response()->json([
                        'authorized' => false,
                        'message' => 'Требуется авторизация. Нажмите "Начать авторизацию" для входа.'
                    ]);
                }
                
                // Для других ошибок возвращаем общее сообщение
                return response()->json([
                    'authorized' => false,
                    'message' => 'Требуется авторизация. Нажмите "Начать авторизацию" для входа.'
                ]);
            }

            ob_end_clean();
            return response()->json([
                'authorized' => false,
                'message' => 'Не авторизован'
            ]);
        } catch (\Exception $e) {
            // Очищаем вывод даже при ошибке
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            $errorMessage = $e->getMessage();
            $errorClass = get_class($e);
            
            Log::error('Ошибка проверки статуса Telegram авторизации', [
                'error' => $errorMessage,
                'error_class' => $errorClass,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Если это ошибка, связанная с авторизацией, возвращаем соответствующее сообщение
            if (strpos($errorMessage, 'not authorized') !== false || 
                strpos($errorMessage, 'AUTH_KEY') !== false ||
                strpos($errorMessage, 'SESSION') !== false) {
                return response()->json([
                    'authorized' => false,
                    'message' => 'Требуется авторизация. Нажмите "Начать авторизацию" для входа.'
                ]);
            }
            
            // Для других ошибок возвращаем сообщение с описанием ошибки
            return response()->json([
                'authorized' => false,
                'message' => 'Ошибка проверки статуса: ' . $errorMessage
            ]);
        }
    }

    /**
     * Начать авторизацию (отправить код)
     */
    public function startAuth(TelegramClientService $telegramService)
    {
        // Перехватываем весь вывод, чтобы MadelineProto не выводил HTML
        // Начинаем перехват вывода ДО любых операций
        ob_start();
        
        try {
            $telegramService->authorize();
            
            // Очищаем вывод
            if (ob_get_level() > 0) {
                $output = ob_get_clean();
                
                // Если был вывод HTML, логируем это
                if (!empty($output) && (strpos($output, '<html') !== false || strpos($output, '<form') !== false)) {
                    Log::warning('MadelineProto вывел HTML при начале авторизации', [
                        'output_length' => strlen($output),
                        'output_preview' => substr($output, 0, 200)
                    ]);
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Код отправлен в Telegram. Проверьте приложение Telegram и введите полученный код.'
            ]);
        } catch (\Exception $e) {
            // Очищаем все буферы вывода
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            Log::error('Ошибка начала авторизации Telegram', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $message = $e->getMessage();
            $statusCode = 500;
            
            // Более понятные сообщения об ошибках
            if (strpos($message, 'Уже авторизован') !== false) {
                $statusCode = 400;
            } elseif (strpos($message, 'не настроен') !== false || strpos($message, 'не инициализирован') !== false) {
                $statusCode = 400;
            } elseif (strpos($message, 'Авторизация уже начата') !== false) {
                $statusCode = 400;
            }
            
            return response()->json([
                'success' => false,
                'message' => $message
            ], $statusCode);
        }
    }

    /**
     * Завершить авторизацию с кодом
     * Согласно документации: https://docs.madelineproto.xyz/docs/LOGIN.html#manual-user
     */
    public function completeAuth(Request $request, TelegramClientService $telegramService)
    {
        $request->validate([
            'code' => 'required|string|max:10',
            'password_2fa' => 'nullable|string',
        ]);

        // Запоминаем начальный уровень буфера
        $initialObLevel = ob_get_level();
        ob_start();
        
        try {
            $result = $telegramService->completeAuth(
                $request->code,
                $request->input('password_2fa')
            );
            
            // Очищаем вывод
            while (ob_get_level() > $initialObLevel) {
                ob_end_clean();
            }
            
            // Результат теперь массив
            if (isset($result['success']) && $result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Авторизация успешно завершена! Теперь вы можете получать сообщения из Telegram.',
                    'user' => [
                        'user_id' => $result['user_id'] ?? null,
                        'first_name' => $result['first_name'] ?? null,
                        'last_name' => $result['last_name'] ?? null,
                        'username' => $result['username'] ?? null,
                        'phone' => $result['phone'] ?? null,
                    ]
                ]);
            } elseif (isset($result['needs_2fa']) && $result['needs_2fa']) {
                // Требуется 2FA пароль - возвращаем 200, чтобы обработалось в success
                return response()->json([
                    'success' => false,
                    'needs_2fa' => true,
                    'hint' => $result['hint'] ?? null,
                    'message' => $result['message'] ?? 'Требуется пароль двухфакторной аутентификации'
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Не удалось завершить авторизацию. Проверьте код и попробуйте снова.'
                ], 400);
            }
        } catch (\Exception $e) {
            // Очищаем все буферы вывода
            while (ob_get_level() > $initialObLevel) {
                ob_end_clean();
            }
            
            Log::error('Ошибка завершения авторизации Telegram', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $message = $e->getMessage();
            $statusCode = 500;
            
            // Определяем код ошибки в зависимости от типа ошибки
            if (strpos($message, 'Неверный код') !== false || 
                strpos($message, 'PHONE_CODE') !== false ||
                strpos($message, 'истек') !== false ||
                strpos($message, 'двухфакторной') !== false) {
                $statusCode = 400;
            }
            
            return response()->json([
                'success' => false,
                'message' => $message
            ], $statusCode);
        }
    }

    /**
     * Сбросить сессию Telegram (для переключения аккаунтов)
     */
    public function resetSession(TelegramClientService $telegramService)
    {
        // Перехватываем весь вывод
        ob_start();
        
        try {
            $telegramService->resetSession();
            $output = ob_get_clean();
            
            // Если был вывод HTML, логируем это
            if (!empty($output) && (strpos($output, '<html') !== false || strpos($output, '<form') !== false)) {
                Log::warning('MadelineProto вывел HTML при сбросе сессии', [
                    'output_length' => strlen($output)
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Сессия Telegram успешно сброшена. Теперь можно авторизоваться с другим аккаунтом.'
            ]);
        } catch (\Exception $e) {
            ob_end_clean();
            Log::error('Ошибка сброса сессии Telegram', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ручной запуск получения сообщений из Telegram
     */
    public function pollMessages(Request $request, TelegramClientService $telegramService)
    {
        // Перехватываем весь вывод, чтобы MadelineProto не вывел HTML
        ob_start();
        
        try {
            $messages = $telegramService->getNewMessages();
            $output = ob_get_clean();
            
            // Если был вывод HTML, логируем это
            if (!empty($output) && (strpos($output, '<html') !== false || strpos($output, '<form') !== false)) {
                Log::warning('MadelineProto вывел HTML при получении сообщений');
            }
            
            $processedCount = 0;
            $errors = [];
            
            foreach ($messages as $messageData) {
                try {
                    $chat = $telegramService->processIncomingMessage($messageData);
                    
                    if ($chat) {
                        $processedCount++;
                    }
                } catch (\Exception $e) {
                    $errors[] = $e->getMessage();
                    Log::error('Ошибка обработки Telegram сообщения', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            if (empty($messages)) {
                return redirect()->back()->with('info', 'Новых сообщений из Telegram не найдено');
            }
            
            $message = "Обработано сообщений: {$processedCount} из " . count($messages);
            if (!empty($errors)) {
                $message .= ". Ошибок: " . count($errors);
            }
            
            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Ошибка получения Telegram сообщений', [
                'error' => $e->getMessage(),
            ]);
            
            ob_end_clean();
            return redirect()->back()->with('error', 'Ошибка получения сообщений: ' . $e->getMessage());
        }
    }
}

