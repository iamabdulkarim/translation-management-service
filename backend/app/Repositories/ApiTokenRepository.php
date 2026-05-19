<?php

namespace App\Repositories;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ApiTokenRepository
{
    /**
     * @return LengthAwarePaginator<int, ApiToken>
     */
    public function paginateForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $user->apiTokens()
            ->latest()
            ->paginate(min(max($perPage, 1), 100))
            ->withQueryString();
    }

    public function revoke(ApiToken $token): void
    {
        $token->revoke();
    }
}
