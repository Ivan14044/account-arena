<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\Option;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    /**
     * Получить список покупок текущего пользователя или гостя
     * Поддерживает фильтрацию по дате и статусу
     * Для гостей требуется передать email в query параметре
     */
    public function index(\App\Http\Requests\Purchase\PurchaseIndexRequest $request)
    {
        $user = $request->user();
        $guestEmail = $request->query('guest_email');
        
        // Если пользователь не авторизован, проверяем guest_email
        if (!$user && !$guestEmail) {
            return response()->json(['error' => 'Unauthorized. Please provide guest_email for guest purchases.'], 401);
        }
        
        // Валидация email для гостей
        if (!$user && $guestEmail) {
            $guestEmail = strtolower(trim($guestEmail));
            if (!filter_var($guestEmail, FILTER_VALIDATE_EMAIL)) {
                return response()->json(['error' => 'Invalid guest email format'], 422);
            }
        }
        
        // Получаем locale из заголовка X-Locale
        $locale = $request->header('X-Locale') ?? $request->query('locale') ?? app()->getLocale();
        if (!in_array($locale, array_keys(config('langs')))) {
            $locale = app()->getLocale();
        }
        
        // Начинаем запрос с основными условиями
        // Eager loading для избежания N+1 запросов
        $query = Purchase::with([
            'serviceAccount' => function($q) {
                $q->select('id', 'title', 'title_en', 'title_uk', 'image_url');
            },
            'transaction' => function($q) {
                $q->select('id', 'currency', 'payment_method');
            },
            'transaction.dispute',
            'statusHistory' => function($q) {
                $q->orderBy('created_at', 'desc')
                  ->limit(5)
                  ->with('changedBy:id,name,email');
            }
        ]);
        
        // Фильтруем по user_id для авторизованных пользователей или по guest_email для гостей
        if ($user) {
            $query->where('user_id', $user->id);
        } else {
            $query->whereNull('user_id')->where('guest_email', $guestEmail);
        }
        
        // Фильтрация по дате "с"
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        // Фильтрация по дате "по"
        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        // Фильтрация по статусу
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }
        
        $purchases = $query->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($purchase) {
                // Возвращаем все названия товара для локализации на frontend
                $productTitle = $purchase->serviceAccount ? [
                    'title' => $purchase->serviceAccount->title,
                    'title_en' => $purchase->serviceAccount->title_en,
                    'title_uk' => $purchase->serviceAccount->title_uk,
                ] : null;
                
                return [
                    'id' => $purchase->id,
                    'order_number' => $purchase->order_number,
                    'transaction_id' => $purchase->transaction_id, // Для создания претензий
                    'product' => [
                        'id' => $purchase->serviceAccount->id,
                        'title' => $productTitle, // Объект с названиями на всех языках
                        'image_url' => $purchase->serviceAccount->image_url,
                    ],
                    'quantity' => $purchase->quantity,
                    'price' => $purchase->price,
                    'total_amount' => $purchase->total_amount,
                    'account_data' => $purchase->account_data, // Данные купленных аккаунтов
                    'status' => $purchase->status,
                    'purchased_at' => $purchase->created_at->format('Y-m-d H:i:s'),
                    
                    // Дополнительные поля для совместимости с ProfilePage
                    'service_name' => $productTitle, // Объект с названиями на всех языках
                    'amount' => $purchase->total_amount,
                    'currency' => $purchase->transaction ? $purchase->transaction->currency : Option::get('currency', 'USD'),
                    'payment_method' => $purchase->transaction ? $purchase->transaction->payment_method : 'unknown',
                    'created_at' => $purchase->created_at->format('Y-m-d H:i:s'),
                    
                    // Информация о претензии (если существует)
                    'has_dispute' => $purchase->transaction && $purchase->transaction->dispute ? true : false,
                    'dispute' => $purchase->transaction && $purchase->transaction->dispute ? [
                        'id' => $purchase->transaction->dispute->id,
                        'status' => $purchase->transaction->dispute->status,
                        'admin_decision' => $purchase->transaction->dispute->admin_decision,
                    ] : null,
                    
                    // История изменений статуса
                    'status_history' => $purchase->statusHistory->map(function ($history) {
                        return [
                            'id' => $history->id,
                            'old_status' => $history->old_status,
                            'new_status' => $history->new_status,
                            'reason' => $history->reason,
                            'changed_by' => $history->changedBy ? [
                                'id' => $history->changedBy->id,
                                'name' => $history->changedBy->name,
                                'email' => $history->changedBy->email,
                            ] : null,
                            'created_at' => $history->created_at->format('Y-m-d H:i:s'),
                        ];
                    })->toArray(),
                ];
            });
        
        return \App\Http\Responses\ApiResponse::success([
            'purchases' => $purchases,
        ]);
    }
    
    /**
     * Получить конкретную покупку
     * Для гостей требуется передать email в query параметре
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $guestEmail = $request->query('guest_email');
        
        // Если пользователь не авторизован, проверяем guest_email
        if (!$user && !$guestEmail) {
            return response()->json(['error' => 'Unauthorized. Please provide guest_email for guest purchases.'], 401);
        }
        
        // Валидация email для гостей
        if (!$user && $guestEmail) {
            $guestEmail = strtolower(trim($guestEmail));
            if (!filter_var($guestEmail, FILTER_VALIDATE_EMAIL)) {
                return response()->json(['error' => 'Invalid guest email format'], 422);
            }
        }
        
        // Получаем locale из заголовка X-Locale
        $locale = $request->header('X-Locale') ?? $request->query('locale') ?? app()->getLocale();
        if (!in_array($locale, array_keys(config('langs')))) {
            $locale = app()->getLocale();
        }
        
        $query = Purchase::with(['serviceAccount', 'transaction'])
            ->where('id', $id);
        
        // Фильтруем по user_id для авторизованных пользователей или по guest_email для гостей
        if ($user) {
            $query->where('user_id', $user->id);
        } else {
            $query->whereNull('user_id')->where('guest_email', $guestEmail);
        }
        
        $purchase = $query->first();
        
        if (!$purchase) {
            return response()->json(['error' => 'Purchase not found'], 404);
        }
        
        // Возвращаем все названия товара для локализации на frontend
        $productTitle = $purchase->serviceAccount ? [
            'title' => $purchase->serviceAccount->title,
            'title_en' => $purchase->serviceAccount->title_en,
            'title_uk' => $purchase->serviceAccount->title_uk,
        ] : null;
        
        return \App\Http\Responses\ApiResponse::success([
            'purchase' => [
                'id' => $purchase->id,
                'order_number' => $purchase->order_number,
                'product' => [
                    'id' => $purchase->serviceAccount->id,
                    'title' => $productTitle, // Объект с названиями на всех языках
                    'description' => $purchase->serviceAccount->description,
                    'image_url' => $purchase->serviceAccount->image_url,
                ],
                'quantity' => $purchase->quantity,
                'price' => $purchase->price,
                'total_amount' => $purchase->total_amount,
                'account_data' => $purchase->account_data,
                'status' => $purchase->status,
                'purchased_at' => $purchase->created_at->format('Y-m-d H:i:s'),
                
                // История изменений статуса
                'status_history' => $purchase->statusHistory->map(function ($history) {
                    return [
                        'id' => $history->id,
                        'old_status' => $history->old_status,
                        'new_status' => $history->new_status,
                        'reason' => $history->reason,
                        'changed_by' => $history->changedBy ? [
                            'id' => $history->changedBy->id,
                            'name' => $history->changedBy->name,
                            'email' => $history->changedBy->email,
                        ] : null,
                        'created_at' => $history->created_at->format('Y-m-d H:i:s'),
                    ];
                })->toArray(),
            ],
        ]);
    }
    
    
    /**
     * Скачать купленные товары в виде текстового файла с кодировкой UTF-8
     * Для гостей требуется передать email в query параметре
     */
    public function download(Request $request, $id)
    {
        $user = $request->user();
        $guestEmail = $request->query('guest_email');
        
        // Если пользователь не авторизован, проверяем guest_email
        if (!$user && !$guestEmail) {
            return response()->json(['error' => 'Unauthorized. Please provide guest_email for guest purchases.'], 401);
        }
        
        // Валидация email для гостей
        if (!$user && $guestEmail) {
            $guestEmail = strtolower(trim($guestEmail));
            if (!filter_var($guestEmail, FILTER_VALIDATE_EMAIL)) {
                return response()->json(['error' => 'Invalid guest email format'], 422);
            }
        }
        
        $query = Purchase::with(['serviceAccount'])
            ->where('id', $id);
        
        // Фильтруем по user_id для авторизованных пользователей или по guest_email для гостей
        if ($user) {
            $query->where('user_id', $user->id);
        } else {
            $query->whereNull('user_id')->where('guest_email', $guestEmail);
        }
        
        $purchase = $query->first();
        
        if (!$purchase) {
            return response()->json(['error' => 'Purchase not found'], 404);
        }
        
        // Формируем содержимое файла из купленных аккаунтов
        $accountData = $purchase->account_data;
        if (is_array($accountData) && !empty($accountData)) {
            $content = implode("\n", $accountData);
        } else {
            $content = "Нет данных для скачивания";
        }
        
        // Генерируем имя файла с информацией о покупке
        $filename = 'purchase_' . $purchase->order_number . '_' . date('Y-m-d') . '.txt';
        
        // Возвращаем файл с UTF-8 кодировкой
        return response($content)
            ->header('Content-Type', 'text/plain; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Отменить заказ в статусе processing
     * Только для заказов с ручной выдачей
     */
    public function cancel(Request $request, $id)
    {
        $user = $request->user();
        $guestEmail = $request->query('guest_email');
        
        // Если пользователь не авторизован, проверяем guest_email
        if (!$user && !$guestEmail) {
            return response()->json(['error' => 'Unauthorized. Please provide guest_email for guest purchases.'], 401);
        }
        
        // Валидация email для гостей
        if (!$user && $guestEmail) {
            $guestEmail = strtolower(trim($guestEmail));
            if (!filter_var($guestEmail, FILTER_VALIDATE_EMAIL)) {
                return response()->json(['error' => 'Invalid guest email format'], 422);
            }
        }
        
        $query = Purchase::where('id', $id);
        
        // Фильтруем по user_id для авторизованных пользователей или по guest_email для гостей
        if ($user) {
            $query->where('user_id', $user->id);
        } else {
            $query->whereNull('user_id')->where('guest_email', $guestEmail);
        }
        
        $purchase = $query->first();
        
        if (!$purchase) {
            return response()->json(['error' => 'Purchase not found'], 404);
        }

        // Валидация причины отмены
        $request->validate([
            'cancellation_reason' => 'required|string|min:10|max:500',
        ]);

        try {
            $manualDeliveryService = app(\App\Services\ManualDeliveryService::class);
            
            // Для гостей создаем временный объект User с email
            if (!$user) {
                $guestUser = new \App\Models\User();
                $guestUser->id = null;
                $guestUser->email = $guestEmail;
                // Устанавливаем guest_email для корректной проверки
                $guestUser->setAttribute('guest_email', $guestEmail);
            } else {
                $guestUser = $user;
            }
            
            $manualDeliveryService->cancelProcessingOrder($purchase, $guestUser, $request->input('cancellation_reason'));
            
            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully',
                'purchase' => $purchase->fresh()->load(['serviceAccount', 'transaction']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Получить статистику обработки заказов (среднее время обработки)
     * Доступно только для авторизованных пользователей
     */
    public function getProcessingStats(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $manualDeliveryService = app(\App\Services\ManualDeliveryService::class);
            $averageTime = $manualDeliveryService->getAverageProcessingTime();

            return \App\Http\Responses\ApiResponse::success([
                'average_processing_time_hours' => $averageTime,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить полную историю изменений статуса покупки
     * Для гостей требуется передать email в query параметре
     */
    public function getStatusHistory(Request $request, $id)
    {
        $user = $request->user();
        $guestEmail = $request->query('guest_email');
        
        // Если пользователь не авторизован, проверяем guest_email
        if (!$user && !$guestEmail) {
            return response()->json(['error' => 'Unauthorized. Please provide guest_email for guest purchases.'], 401);
        }
        
        // Валидация email для гостей
        if (!$user && $guestEmail) {
            $guestEmail = strtolower(trim($guestEmail));
            if (!filter_var($guestEmail, FILTER_VALIDATE_EMAIL)) {
                return response()->json(['error' => 'Invalid guest email format'], 422);
            }
        }
        
        $query = Purchase::where('id', $id);
        
        // Фильтруем по user_id для авторизованных пользователей или по guest_email для гостей
        if ($user) {
            $query->where('user_id', $user->id);
        } else {
            $query->whereNull('user_id')->where('guest_email', $guestEmail);
        }
        
        $purchase = $query->first();
        
        if (!$purchase) {
            return response()->json(['error' => 'Purchase not found'], 404);
        }
        
        // Получаем полную историю статусов (без лимита)
        $statusHistory = $purchase->statusHistory()
            ->with('changedBy:id,name,email')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($history) {
                return [
                    'id' => $history->id,
                    'old_status' => $history->old_status,
                    'new_status' => $history->new_status,
                    'reason' => $history->reason,
                    'metadata' => $history->metadata,
                    'changed_by' => $history->changedBy ? [
                        'id' => $history->changedBy->id,
                        'name' => $history->changedBy->name,
                        'email' => $history->changedBy->email,
                    ] : null,
                    'created_at' => $history->created_at->format('Y-m-d H:i:s'),
                ];
            });
        
        return \App\Http\Responses\ApiResponse::success([
            'status_history' => $statusHistory,
        ]);
    }
}
