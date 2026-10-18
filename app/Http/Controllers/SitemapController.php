<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $articles = Article::query()
            ->published()
            ->select(['id', 'author_id', 'category_id', 'title', 'slug', 'featured_image', 'featured_image_alt', 'featured_image_caption', 'published_at', 'updated_at'])
            ->orderByDesc('published_at')
            ->get();

        $latest = $articles->max('updated_at');
        $urls = [];

        $urls[] = $this->entry(url('/'), $latest, 'weekly', '1.0');
        $urls[] = $this->entry(route('blog.index'), $latest, 'daily', '0.9');

        foreach ($articles as $article) {
            $image = $article->featuredImageUrl(1200);

            $urls[] = array_merge($this->entry($article->url(), $article->updated_at, 'weekly', '0.8'), [
                'image' => $image ? [
                    'loc' => $image,
                    'title' => $article->featured_image_alt ?: $article->title,
                    'caption' => $article->featured_image_caption,
                ] : null,
            ]);
        }

        // Taxonomy and author archives: only those with at least one published article.
        $lastmodBy = fn (string $column) => $articles->groupBy($column)->map(fn ($group) => $group->max('updated_at'));

        $categoryDates = $lastmodBy('category_id');
        Category::query()->whereIn('id', $categoryDates->keys()->filter())->get(['id', 'slug'])
            ->each(function (Category $c) use (&$urls, $categoryDates) {
                $urls[] = $this->entry($c->url(), $categoryDates[$c->id], 'weekly', '0.6');
            });

        $authorDates = $lastmodBy('author_id');
        User::query()->whereIn('id', $authorDates->keys())->get(['id', 'username'])
            ->each(function (User $u) use (&$urls, $authorDates) {
                $urls[] = $this->entry($u->authorUrl(), $authorDates[$u->id], 'weekly', '0.5');
            });

        Tag::query()
            ->whereHas('articles', fn ($q) => $q->published())
            ->with(['articles' => fn ($q) => $q->published()->select('articles.id', 'articles.updated_at')])
            ->get(['id', 'slug'])
            ->each(function (Tag $t) use (&$urls) {
                $urls[] = $this->entry($t->url(), $t->articles->max('updated_at'), 'weekly', '0.4');
            });

        $xml = view('sitemap', ['urls' => $urls])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }

    /** @return array<string, mixed> */
    private function entry(string $loc, mixed $lastmod, string $changefreq, string $priority): array
    {
        return [
            'loc' => $loc,
            'lastmod' => $lastmod ? Carbon::parse($lastmod)->toAtomString() : null,
            'changefreq' => $changefreq,
            'priority' => $priority,
            'image' => null,
        ];
    }
}
