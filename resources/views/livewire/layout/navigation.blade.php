<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Component;

new class extends Component
{
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect(route('login'), navigate: false);
    }

    public function with(): array
    {
        $user = auth()->user();

        return [
            'items' => collect(config('navigation.items', []))
                ->filter(fn (array $item) => Route::has($item['route'])
                    && (empty($item['roles']) || $user->hasAnyRole($item['roles'])))
                ->values(),
            'userName' => $user->name,
            'roleLabel' => $user->primaryRole()?->label(),
        ];
    }
}; ?>

<nav x-data="{ open: false }" class="border-b border-gray-200 bg-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
            <div class="flex items-center gap-8">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2 font-bold text-green-700">
                    <x-application-logo class="h-8 w-8 fill-current text-green-600" />
                    <span>{{ config('app.name', 'GreenhouseHub') }}</span>
                </a>

                <div class="hidden items-center gap-6 sm:flex">
                    @foreach ($items as $item)
                        <a href="{{ route($item['route']) }}" wire:navigate
                           class="border-b-2 py-5 text-sm font-medium {{ request()->routeIs($item['route']) ? 'border-green-600 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="hidden items-center gap-4 sm:flex">
                <div class="text-end leading-tight">
                    <div class="text-sm font-medium text-gray-800">{{ $userName }}</div>
                    @if ($roleLabel)
                        <div class="text-xs text-green-700">{{ $roleLabel }}</div>
                    @endif
                </div>
                <button wire:click="logout" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50">
                    خروج
                </button>
            </div>

            <button @click="open = ! open" class="rounded-md p-2 text-gray-500 hover:bg-gray-100 sm:hidden" aria-label="منو">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>
    </div>

    <div x-show="open" x-cloak class="border-t border-gray-200 sm:hidden">
        <div class="space-y-1 px-4 py-3">
            @foreach ($items as $item)
                <a href="{{ route($item['route']) }}" wire:navigate class="block rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>
        <div class="border-t border-gray-200 px-4 py-3">
            <div class="text-sm font-medium text-gray-800">{{ $userName }}</div>
            @if ($roleLabel)
                <div class="text-xs text-green-700">{{ $roleLabel }}</div>
            @endif
            <button wire:click="logout" class="mt-3 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
                خروج
            </button>
        </div>
    </div>
</nav>
