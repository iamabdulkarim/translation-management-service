<?php

namespace Tests\Unit;

use App\Models\Locale;
use App\Models\TranslationKey;
use App\Repositories\TranslationWriteRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepositoryBranchTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolve_translation_key_updates_existing_description(): void
    {
        TranslationKey::query()->create([
            'key' => 'repository.description',
            'description' => 'Old description',
        ]);

        $translationKey = app(TranslationWriteRepository::class)->resolveTranslationKey([
            'key' => 'repository.description',
            'description' => 'New description',
        ]);

        $this->assertSame('New description', $translationKey->description);
    }

    public function test_translation_exists_can_exclude_current_record(): void
    {
        $repository = app(TranslationWriteRepository::class);
        $locale = Locale::query()->create([
            'code' => 'en',
            'name' => 'English',
            'is_active' => true,
        ]);
        $translationKey = TranslationKey::query()->create([
            'key' => 'repository.exists',
        ]);
        $translation = $repository->createTranslation([
            'translation_key_id' => $translationKey->getKey(),
            'locale_id' => $locale->getKey(),
            'value' => 'Exists',
            'value_hash' => hash('sha256', 'Exists'),
        ]);

        $this->assertFalse($repository->translationExists($translationKey, $locale, $translation));
    }
}
