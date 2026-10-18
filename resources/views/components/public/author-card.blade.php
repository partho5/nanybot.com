@props(['user', 'full' => false])
@php
    $avatar = $user->avatar
        ? app(\App\Services\CloudinaryService::class)->transform($user->avatar, 'c_fill,w_128,h_128,g_face')
        : null;
    $socials = $user->socialProfiles();
@endphp
<aside {{ $attributes->class(['flex gap-4 rounded-lg border border-line bg-soft p-4']) }} aria-label="About {{ $user->name }}">
    @if ($avatar)
        <img src="{{ $avatar }}" width="64" height="64" alt="{{ $user->name }}" loading="lazy" decoding="async" class="size-16 shrink-0 rounded-full object-cover">
    @else
        <span class="flex size-16 shrink-0 items-center justify-center rounded-full bg-brand text-xl font-bold text-brand-ink" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
    @endif

    <div class="min-w-0">
        <p class="font-semibold">
            @if ($full)
                {{ $user->name }}
            @else
                <a href="{{ $user->authorUrl() }}" class="hover:text-brand">{{ $user->name }}</a>
            @endif
        </p>
        @if ($user->headline)
            <p class="text-sm text-muted">{{ $user->headline }}</p>
        @endif
        @if ($user->bio)
            <p class="mt-2 text-sm">{{ $full ? $user->bio : \Illuminate\Support\Str::limit($user->bio, 240) }}</p>
        @endif
        @if ($socials)
            <ul class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-sm">
                @foreach ($socials as $network => $url)
                    <li><a href="{{ $url }}" rel="me noopener" target="_blank" class="text-brand hover:underline">{{ ucfirst($network) }}</a></li>
                @endforeach
            </ul>
        @endif
    </div>
</aside>
