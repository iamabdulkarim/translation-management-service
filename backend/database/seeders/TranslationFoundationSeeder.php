<?php

namespace Database\Seeders;

use App\Models\Locale;
use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Throwable;

class TranslationFoundationSeeder extends Seeder
{
    /**
     * Seed default locales and context tags.
     */
    public function run(): void
    {
        try {
            DB::transaction(function (): void {
                collect([
                    ['code' => 'en', 'name' => 'English'],
                    ['code' => 'fr', 'name' => 'French'],
                    ['code' => 'es', 'name' => 'Spanish'],
                ])->each(fn (array $locale): Locale => Locale::query()->updateOrCreate(
                    ['code' => $locale['code']],
                    ['name' => $locale['name'], 'is_active' => true],
                ));

                collect([
                    ['slug' => 'mobile', 'name' => 'Mobile'],
                    ['slug' => 'desktop', 'name' => 'Desktop'],
                    ['slug' => 'web', 'name' => 'Web'],
                ])->each(fn (array $tag): Tag => Tag::query()->updateOrCreate(
                    ['slug' => $tag['slug']],
                    ['name' => $tag['name']],
                ));
            }, 3);
        } catch (Throwable $exception) {
            report($exception);

            throw $exception;
        }
    }
}
