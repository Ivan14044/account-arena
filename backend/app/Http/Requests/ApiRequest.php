<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Базовый API-запрос с унифицированной JSON-ошибкой валидации.
 */
abstract class ApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator): void
    {
        // Совместим с текущей формой ошибок в проекте: ['errors' => {...}]
        throw new HttpResponseException(
            response()->json(['errors' => $validator->errors()], 422)
        );
    }
}


