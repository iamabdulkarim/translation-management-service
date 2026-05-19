<?php

namespace Tests\Unit;

use App\Exceptions\TranslationPersistenceException;
use App\Models\Locale;
use App\Models\Translation;
use App\Models\TranslationKey;
use App\Models\User;
use App\Repositories\ApiTokenRepository;
use App\Repositories\TranslationWriteRepository;
use App\Services\ApiTokenService;
use App\Services\TranslationExportService;
use App\Services\TranslationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class ServiceFailureTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_token_revoke_logs_and_rethrows_repository_failure(): void
    {
        $token = User::factory()->create()->createApiToken('failing-token')->accessToken;
        $repository = Mockery::mock(ApiTokenRepository::class, function (MockInterface $mock): void {
            $mock->shouldReceive('revoke')
                ->once()
                ->andThrow(new RuntimeException('repository failed'));
        });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('repository failed');

        (new ApiTokenService($repository))->revoke($token);
    }

    public function test_translation_create_wraps_repository_failure(): void
    {
        $user = User::factory()->create();
        $locale = new Locale(['code' => 'en', 'name' => 'English']);
        $locale->id = 1;
        $translationKey = new TranslationKey(['key' => 'failure.create']);
        $translationKey->id = 1;

        $repository = Mockery::mock(TranslationWriteRepository::class, function (MockInterface $mock) use ($locale, $translationKey): void {
            $mock->shouldReceive('resolveLocale')->once()->andReturn($locale);
            $mock->shouldReceive('resolveTranslationKey')->once()->andReturn($translationKey);
            $mock->shouldReceive('translationExists')->once()->andReturnFalse();
            $mock->shouldReceive('createTranslation')->once()->andThrow(new RuntimeException('create failed'));
        });

        $this->expectException(TranslationPersistenceException::class);

        (new TranslationService($repository))->create([
            'key' => 'failure.create',
            'locale' => 'en',
            'value' => 'Failure',
        ], $user);
    }

    public function test_translation_update_wraps_repository_failure(): void
    {
        $translation = $this->translation();
        $repository = Mockery::mock(TranslationWriteRepository::class, function (MockInterface $mock): void {
            $mock->shouldReceive('translationExists')->once()->andReturnFalse();
            $mock->shouldReceive('updateTranslation')->once()->andThrow(new RuntimeException('update failed'));
        });

        $this->expectException(TranslationPersistenceException::class);

        (new TranslationService($repository))->update($translation, [
            'value' => 'Updated failure',
        ], User::factory()->create());
    }

    public function test_translation_delete_wraps_repository_failure(): void
    {
        $translation = $this->translation();
        $repository = Mockery::mock(TranslationWriteRepository::class, function (MockInterface $mock): void {
            $mock->shouldReceive('deleteTranslation')->once()->andThrow(new RuntimeException('delete failed'));
        });

        $this->expectException(TranslationPersistenceException::class);

        (new TranslationService($repository))->delete($translation);
    }

    public function test_export_service_logs_and_rethrows_query_failure(): void
    {
        DB::shouldReceive('table')
            ->once()
            ->with('translations')
            ->andThrow(new RuntimeException('export failed'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('export failed');

        iterator_to_array((new TranslationExportService)->streamLocale('en'));
    }

    private function translation(): Translation
    {
        $locale = Locale::query()->create([
            'code' => 'en',
            'name' => 'English',
            'is_active' => true,
        ]);
        $translationKey = TranslationKey::query()->create([
            'key' => 'failure.translation',
        ]);

        return Translation::query()->create([
            'translation_key_id' => $translationKey->getKey(),
            'locale_id' => $locale->getKey(),
            'value' => 'Failure',
            'value_hash' => hash('sha256', 'Failure'),
        ])->load(['locale', 'translationKey.tags']);
    }
}
