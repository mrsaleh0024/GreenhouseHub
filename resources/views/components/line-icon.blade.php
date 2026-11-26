@props(['name' => 'leaf'])
<svg {{ $attributes->class(['h-5 w-5']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('greenhouse')<path d="m3 11 9-8 9 8v10H3V11Zm5 10V11h8v10M3 11h18"/>@break
        @case('clipboard')<rect x="5" y="5" width="14" height="16" rx="2"/><path d="M9 5V3h6v2M9 11h6m-6 5h6"/>@break
        @case('layers')<path d="m12 3 10 6-10 6L2 9l10-6Zm-10 12 10 6 10-6M2 12l10 6 10-6"/>@break
        @default<path d="M20 4C9 2 3 6 4 13c1 7 13 9 16-9ZM4 21 15 10"/>
    @endswitch
</svg>
