<?php

namespace App\Services;

use App\Data\NewApiToken;
use App\Models\ApiToken;
use App\Models\User;
use App\Repositories\ApiTokenRepository;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class ApiTokenService
{
    public function __construct(
        private readonly ApiTokenRepository $apiTokenRepository,
    ) {}

    /**
     * @param  array<string, mixed>  $credentials
     *
     * @throws ValidationException
     */
    public function issueFromCredentials(array $credentials): NewApiToken
    {
        /** @var User|null $user */
        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check((string) $credentials['password'], $user->password)) {
            Log::warning('api_token.issue_failed', [
                'email' => $credentials['email'],
                'reason' => 'invalid_credentials',
            ]);

            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $abilities = $credentials['abilities'] ?? ['*'];
        $expiresAt = isset($credentials['expires_at'])
            ? CarbonImmutable::parse($credentials['expires_at'])
            : null;

        $token = $user->createApiToken(
            (string) ($credentials['token_name'] ?? 'api-token'),
            $abilities,
            $expiresAt,
        );

        Log::info('api_token.issued', [
            'user_id' => $user->getKey(),
            'token_id' => $token->accessToken->getKey(),
            'abilities' => $abilities,
        ]);

        return $token;
    }

    /**
     * @return LengthAwarePaginator<int, ApiToken>
     */
    public function paginateForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $this->apiTokenRepository->paginateForUser($user, $perPage);
    }

    public function revoke(ApiToken $token): void
    {
        try {
            DB::transaction(function () use ($token): void {
                $this->apiTokenRepository->revoke($token);
            }, 3);

            Log::info('api_token.revoked', [
                'token_id' => $token->getKey(),
                'user_id' => $token->user_id,
            ]);
        } catch (Throwable $exception) {
            Log::error('api_token.revoke_failed', [
                'token_id' => $token->getKey(),
                'user_id' => $token->user_id,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
            report($exception);

            throw $exception;
        }
    }
}
