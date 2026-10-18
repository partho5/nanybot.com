<?php

namespace App\Services\Blog;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

/**
 * Full-page static cache. Public responses are written to public/page-cache
 * so nginx serves them as plain files without booting PHP (see
 * deploy/nginx.conf). Any content change clears the whole directory — cheap,
 * and impossible to get wrong.
 */
class PageCache
{
    public function directory(): string
    {
        return rtrim((string) config('blog.page_cache_path') ?: public_path('page-cache'), '/');
    }

    public function enabled(): bool
    {
        return (bool) config('blog.page_cache');
    }

    public function shouldCache(Request $request, Response $response): bool
    {
        return $this->enabled()
            && $request->isMethod('GET')
            && $request->getQueryString() === null
            && $response->getStatusCode() === 200
            && ! $response->headers->has('Set-Cookie');
    }

    public function store(Request $request, Response $response): void
    {
        $path = $this->pathFor($request);
        if ($path === null) {
            return;
        }

        File::ensureDirectoryExists(dirname($path));
        // Atomic write: nginx never serves a half-written file.
        $tmp = $path.'.'.bin2hex(random_bytes(4)).'.tmp';
        File::put($tmp, $response->getContent());
        rename($tmp, $path);
    }

    /** Map a URI to a file nginx can find with try_files. */
    public function pathFor(Request $request): ?string
    {
        $uri = trim($request->getPathInfo(), '/');

        if (str_contains($uri, '..')) {
            return null;
        }

        if ($uri === '') {
            return $this->directory().'/index.html';
        }

        // Files with an extension (sitemap.xml, feed.xml, *.md, llms.txt) keep it.
        return $this->directory().'/'.$uri.(pathinfo($uri, PATHINFO_EXTENSION) ? '' : '.html');
    }

    public function flush(): void
    {
        if (File::isDirectory($this->directory())) {
            File::cleanDirectory($this->directory());
        }
    }
}
