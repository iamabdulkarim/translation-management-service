<?php

namespace App\Services;

use App\Exceptions\TranslationPersistenceException;
use App\Models\Locale;
use App\Models\Translation;
use App\Models\TranslationKey;
use App\Models\User;
use App\Repositories\TranslationWriteRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class TranslationService
{
    public function __construct(
        private readonly TranslationWriteRepository $translationWriteRepository,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws TranslationPersistenceException
     * @throws ValidationException
     */
    public function create(array $data, ?User $user): Translation
    {
        try {
            $translation = DB::transaction(function () use ($data, $user): Translation {
                $locale = $this->translationWriteRepository->resolveLocale($data);
                $translationKey = $this->translationWriteRepository->resolveTranslationKey($data);

                if ($this->translationWriteRepository->translationExists($translationKey, $locale)) {
                    Log::warning('translation.create_rejected', [
                        'key' => $translationKey->key,
                        'locale' => $locale->code,
                        'reason' => 'duplicate_key_locale',
                    ]);

                    throw ValidationException::withMessages([
                        'key' => ['A translation already exists for this key and locale.'],
                    ]);
                }

                $translation = $this->translationWriteRepository->createTranslation([
                    'translation_key_id' => $translationKey->getKey(),
                    'locale_id' => $locale->getKey(),
                    'value' => $data['value'],
                    'value_hash' => hash('sha256', (string) $data['value']),
                    'is_published' => $data['is_published'] ?? true,
                    'created_by_id' => $user?->getKey(),
                    'updated_by_id' => $user?->getKey(),
                ]);

                $this->translationWriteRepository->syncTagsWhenProvided($translationKey, $data);

                return $translation->load(['locale', 'translationKey.tags']);
            }, 3);

            Log::info('translation.created', [
                'translation_id' => $translation->getKey(),
                'key' => $translation->translationKey?->key,
                'locale' => $translation->locale?->code,
                'user_id' => $user?->getKey(),
            ]);

            return $translation;
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('translation.create_failed', [
                'key' => $data['key'] ?? null,
                'locale' => $data['locale'] ?? null,
                'user_id' => $user?->getKey(),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
            report($exception);

            throw TranslationPersistenceException::becausePersistenceFailed($exception);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws TranslationPersistenceException
     * @throws ValidationException
     */
    public function update(Translation $translation, array $data, ?User $user): Translation
    {
        try {
            $updatedTranslation = DB::transaction(function () use ($translation, $data, $user): Translation {
                $translation->load(['locale', 'translationKey.tags']);

                $locale = array_key_exists('locale', $data)
                    ? $this->translationWriteRepository->resolveLocale($data)
                    : $translation->locale;

                $translationKey = array_key_exists('key', $data)
                    ? $this->translationWriteRepository->resolveTranslationKey($data)
                    : $translation->translationKey;

                if (! $locale instanceof Locale || ! $translationKey instanceof TranslationKey) {
                    throw ValidationException::withMessages([
                        'translation' => ['The translation record is incomplete.'],
                    ]);
                }

                if ($this->translationWriteRepository->translationExists($translationKey, $locale, $translation)) {
                    Log::warning('translation.update_rejected', [
                        'translation_id' => $translation->getKey(),
                        'key' => $translationKey->key,
                        'locale' => $locale->code,
                        'reason' => 'duplicate_key_locale',
                    ]);

                    throw ValidationException::withMessages([
                        'key' => ['A translation already exists for this key and locale.'],
                    ]);
                }

                if (array_key_exists('description', $data)) {
                    $translationKey->forceFill([
                        'description' => $data['description'],
                    ])->save();
                }

                $updates = [
                    'translation_key_id' => $translationKey->getKey(),
                    'locale_id' => $locale->getKey(),
                    'updated_by_id' => $user?->getKey(),
                ];

                if (array_key_exists('value', $data)) {
                    $updates['value'] = $data['value'];
                    $updates['value_hash'] = hash('sha256', (string) $data['value']);
                }

                if (array_key_exists('is_published', $data)) {
                    $updates['is_published'] = $data['is_published'];
                }

                $this->translationWriteRepository->updateTranslation($translation, $updates);
                $this->translationWriteRepository->syncTagsWhenProvided($translationKey, $data);

                return $translation->refresh()->load(['locale', 'translationKey.tags']);
            }, 3);

            Log::info('translation.updated', [
                'translation_id' => $updatedTranslation->getKey(),
                'key' => $updatedTranslation->translationKey?->key,
                'locale' => $updatedTranslation->locale?->code,
                'user_id' => $user?->getKey(),
            ]);

            return $updatedTranslation;
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('translation.update_failed', [
                'translation_id' => $translation->getKey(),
                'user_id' => $user?->getKey(),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
            report($exception);

            throw TranslationPersistenceException::becausePersistenceFailed($exception);
        }
    }

    /**
     * @throws TranslationPersistenceException
     */
    public function delete(Translation $translation): void
    {
        try {
            $context = [
                'translation_id' => $translation->getKey(),
                'translation_key_id' => $translation->translation_key_id,
                'locale_id' => $translation->locale_id,
            ];

            DB::transaction(function () use ($translation): void {
                $this->translationWriteRepository->deleteTranslation($translation);
            }, 3);

            Log::info('translation.deleted', $context);
        } catch (Throwable $exception) {
            Log::error('translation.delete_failed', [
                'translation_id' => $translation->getKey(),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
            report($exception);

            throw TranslationPersistenceException::becausePersistenceFailed($exception);
        }
    }
}
