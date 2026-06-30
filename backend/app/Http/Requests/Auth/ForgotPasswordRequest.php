<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\ApiRequest;

class ForgotPasswordRequest extends ApiRequest
{
    public function rules(): array
    {
        // Без `exists:users,email`: не раскрываем, зарегистрирован ли email
        // (user enumeration). Существование проверяет broker, ответ — generic.
        return [
            'email' => ['required', 'email'],
        ];
    }
}


