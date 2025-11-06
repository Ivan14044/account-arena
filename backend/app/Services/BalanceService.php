<?php

namespace App\Services;

use App\Models\User;
use App\Models\Transaction;
use App\Models\BalanceTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для централизованного управления балансом пользователя
 * 
 * Этот сервис обеспечивает:
 * - Безопасное пополнение баланса с транзакциями
 * - Списание средств с баланса
 * - Полную историю операций с балансом
 * - Защиту от дублирования операций
 * - Автоматическое логирование всех действий
 */
class BalanceService
{
    /**
     * Типы операций с балансом
     */
    const TYPE_TOPUP_CARD = 'topup_card';           // Пополнение картой
    const TYPE_TOPUP_CRYPTO = 'topup_crypto';       // Пополнение криптовалютой
    const TYPE_TOPUP_ADMIN = 'topup_admin';         // Пополнение администратором
    const TYPE_TOPUP_VOUCHER = 'topup_voucher';     // Пополнение ваучером
    const TYPE_DEDUCTION = 'deduction';             // Списание средств
    const TYPE_REFUND = 'refund';                   // Возврат средств
    const TYPE_PURCHASE = 'purchase';               // Покупка товара
    const TYPE_ADJUSTMENT = 'adjustment';           // Корректировка администратором
    
    /**
     * Статусы операций
     */
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';
    
    /**
     * Пополнить баланс пользователя
     * 
     * @param User $user Пользователь
     * @param float $amount Сумма пополнения (должна быть положительной)
     * @param string $type Тип операции (см. константы TYPE_*)
     * @param array $metadata Дополнительные данные (invoice_id, order_id и т.д.)
     * @return BalanceTransaction|null
     * @throws \Exception
     */
    public function topUp(User $user, float $amount, string $type, array $metadata = []): ?BalanceTransaction
    {
        // Валидация суммы
        if ($amount <= 0) {
            Log::error('Попытка пополнения с недопустимой суммой', [
                'user_id' => $user->id,
                'amount' => $amount,
            ]);
            throw new \InvalidArgumentException('Сумма пополнения должна быть больше нуля');
        }
        
        // Округляем до 2 знаков после запятой
        $amount = round($amount, 2);
        
        // Проверка на дублирование операции (если указан invoice_id или order_id)
        if (isset($metadata['invoice_id']) || isset($metadata['order_id'])) {
            $existingTransaction = $this->findDuplicateTransaction($user->id, $metadata);
            if ($existingTransaction) {
                Log::warning('Попытка дублирования операции пополнения', [
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'metadata' => $metadata,
                    'existing_transaction_id' => $existingTransaction->id,
                ]);
                return $existingTransaction;
            }
        }
        
        // Выполняем операцию в транзакции базы данных
        return DB::transaction(function () use ($user, $amount, $type, $metadata) {
            // Сохраняем старый баланс
            $oldBalance = $user->balance ?? 0;
            
            // Пополняем баланс
            $newBalance = $oldBalance + $amount;
            $user->balance = $newBalance;
            $user->save();
            
            // Создаем запись о транзакции баланса
            $balanceTransaction = BalanceTransaction::create([
                'user_id' => $user->id,
                'type' => $type,
                'amount' => $amount,
                'balance_before' => $oldBalance,
                'balance_after' => $newBalance,
                'status' => self::STATUS_COMPLETED,
                'metadata' => $metadata,
                'description' => $this->generateDescription($type, $amount),
            ]);
            
            // Также создаем запись в старой таблице transactions для совместимости
            Transaction::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'currency' => \App\Models\Option::get('currency', 'USD'),
                'payment_method' => $this->mapTypeToPaymentMethod($type),
                'status' => 'completed',
                'metadata' => array_merge($metadata, [
                    'balance_transaction_id' => $balanceTransaction->id,
                    'old_balance' => $oldBalance,
                    'new_balance' => $newBalance,
                ]),
            ]);
            
            Log::info('Баланс успешно пополнен', [
                'user_id' => $user->id,
                'amount' => $amount,
                'type' => $type,
                'old_balance' => $oldBalance,
                'new_balance' => $newBalance,
                'transaction_id' => $balanceTransaction->id,
            ]);
            
            return $balanceTransaction;
        });
    }
    
    /**
     * Списать средства с баланса пользователя
     * 
     * @param User $user Пользователь
     * @param float $amount Сумма списания (должна быть положительной)
     * @param string $type Тип операции (см. константы TYPE_*)
     * @param array $metadata Дополнительные данные
     * @return BalanceTransaction|null
     * @throws \Exception
     */
    public function deduct(User $user, float $amount, string $type = self::TYPE_DEDUCTION, array $metadata = []): ?BalanceTransaction
    {
        // Валидация суммы
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Сумма списания должна быть больше нуля');
        }
        
        // Округляем до 2 знаков после запятой
        $amount = round($amount, 2);
        
        // Проверяем достаточность средств
        $currentBalance = $user->balance ?? 0;
        if ($currentBalance < $amount) {
            Log::error('Недостаточно средств для списания', [
                'user_id' => $user->id,
                'amount' => $amount,
                'current_balance' => $currentBalance,
            ]);
            throw new \Exception('Недостаточно средств на балансе');
        }
        
        // Выполняем операцию в транзакции базы данных
        return DB::transaction(function () use ($user, $amount, $type, $metadata) {
            // Сохраняем старый баланс
            $oldBalance = $user->balance ?? 0;
            
            // Списываем средства
            $newBalance = $oldBalance - $amount;
            $user->balance = $newBalance;
            $user->save();
            
            // Создаем запись о транзакции баланса
            $balanceTransaction = BalanceTransaction::create([
                'user_id' => $user->id,
                'type' => $type,
                'amount' => -$amount, // Отрицательное значение для списания
                'balance_before' => $oldBalance,
                'balance_after' => $newBalance,
                'status' => self::STATUS_COMPLETED,
                'metadata' => $metadata,
                'description' => $this->generateDescription($type, $amount),
            ]);
            
            // Также создаем запись в старой таблице transactions для совместимости
            Transaction::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'currency' => \App\Models\Option::get('currency', 'USD'),
                'payment_method' => $this->mapTypeToPaymentMethod($type),
                'status' => 'completed',
                'metadata' => array_merge($metadata, [
                    'balance_transaction_id' => $balanceTransaction->id,
                    'old_balance' => $oldBalance,
                    'new_balance' => $newBalance,
                ]),
            ]);
            
            Log::info('Средства успешно списаны с баланса', [
                'user_id' => $user->id,
                'amount' => $amount,
                'type' => $type,
                'old_balance' => $oldBalance,
                'new_balance' => $newBalance,
                'transaction_id' => $balanceTransaction->id,
            ]);
            
            return $balanceTransaction;
        });
    }
    
    /**
     * Получить историю операций с балансом пользователя
     * 
     * @param User $user
     * @param int $limit Количество записей (по умолчанию 50)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getHistory(User $user, int $limit = 50)
    {
        return BalanceTransaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
    
    /**
     * Получить баланс пользователя
     * 
     * @param User $user
     * @return float
     */
    public function getBalance(User $user): float
    {
        return round($user->balance ?? 0, 2);
    }
    
    /**
     * Проверить достаточность средств
     * 
     * @param User $user
     * @param float $amount
     * @return bool
     */
    public function hasSufficientFunds(User $user, float $amount): bool
    {
        return $this->getBalance($user) >= $amount;
    }
    
    /**
     * Найти дублирующуюся транзакцию
     * 
     * @param int $userId
     * @param array $metadata
     * @return BalanceTransaction|null
     */
    private function findDuplicateTransaction(int $userId, array $metadata): ?BalanceTransaction
    {
        $query = BalanceTransaction::where('user_id', $userId)
            ->where('created_at', '>', now()->subHours(24))
            ->where('status', self::STATUS_COMPLETED);
        
        // Проверяем наличие invoice_id
        if (isset($metadata['invoice_id'])) {
            $query->whereRaw("JSON_EXTRACT(metadata, '$.invoice_id') = ?", [$metadata['invoice_id']]);
            $result = $query->first();
            if ($result) {
                return $result;
            }
        }
        
        // Проверяем наличие order_id
        if (isset($metadata['order_id'])) {
            $query->whereRaw("JSON_EXTRACT(metadata, '$.order_id') = ?", [$metadata['order_id']]);
            return $query->first();
        }
        
        return null;
    }
    
    /**
     * Генерировать описание операции
     * 
     * @param string $type
     * @param float $amount
     * @return string
     */
    private function generateDescription(string $type, float $amount): string
    {
        $currency = \App\Models\Option::get('currency', 'USD');
        $formattedAmount = number_format($amount, 2, '.', '') . ' ' . strtoupper($currency);
        
        $descriptions = [
            self::TYPE_TOPUP_CARD => "Пополнение баланса картой: {$formattedAmount}",
            self::TYPE_TOPUP_CRYPTO => "Пополнение баланса криптовалютой: {$formattedAmount}",
            self::TYPE_TOPUP_ADMIN => "Пополнение баланса администратором: {$formattedAmount}",
            self::TYPE_TOPUP_VOUCHER => "Пополнение баланса ваучером: {$formattedAmount}",
            self::TYPE_DEDUCTION => "Списание с баланса: {$formattedAmount}",
            self::TYPE_REFUND => "Возврат средств на баланс: {$formattedAmount}",
            self::TYPE_PURCHASE => "Оплата покупки с баланса: {$formattedAmount}",
            self::TYPE_ADJUSTMENT => "Корректировка баланса администратором: {$formattedAmount}",
        ];
        
        return $descriptions[$type] ?? "Операция с балансом: {$formattedAmount}";
    }
    
    /**
     * Маппинг типа операции на payment_method для старой таблицы transactions
     * 
     * @param string $type
     * @return string
     */
    private function mapTypeToPaymentMethod(string $type): string
    {
        $mapping = [
            self::TYPE_TOPUP_CARD => 'balance_topup_card',
            self::TYPE_TOPUP_CRYPTO => 'balance_topup_crypto',
            self::TYPE_TOPUP_ADMIN => 'admin_balance_topup',
            self::TYPE_TOPUP_VOUCHER => 'voucher_balance_topup',
            self::TYPE_DEDUCTION => 'balance_deduction',
            self::TYPE_REFUND => 'balance_refund',
            self::TYPE_PURCHASE => 'balance',
            self::TYPE_ADJUSTMENT => 'admin_balance_adjustment',
        ];
        
        return $mapping[$type] ?? 'balance_operation';
    }
}

