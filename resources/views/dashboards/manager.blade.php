<div>
    <x-app-layout><div class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
        <p class="flex items-center gap-2 text-sm font-semibold text-green-700"><x-line-icon name="clipboard" /> میز مدیریت</p><h1 class="mt-3 text-3xl font-bold text-gray-900">رسیدگی به پرونده‌ها</h1>
        <p class="mt-3 text-sm leading-7 text-gray-600">کیفیت اطلاعات و درخواست‌های اصلاح را مدیریت کنید. ثبت کشت جدید از فضای گلخانه‌دار انجام می‌شود.</p>
        <div class="mt-8 grid gap-4 sm:grid-cols-2">
            <a wire:navigate href="{{ route('admin.corrections') }}" class="rounded-2xl border border-gray-200 bg-white p-6 hover:bg-green-50"><strong class="text-3xl text-green-700">{{ $pendingCorrections }}</strong><h2 class="mt-3 font-bold">درخواست اصلاح در انتظار رسیدگی</h2><p class="mt-2 text-sm text-gray-600">بررسی درخواست و ثبت پاسخ برای مالک <x-line-icon name="arrow-left" class="action-arrow" /></p></a>
            <a wire:navigate href="{{ route('admin.catalog') }}" class="rounded-2xl border border-gray-200 bg-white p-6 hover:bg-green-50"><strong class="text-3xl text-green-700">{{ $pendingCatalog }}</strong><h2 class="mt-3 font-bold">گزینه تخصصی در انتظار بررسی</h2><p class="mt-2 text-sm text-gray-600">تأیید و ادغام گزینه‌های پیشنهادی <x-line-icon name="arrow-left" class="action-arrow" /></p></a>
        </div><h2 class="mt-10 text-xl font-bold">پرونده‌های تحت مدیریت</h2>
        @forelse ($records as $record)
            <article class="mt-4 flex flex-wrap items-center justify-between gap-5 rounded-2xl border border-gray-200 bg-white p-6"><div><p class="text-xs text-green-700">{{ $record->greenhouse_code }}</p><h3 class="mt-2 font-bold">{{ $record->name }}</h3><p class="mt-2 text-xs text-gray-500">تکمیل اطلاعات: {{ $record->completeness_percent }}٪</p></div><div class="flex flex-wrap gap-5 text-sm font-semibold text-green-700"><a wire:navigate href="{{ route('greenhouses.complete', $record) }}" class="hover:underline">بررسی و اصلاح پرونده</a><a wire:navigate href="{{ route('greenhouses.cultivation', $record) }}" class="hover:underline">بررسی سوابق کشت</a><a wire:navigate href="{{ route('greenhouses.zones', $record) }}" class="hover:underline">بخش‌ها و تجهیزات</a></div><x-zone-overview :greenhouse="$record" mode="manager" /></article>
        @empty<p class="mt-5 text-gray-600">هنوز پرونده‌ای ثبت نشده است.</p>@endforelse
        <div class="mt-6">{{ $records->links() }}</div>
        @if ($archivedRecords->isNotEmpty())
            <section class="mt-10 rounded-2xl border border-gray-200 bg-white p-6"><h2 class="text-xl font-bold">گلخانه‌های حذف‌شده</h2><p class="mt-2 text-sm text-gray-600">بازیابی، پرونده و همه اطلاعات وابسته را دوباره در دسترس مالک قرار می‌دهد.</p>
                @foreach ($archivedRecords as $archived)<div class="mt-5 flex items-center justify-between gap-4"><span>{{ $archived->name }} · {{ $archived->greenhouse_code }}</span><form method="POST" action="{{ route('greenhouses.restore', $archived->id) }}">@csrf<button class="rounded-xl border border-green-700 px-4 py-2 text-sm text-green-700">بازیابی پرونده</button></form></div>@endforeach
                {{ $archivedRecords->links() }}
            </section>
        @endif
    </div></x-app-layout>
</div>
