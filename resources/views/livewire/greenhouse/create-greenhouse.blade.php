<div class="record-workspace mx-auto max-w-3xl px-4 py-10">
    <a wire:navigate href="{{ route('dashboard') }}" class="text-green-700">→ بازگشت به گلخانه‌های من</a>
    <section class="mt-7 rounded-2xl border border-gray-200 bg-white p-6 sm:p-8">
        <h1 class="text-2xl font-bold">افزودن گلخانه</h1>
        <p class="mt-3 text-sm leading-7 text-gray-600">گلخانه جدید به همین حساب متصل می‌شود. اطلاعات فنی را می‌توانید بعداً تکمیل کنید.</p>
        <form wire:submit="save" class="mt-7 grid gap-5">
            @foreach (['name' => 'نام گلخانه', 'city' => 'شهر'] as $field => $label)
                <div><label for="new-{{ $field }}">{{ $label }} *</label><input id="new-{{ $field }}" wire:model="{{ $field }}" required maxlength="{{ $field === 'name' ? 120 : 80 }}" class="mt-2 w-full rounded-xl border-gray-300">@error($field)<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror</div>
            @endforeach
            <fieldset x-data="catalogFilter" x-on:change="revision++"><legend>استان *</legend><input type="search" x-model.debounce.100ms="query" aria-label="جست‌وجوی استان" placeholder="نام استان را جست‌وجو کنید" class="mt-3 w-full rounded-xl border-gray-300"><div class="mt-3 grid gap-3 sm:grid-cols-2">@foreach ($provinces as $province)<label data-catalog-option x-show="matches($el.textContent, $el)" class="flex items-center gap-3"><input type="radio" wire:model="province_id" value="{{ $province->id }}">{{ $province->label_fa }}</label>@endforeach</div><button type="button" x-on:click="expanded = !expanded" class="catalog-expand">نمایش همه استان‌ها / پیشنهادهای کوتاه</button>@error('province_id')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror</fieldset>
            <button wire:loading.attr="disabled" class="rounded-xl bg-green-700 px-5 py-3 font-semibold text-white disabled:opacity-60">ثبت گلخانه و ادامه</button>
        </form>
    </section>
</div>
