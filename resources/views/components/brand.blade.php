@php($brandName = config('branding.name') ?: config('app.name', 'GreenhouseHub'))
<span {{ $attributes->class(['inline-flex items-center gap-2']) }}>
    @if (config('branding.logo'))
        <img src="{{ asset(config('branding.logo')) }}" alt="" class="h-9 w-auto object-contain">
    @endif
    <span>{{ $brandName }}</span>
</span>
