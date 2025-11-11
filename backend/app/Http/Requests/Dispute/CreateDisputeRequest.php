<?php

namespace App\Http\Requests\Dispute;

use App\Http\Requests\ApiRequest;

class CreateDisputeRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'transaction_id' => ['required', 'exists:transactions,id'],
            'reason' => ['required', 'in:invalid_account,wrong_data,not_working,already_used,banned,other'],
            'description' => ['required', 'string', 'min:3', 'max:1000'],
            'screenshot_file' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'screenshot_link' => ['nullable', 'url', 'max:500'],
        ];
    }
}


