<?php

namespace App\Data;

use App\Models\ApiToken;

final readonly class NewApiToken
{
    public function __construct(
        public ApiToken $accessToken,
        public string $plainTextToken,
    ) {}
}
