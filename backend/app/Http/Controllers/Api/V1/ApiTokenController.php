<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\TokenIndexRequest;
use App\Http\Resources\Api\V1\ApiTokenResource;
use App\Models\ApiToken;
use App\Models\User;
use App\Services\ApiTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ApiTokenController extends Controller
{
    public function __construct(
        private readonly ApiTokenService $apiTokenService,
    ) {}

    public function index(TokenIndexRequest $request)
    {
        try {
            /** @var User $user */
            $user = $request->user();

            return ApiTokenResource::collection(
                $this->apiTokenService->paginateForUser(
                    $user,
                    (int) ($request->validated('per_page') ?? 15),
                ),
            )->additional([
                'success' => true,
                'message' => 'API tokens retrieved successfully.',
            ]);
        } catch (Throwable $exception) {
            return $this->errorResponse(
                $exception,
                'Unable to retrieve API tokens.',
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    public function destroy(Request $request, ApiToken $apiToken): JsonResponse
    {
        try {
            if ($apiToken->user_id !== $request->user()?->getKey()) {
                return response()->json([
                    'success' => false,
                    'message' => 'API token was not found.',
                ], Response::HTTP_NOT_FOUND);
            }

            $this->apiTokenService->revoke($apiToken);

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
