<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Resources\Api\V1\ApiTokenResource;
use App\Models\ApiToken;
use App\Services\ApiTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuthenticationController extends Controller
{
    public function __construct(
        private readonly ApiTokenService $apiTokenService,
    ) {}

    public function store(LoginRequest $request): JsonResponse
    {
        try {
            $newToken = $this->apiTokenService->issueFromCredentials($request->validated());

            return $this->successResponse([
                'token_type' => 'Bearer',
                'plain_text_token' => $newToken->plainTextToken,
                'token' => new ApiTokenResource($newToken->accessToken),
                'user' => [
                    'id' => $newToken->accessToken->user?->getKey(),
                    'name' => $newToken->accessToken->user?->name,
                    'email' => $newToken->accessToken->user?->email,
                ],
            ], 'API token issued successfully.', Response::HTTP_CREATED);
        } catch (Throwable $exception) {
            return $this->errorResponse(
                $exception,
                'Unable to issue API token.',
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    public function destroy(Request $request): JsonResponse
    {
        try {
            /** @var ApiToken|null $token */
            $token = $request->attributes->get('api_token');

            if ($token instanceof ApiToken) {
                $this->apiTokenService->revoke($token);
            }

            return $this->successResponse(null, 'API token revoked successfully.');
        } catch (Throwable $exception) {
            return $this->errorResponse(
                $exception,
                'Unable to revoke API token.',
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }
}
