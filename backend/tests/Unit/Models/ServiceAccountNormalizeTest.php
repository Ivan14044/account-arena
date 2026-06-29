<?php

namespace Tests\Unit\Models;

use App\Models\ServiceAccount;
use PHPUnit\Framework\TestCase;

/**
 * Шаг 6.2: единая нормализация accounts_data (раньше дублировалась в модели и
 * ProductPurchaseService).
 */
class ServiceAccountNormalizeTest extends TestCase
{
    public function test_array_is_returned_as_is(): void
    {
        $this->assertSame(['a', 'b'], ServiceAccount::normalizeAccountsData(['a', 'b']));
    }

    public function test_json_string_is_decoded(): void
    {
        $this->assertSame(['a', 'b'], ServiceAccount::normalizeAccountsData(json_encode(['a', 'b'])));
    }

    public function test_null_empty_and_invalid_become_empty_array(): void
    {
        $this->assertSame([], ServiceAccount::normalizeAccountsData(null));
        $this->assertSame([], ServiceAccount::normalizeAccountsData(''));
        $this->assertSame([], ServiceAccount::normalizeAccountsData('not a json'));
        $this->assertSame([], ServiceAccount::normalizeAccountsData(123));
    }
}
