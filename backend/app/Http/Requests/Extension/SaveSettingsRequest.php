<?php

namespace App\Http\Requests\Extension;

use App\Http\Requests\ApiRequest;

class SaveSettingsRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'settings' => ['required', 'array'],
        ];
    }
}


