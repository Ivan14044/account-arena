<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductDispute;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Services\NotifierService;
use App\Http\Responses\ApiResponse;

class ProductDisputeController extends Controller
{
    /**
     * Получить список претензий пользователя
     */
    public function index(Request $request)
    {
        // Получаем locale из заголовка X-Locale
        $locale = $request->header('X-Locale') ?? $request->query('locale') ?? app()->getLocale();
        if (!in_array($locale, array_keys(config('langs')))) {
            $locale = app()->getLocale();
        }

        $disputes = $request->user()
            ->disputes()
            ->with([
                'transaction.serviceAccount' => function($q) {
                    $q->select('id', 'title', 'title_en', 'title_uk');
                },
                'serviceAccount' => function($q) {
                    $q->select('id', 'title', 'title_en', 'title_uk');
                }
            ])
            ->latest()
            ->paginate(20);

        return response()->json([
            'disputes' => $disputes->map(function($dispute) use ($locale) {
                // Возвращаем все названия товара для локализации на frontend
                $productTitle = $dispute->serviceAccount ? [
                    'title' => $dispute->serviceAccount->title,
                    'title_en' => $dispute->serviceAccount->title_en,
                    'title_uk' => $dispute->serviceAccount->title_uk,
                ] : null;
                
                return [
                    'id' => $dispute->id,
                    'transaction_id' => $dispute->transaction_id,
                    'product_title' => $productTitle, // Объект с названиями на всех языках
                    'amount' => $dispute->transaction->amount,
                    'reason' => $dispute->reason,
                    'reason_text' => $dispute->getReasonText($locale),
                    'customer_description' => $dispute->customer_description,
                    'screenshot_url' => $dispute->screenshot_url, // Для просмотра скриншота
                    'screenshot_type' => $dispute->screenshot_type,
                    'status' => $dispute->status,
                    'status_text' => $dispute->getStatusText($locale),
                    'admin_decision' => $dispute->admin_decision,
                    'admin_decision_text' => $dispute->getDecisionText($locale),
                    'admin_comment' => $dispute->admin_comment,
                    'refund_amount' => $dispute->refund_amount,
                    'created_at' => $dispute->created_at->format('Y-m-d H:i:s'),
                    'resolved_at' => $dispute->resolved_at?->format('Y-m-d H:i:s'),
                ];
            }),
            'pagination' => [
                'current_page' => $disputes->currentPage(),
                'last_page' => $disputes->lastPage(),
                'per_page' => $disputes->perPage(),
                'total' => $disputes->total(),
            ],
        ]);
    }

    /**
     * Создать новую претензию
     */
    public function store(\App\Http\Requests\Dispute\CreateDisputeRequest $request)
    {
        $validated = $request->validated();

        // Проверка что предоставлен хотя бы скриншот (файл или ссылка)
        if (!$request->hasFile('screenshot_file') && !$request->filled('screenshot_link')) {
            return response()->json([
                'success' => false,
                'message' => 'Необходимо прикрепить скриншот (файл или ссылку)',
            ], 422);
        }

        $transaction = Transaction::with('serviceAccount')->findOrFail($validated['transaction_id']);

        // Проверяем, что транзакция принадлежит пользователю
        if ($transaction->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Транзакция не найдена',
            ], 403);
        }

        // Проверяем, что претензия еще не создана
        if ($transaction->dispute()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Претензия на эту покупку уже создана',
            ], 422);
        }

        // Проверяем, что транзакция не старше 30 дней
        if ($transaction->created_at->diffInDays(now()) > 30) {
            return response()->json([
                'success' => false,
                'message' => 'Срок подачи претензии истек (максимум 30 дней)',
            ], 422);
        }

        // ИСПРАВЛЕНО: Получаем service_account_id из транзакции или из связанной покупки
        $serviceAccountId = $transaction->service_account_id;
        
        // Если в транзакции нет service_account_id, ищем в покупках
        if (!$serviceAccountId) {
            $purchase = \App\Models\Purchase::where('transaction_id', $transaction->id)->first();
            if ($purchase) {
                $serviceAccountId = $purchase->service_account_id;
            }
        }
        
        // Проверяем, что есть service_account_id
        if (!$serviceAccountId) {
            return response()->json([
                'success' => false,
                'message' => 'Эта покупка не поддерживает претензии',
            ], 422);
        }

        // Загружаем service_account если его нет
        if (!$transaction->serviceAccount) {
            $transaction->load('serviceAccount');
        }
        
        // Если все еще нет serviceAccount, загружаем вручную
        if (!$transaction->serviceAccount) {
            $serviceAccount = \App\Models\ServiceAccount::find($serviceAccountId);
            if (!$serviceAccount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Товар не найден',
                ], 422);
            }
            $supplierId = $serviceAccount->supplier_id;
        } else {
            // Получаем supplier_id из service_account
            // Если supplier_id = NULL, значит товар от администратора
            $supplierId = $transaction->serviceAccount->supplier_id;
        }

        // Обработка скриншота
        $screenshotUrl = null;
        $screenshotType = null;

        if ($request->hasFile('screenshot_file')) {
            // Загрузка файла
            $file = $request->file('screenshot_file');
            $fileName = 'dispute_' . time() . '_' . $request->user()->id . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('disputes/screenshots', $fileName, 'public');
            $screenshotUrl = '/storage/' . $path;
            $screenshotType = 'upload';
        } elseif ($request->filled('screenshot_link')) {
            // Использование ссылки
            $screenshotUrl = $validated['screenshot_link'];
            $screenshotType = 'link';
        }

        // Создаем претензию
        $dispute = ProductDispute::create([
            'transaction_id' => $transaction->id,
            'user_id' => $request->user()->id,
            'supplier_id' => $supplierId,
            'service_account_id' => $serviceAccountId, // ИСПРАВЛЕНО: Используем полученный ID
            'reason' => $validated['reason'],
            'customer_description' => $validated['description'],
            'screenshot_url' => $screenshotUrl,
            'screenshot_type' => $screenshotType,
            'status' => ProductDispute::STATUS_NEW,
            'refund_amount' => $transaction->amount, // Сумма возврата = сумма транзакции
        ]);

        // Очищаем кеш счетчика новых претензий
        Cache::forget('disputes_new_count');

        // Отправляем уведомление администратору о новой претензии
        $reasonText = $dispute->getReasonText();
        NotifierService::send(
            'dispute_created',
            'Новая претензия на товар',
            "Пользователь {$request->user()->email} создал претензию #{$dispute->id} на товар. Причина: {$reasonText}",
            'warning'
        );

        return ApiResponse::success([
            'message' => 'Претензия успешно создана. Ожидайте решения администратора.',
            'dispute' => [
                'id' => $dispute->id,
                'status' => $dispute->status,
                'created_at' => $dispute->created_at->format('Y-m-d H:i:s'),
            ],
        ], 201);
    }

    /**
     * Получить детали конкретной претензии
     */
    public function show(Request $request, $id)
    {
        // Получаем locale из заголовка X-Locale
        $locale = $request->header('X-Locale') ?? $request->query('locale') ?? app()->getLocale();
        if (!in_array($locale, array_keys(config('langs')))) {
            $locale = app()->getLocale();
        }

        $dispute = $request->user()
            ->disputes()
            ->with([
                'transaction.serviceAccount' => function($q) {
                    $q->select('id', 'title', 'title_en', 'title_uk');
                },
                'serviceAccount' => function($q) {
                    $q->select('id', 'title', 'title_en', 'title_uk', 'login');
                }
            ])
            ->findOrFail($id);

        // Возвращаем все названия товара для локализации на frontend
        $productTitle = $dispute->serviceAccount ? [
            'title' => $dispute->serviceAccount->title,
            'title_en' => $dispute->serviceAccount->title_en,
            'title_uk' => $dispute->serviceAccount->title_uk,
        ] : null;

        return response()->json([
            'dispute' => [
                'id' => $dispute->id,
                'transaction_id' => $dispute->transaction_id,
                'product_title' => $productTitle, // Объект с названиями на всех языках
                'product_login' => $dispute->serviceAccount->login ?? null,
                'amount' => $dispute->transaction->amount,
                'reason' => $dispute->reason,
                'reason_text' => $dispute->getReasonText($locale),
                'customer_description' => $dispute->customer_description,
                'screenshot_url' => $dispute->screenshot_url,
                'screenshot_type' => $dispute->screenshot_type,
                'status' => $dispute->status,
                'status_text' => $dispute->getStatusText($locale),
                'admin_decision' => $dispute->admin_decision,
                'admin_decision_text' => $dispute->getDecisionText($locale),
                'admin_comment' => $dispute->admin_comment,
                'refund_amount' => $dispute->refund_amount,
                'created_at' => $dispute->created_at->format('Y-m-d H:i:s'),
                'resolved_at' => $dispute->resolved_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Проверить, можно ли создать претензию на транзакцию
     */
    public function canDispute(Request $request, $transactionId)
    {
        $transaction = Transaction::with('serviceAccount')
            ->where('id', $transactionId)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$transaction) {
            return response()->json([
                'can_dispute' => false,
                'reason' => 'Транзакция не найдена',
            ]);
        }

        // Проверяем условия
        $checks = [
            'exists' => $transaction->dispute()->exists(),
            'has_service_account' => (bool) $transaction->service_account_id,
            'not_expired' => $transaction->created_at->diffInDays(now()) <= 30,
            'status_ok' => in_array($transaction->status, ['completed', 'success', null]),
        ];

        $canDispute = !$checks['exists'] 
            && $checks['has_service_account'] 
            && $checks['not_expired']
            && $checks['status_ok'];

        $reason = null;
        if ($checks['exists']) {
            $reason = 'Претензия уже создана';
        } elseif (!$checks['has_service_account']) {
            $reason = 'Эта покупка не поддерживает претензии';
        } elseif (!$checks['not_expired']) {
            $reason = 'Срок подачи претензии истек';
        } elseif (!$checks['status_ok']) {
            $reason = 'Статус транзакции не позволяет создать претензию';
        }

        return response()->json([
            'can_dispute' => $canDispute,
            'reason' => $reason,
            'checks' => $checks,
        ]);
    }

}
