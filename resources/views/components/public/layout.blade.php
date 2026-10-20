@props(['seo'])
@php
    use App\Support\InlineAssets;

    $ogImage = $seo->image ?: url(config('blog.logo'));
    $ogImageAlt = $seo->imageAlt ?: config('blog.name');
    $fullTitle = $seo->fullTitle();
    $onBlog = request()->routeIs('blog.*', 'admin.articles.preview');
    $navLink = 'underline-offset-4 decoration-1 hover:underline';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0f1115" media="(prefers-color-scheme: dark)">
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

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500&family=Schibsted+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <script type="application/ld+json">{!! $seo->jsonLd() !!}</script>

    {{ InlineAssets::css('resources/css/blog.css') }}
</head>
<body class="flex min-h-screen flex-col">
    <a href="#main" class="skip-link">Skip to content</a>

    <header class="border-b border-line">
        <div class="wrap flex h-14 items-center justify-between gap-3">
            <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2 text-lg font-extrabold tracking-tight"><img src="/logo-64.png" alt="" width="28" height="28" class="block size-7">nanybot</a>
            <div class="flex items-center gap-3 sm:gap-6">
                <nav aria-label="Main" class="flex items-center gap-3.5 text-sm font-medium sm:gap-6">
                    <a href="/#software" class="{{ $navLink }}">Software</a>
                    <a href="{{ route('blog.index') }}" class="{{ $navLink }} aria-[current=page]:underline aria-[current=page]:decoration-brand aria-[current=page]:decoration-2"@if ($onBlog) aria-current="page"@endif>Blog</a>
                    <a href="{{ route('contact') }}" class="{{ $navLink }}">Contact</a>
                </nav>
                <form action="{{ route('blog.search') }}" method="get" role="search" class="hidden h-8 items-center border border-line focus-within:border-ink has-[input:focus-visible]:outline-2 has-[input:focus-visible]:outline-offset-2 has-[input:focus-visible]:outline-brand sm:flex">
                    <label for="site-search" class="sr-only">Search articles</label>
                    <input id="site-search" type="search" name="q" minlength="2" placeholder="Search articles" class="h-full w-36 min-w-0 bg-transparent px-2.5 text-sm placeholder:text-muted focus:outline-none lg:w-44">
                    <button type="submit" class="flex size-8 shrink-0 items-center justify-center border-l border-line text-muted hover:text-ink" aria-label="Search">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="square" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                    </button>
                </form>
                <a href="{{ route('blog.search') }}" class="-m-2 flex size-10 items-center justify-center sm:hidden" aria-label="Search articles">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="square" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                </a>
            </div>
        </div>
    </header>

    <main id="main" class="flex-1">
        {{ $slot }}
    </main>

    <footer class="mt-24 border-t border-line">
        <div class="wrap grid gap-10 py-12 sm:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr]">
            <div>
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-lg font-extrabold tracking-tight"><img src="/logo-64.png" alt="" width="28" height="28" class="block size-7">nanybot</a>
                <p class="mt-3 max-w-xs text-sm text-muted">Repeated tasks automated. For business owners.</p>
            </div>
            <nav aria-label="Software">
                <p class="font-mono text-xs uppercase tracking-wider text-muted">Software</p>
                <ul class="mt-3 space-y-2 text-sm">
                    <li><a class="{{ $navLink }}" href="/ai-laptop-solver/">Laptop Care</a></li>
                    <li><a class="{{ $navLink }}" href="https://social.nanybot.com/">Nany Social</a></li>
                    <li><a class="{{ $navLink }}" href="{{ route('blog.index') }}">Blog</a></li>
                    <li><a class="{{ $navLink }}" href="{{ route('blog.feed') }}">RSS feed</a></li>
                </ul>
            </nav>
            <nav aria-label="Legal and contact">
                <p class="font-mono text-xs uppercase tracking-wider text-muted">Company</p>
                <ul class="mt-3 space-y-2 text-sm">
                    <li><a class="{{ $navLink }}" href="{{ route('privacy') }}">Privacy policy</a></li>
                    <li><a class="{{ $navLink }}" href="{{ route('terms') }}">Terms of service</a></li>
                    <li><a class="{{ $navLink }}" href="{{ route('refund') }}">Refund policy</a></li>
                    <li><a class="{{ $navLink }}" href="{{ route('contact') }}">Contact</a></li>
                </ul>
            </nav>
        </div>
        <div class="border-t border-line">
            <div class="wrap flex flex-wrap items-center justify-between gap-x-6 gap-y-2 py-4 font-mono text-xs text-muted">
                <p>&copy; {{ date('Y') }} Protim Soft. Nanybot is a Protim Soft service.</p>
                <p class="flex gap-4">
                    <a href="{{ route('sitemap') }}" class="hover:text-ink">Sitemap</a>
                    <a href="{{ route('llms') }}" class="hover:text-ink">llms.txt</a>
                </p>
            </div>
        </div>
    </footer>
    {{ $scripts ?? '' }}
</body>
</html>
