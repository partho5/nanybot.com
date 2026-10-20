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
    /** @var array<string, string> path => lastmod */
    private const STATIC_PAGES = [
        'privacy' => '2026-10-14',
        'terms' => '2026-10-14',
        'refund' => '2026-10-14',
        'contact' => '2026-10-14',
        'ai-laptop-solver/' => '2026-10-02',
        'ai-laptop-solver/privacy/' => '2026-09-29',
        'ai-laptop-solver/terms/' => '2026-10-01',
        'ai-laptop-solver/refund/' => '2026-10-02',
        'ai-laptop-solver/support/' => '2026-10-04',
    ];

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

        // Company pages and the AI Laptop Solver static site (public/ai-laptop-solver).
        foreach (self::STATIC_PAGES as $path => $lastmod) {
            $urls[] = $this->entry(url($path), $lastmod, 'monthly', str_starts_with($path, 'ai-laptop-solver') ? '0.7' : '0.3');
        }

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
