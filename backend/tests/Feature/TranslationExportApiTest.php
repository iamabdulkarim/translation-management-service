<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationExportApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_locale_export_streams_published_translations_as_key_value_json(): void
    {
        $user = User::factory()->create();
        $token = $user->createApiToken('exporter', ['translations:write', 'translations:export']);

        $this->createTranslation($token->plainTextToken, [
            'key' => 'common.save',
            'locale' => 'en',
            'value' => 'Save',
            'tags' => ['web'],
        ]);
        $this->createTranslation($token->plainTextToken, [
            'key' => 'common.cancel',
            'locale' => 'en',
            'value' => 'Cancel',
            'tags' => ['mobile'],
        ]);
        $this->createTranslation($token->plainTextToken, [
            'key' => 'common.hidden',
            'locale' => 'en',
            'value' => 'Hidden',
            'tags' => ['web'],
            'is_published' => false,
        ]);

        $response = $this->withToken($token->plainTextToken)
            ->get('/api/v1/translations/export/en?tag=web');

        $response
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json; charset=UTF-8');

        $payload = json_decode($response->streamedContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('en', $payload['locale']);
        $this->assertSame([
            'common.save' => 'Save',
        ], $payload['translations']);
    }

    public function test_export_requires_export_ability(): void
    {
        $user = User::factory()->create();
        $token = $user->createApiToken('reader', ['translations:read']);

        $this->withToken($token->plainTextToken)
            ->getJson('/api/v1/translations/export/en')
            ->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function createTranslation(string $token, array $payload): void
    {
        $this->withToken($token)
            ->postJson('/api/v1/translations', $payload)
            ->assertCreated();
    }
}
