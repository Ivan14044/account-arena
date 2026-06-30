<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\ApiRequest;

class ResetPasswordRequest extends ApiRequest
{
    public function rules(): array
    {
        // Без `exists:users,email`: не раскрываем существование email при сбросе.
        // Валидность email+token проверяет broker, ошибка — generic.
        return [
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ];
    }
}


