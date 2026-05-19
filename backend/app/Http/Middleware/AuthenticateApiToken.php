<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$abilities): Response
    {
        $plainTextToken = $request->bearerToken();

        if (! is_string($plainTextToken) || trim($plainTextToken) === '') {
            return $this->unauthorized();
        }

        $token = ApiToken::query()
            ->with('user')
            ->where('token_hash', hash('sha256', $plainTextToken))
            ->first();

        if (! $token instanceof ApiToken || ! $token->isUsable() || $token->user === null) {
            return $this->unauthorized();
        }

        foreach ($abilities as $ability) {
            if (! $token->can($ability)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This token does not have the required ability.',
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $token->markAsUsed();
        $request->setUserResolver(static fn (?string $guard = null) => $token->user);
        $request->attributes->set('api_token', $token);

        return $next($request);
    }

    private function unauthorized(): Response
    {
        return response()->json([
            'success' => false,
            'message' => 'Unauthenticated.',
        ], Response::HTTP_UNAUTHORIZED);
    }
}
