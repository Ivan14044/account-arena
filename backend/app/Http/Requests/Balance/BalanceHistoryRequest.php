<?php

namespace App\Http\Requests\Balance;

use App\Http\Requests\ApiRequest;

class BalanceHistoryRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'type' => ['nullable', 'string', 'in:topup_card,topup_crypto,topup_admin,topup_voucher,deduction,refund,purchase,adjustment'],
            'status' => ['nullable', 'string', 'in:pending,completed,failed,cancelled'],
        ];
    }
}


