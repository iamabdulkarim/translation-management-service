<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc'],
            'password' => ['required', 'string'],
            'token_name' => ['nullable', 'string', 'max:100'],
            'abilities' => ['nullable', 'array', 'max:20'],
            'abilities.*' => ['required', 'string', 'distinct', 'max:80', 'regex:/^[a-z*:_-]+$/i'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
