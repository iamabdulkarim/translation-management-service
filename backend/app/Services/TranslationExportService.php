<?php

namespace App\Services;

use Generator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class TranslationExportService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Generator<string>
     */
    public function streamLocale(string $locale, array $filters = []): Generator
    {
        $exported = 0;

        try {
            yield '{"success":true';
            yield ',"message":"Translations exported successfully."';
            yield ',"locale":'.json_encode($locale);
            yield ',"generated_at":'.json_encode(now()->toISOString());
            yield ',"translations":{';

            $first = true;

            foreach ($this->baseQuery($locale, $filters)->cursor() as $row) {
                if (! $first) {
                    yield ',';
                }

                yield json_encode((string) $row->key).':'.json_encode((string) $row->value);

                $first = false;
                $exported++;
            }

            yield '}}';

            Log::info('translation.export_completed', [
                'locale' => $locale,
                'filters' => $filters,
                'translations' => $exported,
            ]);
        } catch (Throwable $exception) {
            Log::error('translation.export_failed', [
                'locale' => $locale,
                'filters' => $filters,
                'translations' => $exported,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
            report($exception);

            throw $exception;
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function baseQuery(string $locale, array $filters): Builder
    {
        $query = DB::table('translations')
            ->join('locales', 'locales.id', '=', 'translations.locale_id')
            ->join('translation_keys', 'translation_keys.id', '=', 'translations.translation_key_id')
            ->where('locales.code', strtolower($locale))
            ->where('translations.is_published', true)
            ->orderBy('translation_keys.key')
            ->select([
                'translation_keys.key',
                'translations.value',
            ]);

        $tags = $this->tagsFromFilters($filters);

        if ($tags === []) {
            return $query;
        }

        return $query
            ->join('translation_key_tag', 'translation_key_tag.translation_key_id', '=', 'translation_keys.id')
            ->join('tags', 'tags.id', '=', 'translation_key_tag.tag_id')
            ->whereIn('tags.slug', $tags)
            ->distinct();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, string>
     */
    private function tagsFromFilters(array $filters): array
    {
        $tags = [];

        if (! empty($filters['tag'])) {
            $tags[] = (string) $filters['tag'];
        }

        if (! empty($filters['tags'])) {
            $tags = [
                ...$tags,
                ...explode(',', (string) $filters['tags']),
            ];
        }

        return collect($tags)
            ->map(fn (string $tag): string => Str::slug($tag))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
