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

<div class="navigation-frame">
    <header class="workspace-topbar">
        <div class="topbar-leading"><button type="button" class="icon-button mobile-menu-trigger" @click="menuOpen = true" aria-label="باز کردن منو" :aria-expanded="menuOpen" aria-controls="workspace-sidebar"><x-line-icon name="menu" /></button><span class="topbar-context">فضای کار / <strong>{{ $roleLabel }}</strong></span></div>
        <div class="topbar-account"><span class="account-avatar">{{ mb_substr($userName, 0, 1) }}</span><span>{{ $userName }}</span></div>
    </header>
    <div x-show="menuOpen" x-cloak x-transition:enter="overlay-enter" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="overlay-leave" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="mobile-menu-backdrop" @click="menuOpen = false" aria-hidden="true"></div>
    <aside id="workspace-sidebar" class="workspace-sidebar" :class="{ 'is-open': menuOpen }" :inert="!desktop && !menuOpen" x-trap.inert.noscroll="menuOpen" @keydown.escape.window="menuOpen = false" aria-label="منوی اصلی">
        <div class="sidebar-brand"><a href="{{ route('dashboard') }}" wire:navigate aria-label="رویش، داشبورد"><x-brand /></a><button type="button" class="icon-button mobile-menu-trigger" @click="menuOpen = false" aria-label="بستن منو"><x-line-icon name="x" /></button><button type="button" class="icon-button sidebar-collapse" @click="collapsed = !collapsed" :aria-label="collapsed ? 'باز کردن نوار کناری' : 'جمع کردن نوار کناری'"><x-line-icon name="panel-right-close" /></button></div>
        <div class="sidebar-caption">میز کار</div>
        <nav class="sidebar-links">
            @foreach ($items as $item)
                @php($icon = match($item['route']) { 'dashboard' => 'layout-dashboard', 'admin.corrections' => 'clipboard-check', 'admin.catalog' => 'layers', 'admin.admins' => 'users', default => 'leaf' })
                <a href="{{ route($item['route']) }}" wire:navigate @click="menuOpen = false" @if(request()->routeIs($item['route'])) aria-current="page" @endif class="sidebar-link {{ request()->routeIs($item['route']) ? 'is-active' : '' }}" title="{{ $item['label'] }}"><x-line-icon :name="$icon" /><span>{{ $item['label'] }}</span><x-line-icon name="chevron-left" class="sidebar-link-chevron" /></a>
            @endforeach
        </nav>
        <div class="sidebar-bottom"><div class="sidebar-note"><x-line-icon name="sprout" /><p>هر دوره کشت،<br><strong>یک تجربه ارزشمند.</strong></p></div><button wire:click="logout" wire:loading.attr="disabled" class="sidebar-link sidebar-logout" title="خروج از حساب"><x-line-icon name="log-out" /><span>خروج از حساب</span></button></div>
    </aside>
</div>
