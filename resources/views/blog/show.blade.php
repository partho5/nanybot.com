@php
    $url = $article->url();
    $encUrl = urlencode($url);
    $encTitle = urlencode($article->title);
    $toc = collect($article->toc ?? []);
    $published = $article->published_at;
    $updated = $article->updated_at;
    $showUpdated = $published && $updated && $updated->diffInDays($published) >= 1;
@endphp
<x-public.layout :seo="$seo">
    @if ($preview)
        <div class="border-b border-line bg-soft px-[var(--gutter)] py-2 text-center text-sm font-medium" role="status">
            Preview &mdash; this article is {{ $article->status->value }} and not visible to the public.
        </div>
    @endif

    <article class="wrap wrap-narrow py-8 md:py-12">
        <nav aria-label="Breadcrumb" class="mb-5 text-sm text-muted">
            <ol class="flex flex-wrap items-center gap-x-2">
                <li><a href="{{ route('home') }}" class="hover:text-brand">Home</a></li>
                <li aria-hidden="true">/</li>
                <li><a href="{{ route('blog.index') }}" class="hover:text-brand">Blog</a></li>
                @if ($article->category)
                    <li aria-hidden="true">/</li>
                    <li><a href="{{ $article->category->url() }}" class="hover:text-brand">{{ $article->category->name }}</a></li>
                @endif
            </ol>
        </nav>

        <header>
            <h1 class="text-3xl font-extrabold leading-tight tracking-tight md:text-4xl">{{ $article->title }}</h1>
            @if ($article->excerpt)
                <p class="article-excerpt mt-4 text-lg text-muted md:text-xl">{{ $article->excerpt }}</p>
            @endif

            <p class="mt-5 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-muted">
                @if ($article->author)
                    <a href="{{ $article->author->authorUrl() }}" rel="author" class="font-medium text-ink hover:text-brand">{{ $article->author->name }}</a>
                    <span aria-hidden="true">&middot;</span>
                @endif
                @if ($published)
                    <time datetime="{{ $published->toAtomString() }}">{{ $published->format('M j, Y') }}</time>
                    <span aria-hidden="true">&middot;</span>
                @endif
                @if ($showUpdated)
                    <span>Updated <time datetime="{{ $updated->toAtomString() }}">{{ $updated->format('M j, Y') }}</time></span>
                    <span aria-hidden="true">&middot;</span>
                @endif
                <span>{{ $article->read_time_minutes }} min read</span>
            </p>
        </header>

        @if ($article->featured_image)
            <figure class="bleed mt-6">
                <img
                    src="{{ $article->featuredImageUrl(1200) }}"
                    @if ($srcset = $article->featuredImageSrcset()) srcset="{{ $srcset }}" sizes="(min-width: 760px) 720px, 100vw" @endif
                    width="1200" height="675"
                    alt="{{ $article->featured_image_alt ?: $article->title }}"
                    loading="eager" fetchpriority="high" decoding="async"
                    class="aspect-video w-full bg-soft object-cover md:rounded-lg">
                @if ($article->featured_image_caption)
                    <figcaption class="px-[var(--gutter)] pt-2 text-center text-sm text-muted md:px-0">{{ $article->featured_image_caption }}</figcaption>
                @endif
            </figure>
        @endif

        @if ($toc->count() >= 3)
            <details class="mt-8 rounded-lg border border-line bg-soft p-4">
                <summary class="cursor-pointer font-semibold">On this page</summary>
                <ol class="mt-3 space-y-1.5 text-sm">
                    @foreach ($toc as $item)
                        <li @class(['pl-4' => $item['level'] === 3])>
                            <a href="#{{ $item['id'] }}" class="text-muted hover:text-brand">{{ $item['text'] }}</a>
                        </li>
                    @endforeach
                </ol>
            </details>
        @endif

        <div class="article-body mt-8">{!! $article->body_html !!}</div>

        @if ($article->tags->isNotEmpty())
            <ul class="mt-10 flex flex-wrap gap-2" aria-label="Tags">
                @foreach ($article->tags as $tag)
                    <li><a href="{{ $tag->url() }}" rel="tag" class="chip">#{{ $tag->name }}</a></li>
                @endforeach
            </ul>
        @endif

        <div class="mt-8 flex flex-wrap items-center gap-x-4 gap-y-2 border-y border-line py-4 text-sm">
            <span class="font-semibold">Share</span>
            <a href="https://twitter.com/intent/tweet?url={{ $encUrl }}&text={{ $encTitle }}" target="_blank" rel="noopener" class="text-muted hover:text-brand">X</a>
            <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ $encUrl }}" target="_blank" rel="noopener" class="text-muted hover:text-brand">LinkedIn</a>
            <a href="https://www.facebook.com/sharer/sharer.php?u={{ $encUrl }}" target="_blank" rel="noopener" class="text-muted hover:text-brand">Facebook</a>
            <a href="mailto:?subject={{ rawurlencode($article->title) }}&body={{ $encUrl }}" class="text-muted hover:text-brand">Email</a>
        </div>

        @if ($article->author)
            <x-public.author-card :user="$article->author" class="mt-8" />
        @endif

        @if ($newer || $older)
            <nav aria-label="More articles" class="mt-10 grid gap-4 text-sm sm:grid-cols-2">
                @if ($older)
                    <a href="{{ route('blog.show', $older) }}" rel="prev" class="block rounded-lg border border-line p-4 hover:border-brand">
                        <span class="block text-xs uppercase tracking-wide text-muted">&larr; Previous</span>
                        <span class="mt-1 block font-semibold">{{ $older->title }}</span>
                    </a>
                @else
                    <span></span>
                @endif
                @if ($newer)
                    <a href="{{ route('blog.show', $newer) }}" rel="next" class="block rounded-lg border border-line p-4 text-right hover:border-brand">
                        <span class="block text-xs uppercase tracking-wide text-muted">Next &rarr;</span>
                        <span class="mt-1 block font-semibold">{{ $newer->title }}</span>
                    </a>
                @endif
            </nav>
        @endif
    </article>

    @if ($related->isNotEmpty())
        <section class="wrap mt-4" aria-labelledby="related-heading">
            <h2 id="related-heading" class="mb-6 text-2xl font-bold tracking-tight">Related articles</h2>
            <div class="grid gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($related as $item)
                    <x-public.article-card :article="$item" />
                @endforeach
            </div>
        </section>
    @endif
</x-public.layout>
