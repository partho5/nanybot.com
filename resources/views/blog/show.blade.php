{{-- Article page; also the admin preview ($preview). Works fully without JS: the inline script only adds enhancements. --}}
@php
    use App\Models\Article;

    $url = $article->url();
    $encUrl = urlencode($url);
    $encTitle = urlencode($article->title);
    $toc = collect($article->toc ?? []);
    $hasToc = $toc->count() >= 3;
    $published = $article->published_at;
    $updated = $article->updated_at;
    $showUpdated = $published && $updated && $updated->diffInDays($published) >= 1;
    $mono = 'font-mono text-xs font-medium uppercase tracking-[0.08em]';
    $cta = config('blog.cta');
    $shareBtn = 'flex size-10 items-center justify-center border border-line text-ink transition-colors hover:border-ink';
@endphp
<x-public.layout :seo="$seo">
    @if ($preview)
        @php
            $pill = match (true) {
                $unsaved => 'Unsaved preview',
                $article->status->value === 'scheduled' => 'Scheduled'.($article->published_at ? ' · '.$article->published_at->format('M j, Y') : ''),
                default => $article->status->value,
            };
        @endphp
        <div class="ink-panel sticky top-0 z-50 border-b border-paper/20 bg-ink text-paper" role="region" aria-label="Preview">
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 px-[var(--gutter)] py-2.5">
                <span class="{{ $mono }} inline-flex items-center gap-2 border border-brand px-2 py-1 text-[0.6875rem] leading-none"><span class="size-1.5 bg-brand" aria-hidden="true"></span>{{ $pill }}</span>
                <span class="text-sm text-paper/80">Only you can see this.</span>
                @if ($editUrl)
                    <a href="{{ $editUrl }}" class="ml-auto text-sm font-semibold underline decoration-brand decoration-2 underline-offset-4 hover:decoration-paper">&larr; Back to editor</a>
                @else
                    <button type="button" class="js-close-preview ml-auto text-sm font-semibold underline decoration-brand decoration-2 underline-offset-4 hover:decoration-paper" hidden>Close preview</button>
                @endif
            </div>
        </div>
    @endif

    <article>
        <header class="wrap wrap-narrow pt-10 md:pt-16">
            <div class="border-b border-line pb-8 md:pb-10">
                <nav aria-label="Breadcrumb" class="{{ $mono }} text-[0.6875rem] text-muted">
                    <ol class="flex flex-wrap items-center gap-x-2 gap-y-1">
                        <li><a href="{{ route('home') }}" class="underline-offset-4 hover:text-ink hover:underline">Home</a></li>
                        <li aria-hidden="true">/</li>
                        <li><a href="{{ route('blog.index') }}" class="underline-offset-4 hover:text-ink hover:underline">Blog</a></li>
                        @if ($article->category)
                            <li aria-hidden="true">/</li>
                            <li><a href="{{ $article->category->url() }}" class="text-brand underline-offset-4 hover:underline">{{ $article->category->name }}</a></li>
                        @endif
                    </ol>
                </nav>

                <h1 class="mt-5 text-[clamp(2.25rem,5vw,3.5rem)] leading-[1.06] font-extrabold tracking-[-0.03em] text-balance break-words">{{ $article->title }}</h1>
                @if ($article->excerpt)
                    <p class="article-excerpt mt-5 max-w-[60ch] text-xl leading-snug text-muted md:text-2xl">{{ $article->excerpt }}</p>
                @endif

                <div class="mt-8 flex items-center gap-3">
                    @if ($article->author)
                        <x-public.avatar :user="$article->author" :size="40" :eager="true" />
                    @endif
                    <div class="min-w-0">
                        @if ($article->author)
                            <a href="{{ $article->author->authorUrl() }}" rel="author" class="font-semibold leading-tight underline-offset-4 decoration-brand decoration-2 hover:underline">{{ $article->author->name }}</a>
                        @endif
                        <p class="{{ $mono }} mt-0.5 text-[0.6875rem] text-muted">
                            @if ($published)
                                <time datetime="{{ $published->toAtomString() }}">{{ $published->format('M j, Y') }}</time>
                                <span aria-hidden="true" class="mx-1">&middot;</span>
                            @endif
                            @if ($showUpdated)
                                <span>Updated <time datetime="{{ $updated->toAtomString() }}">{{ $updated->format('M j, Y') }}</time></span>
                                <span aria-hidden="true" class="mx-1">&middot;</span>
                            @endif
                            <span>{{ $article->read_time_minutes }} min read</span>
                        </p>
                    </div>
                </div>
            </div>
        </header>

        @if ($article->featured_image)
            <figure class="mx-auto mt-8 max-w-[calc(960px+2*var(--gutter))] md:mt-10 md:px-[var(--gutter)]">
                <img
                    src="{{ $article->featuredImageUrl(1200) }}"
                    @if ($srcset = $article->featuredImageSrcset()) srcset="{{ $srcset }}" sizes="{{ Article::ARTICLE_IMAGE_SIZES }}" @endif
                    width="1200" height="675"
                    alt="{{ $article->featured_image_alt ?: $article->title }}"
                    loading="eager" fetchpriority="high" decoding="async"
                    class="block aspect-video w-full bg-soft object-cover">
                @if ($article->featured_image_caption)
                    <figcaption class="mt-2 px-[var(--gutter)] text-center font-mono text-xs leading-relaxed text-muted md:px-0">{{ $article->featured_image_caption }}</figcaption>
                @endif
            </figure>
        @endif

        <div class="article-layout wrap mt-10 max-w-[calc(1280px+2*var(--gutter))] md:mt-12">
            <div class="article-main">
                @if ($hasToc)
                    {{-- Below 1200px: collapsible box. From 1200px the sticky rail below takes over. --}}
                    <details class="toc-box mb-10 border border-line min-[1200px]:hidden">
                        <summary class="{{ $mono }} flex cursor-pointer list-none items-center justify-between gap-4 px-4 py-3 [&::-webkit-details-marker]:hidden">
                            On this page
                            <span class="toc-box-icon text-base leading-none" aria-hidden="true"></span>
                        </summary>
                        <ol class="toc-list border-t border-line px-4 py-3 text-[0.9375rem]">
                            @foreach ($toc as $item)
                                <li @class(['pl-4' => $item['level'] === 3])><a href="#{{ $item['id'] }}">{{ $item['text'] }}</a></li>
                            @endforeach
                        </ol>
                    </details>
                @endif

                <div class="article-body">{!! $article->displayBodyHtml() !!}</div>
            </div>

            @if ($hasToc)
                <aside class="toc-rail hidden min-[1200px]:block">
                    <nav aria-labelledby="toc-rail-heading" class="sticky top-8">
                        <p id="toc-rail-heading" class="{{ $mono }} text-[0.6875rem] text-muted">On this page</p>
                        <ol class="toc-list toc mt-4 text-sm">
                            @foreach ($toc as $item)
                                <li @class(['toc-sub' => $item['level'] === 3])><a href="#{{ $item['id'] }}">{{ $item['text'] }}</a></li>
                            @endforeach
                        </ol>
                    </nav>
                </aside>
            @endif
        </div>

        <div class="wrap wrap-narrow">
            @if ($article->tags->isNotEmpty())
                <ul class="mt-12 flex flex-wrap gap-2" aria-label="Tags">
                    @foreach ($article->tags as $tag)
                        <li><a href="{{ $tag->url() }}" rel="tag" class="chip">#{{ $tag->name }}</a></li>
                    @endforeach
                </ul>
            @endif

            <div @class(['flex flex-wrap items-center gap-2', 'mt-6' => $article->tags->isNotEmpty(), 'mt-12' => $article->tags->isEmpty()])>
                <p class="{{ $mono }} mr-2 text-muted">Share</p>
                <a href="https://twitter.com/intent/tweet?url={{ $encUrl }}&text={{ $encTitle }}" target="_blank" rel="noopener" class="{{ $shareBtn }}" aria-label="Share on X"><x-public.icon name="x" /></a>
                <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ $encUrl }}" target="_blank" rel="noopener" class="{{ $shareBtn }}" aria-label="Share on LinkedIn"><x-public.icon name="linkedin" /></a>
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ $encUrl }}" target="_blank" rel="noopener" class="{{ $shareBtn }}" aria-label="Share on Facebook"><x-public.icon name="facebook" /></a>
                <a href="mailto:?subject={{ rawurlencode($article->title) }}&body={{ $encUrl }}" class="{{ $shareBtn }}" aria-label="Share by email"><x-public.icon name="email" /></a>
                {{-- Needs the Clipboard API: shown by the inline script only. --}}
                <button type="button" class="js-copy-link {{ $mono }} inline-flex h-10 items-center gap-2 border border-line px-3 transition-colors hover:border-ink" data-url="{{ $url }}" hidden>
                    <x-public.icon name="link" /><span aria-live="polite">Copy link</span>
                </button>
            </div>

            @if ($article->author)
                <x-public.author-card :user="$article->author" class="mt-12" />
            @endif

            <aside class="ink-panel mt-12 bg-ink p-6 text-paper sm:p-8 md:p-10" aria-label="{{ $cta['eyebrow'] }} software">
                <p class="{{ $mono }} flex items-center gap-2 text-paper/70"><span class="size-2 bg-brand" aria-hidden="true"></span>{{ $cta['eyebrow'] }}</p>
                <p class="mt-4 text-2xl leading-tight font-extrabold tracking-tight text-balance md:text-3xl">{{ $cta['headline'] }}</p>
                @if (! empty($cta['text']))
                    <p class="mt-3 max-w-xl text-paper/80">{{ $cta['text'] }}</p>
                @endif
                <div class="mt-7 flex flex-wrap items-center gap-x-6 gap-y-4">
                    <a href="{{ $cta['primary']['url'] }}" class="inline-flex h-11 items-center bg-paper px-5 font-semibold text-ink transition-colors hover:bg-brand hover:text-brand-ink">{{ $cta['primary']['label'] }} &rarr;</a>
                    @if (! empty($cta['secondary']))
                        <a href="{{ $cta['secondary']['url'] }}" class="font-semibold underline decoration-paper/50 decoration-2 underline-offset-4 hover:decoration-paper">{{ $cta['secondary']['label'] }}</a>
                    @endif
                </div>
            </aside>

            @if ($newer || $older)
                <nav aria-label="More articles" class="mt-12 grid gap-3 sm:grid-cols-2">
                    @if ($older)
                        <a href="{{ route('blog.show', $older) }}" rel="prev" class="block border border-line p-5 transition-colors hover:border-ink">
                            <span class="{{ $mono }} block text-[0.6875rem] text-muted">&larr; Previous</span>
                            <span class="mt-2 block leading-snug font-bold">{{ $older->title }}</span>
                        </a>
                    @endif
                    @if ($newer)
                        <a href="{{ route('blog.show', $newer) }}" rel="next" class="block border border-line p-5 text-right transition-colors hover:border-ink sm:col-start-2">
                            <span class="{{ $mono }} block text-[0.6875rem] text-muted">Next &rarr;</span>
                            <span class="mt-2 block leading-snug font-bold">{{ $newer->title }}</span>
                        </a>
                    @endif
                </nav>
            @endif
        </div>
    </article>

    @if ($related->isNotEmpty())
        <section class="wrap mt-20 md:mt-24" aria-labelledby="related-heading">
            <h2 id="related-heading" class="{{ $mono }} border-t border-line pt-6 text-muted">Related articles</h2>
            <div class="mt-8 grid gap-x-8 gap-y-14 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($related as $item)
                    <x-public.article-card :article="$item" heading="h3" />
                @endforeach
            </div>
        </section>
    @endif

    <x-slot:scripts>
        {{-- One small enhancement script (no libraries): TOC highlight, code + link copy. --}}
        <script>
            (() => {
                const d = document;
                const flash = (el, text) => {
                    const label = el.querySelector('[aria-live]') || el;
                    label.dataset.text ??= label.textContent;
                    label.textContent = text;
                    clearTimeout(el._t);
                    el._t = setTimeout(() => { label.textContent = label.dataset.text; }, 1500);
                };
                d.querySelectorAll('.js-close-preview').forEach((b) => {
                    b.hidden = false;
                    b.addEventListener('click', () => window.close());
                });
                const copy = (el, text) => navigator.clipboard.writeText(text).then(() => flash(el, 'Copied'), () => flash(el, 'Copy failed'));

                if (navigator.clipboard && window.isSecureContext) {
                    d.querySelectorAll('.article-body .code-block').forEach((block) => {
                        const b = d.createElement('button');
                        b.type = 'button';
                        b.className = 'code-copy';
                        b.innerHTML = '<span aria-live="polite">Copy</span>';
                        b.addEventListener('click', () => copy(b, block.querySelector('code').textContent));
                        block.append(b);
                    });
                    d.querySelectorAll('.js-copy-link').forEach((b) => {
                        b.hidden = false;
                        b.addEventListener('click', () => copy(b, b.dataset.url));
                    });
                }

                const links = [...d.querySelectorAll('.toc-rail a')].filter((a) => d.getElementById(decodeURIComponent(a.hash.slice(1))));
                const heads = links.map((a) => d.getElementById(decodeURIComponent(a.hash.slice(1))));
                if (!heads.length || !('IntersectionObserver' in window)) return;
                // Active = last heading above the top quarter of the viewport. The observer only signals crossings.
                const update = () => {
                    const line = innerHeight * 0.25;
                    let active = -1;
                    heads.forEach((h, i) => { if (h.getBoundingClientRect().top <= line) active = i; });
                    links.forEach((a, i) => i === active ? a.setAttribute('aria-current', 'true') : a.removeAttribute('aria-current'));
                };
                const io = new IntersectionObserver(update, { rootMargin: '0px 0px -75% 0px' });
                heads.forEach((h) => io.observe(h));
            })();
        </script>
    </x-slot:scripts>
</x-public.layout>
