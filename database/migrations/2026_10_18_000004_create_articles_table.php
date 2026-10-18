<?php

use App\Enums\ArticleStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('api_key_id')->nullable()->constrained('api_keys')->nullOnDelete();
            $table->string('external_id')->nullable();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('excerpt', 500)->nullable();
            $table->string('meta_title', 70)->nullable();
            $table->string('meta_description', 200)->nullable();
            // Raw editor HTML (source of truth) and the processed, render-ready HTML.
            $table->longText('body')->nullable();
            $table->longText('body_html')->nullable();
            $table->json('toc')->nullable();
            $table->json('faq')->nullable();
            $table->string('featured_image', 2048)->nullable();
            $table->string('featured_image_alt')->nullable();
            $table->string('featured_image_caption', 500)->nullable();
            $table->string('status')->default(ArticleStatus::Draft->value);
            $table->unsignedSmallInteger('read_time_minutes')->default(1);
            $table->unsignedInteger('word_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->unique(['api_key_id', 'external_id']);
        });

        Schema::create('article_tag', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['article_id', 'tag_id']);
            $table->index('tag_id');
        });

        DB::statement("
            ALTER TABLE articles
            ADD COLUMN search_vector tsvector
            GENERATED ALWAYS AS (
                setweight(to_tsvector('english', coalesce(title, '')), 'A') ||
                setweight(to_tsvector('english', coalesce(excerpt, '')), 'B') ||
                setweight(to_tsvector('english', coalesce(regexp_replace(body_html, '<[^>]+>', ' ', 'g'), '')), 'C')
            ) STORED
        ");
        DB::statement('CREATE INDEX articles_search_vector_gin ON articles USING gin(search_vector)');
    }

    public function down(): void
    {
        Schema::dropIfExists('article_tag');
        Schema::dropIfExists('articles');
    }
};
