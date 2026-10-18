<?php

namespace App\Observers;

use App\Models\User;
use App\Services\Blog\PageCache;
use Illuminate\Database\Eloquent\Model;

/** Categories, tags and author profiles appear on cached pages. */
class FlushPageCacheObserver
{
    private const USER_PUBLIC_FIELDS = ['name', 'username', 'avatar', 'headline', 'bio', 'social_links'];

    public function __construct(private PageCache $pageCache) {}

    public function saved(Model $model): void
    {
        // Logins touch remember_token etc.; only public profile changes matter.
        if ($model instanceof User && ! $model->wasChanged(self::USER_PUBLIC_FIELDS) && ! $model->wasRecentlyCreated) {
            return;
        }

        $this->pageCache->flush();
    }

    public function deleted(Model $model): void
    {
        $this->pageCache->flush();
    }
}
