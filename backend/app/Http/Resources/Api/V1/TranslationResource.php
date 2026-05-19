<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TranslationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->translationKey?->key,
            'description' => $this->translationKey?->description,
            'locale' => [
                'code' => $this->locale?->code,
                'name' => $this->locale?->name,
            ],
            'value' => $this->value,
            'value_hash' => $this->value_hash,
            'tags' => $this->translationKey?->tags
                ? $this->translationKey->tags->pluck('slug')->values()
                : [],
            'is_published' => $this->is_published,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
