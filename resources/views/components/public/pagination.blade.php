@props(['paginator', 'pageUrl'])
@php
    $current = $paginator->currentPage();
    $last = $paginator->lastPage();
    $pages = collect([1, $last])
        ->merge(range(max(1, $current - 2), min($last, $current + 2)))
        ->unique()->sort()->values();
@endphp
@if ($last > 1)
    <nav aria-label="Pagination" class="mt-12 flex flex-wrap items-center justify-center gap-1 text-sm">
        @if ($current > 1)
            <a href="{{ $pageUrl($current - 1) }}" rel="prev" class="chip">&larr; Newer</a>
        @endif

        @foreach ($pages as $i => $n)
            @if ($i > 0 && $n - $pages[$i - 1] > 1)
                <span class="px-1 text-muted" aria-hidden="true">&hellip;</span>
            @endif
            @if ($n === $current)
                <span class="chip border-brand text-brand" aria-current="page">{{ $n }}</span>
            @else
                <a href="{{ $pageUrl($n) }}" class="chip" aria-label="Page {{ $n }}">{{ $n }}</a>
            @endif
        @endforeach

        @if ($current < $last)
            <a href="{{ $pageUrl($current + 1) }}" rel="next" class="chip">Older &rarr;</a>
        @endif
    </nav>
@endif
