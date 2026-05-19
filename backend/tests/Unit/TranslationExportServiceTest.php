<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\TranslationExportService;
use App\Services\TranslationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationExportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_service_streams_only_matching_published_translations(): void
    {
        $user = User::factory()->create();
        $translationService = app(TranslationService::class);

        $translationService->create([
            'key' => 'dashboard.title',
            'locale' => 'en',
            'value' => 'Dashboard',
            'tags' => ['web'],
        ], $user);
        $translationService->create([
            'key' => 'dashboard.hidden',
            'locale' => 'en',
            'value' => 'Hidden',
            'tags' => ['web'],
            'is_published' => false,
        ], $user);

        $json = implode('', iterator_to_array(
            app(TranslationExportService::class)->streamLocale('en', ['tag' => 'web']),
        ));
        $payload = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('en', $payload['locale']);
        $this->assertSame([
            'dashboard.title' => 'Dashboard',
        ], $payload['translations']);
    }

    public function test_export_service_accepts_comma_separated_tags(): void
    {
        $user = User::factory()->create();
        $translationService = app(TranslationService::class);

        $translationService->create([
            'key' => 'export.mobile',
            'locale' => 'en',
            'value' => 'Mobile',
            'tags' => ['mobile'],
        ], $user);

        $json = implode('', iterator_to_array(
            app(TranslationExportService::class)->streamLocale('en', ['tags' => 'web,mobile']),
        ));
        $payload = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame([
            'export.mobile' => 'Mobile',
        ], $payload['translations']);
    }
}
