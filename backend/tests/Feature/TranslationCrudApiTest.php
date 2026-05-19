<?php

namespace Tests\Feature;

use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationCrudApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_translation_can_be_created_viewed_updated_and_deleted(): void
    {
        $user = User::factory()->create();
        $token = $user->createApiToken('writer', ['translations:read', 'translations:write']);

        $createResponse = $this->withToken($token->plainTextToken)
            ->postJson('/api/v1/translations', [
                'key' => 'home.hero.title',
                'description' => 'Hero title',
                'locale' => 'en',
                'locale_name' => 'English',
                'value' => 'Welcome back',
                'tags' => ['web', 'mobile'],
            ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.key', 'home.hero.title')
            ->assertJsonPath('data.locale.code', 'en')
            ->assertJsonPath('data.value', 'Welcome back')
            ->assertJsonPath('data.tags.0', 'web')
            ->assertJsonPath('data.tags.1', 'mobile');

        $translationId = $createResponse->json('data.id');

        $this->withToken($token->plainTextToken)
            ->getJson("/api/v1/translations/{$translationId}")
            ->assertOk()
            ->assertJsonPath('data.description', 'Hero title');

        $this->withToken($token->plainTextToken)
            ->patchJson("/api/v1/translations/{$translationId}", [
                'value' => 'Welcome again',
                'is_published' => false,
                'tags' => ['desktop'],
            ])
            ->assertOk()
            ->assertJsonPath('data.value', 'Welcome again')
            ->assertJsonPath('data.is_published', false)
            ->assertJsonPath('data.tags.0', 'desktop');

        $this->withToken($token->plainTextToken)
            ->deleteJson("/api/v1/translations/{$translationId}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Translation deleted successfully.');

        $this->assertDatabaseMissing('translations', [
            'id' => $translationId,
        ]);
    }

    public function test_duplicate_translation_for_same_key_and_locale_is_rejected(): void
    {
        $user = User::factory()->create();
        $token = $user->createApiToken('writer', ['translations:write']);

        $payload = [
            'key' => 'common.save',
            'locale' => 'en',
            'value' => 'Save',
            'tags' => ['web'],
        ];

        $this->withToken($token->plainTextToken)
            ->postJson('/api/v1/translations', $payload)
            ->assertCreated();

        $this->withToken($token->plainTextToken)
            ->postJson('/api/v1/translations', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('key');
    }

    public function test_translation_write_requires_write_ability(): void
    {
        $user = User::factory()->create();
        $token = $user->createApiToken('reader', ['translations:read']);

        $this->withToken($token->plainTextToken)
            ->postJson('/api/v1/translations', [
                'key' => 'common.cancel',
                'locale' => 'en',
                'value' => 'Cancel',
            ])
            ->assertForbidden();

        $this->assertSame(0, Translation::query()->count());
    }
}
