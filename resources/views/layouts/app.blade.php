<!DOCTYPE html>
<html lang="fa" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title.' | ' : '' }}{{ config('app.name', 'GreenhouseHub') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div x-data="{ menuOpen: false, collapsed: false, desktop: window.innerWidth >= 1024 }" x-on:resize.window="desktop = window.innerWidth >= 1024; if (desktop) menuOpen = false" x-on:livewire:navigating.window="menuOpen = false" :class="{ 'sidebar-is-collapsed': collapsed }" class="workspace-shell min-h-screen">
            <livewire:layout.navigation />

            @isset($header)
                <header class="bg-white shadow">
                    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main id="main-content" class="workspace-main">
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
