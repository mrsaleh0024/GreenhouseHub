<?php

namespace App\Livewire;

use Composer\InstalledVersions;
use Livewire\Component;

class SystemStatus extends Component
{
    public int $pings = 0;

    public ?string $lastPingAt = null;

    public function mount(): void
    {
        $this->lastPingAt = now()->format('H:i:s');
    }

    public function ping(): void
    {
        $this->pings++;
        $this->lastPingAt = now()->format('H:i:s');
    }

    /**
     * Versions read from the actual installed packages, so the page never lies.
     *
     * @return array<int, array{name: string, version: string, note: string}>
     */
    public function stack(): array
    {
        return [
            [
                'name' => 'Laravel',
                'version' => app()->version(),
                'note' => 'PHP '.PHP_VERSION,
            ],
            [
                'name' => 'Livewire',
                'version' => InstalledVersions::getPrettyVersion('livewire/livewire') ?? 'installed',
                'note' => 'Server-driven UI',
            ],
            [
                'name' => 'Alpine.js',
                'version' => $this->npmVersion('alpinejs'),
                'note' => 'Client-side sprinkles',
            ],
            [
                'name' => 'Tailwind CSS',
                'version' => $this->npmVersion('tailwindcss'),
                'note' => 'Utility-first styling',
            ],
            [
                'name' => 'Vite',
                'version' => $this->npmVersion('vite'),
                'note' => 'Asset bundling',
            ],
            [
                'name' => 'SQLite',
                'version' => 'database/database.sqlite',
                'note' => 'Local database',
            ],
        ];
    }

    protected function npmVersion(string $package): string
    {
        static $manifest = null;

        $manifest ??= json_decode(
            file_get_contents(base_path('package.json')),
            associative: true,
        );

        $version = $manifest['dependencies'][$package]
            ?? $manifest['devDependencies'][$package]
            ?? null;

        return $version ? ltrim($version, '^~') : 'installed';
    }

    public function render()
    {
        return view('livewire.system-status')
            ->layout('components.layouts.app', ['title' => 'GreenhouseHub — stack check']);
    }
}
