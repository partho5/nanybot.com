{{-- Small inline SVG icons for share + social links (brand glyphs come from App\Support\SocialNetworks). Decorative: the parent link/button carries the accessible name. --}}
@props(['name', 'size' => 16])
@php
    $glyph = \App\Support\SocialNetworks::path($name);
    $stroked = [
        'email' => '<rect x="3" y="5" width="18" height="14"/><path d="m3 6 9 7 9-7"/>',
        'link' => '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>',
        'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>',
    ];
@endphp
@if ($glyph)
    <svg {{ $attributes }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="{{ $glyph }}"/></svg>
@else
    <svg {{ $attributes }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="square" aria-hidden="true" focusable="false">{!! $stroked[$name] ?? $stroked['globe'] !!}</svg>
@endif
