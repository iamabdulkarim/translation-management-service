<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuthenticatedUserController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        try {
            /** @var ApiToken|null $token */
            $token = $request->attributes->get('api_token');
            $user = $request->user();

            return $this->successResponse([
                'id' => $user?->getKey(),
                'name' => $user?->name,
                'email' => $user?->email,
                'token' => [
                    'name' => $token?->name,
                    'abilities' => $token?->abilities ?? [],
                ],
            ], 'Authenticated user retrieved successfully.');
        } catch (Throwable $exception) {
            return $this->errorResponse(
                $exception,
                'Unable to retrieve authenticated user.',
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }
}
