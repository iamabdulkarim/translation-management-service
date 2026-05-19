<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

abstract class Controller
{
    protected function successResponse(
        mixed $data = null,
        string $message = 'Request completed successfully.',
        int $status = Response::HTTP_OK,
    ): JsonResponse {
        $payload = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $payload['data'] = $data;
        }

        return response()->json($payload, $status);
    }

    protected function resourceResponse(
        JsonResource $resource,
        string $message,
        int $status = Response::HTTP_OK,
    ): JsonResponse {
        return $resource
            ->additional([
                'success' => true,
                'message' => $message,
            ])
            ->response()
            ->setStatusCode($status);
    }

    protected function errorResponse(
        Throwable $exception,
        string $message = 'Unable to process the request.',
        int $status = Response::HTTP_INTERNAL_SERVER_ERROR,
    ): JsonResponse {
        if ($exception instanceof ValidationException) {
            return response()->json([
                'success' => false,
                'message' => 'The given data was invalid.',
                'errors' => $exception->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        Log::error('api.request_failed', [
            'exception' => $exception::class,
            'message' => $exception->getMessage(),
        ]);

        report($exception);

        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }
}
