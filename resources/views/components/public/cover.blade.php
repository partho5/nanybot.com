{{-- Generated cover for articles without a featured image: pure HTML/CSS, same 16:10 box as a photo. Decorative (the card repeats the title). Sizes scale with the box (container query units). --}}
@props(['article'])
<div {{ $attributes->class(['@container relative aspect-[16/10] overflow-hidden border border-line bg-soft transition-colors duration-300 group-hover:border-ink']) }} aria-hidden="true">
    <div class="absolute inset-0 flex flex-col justify-between p-[max(1rem,6cqi)]">
        <div class="flex items-start justify-between gap-4">
            <span class="truncate font-mono text-[0.6875rem] font-medium uppercase leading-none tracking-[0.08em] text-muted">{{ $article->category?->name ?? config('blog.name') }}</span>
            <span class="size-2.5 shrink-0 bg-brand"></span>
        </div>
        <p class="line-clamp-3 text-[clamp(1.25rem,7.5cqi,3.25rem)] font-extrabold leading-[1.05] tracking-[-0.025em] text-ink">{{ $article->title }}</p>
    </div>
</div>
