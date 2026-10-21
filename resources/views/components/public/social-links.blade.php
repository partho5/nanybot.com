{{-- Author's profile links as icon pills. $links: label => https URL (User::socialProfiles()). --}}
@props(['links', 'name'])
@if ($links)
    <ul {{ $attributes->class(['flex flex-wrap gap-2']) }} aria-label="{{ $name }} elsewhere">
        @foreach ($links as $label => $url)
            <li>
                <a href="{{ $url }}" rel="me noopener" target="_blank" class="inline-flex min-h-10 items-center gap-2 border border-line bg-soft px-3 py-2 text-sm leading-none text-muted underline-offset-4 hover:border-ink hover:text-ink">
                    <x-public.icon :name="\App\Support\SocialNetworks::detect($url)" :size="16" />
                    <span>{{ $label }}</span>
                </a>
            </li>
        @endforeach
    </ul>
@endif
