<x-public.layout :seo="$seo">
    <div class="wrap wrap-narrow py-8 md:py-12">
        <h1 class="text-3xl font-extrabold tracking-tight">Search</h1>

        <form action="{{ route('blog.search') }}" method="get" role="search" class="mt-6 flex gap-2">
            <label for="q" class="sr-only">Search articles</label>
            <input id="q" type="search" name="q" value="{{ $q }}" minlength="2" required autofocus class="min-w-0 flex-1 rounded-md border border-line bg-paper px-3 py-2">
            <button type="submit" class="rounded-md bg-brand px-4 py-2 font-semibold text-brand-ink hover:opacity-90">Search</button>
        </form>

        @if ($q !== '')
            <p class="mt-6 text-sm text-muted" role="status">
                @if (mb_strlen($q) < 2)
                    Type at least 2 characters.
                @else
                    {{ $articles->count() }} {{ \Illuminate\Support\Str::plural('result', $articles->count()) }} for &ldquo;{{ $q }}&rdquo;
                @endif
            </p>

            @if ($articles->isNotEmpty())
                <div class="mt-6 grid gap-8">
                    @foreach ($articles as $article)
                        <x-public.article-card :article="$article" />
                    @endforeach
                </div>
            @elseif (mb_strlen($q) >= 2)
                <p class="mt-4">Nothing found. Try different words, or <a href="{{ route('blog.index') }}" class="text-brand underline">browse all articles</a>.</p>
            @endif
        @endif
    </div>
</x-public.layout>
