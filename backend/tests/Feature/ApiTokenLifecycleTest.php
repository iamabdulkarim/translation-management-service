<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTokenLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_receive_plain_text_token_once(): void
    {
        User::factory()->create([
            'email' => 'senior@example.com',
            'password' => 'secret-password',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'senior@example.com',
            'password' => 'secret-password',
            'token_name' => 'local-dev',
            'abilities' => ['tokens:read', 'translations:read'],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.token.name', 'local-dev')
            ->assertJsonPath('data.user.email', 'senior@example.com')
            ->assertJsonStructure([
                'data' => [
                    'plain_text_token',
                    'token' => ['id', 'name', 'abilities'],
                ],
            ]);

        $this->assertDatabaseHas('api_tokens', [
            'name' => 'local-dev',
        ]);
        $this->assertStringStartsWith('tms_', $response->json('data.plain_text_token'));
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'senior@example.com',
            'password' => 'secret-password',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'senior@example.com',
            'password' => 'wrong-password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_user_can_list_and_revoke_owned_tokens(): void
    {
        $user = User::factory()->create();
        $adminToken = $user->createApiToken('admin', ['tokens:read', 'tokens:write']);
        $managedToken = $user->createApiToken('managed', ['translations:read']);

        $this->withToken($adminToken->plainTextToken)
            ->getJson('/api/v1/auth/tokens')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'admin')
            ->assertJsonPath('data.1.name', 'managed');

        $this->withToken($adminToken->plainTextToken)
            ->deleteJson("/api/v1/auth/tokens/{$managedToken->accessToken->getKey()}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'API token revoked successfully.');

        $this->assertNotNull($managedToken->accessToken->refresh()->revoked_at);
    }

    public function test_logout_revokes_the_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createApiToken('session', ['translations:read']);

        $this->withToken($token->plainTextToken)
            ->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'API token revoked successfully.');

        $this->assertNotNull($token->accessToken->refresh()->revoked_at);

        $this->withToken($token->plainTextToken)
            ->getJson('/api/v1/me')
            ->assertUnauthorized();
    }

    public function test_user_cannot_revoke_another_users_token(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $adminToken = $user->createApiToken('admin', ['tokens:write']);
        $otherToken = $otherUser->createApiToken('other', ['translations:read']);

        $this->withToken($adminToken->plainTextToken)
            ->deleteJson("/api/v1/auth/tokens/{$otherToken->accessToken->getKey()}")
            ->assertNotFound();

        $this->assertNull(ApiToken::query()->find($otherToken->accessToken->getKey())?->revoked_at);
    }
}
