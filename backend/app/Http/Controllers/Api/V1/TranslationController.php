<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreTranslationRequest;
use App\Http\Requests\Api\V1\TranslationSearchRequest;
use App\Http\Requests\Api\V1\UpdateTranslationRequest;
use App\Http\Resources\Api\V1\TranslationResource;
use App\Models\Translation;
use App\Models\User;
use App\Services\TranslationQueryService;
use App\Services\TranslationService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TranslationController extends Controller
{
    public function __construct(
        private readonly TranslationService $translationService,
        private readonly TranslationQueryService $translationQueryService,
    ) {}

    public function index(TranslationSearchRequest $request)
    {
        try {
            return TranslationResource::collection(
                $this->translationQueryService->search($request->validated()),
            )->additional([
                'success' => true,
                'message' => 'Translations retrieved successfully.',
            ]);
        } catch (Throwable $exception) {
            return $this->errorResponse(
                $exception,
                'Unable to retrieve translations.',
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    public function store(StoreTranslationRequest $request): JsonResponse
    {
        try {
            /** @var User|null $user */
            $user = $request->user();

            $translation = $this->translationService->create($request->validated(), $user);

            return $this->resourceResponse(
                new TranslationResource($translation),
                'Translation created successfully.',
                Response::HTTP_CREATED,
            );
        } catch (Throwable $exception) {
            return $this->errorResponse(
                $exception,
                'Unable to create translation.',
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    public function show(Translation $translation): JsonResponse
    {
        try {
            return $this->resourceResponse(
                new TranslationResource($translation->load(['locale', 'translationKey.tags'])),
                'Translation retrieved successfully.',
            );
        } catch (Throwable $exception) {
            return $this->errorResponse(
                $exception,
                'Unable to retrieve translation.',
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    public function update(UpdateTranslationRequest $request, Translation $translation): JsonResponse
    {
        try {
            /** @var User|null $user */
            $user = $request->user();

            return $this->resourceResponse(
                new TranslationResource(
                    $this->translationService->update($translation, $request->validated(), $user),
                ),
                'Translation updated successfully.',
            );
        } catch (Throwable $exception) {
            return $this->errorResponse(
                $exception,
                'Unable to update translation.',
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    public function destroy(Translation $translation): JsonResponse
    {
        try {
            $this->translationService->delete($translation);

            return $this->successResponse(null, 'Translation deleted successfully.');
        } catch (Throwable $exception) {
            return $this->errorResponse(
                $exception,
                'Unable to delete translation.',
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }
}
