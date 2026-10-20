<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ArticleStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveArticleRequest;
use App\Models\Article;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use App\Services\Blog\ArticleWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ArticleController extends Controller
{
    private const PER_PAGE = 20;

    public function __construct(private ArticleWriter $writer) {}

    /** Autocomplete for the tag input: `?q=` prefix/substring match, returns `{ tags: string[] }`. */
    public function tags(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        $tags = Tag::query()
            ->when($q !== '', fn ($query) => $query->where('name', 'ilike', '%'.addcslashes($q, '%_\\').'%'))
            ->orderBy('name')
            ->limit(20)
            ->pluck('name');

        return response()->json(['tags' => $tags]);
    }

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Article::class);

        $user = $request->user();
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => $request->query('status'),
            'category' => $request->query('category'),
            'tag' => $request->query('tag'),
            'author' => $user->isAdmin() ? $request->query('author') : null,
        ];

        // Every filter except status; the status tabs count within this scope.
        $scoped = Article::query()
            ->when(! $user->isAdmin(), fn ($q) => $q->where('author_id', $user->id))
            ->when($filters['author'], fn ($q, $id) => $q->where('author_id', (int) $id))
            ->when($filters['category'], fn ($q, $id) => $q->where('category_id', (int) $id))
            ->when($filters['tag'], fn ($q, $slug) => $q->whereHas('tags', fn ($t) => $t->where('tags.slug', $slug)))
            ->when($filters['q'] !== '', fn ($q) => $q->where('title', 'ilike', '%'.addcslashes($filters['q'], '%_\\').'%'));

        // One grouped query for all tab counts.
        $byStatus = (clone $scoped)->toBase()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $counts = ['all' => (int) $byStatus->sum()];
        foreach (ArticleStatus::cases() as $status) {
            $counts[$status->value] = (int) ($byStatus[$status->value] ?? 0);
        }

        $articles = $scoped
            ->select([
                'id', 'author_id', 'category_id', 'title', 'slug', 'status', 'featured_image',
                'read_time_minutes', 'published_at', 'created_at', 'updated_at',
            ])
            ->with(['author:id,name,username', 'category:id,name,slug'])
            ->when(ArticleStatus::tryFrom((string) $filters['status']), fn ($q, $s) => $q->where('status', $s->value))
            ->latest('updated_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (Article $a) => [
                'id' => $a->id,
                'title' => $a->title,
                'slug' => $a->slug,
                'status' => $a->status->value,
                'read_time_minutes' => $a->read_time_minutes,
                'published_at' => $a->published_at?->toIso8601String(),
                'updated_at' => $a->updated_at?->toIso8601String(),
                'author' => $a->author ? ['id' => $a->author->id, 'name' => $a->author->name] : null,
                'category' => $a->category ? ['id' => $a->category->id, 'name' => $a->category->name] : null,
                // 64x40 list thumbnail, 2x for retina (Cloudinary crop; other URLs untouched).
                'thumbnail_url' => $a->featuredImageUrl(128, '16:10'),
                // Public URL only once visible on the site; otherwise use the preview route.
                'public_url' => $a->isPublished() ? $a->url() : null,
                'preview_url' => route('admin.articles.preview', $a),
            ]);

        return Inertia::render('admin/articles/index', [
            'articles' => $articles,
            'filters' => $filters,
            'categories' => $this->categoryOptions(),
            'authors' => $user->isAdmin() ? $this->authorOptions() : [],
            'statuses' => $this->statusOptions(),
            'counts' => $counts,
            'isAdmin' => $user->isAdmin(),
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Article::class);

        return Inertia::render('admin/articles/create', $this->formProps($request->user()));
    }

    public function store(SaveArticleRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $data['author_id'] = $this->resolveAuthorId($user, $data['author_id'] ?? null, null);

        $article = $this->writer->save(new Article, $data);

        Inertia::flash('success', $this->message($article, 'created'));

        return redirect()->route('admin.articles.edit', $article);
    }

    public function edit(Request $request, Article $article): Response
    {
        Gate::authorize('update', $article);

        $article->load('tags:id,name');

        return Inertia::render('admin/articles/edit', [
            ...$this->formProps($request->user()),
            'article' => [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'excerpt' => $article->excerpt,
                'meta_title' => $article->meta_title,
                'meta_description' => $article->meta_description,
                'body' => $article->body,
                'featured_image' => $article->featured_image,
                'featured_image_alt' => $article->featured_image_alt,
                'featured_image_caption' => $article->featured_image_caption,
                'category_id' => $article->category_id,
                'author_id' => $article->author_id,
                'tags' => $article->tags->pluck('name')->values(),
                'status' => $article->status->value,
                // datetime-local friendly (app timezone); null unless scheduled.
                'publish_at' => $article->status === ArticleStatus::Scheduled
                    ? $article->published_at?->format('Y-m-d\TH:i')
                    : null,
                'published_at' => $article->published_at?->toIso8601String(),
                'updated_at' => $article->updated_at?->toIso8601String(),
                'is_published' => $article->isPublished(),
                'public_url' => $article->isPublished() ? $article->url() : null,
                'preview_url' => route('admin.articles.preview', $article),
            ],
        ]);
    }

    public function update(SaveArticleRequest $request, Article $article): RedirectResponse
    {
        $data = $request->validated();

        if (array_key_exists('author_id', $data)) {
            $data['author_id'] = $this->resolveAuthorId($request->user(), $data['author_id'], $article);
        }

        $article = $this->writer->save($article, $data);

        Inertia::flash('success', $this->message($article, 'updated'));

        // The slug may have changed, so redirect to the fresh route key.
        return redirect()->route('admin.articles.edit', $article);
    }

    public function destroy(Article $article): RedirectResponse
    {
        Gate::authorize('delete', $article);

        $article->delete();

        Inertia::flash('success', 'Article deleted.');

        return redirect()->route('admin.articles.index');
    }

    /**
     * Only admins may choose the author. Others always author their own new
     * articles and cannot reassign existing ones.
     */
    private function resolveAuthorId(User $user, mixed $requested, ?Article $article): int
    {
        if ($user->isAdmin() && $requested) {
            return (int) $requested;
        }

        return $article?->author_id ?? $user->id;
    }

    /** @return array<string, mixed> */
    private function formProps(User $user): array
    {
        return [
            'categories' => $this->categoryOptions(),
            'authors' => $user->isAdmin() ? $this->authorOptions() : [],
            'statuses' => $this->statusOptions(),
            'isAdmin' => $user->isAdmin(),
            'defaultAuthorId' => $user->id,
        ];
    }

    /** @return list<array{id: int, name: string, slug: string}> */
    private function categoryOptions(): array
    {
        return Category::orderBy('name')->get(['id', 'name', 'slug'])->toArray();
    }

    /** @return list<array{id: int, name: string}> */
    private function authorOptions(): array
    {
        return User::orderBy('name')->get(['id', 'name'])->toArray();
    }

    /** @return list<array{value: string, label: string}> */
    private function statusOptions(): array
    {
        return collect(ArticleStatus::cases())
            ->map(fn (ArticleStatus $s) => ['value' => $s->value, 'label' => ucfirst($s->value)])
            ->all();
    }

    private function message(Article $article, string $verb): string
    {
        return match ($article->status) {
            ArticleStatus::Published => "Article {$verb} and published.",
            ArticleStatus::Scheduled => "Article {$verb} and scheduled.",
            default => 'Draft saved.',
        };
    }
}
