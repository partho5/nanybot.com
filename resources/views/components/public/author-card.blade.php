{{--
    Author card.
    full = false: "Written by" block after an article (top hairline, no filled box).
    full = true:  profile header of the author page; the author's name is the page H1.
    count: published articles (author page passes the paginator total; the article
    page eager-loads published_articles_count), omitted when unknown.
--}}
@props(['user', 'full' => false, 'count' => null, 'page' => 1])
@php
    $count ??= $user->published_articles_count ?? null;
    $mono = 'font-mono text-xs font-medium uppercase tracking-[0.08em]';
    $iconFor = function (string $url): string {
        $host = preg_replace('/^www\./', '', (string) parse_url($url, PHP_URL_HOST));

        return match (true) {
            in_array($host, ['x.com', 'twitter.com'], true) => 'x',
            str_ends_with($host, 'linkedin.com') => 'linkedin',
            str_ends_with($host, 'facebook.com') => 'facebook',
            str_ends_with($host, 'github.com') => 'github',
            str_ends_with($host, 'youtube.com') => 'youtube',
            default => 'globe',
        };
    };
    $socials = $user->socialProfiles();
    $countLabel = $count ? $count.' '.\Illuminate\Support\Str::plural('article', $count) : null;
@endphp

@if ($full)
    <header {{ $attributes->class(['border-b border-line pt-12 pb-10 md:pt-20 md:pb-14']) }}>
        <p class="{{ $mono }} text-muted">
            <a href="{{ route('blog.index') }}" class="underline-offset-4 hover:text-ink hover:underline">Blog</a>
            <span aria-hidden="true" class="mx-1">/</span>
            Author
        </p>
        <div class="mt-8 flex flex-col gap-6 sm:flex-row sm:items-start sm:gap-10">
            <x-public.avatar :user="$user" :size="112" :eager="true" />
            <div class="min-w-0">
                <h1 class="text-[clamp(2.5rem,6vw,4.5rem)] leading-[1.02] font-extrabold tracking-[-0.035em] text-balance break-words">
                    {{ $user->name }}@if ($page > 1)<span class="text-muted"> &mdash; Page {{ $page }}</span>@endif
                </h1>
                @if ($user->headline)
                    <p class="mt-4 text-xl text-muted md:text-2xl">{{ $user->headline }}</p>
                @endif
                @if ($user->bio)
                    <p class="mt-5 max-w-2xl text-lg leading-relaxed whitespace-pre-line">{{ $user->bio }}</p>
                @endif
                @if ($socials || $countLabel)
                    <div class="mt-6 flex flex-wrap items-center gap-x-6 gap-y-3">
                        @if ($countLabel)
                            <p class="{{ $mono }} text-muted">{{ $countLabel }}</p>
                        @endif
                        @if ($socials)
                            <ul class="-mx-2 flex flex-wrap" aria-label="{{ $user->name }} elsewhere">
                                @foreach ($socials as $network => $url)
                                    <li><a href="{{ $url }}" rel="me noopener" target="_blank" class="flex size-10 items-center justify-center text-muted hover:text-ink" aria-label="{{ ucfirst($network) }}"><x-public.icon :name="$iconFor($url)" :size="18" /></a></li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </header>
@else
    <aside {{ $attributes->class(['border-t border-line pt-6']) }} aria-labelledby="author-card-name">
        <p class="{{ $mono }} text-muted">Written by</p>
        <div class="mt-5 flex gap-4 sm:gap-5">
            <x-public.avatar :user="$user" :size="64" />
            <div class="min-w-0 flex-1">
                <p id="author-card-name" class="text-lg leading-snug font-bold tracking-tight">
                    <a href="{{ $user->authorUrl() }}" rel="author" class="underline-offset-4 decoration-brand decoration-2 hover:underline">{{ $user->name }}</a>
                </p>
                @if ($user->headline)
                    <p class="mt-0.5 text-sm text-muted">{{ $user->headline }}</p>
                @endif
                @if ($user->bio)
                    <p class="mt-3 line-clamp-3 text-[0.9375rem] leading-relaxed">{{ $user->bio }}</p>
                @endif
                <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-2">
                    <p class="{{ $mono }} py-2.5 leading-normal">
                        <a href="{{ $user->authorUrl() }}" class="underline-offset-4 hover:underline">All articles by {{ $user->name }} &rarr;</a>
                        @if ($countLabel)<span class="whitespace-nowrap text-muted"><span aria-hidden="true" class="mx-1">&middot;</span>{{ $countLabel }}</span>@endif
                    </p>
                    @if ($socials)
                        <ul class="-mx-2 flex flex-wrap" aria-label="{{ $user->name }} elsewhere">
                            @foreach ($socials as $network => $url)
                                <li><a href="{{ $url }}" rel="me noopener" target="_blank" class="flex size-10 items-center justify-center text-muted hover:text-ink" aria-label="{{ ucfirst($network) }}"><x-public.icon :name="$iconFor($url)" /></a></li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </aside>
@endif
