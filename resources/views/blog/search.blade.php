<x-public.layout :seo="$seo">
    <div class="wrap wrap-narrow py-12 md:py-20">
        <p class="font-mono text-xs uppercase tracking-wider text-muted">Blog</p>
        <h1 class="mt-3 text-4xl font-extrabold tracking-tight md:text-5xl">Search</h1>

        <form action="{{ route('blog.search') }}" method="get" role="search" class="mt-8 flex">
            <label for="q" class="sr-only">Search articles</label>
            <input id="q" type="search" name="q" value="{{ $q }}" minlength="2" required autofocus placeholder="What do you want to automate?" class="h-12 min-w-0 flex-1 border border-r-0 border-ink bg-transparent px-3.5 text-base placeholder:text-muted">
            <button type="submit" class="h-12 shrink-0 border border-ink bg-ink px-5 font-mono text-xs font-medium uppercase tracking-wider text-paper hover:bg-brand hover:border-brand hover:text-brand-ink">Search</button>
        </form>

        @if ($q !== '')
            <p class="mt-8 border-b border-line pb-3 font-mono text-xs uppercase tracking-wider text-muted" role="status">
                @if (mb_strlen($q) < 2)
                    Type at least 2 characters.
                @else
                    {{ $articles->count() }} {{ \Illuminate\Support\Str::plural('result', $articles->count()) }} for &ldquo;{{ $q }}&rdquo;
                @endif
            </p>

            @if ($articles->isNotEmpty())
                <div class="mt-8 grid gap-10">
                    @foreach ($articles as $article)
                        <x-public.article-card :article="$article" sizes="(min-width: 768px) 720px, 100vw" />
                    @endforeach
                </div>
            @elseif (mb_strlen($q) >= 2)
                <p class="mt-6">Nothing found. Try different words, or <a href="{{ route('blog.index') }}" class="underline decoration-brand underline-offset-4 hover:text-brand">browse all articles</a>.</p>
            @endif
        @endif
    </div>
</x-public.layout>
