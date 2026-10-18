<x-public.layout :seo="$seo">
    <section class="wrap py-14 md:py-20">
        <h1 class="max-w-3xl text-4xl font-extrabold leading-tight tracking-tight md:text-5xl">{{ config('blog.name') }}</h1>
        <p class="mt-4 max-w-2xl text-lg text-muted">{{ config('blog.tagline') }}</p>
        <p class="mt-8">
            <a href="{{ route('blog.index') }}" class="inline-block rounded-md bg-brand px-5 py-2.5 font-semibold text-brand-ink hover:opacity-90">Read the blog</a>
        </p>
    </section>

    @if ($latest->isNotEmpty())
        <section class="wrap pb-6" aria-labelledby="latest-heading">
            <div class="mb-6 flex items-baseline justify-between gap-4">
                <h2 id="latest-heading" class="text-2xl font-bold tracking-tight">Latest posts</h2>
                <a href="{{ route('blog.index') }}" class="text-sm font-medium text-brand hover:underline">All posts &rarr;</a>
            </div>
            <div class="grid gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($latest as $article)
                    <x-public.article-card :article="$article" />
                @endforeach
            </div>
        </section>
    @endif
</x-public.layout>
