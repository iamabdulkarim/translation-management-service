<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_endpoint_responds_in_milliseconds_on_seeded_dataset(): void
    {
        $this->artisan('translations:populate', [
            'records' => 1500,
            '--chunk' => 500,
        ])->assertSuccessful();

        $token = User::factory()
            ->create()
            ->createApiToken('performance-search', ['translations:read']);

        $startedAt = microtime(true);

        $this->withToken($token->plainTextToken)
            ->getJson('/api/v1/translations/search?locale=en&tag=web&per_page=50')
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'links',
                'meta',
            ]);

        $durationMs = (microtime(true) - $startedAt) * 1000;

        if (! $this->isCoverageRuntime()) {
            $this->assertLessThan(200, $durationMs, "Search took {$durationMs}ms.");
        }
    }

    public function test_export_endpoint_streams_seeded_locale_under_target_budget(): void
    {
        $this->artisan('translations:populate', [
            'records' => 1500,
            '--chunk' => 500,
        ])->assertSuccessful();

        $token = User::factory()
            ->create()
            ->createApiToken('performance-export', ['translations:export']);

        $startedAt = microtime(true);

        $response = $this->withToken($token->plainTextToken)
            ->get('/api/v1/translations/export/en');

        $payload = json_decode($response->streamedContent(), true, flags: JSON_THROW_ON_ERROR);
        $durationMs = (microtime(true) - $startedAt) * 1000;

        $response->assertOk();
        $this->assertSame('en', $payload['locale']);
        $this->assertGreaterThan(100, count($payload['translations']));
        if (! $this->isCoverageRuntime()) {
            $this->assertLessThan(500, $durationMs, "Export took {$durationMs}ms.");
        }
    }

    private function isCoverageRuntime(): bool
    {
        return function_exists('xdebug_info')
            && in_array('coverage', xdebug_info('mode'), true);
    }
}
