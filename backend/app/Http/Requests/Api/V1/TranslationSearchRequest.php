<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class TranslationSearchRequest extends FormRequest
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
            'locale' => ['nullable', 'string', 'max:12'],
            'tag' => ['nullable', 'string', 'max:64'],
            'tags' => ['nullable', 'string', 'max:500'],
            'key' => ['nullable', 'string', 'max:191'],
            'content' => ['nullable', 'string', 'max:255'],
            'q' => ['nullable', 'string', 'max:255'],
            'is_published' => ['nullable', 'boolean'],
            'match_all_tags' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
