<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_returns_service_status(): void
    {
        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertJsonPath('data.service', 'translation-management-service')
            ->assertJsonPath('data.status', 'ok')
            ->assertJsonPath('data.version', 'v1');
    }

    public function test_protected_endpoint_requires_a_bearer_token(): void
    {
        $this->getJson('/api/v1/me')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_protected_endpoint_accepts_valid_first_party_token(): void
    {
        $user = User::factory()->create([
            'email' => 'engineer@example.com',
        ]);

        $token = $user->createApiToken('integration-test', ['translations:read']);

        $this->withToken($token->plainTextToken)
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'engineer@example.com')
            ->assertJsonPath('data.token.name', 'integration-test')
            ->assertJsonPath('data.token.abilities.0', 'translations:read');

        $this->assertNotNull($token->accessToken->refresh()->last_used_at);
    }
}
