@if ($substrateProfile)
    <section wire:key="substrate-profile-{{ $substrate_id }}" aria-labelledby="substrate-details-heading" class="mt-6 rounded-xl border border-gray-200 bg-white p-5">
        <p class="text-xs font-semibold text-green-700">جزئیات متناسب با بستر شما</p><h3 id="substrate-details-heading" class="mt-2 font-bold">{{ $substrateProfile['title'] }}</h3>
        <p class="mt-2 text-xs leading-6 text-gray-600">برای بررسی دقیق‌تر شرایط ریشه، این موارد را مشخص کنید. اگر اطلاعاتی ندارید همان گزینه را انتخاب کنید؛ داده حدسی وارد نکنید.</p>
        <div class="mt-5 grid gap-6 sm:grid-cols-2">
            @foreach ($substrateProfile['fields'] as $key => $field)
                <fieldset><legend class="text-sm font-semibold text-gray-800">{{ $field['label'] }} *</legend><div class="mt-3 space-y-2">@foreach ($field['options'] as $option => $label)<label class="flex items-center gap-3 text-sm text-gray-700"><input type="radio" wire:model="substrateDetails.{{ $key }}" value="{{ $option }}" class="border-gray-300 text-green-700 focus:ring-green-700">{{ $label }}</label>@endforeach</div>@error('substrateDetails.'.$key)<p role="alert" class="mt-2 text-xs text-red-700">{{ $message }}</p>@enderror</fieldset>
            @endforeach
        </div>
        <details class="mt-6"><summary class="cursor-pointer text-sm font-semibold text-green-700">مشخصات بیشتر و نتایج اندازه‌گیری (اختیاری)</summary><p class="mt-3 text-xs leading-6 text-gray-500">pH و EC فقط با تاریخ و روش اندازه‌گیری قابل تفسیر هستند. EC را با واحد میلی‌زیمنس بر سانتی‌متر وارد کنید.</p>
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                @foreach (['volume_l' => 'حجم بستر یا مخزن (لیتر)', 'ph' => 'اسیدیته (pH)', 'ec_ms_cm' => 'هدایت الکتریکی EC (mS/cm)'] as $key => $label)<div><label for="substrate-{{ $key }}" class="block text-sm font-semibold">{{ $label }}</label><input id="substrate-{{ $key }}" wire:model="substrateDetails.{{ $key }}" type="number" step="any" min="0" class="mt-2 w-full rounded-xl border-gray-300 text-sm focus:border-green-600 focus:ring-green-600">@error('substrateDetails.'.$key)<p role="alert" class="mt-2 text-xs text-red-700">{{ $message }}</p>@enderror</div>@endforeach
                <div><livewire:shared.jalali-picker wire:model="substrateDetails.measurement_date" label="تاریخ اندازه‌گیری" :key="'measurement-'.$substrate_id.'-'.($editingId ?? 'new')" />@error('substrateDetails.measurement_date')<p role="alert" class="mt-2 text-xs text-red-700">{{ $message }}</p>@enderror</div>
            </div>
            <fieldset class="mt-5"><legend class="text-sm font-semibold">روش اندازه‌گیری</legend><div class="mt-3 grid gap-3 sm:grid-cols-2">@foreach (['laboratory' => 'آزمایشگاه', 'direct' => 'اندازه‌گیری مستقیم محلول', 'pour_through' => 'روش زه‌آب (Pour-through)', 'extract_1_2' => 'عصاره ۱ به ۲', 'saturated_extract' => 'عصاره اشباع', 'unknown' => 'روش را نمی‌دانم'] as $key => $label)<label class="flex gap-3 text-sm"><input type="radio" wire:model="substrateDetails.measurement_method" value="{{ $key }}" class="text-green-700 focus:ring-green-700">{{ $label }}</label>@endforeach</div>@error('substrateDetails.measurement_method')<p role="alert" class="mt-2 text-xs text-red-700">{{ $message }}</p>@enderror</fieldset>
            <label for="substrate-composition" class="mt-5 block text-sm font-semibold">ترکیب و نسبت اجزا، یا مشخصات محصول مصرفی</label><textarea id="substrate-composition" wire:model="substrateDetails.composition" rows="2" maxlength="500" placeholder="مثلاً ۷۰٪ کوکوپیت و ۳۰٪ پرلیت؛ نام محصول یا مشخصات اسلب را هم می‌توانید بنویسید." class="mt-2 w-full rounded-xl border-gray-300 text-sm focus:border-green-600 focus:ring-green-600"></textarea>
        </details>
    </section>
@endif
