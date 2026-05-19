<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['slug', 'name'])]
class Tag extends Model
{
    use HasFactory;

    /**
     * @return BelongsToMany<TranslationKey, $this>
     */
    public function translationKeys(): BelongsToMany
    {
        return $this->belongsToMany(TranslationKey::class, 'translation_key_tag')
            ->withTimestamps();
    }
}
