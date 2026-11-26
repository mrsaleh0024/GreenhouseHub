<div class="record-workspace mx-auto max-w-3xl px-4 py-10">
    <a wire:navigate href="{{ route('dashboard') }}" class="text-green-700"><x-line-icon name="arrow-left" class="action-arrow action-arrow-back" /> بازگشت به گلخانه‌های من</a>
    <section class="mt-7 rounded-2xl border border-gray-200 bg-white p-6 sm:p-8">
        <h1 class="text-2xl font-bold">افزودن گلخانه</h1>
        <p class="mt-3 text-sm leading-7 text-gray-600">گلخانه جدید به همین حساب متصل می‌شود. اطلاعات فنی را می‌توانید بعداً تکمیل کنید.</p>
        <form wire:submit="save" class="mt-7 grid gap-5">
            @foreach (['name' => 'نام گلخانه'] as $field => $label)
                <div><label for="new-{{ $field }}">{{ $label }} *</label><input id="new-{{ $field }}" wire:model="{{ $field }}" required maxlength="{{ $field === 'name' ? 120 : 80 }}" class="mt-2 w-full rounded-xl border-gray-300">@error($field)<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror</div>
            @endforeach
            <x-location-choice :items="$provinces" model="province_id" label="استان" :value="$province_id" />
            <x-location-choice :items="$cities" model="city" label="شهر" :value="$city" :disabled="$province_id === ''" />
            <button wire:loading.attr="disabled" class="rounded-xl bg-green-700 px-5 py-3 font-semibold text-white disabled:opacity-60">ثبت گلخانه و ادامه</button>
        </form>
    </section>
</div>
