<?php

namespace App\Console\Commands;

use App\Models\ServiceAccount;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Console\Command;

class CreateTestPurchase extends Command
{
    protected $signature = 'test:create-purchase {user_id} {--amount=9.99} {--product-title=Netflix Premium Test}';
    protected $description = 'Создать тестовую покупку для пользователя';

    public function handle()
    {
        $userId = $this->argument('user_id');
        $amount = $this->option('amount');
        $productTitle = $this->option('product-title');

        // Проверяем пользователя
        $user = User::find($userId);
        if (!$user) {
            $this->error("Пользователь с ID {$userId} не найден!");
            return 1;
        }

        $this->info("Создаем тестовую покупку для пользователя: {$user->name} ({$user->email})");

        // Создаем тестовый товар (от администратора)
        $product = ServiceAccount::create([
            'title' => $productTitle,
            'title_en' => $productTitle,
            'title_uk' => $productTitle,
            'description' => 'Тестовый товар для проверки системы возврата',
            'price' => $amount,
            'is_active' => true,
            'supplier_id' => null, // Товар администратора
            'category_id' => 1, // Предполагаем что категория существует
            'accounts_data' => [
                'login:test@example.com',
                'password:TestPassword123'
            ],
            'used' => 0,
        ]);

        $this->info("✓ Создан товар: {$product->title} (ID: {$product->id})");

        // Создаем транзакцию (покупку)
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'service_account_id' => $product->id,
            'amount' => $amount,
            'type' => 'purchase',
            'status' => 'completed',
            'description' => "Покупка: {$product->title}",
            'credentials' => [
                'login' => 'test@example.com',
                'password' => 'TestPassword123'
            ],
        ]);

        // Увеличиваем счетчик использования
        $product->increment('used');

        $this->info("✓ Создана транзакция ID: {$transaction->id}");
        $this->info("✓ Сумма: \${$amount}");
        $this->info("✓ Статус: completed");
        
        $this->newLine();
        $this->line("═══════════════════════════════════════");
        $this->info("✅ ТЕСТОВАЯ ПОКУПКА СОЗДАНА!");
        $this->line("═══════════════════════════════════════");
        $this->info("Пользователь: {$user->name}");
        $this->info("Email: {$user->email}");
        $this->info("Товар: {$product->title}");
        $this->info("ID транзакции: {$transaction->id}");
        $this->info("Сумма: \${$amount}");
        $this->newLine();
        $this->comment("Теперь пользователь может:");
        $this->comment("1. Зайти в личный кабинет");
        $this->comment("2. Открыть раздел 'Покупки'");
        $this->comment("3. Создать претензию на этот товар");
        $this->comment("4. Прикрепить скриншот");
        $this->newLine();
        $this->comment("Администратор может:");
        $this->comment("1. Открыть /admin/disputes");
        $this->comment("2. Увидеть претензию");
        $this->comment("3. Сделать возврат средств или замену");

        return 0;
    }
}




