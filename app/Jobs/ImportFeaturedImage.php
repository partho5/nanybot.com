<?php

namespace App\Jobs;

use App\Models\Article;
use App\Services\Blog\ArticleWriter;
use App\Services\CloudinaryService;
use App\Support\PublicUrl;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Copies a webhook-supplied (non-Cloudinary) featured image into Cloudinary and
 * swaps it onto the article through ArticleWriter so caches are flushed.
 */
class ImportFeaturedImage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $articleId, public string $sourceUrl)
    {
        $this->afterCommit();
    }

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [30, 120];
    }

    public function handle(CloudinaryService $cloudinary, ArticleWriter $writer): void
    {
        $article = Article::find($this->articleId);

        // Deleted, or the image was replaced since this job was queued.
        if (! $article || $article->featured_image !== $this->sourceUrl) {
            return;
        }

        if (! PublicUrl::isSafe($this->sourceUrl, resolve: true)) {
            Log::warning('Featured image import skipped: URL is not a public http(s) address.', [
                'article' => $this->articleId, 'url' => $this->sourceUrl,
            ]);

            return;
        }

        if (! config('services.cloudinary.cloud_name') || ! config('services.cloudinary.api_key') || ! config('services.cloudinary.api_secret')) {
            Log::warning('Featured image import skipped: Cloudinary is not configured.', ['article' => $this->articleId]);

            return;
        }

        $uploaded = $cloudinary->uploadFromUrl($this->sourceUrl);

        if ($uploaded === null) {
            // Throw so the queue retries; the remote URL keeps working meanwhile.
            throw new \RuntimeException("Cloudinary upload failed for article {$this->articleId}.");
        }

        // Re-read in case the article changed during the upload.
        $article = Article::find($this->articleId);
        if (! $article || $article->featured_image !== $this->sourceUrl) {
            $cloudinary->delete($uploaded[0]);

            return;
        }

        $writer->save($article, ['featured_image' => $uploaded[0]]);
    }
}
