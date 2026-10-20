{{-- Round author avatar (Cloudinary, 2x for retina); fallback = initial on ink. Decorative: the name is always next to it. --}}
@props(['user', 'size' => 40, 'eager' => false])
@php
    $px = (int) $size * 2;
    $src = $user->avatar
        ? app(\App\Services\CloudinaryService::class)->transform($user->avatar, "c_fill,w_{$px},h_{$px},g_face")
        : null;
    $box = 'shrink-0 rounded-full';
@endphp
@if ($src)
    <img src="{{ $src }}" width="{{ $size }}" height="{{ $size }}" alt="" @if (! $eager) loading="lazy" @endif decoding="async"
        {{ $attributes->class([$box, 'bg-soft object-cover']) }} style="width: {{ $size }}px; height: {{ $size }}px">
@else
    <span {{ $attributes->class([$box, 'flex items-center justify-center bg-ink font-bold text-paper']) }}
        style="width: {{ $size }}px; height: {{ $size }}px; font-size: {{ round($size * 0.42) }}px" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
@endif
