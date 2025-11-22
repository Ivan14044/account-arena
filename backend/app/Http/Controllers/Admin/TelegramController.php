<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TelegramClientService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramController extends Controller
{
    /**
     * Check authorization status
     */
    public function checkAuthStatus(TelegramClientService $telegramService)
    {
        try {
            $client = $telegramService->getClient();
            
            if (!$client) {
                return response()->json([
                    'authorized' => false,
                    'message' => 'Telegram Client not initialized. Check API ID and API Hash settings.'
                ]);
            }

            try {
                $self = $client->getSelf();
                
                if ($self) {
                    $getValue = function($key) use ($self) {
                        if (is_array($self)) {
                            return $self[$key] ?? null;
                        }
                        if (is_object($self)) {
                            return $self->$key ?? null;
                        }
                        return null;
                    };
                    
                    return response()->json([
                        'authorized' => true,
                        'user_id' => $getValue('id'),
                        'first_name' => $getValue('first_name'),
                        'last_name' => $getValue('last_name'),
                        'username' => $getValue('username'),
                        'phone' => $getValue('phone'),
                    ]);
                }
            } catch (\Exception $e) {
                Log::debug('Telegram authorization check failed', [
                    'error' => $e->getMessage()
                ]);
            }

            return response()->json([
                'authorized' => false,
                'message' => 'Authorization required. Click "Start Authorization" to login.'
            ]);
        } catch (\Exception $e) {
            Log::error('Error checking Telegram authorization status', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'authorized' => false,
                'message' => 'Error checking status: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Start authorization (send code)
     */
    public function startAuth(TelegramClientService $telegramService)
    {
        try {
            $telegramService->authorize();
            
            return response()->json([
                'success' => true,
                'message' => 'Code sent to Telegram. Check your Telegram app and enter the code.'
            ]);
        } catch (\Exception $e) {
            Log::error('Error starting Telegram authorization', [
                'error' => $e->getMessage()
            ]);
            
            $statusCode = strpos($e->getMessage(), 'Already') !== false ? 400 : 500;
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }

    /**
     * Complete authorization with code
     */
    public function completeAuth(Request $request, TelegramClientService $telegramService)
    {
        $request->validate([
            'code' => 'required|string|max:10',
            'password_2fa' => 'nullable|string',
        ]);

        try {
            $result = $telegramService->completeAuth(
                $request->code,
                $request->input('password_2fa')
            );
            
            if (isset($result['success']) && $result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Authorization completed successfully! You can now receive messages from Telegram.',
                    'user' => [
                        'user_id' => $result['user_id'] ?? null,
                        'first_name' => $result['first_name'] ?? null,
                        'last_name' => $result['last_name'] ?? null,
                        'username' => $result['username'] ?? null,
                        'phone' => $result['phone'] ?? null,
                    ]
                ]);
            } elseif (isset($result['needs_2fa']) && $result['needs_2fa']) {
                return response()->json([
                    'success' => false,
                    'needs_2fa' => true,
                    'hint' => $result['hint'] ?? null,
                    'message' => $result['message'] ?? 'Two-factor authentication password required'
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to complete authorization. Check the code and try again.'
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Error completing Telegram authorization', [
                'error' => $e->getMessage()
            ]);
            
            $statusCode = strpos($e->getMessage(), 'PHONE_CODE') !== false ? 400 : 500;
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }

    /**
     * Reset Telegram session
     */
    public function resetSession(TelegramClientService $telegramService)
    {
        try {
            $telegramService->resetSession();
            return response()->json([
                'success' => true,
                'message' => 'Telegram session reset successfully. You can now authorize with a different account.'
            ]);
        } catch (\Exception $e) {
            Log::error('Error resetting Telegram session', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Manually poll messages from Telegram
     */
    public function pollMessages(Request $request, TelegramClientService $telegramService)
    {
        try {
            $messages = $telegramService->getNewMessages();
            
            if (empty($messages)) {
                return redirect()->back()->with('info', 'No new messages from Telegram');
            }
            
            $processedCount = 0;
            foreach ($messages as $messageData) {
                try {
                    $chat = $telegramService->processIncomingMessage($messageData);
                    if ($chat) {
                        $processedCount++;
                    }
                } catch (\Exception $e) {
                    Log::error('Error processing Telegram message', [
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            $message = "Processed {$processedCount} of " . count($messages) . " messages";
            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Error polling Telegram messages', [
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }
}

