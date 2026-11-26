<!DOCTYPE html>
<html lang="fa" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title.' | ' : '' }}{{ config('app.name', 'GreenhouseHub') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        {{-- Tailwind must see both widths: max-w-md max-w-2xl --}}
        <div class="auth-shell flex min-h-screen flex-col items-center justify-center px-4 py-8">
            <a href="{{ route('home') }}" class="mb-6 text-xl font-bold text-green-700">
                <x-brand />
            </a>

            <div class="auth-card w-full {{ $maxWidth ?? 'max-w-md' }} rounded-xl bg-white p-6 sm:p-8">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
