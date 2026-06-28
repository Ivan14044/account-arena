<?php

namespace Tests\Feature;

use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Тест извлечённого метода идемпотентности вебхуков (шаг 3 рефакторинга):
 * Transaction::claimForCompletion() заменил 6 копий compare-and-set.
 */
class TransactionClaimTest extends TestCase
{
    use RefreshDatabase;

    public function test_claim_for_completion_wins_once_then_loses(): void
    {
        $tx = Transaction::factory()->create(['status' => Transaction::STATUS_PENDING]);

        // Первый вызов «застолбил» транзакцию: true + статус completed (и в памяти, и в БД).
        $this->assertTrue($tx->claimForCompletion());
        $this->assertSame(Transaction::STATUS_COMPLETED, $tx->status);
        $this->assertSame(Transaction::STATUS_COMPLETED, $tx->fresh()->status);

        // Повторный вебхук (другой инстанс той же транзакции) проигрывает гонку.
        $duplicate = Transaction::find($tx->id);
        $this->assertFalse($duplicate->claimForCompletion());
    }

    public function test_claim_for_completion_returns_false_if_already_completed(): void
    {
        $tx = Transaction::factory()->create(['status' => Transaction::STATUS_COMPLETED]);

        $this->assertFalse($tx->claimForCompletion());
    }
}
