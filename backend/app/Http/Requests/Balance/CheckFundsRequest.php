<?php

namespace App\Http\Requests\Balance;

use App\Http\Requests\ApiRequest;

class CheckFundsRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}


