<?php

namespace Tests\Feature;

use App\Models\Translation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PopulateTranslationsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_populate_command_creates_requested_translation_volume(): void
    {
        $this->artisan('translations:populate', [
            'records' => 120,
            '--chunk' => 100,
            '--locales' => 'en,fr',
            '--tags' => 'web,mobile',
        ])->assertSuccessful();

        $this->assertSame(120, Translation::query()->count());
        $this->assertDatabaseHas('translation_keys', [
            'key' => 'seed.translation.0',
        ]);
        $this->assertDatabaseHas('translations', [
            'value' => 'Generated en translation 0',
        ]);
    }

    public function test_populate_command_defaults_empty_options_and_stops_at_requested_count(): void
    {
        $this->artisan('translations:populate', [
            'records' => 3,
            '--chunk' => 100,
            '--locales' => '',
            '--tags' => '',
        ])->assertSuccessful();

        $this->assertSame(3, Translation::query()->count());
        $this->assertDatabaseHas('locales', [
            'code' => 'en',
        ]);
        $this->assertDatabaseHas('tags', [
            'slug' => 'web',
        ]);
    }

    public function test_populate_command_stops_inside_locale_loop_when_requested_count_is_reached(): void
    {
        $this->artisan('translations:populate', [
            'records' => 3,
            '--chunk' => 100,
            '--locales' => 'en,fr',
            '--tags' => 'web',
        ])->assertSuccessful();

        $this->assertSame(3, Translation::query()->count());
    }
}
