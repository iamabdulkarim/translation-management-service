<?php

namespace Tests\Unit;

use App\Models\ApiToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTokenModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_token_ability_supports_wildcard_and_specific_abilities(): void
    {
        $wildcardToken = new ApiToken([
            'abilities' => ['*'],
        ]);
        $readToken = new ApiToken([
            'abilities' => ['translations:read'],
        ]);

        $this->assertTrue($wildcardToken->can('tokens:write'));
        $this->assertTrue($readToken->can('translations:read'));
        $this->assertFalse($readToken->can('translations:write'));
    }

    public function test_token_usability_respects_revocation_and_expiration(): void
    {
        $usableToken = new ApiToken([
            'expires_at' => now()->addMinute(),
        ]);
        $expiredToken = new ApiToken([
            'expires_at' => now()->subMinute(),
        ]);
        $revokedToken = new ApiToken([
            'revoked_at' => now(),
        ]);

        $this->assertTrue($usableToken->isUsable());
        $this->assertFalse($expiredToken->isUsable());
        $this->assertFalse($revokedToken->isUsable());
    }
}
