<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\User;
use App\Models\ServiceAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory для создания тестовых транзакций
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'guest_email' => null,
            'amount' => fake()->randomFloat(2, 10, 500),
            'currency' => 'USD',
            'payment_method' => fake()->randomElement(['balance', 'crypto', 'card']),
            'service_account_id' => null,
            'subscription_id' => null,
            'status' => 'completed',
        ];
    }

    /**
     * Транзакция для товара
     */
    public function forProduct(ServiceAccount $product): static
    {
        return $this->state(fn (array $attributes) => [
            'service_account_id' => $product->id,
        ]);
    }

    /**
     * Гостевая транзакция
     */
    public function guest(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
            'guest_email' => fake()->email(),
        ]);
    }

    /**
     * Pending транзакция
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Неудачная транзакция
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
        ]);
    }
}



