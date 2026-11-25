@props(['type' => 'success'])

<div {{ $attributes->class([
    'mb-4 rounded-lg px-4 py-3 text-sm',
    'bg-green-50 text-green-800' => $type === 'success',
    'bg-red-50 text-red-700' => $type === 'error',
    'bg-blue-50 text-blue-800' => $type === 'info',
]) }} role="alert">
    {{ $slot }}
</div>
