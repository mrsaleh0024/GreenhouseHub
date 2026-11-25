@props(['label', 'name', 'type' => 'text', 'hint' => null, 'ltr' => false])

<div>
    <label for="{{ $name }}" class="mb-1 block text-sm font-medium text-gray-700">{{ $label }}</label>

    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        {{ $attributes->class([
            'block w-full rounded-lg border-gray-300 px-4 py-2.5 shadow-sm focus:border-green-600 focus:ring-green-600',
            'text-left' => $ltr,
        ]) }}
        @if ($ltr) dir="ltr" @endif
    >

    @if ($hint)
        <p class="mt-1 text-xs text-gray-500">{{ $hint }}</p>
    @endif

    @error($name)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
