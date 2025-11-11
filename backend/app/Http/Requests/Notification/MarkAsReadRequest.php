<?php

namespace App\Http\Requests\Notification;

use App\Http\Requests\ApiRequest;

class MarkAsReadRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ];
    }
}


