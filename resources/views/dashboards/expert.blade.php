<div>
    <x-app-layout><div class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
        <p class="text-sm font-semibold text-green-700">فضای کار تخصصی</p><h1 class="mt-3 text-3xl font-bold text-gray-900">بررسی فنی گلخانه‌ها</h1><p class="mt-3 max-w-2xl text-sm leading-7 text-gray-600">ساختار بخش‌ها، تجهیزات و سوابق کشت را برای بررسی فنی مطالعه کنید. اطلاعات شخصی مالکان در این نما نمایش داده نمی‌شود.</p>
        <div class="mt-8 grid gap-5 md:grid-cols-2">@forelse ($records as $record)
            <article class="rounded-2xl border border-gray-200 bg-white p-6"><p class="text-xs font-semibold text-green-700">{{ $record->greenhouse_code }}</p><h2 class="mt-3 text-xl font-bold">{{ $record->name }}</h2><dl class="mt-5 grid grid-cols-2 gap-4 text-sm"><div><dt class="text-gray-500">بخش‌های ثبت‌شده</dt><dd class="mt-2 font-bold">{{ $record->zones_count }}</dd></div><div><dt class="text-gray-500">دوره‌های کشت</dt><dd class="mt-2 font-bold">{{ $record->cycles_count }}</dd></div></dl><div class="mt-6 flex flex-wrap gap-5 text-sm font-semibold text-green-700"><a href="{{ route('greenhouses.zones', $record) }}" class="hover:underline">ساختار و تجهیزات ←</a><a href="{{ route('greenhouses.cultivation', $record) }}" class="hover:underline">مطالعه سوابق کشت ←</a></div></article>
        @empty<p class="text-sm text-gray-600">پرونده فنی برای نمایش وجود ندارد.</p>@endforelse</div><div class="mt-6">{{ $records->links() }}</div>
    </div></x-app-layout>
</div>
