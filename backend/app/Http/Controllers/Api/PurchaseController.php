<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\Option;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    /**
     * Получить список покупок текущего пользователя
     * Поддерживает фильтрацию по дате и статусу
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        // Начинаем запрос с основными условиями
        // Загружаем связанные претензии для проверки возможности создания новой
        $query = Purchase::with(['serviceAccount', 'transaction', 'transaction.dispute'])
            ->where('user_id', $user->id);
        
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
                return [
                    'id' => $purchase->id,
                    'order_number' => $purchase->order_number,
                    'transaction_id' => $purchase->transaction_id, // Для создания претензий
                    'product' => [
                        'id' => $purchase->serviceAccount->id,
                        'title' => $purchase->serviceAccount->title,
                        'image_url' => $purchase->serviceAccount->image_url,
                    ],
                    'quantity' => $purchase->quantity,
                    'price' => $purchase->price,
                    'total_amount' => $purchase->total_amount,
                    'account_data' => $purchase->account_data, // Данные купленных аккаунтов
                    'status' => $purchase->status,
                    'purchased_at' => $purchase->created_at->format('Y-m-d H:i:s'),
                    
                    // Дополнительные поля для совместимости с ProfilePage
                    'service_name' => $purchase->serviceAccount->title,
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
                ];
            });
        
        return response()->json([
            'success' => true,
            'purchases' => $purchases,
        ]);
    }
    
    /**
     * Получить конкретную покупку
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $purchase = Purchase::with(['serviceAccount', 'transaction'])
            ->where('user_id', $user->id)
            ->where('id', $id)
            ->first();
        
        if (!$purchase) {
            return response()->json(['error' => 'Purchase not found'], 404);
        }
        
        return response()->json([
            'success' => true,
            'purchase' => [
                'id' => $purchase->id,
                'order_number' => $purchase->order_number,
                'product' => [
                    'id' => $purchase->serviceAccount->id,
                    'title' => $purchase->serviceAccount->title,
                    'description' => $purchase->serviceAccount->description,
                    'image_url' => $purchase->serviceAccount->image_url,
                ],
                'quantity' => $purchase->quantity,
                'price' => $purchase->price,
                'total_amount' => $purchase->total_amount,
                'account_data' => $purchase->account_data,
                'status' => $purchase->status,
                'purchased_at' => $purchase->created_at->format('Y-m-d H:i:s'),
            ],
        ]);
    }
}
