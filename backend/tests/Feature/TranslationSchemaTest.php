<?php

namespace Tests\Feature;

use App\Models\Locale;
use App\Models\Tag;
use App\Models\Translation;
use App\Models\TranslationKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_translation_schema_supports_locales_tags_and_values(): void
    {
        $user = User::factory()->create();
        $locale = Locale::query()->create([
            'code' => 'en',
            'name' => 'English',
            'is_active' => true,
        ]);
        $tag = Tag::query()->create([
            'slug' => 'web',
            'name' => 'Web',
        ]);
        $translationKey = TranslationKey::query()->create([
            'key' => 'home.hero.title',
            'description' => 'Landing hero title',
        ]);

        $translationKey->tags()->attach($tag);

        $translation = Translation::query()->create([
            'translation_key_id' => $translationKey->getKey(),
            'locale_id' => $locale->getKey(),
            'value' => 'Welcome back',
            'value_hash' => hash('sha256', 'Welcome back'),
            'created_by_id' => $user->getKey(),
            'updated_by_id' => $user->getKey(),
        ]);

        $this->assertSame('home.hero.title', $translation->translationKey->key);
        $this->assertSame('en', $translation->locale->code);
        $this->assertSame('web', $translationKey->tags()->first()?->slug);
        $this->assertTrue($translation->refresh()->is_published);
    }
}
