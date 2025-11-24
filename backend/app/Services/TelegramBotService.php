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

        try {
            $response = Http::timeout(10)->post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $greetingMessage,
                'parse_mode' => 'HTML'
            ]);

            if ($response->successful() && $response->json('ok')) {
                Log::info('TelegramBotService: Greeting message sent successfully', [
                    'chat_id' => $chatId,
                    'locale' => $locale
                ]);
                return true;
            } else {
                Log::error('TelegramBotService: Failed to send greeting message', [
                    'chat_id' => $chatId,
                    'response' => $response->json()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('TelegramBotService: Error sending greeting message', [
                'chat_id' => $chatId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
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
            try {
                $response = Http::timeout(10)->post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => 'HTML'
                ]);

                if ($response->successful() && $response->json('ok')) {
                    $sentSomething = true;
                    Log::info('TelegramBotService: Text message sent successfully', [
                        'chat_id' => $chatId,
                        'message_id' => $response->json('result.message_id')
                    ]);
                } else {
                    Log::error('TelegramBotService: Failed to send text message', [
                        'chat_id' => $chatId,
                        'response' => $response->json()
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('TelegramBotService: Error sending text message', [
                    'chat_id' => $chatId,
                    'error' => $e->getMessage()
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

        try {
            $file = fopen($filePath, 'r');
            $fileName = $attachment->file_name;

            if ($attachment->isImage()) {
                // Send as photo
                $response = Http::timeout(30)->attach('photo', $file, $fileName)
                    ->post("https://api.telegram.org/bot{$this->botToken}/sendPhoto", [
                        'chat_id' => $chatId,
                        'caption' => $caption
                    ]);
            } else {
                // Send as document
                $response = Http::timeout(30)->attach('document', $file, $fileName)
                    ->post("https://api.telegram.org/bot{$this->botToken}/sendDocument", [
                        'chat_id' => $chatId,
                        'caption' => $caption
                    ]);
            }

            fclose($file);

            if ($response->successful() && $response->json('ok')) {
                Log::info('TelegramBotService: Attachment sent successfully', [
                    'chat_id' => $chatId,
                    'attachment_id' => $attachment->id
                ]);
                return true;
            } else {
                Log::error('TelegramBotService: Failed to send attachment', [
                    'chat_id' => $chatId,
                    'response' => $response->json()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('TelegramBotService: Error sending attachment', [
                'chat_id' => $chatId,
                'attachment_id' => $attachment->id,
                'error' => $e->getMessage()
            ]);
            return false;
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

        try {
            $response = Http::timeout(10)->post("https://api.telegram.org/bot{$this->botToken}/setWebhook", [
                'url' => $webhookUrl
            ]);

            if ($response->successful() && $response->json('ok')) {
                Log::info('TelegramBotService: Webhook set successfully', ['url' => $webhookUrl]);
                return true;
            } else {
                Log::error('TelegramBotService: Failed to set webhook', [
                    'url' => $webhookUrl,
                    'response' => $response->json()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('TelegramBotService: Error setting webhook', [
                'url' => $webhookUrl,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Delete webhook
     */
    public function deleteWebhook(): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        try {
            $response = Http::timeout(10)->post("https://api.telegram.org/bot{$this->botToken}/deleteWebhook");

            if ($response->successful() && $response->json('ok')) {
                Log::info('TelegramBotService: Webhook deleted successfully');
                return true;
            } else {
                Log::error('TelegramBotService: Failed to delete webhook', [
                    'response' => $response->json()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('TelegramBotService: Error deleting webhook', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get webhook info
     */
    public function getWebhookInfo(): ?array
    {
        if (!$this->isEnabled()) {
            return null;
        }

        try {
            $response = Http::timeout(10)->get("https://api.telegram.org/bot{$this->botToken}/getWebhookInfo");

            if ($response->successful() && $response->json('ok')) {
                return $response->json('result');
            }
        } catch (\Exception $e) {
            Log::error('TelegramBotService: Error getting webhook info', [
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    /**
     * Process incoming message from webhook
     * 
     * @param array $update Telegram update object
     * @return SupportChat|null
     */
    public function processIncomingMessage(array $update): ?SupportChat
    {
        if (!isset($update['message'])) {
            return null;
        }

        $message = $update['message'];
        $chat = $message['chat'] ?? null;
        $from = $message['from'] ?? null;

        if (!$chat || !$from) {
            return null;
        }

        $chatId = $chat['id'] ?? null;
        $messageId = $message['message_id'] ?? null;
        $text = $message['text'] ?? '';
        $date = $message['date'] ?? time();

        if (!$chatId || !$messageId) {
            return null;
        }

        // Skip empty messages without attachments
        if (empty($text) && empty($message['photo'] ?? []) && empty($message['document'] ?? [])) {
            return null;
        }

        try {
            // Check if message already exists
            $existingMessage = SupportMessage::where('telegram_message_id', $messageId)->first();
            if ($existingMessage) {
                $supportChat = SupportChat::find($existingMessage->support_chat_id);
                if ($supportChat) {
                    $supportChat->update(['last_message_at' => now()]);
                }
                return $supportChat;
            }

            // Find or create support chat (exclude closed chats)
            $supportChat = SupportChat::where('telegram_chat_id', $chatId)
                ->where('source', SupportChat::SOURCE_TELEGRAM)
                ->where('status', '!=', SupportChat::STATUS_CLOSED)
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$supportChat) {
                // Try to find user by telegram_id
                $telegramUserId = $from['id'] ?? null;
                $user = $telegramUserId ? User::where('telegram_id', $telegramUserId)->first() : null;

                $firstName = $from['first_name'] ?? '';
                $lastName = $from['last_name'] ?? '';
                $displayName = trim("{$firstName} {$lastName}") ?: "User {$chatId}";
                $username = $from['username'] ?? null;

                $supportChat = SupportChat::create([
                    'user_id' => $user?->id,
                    'source' => SupportChat::SOURCE_TELEGRAM,
                    'telegram_chat_id' => $chatId,
                    'status' => SupportChat::STATUS_PENDING,
                    'guest_name' => $user ? null : $displayName,
                    'guest_email' => $user ? null : "tg{$chatId}@telegram.local",
                    'telegram_first_name' => $firstName ?: null,
                    'telegram_last_name' => $lastName ?: null,
                    'last_message_at' => now(),
                ]);
            }

            // Create support message
            $content = $text ?: '[Attachment from Telegram]';
            $supportMessage = SupportMessage::create([
                'support_chat_id' => $supportChat->id,
                'user_id' => $supportChat->user_id,
                'sender_type' => $supportChat->user_id ? SupportMessage::SENDER_USER : SupportMessage::SENDER_GUEST,
                'message' => $content,
                'telegram_message_id' => $messageId,
                'is_read' => false,
            ]);

            // Process attachments (photos, documents)
            $this->processAttachments($supportMessage, $message);

            // Update chat
            $supportChat->update([
                'last_message_at' => now(),
                'status' => SupportChat::STATUS_OPEN,
            ]);

            return $supportChat;
        } catch (\Exception $e) {
            Log::error('TelegramBotService: Error processing incoming message', [
                'error' => $e->getMessage(),
                'update' => $update
            ]);
            return null;
        }
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
        try {
            // Get file path from Telegram
            $response = Http::timeout(10)->get("https://api.telegram.org/bot{$this->botToken}/getFile", [
                'file_id' => $fileId
            ]);

            if (!$response->successful() || !$response->json('ok')) {
                Log::warning('TelegramBotService: Failed to get file path', ['file_id' => $fileId]);
                return;
            }

            $filePath = $response->json('result.file_path');
            if (!$filePath) {
                return;
            }

            // Download file
            $fileUrl = "https://api.telegram.org/file/bot{$this->botToken}/{$filePath}";
            $fileContent = Http::timeout(30)->get($fileUrl)->body();

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


