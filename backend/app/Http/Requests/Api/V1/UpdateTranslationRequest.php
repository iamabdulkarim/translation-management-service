<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTranslationRequest extends FormRequest
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
            'key' => ['sometimes', 'string', 'max:191', 'regex:/^[A-Za-z0-9_.-]+$/'],
            'description' => ['nullable', 'string', 'max:1000'],
            'locale' => ['sometimes', 'string', 'max:12', 'regex:/^[A-Za-z]{2,3}(-[A-Za-z0-9]{2,8})?$/'],
            'locale_name' => ['nullable', 'string', 'max:100'],
            'value' => ['sometimes', 'string', 'max:65535'],
            'tags' => ['sometimes', 'array', 'max:20'],
            'tags.*' => ['required', 'string', 'distinct:ignore_case', 'max:64'],
            'is_published' => ['sometimes', 'boolean'],
        ];
    }
}
