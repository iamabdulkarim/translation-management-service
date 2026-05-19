<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\TranslationSearchRequest;
use App\Http\Resources\Api\V1\TranslationResource;
use App\Services\TranslationQueryService;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TranslationSearchController extends Controller
{
    public function __construct(
        private readonly TranslationQueryService $translationQueryService,
    ) {}

    public function __invoke(TranslationSearchRequest $request)
    {
        try {
            return TranslationResource::collection(
                $this->translationQueryService->search($request->validated()),
            )->additional([
                'success' => true,
                'message' => 'Translations searched successfully.',
            ]);
        } catch (Throwable $exception) {
            return $this->errorResponse(
                $exception,
                'Unable to search translations.',
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }
}
