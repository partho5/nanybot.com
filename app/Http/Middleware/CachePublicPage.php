<?php

namespace App\Http\Middleware;

use App\Services\Blog\PageCache;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CachePublicPage
{
    public function __construct(private PageCache $cache) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->cache->shouldCache($request, $response)) {
            $this->cache->store($request, $response);
        }

        // Browsers revalidate after a minute; a CDN in front may hold it longer.
        if ($request->isMethod('GET') && $response->getStatusCode() === 200) {
            $response->headers->set('Cache-Control', 'public, max-age=60, s-maxage=600, stale-while-revalidate=86400');
        }

        return $response;
    }
}
