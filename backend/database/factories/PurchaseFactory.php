<?php

namespace Database\Factories;

use App\Models\Purchase;
use App\Models\User;
use App\Models\ServiceAccount;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory для создания тестовых покупок
 */
class PurchaseFactory extends Factory
{
    protected $model = Purchase::class;

    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 3);
        $price = fake()->randomFloat(2, 10, 100);
        $total = $price * $quantity;

        return [
            'order_number' => Purchase::generateOrderNumber(),
            'user_id' => User::factory(),
            'guest_email' => null,
            'service_account_id' => ServiceAccount::factory(),
            'transaction_id' => Transaction::factory(),
            'quantity' => $quantity,
            'price' => $price,
            'total_amount' => $total,
            'account_data' => $this->generateAccountData($quantity),
            'status' => 'completed',
        ];
    }

    /**
     * Гостевая покупка
     */
    public function guest(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
            'guest_email' => fake()->email(),
        ]);
    }

    /**
     * Покупка для конкретного пользователя
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
            'guest_email' => null,
        ]);
    }

    /**
     * Покупка конкретного товара
     */
    public function forProduct(ServiceAccount $product): static
    {
        return $this->state(fn (array $attributes) => [
            'service_account_id' => $product->id,
        ]);
    }

    /**
     * Генерация тестовых данных аккаунтов
     */
    private function generateAccountData(int $quantity): array
    {
        $data = [];
        for ($i = 0; $i < $quantity; $i++) {
            $data[] = fake()->userName() . ':' . fake()->password();
        }
        return $data;
    }
}



