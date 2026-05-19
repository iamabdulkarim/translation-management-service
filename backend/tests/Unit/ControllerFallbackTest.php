<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\V1\AuthenticatedUserController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\TranslationExportController;
use App\Http\Requests\Api\V1\TranslationExportRequest;
use App\Services\TranslationExportService;
use Illuminate\Http\JsonResponse;
use Mockery;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ControllerFallbackTest extends TestCase
{
    public function test_health_controller_returns_error_envelope_when_response_creation_fails(): void
    {
        $controller = new class extends HealthController
        {
            protected function successResponse(mixed $data = null, string $message = 'Request completed successfully.', int $status = Response::HTTP_OK): JsonResponse
            {
                throw new RuntimeException('health failed');
            }
        };

        $response = $controller();

        $this->assertSame(500, $response->getStatusCode());
        $this->assertFalse($response->getData(true)['success']);
    }

    public function test_authenticated_user_controller_returns_error_envelope_when_response_creation_fails(): void
    {
        $controller = new class extends AuthenticatedUserController
        {
            protected function successResponse(mixed $data = null, string $message = 'Request completed successfully.', int $status = Response::HTTP_OK): JsonResponse
            {
                throw new RuntimeException('user failed');
            }
        };

        $response = $controller(request());

        $this->assertSame(500, $response->getStatusCode());
        $this->assertFalse($response->getData(true)['success']);
    }

    public function test_export_controller_returns_error_envelope_when_validation_fails_inside_action(): void
    {
        $request = Mockery::mock(TranslationExportRequest::class);
        $request->shouldReceive('validated')
            ->once()
            ->andThrow(new RuntimeException('export validation failed'));

        $controller = new TranslationExportController(new TranslationExportService);
        $response = $controller($request, 'en');

        $this->assertSame(500, $response->getStatusCode());
        $this->assertFalse($response->getData(true)['success']);
    }
}
