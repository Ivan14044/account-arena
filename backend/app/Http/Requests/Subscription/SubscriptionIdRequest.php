<?php

namespace App\Http\Requests\Subscription;

use App\Http\Requests\ApiRequest;

class SubscriptionIdRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'subscription_id' => ['required', 'integer'],
        ];
    }
}


