<?php

namespace App\Models;

use App\Enums\ArticleStatus;
use App\Services\CloudinaryService;
use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Article extends Model
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory;

    /** Slugs that would collide with /blog/{search,page,feed,category,tag,author} routes. */
    public const RESERVED_SLUGS = ['search', 'page', 'feed', 'category', 'tag', 'author'];

    /**
     * Listing card images (resources/views/components/public/article-card).
     * The controller preloads the first one, so its <link rel=preload> must
     * use the same aspect, src width and sizes as the <img>.
     */
    public const CARD_ASPECT = '16:10';

    public const CARD_SIZES = '(min-width: 1168px) 352px, (min-width: 1024px) 30vw, (min-width: 640px) 50vw, 100vw';

    /** Featured (split) slot on page 1: image takes 7/12 of the row from md. */
    public const FEATURED_SIZES = '(min-width: 1168px) 630px, (min-width: 768px) 58vw, 100vw';

    /** Article page featured image: edge to edge on mobile, max 960px wide (blog/show). */
    public const ARTICLE_IMAGE_SIZES = '(min-width: 1008px) 960px, (min-width: 768px) calc(100vw - 48px), 100vw';

    protected $fillable = [
        'author_id',
        'category_id',
        'api_key_id',
        'external_id',
        'title',
        'slug',
        'excerpt',
        'meta_title',
        'meta_description',
        'body',
        'body_html',
        'toc',
        'faq',
        'featured_image',
        'featured_image_alt',
        'featured_image_caption',
        'status',
        'read_time_minutes',
        'word_count',
        'published_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => ArticleStatus::class,
            'toc' => 'array',
            'faq' => 'array',
            'published_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsToMany<Tag, $this> */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->orderBy('name');
    }

    /** @return BelongsTo<ApiKey, $this> */
    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(ApiKey::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Visible on the public site. A scheduled article whose time has passed is
     * visible even if the scheduler hasn't flipped its status yet.
     */
    public function isPublished(): bool
    {
        return in_array($this->status, [ArticleStatus::Published, ArticleStatus::Scheduled], true)
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }

    /** @param Builder<Article> $query */
    public function scopePublished(Builder $query): void
    {
        $query->whereIn('status', [ArticleStatus::Published->value, ArticleStatus::Scheduled->value])
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Only the columns listing pages need — keeps body/body_html out of memory.
     *
     * @param  Builder<Article>  $query
     */
    public function scopeForListing(Builder $query): void
    {
        $query->select([
            'id', 'author_id', 'category_id', 'title', 'slug', 'excerpt',
            'featured_image', 'featured_image_alt', 'read_time_minutes', 'published_at', 'updated_at',
        ])->with(['author:id,name,username,avatar', 'category:id,name,slug']);
    }

    public function url(): string
    {
        return route('blog.show', $this);
    }

    public function metaTitle(): string
    {
        return $this->meta_title ?: $this->title;
    }

    public function metaDescription(): string
    {
        $text = $this->meta_description ?: $this->excerpt ?: strip_tags($this->body_html ?? '');

        return str(preg_replace('/\s+/', ' ', trim($text)))->limit(160, '…')->toString();
    }

    /**
     * body_html for the article page: each highlighted code block (always
     * `<pre><code class="hljs …">` from ContentProcessor) is wrapped in a
     * `.code-block` carrying its language, for the label and copy button.
     * h2/h3 with ids also get a `#` permalink. Done at render time so stored
     * HTML (feed, Markdown) stays untouched.
     */
    public function displayBodyHtml(): string
    {
        $html = $this->body_html ?? '';

        $html = preg_replace_callback(
            '~<pre><code class="hljs(?: language-([a-z0-9_+-]+))?">.*?</code></pre>~is',
            fn ($m) => '<div class="code-block"'.(! empty($m[1]) ? ' data-lang="'.e(strtolower($m[1])).'"' : '').'>'.$m[0].'</div>',
            $html
        ) ?? $html;

        // Heading permalinks (ids are slugs set by ContentProcessor); shown on hover/focus via CSS.
        return preg_replace_callback(
            '~<(h[23]) id="([a-z0-9-]+)">(.*?)</\1>~is',
            fn ($m) => "<{$m[1]} id=\"{$m[2]}\">{$m[3]} <a href=\"#{$m[2]}\" class=\"h-anchor\" aria-label=\"".e('Link to section: '.trim(html_entity_decode(strip_tags($m[3]), ENT_QUOTES | ENT_HTML5))).'">#</a></'.$m[1].'>',
            $html
        ) ?? $html;
    }

    /** Featured image at a given width, cropped to $aspect (default 16:9) when served from Cloudinary. */
    public function featuredImageUrl(int $width = 1200, string $aspect = '16:9'): ?string
    {
        if (! $this->featured_image) {
            return null;
        }

        return app(CloudinaryService::class)->transform(
            $this->featured_image,
            "c_fill,ar_{$aspect},g_auto,w_{$width}"
        );
    }

    public function featuredImageSrcset(string $aspect = '16:9'): ?string
    {
        if (! $this->featured_image || ! app(CloudinaryService::class)->isCloudinaryUrl($this->featured_image)) {
            return null;
        }

        return collect([480, 768, 1200, 1600])
            ->map(fn ($w) => $this->featuredImageUrl($w, $aspect)." {$w}w")
            ->implode(', ');
    }

    /** Auto-calculate read time from word count (200 wpm). Copied from reference. */
    public static function calculateReadTime(string $body): int
    {
        $words = str_word_count(strip_tags($body));

        return max(1, (int) ceil($words / 200));
    }
}
