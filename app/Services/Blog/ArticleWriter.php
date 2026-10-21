<?php

namespace App\Services\Blog;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Tag;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The single write path for articles (admin UI and webhook). Keeping every
 * write here guarantees content processing and cache invalidation always run.
 */
class ArticleWriter
{
    public function __construct(private ContentProcessor $processor, private ImageCleaner $images) {}

    /**
     * @param  array<string, mixed>  $data  validated attributes (+ optional publish_at, tags, uploaded_images)
     */
    public function save(Article $article, array $data): Article
    {
        $tags = $data['tags'] ?? null;
        $publishAt = $data['publish_at'] ?? null;
        // Images the editor uploaded this session: removed again before saving, they are never referenced.
        $uploaded = $data['uploaded_images'] ?? [];
        unset($data['tags'], $data['publish_at'], $data['uploaded_images']);

        $bodyImages = $this->images->urlsIn($article->body);

        if (! $article->exists || (isset($data['slug']) && $data['slug'] !== $article->slug)) {
            $data['slug'] = $this->uniqueSlug($data['slug'] ?? $data['title'] ?? $article->title, $article->id);
        }

        $data = $this->processBody($article, $data);

        // Status/date rules copied from the reference controller.
        $status = isset($data['status']) ? ArticleStatus::from($data['status'] instanceof ArticleStatus ? $data['status']->value : $data['status']) : $article->status;
        if ($status === ArticleStatus::Published) {
            // Only stamp published_at when first transitioning to published
            if ($article->status !== ArticleStatus::Published || ! $article->published_at) {
                $data['published_at'] = $publishAt ? Carbon::parse($publishAt)->min(now()) : now();
            }
        } elseif ($status === ArticleStatus::Scheduled) {
            $data['published_at'] = Carbon::parse($publishAt ?? $article->published_at ?? now());
            if ($data['published_at']->lte(now())) {
                $data['status'] = ArticleStatus::Published->value;
            }
        } elseif ($status === ArticleStatus::Draft) {
            $data['published_at'] = null;
        }

        $saved = DB::transaction(function () use ($article, $data, $tags) {
            $article->fill($data)->save();

            if (is_array($tags)) {
                $article->tags()->sync(Tag::idsFromNames($tags));
                // Tag changes don't dirty the article; make sure caches refresh.
                $article->touch();
            }

            return $article;
        });

        // Anything dropped from the body (or uploaded and then removed) and used nowhere else.
        $this->images->purgeUnused([...$bodyImages, ...$uploaded]);

        return $saved;
    }

    /**
     * Fills an in-memory article exactly as save() would (same body to
     * HTML / TOC / read-time pipeline and sanitisation) but never touches the
     * database. Used by the unsaved preview; the model is not persisted.
     *
     * @param  array<string, mixed>  $data  attributes (tags / publish_at are ignored here)
     */
    public function preview(Article $article, array $data): Article
    {
        unset($data['tags'], $data['publish_at']);

        return $article->fill($this->processBody($article, $data));
    }

    /**
     * Derives body_html, toc, faq, word count, read time and a fallback
     * excerpt from `body`. No-op when the payload has no body.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function processBody(Article $article, array $data): array
    {
        if (! array_key_exists('body', $data)) {
            return $data;
        }

        $processed = $this->processor->process((string) $data['body']);
        $data['body_html'] = $processed->html;
        $data['toc'] = $processed->toc;
        $data['faq'] = $processed->faq;
        $data['word_count'] = $processed->wordCount;
        $data['read_time_minutes'] = $processed->readTimeMinutes;

        if (empty($data['excerpt']) && empty($article->excerpt) && $processed->firstParagraph) {
            $data['excerpt'] = Str::limit($processed->firstParagraph, 200, '…');
        }

        return $data;
    }

    /** Deletes an article; the observer removes its image and flushes the page cache. */
    public function delete(Article $article): void
    {
        $article->delete();
    }

    public function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'article';
        $base = Str::limit($base, 180, '');
        $slug = $base;
        $i = 2;

        while (
            in_array($slug, Article::RESERVED_SLUGS, true)
            || Article::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()
        ) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
