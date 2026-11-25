<!DOCTYPE html>
<html lang="fa" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title.' | ' : '' }}{{ config('app.name', 'GreenhouseHub') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=vazirmatn:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        {{-- Tailwind must see both widths: max-w-md max-w-2xl --}}
        <div class="flex min-h-screen flex-col items-center justify-center bg-gray-100 px-4 py-8">
            <a href="{{ route('home') }}" class="mb-6">
                <x-application-logo class="h-16 w-16 fill-current text-green-600" />
            </a>

            <div class="w-full {{ $maxWidth ?? 'max-w-md' }} rounded-xl bg-white p-6 shadow-lg sm:p-8">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
