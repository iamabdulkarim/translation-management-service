<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('locales', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 12)->unique();
            $table->string('name', 100);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('translation_keys', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 191)->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('tags', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('name', 100);
            $table->timestamps();
        });

        Schema::create('translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('translation_key_id')->constrained()->cascadeOnDelete();
            $table->foreignId('locale_id')->constrained()->cascadeOnDelete();
            $table->longText('value');
            $table->char('value_hash', 64)->index();
            $table->boolean('is_published')->default(true)->index();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['translation_key_id', 'locale_id']);
            $table->index(['locale_id', 'updated_at']);
            $table->index(['translation_key_id', 'updated_at']);
        });

        Schema::create('translation_key_tag', function (Blueprint $table): void {
            $table->foreignId('translation_key_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['translation_key_id', 'tag_id']);
            $table->index(['tag_id', 'translation_key_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('translation_key_tag');
        Schema::dropIfExists('translations');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('translation_keys');
        Schema::dropIfExists('locales');
    }
};
