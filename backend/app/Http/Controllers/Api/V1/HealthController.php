<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        try {
            return $this->successResponse([
                'service' => 'translation-management-service',
                'status' => 'ok',
                'version' => 'v1',
            ], 'Service is healthy.');
        } catch (Throwable $exception) {
            return $this->errorResponse(
                $exception,
                'Unable to determine service health.',
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }
}
