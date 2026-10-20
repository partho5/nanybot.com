{{-- Blog listing: index, category, tag and author pages (BlogController::listing). --}}
@php
    $mono = 'font-mono text-xs font-medium uppercase tracking-[0.08em]';
    $featuredFirst = $page === 1;
@endphp
<x-public.layout :seo="$seo">
    <div class="wrap">
        @if ($author)
            {{-- The profile header is the page intro; the author's name is the H1. --}}
            <x-public.author-card :user="$author" :full="true" :count="$articles->total()" :page="$page" />
        @else
            <header class="border-b border-line pt-12 pb-10 md:pt-20 md:pb-14">
                <p class="{{ $mono }} text-muted">
                    @if ($eyebrow)
                        <a href="{{ route('blog.index') }}" class="underline-offset-4 hover:text-ink hover:underline">Blog</a>
                        <span aria-hidden="true" class="mx-1">/</span>
                        {{ $eyebrow }}
                    @else
                        Blog
                    @endif
                </p>
                <h1 class="mt-4 max-w-4xl text-[clamp(2.5rem,6vw,4.5rem)] leading-[1.02] font-extrabold tracking-[-0.035em] text-balance break-words">
                    {{ $heading }}@if ($page > 1)<span class="text-muted"> &mdash; Page {{ $page }}</span>@endif
                </h1>
                @if ($intro)
                    <p class="mt-5 max-w-2xl text-lg text-muted md:text-xl">{{ $intro }}</p>
                @endif
            </header>
        @endif

        @if ($categories->isNotEmpty() && ! $author)
            {{-- One row on mobile (scrolls sideways, no visible scrollbar); wraps from md. py-1 keeps focus outlines unclipped. --}}
            <nav aria-label="Categories" class="bleed mt-6 overflow-x-auto px-[var(--gutter)] [scrollbar-width:none] md:mt-8 md:overflow-visible md:px-0 [&::-webkit-scrollbar]:hidden">
                <ul class="flex w-max gap-2 py-1 md:w-auto md:flex-wrap">
                    <li><a href="{{ route('blog.index') }}" class="chip" @if (! $activeCategory && ! $eyebrow) aria-current="page" @endif>All</a></li>
                    @foreach ($categories as $category)
                        <li><a href="{{ $category->url() }}" class="chip" @if ($activeCategory === $category->id) aria-current="page" @endif>{{ $category->name }}</a></li>
                    @endforeach
                </ul>
            </nav>
        @endif

        @if ($articles->isEmpty())
            <div class="mx-auto max-w-md py-20 text-center md:py-28">
                <span class="mx-auto block size-2.5 bg-brand" aria-hidden="true"></span>
                <h2 class="mt-6 text-3xl font-extrabold tracking-tight">No articles yet</h2>
                <p class="mt-3 text-muted">New guides are on the way. Check back soon.</p>
                @if ($eyebrow)
                    <a href="{{ route('blog.index') }}" class="{{ $mono }} mt-8 inline-flex h-10 items-center border border-ink px-4 hover:bg-ink hover:text-paper">&larr; All articles</a>
                @else
                    <a href="/#software" class="{{ $mono }} mt-8 inline-flex h-10 items-center border border-ink px-4 hover:bg-ink hover:text-paper">See the software &rarr;</a>
                @endif
            </div>
        @else
            @php
                $items = collect($articles->items());
                $featured = $featuredFirst ? $items->shift() : null;
            @endphp

            @if ($featured)
                <x-public.article-card :article="$featured" :featured="true" :eager="true" class="mt-10 md:mt-14" />
            @endif

            @if ($items->isNotEmpty())
                <div @class(['grid gap-x-8 gap-y-14 sm:grid-cols-2 lg:grid-cols-3', 'mt-16 md:mt-20' => $featured, 'mt-10 md:mt-14' => ! $featured])>
                    @foreach ($items as $article)
                        <x-public.article-card :article="$article" :eager="! $featured && $loop->first" />
                    @endforeach
                </div>
            @endif

            <x-public.pagination :paginator="$articles" :page-url="$pageUrl" />
        @endif
    </div>
</x-public.layout>
