<?php

namespace Tests\Feature;

use App\Models\Locale;
use App\Models\Tag;
use App\Models\Translation;
use App\Models\TranslationKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_translation_domain_relationships_are_wired(): void
    {
        $creator = User::factory()->create();
        $updater = User::factory()->create();
        $locale = Locale::query()->create([
            'code' => 'en',
            'name' => 'English',
            'is_active' => true,
        ]);
        $translationKey = TranslationKey::query()->create([
            'key' => 'profile.title',
        ]);
        $tag = Tag::query()->create([
            'slug' => 'web',
            'name' => 'Web',
        ]);
        $translationKey->tags()->attach($tag);

        $translation = Translation::query()->create([
            'translation_key_id' => $translationKey->getKey(),
            'locale_id' => $locale->getKey(),
            'value' => 'Profile',
            'value_hash' => hash('sha256', 'Profile'),
            'created_by_id' => $creator->getKey(),
            'updated_by_id' => $updater->getKey(),
        ]);

        $this->assertTrue($locale->translations->contains($translation));
        $this->assertTrue($translationKey->translations->contains($translation));
        $this->assertTrue($tag->translationKeys->contains($translationKey));
        $this->assertSame($creator->getKey(), $translation->creator->getKey());
        $this->assertSame($updater->getKey(), $translation->updater->getKey());
    }

    public function test_user_can_create_expiring_api_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createApiToken('temporary', ['translations:read'], now()->addHour());

        $this->assertSame('temporary', $token->accessToken->name);
        $this->assertTrue($user->apiTokens->contains($token->accessToken));
        $this->assertNotNull($token->accessToken->expires_at);
        $this->assertStringStartsWith('tms_', $token->plainTextToken);
    }
}
