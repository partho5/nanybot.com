<?php

namespace App\Console\Commands;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Services\Blog\PageCache;
use Illuminate\Console\Command;

class PublishScheduledArticles extends Command
{
    protected $signature = 'articles:publish-scheduled';

    protected $description = 'Promote scheduled articles whose publish time has arrived to published status';

    public function handle(PageCache $pageCache): int
    {
        // Mass update skips model events, so the page cache is flushed explicitly.
        $count = Article::query()
            ->where('status', ArticleStatus::Scheduled->value)
            ->where('published_at', '<=', now())
            ->update(['status' => ArticleStatus::Published->value, 'updated_at' => now()]);

        if ($count > 0) {
            $pageCache->flush();
            $this->info("Published {$count} scheduled article(s).");
        }

        return self::SUCCESS;
    }
}
