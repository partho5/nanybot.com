<?php

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use App\Support\Schema;
use App\Support\Seo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use League\HTMLToMarkdown\HtmlConverter;

class BlogController extends Controller
{
    public function index(?int $page = null): View|RedirectResponse
    {
        return $this->listing(
            query: Article::query(),
            baseUrl: route('blog.index'),
            page: $page,
            heading: 'Blog',
            intro: config('blog.tagline'),
            breadcrumbs: [],
        );
    }

    public function category(Category $category, ?int $page = null): View|RedirectResponse
    {
        return $this->listing(
            query: Article::query()->where('category_id', $category->id),
            baseUrl: $category->url(),
            page: $page,
            heading: $category->name,
            intro: $category->description,
            breadcrumbs: [[$category->name, $category->url()]],
            eyebrow: 'Category',
            activeCategory: $category->id,
        );
    }

    public function tag(Tag $tag, ?int $page = null): View|RedirectResponse
    {
        return $this->listing(
            query: Article::query()->whereHas('tags', fn ($q) => $q->whereKey($tag->id)),
            baseUrl: $tag->url(),
            page: $page,
            heading: '#'.$tag->name,
            intro: $tag->description,
            breadcrumbs: [[$tag->name, $tag->url()]],
            eyebrow: 'Tag',
        );
    }

    public function author(string $author, ?int $page = null): View|RedirectResponse
    {
        $user = $this->resolveAuthor($author);

        // Canonical author URL uses the username when there is one.
        if ($user->username && $author !== $user->username) {
            return redirect()->to($user->authorUrl(), 301);
        }

        return $this->listing(
            query: Article::query()->where('author_id', $user->id),
            baseUrl: $user->authorUrl(),
            page: $page,
            heading: $user->name,
            intro: $user->headline,
            breadcrumbs: [[$user->name, $user->authorUrl()]],
            eyebrow: 'Author',
            author: $user,
        );
    }

    public function search(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $articles = collect();

        if (mb_strlen($q) >= 2) {
            $articles = Article::query()
                ->published()
                ->forListing()
                ->whereRaw("search_vector @@ websearch_to_tsquery('english', ?)", [$q])
                ->orderByRaw("ts_rank(search_vector, websearch_to_tsquery('english', ?)) DESC", [$q])
                ->limit(30)
                ->get();
        }

        $seo = Seo::make($q ? "Search: {$q}" : 'Search', 'Search the '.config('blog.name').' blog.', route('blog.search'))->noindex();

        return view('blog.search', compact('seo', 'q', 'articles'));
    }

    public function show(Article $article): View
    {
        abort_unless($article->isPublished(), 404);

        return $this->renderArticle($article);
    }

    /** Admin preview (auth-protected route in web.php), never cached. */
    public function preview(Article $article): View
    {
        Gate::authorize('update', $article);

        return $this->renderArticle($article, preview: true);
    }

    /** Plain Markdown copy of an article, for LLMs and readers who want raw text. */
    public function markdown(Article $article): Response
    {
        abort_unless($article->isPublished(), 404);
        $article->load('author:id,name,username', 'category:id,name,slug', 'tags:id,name,slug');

        $body = (new HtmlConverter(['strip_tags' => true, 'header_style' => 'atx', 'remove_nodes' => 'iframe script style']))
            ->convert($article->body_html ?? '');

        $front = collect([
            'title' => $article->title,
            'url' => $article->url(),
            'author' => $article->author?->name,
            'published' => $article->published_at?->toDateString(),
            'updated' => $article->updated_at?->toDateString(),
            'category' => $article->category?->name,
            'tags' => $article->tags->pluck('name')->implode(', ') ?: null,
            'description' => $article->metaDescription(),
        ])->filter()->map(fn ($v, $k) => $k.': '.str_replace("\n", ' ', (string) $v))->implode("\n");

        return response("---\n{$front}\n---\n\n# {$article->title}\n\n{$body}\n", 200, [
            'Content-Type' => 'text/markdown; charset=utf-8',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    private function renderArticle(Article $article, bool $preview = false): View
    {
        $article->load([
            'author:id,name,username,avatar,headline,bio,social_links',
            'category:id,name,slug',
            'tags:id,name,slug',
        ]);

        $url = $article->url();
        $seo = Seo::make($article->metaTitle(), $article->metaDescription(), $url);
        $seo->type = 'article';
        $seo->image = $article->featuredImageUrl(1200);
        $seo->imageAlt = $article->featured_image_alt ?: $article->title;
        $seo->links[] = ['rel' => 'alternate', 'type' => 'text/markdown', 'href' => route('blog.markdown', $article), 'title' => 'Markdown'];

        if ($article->featured_image) {
            $seo->preloadImage = [
                'href' => $article->featuredImageUrl(1200),
                'srcset' => $article->featuredImageSrcset(),
                'sizes' => '(min-width: 760px) 720px, 100vw',
            ];
        }

        $seo->meta = array_filter([
            ['property' => 'article:published_time', 'content' => $article->published_at?->toAtomString()],
            ['property' => 'article:modified_time', 'content' => $article->updated_at?->toAtomString()],
            $article->author ? ['property' => 'article:author', 'content' => $article->author->authorUrl()] : null,
            $article->category ? ['property' => 'article:section', 'content' => $article->category->name] : null,
            ...$article->tags->map(fn ($t) => ['property' => 'article:tag', 'content' => $t->name])->all(),
        ], fn ($m) => $m && $m['content']);

        $crumbs = [['Home', route('home')], ['Blog', route('blog.index')]];
        if ($article->category) {
            $crumbs[] = [$article->category->name, $article->category->url()];
        }
        $crumbs[] = [$article->title, $url];

        $seo->addSchema(Schema::blogPosting($article))->addSchema(Schema::breadcrumbs($crumbs));
        if ($article->faq) {
            $seo->addSchema(Schema::faq($article->faq, $url));
        }

        if ($preview) {
            $seo->noindex();
        }

        $related = $this->related($article);

        $newer = Article::published()->where('published_at', '>', $article->published_at ?? now())
            ->orderBy('published_at')->first(['id', 'title', 'slug']);
        $older = Article::published()->where('published_at', '<', $article->published_at ?? now())
            ->latest('published_at')->first(['id', 'title', 'slug']);

        return view('blog.show', compact('article', 'seo', 'related', 'newer', 'older', 'preview'));
    }

    /** @return Collection<int, Article> */
    private function related(Article $article)
    {
        $tagIds = $article->tags->pluck('id');

        return Article::query()
            ->published()
            ->forListing()
            ->whereKeyNot($article->id)
            ->where(fn (Builder $q) => $q
                ->when($article->category_id, fn ($q) => $q->where('category_id', $article->category_id))
                ->when($tagIds->isNotEmpty(), fn ($q) => $q->orWhereHas('tags', fn ($t) => $t->whereIn('tags.id', $tagIds))))
            ->latest('published_at')
            ->limit(3)
            ->get();
    }

    /**
     * Shared listing renderer. Pagination is path-based (/page/2) so every
     * page is a static file in the page cache; ?page= would bypass it.
     *
     * @param  Builder<Article>  $query
     * @param  array<int, array{0: string, 1: string}>  $breadcrumbs
     */
    private function listing(
        Builder $query,
        string $baseUrl,
        ?int $page,
        string $heading,
        ?string $intro,
        array $breadcrumbs,
        ?string $eyebrow = null,
        ?int $activeCategory = null,
        ?User $author = null,
    ): View|RedirectResponse {
        if ($page === 1) {
            return redirect()->to($baseUrl, 301);
        }
        $page ??= 1;

        /** @var LengthAwarePaginator<int, Article> $articles */
        $articles = $query->published()->forListing()
            ->latest('published_at')
            ->paginate(config('blog.per_page'), ['*'], 'page', $page);

        abort_if($page > 1 && $articles->isEmpty(), 404);

        $pageUrl = fn (int $n) => $n <= 1 ? $baseUrl : rtrim($baseUrl, '/').'/page/'.$n;
        $canonical = $pageUrl($page);

        $title = $heading.($page > 1 ? " — Page {$page}" : '');
        $seo = Seo::make(
            $heading === 'Blog' ? config('blog.name').' Blog'.($page > 1 ? " — Page {$page}" : '') : $title,
            $intro ?: "Articles about {$heading} on the ".config('blog.name').' blog.',
            $canonical,
        );
        if ($page > 1) {
            $seo->links[] = ['rel' => 'prev', 'href' => $pageUrl($page - 1)];
        }
        if ($articles->hasMorePages()) {
            $seo->links[] = ['rel' => 'next', 'href' => $pageUrl($page + 1)];
        }

        $first = $articles->first();
        if ($first?->featured_image) {
            $seo->image = $first->featuredImageUrl(1200);
            $seo->preloadImage = [
                'href' => $first->featuredImageUrl(1200),
                'srcset' => $first->featuredImageSrcset(),
                'sizes' => '(min-width: 1160px) 1120px, 100vw',
            ];
        }

        $crumbs = array_merge([['Home', route('home')], ['Blog', route('blog.index')]], $breadcrumbs);
        $seo->addSchema(Schema::collection($title, $canonical, $articles->items()))
            ->addSchema(Schema::breadcrumbs($crumbs));
        if ($author) {
            $seo->addSchema(Schema::person($author) + ['mainEntityOfPage' => $author->authorUrl()]);
        }

        $categories = Category::query()
            ->whereHas('articles', fn ($q) => $q->published())
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return view('blog.index', compact(
            'articles', 'seo', 'heading', 'intro', 'eyebrow', 'categories', 'activeCategory', 'author', 'page', 'pageUrl', 'breadcrumbs'
        ));
    }

    private function resolveAuthor(string $key): User
    {
        $user = User::query()
            ->where('username', $key)
            ->when(ctype_digit($key), fn ($q) => $q->orWhere('id', (int) $key))
            ->first(['id', 'name', 'username', 'avatar', 'headline', 'bio', 'social_links']);

        abort_unless($user && $user->articles()->published()->exists(), 404);

        return $user;
    }
}
