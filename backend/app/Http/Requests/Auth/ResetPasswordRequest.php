<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\ApiRequest;

class ResetPasswordRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'token' => ['required'],
            'email' => ['required', 'email', 'exists:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ];
    }
}


