<?php

namespace App\Http\Requests\Cart;

use App\Http\Requests\ApiRequest;

class CancelSubscriptionRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'subscription_id' => ['required', 'integer'],
        ];
    }
}


