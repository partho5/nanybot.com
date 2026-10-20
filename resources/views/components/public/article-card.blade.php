{{--
    Listing card. The title link is stretched over the whole card (after:inset-0);
    category + author links sit above it (relative z-10) and stay clickable.
    featured: split layout from md (media 7/12, text 5/12), used for the first item on page 1.
    eager: LCP candidate (the controller preloads the same src/srcset/sizes).
--}}
@props(['article', 'eager' => false, 'featured' => false, 'sizes' => null, 'heading' => 'h2'])
@php
    use App\Models\Article;

    $sizes ??= $featured ? Article::FEATURED_SIZES : Article::CARD_SIZES;
    $imgWidth = $featured ? 1200 : 768;
    $imgHeight = (int) round($imgWidth * 10 / 16);
    $meta = 'font-mono text-[0.6875rem] font-medium uppercase leading-normal tracking-[0.08em]';
@endphp
<article {{ $attributes->class(['group relative', 'grid gap-6 md:grid-cols-[7fr_5fr] md:items-center md:gap-10' => $featured, 'flex flex-col' => ! $featured]) }}>
    @if ($article->featured_image)
        <div class="relative aspect-[16/10] overflow-hidden bg-soft">
            <img
                src="{{ $article->featuredImageUrl($imgWidth, Article::CARD_ASPECT) }}"
                @if ($srcset = $article->featuredImageSrcset(Article::CARD_ASPECT)) srcset="{{ $srcset }}" sizes="{{ $sizes }}" @endif
                width="{{ $imgWidth }}" height="{{ $imgHeight }}"
                alt="{{ $article->featured_image_alt ?: '' }}"
                @if ($eager) loading="eager" fetchpriority="high" @else loading="lazy" @endif
                decoding="async"
                class="size-full object-cover transition-transform duration-300 ease-out group-hover:scale-[1.03] motion-reduce:transition-none motion-reduce:group-hover:scale-100">
        </div>
    @else
        <x-public.cover :article="$article" />
    @endif

    <div @class(['flex flex-1 flex-col border-t border-line', 'pt-5 md:pt-6' => $featured, 'mt-5 pt-4' => ! $featured])>
        <p class="{{ $meta }} text-muted">
            @if ($article->category)
                <a href="{{ $article->category->url() }}" class="relative z-10 text-brand underline-offset-4 hover:underline">{{ $article->category->name }}</a>
                <span aria-hidden="true" class="mx-1">&middot;</span>
            @endif
            {{ $article->read_time_minutes }} min read
        </p>

        <{{ $heading }} @class([
            'mt-3 font-extrabold tracking-tight text-balance',
            'text-3xl leading-[1.1] md:text-4xl' => $featured,
            'text-xl leading-snug' => ! $featured,
        ])>
            <a href="{{ $article->url() }}" class="decoration-brand decoration-2 underline-offset-4 group-hover:underline focus-visible:underline after:absolute after:inset-0">{{ $article->title }}</a>
        </{{ $heading }}>

        @if ($article->excerpt)
            <p @class(['mt-3 text-muted', 'line-clamp-3 text-lg' => $featured, 'line-clamp-2 text-[0.9375rem]' => ! $featured])>{{ \Illuminate\Support\Str::limit($article->excerpt, 300) }}</p>
        @endif

        <p @class(['text-sm text-muted', 'mt-5' => $featured, 'mt-auto pt-4' => ! $featured])>
            @if ($article->author)
                <a href="{{ $article->author->authorUrl() }}" class="relative z-10 font-medium text-ink underline-offset-4 hover:underline">{{ $article->author->name }}</a>
                @if ($article->published_at)<span aria-hidden="true" class="mx-1">&middot;</span>@endif
            @endif
            @if ($article->published_at)
                <time datetime="{{ $article->published_at->toAtomString() }}">{{ $article->published_at->format('M j, Y') }}</time>
            @endif
        </p>
    </div>
</article>
