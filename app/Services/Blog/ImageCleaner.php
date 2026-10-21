<?php

namespace App\Services\Blog;

use App\Models\Article;
use App\Models\User;
use App\Services\CloudinaryService;

/**
 * Removes images from Cloudinary once nothing on the site uses them any more.
 *
 * Callers pass candidate URLs after the new state is saved; an image is only
 * deleted when no article body, featured image or avatar still points at it,
 * so a URL reused elsewhere (or submitted by a client) can never take down
 * a live image. Only images in our own Cloudinary cloud are ever touched.
 */
class ImageCleaner
{
    public function __construct(private CloudinaryService $cloudinary) {}

    /**
     * Our Cloudinary image URLs found anywhere in a body (HTML or Markdown).
     *
     * @return list<string>
     */
    public function urlsIn(?string $content): array
    {
        if (! $content || ! preg_match_all('#https://res\.cloudinary\.com/[^\s"\'<>()\\\\]+#i', $content, $m)) {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map(fn ($u) => html_entity_decode($u), $m[0]),
            fn ($u) => $this->owns($u),
        )));
    }

    /**
     * Delete each candidate that is no longer referenced anywhere.
     *
     * @param  iterable<string>  $urls
     */
    public function purgeUnused(iterable $urls): void
    {
        $done = [];

        foreach ($urls as $url) {
            $id = is_string($url) && $this->owns($url) ? $this->cloudinary->extractPublicId($url) : null;

            if ($id === null || isset($done[$id])) {
                continue;
            }
            $done[$id] = true;

            if (! $this->isReferenced($id)) {
                $this->cloudinary->delete($url);
            }
        }
    }

    private function owns(string $url): bool
    {
        $cloud = config('services.cloudinary.cloud_name');

        return $cloud && str_contains($url, "res.cloudinary.com/{$cloud}/image/upload/");
    }

    private function isReferenced(string $publicId): bool
    {
        $needle = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $publicId).'%';

        return Article::query()
            ->where(fn ($q) => $q->where('body', 'like', $needle)
                ->orWhere('body_html', 'like', $needle)
                ->orWhere('featured_image', 'like', $needle))
            ->exists()
            || User::query()->where('avatar', 'like', $needle)->exists();
    }
}
