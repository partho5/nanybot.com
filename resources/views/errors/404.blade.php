@php
    $seo = \App\Support\Seo::make('Page not found', 'The page you were looking for does not exist.', '')->noindex();
@endphp
<x-public.layout :seo="$seo">
    <div class="wrap wrap-narrow py-12 md:py-20">
        <p class="font-mono text-xs uppercase tracking-wider text-brand">Error 404</p>
        <h1 class="mt-3 text-4xl font-extrabold tracking-tight md:text-5xl">Page not found</h1>
        <p class="mt-4 text-lg text-muted">The page you were looking for does not exist or has moved.</p>

        <form action="{{ route('blog.search') }}" method="get" role="search" class="mt-10 flex">
            <label for="nf-search" class="sr-only">Search articles</label>
            <input id="nf-search" type="search" name="q" minlength="2" required placeholder="Search articles" class="h-12 min-w-0 flex-1 border border-r-0 border-ink bg-transparent px-3.5 text-base placeholder:text-muted">
            <button type="submit" class="h-12 shrink-0 border border-ink bg-ink px-5 font-mono text-xs font-medium uppercase tracking-wider text-paper hover:bg-brand hover:border-brand hover:text-brand-ink">Search</button>
        </form>

        <p class="mt-8 flex flex-wrap gap-3">
            <a href="{{ route('blog.index') }}" class="inline-flex h-11 items-center border border-ink px-5 font-mono text-xs font-medium uppercase tracking-wider hover:bg-ink hover:text-paper">Browse the blog</a>
            <a href="{{ route('home') }}" class="inline-flex h-11 items-center border border-line px-5 font-mono text-xs font-medium uppercase tracking-wider hover:border-ink">Home</a>
        </p>
    </div>
</x-public.layout>
