<?php

namespace App\Services;

use App\Models\Translation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class TranslationQueryService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Translation>
     */
    public function search(array $filters): LengthAwarePaginator
    {
        $query = Translation::query()
            ->with(['locale', 'translationKey.tags']);

        $this->applyFilters($query, $filters);

        $perPage = min(max((int) ($filters['per_page'] ?? 15), 1), 100);

        return $query
            ->orderByDesc('translations.updated_at')
            ->orderByDesc('translations.id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  Builder<Translation>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['locale'])) {
            $locale = strtolower((string) $filters['locale']);

            $query->whereHas('locale', fn (Builder $builder): Builder => $builder->where('code', $locale));
        }

        if (array_key_exists('is_published', $filters) && $filters['is_published'] !== null) {
            $query->where('is_published', filter_var($filters['is_published'], FILTER_VALIDATE_BOOL));
        }

        if (! empty($filters['key'])) {
            $key = $this->likeTerm((string) $filters['key']);

            $query->whereHas('translationKey', fn (Builder $builder): Builder => $builder->where('key', 'like', "%{$key}%"));
        }

        if (! empty($filters['content'])) {
            $content = $this->likeTerm((string) $filters['content']);

            $query->where('value', 'like', "%{$content}%");
        }

        if (! empty($filters['q'])) {
            $term = $this->likeTerm((string) $filters['q']);

            $query->where(function (Builder $builder) use ($term): void {
                $builder
                    ->where('value', 'like', "%{$term}%")
                    ->orWhereHas('translationKey', fn (Builder $keyBuilder): Builder => $keyBuilder->where('key', 'like', "%{$term}%"));
            });
        }

        $tags = $this->tagsFromFilters($filters);

        if ($tags === []) {
            return;
        }

        if (filter_var($filters['match_all_tags'] ?? false, FILTER_VALIDATE_BOOL)) {
            foreach ($tags as $tag) {
                $query->whereHas(
                    'translationKey.tags',
                    fn (Builder $builder): Builder => $builder->where('slug', $tag),
                );
            }

            return;
        }

        $query->whereHas(
            'translationKey.tags',
            fn (Builder $builder): Builder => $builder->whereIn('slug', $tags),
        );
    }

    private function likeTerm(string $value): string
    {
        return addcslashes(trim($value), '\\%_');
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
