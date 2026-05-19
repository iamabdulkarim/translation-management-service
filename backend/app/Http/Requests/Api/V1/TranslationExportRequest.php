<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class TranslationExportRequest extends FormRequest
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
            'tag' => ['nullable', 'string', 'max:64'],
            'tags' => ['nullable', 'string', 'max:500'],
        ];
    }
}
