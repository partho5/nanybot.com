@props(['article', 'eager' => false, 'hero' => false])
@php
    $img = $article->featured_image;
    $sizes = $hero
        ? '(min-width: 1160px) 1120px, 100vw'
        : '(min-width: 1160px) 360px, (min-width: 640px) 50vw, 100vw';
    $imgWidth = $hero ? 1200 : 768;
    $imgHeight = $hero ? 675 : 432;
@endphp
<article {{ $attributes->class(['group flex flex-col']) }}>
    @if ($img)
        <a href="{{ $article->url() }}" tabindex="-1" aria-hidden="true" class="bleed block overflow-hidden bg-soft md:rounded-lg">
            <img
                src="{{ $article->featuredImageUrl($imgWidth) }}"
                @if ($srcset = $article->featuredImageSrcset()) srcset="{{ $srcset }}" sizes="{{ $sizes }}" @endif
                width="{{ $imgWidth }}" height="{{ $imgHeight }}"
                alt="{{ $article->featured_image_alt ?: '' }}"
                @if ($eager) loading="eager" fetchpriority="high" @else loading="lazy" @endif
                decoding="async"
                class="aspect-video w-full object-cover {{ $hero ? 'md:aspect-[2/1]' : '' }}">
        </a>
    @endif

    <div class="flex flex-1 flex-col gap-2 pt-3">
        @if ($article->category)
            <p class="text-xs font-semibold uppercase tracking-wide text-brand">
                <a href="{{ $article->category->url() }}">{{ $article->category->name }}</a>
            </p>
        @endif

        <h2 class="{{ $hero ? 'text-2xl md:text-3xl' : 'text-lg' }} font-bold leading-snug tracking-tight">
            <a href="{{ $article->url() }}" class="hover:text-brand">{{ $article->title }}</a>
        </h2>

        @if ($article->excerpt)
            <p class="text-muted {{ $hero ? '' : 'text-sm' }}">{{ \Illuminate\Support\Str::limit($article->excerpt, $hero ? 220 : 140) }}</p>
        @endif

        <p class="mt-auto pt-1 text-xs text-muted">
            @if ($article->author)
                <a href="{{ $article->author->authorUrl() }}" class="font-medium text-ink hover:text-brand">{{ $article->author->name }}</a>
                <span aria-hidden="true">&middot;</span>
            @endif
            @if ($article->published_at)
                <time datetime="{{ $article->published_at->toAtomString() }}">{{ $article->published_at->format('M j, Y') }}</time>
                <span aria-hidden="true">&middot;</span>
            @endif
            {{ $article->read_time_minutes }} min read
        </p>
    </div>
</article>
