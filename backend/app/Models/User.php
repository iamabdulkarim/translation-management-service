<?php

namespace App\Models;

use App\Data\NewApiToken;
use App\Exceptions\TokenCreationException;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @return HasMany<ApiToken, $this>
     */
    public function apiTokens(): HasMany
    {
        return $this->hasMany(ApiToken::class);
    }

    /**
     * @param  array<int, string>  $abilities
     *
     * @throws TokenCreationException
     */
    public function createApiToken(
        string $name,
        array $abilities = ['*'],
        ?DateTimeInterface $expiresAt = null,
    ): NewApiToken {
        try {
            return DB::transaction(function () use ($name, $abilities, $expiresAt): NewApiToken {
                $plainTextToken = 'tms_'.Str::random(64);

                /** @var ApiToken $token */
                $token = $this->apiTokens()->create([
                    'name' => $name,
                    'token_hash' => hash('sha256', $plainTextToken),
                    'abilities' => $abilities,
                    'expires_at' => $expiresAt,
                ]);

                return new NewApiToken($token, $plainTextToken);
            }, 3);
        } catch (Throwable $exception) {
            report($exception);

            throw TokenCreationException::becauseCreationFailed($exception);
        }
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
