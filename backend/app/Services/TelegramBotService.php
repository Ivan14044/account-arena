<?php

namespace App\Services;

use App\Models\Option;
use App\Models\SupportChat;
use App\Models\SupportMessage;
use App\Models\SupportMessageAttachment;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TelegramBotService
{
    private const API_BASE_URL = 'https://api.telegram.org/bot';
    private const TIMEOUT = 10;
    private const FILE_TIMEOUT = 30;

    private ?string $botToken = null;
    private bool $enabled = false;

    public function __construct()
    {
        $this->enabled = Option::get('telegram_client_enabled', false);
        $this->botToken = Option::get('telegram_bot_token', '');
    }

    /**
     * Check if service is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled && !empty($this->botToken);
    }

    /**
     * Get bot token
     */
    public function getBotToken(): ?string
    {
        return $this->botToken;
    }

    /**
     * Make API request to Telegram Bot API
     */
    private function makeApiRequest(string $method, array $data = [], int $timeout = self::TIMEOUT, string $httpMethod = 'POST'): ?array
    {
        if (!$this->isEnabled()) {
            return null;
        }

        try {
            $url = self::API_BASE_URL . $this->botToken . '/' . $method;

            if ($httpMethod === 'GET') {
                $response = Http::timeout($timeout)->get($url, $data);
            } else {
                $response = Http::timeout($timeout)->post($url, $data);
            }

            if ($response->successful() && $response->json('ok')) {
                return $response->json('result');
            }

            Log::error("TelegramBotService: API request failed", [
                'method' => $method,
                'response' => $response->json()
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error("TelegramBotService: API request error", [
                'method' => $method,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Make API request with file attachment
     */
    private function makeApiRequestWithFile(string $method, string $fileField, $file, string $fileName, array $data = []): ?array
    {
        if (!$this->isEnabled()) {
            return null;
        }

        try {
            $url = self::API_BASE_URL . $this->botToken . '/' . $method;
            $response = Http::timeout(self::FILE_TIMEOUT)
                ->attach($fileField, $file, $fileName)
                ->post($url, $data);

            if ($response->successful() && $response->json('ok')) {
                return $response->json('result');
            }

            Log::error("TelegramBotService: API request with file failed", [
                'method' => $method,
                'response' => $response->json()
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error("TelegramBotService: API request with file error", [
                'method' => $method,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Send greeting message for /start command
     * 
     * @param int $chatId Telegram chat ID
     * @param string $languageCode User's language code (e.g., 'uk', 'ru', 'en')
     * @return bool
     */
    public function sendGreetingMessage(int $chatId, string $languageCode = 'en'): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        // Check if greeting is enabled
        $greetingEnabled = Option::get('support_chat_greeting_enabled', false);
        $greetingEnabled = filter_var($greetingEnabled, FILTER_VALIDATE_BOOLEAN);

        if (!$greetingEnabled) {
            return false;
        }

        // Map Telegram language codes to our locale codes
        $localeMap = [
            'uk' => 'uk',
            'ru' => 'ru',
            'en' => 'en',
        ];
        $locale = $localeMap[$languageCode] ?? 'en';

        // Get greeting message for the locale
        $greetingMessage = Option::get('support_chat_greeting_message_' . $locale, '');

        // Fallback to Russian if English is also empty
        if (empty($greetingMessage)) {
            $greetingMessage = Option::get('support_chat_greeting_message_ru', '');
        }

        if (empty($greetingMessage)) {
            Log::warning('TelegramBotService: No greeting message found for any locale');
            return false;
        }

        $result = $this->makeApiRequest('sendMessage', [
            'chat_id' => $chatId,
            'text' => $greetingMessage,
            'parse_mode' => 'HTML'
        ]);

        if ($result) {
            Log::info('TelegramBotService: Greeting message sent successfully', [
                'chat_id' => $chatId,
                'locale' => $locale
            ]);
            return true;
        }

        return false;
    }

    /**
     * Send message to Telegram chat
     * 
     * @param int $chatId Telegram chat ID
     * @param string $message Message text
     * @param array|\Illuminate\Support\Collection $attachments Array or Collection of SupportMessageAttachment objects
     * @return bool
     */
    public function sendMessage(int $chatId, string $message = '', array|\Illuminate\Support\Collection $attachments = []): bool
    {
        if (!$this->isEnabled()) {
            Log::warning('TelegramBotService: Service not enabled or token missing');
            return false;
        }

        $sentSomething = false;

        // Send text message if provided
        if (!empty($message)) {
            $result = $this->makeApiRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML'
            ]);

            if ($result) {
                $sentSomething = true;
                Log::info('TelegramBotService: Text message sent successfully', [
                    'chat_id' => $chatId,
                    'message_id' => $result['message_id'] ?? null
                ]);
            }
        }

        // Send attachments
        if (!empty($attachments)) {
            // Convert Collection to array if needed
            $attachmentsArray = is_array($attachments) ? $attachments : $attachments->all();

            foreach ($attachmentsArray as $attachment) {
                if ($attachment instanceof SupportMessageAttachment) {
                    try {
                        if ($this->sendAttachment($chatId, $attachment, $message && !$sentSomething ? $message : '')) {
                            $sentSomething = true;
                        }
                    } catch (\Exception $e) {
                        Log::error('TelegramBotService: Error sending attachment', [
                            'chat_id' => $chatId,
                            'attachment_id' => $attachment->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
        }

        return $sentSomething;
    }

    /**
     * Send attachment to Telegram
     */
    private function sendAttachment(int $chatId, SupportMessageAttachment $attachment, string $caption = ''): bool
    {
        // Files are stored in storage/app/public/ via Storage::store()
        // Get the full path to the file
        $filePath = storage_path('app/public/' . $attachment->file_path);
        if (!file_exists($filePath)) {
            Log::error('TelegramBotService: Attachment file not found', ['path' => $filePath]);
            return false;
        }

        $file = fopen($filePath, 'r');
        if (!$file) {
            Log::error('TelegramBotService: Failed to open file', ['path' => $filePath]);
            return false;
        }

        try {
            $method = $attachment->isImage() ? 'sendPhoto' : 'sendDocument';
            $fileField = $attachment->isImage() ? 'photo' : 'document';

            $result = $this->makeApiRequestWithFile(
                $method,
                $fileField,
                $file,
                $attachment->file_name,
                [
                    'chat_id' => $chatId,
                    'caption' => $caption
                ]
            );

            if ($result) {
                Log::info('TelegramBotService: Attachment sent successfully', [
                    'chat_id' => $chatId,
                    'attachment_id' => $attachment->id
                ]);
                return true;
            }

            return false;
        } finally {
            fclose($file);
        }
    }

    /**
     * Set webhook URL
     * 
     * @param string $webhookUrl HTTPS URL for webhook
     * @return bool
     */
    public function setWebhook(string $webhookUrl): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $result = $this->makeApiRequest('setWebhook', ['url' => $webhookUrl]);

        if ($result) {
            Log::info('TelegramBotService: Webhook set successfully', ['url' => $webhookUrl]);
            return true;
        }

        return false;
    }

    /**
     * Delete webhook
     */
    public function deleteWebhook(): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $result = $this->makeApiRequest('deleteWebhook');

        if ($result !== null) {
            Log::info('TelegramBotService: Webhook deleted successfully');
            return true;
        }

        return false;
    }

    /**
     * Get webhook info
     */
    public function getWebhookInfo(): ?array
    {
        if (!$this->isEnabled()) {
            return null;
        }

        return $this->makeApiRequest('getWebhookInfo', [], self::TIMEOUT, 'GET');
    }

    /**
     * Process incoming message from webhook
     * 
     * @param array $update Telegram update object
     * @return SupportChat|null
     */
    public function processIncomingMessage(array $update): ?SupportChat
    {
        $message = $update['message'] ?? null;
        if (!$message) {
            return null;
        }

        $chat = $message['chat'] ?? null;
        $from = $message['from'] ?? null;
        $chatId = $chat['id'] ?? null;
        $messageId = $message['message_id'] ?? null;

        // Validate required data
        if (!$chat || !$from || !$chatId || !$messageId) {
            return null;
        }

        // Skip empty messages without attachments
        $text = trim($message['text'] ?? '');
        $hasAttachments = !empty($message['photo'] ?? []) || !empty($message['document'] ?? []);
        if (empty($text) && !$hasAttachments) {
            return null;
        }

        try {
            // Check if message already exists (prevent duplicates)
            $existingMessage = SupportMessage::where('telegram_message_id', $messageId)->first();
            if ($existingMessage) {
                $supportChat = SupportChat::find($existingMessage->support_chat_id);
                if ($supportChat) {
                    $supportChat->update(['last_message_at' => now()]);
                }
                return $supportChat;
            }

            // Extract user information
            $telegramUserId = $from['id'] ?? null;
            $user = $telegramUserId ? User::where('telegram_id', $telegramUserId)->first() : null;
            $displayName = $this->formatTelegramDisplayName($from, $chatId);

            // Find or create support chat (exclude closed chats)
            $supportChat = $this->findOrCreateSupportChat($chatId, $user, $displayName);

            // Create support message
            $supportMessage = SupportMessage::create([
                'support_chat_id' => $supportChat->id,
                'user_id' => $supportChat->user_id,
                'sender_type' => $supportChat->user_id ? SupportMessage::SENDER_USER : SupportMessage::SENDER_GUEST,
                'message' => $text ?: '[Attachment from Telegram]',
                'telegram_message_id' => $messageId,
                'is_read' => false,
            ]);

            // Process attachments (photos, documents)
            $this->processAttachments($supportMessage, $message);

            // Update chat status and timestamp
            $supportChat->update([
                'last_message_at' => now(),
                'status' => SupportChat::STATUS_OPEN,
            ]);

            return $supportChat;
        } catch (\Exception $e) {
            Log::error('TelegramBotService: Error processing incoming message', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'chat_id' => $chatId,
                'message_id' => $messageId,
            ]);
            return null;
        }
    }

    /**
     * Format display name from Telegram user data
     * Priority: FirstName + LastName > username > User {id}
     * 
     * @param array $from Telegram user data
     * @param int $chatId Telegram chat ID (fallback)
     * @return string
     */
    private function formatTelegramDisplayName(array $from, int $chatId): string
    {
        $firstName = trim($from['first_name'] ?? '');
        $lastName = trim($from['last_name'] ?? '');
        $username = $from['username'] ?? null;

        // Try FirstName + LastName
        $fullName = trim("{$firstName} {$lastName}");
        if (!empty($fullName)) {
            return $fullName;
        }

        // Try username
        if ($username) {
            return $username;
        }

        // Fallback to User {id}
        return "User {$chatId}";
    }

    /**
     * Find or create support chat for Telegram
     * 
     * @param int $telegramChatId Telegram chat ID
     * @param User|null $user User if linked
     * @param string $displayName Display name for guest
     * @return SupportChat
     */
    private function findOrCreateSupportChat(int $telegramChatId, ?User $user, string $displayName): SupportChat
    {
        // Find existing active chat (exclude closed)
        $supportChat = SupportChat::where('telegram_chat_id', $telegramChatId)
            ->where('source', SupportChat::SOURCE_TELEGRAM)
            ->where('status', '!=', SupportChat::STATUS_CLOSED)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($supportChat) {
            $supportChat->update(['guest_name' => $displayName]);
            Log::info('TelegramBotService: Existing chat updated', ['chat_id' => $supportChat->id, 'guest_name' => $displayName]);
            return $supportChat;
        }

        Log::info('TelegramBotService: No existing chat found, creating new one', ['telegram_chat_id' => $telegramChatId, 'display_name' => $displayName]);

        // Create new chat
        return SupportChat::create([
            'user_id' => $user?->id,
            'source' => SupportChat::SOURCE_TELEGRAM,
            'telegram_chat_id' => $telegramChatId,
            'status' => SupportChat::STATUS_PENDING,
            'guest_name' => $displayName,
            'guest_email' => "tg{$telegramChatId}@telegram.local",
            'last_message_at' => now(),
        ]);
    }

    /**
     * Process attachments from Telegram message
     */
    private function processAttachments(SupportMessage $supportMessage, array $message): void
    {
        // Process photos
        if (isset($message['photo']) && is_array($message['photo'])) {
            // Get the largest photo
            $photo = end($message['photo']);
            if (isset($photo['file_id'])) {
                $this->downloadAndSaveAttachment($supportMessage, $photo['file_id'], 'photo', 'image.jpg');
            }
        }

        // Process documents
        if (isset($message['document'])) {
            $document = $message['document'];
            $fileId = $document['file_id'] ?? null;
            $fileName = $document['file_name'] ?? 'document';
            $mimeType = $document['mime_type'] ?? 'application/octet-stream';

            if ($fileId) {
                $this->downloadAndSaveAttachment($supportMessage, $fileId, 'document', $fileName, $mimeType);
            }
        }
    }

    /**
     * Download and save attachment from Telegram
     */
    private function downloadAndSaveAttachment(
        SupportMessage $supportMessage,
        string $fileId,
        string $fileType,
        string $fileName,
        string $mimeType = 'application/octet-stream'
    ): void {
        // Get file path from Telegram
        $fileInfo = $this->makeApiRequest('getFile', ['file_id' => $fileId]);
        if (!$fileInfo || !isset($fileInfo['file_path'])) {
            Log::warning('TelegramBotService: Failed to get file path', ['file_id' => $fileId]);
            return;
        }

        $filePath = $fileInfo['file_path'];

        try {
            // Download file
            $fileUrl = "https://api.telegram.org/file/bot{$this->botToken}/{$filePath}";
            $fileContent = Http::timeout(self::FILE_TIMEOUT)->get($fileUrl)->body();

            // Save file
            $storagePath = 'support/attachments/' . date('Y/m') . '/' . uniqid() . '_' . $fileName;
            Storage::disk('public')->put($storagePath, $fileContent);

            // Create attachment record
            $fileUrl = asset('storage/' . $storagePath);
            SupportMessageAttachment::create([
                'support_message_id' => $supportMessage->id,
                'file_name' => $fileName,
                'file_path' => $storagePath,
                'file_url' => $fileUrl,
                'mime_type' => $mimeType,
                'file_size' => strlen($fileContent),
            ]);
        } catch (\Exception $e) {
            Log::error('TelegramBotService: Error downloading attachment', [
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ]);
        }
    }
}


