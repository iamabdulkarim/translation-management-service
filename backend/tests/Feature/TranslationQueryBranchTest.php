<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationQueryBranchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_supports_q_published_filter_and_all_tag_matching(): void
    {
        $user = User::factory()->create();
        $token = $user->createApiToken('query-branches', ['translations:read', 'translations:write']);

        $this->createTranslation($token->plainTextToken, [
            'key' => 'dashboard.title',
            'locale' => 'en',
            'value' => 'Dashboard',
            'tags' => ['web', 'mobile'],
            'is_published' => true,
        ]);
        $this->createTranslation($token->plainTextToken, [
            'key' => 'dashboard.draft',
            'locale' => 'en',
            'value' => 'Draft Dashboard',
            'tags' => ['web'],
            'is_published' => false,
        ]);
        $this->createTranslation($token->plainTextToken, [
            'key' => 'settings.title',
            'locale' => 'en',
            'value' => 'Settings',
            'tags' => ['desktop'],
            'is_published' => true,
        ]);

        $this->withToken($token->plainTextToken)
            ->getJson('/api/v1/translations/search?q=dashboard&is_published=0&per_page=100')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.key', 'dashboard.draft');

        $this->withToken($token->plainTextToken)
            ->getJson('/api/v1/translations/search?tags=web,mobile&match_all_tags=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.key', 'dashboard.title');
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
