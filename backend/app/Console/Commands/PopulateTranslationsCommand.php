<?php

namespace App\Console\Commands;

use App\Models\Locale;
use App\Models\Tag;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class PopulateTranslationsCommand extends Command
{
    protected $signature = 'translations:populate
        {records=100000 : Number of translation rows to create}
        {--chunk=1000 : Number of rows inserted per batch}
        {--locales=en,fr,es : Comma-separated locale codes}
        {--tags=mobile,desktop,web : Comma-separated context tags}';

    protected $description = 'Populate the database with translation records for scalability testing.';

    public function handle(): int
    {
        $records = max((int) $this->argument('records'), 1);
        $chunkSize = min(max((int) $this->option('chunk'), 100), 5000);
        $localeCodes = $this->csvOption('locales');
        $tagSlugs = $this->csvOption('tags');

        if ($localeCodes->isEmpty()) {
            $localeCodes = collect(['en']);
        }

        if ($tagSlugs->isEmpty()) {
            $tagSlugs = collect(['web']);
        }

        $startedAt = microtime(true);

        try {
            $locales = $this->ensureLocales($localeCodes);
            $tags = $this->ensureTags($tagSlugs);
            $keysNeeded = (int) ceil($records / max($locales->count(), 1));
            $created = 0;

            $this->info("Populating {$records} translations across {$locales->count()} locales.");
            $bar = $this->output->createProgressBar($records);
            $bar->start();

            for ($offset = 0; $offset < $keysNeeded && $created < $records; $offset += $chunkSize) {
                $keyRows = [];
                $now = now();
                $upperBound = min($offset + $chunkSize, $keysNeeded);

                for ($index = $offset; $index < $upperBound; $index++) {
                    $keyRows[] = [
                        'key' => "seed.translation.{$index}",
                        'description' => "Generated translation key {$index}",
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                DB::transaction(function () use ($keyRows, $locales, $tags, &$created, $records, $bar, $now): void {
                    DB::table('translation_keys')->insertOrIgnore($keyRows);

                    $keys = DB::table('translation_keys')
                        ->whereIn('key', collect($keyRows)->pluck('key')->all())
                        ->pluck('id', 'key');

                    $translationRows = [];
                    $pivotRows = [];
                    $tagIds = $tags->pluck('id')->values();

                    foreach ($keys as $key => $keyId) {
                        $numericKey = (int) Str::afterLast((string) $key, '.');

                        foreach ($locales as $locale) {
                            if ($created >= $records) {
                                break 2;
                            }

                            $value = "Generated {$locale->code} translation {$numericKey}";

                            $translationRows[] = [
                                'translation_key_id' => $keyId,
                                'locale_id' => $locale->id,
                                'value' => $value,
                                'value_hash' => hash('sha256', $value),
                                'is_published' => true,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];

                            $tagId = $tagIds[$numericKey % max($tagIds->count(), 1)] ?? null;

                            if ($tagId !== null) {
                                $pivotRows[] = [
                                    'translation_key_id' => $keyId,
                                    'tag_id' => $tagId,
                                    'created_at' => $now,
                                    'updated_at' => $now,
                                ];
                            }

                            $created++;
                            $bar->advance();
                        }
                    }

                    if ($translationRows !== []) {
                        DB::table('translations')->insertOrIgnore($translationRows);
                    }

                    if ($pivotRows !== []) {
                        DB::table('translation_key_tag')->insertOrIgnore($pivotRows);
                    }
                }, 3);
            }

            $bar->finish();
            $this->newLine(2);
            $this->info(sprintf('Done in %.2fs.', microtime(true) - $startedAt));
            Log::info('translations.populate_completed', [
                'records_requested' => $records,
                'records_created' => $created,
                'chunk_size' => $chunkSize,
                'locales' => $localeCodes->all(),
                'tags' => $tagSlugs->all(),
                'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
            ]);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            Log::error('translations.populate_failed', [
                'records_requested' => $records,
                'chunk_size' => $chunkSize,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
            report($exception);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * @return Collection<int, string>
     */
    private function csvOption(string $option): Collection
    {
        return collect(explode(',', (string) $this->option($option)))
            ->map(fn (string $value): string => Str::slug($value))
            ->filter()
            ->unique()
            ->values();
    }

    /**
     * @param  Collection<int, string>  $localeCodes
     * @return Collection<int, object>
     */
    private function ensureLocales(Collection $localeCodes): Collection
    {
        foreach ($localeCodes as $code) {
            Locale::query()->updateOrCreate(
                ['code' => $code],
                ['name' => strtoupper($code), 'is_active' => true],
            );
        }

        return DB::table('locales')
            ->whereIn('code', $localeCodes->all())
            ->orderBy('code')
            ->get(['id', 'code']);
    }

    /**
     * @param  Collection<int, string>  $tagSlugs
     * @return Collection<int, object>
     */
    private function ensureTags(Collection $tagSlugs): Collection
    {
        foreach ($tagSlugs as $slug) {
            Tag::query()->updateOrCreate(
                ['slug' => $slug],
                ['name' => Str::headline($slug)],
            );
        }

        return DB::table('tags')
            ->whereIn('slug', $tagSlugs->all())
            ->orderBy('slug')
            ->get(['id', 'slug']);
    }
}
