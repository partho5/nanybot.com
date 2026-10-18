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

    /** Featured image at a given width, 16:9 crop when served from Cloudinary. */
    public function featuredImageUrl(int $width = 1200): ?string
    {
        if (! $this->featured_image) {
            return null;
        }

        return app(CloudinaryService::class)->transform(
            $this->featured_image,
            "c_fill,ar_16:9,g_auto,w_{$width}"
        );
    }

    public function featuredImageSrcset(): ?string
    {
        if (! $this->featured_image || ! app(CloudinaryService::class)->isCloudinaryUrl($this->featured_image)) {
            return null;
        }

        return collect([480, 768, 1200, 1600])
            ->map(fn ($w) => $this->featuredImageUrl($w)." {$w}w")
            ->implode(', ');
    }

    /** Auto-calculate read time from word count (200 wpm). Copied from reference. */
    public static function calculateReadTime(string $body): int
    {
        $words = str_word_count(strip_tags($body));

        return max(1, (int) ceil($words / 200));
    }
}
