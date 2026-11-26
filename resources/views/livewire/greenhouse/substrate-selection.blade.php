<details class="mt-7 rounded-xl bg-gray-50 p-4" @if ($substrate_id !== '' || $harvest_started_on !== '' || $errors->has('substrateDetails.*') || $errors->has('substrate_id')) open @endif>
    <summary class="cursor-pointer text-sm font-semibold text-green-700">بستر و شرایط ریشه <span class="font-normal text-gray-600">(برای بررسی دقیق‌تر)</span></summary>
    <fieldset x-data="catalogFilter" x-on:change="revision++" class="mt-5">
        <legend class="text-sm font-semibold text-gray-800">بستر کشت</legend>
        <input type="search" x-model.debounce.100ms="query" aria-label="جست‌وجو در بسترها" placeholder="جست‌وجو در بسترها…" class="mt-4 w-full rounded-xl border-gray-300 text-sm focus:border-green-600 focus:ring-green-600"><p class="catalog-hint">چند پیشنهاد برای شروع؛ نام گزینه را جست‌وجو کنید.</p><p x-cloak x-show="query.trim() && !hasResults" role="status" class="catalog-hint">گزینه‌ای پیدا نشد. نام کوتاه‌تری بنویسید یا گزینه جدید ثبت کنید.</p><button type="button" x-show="!query.trim()" x-on:click="expanded = !expanded" class="catalog-expand" x-text="expanded ? 'نمایش پیشنهادهای کوتاه' : 'دیدن همه گزینه‌ها'"></button>
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            @foreach ($catalog->get('substrate', collect()) as $item)
                <label data-catalog-option x-show="matches($el.textContent, $el)" class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white px-3 py-3 text-sm text-gray-700"><input type="radio" wire:model.live="substrate_id" value="{{ $item->id }}" class="border-gray-300 text-green-600 focus:ring-green-600">{{ $item->label_fa }} @if ($item->status === 'pending')<small>(در انتظار بررسی)</small>@endif</label>
            @endforeach
        </div>
        <div class="mt-5 flex flex-wrap items-end gap-3"><div class="min-w-0 flex-1"><label for="custom-substrate" class="block text-xs font-semibold text-gray-700">بستر دیگری دارید؟</label><input id="custom-substrate" wire:model="customLabels.substrate" maxlength="150" class="mt-2 w-full rounded-lg border-gray-300 px-3 py-2 text-sm focus:border-green-600 focus:ring-green-600">@error('customLabels.substrate')<p role="alert" class="mt-2 text-xs text-red-700">{{ $message }}</p>@enderror</div><button type="button" wire:click="addCustom('substrate')" wire:loading.attr="disabled" class="rounded-lg border border-green-600 px-4 py-2 text-sm font-semibold text-green-700 hover:bg-white disabled:opacity-60">افزودن بستر</button></div>
        @error('substrate_id')<p role="alert" class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
    </fieldset>
    @include('livewire.greenhouse.substrate-fields')
    <div class="mt-6"><livewire:shared.jalali-picker wire:model="harvest_started_on" label="تاریخ شروع برداشت" :key="'harvest-start-'.($editingId ?? 'new')" />@error('harvest_started_on')<p role="alert" class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror</div>
</details>
