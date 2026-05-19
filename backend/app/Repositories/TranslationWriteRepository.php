<?php

namespace App\Repositories;

use App\Models\Locale;
use App\Models\Tag;
use App\Models\Translation;
use App\Models\TranslationKey;
use Illuminate\Support\Str;

class TranslationWriteRepository
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function resolveLocale(array $data): Locale
    {
        $code = strtolower((string) $data['locale']);

        /** @var Locale $locale */
        $locale = Locale::query()->updateOrCreate(
            ['code' => $code],
            [
                'name' => $data['locale_name'] ?? strtoupper($code),
                'is_active' => true,
            ],
        );

        return $locale;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function resolveTranslationKey(array $data): TranslationKey
    {
        /** @var TranslationKey $translationKey */
        $translationKey = TranslationKey::query()->firstOrCreate(
            ['key' => $data['key']],
            ['description' => $data['description'] ?? null],
        );

        if (array_key_exists('description', $data) && $translationKey->description !== $data['description']) {
            $translationKey->forceFill([
                'description' => $data['description'],
            ])->save();
        }

        return $translationKey;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createTranslation(array $attributes): Translation
    {
        /** @var Translation $translation */
        $translation = Translation::query()->create($attributes);

        return $translation;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateTranslation(Translation $translation, array $attributes): Translation
    {
        $translation->forceFill($attributes)->save();

        return $translation;
    }

    public function deleteTranslation(Translation $translation): void
    {
        $translation->delete();
    }

    public function translationExists(
        TranslationKey $translationKey,
        Locale $locale,
        ?Translation $except = null,
    ): bool {
        return Translation::query()
            ->where('translation_key_id', $translationKey->getKey())
            ->where('locale_id', $locale->getKey())
            ->when(
                $except instanceof Translation,
                fn ($query) => $query->whereKeyNot($except->getKey()),
            )
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function syncTagsWhenProvided(TranslationKey $translationKey, array $data): void
    {
        if (! array_key_exists('tags', $data)) {
            return;
        }

        $tagIds = collect($data['tags'] ?? [])
            ->map(fn (string $tag): string => Str::slug($tag))
            ->filter()
            ->unique()
            ->map(function (string $slug): int {
                /** @var Tag $tag */
                $tag = Tag::query()->firstOrCreate(
                    ['slug' => $slug],
                    ['name' => Str::headline($slug)],
                );

                return (int) $tag->getKey();
            })
            ->values()
            ->all();

        $translationKey->tags()->sync($tagIds);
    }
}
