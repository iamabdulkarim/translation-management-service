<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreTranslationRequest extends FormRequest
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
            'key' => ['required', 'string', 'max:191', 'regex:/^[A-Za-z0-9_.-]+$/'],
            'description' => ['nullable', 'string', 'max:1000'],
            'locale' => ['required', 'string', 'max:12', 'regex:/^[A-Za-z]{2,3}(-[A-Za-z0-9]{2,8})?$/'],
            'locale_name' => ['nullable', 'string', 'max:100'],
            'value' => ['required', 'string', 'max:65535'],
            'tags' => ['nullable', 'array', 'max:20'],
            'tags.*' => ['required', 'string', 'distinct:ignore_case', 'max:64'],
            'is_published' => ['sometimes', 'boolean'],
        ];
    }
}
