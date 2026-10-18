@php
    $seo = \App\Support\Seo::make('Page not found', 'The page you were looking for does not exist.', '')->noindex();
@endphp
<x-public.layout :seo="$seo">
    <div class="wrap wrap-narrow py-20 text-center">
        <p class="text-sm font-semibold uppercase tracking-wide text-brand">404</p>
        <h1 class="mt-2 text-3xl font-extrabold tracking-tight">Page not found</h1>
        <p class="mt-3 text-muted">The page you were looking for does not exist or has moved.</p>
        <p class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ route('home') }}" class="rounded-md bg-brand px-5 py-2.5 font-semibold text-brand-ink hover:opacity-90">Home</a>
            <a href="{{ route('blog.index') }}" class="rounded-md border border-line px-5 py-2.5 font-semibold hover:border-brand hover:text-brand">Browse the blog</a>
        </p>
    </div>
</x-public.layout>
