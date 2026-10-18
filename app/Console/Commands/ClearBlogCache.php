<?php

namespace App\Console\Commands;

use App\Services\Blog\PageCache;
use Illuminate\Console\Command;

class ClearBlogCache extends Command
{
    protected $signature = 'blog:cache-clear';

    protected $description = 'Delete every statically cached public page';

    public function handle(PageCache $pageCache): int
    {
        $pageCache->flush();
        $this->info('Page cache cleared.');

        return self::SUCCESS;
    }
}
