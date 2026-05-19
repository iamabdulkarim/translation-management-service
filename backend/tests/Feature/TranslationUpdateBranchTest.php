<?php

namespace Tests\Feature;

use App\Models\Translation;
use App\Models\User;
use App\Services\TranslationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TranslationUpdateBranchTest extends TestCase
{
    use RefreshDatabase;

    public function test_translation_update_can_move_key_locale_description_value_and_publication_state(): void
    {
        $user = User::factory()->create();
        $token = $user->createApiToken('writer', ['translations:read', 'translations:write']);
        $translation = $this->createTranslation($token->plainTextToken, [
            'key' => 'branch.original',
            'locale' => 'en',
            'value' => 'Original',
            'tags' => ['web'],
        ]);

        $this->withToken($token->plainTextToken)
            ->patchJson("/api/v1/translations/{$translation->getKey()}", [
                'key' => 'branch.updated',
                'description' => 'Updated branch',
                'locale' => 'fr',
                'locale_name' => 'French',
                'value' => 'Mis a jour',
                'is_published' => false,
                'tags' => ['mobile'],
            ])
            ->assertOk()
            ->assertJsonPath('data.key', 'branch.updated')
            ->assertJsonPath('data.description', 'Updated branch')
            ->assertJsonPath('data.locale.code', 'fr')
            ->assertJsonPath('data.is_published', false)
            ->assertJsonPath('data.tags.0', 'mobile');
    }

    public function test_translation_update_rejects_duplicate_target_key_and_locale(): void
    {
        $user = User::factory()->create();
        $token = $user->createApiToken('writer', ['translations:write']);
        $this->createTranslation($token->plainTextToken, [
            'key' => 'duplicate.target',
            'locale' => 'en',
            'value' => 'Target',
        ]);
        $translation = $this->createTranslation($token->plainTextToken, [
            'key' => 'duplicate.source',
            'locale' => 'en',
            'value' => 'Source',
        ]);

        $this->withToken($token->plainTextToken)
            ->patchJson("/api/v1/translations/{$translation->getKey()}", [
                'key' => 'duplicate.target',
                'locale' => 'en',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('key');
    }

    public function test_translation_service_rejects_incomplete_translation_record(): void
    {
        $this->expectException(ValidationException::class);

        app(TranslationService::class)->update(new Translation, [
            'value' => 'Incomplete',
        ], User::factory()->create());
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function createTranslation(string $token, array $payload): Translation
    {
        $response = $this->withToken($token)
            ->postJson('/api/v1/translations', $payload)
            ->assertCreated();

        return Translation::query()->findOrFail($response->json('data.id'));
    }
}
