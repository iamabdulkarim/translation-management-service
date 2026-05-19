<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\TranslationExportRequest;
use App\Services\TranslationExportService;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TranslationExportController extends Controller
{
    public function __construct(
        private readonly TranslationExportService $translationExportService,
    ) {}

    public function __invoke(TranslationExportRequest $request, string $locale): Response
    {
        try {
            $locale = strtolower($locale);

            Log::info('translation.export_requested', [
                'locale' => $locale,
                'filters' => $request->validated(),
                'user_id' => $request->user()?->getKey(),
            ]);

            return response()->stream(function () use ($locale, $request): void {
                foreach ($this->translationExportService->streamLocale($locale, $request->validated()) as $chunk) {
                    echo $chunk;
                }
            }, 200, [
                'Cache-Control' => (string) config('tms.headers.export_cache_control'),
                'Content-Type' => 'application/json; charset=UTF-8',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        } catch (Throwable $exception) {
            return $this->errorResponse(
                $exception,
                'Unable to export translations.',
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }
}
