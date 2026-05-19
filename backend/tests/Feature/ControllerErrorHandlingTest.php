<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Translation;
use App\Models\User;
use App\Services\ApiTokenService;
use App\Services\TranslationQueryService;
use App\Services\TranslationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class ControllerErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    public function test_token_index_returns_error_response_when_service_fails(): void
    {
        $token = User::factory()->create()->createApiToken('admin', ['tokens:read']);

        $this->mock(ApiTokenService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('paginateForUser')
                ->once()
                ->andThrow(new RuntimeException('token list failed'));
        });

        $this->withToken($token->plainTextToken)
            ->getJson('/api/v1/auth/tokens')
            ->assertInternalServerError()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unable to retrieve API tokens.');
    }

    public function test_token_revoke_returns_error_response_when_service_fails(): void
    {
        $user = User::factory()->create();
        $token = $user->createApiToken('admin', ['tokens:write']);

        $this->mock(ApiTokenService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('revoke')
                ->once()
                ->with(Mockery::type(ApiToken::class))
                ->andThrow(new RuntimeException('revoke failed'));
        });

        $this->withToken($token->plainTextToken)
            ->deleteJson("/api/v1/auth/tokens/{$token->accessToken->getKey()}")
            ->assertInternalServerError()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unable to revoke API token.');
    }

    public function test_login_returns_error_response_when_service_fails(): void
    {
        $this->mock(ApiTokenService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('issueFromCredentials')
                ->once()
                ->andThrow(new RuntimeException('issue failed'));
        });

        $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ])
            ->assertInternalServerError()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unable to issue API token.');
    }

    public function test_logout_returns_error_response_when_service_fails(): void
    {
        $token = User::factory()->create()->createApiToken('session', ['translations:read']);

        $this->mock(ApiTokenService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('revoke')
                ->once()
                ->andThrow(new RuntimeException('logout revoke failed'));
        });

        $this->withToken($token->plainTextToken)
            ->postJson('/api/v1/auth/logout')
            ->assertInternalServerError()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unable to revoke API token.');
    }

    public function test_translation_index_returns_error_response_when_query_fails(): void
    {
        $token = User::factory()->create()->createApiToken('reader', ['translations:read']);

        $this->mock(TranslationQueryService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('search')
                ->once()
                ->andThrow(new RuntimeException('query failed'));
        });

        $this->withToken($token->plainTextToken)
            ->getJson('/api/v1/translations')
            ->assertInternalServerError()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unable to retrieve translations.');
    }

    public function test_translation_search_returns_error_response_when_query_fails(): void
    {
        $token = User::factory()->create()->createApiToken('reader', ['translations:read']);

        $this->mock(TranslationQueryService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('search')
                ->once()
                ->andThrow(new RuntimeException('search failed'));
        });

        $this->withToken($token->plainTextToken)
            ->getJson('/api/v1/translations/search')
            ->assertInternalServerError()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unable to search translations.');
    }

    public function test_translation_store_returns_error_response_when_service_fails(): void
    {
        $token = User::factory()->create()->createApiToken('writer', ['translations:write']);

        $this->mock(TranslationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('create')
                ->once()
                ->andThrow(new RuntimeException('create failed'));
        });

        $this->withToken($token->plainTextToken)
            ->postJson('/api/v1/translations', [
                'key' => 'error.create',
                'locale' => 'en',
                'value' => 'Create',
            ])
            ->assertInternalServerError()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unable to create translation.');
    }

    public function test_translation_update_and_delete_return_error_responses_when_service_fails(): void
    {
        $user = User::factory()->create();
        $token = $user->createApiToken('writer', ['translations:write']);
        $translation = $this->createTranslation($token->plainTextToken);

        $this->mock(TranslationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('update')
                ->once()
                ->andThrow(new RuntimeException('update failed'));
            $mock->shouldReceive('delete')
                ->once()
                ->andThrow(new RuntimeException('delete failed'));
        });

        $this->withToken($token->plainTextToken)
            ->patchJson("/api/v1/translations/{$translation->getKey()}", [
                'value' => 'Updated',
            ])
            ->assertInternalServerError()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unable to update translation.');

        $this->withToken($token->plainTextToken)
            ->deleteJson("/api/v1/translations/{$translation->getKey()}")
            ->assertInternalServerError()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unable to delete translation.');
    }

    private function createTranslation(string $plainTextToken): Translation
    {
        $response = $this->withToken($plainTextToken)
            ->postJson('/api/v1/translations', [
                'key' => 'error.update',
                'locale' => 'en',
                'value' => 'Original',
            ]);

        return Translation::query()->findOrFail($response->json('data.id'));
    }
}
