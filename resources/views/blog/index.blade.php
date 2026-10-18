<x-public.layout :seo="$seo">
    <div class="wrap py-8 md:py-12">
        @if ($breadcrumbs)
            <nav aria-label="Breadcrumb" class="mb-4 text-sm text-muted">
                <ol class="flex flex-wrap items-center gap-x-2">
                    <li><a href="{{ route('home') }}" class="hover:text-brand">Home</a></li>
                    <li aria-hidden="true">/</li>
                    <li><a href="{{ route('blog.index') }}" class="hover:text-brand">Blog</a></li>
                    @foreach ($breadcrumbs as [$name, $url])
                        <li aria-hidden="true">/</li>
                        <li><a href="{{ $url }}" @if ($loop->last) aria-current="page" @endif class="hover:text-brand">{{ $name }}</a></li>
                    @endforeach
                </ol>
            </nav>
        @endif

        <header class="mb-8 max-w-3xl">
            @if ($eyebrow)
                <p class="text-sm font-semibold uppercase tracking-wide text-brand">{{ $eyebrow }}</p>
            @endif
            <h1 class="text-3xl font-extrabold leading-tight tracking-tight md:text-4xl">{{ $heading }}@if ($page > 1) <span class="text-muted">&mdash; Page {{ $page }}</span>@endif</h1>
            @if ($intro && ! $author)
                <p class="mt-3 text-lg text-muted">{{ $intro }}</p>
            @endif
        </header>

        @if ($author)
            <x-public.author-card :user="$author" :full="true" class="mb-8 max-w-3xl" />
        @endif

        @if ($categories->isNotEmpty() && ! $author)
            <nav aria-label="Categories" class="mb-8 flex flex-wrap gap-2">
                <a href="{{ route('blog.index') }}" class="chip" @if (! $activeCategory && ! $eyebrow) aria-current="page" @endif>All</a>
                @foreach ($categories as $category)
                    <a href="{{ $category->url() }}" class="chip" @if ($activeCategory === $category->id) aria-current="page" @endif>{{ $category->name }}</a>
                @endforeach
            </nav>
        @endif

        @if ($articles->isEmpty())
            <p class="rounded-lg border border-line bg-soft p-6 text-muted">No articles yet. Check back soon.</p>
        @else
            <div class="grid gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($articles as $article)
                    @if ($loop->first)
                        <x-public.article-card :article="$article" :eager="true" :hero="true" class="sm:col-span-2 lg:col-span-3" />
                    @else
                        <x-public.article-card :article="$article" />
                    @endif
                @endforeach
            </div>

            <x-public.pagination :paginator="$articles" :page-url="$pageUrl" />
        @endif
    </div>
</x-public.layout>
