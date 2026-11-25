<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-bold text-gray-800">داشبورد</h1>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-xl bg-white p-6 shadow-sm">
                <p class="text-lg font-semibold text-gray-800">
                    {{ auth()->user()->name }}، خوش آمدید 👋
                </p>
                <p class="mt-1 text-sm text-gray-500">
                    نقش شما: <span class="font-medium text-green-700">{{ auth()->user()->primaryRole()?->label() }}</span>
                </p>
            </div>
        </div>
    </div>
</x-app-layout>
