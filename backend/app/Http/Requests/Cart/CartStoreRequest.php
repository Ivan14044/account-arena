<?php

namespace App\Http\Requests\Cart;

use App\Http\Requests\ApiRequest;

class CartStoreRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'services' => ['nullable', 'array'],
            'services.*.id' => ['required', 'integer', 'exists:services,id'],
            'services.*.subscription_type' => ['required', 'in:trial,premium'],
            'products' => ['nullable', 'array'],
            'products.*.id' => ['required', 'integer', 'exists:service_accounts,id'],
            'products.*.quantity' => ['required', 'integer', 'min:1'],
            'payment_method' => ['required', 'in:credit_card,crypto,admin_bypass,free,balance'],
            'promocode' => ['nullable', 'string', 'required_if:payment_method,free'],
        ];
    }
}


