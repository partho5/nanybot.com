@props(['paginator', 'pageUrl'])
@php
    $current = $paginator->currentPage();
    $last = $paginator->lastPage();
    $pages = collect([1, $last])
        ->merge(range(max(1, $current - 2), min($last, $current + 2)))
        ->unique()->sort()->values();
    $box = 'inline-flex h-10 min-w-10 items-center justify-center border px-3 font-mono text-xs font-medium uppercase tracking-wider';
@endphp
@if ($last > 1)
    <nav aria-label="Pagination" class="mt-16 flex flex-wrap items-center justify-center gap-2 border-t border-line pt-8">
        @if ($current > 1)
            <a href="{{ $pageUrl($current - 1) }}" rel="prev" class="{{ $box }} border-line hover:border-ink">&larr; Newer</a>
        @endif

        @foreach ($pages as $i => $n)
            @if ($i > 0 && $n - $pages[$i - 1] > 1)
                <span class="px-1 font-mono text-xs text-muted" aria-hidden="true">&hellip;</span>
            @endif
            @if ($n === $current)
                <span class="{{ $box }} border-ink bg-ink text-paper" aria-current="page"><span class="sr-only">Page </span>{{ $n }}</span>
            @else
                <a href="{{ $pageUrl($n) }}" class="{{ $box }} border-line hover:border-ink" aria-label="Page {{ $n }}">{{ $n }}</a>
            @endif
        @endforeach

        @if ($current < $last)
            <a href="{{ $pageUrl($current + 1) }}" rel="next" class="{{ $box }} border-line hover:border-ink">Older &rarr;</a>
        @endif
    </nav>
@endif
