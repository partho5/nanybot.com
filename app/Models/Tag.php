<?php

namespace App\Models;

use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Tag extends Model
{
    /** @use HasFactory<TagFactory> */
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description'];

    /** @return BelongsToMany<Article, $this> */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function url(): string
    {
        return route('blog.tag', $this);
    }

    /**
     * Resolve tag names to ids, creating missing tags. Matching is by slug,
     * so "Remote Work" and "remote work" are the same tag.
     *
     * @param  array<int, string>  $names
     * @return Collection<int, int>
     */
    public static function idsFromNames(array $names): Collection
    {
        return collect($names)
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique(fn ($name) => Str::slug($name))
            ->take(20)
            ->map(fn ($name) => static::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => Str::limit($name, 60, '')],
            )->id)
            ->values();
    }
}
