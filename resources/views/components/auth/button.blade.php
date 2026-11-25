@props(['target' => null])

<button
    {{ $attributes->merge(['type' => 'submit'])->class([
        'flex w-full items-center justify-center rounded-lg bg-green-600 px-4 py-2.5 text-sm font-semibold text-white',
        'transition hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-600 focus:ring-offset-2',
        'disabled:cursor-not-allowed disabled:opacity-60',
    ]) }}
    wire:loading.attr="disabled"
    @if ($target) wire:target="{{ $target }}" @endif
>
    {{ $slot }}
</button>
