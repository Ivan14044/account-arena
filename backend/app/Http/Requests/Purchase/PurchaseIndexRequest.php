<?php

namespace App\Http\Requests\Purchase;

use App\Http\Requests\ApiRequest;

class PurchaseIndexRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date'],
            'status' => ['sometimes', 'string'],
        ];
    }
}


