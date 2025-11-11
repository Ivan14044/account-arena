<?php

namespace Database\Factories;

use App\Models\ServiceAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory для создания тестовых товаров
 */
class ServiceAccountFactory extends Factory
{
    protected $model = ServiceAccount::class;

    public function definition(): array
    {
        return [
            'sku' => ServiceAccount::generateSku(),
            'title' => fake()->words(3, true),
            'title_en' => fake()->words(3, true),
            'title_uk' => fake()->words(3, true),
            'description' => fake()->paragraph(),
            'description_en' => fake()->paragraph(),
            'description_uk' => fake()->paragraph(),
            'price' => fake()->randomFloat(2, 10, 500),
            'discount_percent' => 0,
            'is_active' => true,
            'image_url' => null,
            'category_id' => null,
            'supplier_id' => null,
            'accounts_data' => $this->generateAccounts(10),
            'used' => 0,
        ];
    }

    /**
     * Товар с конкретным количеством аккаунтов
     */
    public function withAccounts(int $count): static
    {
        return $this->state(fn (array $attributes) => [
            'accounts_data' => $this->generateAccounts($count),
        ]);
    }

    /**
     * Товар со скидкой
     */
    public function withDiscount(int $percent): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_percent' => $percent,
        ]);
    }

    /**
     * Неактивный товар
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Товар без аккаунтов (out of stock)
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'accounts_data' => [],
            'used' => 0,
        ]);
    }

    /**
     * Товар с частично проданными аккаунтами
     */
    public function partiallySold(int $total = 10, int $sold = 5): static
    {
        return $this->state(fn (array $attributes) => [
            'accounts_data' => $this->generateAccounts($total),
            'used' => $sold,
        ]);
    }

    /**
     * Генерация тестовых аккаунтов
     */
    private function generateAccounts(int $count): array
    {
        $accounts = [];
        for ($i = 0; $i < $count; $i++) {
            $accounts[] = fake()->userName() . ':' . fake()->password();
        }
        return $accounts;
    }
}



