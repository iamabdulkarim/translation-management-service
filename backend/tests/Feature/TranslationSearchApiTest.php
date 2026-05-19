<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationSearchApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_translations_can_be_searched_by_key_content_locale_and_tag(): void
    {
        $user = User::factory()->create();
        $token = $user->createApiToken('writer', ['translations:read', 'translations:write']);

        $this->createTranslation($token->plainTextToken, [
            'key' => 'home.hero.title',
            'locale' => 'en',
            'value' => 'Welcome home',
            'tags' => ['web', 'desktop'],
        ]);
        $this->createTranslation($token->plainTextToken, [
            'key' => 'home.hero.subtitle',
            'locale' => 'fr',
            'value' => 'Bienvenue',
            'tags' => ['web'],
        ]);
        $this->createTranslation($token->plainTextToken, [
            'key' => 'settings.mobile.title',
            'locale' => 'en',
            'value' => 'Phone settings',
            'tags' => ['mobile'],
        ]);

        $this->withToken($token->plainTextToken)
            ->getJson('/api/v1/translations/search?locale=en&tag=mobile')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.key', 'settings.mobile.title');

        $this->withToken($token->plainTextToken)
            ->getJson('/api/v1/translations/search?key=hero&content=Welcome')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.key', 'home.hero.title');

        $this->withToken($token->plainTextToken)
            ->getJson('/api/v1/translations?tag=web&per_page=10')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_search_requires_read_ability(): void
    {
        $user = User::factory()->create();
        $token = $user->createApiToken('writer-only', ['translations:write']);

        $this->withToken($token->plainTextToken)
            ->getJson('/api/v1/translations/search?q=anything')
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
