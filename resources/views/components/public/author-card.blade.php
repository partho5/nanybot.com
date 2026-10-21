{{--
    Author card.
    full = false: "About the author" box after an article (soft header strip, bordered).
    full = true:  profile header of the author page; the author's name is the page H1.
    count: published articles (author page passes the paginator total; the article
    page eager-loads published_articles_count), omitted when unknown.
--}}
@props(['user', 'full' => false, 'count' => null, 'page' => 1])
@php
    $count ??= $user->published_articles_count ?? null;
    $mono = 'font-mono text-xs font-medium uppercase tracking-[0.08em]';
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
                @if ($countLabel)
                    <p class="{{ $mono }} mt-6 text-muted">{{ $countLabel }}</p>
                @endif
                <x-public.social-links :links="$socials" :name="$user->name" class="mt-5" />
            </div>
        </div>
    </header>
@else
    <aside {{ $attributes->class(['border border-line']) }} aria-labelledby="author-card-title">
        <h2 id="author-card-title" class="border-b border-line bg-soft px-5 py-3 text-base font-bold tracking-tight sm:px-6">About the author</h2>
        <div class="flex gap-4 p-5 sm:gap-5 sm:p-6">
            <x-public.avatar :user="$user" :size="72" class="ring-2 ring-line" />
            <div class="min-w-0 flex-1">
                <p class="text-lg leading-snug font-bold tracking-tight">
                    <a href="{{ $user->authorUrl() }}" rel="author" class="underline-offset-4 decoration-brand decoration-2 hover:underline">{{ $user->name }}</a>
                </p>
                @if ($user->headline)
                    <p class="mt-0.5 text-[0.9375rem] font-semibold text-muted">{{ $user->headline }}</p>
                @endif
                @if ($user->bio)
                    <p class="mt-3 text-[0.9375rem] leading-relaxed whitespace-pre-line text-muted">{{ $user->bio }}</p>
                @endif
                <x-public.social-links :links="$socials" :name="$user->name" class="mt-4" />
                <p class="{{ $mono }} mt-4 leading-normal">
                    <a href="{{ $user->authorUrl() }}" class="underline-offset-4 hover:underline">All articles by {{ $user->name }} &rarr;</a>
                    @if ($countLabel)<span class="whitespace-nowrap text-muted"><span aria-hidden="true" class="mx-1">&middot;</span>{{ $countLabel }}</span>@endif
                </p>
            </div>
        </div>
    </aside>
@endif
