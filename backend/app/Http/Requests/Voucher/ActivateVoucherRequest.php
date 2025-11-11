<?php

namespace App\Http\Requests\Voucher;

use App\Http\Requests\ApiRequest;

class ActivateVoucherRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'code' => ['required', 'string'],
        ];
    }
}


