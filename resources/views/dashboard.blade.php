<x-app-layout>
    <div class="mx-auto max-w-6xl px-4 pb-16 pt-8 sm:px-6 sm:pt-12 lg:px-8">
        <div class="flex flex-wrap items-end justify-between gap-5">
            <div>
                <p class="flex items-center gap-2 text-sm font-semibold text-green-700"><x-line-icon name="greenhouse" /> فضای کار شما</p>
                <h1 class="mt-2 text-2xl font-bold leading-10 text-gray-900 sm:text-3xl">{{ auth()->user()->name }}، خوش آمدید</h1>
                <p class="mt-2 text-sm leading-7 text-gray-600">@if ($isOwner) اطلاعات گلخانه‌تان را از همین‌جا دنبال کنید. @elseif ($isExpert) اطلاعات فنی گلخانه‌ها را در این بخش دنبال کنید. @else وضعیت پرونده‌های گلخانه را اینجا دنبال کنید. @endif</p>
            </div>
            <span class="rounded-full border border-gray-200 bg-white px-4 py-2 text-xs font-semibold text-green-700">{{ auth()->user()->primaryRole()?->label() }}</span>
        </div>

        @if ($isOwner && $records->isNotEmpty())<x-owner-prompts :greenhouse="$records->first()" />@endif

        <section aria-labelledby="records-heading" class="mt-10">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold text-green-700">پرونده دیجیتال · {{ $records->total() }} پرونده</p>
                    <h2 id="records-heading" class="mt-2 text-xl font-bold text-gray-900">{{ $isOwner ? 'گلخانه‌های من' : 'گلخانه‌ها' }}</h2>
                </div>
                @if ($isOwner)
                    <a wire:navigate href="{{ route('greenhouses.create') }}" class="inline-flex min-h-11 shrink-0 items-center gap-2 rounded-xl border border-green-700/20 bg-white px-4 py-2.5 text-sm font-semibold text-green-700 hover:border-green-700/40 hover:bg-green-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-green-700">
                        <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M12 5v14M5 12h14" /></svg>
                        افزودن گلخانه
                    </a>
                @endif
            </div>

            @forelse ($records as $record)
                <article class="mt-4 rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
                    <div class="flex flex-wrap items-start justify-between gap-4"><div><p dir="ltr" class="text-right text-xs font-semibold tracking-wide text-green-700">{{ $record->greenhouse_code }}</p><h3 class="mt-2 text-lg font-bold text-gray-900">{{ $record->name }}</h3><p class="mt-1 text-sm text-gray-600">@if ($record->area_m2) {{ number_format((float) $record->area_m2, 0) }} متر مربع @else مساحت هنوز ثبت نشده است @endif</p></div><span class="rounded-full bg-green-50 px-3 py-1.5 text-xs font-semibold text-green-700">{{ $record->status === 'active' ? 'فعال' : ($record->status === 'under_construction' ? 'در حال ساخت' : 'غیرفعال') }}</span></div>
                    <div class="mt-4 flex flex-wrap gap-x-6 gap-y-3"><a wire:navigate href="{{ route('greenhouses.zones', $record) }}" class="text-sm font-semibold text-green-700 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-green-700">بخش‌ها و تجهیزات ←</a><a wire:navigate href="{{ route('greenhouses.cultivation', $record) }}" class="text-sm font-semibold text-green-700 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-green-700">کشت‌ها و سوابق ←</a></div>
                    @if (! $isExpert)
                        @php($action = $nextActions[$record->id])
                        <div class="mt-6 flex flex-wrap items-end justify-between gap-5 border-t border-gray-200 pt-5"><div class="w-full max-w-sm"><div class="flex justify-between gap-4 text-xs font-semibold text-gray-700"><span>تکمیل پرونده</span><span>{{ $record->completeness_percent }}٪</span></div><progress class="record-progress mt-3 w-full" max="100" value="{{ $record->completeness_percent }}" aria-label="میزان تکمیل پرونده {{ $record->name }}">{{ $record->completeness_percent }}%</progress><p class="mt-2 text-xs leading-6 text-gray-500">{{ $action['description'] }}</p></div><a wire:navigate href="{{ route($action['route'], array_filter(['greenhouse' => $record->id, 'step' => $action['step']], fn ($value) => $value !== null)) }}" class="inline-flex items-center gap-2 text-sm font-bold text-green-700 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-green-700">{{ $action['label'] }} <span aria-hidden="true">←</span></a></div>
                    @else
                        <p class="mt-5 border-t border-gray-200 pt-4 text-xs leading-6 text-gray-500">دسترسی شما به اطلاعات این گلخانه فقط برای مشاهده است.</p>
                    @endif
                <x-zone-overview :greenhouse="$record" mode="owner" />
                <livewire:greenhouse.record-removal :greenhouse-id="$record->id" :key="'remove-'.$record->id" /></article>
            @empty
                <div class="mt-4 rounded-2xl border border-gray-200 bg-white px-6 py-10"><div class="grid h-11 w-11 place-items-center rounded-xl bg-green-50 text-green-700" aria-hidden="true"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 11 12 3l9 8v9H3v-9Zm4 0v9m10-9v9M3 14h18" stroke-linecap="round" stroke-linejoin="round"/></svg></div><h3 class="mt-5 text-base font-bold text-gray-900">هنوز پرونده‌ای در دسترس نیست</h3><p class="mt-2 max-w-lg text-sm leading-7 text-gray-600">@if ($isOwner) اگر تازه حساب ساخته‌اید، پس از تأیید شماره موبایل پرونده شما به حسابتان متصل می‌شود. اگر همچنان آن را نمی‌بینید با پشتیبانی تماس بگیرید. @else در حال حاضر گلخانه‌ای برای نمایش وجود ندارد. @endif</p></div>
            @endforelse
            <div class="mt-6">{{ $records->links() }}</div>
        </section>
    </div>
</x-app-layout>
