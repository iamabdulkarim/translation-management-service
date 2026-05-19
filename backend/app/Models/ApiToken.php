<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'name',
    'token_hash',
    'abilities',
    'last_used_at',
    'expires_at',
    'revoked_at',
])]
#[Hidden(['token_hash'])]
class ApiToken extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'abilities' => 'array',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isUsable(): bool
    {
        return $this->revoked_at === null
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function can(string $ability): bool
    {
        $abilities = $this->abilities ?? [];

        return in_array('*', $abilities, true) || in_array($ability, $abilities, true);
    }

    public function markAsUsed(): void
    {
        $now = now();

        if ($this->last_used_at !== null && $this->last_used_at->greaterThan($now->copy()->subMinute())) {
            return;
        }

        $this->forceFill([
            'last_used_at' => $now,
        ])->save();
    }

    public function revoke(): void
    {
        if ($this->revoked_at !== null) {
            return;
        }

        $this->forceFill([
            'revoked_at' => now(),
        ])->save();
    }
}
