<?php

namespace App\Observers;

use App\Models\Article;
use App\Services\Blog\ImageCleaner;
use App\Services\Blog\PageCache;
use App\Services\CloudinaryService;

class ArticleObserver
{
    public function __construct(private CloudinaryService $cloudinary, private PageCache $pageCache, private ImageCleaner $images) {}

    public function updating(Article $article): void
    {
        if ($article->isDirty('featured_image') && $article->getOriginal('featured_image')) {
            $this->cloudinary->delete($article->getOriginal('featured_image'));
        }
    }

    public function saved(Article $article): void
    {
        $this->pageCache->flush();
    }

    public function deleted(Article $article): void
    {
        if ($article->featured_image) {
            $this->cloudinary->delete($article->featured_image);
        }

        // Body images, unless another article or an avatar still uses them.
        $this->images->purgeUnused($this->images->urlsIn($article->body));

        $this->pageCache->flush();
    }
}
