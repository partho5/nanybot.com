@props(['seo'])
@php
    $ld = [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => 'Nanybot',
        'url' => url('/'),
        'parentOrganization' => ['@type' => 'Organization', 'name' => 'Protim Soft'],
        'description' => 'Software that automates repeated business tasks.',
    ];
@endphp
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $seo->title }}</title>
<meta name="description" content="{{ $seo->description }}">
<meta name="robots" content="{{ $seo->robots }}">
<link rel="canonical" href="{{ $seo->canonical }}">
<meta name="theme-color" content="#EFEBE2">
<meta property="og:type" content="website">
<meta property="og:site_name" content="Nanybot">
<meta property="og:title" content="{{ $seo->title }}">
<meta property="og:description" content="{{ $seo->description }}">
<meta property="og:url" content="{{ $seo->canonical }}">
<meta property="og:image" content="{{ url(config('blog.logo')) }}">
<meta name="twitter:card" content="summary">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="icon" href="/favicon.ico" sizes="48x48">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<link rel="alternate" type="application/rss+xml" title="{{ config('blog.name') }} RSS" href="{{ route('blog.feed') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500&family=Schibsted+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
{{ \App\Support\InlineAssets::css('resources/css/landing.css') }}
</head>
<body class="bg-paper font-sans text-ink antialiased">
<a href="#main" class="skip">Skip to content</a>
<header class="border-b border-ink">
  <div class="max-w-6xl mx-auto px-5 h-14 flex items-center justify-between">
    <a href="/" class="flex items-center gap-2 text-lg font-extrabold tracking-tight"><span class="block w-3 h-3 bg-rust" aria-hidden="true"></span>nanybot</a>
    <nav aria-label="Main" class="flex items-center gap-6 text-sm font-medium">
      <a class="hover:underline underline-offset-4" href="/#software">Software</a>
      <a class="hover:underline underline-offset-4 hidden sm:inline" href="/#how">How it saves</a>
      <a class="hover:underline underline-offset-4" href="{{ route('blog.index') }}">Blog</a>
      <a class="hover:underline underline-offset-4" href="{{ route('contact') }}">Contact</a>
    </nav>
  </div>
</header>
<main id="main">
{{ $slot }}
</main>
<footer class="border-t border-ink mt-24">
  <div class="max-w-6xl mx-auto px-5 py-12 grid gap-10 sm:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr]">
    <div>
      <a href="/" class="flex items-center gap-2 text-lg font-extrabold tracking-tight"><span class="block w-3 h-3 bg-rust" aria-hidden="true"></span>nanybot</a>
      <p class="mt-3 max-w-xs text-sm text-mute">Repeated tasks automated. For business owners.</p>
    </div>
    <nav aria-label="Software">
      <p class="font-mono text-xs uppercase tracking-wider text-mute">Software</p>
      <ul class="mt-3 space-y-2 text-sm">
        <li><a class="hover:underline underline-offset-4" href="/ai-laptop-solver/">Laptop Care</a></li>
        <li><a class="hover:underline underline-offset-4" href="https://social.nanybot.com/">Nany Social</a></li>
        <li><a class="hover:underline underline-offset-4" href="{{ route('blog.index') }}">Blog</a></li>
      </ul>
    </nav>
    <nav aria-label="Legal and contact">
      <p class="font-mono text-xs uppercase tracking-wider text-mute">Company</p>
      <ul class="mt-3 space-y-2 text-sm">
        <li><a class="hover:underline underline-offset-4" href="{{ route('privacy') }}">Privacy policy</a></li>
        <li><a class="hover:underline underline-offset-4" href="{{ route('terms') }}">Terms of service</a></li>
        <li><a class="hover:underline underline-offset-4" href="{{ route('refund') }}">Refund policy</a></li>
        <li><a class="hover:underline underline-offset-4" href="{{ route('contact') }}">Contact</a></li>
      </ul>
    </nav>
  </div>
  <div class="border-t border-ink">
    <p class="max-w-6xl mx-auto px-5 py-4 font-mono text-xs text-mute">&copy; {{ date('Y') }} Protim Soft. Nanybot is a Protim Soft service.</p>
  </div>
</footer>
@stack('scripts')
</body>
</html>
