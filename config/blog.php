<?php

use App\Models\Article;

return [

    /*
    | Brand / publisher (Organization schema, OG site_name, feed).
    */
    'name' => env('APP_NAME', 'NanyBot'),
    'domain' => 'nanybot.com',
    'tagline' => 'Guides, ideas and notes from the NanyBot team.',
    'logo' => '/logo.png',
    'locale' => 'en_US',
    'same_as' => array_values(array_filter(array_map('trim', explode(',', (string) env('BLOG_SAME_AS', ''))))),

    'per_page' => 12,

    /*
    | Single-segment paths under /blog/ that are routes, not article slugs.
    */
    'reserved_slugs' => Article::RESERVED_SLUGS,

    /*
    | Static page cache (public/page-cache, served by nginx). Disable locally if
    | you are iterating on Blade templates.
    */
    'page_cache' => env('BLOG_PAGE_CACHE', true),

    // Where cached pages are written; null means public/page-cache. Tests point this at a temp dir.
    'page_cache_path' => env('BLOG_PAGE_CACHE_PATH'),

    'webhook' => [
        'rate_limit_per_minute' => 30,
    ],

];
