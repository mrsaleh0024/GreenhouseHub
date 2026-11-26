<div class="relative isolate flex-1 overflow-hidden">
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-10">
        <div class="absolute -top-40 left-1/2 h-[32rem] w-[64rem] -translate-x-1/2 rounded-full bg-emerald-500/10 blur-3xl"></div>
        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-emerald-400/40 to-transparent"></div>
    </div>

    <header class="mx-auto flex w-full max-w-5xl items-center justify-between px-6 pt-8">
        <div class="flex items-center gap-3">
            <span class="grid size-10 place-items-center rounded-xl bg-emerald-400/15 ring-1 ring-emerald-400/30 ring-inset">
                <svg class="size-5 text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                    <path d="M5 20c0-7 5-12 14-13-1 9-6 14-13 14" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M5 20c0-4 2-7 5-9" stroke-linecap="round" />
                </svg>
            </span>
            <div>
                <p class="text-sm font-semibold tracking-tight text-stone-50">GreenhouseHub</p>
                <p class="text-xs text-stone-400">Comprehensive greenhouse platform</p>
            </div>
        </div>

        <span class="hidden items-center gap-2 rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3 py-1 text-xs font-medium text-emerald-300 sm:inline-flex">
            <span class="size-1.5 rounded-full bg-emerald-400"></span>
            Foundations ready
        </span>
    </header>

    <main class="mx-auto w-full max-w-5xl px-6 pt-16 pb-24">
        <p class="text-xs font-medium tracking-[0.2em] text-emerald-400/80 uppercase">Stack installed</p>
        <h1 class="mt-4 max-w-2xl text-4xl font-semibold tracking-tight text-balance text-stone-50 sm:text-5xl">
            Laravel&nbsp;13, Livewire&nbsp;3, Alpine and Tailwind — wired and building.
        </h1>
        <p class="mt-4 max-w-xl text-base text-pretty text-stone-400">
            A clean foundation for managing greenhouses: houses, zones, crops, sensors and harvests.
            Everything below is rendered by the real stack, not a mockup.
        </p>

        <section aria-labelledby="stack-heading" class="mt-12">
            <h2 id="stack-heading" class="text-xs font-medium tracking-[0.2em] text-stone-500 uppercase">Installed stack</h2>
            <ul role="list" class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($this->stack() as $item)
                    <li class="rounded-xl border border-white/10 bg-white/[0.03] p-4 transition hover:border-emerald-400/30 hover:bg-emerald-400/[0.04]">
                        <div class="flex items-baseline justify-between gap-3">
                            <span class="text-sm font-medium text-stone-200">{{ $item['name'] }}</span>
                            <span class="font-mono text-xs text-emerald-300">{{ $item['version'] }}</span>
                        </div>
                        <p class="mt-1 text-xs text-stone-500">{{ $item['note'] }}</p>
                    </li>
                @endforeach
            </ul>
        </section>

        <section aria-labelledby="checks-heading" class="mt-6 grid gap-3 lg:grid-cols-2">
            <h2 id="checks-heading" class="sr-only">Live checks</h2>

            {{-- Livewire roundtrip --}}
            <div class="rounded-xl border border-white/10 bg-white/[0.03] p-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-medium text-stone-200">Livewire roundtrip</h3>
                        <p class="mt-1 text-xs text-stone-500">Every click is a real server request.</p>
                    </div>
                    <span class="rounded-md bg-emerald-400/10 px-2 py-0.5 font-mono text-[11px] text-emerald-300">wire:click</span>
                </div>

                <div class="mt-5 flex flex-wrap items-center gap-3">
                    <button
                        type="button"
                        wire:click="ping"
                        wire:loading.attr="disabled"
                        wire:target="ping"
                        class="inline-flex items-center gap-2 rounded-lg bg-emerald-400 px-3.5 py-2 text-sm font-semibold text-emerald-950 transition hover:bg-emerald-300 focus-visible:ring-2 focus-visible:ring-emerald-300 focus-visible:ring-offset-2 focus-visible:ring-offset-stone-950 focus-visible:outline-none disabled:cursor-progress disabled:opacity-60"
                    >
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M13 2 4 14h6l-1 8 9-12h-6l1-8Z" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        Ping the server
                    </button>

                    <p class="text-xs text-stone-400" aria-live="polite">
                        <span class="font-mono text-stone-200">{{ $pings }}</span> pings
                        @if ($lastPingAt)
                            · last at <span class="font-mono text-stone-200">{{ $lastPingAt }}</span>
                        @endif
                    </p>
                </div>
            </div>

            {{-- Alpine playground --}}
            <div
                class="rounded-xl border border-white/10 bg-white/[0.03] p-5"
                x-data="temperatureZone"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-medium text-stone-200">Alpine playground</h3>
                        <p class="mt-1 text-xs text-stone-500">Client-side state, no server involved.</p>
                    </div>
                    <button
                        type="button"
                        x-on:click="open = ! open"
                        x-bind:aria-expanded="open"
                        aria-controls="alpine-panel"
                        class="rounded-md text-xs font-medium text-emerald-300 underline-offset-4 hover:underline focus-visible:ring-2 focus-visible:ring-emerald-300 focus-visible:outline-none"
                    >
                        <span x-text="open ? 'Hide' : 'Show'">Hide</span>
                    </button>
                </div>

                <div id="alpine-panel" x-show="open" x-cloak x-transition.opacity class="mt-5">
                    <div class="flex items-center justify-between gap-3">
                        <label for="zone-temp" class="text-xs text-stone-400">Zone&nbsp;A temperature</label>
                        <span
                            class="rounded-md px-2 py-0.5 font-mono text-[11px]"
                            x-bind:class="badgeClass"
                            x-text="temp + ' °C'"
                        >22 °C</span>
                    </div>
                    <input
                        id="zone-temp"
                        type="range"
                        min="10"
                        max="34"
                        x-model.number="temp"
                        class="mt-3 w-full accent-emerald-400"
                    >
                    <p class="mt-2 text-xs text-stone-500" x-text="status.label"></p>
                </div>
            </div>
        </section>

        <footer class="mt-12 flex flex-col gap-3 border-t border-white/10 pt-6 text-xs text-stone-500 sm:flex-row sm:items-center sm:justify-between">
            <p>Built on Laravel {{ app()->version() }} · Livewire 3 · Alpine · Tailwind 4</p>
            <p class="font-mono">php artisan serve &nbsp;·&nbsp; npm run dev</p>
        </footer>
    </main>
</div>
