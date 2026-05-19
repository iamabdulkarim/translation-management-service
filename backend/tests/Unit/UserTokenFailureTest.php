<?php

namespace Tests\Unit;

use App\Exceptions\TokenCreationException;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class UserTokenFailureTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_token_creation_wraps_database_failure(): void
    {
        DB::shouldReceive('transaction')
            ->once()
            ->andThrow(new RuntimeException('token write failed'));

        $this->expectException(TokenCreationException::class);

        User::factory()->make()->createApiToken('failing-token');
    }
}
