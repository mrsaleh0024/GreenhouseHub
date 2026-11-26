<!DOCTYPE html>
<html lang="fa" dir="rtl" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="flex min-h-full flex-col bg-gray-100 font-sans text-gray-800 antialiased">
        {{ $slot }}

        @livewireScripts
    </body>
</html>
