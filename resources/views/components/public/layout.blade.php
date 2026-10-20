@props(['seo'])
@php
    use App\Support\InlineAssets;

    $ogImage = $seo->image ?: url(config('blog.logo'));
    $ogImageAlt = $seo->imageAlt ?: config('blog.name');
    $fullTitle = $seo->fullTitle();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title>{{ $fullTitle }}</title>
    <meta name="description" content="{{ $seo->description }}">
    <meta name="robots" content="{{ $seo->robots }}">
    @if ($seo->canonical)
        <link rel="canonical" href="{{ $seo->canonical }}">
    @endif

    <meta property="og:site_name" content="{{ config('blog.name') }}">
    <meta property="og:locale" content="{{ config('blog.locale') }}">
    <meta property="og:type" content="{{ $seo->type }}">
    <meta property="og:title" content="{{ $seo->title }}">
    <meta property="og:description" content="{{ $seo->description }}">
    <meta property="og:url" content="{{ $seo->canonical }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:alt" content="{{ $ogImageAlt }}">
    @foreach ($seo->meta as $m)
        <meta property="{{ $m['property'] }}" content="{{ $m['content'] }}">
    @endforeach
    <meta name="twitter:card" content="{{ $seo->image ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $seo->title }}">
    <meta name="twitter:description" content="{{ $seo->description }}">
    <meta name="twitter:image" content="{{ $ogImage }}">
    <meta name="twitter:image:alt" content="{{ $ogImageAlt }}">

    <link rel="icon" href="/favicon.ico" sizes="48x48">
    <link rel="icon" href="/favicon-32x32.png" type="image/png" sizes="32x32">
    <link rel="icon" href="/favicon-16x16.png" type="image/png" sizes="16x16">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png" sizes="180x180">
    <link rel="manifest" href="/site.webmanifest">
    <link rel="alternate" type="application/rss+xml" title="{{ config('blog.name') }} RSS" href="{{ route('blog.feed') }}">
    @foreach ($seo->links as $link)
        <link rel="{{ $link['rel'] }}" href="{{ $link['href'] }}"@isset($link['type']) type="{{ $link['type'] }}"@endisset @isset($link['title']) title="{{ $link['title'] }}"@endisset>
    @endforeach

    @if ($seo->preloadImage)
        <link rel="preload" as="image" href="{{ $seo->preloadImage['href'] }}"@if (! empty($seo->preloadImage['srcset'])) imagesrcset="{{ $seo->preloadImage['srcset'] }}" imagesizes="{{ $seo->preloadImage['sizes'] ?? '100vw' }}"@endif fetchpriority="high">
    @endif

    <script type="application/ld+json">{!! $seo->jsonLd() !!}</script>

    {{ InlineAssets::css('resources/css/blog.css') }}
</head>
<body class="flex min-h-screen flex-col">
    <a href="#main" class="skip-link">Skip to content</a>

    <header class="border-b border-line">
        <div class="wrap flex flex-wrap items-center justify-between gap-x-4 gap-y-2 py-3">
            <a href="{{ route('home') }}" class="flex items-center gap-2 text-lg font-bold tracking-tight"><img src="/logo-64.png" alt="" width="28" height="28" class="block size-7">{{ config('blog.name') }}</a>
            <nav aria-label="Primary" class="flex items-center gap-4 text-sm">
                <a href="{{ route('blog.index') }}" class="font-medium hover:text-brand">Blog</a>
                <a href="{{ route('blog.feed') }}" class="text-muted hover:text-brand">RSS</a>
                <form action="{{ route('blog.search') }}" method="get" role="search" class="flex">
                    <label for="site-search" class="sr-only">Search articles</label>
                    <input id="site-search" type="search" name="q" minlength="2" placeholder="Search…" class="w-28 rounded-md border border-line bg-paper px-2 py-1 text-sm sm:w-44">
                    <button type="submit" class="sr-only">Search</button>
                </form>
            </nav>
        </div>
    </header>

    <main id="main" class="flex-1">
        {{ $slot }}
    </main>

    <footer class="mt-16 border-t border-line text-sm text-muted">
        <div class="wrap flex flex-wrap items-center justify-between gap-x-6 gap-y-2 py-6">
            <p>&copy; {{ date('Y') }} {{ config('blog.name') }}</p>
            <nav aria-label="Footer" class="flex flex-wrap gap-x-4 gap-y-1">
                <a href="{{ route('blog.index') }}" class="hover:text-brand">Blog</a>
                <a href="{{ route('blog.feed') }}" class="hover:text-brand">RSS</a>
                <a href="{{ route('sitemap') }}" class="hover:text-brand">Sitemap</a>
                <a href="{{ route('llms') }}" class="hover:text-brand">llms.txt</a>
            </nav>
        </div>
    </footer>
</body>
</html>
