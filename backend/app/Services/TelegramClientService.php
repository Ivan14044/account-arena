<?php

namespace App\Services;

use App\Models\Option;
use App\Models\SupportChat;
use App\Models\SupportMessage;
use App\Models\SupportMessageAttachment;
use App\Models\User;
use Amp\Ipc\Sync\ChannelException;
use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\AppInfo;
use Illuminate\Support\Facades\Log;

class TelegramClientService
{
    private ?API $madeline = null;
    private string $sessionPath;
    private bool $enabled;
    private ?bool $isAuthorizedCache = null;

    public function __construct()
    {
        $this->enabled = Option::get('telegram_client_enabled', config('telegram.client.enabled', false));
        $this->sessionPath = config('telegram.client.session_path', storage_path('app/telegram/session.madeline'));
        
        $sessionDir = dirname($this->sessionPath);
        if (!is_dir($sessionDir)) {
            mkdir($sessionDir, 0755, true);
        }
    }

    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * Disconnect and cleanup
     */
    public function disconnect(): void
    {
        if ($this->madeline !== null) {
            try {
                // MadelineProto handles cleanup automatically
                $this->madeline = null;
                $this->isAuthorizedCache = null;
            } catch (\Exception $e) {
                Log::warning('Error cleaning up TelegramClientService: ' . $e->getMessage());
                $this->madeline = null;
                $this->isAuthorizedCache = null;
            }
        }
    }

    /**
     * Get or initialize MadelineProto client
     * According to docs: https://docs.madelineproto.xyz/docs/UPDATES.html#ipc
     */
    public function getClient(): ?API
    {
        if (!$this->enabled) {
            return null;
        }

        if ($this->madeline !== null) {
            return $this->madeline;
        }

        try {
            $apiId = Option::get('telegram_api_id', config('telegram.client.api_id'));
            $apiHash = Option::get('telegram_api_hash', config('telegram.client.api_hash'));

            if (!$apiId || !$apiHash) {
                Log::error('Telegram API ID or API Hash not configured');
                return null;
            }

            // Check if session exists - if not, we know we need to authorize
            $sessionExists = file_exists($this->sessionPath);
            
            $settings = new Settings();
            $appInfo = new AppInfo();
            $appInfo->setApiId((int) $apiId);
            $appInfo->setApiHash((string) $apiHash);
            $appInfo->setShowPrompt(false);
            $settings->setAppInfo($appInfo);
            
            // Create API instance - session is saved automatically by MadelineProto
            // If session doesn't exist, MadelineProto will create a new one
            $this->madeline = new API($this->sessionPath, $settings);
            
            // Only check authorization if session exists
            // If session was deleted, checkAuthorization will return false
            if ($sessionExists) {
                $this->checkAuthorization();
            } else {
                // No session = not authorized
                $this->isAuthorizedCache = false;
                Log::debug('No session file found, authorization required');
            }
            
            return $this->madeline;
        } catch (\Exception $e) {
            Log::error('Error initializing Telegram Client: ' . $e->getMessage());
            $this->madeline = null;
            return null;
        }
    }

    /**
     * Check authorization status with proper IPC channel handling
     */
    private function checkAuthorization(): bool
    {
        if ($this->madeline === null) {
            return false;
        }

        // Use cache if available
        if ($this->isAuthorizedCache !== null) {
            return $this->isAuthorizedCache;
        }

        try {
            $self = $this->madeline->getSelf();
            $this->isAuthorizedCache = $self !== null;
            
            if ($this->isAuthorizedCache && $self) {
                $userId = is_array($self) ? ($self['id'] ?? null) : (is_object($self) ? ($self->id ?? null) : null);
                Log::info('Telegram Client authorized', ['user_id' => $userId ?? 'unknown']);
            }
            
            return $this->isAuthorizedCache;
        } catch (ChannelException $e) {
            // IPC channel closed - need to reinitialize
            Log::warning('Telegram IPC channel closed, reinitializing', [
                'error' => $e->getMessage()
            ]);
            
            $this->madeline = null;
            $this->isAuthorizedCache = null;
            
            // Try to get client again (will reinitialize)
            $client = $this->getClient();
            if ($client) {
                return $this->checkAuthorization();
            }
            
            return false;
        } catch (\Exception $e) {
            // Not authorized or other error
            $this->isAuthorizedCache = false;
            Log::debug('Telegram Client not authorized: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Start phone login
     */
    public function authorize(): void
    {
        $phoneNumber = Option::get('telegram_phone_number', config('telegram.client.phone_number'));
        
        if (!$phoneNumber) {
            throw new \Exception('Phone number not configured');
        }

        // Clear cache and client to force fresh check
        $this->isAuthorizedCache = null;
        $this->madeline = null;

        $client = $this->getClient();
        if (!$client) {
            throw new \Exception('Failed to initialize Telegram client');
        }

        // Check if session file exists - if not, we can proceed with authorization
        $sessionExists = file_exists($this->sessionPath);
        
        if ($sessionExists) {
            // Session exists - check if already authorized
            if ($this->checkAuthorization()) {
                // Double check - try to get self to verify
                try {
                    $self = $client->getSelf();
                    if ($self) {
                        throw new \Exception('Already authorized. Use "Reset Session" to switch accounts.');
                    }
                } catch (\Exception $e) {
                    // If getSelf fails, we're not actually authorized
                    // This might happen if session is corrupted
                    $this->isAuthorizedCache = false;
                    Log::info('Session exists but authorization check failed, proceeding with new authorization', [
                        'error' => $e->getMessage()
                    ]);
                }
            }
        } else {
            // No session file - we can proceed with authorization
            $this->isAuthorizedCache = false;
            Log::info('No session file found, proceeding with new authorization');
        }

        try {
            $client->phoneLogin($phoneNumber);
            Log::info('Authorization code sent', ['phone' => $phoneNumber]);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            if (strpos($errorMessage, 'already') !== false || 
                strpos($errorMessage, 'PHONE_CODE_HASH_EMPTY') !== false) {
                throw new \Exception('Authorization already started. Enter the code or reset session.');
            }
            throw new \Exception('Error sending code: ' . $errorMessage);
        }
    }

    /**
     * Complete authorization with code
     */
    public function completeAuth(string $code, ?string $password2FA = null): array
    {
        $client = $this->getClient();
        if (!$client) {
            throw new \Exception('Telegram client not initialized');
        }

        try {
            $authorization = $client->completePhoneLogin($code);
            
            // Check if 2FA is required
            if (isset($authorization['_']) && $authorization['_'] === 'account.password') {
                if ($password2FA) {
                    $authorization = $client->complete2falogin($password2FA);
                } else {
                    return [
                        'success' => false,
                        'needs_2fa' => true,
                        'hint' => $authorization['hint'] ?? null,
                        'message' => 'Two-factor authentication password required'
                    ];
                }
            }
            
            // Verify authorization
            $self = $client->getSelf();
            if (!$self) {
                return ['success' => false, 'message' => 'Authorization not completed'];
            }
            
            $this->isAuthorizedCache = true;
            $userId = is_array($self) ? ($self['id'] ?? null) : (is_object($self) ? ($self->id ?? null) : null);
            
            Log::info('Telegram authorization completed', ['user_id' => $userId ?? 'unknown']);
            
            $getValue = function($key) use ($self) {
                if (is_array($self)) {
                    return $self[$key] ?? null;
                }
                if (is_object($self)) {
                    return $self->$key ?? null;
                }
                return null;
            };
            
            return [
                'success' => true,
                'user_id' => $userId,
                'first_name' => $getValue('first_name'),
                'last_name' => $getValue('last_name'),
                'username' => $getValue('username'),
                'phone' => $getValue('phone'),
            ];
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            
            if (strpos($errorMessage, 'PHONE_CODE_INVALID') !== false) {
                return ['success' => false, 'message' => 'Invalid authorization code'];
            } elseif (strpos($errorMessage, 'PHONE_CODE_EXPIRED') !== false) {
                return ['success' => false, 'message' => 'Authorization code expired'];
            } elseif (strpos($errorMessage, 'SESSION_PASSWORD_NEEDED') !== false) {
                return ['success' => false, 'needs_2fa' => true, 'message' => 'Two-factor authentication required'];
            }
            
            return ['success' => false, 'message' => $errorMessage];
        }
    }

    /**
     * Reset session
     * MadelineProto 8.x stores session as directory, not file
     */
    public function resetSession(): void
    {
        // Disconnect and clear client first
        $this->disconnect();
        $this->isAuthorizedCache = null;
        
        // Delete session (can be file or directory in MadelineProto 8.x)
        if (file_exists($this->sessionPath)) {
            if (is_dir($this->sessionPath)) {
                // Remove directory recursively
                $this->removeDirectory($this->sessionPath);
                Log::info('Telegram session directory deleted', ['path' => $this->sessionPath]);
            } else {
                // Remove file
                @unlink($this->sessionPath);
                Log::info('Telegram session file deleted', ['path' => $this->sessionPath]);
            }
        }
        
        // Delete session lock file if exists
        $lockFile = $this->sessionPath . '.lock';
        if (file_exists($lockFile) && is_file($lockFile)) {
            @unlink($lockFile);
        }
        
        // Delete all related session files in directory
        $sessionDir = dirname($this->sessionPath);
        $sessionBase = basename($this->sessionPath, '.madeline');
        
        if (is_dir($sessionDir)) {
            $files = glob($sessionDir . '/' . $sessionBase . '*');
            if ($files) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        @unlink($file);
                    } elseif (is_dir($file)) {
                        $this->removeDirectory($file);
                    }
                }
            }
        }
        
        // Force clear client to prevent reloading from deleted session
        $this->madeline = null;
        $this->isAuthorizedCache = null;
        
        Log::info('Telegram session reset completed');
    }

    /**
     * Remove directory recursively
     */
    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }
        
        @rmdir($dir);
    }

    /**
     * Send message to Telegram
     * According to docs: https://docs.madelineproto.xyz/
     */
    public function sendMessage(int $chatId, string $message = '', $attachments = []): bool
    {
        try {
            $client = $this->getClient();
            if (!$client) {
                Log::error('Telegram Client not initialized for sending message', ['chat_id' => $chatId]);
                return false;
            }

            if (!$this->checkAuthorization()) {
                Log::error('Telegram Client not authorized for sending message', ['chat_id' => $chatId]);
                return false;
            }

            $sentSomething = false;

            // Prepare peer - convert chat ID to proper format
            $peer = $this->preparePeer($chatId);
            if (!$peer) {
                Log::error('Invalid peer format', ['chat_id' => $chatId]);
                return false;
            }

            // Send text message if provided
            if ($message !== '') {
                try {
                    Log::info('Sending text message to Telegram', [
                        'chat_id' => $chatId,
                        'peer' => $peer,
                        'text_length' => strlen($message),
                        'text_preview' => substr($message, 0, 50)
                    ]);
                    
                    $result = $client->messages->sendMessage(
                        peer: $peer,
                        message: $message
                    );
                    
                    $sentSomething = true;
                    Log::info('Text message sent successfully', [
                        'chat_id' => $chatId,
                        'result_id' => $result['id'] ?? $result['updates'][0]['id'] ?? null
                    ]);
                } catch (ChannelException $e) {
                    Log::warning('IPC channel closed during sendMessage, reinitializing');
                    $this->handleChannelException();
                    return $this->sendMessage($chatId, $message, $attachments);
                } catch (\Exception $e) {
                    Log::error('Error sending text message', [
                        'chat_id' => $chatId,
                        'error' => $e->getMessage(),
                        'error_type' => get_class($e),
                        'trace' => substr($e->getTraceAsString(), 0, 500)
                    ]);
                    // Continue to send attachments even if text failed
                }
            }

            // Send attachments
            if (!empty($attachments)) {
                foreach ($attachments as $attachment) {
                    if ($attachment instanceof SupportMessageAttachment) {
                        try {
                            $caption = ($message !== '' && !$sentSomething) ? $message : '';
                            if ($this->sendTelegramAttachment($client, $peer, $attachment, $caption)) {
                                $sentSomething = true;
                            }
                        } catch (ChannelException $e) {
                            Log::warning('IPC channel closed during attachment send, reinitializing');
                            $this->handleChannelException();
                            $client = $this->getClient();
                            if ($client && $this->checkAuthorization()) {
                                $peer = $this->preparePeer($chatId);
                                if ($peer) {
                                    try {
                                        if ($this->sendTelegramAttachment($client, $peer, $attachment, $caption)) {
                                            $sentSomething = true;
                                        }
                                    } catch (\Exception $e2) {
                                        Log::error('Error sending attachment after reinit', [
                                            'error' => $e2->getMessage()
                                        ]);
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                            Log::error('Error sending attachment', [
                                'attachment_id' => $attachment->id,
                                'file_name' => $attachment->file_name,
                                'error' => $e->getMessage(),
                                'error_type' => get_class($e)
                            ]);
                        }
                    }
                }
            }

            if (!$sentSomething) {
                Log::warning('Nothing was sent to Telegram', [
                    'chat_id' => $chatId,
                    'has_text' => $message !== '',
                    'has_attachments' => !empty($attachments)
                ]);
            }

            return $sentSomething;
        } catch (\Exception $e) {
            Log::error('Error in sendMessage', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'trace' => substr($e->getTraceAsString(), 0, 500)
            ]);
            return false;
        }
    }

    /**
     * Handle ChannelException by reinitializing client
     */
    private function handleChannelException(): void
    {
        Log::warning('IPC channel closed, reinitializing');
        $this->madeline = null;
        $this->isAuthorizedCache = null;
    }

    /**
     * Get new messages from Telegram
     */
    public function getNewMessages(): array
    {
        try {
            $client = $this->getClient();
            if (!$client) {
                return [];
            }

            // Check authorization with proper channel handling
            if (!$this->checkAuthorization()) {
                Log::warning('Telegram Client not authorized for getting messages');
                return [];
            }

            try {
                $dialogsResponse = $client->messages->getDialogs(limit: 10);
                $messages = [];
                
                if (!isset($dialogsResponse['dialogs']) || !is_array($dialogsResponse['dialogs'])) {
                    return [];
                }

                foreach ($dialogsResponse['dialogs'] as $dialog) {
                    $peer = $dialog['peer'] ?? null;
                    if (!$peer) {
                        continue;
                    }

                    $chatId = $this->extractChatId($peer);
                    if (!$chatId) {
                        continue;
                    }

                    // Get messages for this chat
                    try {
                        $chatMessages = $client->messages->getHistory(
                            peer: $chatId,
                            limit: 10
                        );

                        if (isset($chatMessages['messages']) && is_array($chatMessages['messages'])) {
                            foreach ($chatMessages['messages'] as $msg) {
                                // Only process incoming messages
                                if (isset($msg['out']) && $msg['out']) {
                                    continue;
                                }

                                $messages[] = [
                                    'chat_id' => $chatId,
                                    'message_id' => $msg['id'] ?? null,
                                    'text' => $msg['message'] ?? '',
                                    'date' => $msg['date'] ?? time(),
                                    'from_id' => $msg['from_id'] ?? null,
                                ];
                            }
                        }
                    } catch (ChannelException $e) {
                        Log::warning('IPC channel closed during getHistory, reinitializing');
                        $this->madeline = null;
                        $this->isAuthorizedCache = null;
                        // Continue with next dialog
                        continue;
                    } catch (\Exception $e) {
                        Log::warning('Error getting history for chat', [
                            'chat_id' => $chatId,
                            'error' => $e->getMessage()
                        ]);
                        continue;
                    }
                }

                return $messages;
            } catch (ChannelException $e) {
                Log::warning('IPC channel closed during getDialogs, reinitializing');
                $this->madeline = null;
                $this->isAuthorizedCache = null;
                return [];
            } catch (\Exception $e) {
                Log::error('Error getting messages', ['error' => $e->getMessage()]);
                return [];
            }
        } catch (\Exception $e) {
            Log::error('Error in getNewMessages', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Process incoming message and create/update support chat
     */
    public function processIncomingMessage(array $messageData): ?SupportChat
    {
        $chatId = $messageData['chat_id'] ?? null;
        if (!$chatId) {
            return null;
        }

        try {
            $text = trim((string) ($messageData['text'] ?? ''));
            $messageId = $messageData['message_id'] ?? null;
            $attachments = $messageData['attachments'] ?? [];

            // Skip empty messages
            if (empty($text) && empty($attachments)) {
                return null;
            }

            // Check if message already exists
            if ($messageId) {
                $existingMessage = SupportMessage::where('telegram_message_id', $messageId)->first();
                if ($existingMessage) {
                    $chat = SupportChat::find($existingMessage->support_chat_id);
                    if ($chat) {
                        $chat->update(['last_message_at' => now()]);
                    }
                    return $chat;
                }
            }

            // Find or create support chat
            $chat = SupportChat::where('telegram_chat_id', $chatId)
                ->where('source', SupportChat::SOURCE_TELEGRAM)
                ->orderBy('created_at', 'desc')
                ->first();

            // If chat was closed, create new one
            if ($chat && $chat->status === SupportChat::STATUS_CLOSED) {
                $chat = null;
            }

            if (!$chat) {
                // Try to find user by telegram_id
                $user = User::where('telegram_id', $chatId)->first();
                
                // Get user info from Telegram
                $userInfo = $this->getUserInfo($chatId);
                $displayName = ($userInfo['first_name'] ?? '') . ' ' . ($userInfo['last_name'] ?? '');
                $displayName = trim($displayName) ?: "User {$chatId}";

                $chat = SupportChat::create([
                    'user_id' => $user?->id,
                    'source' => SupportChat::SOURCE_TELEGRAM,
                    'telegram_chat_id' => $chatId,
                    'status' => SupportChat::STATUS_PENDING,
                    'guest_name' => $user ? null : $displayName,
                    'guest_email' => $user ? null : "tg{$chatId}@telegram.local",
                    'telegram_first_name' => $userInfo['first_name'] ?? null,
                    'telegram_last_name' => $userInfo['last_name'] ?? null,
                    'telegram_photo' => $userInfo['photo_path'] ?? null,
                    'last_message_at' => now(),
                ]);
            }

            // Create support message
            $content = $text ?: '[Attachment from Telegram]';
            $supportMessage = SupportMessage::create([
                'support_chat_id' => $chat->id,
                'user_id' => $chat->user_id,
                'sender_type' => $chat->user_id ? SupportMessage::SENDER_USER : SupportMessage::SENDER_GUEST,
                'message' => $content,
                'telegram_message_id' => $messageId,
                'is_read' => false,
            ]);

            // Process attachments
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
            }

            // Update chat
            $chat->update([
                'last_message_at' => now(),
                'status' => SupportChat::STATUS_OPEN,
            ]);

            return $chat;
        } catch (\Exception $e) {
            Log::error('Error processing incoming message', [
                'error' => $e->getMessage(),
                'message_data' => $messageData
            ]);
            return null;
        }
    }

    /**
     * Get user info from Telegram
     */
    private function getUserInfo(int $userId): array
    {
        $client = $this->getClient();
        if (!$client) {
            return [];
        }

        try {
            $fullInfo = $client->getFullInfo($userId);
            
            $firstName = $fullInfo['User']['first_name'] ?? null;
            $lastName = $fullInfo['User']['last_name'] ?? null;
            
            // Download photo if exists
            $photoPath = null;
            if (isset($fullInfo['User']['photo']) && isset($fullInfo['User']['photo']['_'])) {
                $photoPath = $this->downloadUserPhoto($client, $userId, $fullInfo['User']);
            }
            
            return [
                'id' => $userId,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'photo_path' => $photoPath,
            ];
        } catch (\Exception $e) {
            Log::error('Error getting user info', ['error' => $e->getMessage()]);
            return ['id' => $userId];
        }
    }

    /**
     * Download user photo
     */
    private function downloadUserPhoto(API $client, int $userId, array $user): ?string
    {
        try {
            if (!isset($user['photo']) || !isset($user['photo']['_'])) {
                return null;
            }

            $avatarsDir = public_path('telegram/avatars');
            if (!file_exists($avatarsDir)) {
                mkdir($avatarsDir, 0755, true);
            }

            $fileName = "user_{$userId}_" . time() . ".jpg";
            $filePath = $avatarsDir . '/' . $fileName;

            $client->downloadToFile($user, $filePath);

            if (file_exists($filePath) && filesize($filePath) > 0) {
                return 'telegram/avatars/' . $fileName;
            }

            if (file_exists($filePath)) {
                @unlink($filePath);
            }
            return null;
        } catch (\Exception $e) {
            Log::warning('Error downloading user photo', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Send attachment to Telegram
     * According to docs: https://docs.madelineproto.xyz/
     */
    private function sendTelegramAttachment(API $client, array|int $peer, SupportMessageAttachment $attachment, string $caption = ''): bool
    {
        $filePath = public_path($attachment->file_path);
        if (!file_exists($filePath)) {
            Log::error('Attachment file not found', ['path' => $filePath]);
            return false;
        }

        try {
            // Upload file first (required by MadelineProto)
            $uploadedFile = $client->upload($filePath);
            
            if ($attachment->isImage()) {
                // Send as photo
                $result = $client->messages->sendMedia(
                    peer: $peer,
                    media: [
                        '_' => 'inputMediaUploadedPhoto',
                        'file' => $uploadedFile,
                    ],
                    message: $caption
                );
            } else {
                // Send as document
                $mimeType = $attachment->mime_type ?: mime_content_type($filePath) ?: 'application/octet-stream';
                
                $result = $client->messages->sendMedia(
                    peer: $peer,
                    media: [
                        '_' => 'inputMediaUploadedDocument',
                        'file' => $uploadedFile,
                        'mime_type' => $mimeType,
                        'attributes' => [
                            [
                                '_' => 'documentAttributeFilename',
                                'file_name' => $attachment->file_name,
                            ]
                        ]
                    ],
                    message: $caption
                );
            }
            
            $chatId = is_array($peer) ? ($peer['user_id'] ?? $peer['chat_id'] ?? $peer['channel_id'] ?? 'unknown') : $peer;
            Log::info('Attachment sent to Telegram', [
                'chat_id' => $chatId,
                'peer' => $peer,
                'attachment_id' => $attachment->id,
                'file_name' => $attachment->file_name,
                'result_id' => $result['id'] ?? $result['updates'][0]['id'] ?? null
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Error sending attachment', [
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
     * Prepare peer for sending messages
     * Converts chat ID to proper MadelineProto peer format
     */
    private function preparePeer(int $chatId): array|int|null
    {
        // For positive user IDs, use peerUser format
        if ($chatId > 0) {
            return [
                '_' => 'peerUser',
                'user_id' => $chatId
            ];
        }
        
        // For negative IDs (groups/channels)
        if ($chatId < 0) {
            // Check if it's a channel (starts with -100)
            if ($chatId < -1000000000000) {
                // Supergroup/channel
                $channelId = abs($chatId) - 1000000000000;
                return [
                    '_' => 'peerChannel',
                    'channel_id' => $channelId
                ];
            } else {
                // Regular group
                $groupId = abs($chatId);
                return [
                    '_' => 'peerChat',
                    'chat_id' => $groupId
                ];
            }
        }
        
        return null;
    }

    /**
     * Extract chat ID from peer
     */
    private function extractChatId($peer): ?int
    {
        if (is_numeric($peer)) {
            return (int) $peer;
        }

        if (is_array($peer)) {
            if (isset($peer['user_id'])) {
                return (int) $peer['user_id'];
            }
            if (isset($peer['chat_id'])) {
                return (int) $peer['chat_id'];
            }
            if (isset($peer['channel_id'])) {
                // For channels, convert back to negative format
                return -((int) $peer['channel_id'] + 1000000000000);
            }
        }

        return null;
    }
}
